<?php
/**
 * Tests cho TaxService — BR-PAY-03.
 *
 * Service tiền bạc: mọi case phải pass 100% (plan Stage C, R2).
 */

namespace Tests\Services;

use Lms\TaxService;
use Lms\UserService;
use Tests\LmsTestCase;

class TaxServiceTest extends LmsTestCase
{
    /** @var TaxService */
    private $svc;

    /** @var int */
    private $teacherId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = new TaxService($this->db);

        $userSvc = new UserService($this->db);
        $this->teacherId = $userSvc->createUser('gv_tax', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'              => $this->teacherId,
            'teacher_code'         => 'TX001',
            'full_name'            => 'GV Tax',
            'service_fee_percentage' => 48.00,
            'is_commit_08'         => 0,
            'status'               => 'active',
            'max_classes'          => 10,
        ));
    }

    // ===================== Helpers =====================

    private function txn($type, $amount, $month = '2026-09')
    {
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'session_id'       => null,
            'amount'           => $amount,
            'type'             => $type,
            'reason'           => 'test ' . $type,
            'transaction_date' => '2026-09-10',
            'period_month'     => $month,
            'status'           => 'draft',
        ));
        return (int) $this->db->insertId;
    }

    // ===================== computeTax (BR-PAY-03) =====================

    public function testComputeTax_Commit08_ReturnsZero()
    {
        $this->assertSame(0.0, $this->svc->computeTax(10000000, true));
    }

    public function testComputeTax_BelowThreshold_ReturnsZero()
    {
        $this->assertSame(0.0, $this->svc->computeTax(4999999, false));
    }

    public function testComputeTax_AtThreshold_Charges10Percent()
    {
        $this->assertSame(500000.0, $this->svc->computeTax(5000000, false));
    }

    public function testComputeTax_AboveThreshold_Charges10Percent()
    {
        $this->assertSame(1000000.0, $this->svc->computeTax(10000000, false));
    }

    public function testComputeTax_Zero_ReturnsZero()
    {
        $this->assertSame(0.0, $this->svc->computeTax(0, false));
    }

    // ===================== taxableIncomeForMonth (Q3) =====================

    public function testTaxableIncome_OnlySalaryBonusAdjustment()
    {
        $this->txn('salary_session', 5000000);
        $this->txn('bonus', 1000000);
        $this->txn('adjustment', -500000);

        $this->assertSame(5500000.0, $this->svc->taxableIncomeForMonth($this->teacherId, '2026-09'));
    }

    public function testTaxableIncome_IgnoresPenaltyAndTax()
    {
        $this->txn('salary_session', 5000000);
        $this->txn('penalty', -2000000);
        $this->txn('tax', -500000);
        $this->txn('deposit_deduct', -300000);

        $this->assertSame(5000000.0, $this->svc->taxableIncomeForMonth($this->teacherId, '2026-09'));
    }

    public function testTaxableIncome_ScopedToMonth()
    {
        $this->txn('salary_session', 5000000, '2026-09');
        $this->txn('salary_session', 3000000, '2026-10');

        $this->assertSame(5000000.0, $this->svc->taxableIncomeForMonth($this->teacherId, '2026-09'));
        $this->assertSame(3000000.0, $this->svc->taxableIncomeForMonth($this->teacherId, '2026-10'));
    }

    public function testTaxableIncome_NoTransactions_ReturnsZero()
    {
        $this->assertSame(0.0, $this->svc->taxableIncomeForMonth($this->teacherId, '2026-09'));
    }

    public function testTaxableIncome_InvalidMonth_ReturnsZero()
    {
        $this->txn('salary_session', 5000000);
        $this->assertSame(0.0, $this->svc->taxableIncomeForMonth($this->teacherId, 'not-a-month'));
    }

    // ===================== teacherIsCommit08 =====================

    public function testTeacherIsCommit08_FlagsCorrectly()
    {
        $this->assertFalse($this->svc->teacherIsCommit08($this->teacherId));

        $this->db->update('db_lms_teachers', array('is_commit_08' => 1), 'user_id = ?', array($this->teacherId));
        $this->assertTrue($this->svc->teacherIsCommit08($this->teacherId));
    }

    // ===================== recordTax =====================

    public function testRecordTax_InsertsNegativeAmount()
    {
        $id = $this->svc->recordTax($this->teacherId, '2026-09', 500000);

        $this->assertGreaterThan(0, (int) $id);
        $row = $this->db->safeFetchOne(
            'select * from db_lms_transactions where id = ?',
            array($id)
        );
        $this->assertSame('tax', $row['type']);
        $this->assertSame('-500000.00', $row['amount']);
        $this->assertSame('2026-09', $row['period_month']);
        $this->assertSame('draft', $row['status']);
    }

    public function testRecordTax_WritesAuditLog()
    {
        $id = $this->svc->recordTax($this->teacherId, '2026-09', 500000);

        $count = $this->db->count(
            'db_lms_audit_logs',
            'action = ? and target_type = ? and target_id = ?',
            array('record_tax', 'transactions', $id)
        );
        $this->assertSame(1, $count);
    }

    public function testRecordTax_RejectsNonPositiveAmount()
    {
        $this->assertNull($this->svc->recordTax($this->teacherId, '2026-09', 0));
        $this->assertNull($this->svc->recordTax($this->teacherId, '2026-09', -100));
        $this->assertTrue($this->svc->hasErrors());

        $count = $this->db->count('db_lms_transactions', "type = 'tax'");
        $this->assertSame(0, $count);
    }

    public function testRecordTax_RejectsInvalidTeacher()
    {
        $this->assertNull($this->svc->recordTax(0, '2026-09', 500000));
        $this->assertTrue($this->svc->hasErrors());
    }
}
