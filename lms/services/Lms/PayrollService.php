<?php
/**
 * PayrollService — chốt lương + quy trình duyệt giáo viên (Phase 2).
 *
 * Implement SRS §3.2:
 *  - closeMonth()/closeMonthAll(): BR-PAY-01 (thu nhập buổi) + BR-PAY-02 (áp
 *    phí đã hạ theo tick tháng trước) + BR-PAY-03 (ghi thuế qua TaxService).
 *  - monthSummary()/monthSummaryAll(): BR-PAY-04 (thực nhận).
 *  - setMonthStatus(): quy trình duyệt draft → pending → paid.
 *  - addAdjustment(): admin tạo penalty/bonus/adjustment thủ công.
 *
 * Quyết định thiết kế (plan §Quyết định):
 *  - Buổi tính tiền = DERIVED (Q1): status='finished' AND không còn
 *    session_students.status='submitted' (chưa chấm xong).
 *  - Tick không tự sinh transaction penalty (Q2) — phạt gián tiếp qua BR-PAY-02.
 *  - BR-PAY-02: chốt tháng M, nếu tick_thực_tế tháng M-1 >= ticks_to_demote_fee
 *    thì MỌI buổi của tháng M dùng fee_percentage_after_demote.
 *
 * Idempotency: SELECT ... FOR UPDATE trên row teacher + check đã tồn tại
 * salary_session của teacher+month (Q7).
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class PayrollService extends LmsService
{
    /** Trạng thái duyệt (theo thứ tự). */
    const STATUS_DRAFT   = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_PAID    = 'paid';

    const STATUS_FLOW = array(
        self::STATUS_DRAFT   => self::STATUS_PENDING,
        self::STATUS_PENDING => self::STATUS_PAID,
    );

    /** Type mà admin được tạo thủ công (Q2). */
    const ADJUSTMENT_TYPES = array('penalty', 'bonus', 'adjustment');

    /** @var TaxService */
    private $tax;

    /** @var TickService */
    private $tick;

    public function __construct(\LmsDb $db = null)
    {
        parent::__construct($db);
        $this->tax  = new TaxService($this->db);
        $this->tick = new TickService($this->db);
    }

    // ===================== BR-PAY-01: đóng lương tháng =====================

    /**
     * Sinh transactions cho 1 GV × 1 tháng (idempotent).
     *
     * @param int    $teacherId
     * @param string $month    'Y-m'
     * @param bool   $dryRun
     * @return array array('dry_run','sessions_payable','salary','tax','demoted',
     *                     'skipped','closed')
     */
    public function closeMonth($teacherId, $month, $dryRun = false)
    {
        $this->clearErrors();

        $teacherId = (int) $teacherId;

        if ($teacherId <= 0) {
            $this->addError('teacher_id', 'Giáo viên không hợp lệ.');
            return $this->emptyStats($dryRun);
        }
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            $this->addError('month', 'Tháng không hợp lệ (định dạng Y-m).');
            return $this->emptyStats($dryRun);
        }

        $this->db->beginTransaction();

        try {
            // Khóa row teacher (idempotency + tránh chốt song song).
            $teacher = $this->db->safeFetchOne(
                'select * from db_lms_teachers where user_id = ? for update',
                array($teacherId)
            );
            if (empty($teacher) || !is_array($teacher)) {
                $this->db->rollBack();
                $this->addError('teacher_id', 'Giáo viên không tồn tại.');
                return $this->emptyStats($dryRun);
            }

            // Đã chốt tháng này rồi → skip (idempotent).
            $done = $this->db->safeFetchOne(
                'select id from db_lms_transactions'
                . ' where teacher_id = ? and period_month = ? and type = ? limit 1',
                array($teacherId, $month, 'salary_session')
            );
            if (!empty($done)) {
                $this->db->commit();
                $stats          = $this->emptyStats($dryRun);
                $stats['skipped'] = 1;
                return $stats;
            }

            // ---- BR-PAY-02: % phí theo tick tháng trước ----
            $prevMonth  = $this->previousMonth($month);
            $prevTicks  = $this->tick->monthTickSummary($teacherId, $prevMonth);
            $demoteAt   = 0;
            $demoteFee  = null;
            $threshold  = $prevTicks['threshold'];
            if (!empty($threshold) && is_array($threshold)) {
                $demoteAt  = (int) $threshold['ticks_to_demote_fee'];
                $demoteFee = isset($threshold['fee_percentage_after_demote'])
                    ? (float) $threshold['fee_percentage_after_demote']
                    : null;
            }
            $demoted = ($demoteFee !== null && $prevTicks['real'] >= $demoteAt);

            // ---- BR-PAY-01: query buổi tính tiền (derived, Q1) ----
            $lastDay = date('Y-m-t', strtotime($month . '-01'));
            $rows    = $this->db->safeFetch(
                'select s.id, s.class_id, s.session_date, c.price_per_session,'
                . ' c.teacher_percentage, t.service_fee_percentage'
                . ' from db_lms_sessions s'
                . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
                . ' join db_lms_teachers t on t.user_id = s.teacher_id'
                . ' where s.teacher_id = ? and s.status = ?'
                . ' and s.session_date between ? and ?'
                . ' and not exists ('
                . '   select 1 from db_lms_session_students ss'
                . '   where ss.session_id = s.id and ss.status = ?'
                . ' )'
                . ' order by s.session_date asc, s.id asc',
                array($teacherId, 'finished', $month . '-01', $lastDay, 'submitted')
            );

            $stats             = $this->emptyStats($dryRun);
            $stats['demoted']  = $demoted;
            $stats['demote_reason'] = $demoted
                ? 'tick_thực_tế ' . $prevMonth . ' = ' . $prevTicks['real'] . ' >= ' . $demoteAt
                : '';

            if (empty($rows)) {
                $this->db->commit();
                return $stats;
            }

            $stats['sessions_payable'] = count($rows);
            $salary = 0.0;

            foreach ($rows as $row) {
                $price = (float) $row['price_per_session'];
                if ($demoted && $demoteFee !== null) {
                    $feePct = $demoteFee;
                } else {
                    $feePct = $row['teacher_percentage'] !== null
                        ? (float) $row['teacher_percentage']
                        : (float) $row['service_fee_percentage'];
                }
                $amount = round($price * $feePct / 100.0, 2);
                $salary += $amount;

                if ($dryRun) {
                    continue;
                }

                $ok = $this->db->insert('db_lms_transactions', array(
                    'teacher_id'       => $teacherId,
                    'session_id'       => (int) $row['id'],
                    'amount'           => $amount,
                    'type'             => 'salary_session',
                    'reason'           => 'Lương buổi ' . $row['session_date'],
                    'reference_id'     => null,
                    'transaction_date' => $row['session_date'],
                    'period_month'     => $month,
                    'created_by'       => null,
                    'status'           => self::STATUS_DRAFT,
                ));
                if (!$ok) {
                    throw new \RuntimeException('Không insert được salary_session.');
                }
            }

            $stats['salary'] = round($salary, 2);

            // ---- BR-PAY-03: thuế ----
            $taxable  = $this->tax->taxableIncomeForMonth($teacherId, $month);
            $commit08 = (int) $teacher['is_commit_08'] === 1;
            $tax      = $this->tax->computeTax($taxable, $commit08);
            $stats['tax'] = $tax;

            if (!$dryRun && $tax > 0) {
                $taxId = $this->tax->recordTax($teacherId, $month, $tax);
                if ($taxId === null) {
                    throw new \RuntimeException('Không ghi được thuế.');
                }
            }

            if (!$dryRun) {
                $this->audit('close_payroll_month', 'transactions', null, null, array(
                    'teacher_id'       => $teacherId,
                    'period_month'     => $month,
                    'sessions_payable' => $stats['sessions_payable'],
                    'salary'           => $stats['salary'],
                    'tax'              => $tax,
                    'demoted'          => $demoted,
                ), 'system_cron');
            }

            $this->db->commit();
            $stats['closed'] = !$dryRun;
            return $stats;
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->addError('system', $e->getMessage());
            return $this->emptyStats($dryRun);
        }
    }

    /**
     * Chốt cho mọi GV có buổi tính tiền trong tháng (dùng payroll cron).
     *
     * @param string $month
     * @param bool   $dryRun
     * @return array array('month','teachers','closed','skipped','salary_total',
     *                     'tax_total','details')
     */
    public function closeMonthAll($month, $dryRun = false)
    {
        $this->clearErrors();

        $summary = array(
            'month'        => $month,
            'dry_run'      => (bool) $dryRun,
            'teachers'     => 0,
            'closed'       => 0,
            'skipped'      => 0,
            'salary_total' => 0.0,
            'tax_total'    => 0.0,
            'details'      => array(),
        );

        if (!preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            $this->addError('month', 'Tháng không hợp lệ (định dạng Y-m).');
            return $summary;
        }

        $lastDay = date('Y-m-t', strtotime($month . '-01'));
        $teachers = $this->db->safeFetch(
            'select distinct s.teacher_id from db_lms_sessions s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' where s.status = ? and s.session_date between ? and ?'
            . ' order by s.teacher_id asc',
            array('finished', $month . '-01', $lastDay)
        );

        foreach ($teachers as $row) {
            $teacherId = (int) $row['teacher_id'];
            $stats     = $this->closeMonth($teacherId, $month, $dryRun);
            $summary['teachers']++;
            if (!empty($stats['skipped'])) {
                $summary['skipped']++;
            } elseif (!empty($stats['closed'])) {
                $summary['closed']++;
            }
            $summary['salary_total'] += (float) $stats['salary'];
            $summary['tax_total']    += (float) $stats['tax'];
            $summary['details'][]    = array('teacher_id' => $teacherId) + $stats;
        }

        $summary['salary_total'] = round($summary['salary_total'], 2);
        $summary['tax_total']    = round($summary['tax_total'], 2);

        return $summary;
    }

    // ===================== Tra cứu (ADM-07 / TCH-05) =====================

    /**
     * Bảng giao dịch 1 GV × 1 tháng (filter type).
     *
     * @param int    $teacherId
     * @param string $month
     * @param string $type '' = tất cả
     * @return array
     */
    public function listTransactions($teacherId, $month, $type = '')
    {
        $teacherId = (int) $teacherId;
        $type      = (string) $type;

        $where  = 'teacher_id = ? and period_month = ?';
        $params = array($teacherId, $month);

        if ($type !== '') {
            $where .= ' and type = ?';
            $params[] = $type;
        }

        return $this->db->safeFetch(
            'select * from db_lms_transactions where ' . $where
            . ' order by transaction_date asc, id asc',
            $params
        );
    }

    /**
     * Tổng hợp 1 GV × 1 tháng (BR-PAY-04).
     *
     * @param int    $teacherId
     * @param string $month
     * @return array array('tong_thu','tong_phat','tong_thuong','thue',
     *                     'thuc_nhan','status')
     */
    public function monthSummary($teacherId, $month)
    {
        $teacherId = (int) $teacherId;

        $row = $this->db->safeFetchOne(
            'select'
            . ' coalesce(sum(case when type in (?, ?, ?) then amount else 0 end), 0) as tong_thu,'
            . ' coalesce(sum(case when type = ? then amount else 0 end), 0) as thue_raw,'
            . ' coalesce(sum(case when type = ? then amount else 0 end), 0) as phat_raw,'
            . ' coalesce(sum(case when type = ? then amount else 0 end), 0) as tong_thuong'
            . ' from db_lms_transactions'
            . ' where teacher_id = ? and period_month = ?',
            array('salary_session', 'bonus', 'adjustment', 'tax', 'penalty', 'bonus', $teacherId, $month)
        );

        $tongThu    = isset($row['tong_thu']) ? (float) $row['tong_thu'] : 0.0;
        $thue       = abs(isset($row['thue_raw']) ? (float) $row['thue_raw'] : 0.0);
        $tongPhat   = abs(isset($row['phat_raw']) ? (float) $row['phat_raw'] : 0.0);
        $tongThuong = isset($row['tong_thuong']) ? (float) $row['tong_thuong'] : 0.0;

        return array(
            'tong_thu'    => round($tongThu, 2),
            'tong_phat'   => round($tongPhat, 2),
            'tong_thuong' => round($tongThuong, 2),
            'thue'        => round($thue, 2),
            'thuc_nhan'   => round($tongThu - $tongPhat - $thue, 2),
            'status'      => $this->monthStatus($teacherId, $month),
        );
    }

    /**
     * Trạng thái duyệt của teacher-month (lấy từ transaction gần nhất).
     *
     * @param int    $teacherId
     * @param string $month
     * @return string '' khi không có transaction
     */
    public function monthStatus($teacherId, $month)
    {
        $row = $this->db->safeFetchOne(
            'select status from db_lms_transactions'
            . ' where teacher_id = ? and period_month = ?'
            . ' order by id desc limit 1',
            array((int) $teacherId, $month)
        );
        return isset($row['status']) ? (string) $row['status'] : '';
    }

    /**
     * Tổng hợp tất cả GV × 1 tháng (ADM-07).
     *
     * @param string $month
     * @param int    $page
     * @param int    $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function monthSummaryAll($month, $page = 1, $perPage = 20)
    {
        $summary = array('items' => array(), 'total' => 0);

        if (!preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            return $summary;
        }

        $total = $this->db->safeFetchOne(
            'select count(distinct teacher_id) as c from db_lms_transactions where period_month = ?',
            array($month)
        );
        $summary['total'] = isset($total['c']) ? (int) $total['c'] : 0;

        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        // LIMIT/OFFSET inline (đã cast int) — PDO bind chuỗi sẽ lỗi syntax
        // trên MySQL 5.7 (xem listViolations).
        $teachers = $this->db->safeFetch(
            'select distinct teacher_id from db_lms_transactions where period_month = ?'
            . ' order by teacher_id asc limit ' . $offset . ', ' . $perPage,
            array($month)
        );

        foreach ($teachers as $row) {
            $teacherId = (int) $row['teacher_id'];
            $info      = $this->db->safeFetchOne(
                'select teacher_code, full_name from db_lms_teachers where user_id = ? limit 1',
                array($teacherId)
            );
            $item          = $this->monthSummary($teacherId, $month);
            $item['teacher_id']   = $teacherId;
            $item['teacher_code'] = isset($info['teacher_code']) ? $info['teacher_code'] : '';
            $item['full_name']    = isset($info['full_name']) ? $info['full_name'] : '';
            $summary['items'][]   = $item;
        }

        return $summary;
    }

    // ===================== Quy trình duyệt =====================

    /**
     * Chuyển trạng thái cả teacher-month: draft → pending → paid (Q6).
     * Atomic: cập nhật mọi transaction của teacher+month; set paid_at khi paid.
     * Không có reverse.
     *
     * @param int    $teacherId
     * @param string $month
     * @param string $newStatus
     * @return bool
     */
    public function setMonthStatus($teacherId, $month, $newStatus)
    {
        $this->clearErrors();

        $teacherId = (int) $teacherId;
        $newStatus = (string) $newStatus;

        if (!isset(self::STATUS_FLOW[$newStatus]) && !in_array($newStatus, self::STATUS_FLOW, true)) {
            $this->addError('status', 'Trạng thái không hợp lệ.');
            return false;
        }

        $current = $this->monthStatus($teacherId, $month);
        if ($current === '') {
            $this->addError('month', 'Tháng này chưa có giao dịch nào.');
            return false;
        }
        if (!isset(self::STATUS_FLOW[$current])) {
            // Đã 'paid' (hoặc trạng thái không có bước tiếp).
            $this->addError(
                'status',
                'Trạng thái hiện tại "' . $current . '" không thể chuyển tiếp.'
            );
            return false;
        }
        if (self::STATUS_FLOW[$current] !== $newStatus) {
            $this->addError(
                'status',
                'Không thể chuyển từ "' . $current . '" sang "' . $newStatus . '".'
            );
            return false;
        }

        $this->db->beginTransaction();

        try {
            $data = array('status' => $newStatus);
            if ($newStatus === self::STATUS_PAID) {
                $data['paid_at'] = date('Y-m-d H:i:s');
            }

            // Cập nhật tất cả transaction của teacher+month.
            $ok = $this->db->update(
                'db_lms_transactions',
                $data,
                'teacher_id = ? and period_month = ?',
                array($teacherId, $month)
            );
            if (!$ok) {
                $this->db->rollBack();
                $this->addError('system', 'Không cập nhật được giao dịch.');
                return false;
            }

            $this->audit('payroll_set_status', 'transactions', null, array(
                'teacher_id'   => $teacherId,
                'period_month' => $month,
                'from'         => $current,
            ), array(
                'teacher_id'   => $teacherId,
                'period_month' => $month,
                'to'           => $newStatus,
            ), 'admin');

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->addError('system', $e->getMessage());
            return false;
        }
    }

    // ===================== Admin: điều chỉnh thủ công (Q2) =====================

    /**
     * Tạo penalty / bonus / adjustment thủ công.
     *
     * @param int    $teacherId
     * @param string $month
     * @param string $type    penalty | bonus | adjustment
     * @param float  $amount  khác 0 (adjustment giữ dấu, penalty luôn âm, bonus luôn dương)
     * @param string $note
     * @param int    $actorId user_id admin
     * @return int|null id transaction hoặc null khi lỗi
     */
    public function addAdjustment($teacherId, $month, $type, $amount, $note, $actorId)
    {
        $this->clearErrors();

        $teacherId = (int) $teacherId;
        $type      = (string) $type;
        $amount    = (float) $amount;
        $note      = trim((string) $note);

        if (!in_array($type, self::ADJUSTMENT_TYPES, true)) {
            $this->addError('type', 'Loại điều chỉnh không hợp lệ.');
            return null;
        }
        if ($amount == 0) {
            $this->addError('amount', 'Số tiền phải khác 0.');
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            $this->addError('month', 'Tháng không hợp lệ (định dạng Y-m).');
            return null;
        }

        $teacher = $this->db->safeFetchOne(
            'select user_id from db_lms_teachers where user_id = ? limit 1',
            array($teacherId)
        );
        if (empty($teacher) || !is_array($teacher)) {
            $this->addError('teacher_id', 'Giáo viên không tồn tại.');
            return null;
        }

        $createdBy = null;
        if ((int) $actorId > 0) {
            $admin = $this->db->safeFetchOne(
                'select user_id from db_lms_admins where user_id = ? limit 1',
                array((int) $actorId)
            );
            if (empty($admin) || !is_array($admin)) {
                $this->addError('actor', 'Tài khoản admin không hợp lệ.');
                return null;
            }
            $createdBy = (int) $actorId;
        }

        // Dấu theo loại: penalty luôn trừ, bonus luôn cộng, adjustment giữ dấu.
        if ($type === 'penalty') {
            $amount = -abs($amount);
        } elseif ($type === 'bonus') {
            $amount = abs($amount);
        }

        $ok = $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $teacherId,
            'session_id'       => null,
            'amount'           => $amount,
            'type'             => $type,
            'reason'           => $note !== '' ? $note : null,
            'reference_id'     => null,
            'transaction_date' => date('Y-m-d'),
            'period_month'     => $month,
            'created_by'       => $createdBy,
            'status'           => self::STATUS_DRAFT,
        ));
        if (!$ok) {
            $this->addError('system', 'Không thể thêm điều chỉnh. Vui lòng thử lại.');
            return null;
        }

        $id = (int) $this->db->insertId;
        $this->audit('add_adjustment', 'transactions', $id, null, array(
            'teacher_id'   => $teacherId,
            'period_month' => $month,
            'type'         => $type,
            'amount'       => $amount,
        ), 'admin');

        return $id;
    }

    // ===================== Helpers =====================

    /**
     * Tháng trước (VD '2026-09' → '2026-08', '2026-01' → '2025-12').
     *
     * @param string $month 'Y-m'
     * @return string
     */
    public function previousMonth($month)
    {
        $ts = strtotime((string) $month . '-01 -1 month');
        return $ts !== false ? date('Y-m', $ts) : date('Y-m');
    }

    private function emptyStats($dryRun)
    {
        return array(
            'dry_run'         => (bool) $dryRun,
            'sessions_payable' => 0,
            'salary'          => 0.0,
            'tax'             => 0.0,
            'demoted'         => false,
            'demote_reason'   => '',
            'skipped'         => 0,
            'closed'          => false,
        );
    }
}
