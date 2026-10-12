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

        // Seed threshold: 1-3 lớp → miễn 2, warn 5, demote 8, phí sau hạ 50%
        $this->db->insert('db_lms_violation_thresholds', array(
            'min_classes'                 => 1,
            'max_classes'                 => 3,
            'ticks_to_warn'               => 5,
            'ticks_to_demote_fee'         => 8,
            'fee_percentage_after_demote' => 50.00,
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
        // 500000 × 50% = 250000 (chứ không phải 240000).
        $this->assertSame(250000.0, $stats['salary']);
        $rows = $this->salaryRows();
        $this->assertSame('250000.00', $rows[0]['amount']);
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

    public function testPreviousMonth_WrapsYearBoundary()
    {
        $this->assertSame('2026-08', $this->svc->previousMonth('2026-09'));
        $this->assertSame('2025-12', $this->svc->previousMonth('2026-01'));
    }
}
