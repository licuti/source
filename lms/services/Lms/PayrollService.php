<?php
/**
 * PayrollService — chốt lương + quy trình duyệt giáo viên (Phase 2).
 *
 * Implement SRS §3.2:
 *  - closeMonth()/closeMonthAll(): BR-PAY-01 (thu nhập buổi) + BR-PAY-02 (áp
 *    phí đã hạ theo tick tháng trước) + BR-PAY-03 (ghi thuế qua TaxService).
 *  - monthSummary(): BR-PAY-04 (thực nhận).
 *  - setMonthStatus(): quy trình duyệt draft → pending → paid.
 *  - addAdjustment(): admin tạo penalty/bonus/adjustment thủ công.
 *
 * Quyết định thiết kế (plan §Quyết định):
 *  - Buổi tính tiền = DERIVED (Q1): status='finished' AND không còn
 *    session_students.status='submitted' (chưa chấm xong).
 *  - Tick không tự sinh transaction penalty (Q2) — phạt gián tiếp qua BR-PAY-02.
 *  - BR-PAY-02: chốt tháng M, nếu tick_thực_tế tháng M-1 >= ticks_to_demote_fee
 *    thì MỌI buổi của tháng M dùng fee_percentage_after_demote. Đây là phần
 *    trăm trực tiếp; không được phép làm tỷ lệ thực nhận tăng so với tỷ lệ
 *    hiện hành của lớp/GV.
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
    const STATUS_MIXED   = 'mixed';

    const STATUS_FLOW = array(
        self::STATUS_DRAFT   => self::STATUS_PENDING,
        self::STATUS_PENDING => self::STATUS_PAID,
    );

    /** Type mà admin được tạo thủ công (Q2 + §6.5 mục 12). */
    const ADJUSTMENT_TYPES = array('penalty', 'bonus', 'adjustment', 'deposit_deduct');

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
                'select s.id, s.class_id, s.session_date,'
                . ' s.price_per_session as session_price_per_session,'
                . ' s.teacher_percentage as session_teacher_percentage,'
                . ' cc.price_per_session as cycle_price_per_session,'
                . ' cc.teacher_percentage as cycle_teacher_percentage,'
                . ' c.price_per_session, c.teacher_percentage, t.service_fee_percentage'
                . ' from db_lms_sessions s'
                . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
                . ' left join db_lms_course_cycles cc on cc.id = s.cycle_id'
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
                if ($row['session_price_per_session'] !== null) {
                    $price = (float) $row['session_price_per_session'];
                } elseif (isset($row['cycle_price_per_session']) && $row['cycle_price_per_session'] !== null) {
                    $price = (float) $row['cycle_price_per_session'];
                } else {
                    $price = (float) $row['price_per_session'];
                }

                if ($row['session_teacher_percentage'] !== null) {
                    $baseFeePct = (float) $row['session_teacher_percentage'];
                } elseif (isset($row['cycle_teacher_percentage']) && $row['cycle_teacher_percentage'] !== null) {
                    $baseFeePct = (float) $row['cycle_teacher_percentage'];
                } elseif ($row['teacher_percentage'] !== null) {
                    $baseFeePct = (float) $row['teacher_percentage'];
                } else {
                    $baseFeePct = (float) $row['service_fee_percentage'];
                }

                if ($demoted && $demoteFee !== null) {
                    // The configured value is a direct percentage, but a bad
                    // threshold must never turn a demotion into an increase.
                    $feePct = min($demoteFee, $baseFeePct);
                } else {
                    $feePct = $baseFeePct;
                }
                $amount = round($price * $feePct / 100.0, 2);
                $salary += $amount;

                if ($dryRun) {
                    continue;
                }

                if ($row['session_price_per_session'] === null || $row['session_teacher_percentage'] === null) {
                    $this->db->update(
                        'db_lms_sessions',
                        array(
                            'price_per_session'  => $price,
                            'teacher_percentage' => $feePct,
                        ),
                        'id = ?',
                        array((int) $row['id'])
                    );
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
     * Trạng thái duyệt của teacher-month.
     * Nếu trong cùng kỳ có nhiều status khác nhau (VD: đã thanh toán một phần tháng
     * qua modal), trả về 'mixed' (§6.2.4, §6.5 mục 5).
     *
     * @param int    $teacherId
     * @param string $month
     * @return string '' khi không có transaction
     */
    public function monthStatus($teacherId, $month)
    {
        $rows = $this->db->safeFetch(
            'select distinct status from db_lms_transactions'
            . ' where teacher_id = ? and period_month = ?',
            array((int) $teacherId, $month)
        );
        if (empty($rows)) {
            return '';
        }
        if (count($rows) > 1) {
            return self::STATUS_MIXED;
        }
        return isset($rows[0]['status']) ? (string) $rows[0]['status'] : '';
    }

    // ===================== Báo cáo phí DV theo khoảng ngày (ADM-07) =====================

    /**
     * Tìm GV theo mã, kèm liên hệ + avatar cho báo cáo phí dịch vụ.
     *
     * @param string $code teacher_code
     * @return array rỗng khi không tìm thấy
     */
    public function findTeacherByCode($code)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return array();
        }

        $row = $this->db->safeFetchOne(
            'select t.user_id, t.teacher_code, t.full_name, t.is_commit_08,'
            . ' u.phone, u.email, p.avatar'
            . ' from db_lms_teachers t'
            . ' join db_lms_users u on u.id = t.user_id'
            . ' left join db_lms_teacher_profiles p on p.teacher_id = t.user_id'
            . ' where t.teacher_code = ? limit 1',
            array($code)
        );

        return is_array($row) ? $row : array();
    }

    /**
     * Giao dịch của 1 GV trong khoảng ngày (kèm giờ buổi học + lớp + HV).
     *
     * @param int    $teacherId
     * @param string $dateFrom 'Y-m-d'
     * @param string $dateTo   'Y-m-d'
     * @param int    $studentId >0: chỉ lấy giao dịch thuộc lớp có HV đang active
     * @return array
     */
    public function transactionsByRange($teacherId, $dateFrom, $dateTo, $studentId = 0)
    {
        if (!$this->isValidDate($dateFrom) || !$this->isValidDate($dateTo)) {
            return array();
        }

        $where  = array('t.teacher_id = ?', 't.transaction_date >= ?', 't.transaction_date <= ?');
        $params = array((int) $teacherId, (string) $dateFrom, (string) $dateTo);

        $studentId = (int) $studentId;
        if ($studentId > 0) {
            $where[]  = 'exists (select 1 from db_lms_class_students cs2'
                . ' where cs2.class_id = c.id and cs2.student_id = ? and cs2.status = ?)';
            $params[] = $studentId;
            $params[] = 'active';
        }

        return $this->db->safeFetch(
            'select t.id, t.session_id, t.amount, t.type, t.reason, t.transaction_date,'
            . ' t.period_month, t.status, t.paid_at, t.payment_batch_id, t.payment_note,'
            . ' t.created_at, s.start_time, s.end_time, s.session_date,'
            . ' coalesce(s.price_per_session, c.price_per_session) as price_per_session,'
            . ' coalesce(s.teacher_percentage, c.teacher_percentage) as teacher_percentage,'
            . ' c.id as class_id, c.class_code, c.name as class_name, c.class_type,'
            . ' (select group_concat(st.full_name order by st.full_name separator \', \')'
            . '   from db_lms_class_students cs'
            . '   join db_lms_students st on st.user_id = cs.student_id'
            . '  where cs.class_id = c.id and cs.status = \'active\') as student_names'
            . ' from db_lms_transactions t'
            . ' left join db_lms_sessions s on s.id = t.session_id'
            . ' left join db_lms_classes c on c.id = s.class_id'
            . ' where ' . implode(' and ', $where)
            . ' order by t.transaction_date desc, s.start_time desc, t.id desc',
            $params
        );
    }

    /**
     * Tổng hợp phí dịch vụ từ danh sách giao dịch.
     *
     * Quy ước (BR-PAY-03/04):
     *  - tong_thu    = salary_session + bonus + adjustment (mang dấu)
     *  - ky_quy      = deposit_deduct (mang dấu)
     *  - vi_pham     = |penalty|
     *  - phi_cong_them = bonus + adjustment (mang dấu)
     *  - thue        = |tax| thực tế đã ghi
     *  - thue_rate   = 0% nếu cam kết 08 hoặc thu nhập < 5tr, ngược lại 10%
     *  - thuc_nhan   = tong_thu + ky_quy - vi_pham - thue (khớp tổng các dòng
     *                  giao dịch hiển thị; ký quỹ mang dấu âm khi bị trừ)
     *
     * @param array $items
     * @param bool  $isCommit08
     * @return array
     */
    public function feeSummary(array $items, $isCommit08 = false)
    {
        $tongThu       = 0.0;
        $kyQuy         = 0.0;
        $viPham        = 0.0;
        $phiCongThem   = 0.0;
        $thue          = 0.0;
        $daThanhToan   = 0.0;
        $choThanhToan  = 0.0;

        foreach ($items as $row) {
            $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;
            $type   = isset($row['type']) ? (string) $row['type'] : '';
            $status = isset($row['status']) ? (string) $row['status'] : 'draft';

            if ($status === self::STATUS_PAID) {
                $daThanhToan += $amount;
            } else {
                $choThanhToan += $amount;
            }

            switch ($type) {
                case 'salary_session':
                    $tongThu += $amount;
                    break;
                case 'bonus':
                    $tongThu     += $amount;
                    $phiCongThem += $amount;
                    break;
                case 'adjustment':
                    $tongThu     += $amount;
                    $phiCongThem += $amount;
                    break;
                case 'penalty':
                    $viPham += abs($amount);
                    break;
                case 'tax':
                    $thue += abs($amount);
                    break;
                case 'deposit_deduct':
                    $kyQuy += $amount;
                    break;
            }
        }

        $rate = 0.0;
        if (!$isCommit08 && $tongThu >= TaxService::TAX_FREE_THRESHOLD) {
            $rate = TaxService::TAX_RATE * 100;
        }

        return array(
            'count'          => count($items),
            'tong_thu'       => round($tongThu, 2),
            'ky_quy'         => round($kyQuy, 2),
            'vi_pham'        => round($viPham, 2),
            'phi_cong_them'  => round($phiCongThem, 2),
            'thue'           => round($thue, 2),
            'thue_rate'      => round($rate, 2),
            'thuc_nhan'      => round($tongThu + $kyQuy - $viPham - $thue, 2),
            'da_thanh_toan'  => round($daThanhToan, 2),
            'cho_thanh_toan' => round($choThanhToan, 2),
        );
    }

    /**
     * Tổng hợp phí dịch vụ của tất cả GV có giao dịch trong khoảng ngày.
     *
     * @param string $dateFrom 'Y-m-d'
     * @param string $dateTo   'Y-m-d'
     * @return array Danh sách item: teacher info + summary keys.
     */
    public function allFeeSummaries($dateFrom, $dateTo)
    {
        $items = array();

        if (!$this->isValidDate($dateFrom) || !$this->isValidDate($dateTo)) {
            return $items;
        }

        $rows = $this->db->safeFetch(
            'select t.teacher_id, tc.teacher_code, tc.full_name, tc.is_commit_08,'
            . ' u.phone, u.email,'
            . ' coalesce(sum(case when t.type in (?, ?, ?) then t.amount else 0 end), 0) as tong_thu,'
            . ' coalesce(sum(case when t.type = ? then t.amount else 0 end), 0) as ky_quy,'
            . ' coalesce(sum(case when t.type = ? then t.amount else 0 end), 0) as vi_pham,'
            . ' coalesce(sum(case when t.type in (?, ?) then t.amount else 0 end), 0) as phi_cong_them,'
            . ' coalesce(sum(case when t.type = ? then t.amount else 0 end), 0) as thue'
            . ' from db_lms_transactions t'
            . ' join db_lms_teachers tc on tc.user_id = t.teacher_id'
            . ' join db_lms_users u on u.id = t.teacher_id'
            . ' where t.transaction_date >= ? and t.transaction_date <= ?'
            . ' group by t.teacher_id, tc.teacher_code, tc.full_name, tc.is_commit_08,'
            . ' u.phone, u.email'
            . ' order by tc.teacher_code asc',
            array(
                'salary_session', 'bonus', 'adjustment',
                'deposit_deduct', 'penalty', 'bonus', 'adjustment', 'tax',
                (string) $dateFrom, (string) $dateTo,
            )
        );

        foreach ($rows as $row) {
            $tongThu     = (float) $row['tong_thu'];
            $kyQuy       = (float) $row['ky_quy'];
            $viPham      = abs((float) $row['vi_pham']);
            $thue        = abs((float) $row['thue']);
            $rate        = 0.0;
            $isCommit08  = (int) $row['is_commit_08'] === 1;
            if (!$isCommit08 && $tongThu >= TaxService::TAX_FREE_THRESHOLD) {
                $rate = TaxService::TAX_RATE * 100;
            }

            $items[] = array(
                'teacher_id'    => (int) $row['teacher_id'],
                'teacher_code'  => (string) $row['teacher_code'],
                'full_name'     => (string) $row['full_name'],
                'phone'         => (string) $row['phone'],
                'email'         => (string) $row['email'],
                'tong_thu'      => round($tongThu, 2),
                'ky_quy'        => round($kyQuy, 2),
                'vi_pham'       => round($viPham, 2),
                'phi_cong_them' => round((float) $row['phi_cong_them'], 2),
                'thue'          => round($thue, 2),
                'thue_rate'     => round($rate, 2),
                'thuc_nhan'     => round($tongThu + $kyQuy - $viPham - $thue, 2),
            );
        }

        return $items;
    }

    /**
     * Buổi dạy của 1 GV trong khoảng ngày (xuất file số buổi dạy).
     *
     * @param int    $teacherId
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function sessionsByRange($teacherId, $dateFrom, $dateTo)
    {
        if (!$this->isValidDate($dateFrom) || !$this->isValidDate($dateTo)) {
            return array();
        }

        return $this->db->safeFetch(
            'select s.id, s.session_date, s.start_time, s.end_time, s.status,'
            . ' coalesce(s.price_per_session, c.price_per_session) as price_per_session,'
            . ' coalesce(s.teacher_percentage, c.teacher_percentage) as teacher_percentage,'
            . ' s.lesson_summary, c.class_code, c.name as class_name'
            . ' from db_lms_sessions s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' where s.teacher_id = ? and s.session_date >= ? and s.session_date <= ?'
            . ' and s.status <> ?'
            . ' order by s.session_date asc, s.start_time asc, s.id asc',
            array((int) $teacherId, (string) $dateFrom, (string) $dateTo, 'cancelled')
        );
    }

    private function isValidDate($date)
    {
        $date = (string) $date;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
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

        // Dấu theo loại: penalty / deposit_deduct luôn trừ, bonus luôn cộng, adjustment giữ dấu.
        if ($type === 'penalty' || $type === 'deposit_deduct') {
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

    // ===================== Ví Phí Dịch Vụ & Payment Batch (§6) =====================

    /**
     * Đồng bộ các buổi học đủ điều kiện trả thù lao (status='finished' và không còn BTVN 'submitted')
     * nhưng chưa có bản ghi salary_session trong db_lms_transactions (§6.2.5, §6.7 bước 2).
     *
     * Idempotent: kiểm tra NOT EXISTS (teacher_id, session_id, 'salary_session')
     * và bảo vệ thêm bằng UNIQUE KEY uk_txn_teacher_session_type.
     *
     * @param array       $teacherIds Rỗng = mọi giáo viên
     * @param string|null $dateFrom   'Y-m-d' hoặc null
     * @param string|null $dateTo     'Y-m-d' hoặc null
     * @return array array('synced_count' => int, 'synced_amount' => float)
     */
    public function syncPayableSessionsForRange(array $teacherIds = array(), $dateFrom = null, $dateTo = null)
    {
        $cleanIds = array();
        foreach ($teacherIds as $tid) {
            $tid = (int) $tid;
            if ($tid > 0) {
                $cleanIds[$tid] = $tid;
            }
        }
        $cleanIds = array_values($cleanIds);

        $where  = array('s.status = ?');
        $params = array('finished');

        if (!empty($cleanIds)) {
            $placeholders = implode(', ', array_fill(0, count($cleanIds), '?'));
            $where[]      = 's.teacher_id in (' . $placeholders . ')';
            $params       = array_merge($params, $cleanIds);
        }

        if ($dateFrom !== null && $dateFrom !== '' && $this->isValidDate($dateFrom)) {
            $where[]  = 's.session_date >= ?';
            $params[] = (string) $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '' && $this->isValidDate($dateTo)) {
            $where[]  = 's.session_date <= ?';
            $params[] = (string) $dateTo;
        }

        $where[]  = 'not exists (select 1 from db_lms_session_students ss where ss.session_id = s.id and ss.status = ?)';
        $params[] = 'submitted';

        $where[]  = 'not exists (select 1 from db_lms_transactions tr where tr.teacher_id = s.teacher_id and tr.session_id = s.id and tr.type = ?)';
        $params[] = 'salary_session';

        $rows = $this->db->safeFetch(
            'select s.id, s.teacher_id, s.class_id, s.session_date,'
            . ' s.price_per_session as session_price_per_session,'
            . ' s.teacher_percentage as session_teacher_percentage,'
            . ' cc.price_per_session as cycle_price_per_session,'
            . ' cc.teacher_percentage as cycle_teacher_percentage,'
            . ' c.price_per_session, c.teacher_percentage, t.service_fee_percentage'
            . ' from db_lms_sessions s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' left join db_lms_course_cycles cc on cc.id = s.cycle_id'
            . ' join db_lms_teachers t on t.user_id = s.teacher_id'
            . ' where ' . implode(' and ', $where)
            . ' order by s.session_date asc, s.id asc',
            $params
        );

        if (empty($rows)) {
            return array('synced_count' => 0, 'synced_amount' => 0.0);
        }

        $demoteCache  = array();
        $syncedCount  = 0;
        $syncedAmount = 0.0;

        foreach ($rows as $row) {
            $teacherId   = (int) $row['teacher_id'];
            $sessionDate = (string) $row['session_date'];
            $periodMonth = substr($sessionDate, 0, 7);
            $cacheKey    = $teacherId . ':' . $periodMonth;

            if (!isset($demoteCache[$cacheKey])) {
                $prevMonth = $this->previousMonth($periodMonth);
                $prevTicks = $this->tick->monthTickSummary($teacherId, $prevMonth);
                $demoteAt  = 0;
                $demoteFee = null;
                $threshold = isset($prevTicks['threshold']) ? $prevTicks['threshold'] : null;
                if (!empty($threshold) && is_array($threshold)) {
                    $demoteAt  = (int) $threshold['ticks_to_demote_fee'];
                    $demoteFee = isset($threshold['fee_percentage_after_demote'])
                        ? (float) $threshold['fee_percentage_after_demote']
                        : null;
                }
                $demoteCache[$cacheKey] = array(
                    'demoted'    => ($demoteFee !== null && $prevTicks['real'] >= $demoteAt),
                    'demote_fee' => $demoteFee,
                );
            }

            $demoteInfo = $demoteCache[$cacheKey];

            if ($row['session_price_per_session'] !== null) {
                $price = (float) $row['session_price_per_session'];
            } elseif (isset($row['cycle_price_per_session']) && $row['cycle_price_per_session'] !== null) {
                $price = (float) $row['cycle_price_per_session'];
            } else {
                $price = (float) $row['price_per_session'];
            }

            if ($row['session_teacher_percentage'] !== null) {
                $baseFeePct = (float) $row['session_teacher_percentage'];
            } elseif (isset($row['cycle_teacher_percentage']) && $row['cycle_teacher_percentage'] !== null) {
                $baseFeePct = (float) $row['cycle_teacher_percentage'];
            } elseif ($row['teacher_percentage'] !== null) {
                $baseFeePct = (float) $row['teacher_percentage'];
            } else {
                $baseFeePct = (float) $row['service_fee_percentage'];
            }

            if ($demoteInfo['demoted'] && $demoteInfo['demote_fee'] !== null) {
                $feePct = min($demoteInfo['demote_fee'], $baseFeePct);
            } else {
                $feePct = $baseFeePct;
            }

            $amount = round($price * $feePct / 100.0, 2);

            if ($row['session_price_per_session'] === null || $row['session_teacher_percentage'] === null) {
                $this->db->update(
                    'db_lms_sessions',
                    array(
                        'price_per_session'  => $price,
                        'teacher_percentage' => $feePct,
                    ),
                    'id = ?',
                    array((int) $row['id'])
                );
            }

            // Kiểm tra lại lần cuối phòng race condition trước khi insert
            $exists = $this->db->safeFetchOne(
                'select id from db_lms_transactions where teacher_id = ? and session_id = ? and type = ? limit 1',
                array($teacherId, (int) $row['id'], 'salary_session')
            );
            if (!empty($exists)) {
                continue;
            }

            $ok = $this->db->insert('db_lms_transactions', array(
                'teacher_id'       => $teacherId,
                'session_id'       => (int) $row['id'],
                'amount'           => $amount,
                'type'             => 'salary_session',
                'reason'           => 'Lương buổi ' . $sessionDate,
                'reference_id'     => null,
                'transaction_date' => $sessionDate,
                'period_month'     => $periodMonth,
                'created_by'       => null,
                'status'           => self::STATUS_DRAFT,
            ));
            if ($ok) {
                $syncedCount++;
                $syncedAmount += $amount;
            }
        }

        return array(
            'synced_count'  => $syncedCount,
            'synced_amount' => round($syncedAmount, 2),
        );
    }

    /**
     * Tính số dư phí dịch vụ chờ thanh toán (status != 'paid', toàn bộ các loại giao dịch,
     * mọi tháng) cho danh sách giáo viên (§6.2.1, §6.2.2, §6.5 mục 1 & 2).
     *
     * @param array $teacherIds Rỗng = mọi giáo viên
     * @param bool  $syncFirst  Có tự động sync các buổi finished chưa có transaction trước không
     * @return array Map: teacher_id => float unpaid_amount
     */
    public function unpaidBalancesForTeachers(array $teacherIds = array(), $syncFirst = true)
    {
        $cleanIds = array();
        foreach ($teacherIds as $tid) {
            $tid = (int) $tid;
            if ($tid > 0) {
                $cleanIds[$tid] = $tid;
            }
        }
        $cleanIds = array_values($cleanIds);

        if ($syncFirst) {
            $this->syncPayableSessionsForRange($cleanIds);
        }

        $where  = array('status <> ?');
        $params = array(self::STATUS_PAID);

        if (!empty($cleanIds)) {
            $placeholders = implode(', ', array_fill(0, count($cleanIds), '?'));
            $where[]      = 'teacher_id in (' . $placeholders . ')';
            $params       = array_merge($params, $cleanIds);
        }

        $rows = $this->db->safeFetch(
            'select teacher_id, coalesce(sum(amount), 0) as unpaid_amount'
            . ' from db_lms_transactions'
            . ' where ' . implode(' and ', $where)
            . ' group by teacher_id',
            $params
        );

        $map = array();
        foreach ($cleanIds as $tid) {
            $map[$tid] = 0.0;
        }
        foreach ($rows as $r) {
            $map[(int) $r['teacher_id']] = round((float) $r['unpaid_amount'], 2);
        }

        return $map;
    }

    /**
     * Lấy ngày phát sinh giao dịch chưa thanh toán sớm nhất (dùng để gợi ý date_from mặc định trên modal).
     *
     * @param array $teacherIds
     * @return string|null 'Y-m-d' hoặc null nếu không có
     */
    public function getEarliestUnpaidDate(array $teacherIds = array())
    {
        $cleanIds = array();
        foreach ($teacherIds as $tid) {
            $tid = (int) $tid;
            if ($tid > 0) {
                $cleanIds[$tid] = $tid;
            }
        }
        $cleanIds = array_values($cleanIds);

        // Sync trước để đảm bảo phát hiện các buổi finished cũ chưa có transaction
        if (!empty($cleanIds)) {
            $this->syncPayableSessionsForRange($cleanIds);
        }

        $where  = array('status <> ?');
        $params = array(self::STATUS_PAID);

        if (!empty($cleanIds)) {
            $placeholders = implode(', ', array_fill(0, count($cleanIds), '?'));
            $where[]      = 'teacher_id in (' . $placeholders . ')';
            $params       = array_merge($params, $cleanIds);
        }

        $row = $this->db->safeFetchOne(
            'select min(transaction_date) as min_date from db_lms_transactions where ' . implode(' and ', $where),
            $params
        );

        return !empty($row['min_date']) ? (string) $row['min_date'] : null;
    }

    /**
     * Số dư phí dịch vụ chờ thanh toán của 1 giáo viên.
     *
     * @param int  $teacherId
     * @param bool $syncFirst
     * @return float
     */
    public function unpaidBalanceForTeacher($teacherId, $syncFirst = true)
    {
        $teacherId = (int) $teacherId;
        if ($teacherId <= 0) {
            return 0.0;
        }
        $map = $this->unpaidBalancesForTeachers(array($teacherId), $syncFirst);
        return isset($map[$teacherId]) ? (float) $map[$teacherId] : 0.0;
    }

    /**
     * Thanh toán phí dịch vụ cho danh sách giáo viên (§6.4, §6.7 bước 2).
     *
     * - Tự động sync các buổi đã finished đến date_to nhưng chưa có transaction.
     * - Tất toán toàn bộ công nợ chờ thanh toán phát sinh đến hết ngày date_to (transaction_date <= date_to)
     *   để giải quyết triệt để lỗi bỏ sót nợ cũ / backdate và đưa ví về 0 đ.
     * - Tự động trích nộp thuế TNCN (10%) nếu kỳ tháng đạt ngưỡng >= 5.000.000 đ mà chưa chốt thuế.
     * - Gom các batch của cùng lượt thanh toán vào một mã nhóm chung (group_code).
     * - Idempotent: nếu không còn giao dịch chưa thanh toán thì bỏ qua, không tạo batch trùng.
     *
     * @param array       $teacherIds
     * @param string      $dateFrom   'Y-m-d' (dùng cho hiển thị / ghi nhận khoảng thời gian)
     * @param string      $dateTo     'Y-m-d' (tất toán toàn bộ giao dịch <= date_to)
     * @param string      $note
     * @param int         $actorId
     * @param string|null $groupCode
     * @param bool        $autoDeductTax
     * @return array
     */
    public function payBatch(
        array $teacherIds,
        $dateFrom,
        $dateTo,
        $note = '',
        $actorId = 0,
        $groupCode = null,
        $autoDeductTax = true
    ) {
        $this->clearErrors();

        $cleanIds = array();
        foreach ($teacherIds as $tid) {
            $tid = (int) $tid;
            if ($tid > 0) {
                $cleanIds[$tid] = $tid;
            }
        }
        $cleanIds = array_values($cleanIds);

        if (empty($cleanIds)) {
            $this->addError('teacher_ids', 'Vui lòng chọn ít nhất một giáo viên.');
            return array('ok' => false);
        }

        $dateFrom = trim((string) $dateFrom);
        $dateTo   = trim((string) $dateTo);
        if (!$this->isValidDate($dateFrom) || !$this->isValidDate($dateTo)) {
            $this->addError('date_range', 'Khoảng ngày thanh toán không hợp lệ.');
            return array('ok' => false);
        }
        if ($dateFrom > $dateTo) {
            $tmp      = $dateFrom;
            $dateFrom = $dateTo;
            $dateTo   = $tmp;
        }

        if (empty($groupCode)) {
            $groupCode = 'GRP-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -6));
        }

        // Sync các buổi đủ điều kiện phát sinh tính đến hết date_to (không giới hạn chặn dưới date_from)
        $this->syncPayableSessionsForRange($cleanIds, null, $dateTo);

        $createdBy = (int) $actorId > 0 ? (int) $actorId : null;
        $note      = trim((string) $note);

        $this->db->beginTransaction();

        try {
            $paidTeachers     = 0;
            $skippedTeachers  = 0;
            $transactionsPaid = 0;
            $totalNetAmount   = 0.0;
            $batches          = array();
            $warnings         = array();

            foreach ($cleanIds as $teacherId) {
                $teacher = $this->db->safeFetchOne(
                    'select user_id, teacher_code, full_name, is_commit_08'
                    . ' from db_lms_teachers where user_id = ? for update',
                    array($teacherId)
                );
                if (empty($teacher) || !is_array($teacher)) {
                    $skippedTeachers++;
                    continue;
                }

                // Tất toán toàn bộ giao dịch chưa trả phát sinh đến date_to để đảm bảo ví về 0 đ
                $unpaidRows = $this->db->safeFetch(
                    'select id, amount, type, period_month, transaction_date'
                    . ' from db_lms_transactions'
                    . ' where teacher_id = ? and status <> ?'
                    . ' and transaction_date <= ?'
                    . ' order by transaction_date asc, id asc for update',
                    array($teacherId, self::STATUS_PAID, $dateTo)
                );

                if (empty($unpaidRows)) {
                    $skippedTeachers++;
                    continue;
                }

                $gross     = 0.0;
                $deduction = 0.0;
                $net       = 0.0;
                $txIds     = array();
                $months    = array();

                foreach ($unpaidRows as $tx) {
                    $amt     = (float) $tx['amount'];
                    $txIds[] = (int) $tx['id'];
                    $net    += $amt;
                    if ($amt >= 0) {
                        $gross += $amt;
                    } else {
                        $deduction += abs($amt);
                    }
                    if (!empty($tx['period_month'])) {
                        $months[(string) $tx['period_month']] = true;
                    }
                }

                // Tự động kiểm tra và trích nộp thuế TNCN (10%) nếu tháng đạt >= 5.000.000 đ chưa có dòng tax
                $isCommit08 = (int) $teacher['is_commit_08'] === 1;
                if (!$isCommit08) {
                    foreach (array_keys($months) as $m) {
                        $taxable = $this->tax->taxableIncomeForMonth($teacherId, $m);
                        if ($taxable >= TaxService::TAX_FREE_THRESHOLD) {
                            $hasTaxRow = $this->db->safeFetchOne(
                                'select id from db_lms_transactions where teacher_id = ? and period_month = ? and type = ? limit 1',
                                array($teacherId, $m, 'tax')
                            );
                            if (empty($hasTaxRow)) {
                                if ($autoDeductTax) {
                                    $taxAmt = $this->tax->computeTax($taxable, false);
                                    if ($taxAmt > 0) {
                                        $taxDate = min($dateTo, date('Y-m-t', strtotime($m . '-01')));
                                        $taxId   = $this->tax->recordTax($teacherId, $m, $taxAmt, $actorId);
                                        if ($taxId !== null) {
                                            $txIds[]    = (int) $taxId;
                                            $deduction += $taxAmt;
                                            $net       -= $taxAmt;
                                            $warnings[] = 'Đã tự động trích nộp thuế TNCN 10% (' . number_format($taxAmt) . ' đ) cho kỳ ' . $m . ' của GV ' . $teacher['teacher_code'] . '.';
                                        }
                                    }
                                } else {
                                    $warnings[] = 'CẢNH BÁO: GV ' . $teacher['teacher_code'] . ' có thu nhập chịu thuế kỳ ' . $m . ' (' . number_format($taxable) . ' đ) chưa được trích thuế TNCN.';
                                }
                            }
                        }
                    }
                }

                $gross     = round($gross, 2);
                $deduction = round($deduction, 2);
                $net       = round($net, 2);

                $batchDateFrom = !empty($unpaidRows) ? $unpaidRows[0]['transaction_date'] : $dateFrom;
                $tempCode      = 'PAY-' . date('Ym') . '-' . uniqid();
                $now           = date('Y-m-d H:i:s');

                $okBatch = $this->db->insert('db_lms_payment_batches', array(
                    'code'             => $tempCode,
                    'group_code'       => $groupCode,
                    'teacher_id'       => $teacherId,
                    'date_from'        => $batchDateFrom,
                    'date_to'          => $dateTo,
                    'gross_amount'     => $gross,
                    'deduction_amount' => $deduction,
                    'net_amount'       => $net,
                    'status'           => 'paid',
                    'note'             => $note !== '' ? $note : null,
                    'created_by'       => $createdBy,
                    'created_at'       => $now,
                ));
                if (!$okBatch) {
                    throw new \RuntimeException('Không thể khởi tạo phiên thanh toán.');
                }

                $batchId   = (int) $this->db->insertId;
                $batchCode = 'PAY-' . date('Ym') . '-' . str_pad((string) $batchId, 4, '0', STR_PAD_LEFT);
                $this->db->update(
                    'db_lms_payment_batches',
                    array('code' => $batchCode),
                    'id = ?',
                    array($batchId)
                );

                $paymentNote  = $note !== '' ? $note : ('Thanh toán phiên ' . $batchCode);
                $placeholders = implode(', ', array_fill(0, count($txIds), '?'));
                $updateParams = array_merge(
                    array(self::STATUS_PAID, $now, $batchId, $createdBy, $paymentNote),
                    $txIds
                );

                $okTx = $this->db->safeExec(
                    'update db_lms_transactions'
                    . ' set status = ?, paid_at = ?, payment_batch_id = ?, paid_by = ?, payment_note = ?'
                    . ' where id in (' . $placeholders . ')',
                    $updateParams
                );
                if (!$okTx) {
                    throw new \RuntimeException('Không thể cập nhật trạng thái giao dịch.');
                }

                $this->audit('pay_service_fee_batch', 'payment_batches', $batchId, null, array(
                    'batch_code'       => $batchCode,
                    'group_code'       => $groupCode,
                    'teacher_id'       => $teacherId,
                    'date_from'        => $batchDateFrom,
                    'date_to'          => $dateTo,
                    'gross_amount'     => $gross,
                    'deduction_amount' => $deduction,
                    'net_amount'       => $net,
                    'tx_count'         => count($txIds),
                ), 'admin');

                // Kiểm tra xem GV có còn giao dịch chưa trả sau date_to không để cảnh báo
                $remRow = $this->db->safeFetchOne(
                    'select coalesce(sum(amount), 0) as s, count(*) as c'
                    . ' from db_lms_transactions'
                    . ' where teacher_id = ? and status <> ?',
                    array($teacherId, self::STATUS_PAID)
                );
                $remAmt = isset($remRow['s']) ? (float) $remRow['s'] : 0.0;
                $remCnt = isset($remRow['c']) ? (int) $remRow['c'] : 0;
                if ($remCnt > 0) {
                    $warnings[] = 'GV ' . $teacher['teacher_code'] . ' vẫn còn ' . number_format($remAmt) . ' đ (' . $remCnt . ' khoản) phát sinh sau ngày ' . $dateTo . ' chưa thanh toán.';
                }

                $paidTeachers++;
                $transactionsPaid += count($txIds);
                $totalNetAmount   += $net;
                $batches[] = array(
                    'id'           => $batchId,
                    'code'         => $batchCode,
                    'group_code'   => $groupCode,
                    'teacher_id'   => $teacherId,
                    'teacher_code' => (string) $teacher['teacher_code'],
                    'full_name'    => (string) $teacher['full_name'],
                    'net_amount'   => $net,
                    'tx_count'     => count($txIds),
                );
            }

            $this->db->commit();

            return array(
                'ok'                => true,
                'group_code'        => $groupCode,
                'paid_teachers'     => $paidTeachers,
                'skipped_teachers'  => $skippedTeachers,
                'transactions_paid' => $transactionsPaid,
                'total_net_amount'  => round($totalNetAmount, 2),
                'batches'           => $batches,
                'warnings'          => $warnings,
            );
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->addError('system', $e->getMessage());
            return array('ok' => false);
        }
    }

    /**
     * Kiểm tra các giao dịch đã thanh toán (status = 'paid') trong khoảng ngày
     * trước khi hoàn tác ("Thêm tiền" / "Cộng lại các buổi") (§6.2.3).
     *
     * Phát hiện:
     *  - Các batch đã khóa sổ (is_locked = 1) → chặn hoàn tác.
     *  - Các batch bị chọn lệch một phần khoảng ngày (partial match) → yêu cầu xác nhận
     *    hoàn tác trọn vẹn cả phiên thanh toán để không làm lệch thuế/phạt.
     *
     * @param array  $teacherIds
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function inspectUnpayRange(array $teacherIds, $dateFrom, $dateTo)
    {
        $cleanIds = array();
        foreach ($teacherIds as $tid) {
            $tid = (int) $tid;
            if ($tid > 0) {
                $cleanIds[$tid] = $tid;
            }
        }
        $cleanIds = array_values($cleanIds);

        $empty = array(
            'paid_tx_count'   => 0,
            'locked_batches'  => array(),
            'partial_batches' => array(),
            'exact_batches'   => array(),
        );

        if (empty($cleanIds) || !$this->isValidDate($dateFrom) || !$this->isValidDate($dateTo)) {
            return $empty;
        }
        if ($dateFrom > $dateTo) {
            $tmp      = $dateFrom;
            $dateFrom = $dateTo;
            $dateTo   = $tmp;
        }

        $placeholders = implode(', ', array_fill(0, count($cleanIds), '?'));
        $paidRows = $this->db->safeFetch(
            'select id, teacher_id, payment_batch_id, amount, transaction_date'
            . ' from db_lms_transactions'
            . ' where teacher_id in (' . $placeholders . ')'
            . ' and status = ? and transaction_date >= ? and transaction_date <= ?',
            array_merge($cleanIds, array(self::STATUS_PAID, $dateFrom, $dateTo))
        );

        if (empty($paidRows)) {
            return $empty;
        }

        $batchIds = array();
        foreach ($paidRows as $r) {
            $bid = isset($r['payment_batch_id']) ? (int) $r['payment_batch_id'] : 0;
            if ($bid > 0) {
                $batchIds[$bid] = $bid;
            }
        }
        $batchIds = array_values($batchIds);

        $lockedBatches  = array();
        $partialBatches = array();
        $exactBatches   = array();

        if (!empty($batchIds)) {
            $bPlaceholders = implode(', ', array_fill(0, count($batchIds), '?'));
            $batches = $this->db->safeFetch(
                'select b.*, tc.teacher_code, tc.full_name'
                . ' from db_lms_payment_batches b'
                . ' join db_lms_teachers tc on tc.user_id = b.teacher_id'
                . ' where b.id in (' . $bPlaceholders . ')',
                $batchIds
            );

            foreach ($batches as $b) {
                $bid = (int) $b['id'];
                if ((int) $b['is_locked'] === 1) {
                    $lockedBatches[] = $b;
                    continue;
                }

                // Kiểm tra xem batch này có giao dịch nào nằm ngoài khoảng [dateFrom, dateTo] không
                $outsideRow = $this->db->safeFetchOne(
                    'select count(*) as c from db_lms_transactions'
                    . ' where payment_batch_id = ? and status = ?'
                    . ' and (transaction_date < ? or transaction_date > ?)',
                    array($bid, self::STATUS_PAID, $dateFrom, $dateTo)
                );
                $outsideCount = isset($outsideRow['c']) ? (int) $outsideRow['c'] : 0;

                if ($outsideCount > 0 || (string) $b['date_from'] < $dateFrom || (string) $b['date_to'] > $dateTo) {
                    $partialBatches[] = $b;
                } else {
                    $exactBatches[] = $b;
                }
            }
        }

        return array(
            'paid_tx_count'   => count($paidRows),
            'locked_batches'  => $lockedBatches,
            'partial_batches' => $partialBatches,
            'exact_batches'   => $exactBatches,
        );
    }

    /**
     * Hoàn tác (void) 1 phiên thanh toán theo ID (§6.7 bước 2).
     *
     * @param int    $batchId
     * @param string $reason
     * @param int    $actorId
     * @return array
     */
    public function voidBatch($batchId, $reason = '', $actorId = 0)
    {
        $this->clearErrors();

        $batchId   = (int) $batchId;
        $reason    = trim((string) $reason);
        $voidedBy  = (int) $actorId > 0 ? (int) $actorId : null;

        if ($batchId <= 0) {
            $this->addError('batch_id', 'Mã phiên thanh toán không hợp lệ.');
            return array('ok' => false);
        }

        $this->db->beginTransaction();

        try {
            $batch = $this->db->safeFetchOne(
                'select * from db_lms_payment_batches where id = ? for update',
                array($batchId)
            );
            if (empty($batch) || !is_array($batch)) {
                $this->db->rollBack();
                $this->addError('batch_id', 'Phiên thanh toán không tồn tại.');
                return array('ok' => false);
            }

            if ((string) $batch['status'] === 'void') {
                $this->db->commit();
                return array(
                    'ok'                    => true,
                    'skipped'               => 1,
                    'reverted_transactions' => 0,
                    'reverted_amount'       => 0.0,
                );
            }

            if ((int) $batch['is_locked'] === 1) {
                $this->db->rollBack();
                $this->addError('locked', 'Phiên thanh toán ' . $batch['code'] . ' đã khóa sổ, không thể hoàn tác.');
                return array('ok' => false);
            }

            $txRows = $this->db->safeFetch(
                'select id, amount from db_lms_transactions where payment_batch_id = ? and status = ? for update',
                array($batchId, self::STATUS_PAID)
            );

            $revertedAmount = 0.0;
            foreach ($txRows as $tx) {
                $revertedAmount += (float) $tx['amount'];
            }

            $now = date('Y-m-d H:i:s');
            $this->db->update(
                'db_lms_payment_batches',
                array(
                    'status'      => 'void',
                    'voided_at'   => $now,
                    'voided_by'   => $voidedBy,
                    'void_reason' => $reason !== '' ? $reason : 'Hoàn tác phiên thanh toán',
                ),
                'id = ?',
                array($batchId)
            );

            $this->db->safeExec(
                'update db_lms_transactions'
                . ' set status = ?, paid_at = null, payment_batch_id = null, paid_by = null, payment_note = null'
                . ' where payment_batch_id = ?',
                array(self::STATUS_DRAFT, $batchId)
            );

            $this->audit('void_service_fee_batch', 'payment_batches', $batchId, array(
                'status' => 'paid',
            ), array(
                'status'                => 'void',
                'batch_code'            => $batch['code'],
                'teacher_id'            => (int) $batch['teacher_id'],
                'reverted_transactions' => count($txRows),
                'reverted_amount'       => round($revertedAmount, 2),
                'reason'                => $reason,
            ), 'admin');

            $this->db->commit();

            return array(
                'ok'                    => true,
                'skipped'               => 0,
                'batch_id'              => $batchId,
                'batch_code'            => (string) $batch['code'],
                'reverted_transactions' => count($txRows),
                'reverted_amount'       => round($revertedAmount, 2),
            );
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->addError('system', $e->getMessage());
            return array('ok' => false);
        }
    }

    /**
     * Hoàn tác ("Thêm tiền" / "Cộng lại các buổi") theo khoảng ngày cho danh sách giáo viên (§6.2.3).
     *
     * - Nếu khoảng ngày chọn nằm một phần trong một Batch (Partial Match) và $confirmPartialBatch = false,
     *   trả về needs_confirm = true kèm danh sách batch để UI hiển thị cảnh báo xác nhận.
     * - Khi hoàn tác: void toàn bộ các batch liên quan (và các giao dịch paid lẻ chưa gắn batch nếu có),
     *   đưa trạng thái giao dịch về 'draft'.
     * - Idempotent: bấm lần 2 trên cùng khoảng ngày đã hoàn tác sẽ không cộng thêm tiền.
     *
     * @param array  $teacherIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $reason
     * @param int    $actorId
     * @param bool   $confirmPartialBatch
     * @return array
     */
    public function unpayByDateRange(
        array $teacherIds,
        $dateFrom,
        $dateTo,
        $reason = '',
        $actorId = 0,
        $confirmPartialBatch = false
    ) {
        $this->clearErrors();

        $cleanIds = array();
        foreach ($teacherIds as $tid) {
            $tid = (int) $tid;
            if ($tid > 0) {
                $cleanIds[$tid] = $tid;
            }
        }
        $cleanIds = array_values($cleanIds);

        if (empty($cleanIds)) {
            $this->addError('teacher_ids', 'Vui lòng chọn ít nhất một giáo viên.');
            return array('ok' => false);
        }

        $dateFrom = trim((string) $dateFrom);
        $dateTo   = trim((string) $dateTo);
        if (!$this->isValidDate($dateFrom) || !$this->isValidDate($dateTo)) {
            $this->addError('date_range', 'Khoảng ngày hoàn tác không hợp lệ.');
            return array('ok' => false);
        }
        if ($dateFrom > $dateTo) {
            $tmp      = $dateFrom;
            $dateFrom = $dateTo;
            $dateTo   = $tmp;
        }

        $inspection = $this->inspectUnpayRange($cleanIds, $dateFrom, $dateTo);

        if (!empty($inspection['locked_batches'])) {
            $codes = array();
            foreach ($inspection['locked_batches'] as $lb) {
                $codes[] = $lb['code'];
            }
            $this->addError(
                'locked',
                'Phiên thanh toán ' . implode(', ', $codes) . ' đã khóa sổ/nộp thuế, không thể hoàn tác.'
            );
            return array('ok' => false, 'locked_batches' => $inspection['locked_batches']);
        }

        if (!empty($inspection['partial_batches']) && !$confirmPartialBatch) {
            return array(
                'ok'              => false,
                'needs_confirm'   => true,
                'partial_batches' => $inspection['partial_batches'],
            );
        }

        if ((int) $inspection['paid_tx_count'] === 0) {
            return array(
                'ok'                    => true,
                'reverted_teachers'     => 0,
                'voided_batches'        => array(),
                'reverted_transactions' => 0,
                'reverted_amount'       => 0.0,
            );
        }

        $allBatches = array_merge($inspection['exact_batches'], $inspection['partial_batches']);
        $voidedBatches        = array();
        $revertedTransactions = 0;
        $revertedAmount       = 0.0;
        $affectedTeachers     = array();

        foreach ($allBatches as $b) {
            $res = $this->voidBatch((int) $b['id'], $reason, $actorId);
            if (empty($res['ok'])) {
                return array('ok' => false);
            }
            if (empty($res['skipped'])) {
                $voidedBatches[]       = $res['batch_code'];
                $revertedTransactions += (int) $res['reverted_transactions'];
                $revertedAmount       += (float) $res['reverted_amount'];
                $affectedTeachers[(int) $b['teacher_id']] = true;
            }
        }

        // Hoàn tác thêm các giao dịch paid lẻ chưa gắn payment_batch_id (dữ liệu lịch sử duyệt theo tháng cũ)
        $this->db->beginTransaction();
        try {
            $placeholders = implode(', ', array_fill(0, count($cleanIds), '?'));
            $legacyRows = $this->db->safeFetch(
                'select id, teacher_id, amount from db_lms_transactions'
                . ' where teacher_id in (' . $placeholders . ')'
                . ' and status = ? and payment_batch_id is null'
                . ' and transaction_date >= ? and transaction_date <= ? for update',
                array_merge($cleanIds, array(self::STATUS_PAID, $dateFrom, $dateTo))
            );

            if (!empty($legacyRows)) {
                $legacyIds = array();
                foreach ($legacyRows as $lr) {
                    $legacyIds[] = (int) $lr['id'];
                    $revertedTransactions++;
                    $revertedAmount += (float) $lr['amount'];
                    $affectedTeachers[(int) $lr['teacher_id']] = true;
                }

                $lPlaceholders = implode(', ', array_fill(0, count($legacyIds), '?'));
                $this->db->safeExec(
                    'update db_lms_transactions'
                    . ' set status = ?, paid_at = null, paid_by = null, payment_note = null'
                    . ' where id in (' . $lPlaceholders . ')',
                    array_merge(array(self::STATUS_DRAFT), $legacyIds)
                );

                $this->audit('unpay_legacy_transactions', 'transactions', null, null, array(
                    'teacher_ids' => $cleanIds,
                    'date_from'   => $dateFrom,
                    'date_to'     => $dateTo,
                    'tx_count'    => count($legacyIds),
                ), 'admin');
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->addError('system', $e->getMessage());
            return array('ok' => false);
        }

        return array(
            'ok'                    => true,
            'reverted_teachers'     => count($affectedTeachers),
            'voided_batches'        => $voidedBatches,
            'reverted_transactions' => $revertedTransactions,
            'reverted_amount'       => round($revertedAmount, 2),
        );
    }

    /**
     * Tạo điều chỉnh tiền hàng loạt cho nhiều giáo viên từ Modal Phí dịch vụ (§6.4, §6.7 bước 3).
     * Có hỗ trợ idempotency_token để chống double-click khi mạng lag hoặc bấm nhiều lần.
     *
     * @param array       $teacherIds
     * @param string      $type             adjustment | bonus | penalty | deposit_deduct
     * @param float       $amount
     * @param string      $note
     * @param int         $actorId
     * @param string|null $transactionDate  'Y-m-d' (mặc định hôm nay)
     * @param string|null $idempotencyToken Token chống double-click từ client
     * @return array
     */
    public function bulkAddAdjustment(
        array $teacherIds,
        $type,
        $amount,
        $note,
        $actorId = 0,
        $transactionDate = null,
        $idempotencyToken = null
    ) {
        $this->clearErrors();

        $cleanIds = array();
        foreach ($teacherIds as $tid) {
            $tid = (int) $tid;
            if ($tid > 0) {
                $cleanIds[$tid] = $tid;
            }
        }
        $cleanIds = array_values($cleanIds);

        if (empty($cleanIds)) {
            $this->addError('teacher_ids', 'Vui lòng chọn ít nhất một giáo viên.');
            return array('ok' => false);
        }

        $type   = (string) $type;
        $amount = (float) $amount;
        $note   = trim((string) $note);

        if (!in_array($type, self::ADJUSTMENT_TYPES, true)) {
            $this->addError('type', 'Loại điều chỉnh không hợp lệ.');
            return array('ok' => false);
        }
        if ($amount == 0) {
            $this->addError('amount', 'Số tiền điều chỉnh phải khác 0.');
            return array('ok' => false);
        }
        if ($note === '') {
            $this->addError('note', 'Vui lòng nhập nội dung/lý do điều chỉnh tiền.');
            return array('ok' => false);
        }

        $txDate = ($transactionDate !== null && $this->isValidDate($transactionDate))
            ? (string) $transactionDate
            : date('Y-m-d');
        $month = substr($txDate, 0, 7);

        if ($type === 'penalty' || $type === 'deposit_deduct') {
            $signedAmount = -abs($amount);
        } elseif ($type === 'bonus') {
            $signedAmount = abs($amount);
        } else {
            $signedAmount = $amount;
        }

        $createdBy = null;
        if ((int) $actorId > 0) {
            $admin = $this->db->safeFetchOne(
                'select user_id from db_lms_admins where user_id = ? limit 1',
                array((int) $actorId)
            );
            if (!empty($admin)) {
                $createdBy = (int) $actorId;
            }
        }

        $this->db->beginTransaction();

        try {
            $ids = array();
            foreach ($cleanIds as $teacherId) {
                $teacher = $this->db->safeFetchOne(
                    'select user_id from db_lms_teachers where user_id = ? limit 1',
                    array($teacherId)
                );
                if (empty($teacher)) {
                    continue;
                }

                $idemKey = null;
                if (!empty($idempotencyToken)) {
                    $idemKey = substr(trim((string) $idempotencyToken) . '_' . $teacherId, 0, 64);
                    $existing = $this->db->safeFetchOne(
                        'select id from db_lms_transactions where idempotency_key = ? limit 1',
                        array($idemKey)
                    );
                    if (!empty($existing)) {
                        // Đã tạo trước đó từ cùng token (idempotent chống double-click)
                        $ids[] = (int) $existing['id'];
                        continue;
                    }
                }

                $ok = $this->db->insert('db_lms_transactions', array(
                    'teacher_id'       => $teacherId,
                    'session_id'       => null,
                    'amount'           => $signedAmount,
                    'type'             => $type,
                    'reason'           => $note,
                    'reference_id'     => null,
                    'transaction_date' => $txDate,
                    'period_month'     => $month,
                    'created_by'       => $createdBy,
                    'status'           => self::STATUS_DRAFT,
                    'idempotency_key'  => $idemKey,
                ));
                if (!$ok) {
                    throw new \RuntimeException('Không thể lưu giao dịch điều chỉnh.');
                }

                $txId  = (int) $this->db->insertId;
                $ids[] = $txId;

                $this->audit('add_adjustment', 'transactions', $txId, null, array(
                    'teacher_id'       => $teacherId,
                    'period_month'     => $month,
                    'transaction_date' => $txDate,
                    'type'             => $type,
                    'amount'           => $signedAmount,
                    'reason'           => $note,
                    'idempotency_key'  => $idemKey,
                ), 'admin');
            }

            $this->db->commit();

            return array(
                'ok'            => true,
                'created_count' => count($ids),
                'amount'        => $signedAmount,
                'ids'           => $ids,
            );
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->addError('system', $e->getMessage());
            return array('ok' => false);
        }
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
