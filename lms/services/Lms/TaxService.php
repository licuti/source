<?php
/**
 * TaxService — tính thuế thu nhập cá nhân cho giáo viên (Phase 2).
 *
 * Implement SRS §3.2 BR-PAY-03:
 *  - computeTax():           thuế TNCN — 0% nếu cam kết 08 hoặc dưới ngưỡng,
 *                            10% phần vượt ngưỡng.
 *  - taxableIncomeForMonth(): tổng thu nhập chịu thuế 1 GV × 1 tháng.
 *  - recordTax():            ghi 1 transaction type='tax' (amount âm).
 *
 * Tax base (Q3): SUM(amount) WHERE type IN ('salary_session','bonus',
 * 'adjustment') — adjustment mang dấu (cộng/trừ).
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class TaxService extends LmsService
{
    /** Ngưỡng miễn thuế (BR-PAY-03). */
    const TAX_FREE_THRESHOLD = 5000000; // 5,000,000đ

    /** Thuế suất (BR-PAY-03). */
    const TAX_RATE = 0.10; // 10%

    /**
     * BR-PAY-03: tính thuế thu nhập.
     *
     * - is_commit_08 = 1 → 0 (cam kết 08).
     * - taxable < 5,000,000 → 0.
     * - ngược lại 10% (làm tròn 2 chữ số).
     *
     * @param float|int $taxableIncome
     * @param bool      $isCommit08
     * @return float
     */
    public function computeTax($taxableIncome, $isCommit08)
    {
        $taxableIncome = (float) $taxableIncome;

        if ($isCommit08) {
            return 0.0;
        }
        if ($taxableIncome < self::TAX_FREE_THRESHOLD) {
            return 0.0;
        }

        return round($taxableIncome * self::TAX_RATE, 2);
    }

    /**
     * Tổng thu nhập chịu thuế của 1 GV trong 1 tháng (Q3).
     *
     * Chỉ tính salary_session + bonus + adjustment (adjustment mang dấu).
     * penalty / tax / deposit_deduct không vào tax base (tránh giảm trừ kép).
     *
     * @param int    $teacherId
     * @param string $month 'Y-m'
     * @return float
     */
    public function taxableIncomeForMonth($teacherId, $month)
    {
        $teacherId = (int) $teacherId;

        if (!preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            return 0.0;
        }

        $row = $this->db->safeFetchOne(
            'select coalesce(sum(amount), 0) as s from db_lms_transactions'
            . ' where teacher_id = ? and period_month = ?'
            . ' and type in (?, ?, ?)',
            array($teacherId, $month, 'salary_session', 'bonus', 'adjustment')
        );

        return isset($row['s']) ? (float) $row['s'] : 0.0;
    }

    /**
     * Lấy is_commit_08 của 1 GV.
     *
     * @param int $teacherId
     * @return bool
     */
    public function teacherIsCommit08($teacherId)
    {
        $row = $this->db->safeFetchOne(
            'select is_commit_08 from db_lms_teachers where user_id = ? limit 1',
            array((int) $teacherId)
        );
        return !empty($row) && (int) $row['is_commit_08'] === 1;
    }

    /**
     * Ghi 1 transaction type='tax' (amount âm) cho tháng.
     * Idempotent theo (teacher, month) — gọi khi chưa có row tax.
     *
     * @param int    $teacherId
     * @param string $month    'Y-m'
     * @param float  $amount   giá trị dương (hàm sẽ lưu âm)
     * @param int    $actorId  user_id admin (0 → NULL)
     * @return int|null id transaction hoặc null khi lỗi
     */
    public function recordTax($teacherId, $month, $amount, $actorId = null)
    {
        $this->clearErrors();

        $teacherId = (int) $teacherId;
        $amount    = (float) $amount;

        if ($teacherId <= 0) {
            $this->addError('teacher_id', 'Giáo viên không hợp lệ.');
            return null;
        }
        if ($amount <= 0) {
            $this->addError('amount', 'Số tiền thuế phải lớn hơn 0.');
            return null;
        }

        $createdBy = $actorId !== null && (int) $actorId > 0 ? (int) $actorId : null;

        $ok = $this->db->insert(
            'db_lms_transactions',
            array(
                'teacher_id'       => $teacherId,
                'session_id'       => null,
                'amount'           => -abs($amount),
                'type'             => 'tax',
                'reason'           => 'Thuế TNCN 10% tháng ' . $month,
                'reference_id'     => null,
                'transaction_date' => date('Y-m-d'),
                'period_month'     => $month,
                'created_by'       => $createdBy,
                'status'           => 'draft',
            )
        );

        if (!$ok) {
            $this->addError('system', 'Không thể ghi thuế. Vui lòng thử lại.');
            return null;
        }

        $id = (int) $this->db->insertId;
        $this->audit('record_tax', 'transactions', $id, null, array(
            'teacher_id'   => $teacherId,
            'period_month' => $month,
            'amount'       => -abs($amount),
        ), 'system_cron');

        return $id;
    }
}
