<?php
/**
 * Tests cho PayrollService — BR-PAY-01/02/03/04 + quy trình duyệt.
 *
 * Service tiền bạc: mọi case phải pass 100% (plan Stage D, R2/R5).
 */

namespace Tests\Services;

use Lms\PayrollService;
use Lms\TickService;
use Lms\UserService;
use Tests\LmsTestCase;

class PayrollServiceTest extends LmsTestCase
{
    /** @var PayrollService */
    private $svc;

    /** @var int */
    private $teacherId;

    /** @var int */
    private $classId;

    /** @var int */
    private $studentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = new PayrollService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'CT pay'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Môn pay'));
        $subjectId = (int) $this->db->insertId;

        $userSvc = new UserService($this->db);
        $this->teacherId = $userSvc->createUser('gv_pay', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'              => $this->teacherId,
            'teacher_code'         => 'PY001',
            'full_name'            => 'GV Pay',
            'service_fee_percentage' => 48.00,
            'is_commit_08'         => 0,
            'status'               => 'active',
            'max_classes'          => 10,
        ));

        $this->studentId = $this->createStudent('hv_pay', 'PS001');

        $this->db->insert('db_lms_classes', array(
            'class_code'         => 'PAY01',
            'program_id'         => $programId,
            'subject_id'         => $subjectId,
            'teacher_id'         => $this->teacherId,
            'class_type'         => 'one_on_one',
            'name'               => 'Lớp pay test',
            'price_per_session'  => 500000,
            'hw_deadline_time'   => '12:00',
            'hw_check_hours'     => 24,
            'status'             => 'active',
        ));
        $this->classId = (int) $this->db->insertId;

        // Seed threshold: 1-3 lớp → miễn 2, warn 5, demote 8, phí sau hạ 30%
        $this->db->insert('db_lms_violation_thresholds', array(
            'min_classes'                 => 1,
            'max_classes'                 => 3,
            'ticks_to_warn'               => 5,
            'ticks_to_demote_fee'         => 8,
            'fee_percentage_after_demote' => 30.00,
            'ticks_exempted'              => 2,
            'effective_from'              => '2026-01-01',
            'effective_to'                => null,
        ));
    }

    // ===================== Helpers =====================

    private function createStudent($username, $code)
    {
        $userSvc = new UserService($this->db);
        $id = $userSvc->createUser($username, 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $id,
            'student_code' => $code,
            'full_name'    => 'HV ' . $code,
            'status'       => 'studying',
        ));
        return $id;
    }

    private function createSession($date, $status = 'finished', $classId = null, $teacherId = null)
    {
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $classId !== null ? $classId : $this->classId,
            'teacher_id'   => $teacherId !== null ? $teacherId : $this->teacherId,
            'session_date' => $date,
            'start_time'   => '17:30',
            'end_time'     => '19:00',
            'status'       => $status,
        ));
        return (int) $this->db->insertId;
    }

    private function submitHw($sessionId, $status = 'submitted')
    {
        $this->db->insert('db_lms_session_students', array(
            'session_id'           => $sessionId,
            'student_id'           => $this->studentId,
            'hw_submission_status' => $status === 'submitted' ? 'submitted_on_time' : 'not_submitted',
            'status'               => $status,
        ));
        return (int) $this->db->insertId;
    }

    private function insertPrevMonthTicks($n)
    {
        $prevMonth = $this->svc->previousMonth('2026-09');
        for ($i = 0; $i < $n; $i++) {
            $this->db->insert('db_lms_violations', array(
                'teacher_id'     => $this->teacherId,
                'class_id'       => null,
                'session_id'     => null,
                'tick_amount'    => 1,
                'reason'         => 'late_lesson_summary',
                'auto_generated' => 1,
                'violation_date' => $prevMonth . '-' . str_pad((string) min($i + 1, 28), 2, '0', STR_PAD_LEFT),
            ));
        }
    }

    private function salaryRows()
    {
        return $this->db->safeFetch(
            "select * from db_lms_transactions where type = 'salary_session' order by id asc"
        );
    }

    // ===================== BR-PAY-01: đóng lương =====================

    public function testCloseMonth_PaysFinishedGradedSession()
    {
        $this->createSession('2026-09-05');

        $stats = $this->svc->closeMonth($this->teacherId, '2026-09');

        $this->assertSame(1, $stats['sessions_payable']);
        $this->assertSame(240000.0, $stats['salary']);
        $this->assertTrue($stats['closed']);

        $rows = $this->salaryRows();
        $this->assertCount(1, $rows);
        $this->assertSame('240000.00', $rows[0]['amount']);
        $this->assertSame('2026-09-05', $rows[0]['transaction_date']);
        $this->assertSame('2026-09', $rows[0]['period_month']);
        $this->assertSame('draft', $rows[0]['status']);
    }

    public function testCloseMonth_SkipsSessionWithUngradedHomework()
    {
        $sessionId = $this->createSession('2026-09-05');
        $this->submitHw($sessionId, 'submitted');

        $stats = $this->svc->closeMonth($this->teacherId, '2026-09');

        $this->assertSame(0, $stats['sessions_payable']);
        $this->assertSame(0.0, $stats['salary']);
        $this->assertSame(0, $this->db->count('db_lms_transactions'));
    }

    public function testCloseMonth_SkipsNonFinishedSessions()
    {
        $this->createSession('2026-09-05', 'upcoming');
        $this->createSession('2026-09-06', 'cancelled');

        $stats = $this->svc->closeMonth($this->teacherId, '2026-09');

        $this->assertSame(0, $stats['sessions_payable']);
        $this->assertSame(0, $this->db->count('db_lms_transactions'));
    }

    public function testCloseMonth_GradedHomeworkIsPaid()
    {
        $sessionId = $this->createSession('2026-09-05');
        $this->submitHw($sessionId, 'graded');

        $stats = $this->svc->closeMonth($this->teacherId, '2026-09');

        $this->assertSame(1, $stats['sessions_payable']);
    }

    public function testCloseMonth_IsIdempotent()
    {
        $this->createSession('2026-09-05');
        $this->createSession('2026-09-07');

        $this->svc->closeMonth($this->teacherId, '2026-09');
        $stats = $this->svc->closeMonth($this->teacherId, '2026-09');

        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(2, $this->db->count("db_lms_transactions", "type = 'salary_session'"));
    }

    // ===================== BR-PAY-02: phí đã hạ =====================

    public function testCloseMonth_DemotesFeeWhenPrevMonthTicksReachThreshold()
    {
        // threshold: exempted 2, demote 8 → 10 raw ticks = 8 real → demote.
        $this->insertPrevMonthTicks(10);
        $this->createSession('2026-09-05');

        $stats = $this->svc->closeMonth($this->teacherId, '2026-09');

        $this->assertTrue($stats['demoted']);
        // 30% là tỷ lệ trực tiếp và thấp hơn mức mặc định 48% → 500000 × 30%.
        $this->assertSame(150000.0, $stats['salary']);
        $rows = $this->salaryRows();
        $this->assertSame('150000.00', $rows[0]['amount']);
    }

    public function testCloseMonth_DemotedFeeUsesDirectPercentageWhenBelowCurrent()
    {
        $this->db->update(
            'db_lms_violation_thresholds',
            array('fee_percentage_after_demote' => 30.00),
            'min_classes = ? and effective_from = ?',
            array(1, '2026-01-01')
        );
        $this->insertPrevMonthTicks(10);
        $this->createSession('2026-09-05');

        $stats = $this->svc->closeMonth($this->teacherId, '2026-09');

        $this->assertTrue($stats['demoted']);
        $this->assertSame(150000.0, $stats['salary']);
    }

    public function testCloseMonth_KeepsDefaultFeeWhenPrevMonthTicksBelowThreshold()
    {
        $this->insertPrevMonthTicks(5); // real = 3 < 8.
        $this->createSession('2026-09-05');

        $stats = $this->svc->closeMonth($this->teacherId, '2026-09');

        $this->assertFalse($stats['demoted']);
        $this->assertSame(240000.0, $stats['salary']);
    }

    public function testCloseMonth_ClassPercentageOverridesDefault()
    {
        $this->db->update(
            'db_lms_classes',
            array('teacher_percentage' => 60.00),
            'id = ?',
            array($this->classId)
        );
        $this->createSession('2026-09-05');

        $stats = $this->svc->closeMonth($this->teacherId, '2026-09');

        // 500000 × 60% = 300000.
        $this->assertSame(300000.0, $stats['salary']);
    }

    // ===================== BR-PAY-03/04: thuế + tổng hợp =====================

    public function testCloseMonth_RecordsTaxWhenAboveThreshold()
    {
        $this->db->update(
            'db_lms_classes',
            array('price_per_session' => 12000000),
            'id = ?',
            array($this->classId)
        );
        $this->createSession('2026-09-05');

        $stats = $this->svc->closeMonth($this->teacherId, '2026-09');

        // 12tr × 48% = 5.76tr → thuế 10% = 576000.
        $this->assertSame(5760000.0, $stats['salary']);
        $this->assertSame(576000.0, $stats['tax']);

        $tax = $this->db->safeFetchOne("select * from db_lms_transactions where type = 'tax'");
        $this->assertSame('-576000.00', $tax['amount']);
    }

    public function testCloseMonth_NoTaxForCommit08Teacher()
    {
        $this->db->update(
            'db_lms_classes',
            array('price_per_session' => 12000000),
            'id = ?',
            array($this->classId)
        );
        $this->db->update(
            'db_lms_teachers',
            array('is_commit_08' => 1),
            'user_id = ?',
            array($this->teacherId)
        );
        $this->createSession('2026-09-05');

        $stats = $this->svc->closeMonth($this->teacherId, '2026-09');

        $this->assertSame(0.0, $stats['tax']);
        $this->assertSame(0, $this->db->count("db_lms_transactions", "type = 'tax'"));
    }

    public function testMonthSummary_ComputesThucNhan()
    {
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'amount'           => 5000000,
            'type'             => 'salary_session',
            'transaction_date' => '2026-09-05',
            'period_month'     => '2026-09',
            'status'           => 'draft',
        ));
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'amount'           => -500000,
            'type'             => 'tax',
            'transaction_date' => '2026-09-05',
            'period_month'     => '2026-09',
            'status'           => 'draft',
        ));

        $s = $this->svc->monthSummary($this->teacherId, '2026-09');

        $this->assertSame(5000000.0, $s['tong_thu']);
        $this->assertSame(500000.0, $s['thue']);
        $this->assertSame(4500000.0, $s['thuc_nhan']);
        $this->assertSame('draft', $s['status']);
    }

    public function testMonthSummary_IncludesPenaltyAndBonus()
    {
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'amount'           => 4000000,
            'type'             => 'salary_session',
            'transaction_date' => '2026-09-05',
            'period_month'     => '2026-09',
            'status'           => 'draft',
        ));
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'amount'           => -300000,
            'type'             => 'penalty',
            'transaction_date' => '2026-09-06',
            'period_month'     => '2026-09',
            'status'           => 'draft',
        ));
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'amount'           => 700000,
            'type'             => 'bonus',
            'transaction_date' => '2026-09-07',
            'period_month'     => '2026-09',
            'status'           => 'draft',
        ));

        $s = $this->svc->monthSummary($this->teacherId, '2026-09');

        // tong_thu = salary + bonus + adjustment = 4tr + 700k = 4.7tr
        $this->assertSame(4700000.0, $s['tong_thu']);
        $this->assertSame(700000.0, $s['tong_thuong']);
        $this->assertSame(300000.0, $s['tong_phat']);
        // thuc_nhan = 4.7tr - 300k - 0 = 4.4tr
        $this->assertSame(4400000.0, $s['thuc_nhan']);
    }

    // ===================== Quy trình duyệt =====================

    public function testSetMonthStatus_AdvancesAndSetsPaidAt()
    {
        $this->createSession('2026-09-05');
        $this->svc->closeMonth($this->teacherId, '2026-09');

        $this->assertSame('draft', $this->svc->monthStatus($this->teacherId, '2026-09'));

        $this->assertTrue($this->svc->setMonthStatus($this->teacherId, '2026-09', 'pending'));
        $this->assertSame('pending', $this->svc->monthStatus($this->teacherId, '2026-09'));

        $this->assertTrue($this->svc->setMonthStatus($this->teacherId, '2026-09', 'paid'));
        $this->assertSame('paid', $this->svc->monthStatus($this->teacherId, '2026-09'));

        $row = $this->db->safeFetchOne(
            "select * from db_lms_transactions where type = 'salary_session' limit 1"
        );
        $this->assertNotNull($row['paid_at']);
    }

    public function testSetMonthStatus_RejectsWrongOrder()
    {
        $this->createSession('2026-09-05');
        $this->svc->closeMonth($this->teacherId, '2026-09');

        $this->assertFalse($this->svc->setMonthStatus($this->teacherId, '2026-09', 'paid'));
        $this->assertTrue($this->svc->hasErrors());
        $this->assertSame('draft', $this->svc->monthStatus($this->teacherId, '2026-09'));
    }

    public function testSetMonthStatus_RejectsEmptyMonth()
    {
        $this->assertFalse($this->svc->setMonthStatus($this->teacherId, '2026-09', 'pending'));
        $this->assertTrue($this->svc->hasErrors());
    }

    public function testSetMonthStatus_AuditLogged()
    {
        $this->createSession('2026-09-05');
        $this->svc->closeMonth($this->teacherId, '2026-09');
        $this->svc->setMonthStatus($this->teacherId, '2026-09', 'pending');

        $count = $this->db->count(
            'db_lms_audit_logs',
            'action = ?',
            array('payroll_set_status')
        );
        $this->assertSame(1, $count);
    }

    // ===================== addAdjustment =====================

    public function testAddAdjustment_InsertsSignedAmounts()
    {
        $penalty = $this->svc->addAdjustment($this->teacherId, '2026-09', 'penalty', 500000, 'Phạt muộn', 0);
        $bonus   = $this->svc->addAdjustment($this->teacherId, '2026-09', 'bonus', 300000, 'Thưởng', 0);
        $adj     = $this->svc->addAdjustment($this->teacherId, '2026-09', 'adjustment', -120000, 'Điều chỉnh', 0);

        $this->assertGreaterThan(0, $penalty);
        $this->assertGreaterThan(0, $bonus);
        $this->assertGreaterThan(0, $adj);

        $pen = $this->db->safeFetchOne('select * from db_lms_transactions where id = ?', array($penalty));
        $bon = $this->db->safeFetchOne('select * from db_lms_transactions where id = ?', array($bonus));
        $adjr = $this->db->safeFetchOne('select * from db_lms_transactions where id = ?', array($adj));

        $this->assertSame('-500000.00', $pen['amount']);
        $this->assertSame('300000.00', $bon['amount']);
        $this->assertSame('-120000.00', $adjr['amount']);
    }

    public function testAddAdjustment_RejectsInvalidType()
    {
        $this->assertNull($this->svc->addAdjustment($this->teacherId, '2026-09', 'salary_session', 500000, '', 0));
        $this->assertNull($this->svc->addAdjustment($this->teacherId, '2026-09', 'bogus', 500000, '', 0));
        $this->assertTrue($this->svc->hasErrors());
        $this->assertSame(0, $this->db->count('db_lms_transactions'));
    }

    public function testAddAdjustment_RejectsZeroAmount()
    {
        $this->assertNull($this->svc->addAdjustment($this->teacherId, '2026-09', 'bonus', 0, '', 0));
        $this->assertSame(0, $this->db->count('db_lms_transactions'));
    }

    public function testAddAdjustment_RejectsUnknownTeacher()
    {
        $this->assertNull($this->svc->addAdjustment(99999, '2026-09', 'bonus', 500000, '', 0));
        $this->assertTrue($this->svc->hasErrors());
        $this->assertSame(0, $this->db->count('db_lms_transactions'));
    }

    // ===================== listTransactions =====================

    public function testListTransactions_FiltersByType()
    {
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'amount'           => 1000000,
            'type'             => 'salary_session',
            'transaction_date' => '2026-09-05',
            'period_month'     => '2026-09',
            'status'           => 'draft',
        ));
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'amount'           => -200000,
            'type'             => 'penalty',
            'transaction_date' => '2026-09-06',
            'period_month'     => '2026-09',
            'status'           => 'draft',
        ));

        $all = $this->svc->listTransactions($this->teacherId, '2026-09');
        $this->assertCount(2, $all);

        $penalty = $this->svc->listTransactions($this->teacherId, '2026-09', 'penalty');
        $this->assertCount(1, $penalty);
        $this->assertSame('penalty', $penalty[0]['type']);
    }

    // ===================== closeMonthAll =====================

    public function testCloseMonthAll_ProcessesMultipleTeachers()
    {
        // GV 1: 1 buổi payable.
        $this->createSession('2026-09-05');

        // GV 2: 1 buổi payable.
        $userSvc = new UserService($this->db);
        $teacher2 = $userSvc->createUser('gv_pay2', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'              => $teacher2,
            'teacher_code'         => 'PY002',
            'full_name'            => 'GV Pay 2',
            'service_fee_percentage' => 48.00,
            'is_commit_08'         => 0,
            'status'               => 'active',
            'max_classes'          => 10,
        ));
        $this->db->insert('db_lms_classes', array(
            'class_code'        => 'PAY02',
            'program_id'        => 1,
            'subject_id'        => 1,
            'teacher_id'        => $teacher2,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp pay 2',
            'price_per_session' => 500000,
            'status'            => 'active',
        ));
        $class2 = (int) $this->db->insertId;
        $this->createSession('2026-09-06', 'finished', $class2, $teacher2);

        $summary = $this->svc->closeMonthAll('2026-09');

        $this->assertSame(2, $summary['teachers']);
        $this->assertSame(2, $summary['closed']);
        $this->assertSame(480000.0, $summary['salary_total']);
        $this->assertCount(2, $summary['details']);
    }

    public function testCloseMonthAll_SkipsTeachersWithoutSessions()
    {
        $this->createSession('2026-09-05');

        $summary = $this->svc->closeMonthAll('2026-09');

        $this->assertSame(1, $summary['teachers']);
        $this->assertSame(1, $summary['closed']);
    }

    public function testCloseMonthAll_DryRunMakesNoChanges()
    {
        $this->createSession('2026-09-05');

        $summary = $this->svc->closeMonthAll('2026-09', true);

        $this->assertTrue($summary['dry_run']);
        $this->assertSame(1, $summary['teachers']);
        $this->assertSame(240000.0, $summary['salary_total']);
        $this->assertSame(0, $this->db->count('db_lms_transactions'));
    }

    public function testCloseMonth_InvalidMonthFailsCleanly()
    {
        $stats = $this->svc->closeMonth($this->teacherId, '2026/09');

        $this->assertSame(0, $stats['sessions_payable']);
        $this->assertTrue($this->svc->hasErrors());
        $this->assertSame(0, $this->db->count('db_lms_transactions'));
    }

    // ===================== Báo cáo phí DV theo khoảng ngày =====================

    public function testFeeSummary_GroupsTypesAndAppliesZeroTaxUnderThreshold()
    {
        $summary = $this->svc->feeSummary(array(
            array('amount' => 3000000, 'type' => 'salary_session'),
            array('amount' => 500000, 'type' => 'bonus'),
            array('amount' => -100000, 'type' => 'penalty'),
            array('amount' => -200000, 'type' => 'deposit_deduct'),
            array('amount' => 50000, 'type' => 'adjustment'),
        ), false);

        $this->assertSame(3550000.0, $summary['tong_thu']);
        $this->assertSame(550000.0, $summary['phi_cong_them']);
        $this->assertSame(100000.0, $summary['vi_pham']);
        $this->assertSame(-200000.0, $summary['ky_quy']);
        $this->assertSame(0.0, $summary['thue_rate']);
        $this->assertSame(3250000.0, $summary['thuc_nhan']);
    }

    public function testFeeSummary_AppliesTenPercentAboveThreshold()
    {
        $summary = $this->svc->feeSummary(array(
            array('amount' => 6000000, 'type' => 'salary_session'),
            array('amount' => -100000, 'type' => 'penalty'),
            array('amount' => -600000, 'type' => 'tax'),
        ), false);

        $this->assertSame(10.0, $summary['thue_rate']);
        $this->assertSame(600000.0, $summary['thue']);
        $this->assertSame(5300000.0, $summary['thuc_nhan']);
    }

    public function testFeeSummary_Commit08KeepsTaxZero()
    {
        $summary = $this->svc->feeSummary(array(
            array('amount' => 6000000, 'type' => 'salary_session'),
        ), true);

        $this->assertSame(0.0, $summary['thue_rate']);
        $this->assertSame(6000000.0, $summary['thuc_nhan']);
    }

    public function testTransactionsByRange_FiltersByDate()
    {
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'amount'           => 1000000,
            'type'             => 'salary_session',
            'transaction_date' => '2026-09-05',
            'period_month'     => '2026-09',
            'status'           => 'draft',
        ));
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'amount'           => 2000000,
            'type'             => 'salary_session',
            'transaction_date' => '2026-10-01',
            'period_month'     => '2026-10',
            'status'           => 'draft',
        ));

        $items = $this->svc->transactionsByRange($this->teacherId, '2026-09-01', '2026-09-30');

        $this->assertCount(1, $items);
        $this->assertSame('2026-09-05', $items[0]['transaction_date']);
    }

    public function testAllFeeSummaries_AggregatesEachTeacherInRange()
    {
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'amount'           => 6000000,
            'type'             => 'salary_session',
            'transaction_date' => '2026-09-05',
            'period_month'     => '2026-09',
            'status'           => 'draft',
        ));
        $this->db->insert('db_lms_transactions', array(
            'teacher_id'       => $this->teacherId,
            'amount'           => -100000,
            'type'             => 'penalty',
            'transaction_date' => '2026-09-06',
            'period_month'     => '2026-09',
            'status'           => 'draft',
        ));

        $items = $this->svc->allFeeSummaries('2026-09-01', '2026-09-30');

        $this->assertCount(1, $items);
        $this->assertSame('PY001', $items[0]['teacher_code']);
        $this->assertSame(6000000.0, $items[0]['tong_thu']);
        $this->assertSame(100000.0, $items[0]['vi_pham']);
        $this->assertSame(10.0, $items[0]['thue_rate']);
        $this->assertSame(5900000.0, $items[0]['thuc_nhan']);
    }

    public function testSessionsByRange_ExcludesCancelledSessions()
    {
        $this->createSession('2026-09-05', 'finished');
        $this->createSession('2026-09-06', 'cancelled');
        $this->createSession('2026-09-07', 'upcoming');

        $items = $this->svc->sessionsByRange($this->teacherId, '2026-09-01', '2026-09-30');

        $this->assertCount(2, $items);
    }

    public function testPreviousMonth_WrapsYearBoundary()
    {
        $this->assertSame('2026-08', $this->svc->previousMonth('2026-09'));
        $this->assertSame('2025-12', $this->svc->previousMonth('2026-01'));
    }

    public function testCloseMonth_MidMonthFeeChangeSplits4Sessions45PercentAnd4Sessions41Percent()
    {
        $classSvc = new \Lms\ClassService($this->db);
        $classId = $classSvc->createClass(array(
            'program_id'         => 1,
            'subject_id'         => 1,
            'teacher_id'         => $this->teacherId,
            'class_type'         => 'one_on_one',
            'name'               => 'Lớp hạ phí giữa tháng',
            'price_per_session'  => 500000,
            'teacher_percentage' => 45.00,
            'total_sessions'     => 8,
            'start_date'         => '2026-11-01',
            'student_ids'        => array($this->studentId),
        ));
        $this->assertGreaterThan(0, $classId);

        // 4 buổi đầu tháng đã dạy với mức 45%
        $firstFourDates = array('2026-11-02', '2026-11-05', '2026-11-09', '2026-11-12');
        foreach ($firstFourDates as $d) {
            $this->db->insert('db_lms_sessions', array(
                'class_id'           => $classId,
                'teacher_id'         => $this->teacherId,
                'session_date'       => $d,
                'start_time'         => '17:30',
                'end_time'           => '19:00',
                'price_per_session'  => 500000,
                'teacher_percentage' => 45.00,
                'status'             => 'finished',
            ));
        }

        // 4 buổi nửa cuối tháng (ban đầu sinh ở mức 45%)
        $lastFourDates = array('2026-11-16', '2026-11-19', '2026-11-23', '2026-11-26');
        $lastFourIds = array();
        foreach ($lastFourDates as $d) {
            $this->db->insert('db_lms_sessions', array(
                'class_id'           => $classId,
                'teacher_id'         => $this->teacherId,
                'session_date'       => $d,
                'start_time'         => '17:30',
                'end_time'           => '19:00',
                'price_per_session'  => 500000,
                'teacher_percentage' => 45.00,
                'status'             => 'upcoming',
            ));
            $lastFourIds[] = (int) $this->db->insertId;
        }

        // Admin hạ mức phí dịch vụ xuống 41% áp dụng từ 2026-11-15
        $ok = $classSvc->updateClass($classId, array(
            'program_id'         => 1,
            'subject_id'         => 1,
            'teacher_id'         => $this->teacherId,
            'class_type'         => 'one_on_one',
            'name'               => 'Lớp hạ phí giữa tháng',
            'price_per_session'  => 500000,
            'teacher_percentage' => 41.00,
            'total_sessions'     => 8,
            'start_date'         => '2026-11-15',
            'student_ids'        => array($this->studentId),
        ));
        $this->assertTrue($ok);

        // Sau đó GV dạy xong 4 buổi còn lại
        foreach ($lastFourIds as $sid) {
            $this->db->update('db_lms_sessions', array('status' => 'finished'), 'id = ?', array($sid));
        }

        // Chốt lương tháng 11/2026: 4 buổi * (500k * 45%) + 4 buổi * (500k * 41%) = 900k + 820k = 1.720.000
        $stats = $this->svc->closeMonth($this->teacherId, '2026-11');
        $this->assertSame(8, $stats['sessions_payable']);
        $this->assertSame(1720000.0, $stats['salary']);

        $rows = $this->salaryRows();
        $this->assertCount(8, $rows);
        for ($i = 0; $i < 4; $i++) {
            $this->assertSame('225000.00', $rows[$i]['amount']);
        }
        for ($i = 4; $i < 8; $i++) {
            $this->assertSame('205000.00', $rows[$i]['amount']);
        }
    }

    // ===================== Ví Phí Dịch Vụ & Payment Batch (§6.6) =====================

    public function testPayBatchAndUnpayByDateRange_IdempotentAndSupportsPartialConfirm()
    {
        $this->createSession('2026-10-05', 'finished');
        $this->createSession('2026-10-10', 'finished');
        $this->createSession('2026-10-20', 'finished');
        $this->createSession('2026-10-25', 'finished');

        // 4 buổi * (500k * 48%) = 4 * 240k = 960,000
        $this->assertSame(960000.0, $this->svc->unpaidBalanceForTeacher($this->teacherId, true));

        // Thêm điều chỉnh +100k thưởng và -40k phạt
        $this->svc->bulkAddAdjustment(array($this->teacherId), 'bonus', 100000, 'Thưởng', 0, '2026-10-12');
        $this->svc->bulkAddAdjustment(array($this->teacherId), 'penalty', 40000, 'Phạt', 0, '2026-10-14');
        $this->assertSame(1020000.0, $this->svc->unpaidBalanceForTeacher($this->teacherId, false));

        // Thanh toán đợt 1 (01/10 -> 15/10): 2 buổi (480k) + 100k - 40k = 540,000
        $pay1 = $this->svc->payBatch(array($this->teacherId), '2026-10-01', '2026-10-15', 'Đợt 1', 0);
        $this->assertTrue($pay1['ok']);
        $this->assertSame(1, $pay1['paid_teachers']);
        $this->assertSame(540000.0, $pay1['total_net_amount']);
        $this->assertSame(480000.0, $this->svc->unpaidBalanceForTeacher($this->teacherId, false));
        $this->assertSame('mixed', $this->svc->monthStatus($this->teacherId, '2026-10'));

        // Bấm Thanh toán cùng khoảng ngày lần 2 -> không tạo batch trùng, không đổi số dư
        $pay1Dup = $this->svc->payBatch(array($this->teacherId), '2026-10-01', '2026-10-15', 'Đợt 1 dup', 0);
        $this->assertSame(0, $pay1Dup['paid_teachers']);
        $this->assertSame(480000.0, $this->svc->unpaidBalanceForTeacher($this->teacherId, false));

        // Bấm Thêm tiền chọn lẻ ngày (01/10 -> 06/10) -> yêu cầu xác nhận hoàn tác cả batch
        $unpayPartial = $this->svc->unpayByDateRange(array($this->teacherId), '2026-10-01', '2026-10-06', 'Hoàn tác lẻ', 0, false);
        $this->assertFalse($unpayPartial['ok']);
        $this->assertTrue(!empty($unpayPartial['needs_confirm']));

        // Xác nhận hoàn tác toàn bộ batch -> số dư về lại 1,020,000
        $unpayOk = $this->svc->unpayByDateRange(array($this->teacherId), '2026-10-01', '2026-10-06', 'Hoàn tác cả batch', 0, true);
        $this->assertTrue($unpayOk['ok']);
        $this->assertSame(540000.0, $unpayOk['reverted_amount']);
        $this->assertSame(1020000.0, $this->svc->unpaidBalanceForTeacher($this->teacherId, false));

        // Bấm Thêm tiền lần 2 cùng khoảng ngày -> không cộng trùng tiền
        $unpayDup = $this->svc->unpayByDateRange(array($this->teacherId), '2026-10-01', '2026-10-15', 'Hoàn tác lần 2', 0, true);
        $this->assertSame(0, $unpayDup['reverted_transactions']);
        $this->assertSame(1020000.0, $this->svc->unpaidBalanceForTeacher($this->teacherId, false));
    }
}
