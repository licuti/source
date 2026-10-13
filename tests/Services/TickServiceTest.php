<?php
/**
 * Tests cho TickService — BR-TICK-01/02/03 + admin (ADM-06).
 *
 * Service tiền bạc: mọi case phải pass 100% (plan Stage B, R2).
 */

namespace Tests\Services;

use Lms\TickService;
use Lms\UserService;
use Tests\LmsTestCase;

class TickServiceTest extends LmsTestCase
{
    /** @var TickService */
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

        $this->svc = new TickService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'CT tick'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Môn tick'));
        $subjectId = (int) $this->db->insertId;

        $userSvc = new UserService($this->db);
        $this->teacherId = $userSvc->createUser('gv_tick', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->teacherId,
            'teacher_code' => 'TT001',
            'full_name'    => 'GV Tick',
            'status'       => 'active',
            'max_classes'  => 10,
        ));

        $this->studentId = $this->createStudent('hv_tick', 'TS001');

        $this->db->insert('db_lms_classes', array(
            'class_code'  => 'TICK01',
            'program_id'  => $programId,
            'subject_id'  => $subjectId,
            'teacher_id'  => $this->teacherId,
            'class_type'  => 'one_on_one',
            'name'        => 'Lớp tick test',
            'hw_deadline_time' => '12:00',
            'hw_check_hours'   => 24,
            'status'      => 'active',
        ));
        $this->classId = (int) $this->db->insertId;

        // Seed threshold: 1-3 lớp → miễn 2, warn 5, demote 8, phí sau hạ 30%
        $this->db->insert('db_lms_violation_thresholds', array(
            'min_classes'              => 1,
            'max_classes'              => 3,
            'ticks_to_warn'            => 5,
            'ticks_to_demote_fee'      => 8,
            'fee_percentage_after_demote' => 30.00,
            'ticks_exempted'           => 2,
            'effective_from'           => date('Y-m-01'),
            'effective_to'             => null,
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

    private function createSession($date, $status = 'upcoming', array $extra = array())
    {
        $data = array_merge(array(
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'session_date' => $date,
            'start_time'   => '17:30',
            'end_time'     => '19:00',
            'status'       => $status,
        ), $extra);
        $this->db->insert('db_lms_sessions', $data);
        return (int) $this->db->insertId;
    }

    private function submitHw($sessionId, $studentId, $submittedAt, $status = 'submitted')
    {
        $this->db->insert('db_lms_session_students', array(
            'session_id'           => $sessionId,
            'student_id'           => $studentId,
            'hw_submitted_at'      => $submittedAt,
            'hw_submission_status' => 'submitted_on_time',
            'status'               => $status,
        ));
        return (int) $this->db->insertId;
    }

    private function insertTicks($n, $reason = 'late_lesson_summary')
    {
        for ($i = 0; $i < $n; $i++) {
            $this->db->insert('db_lms_violations', array(
                'teacher_id'     => $this->teacherId,
                'class_id'       => null,
                'session_id'     => null,
                'tick_amount'    => 1,
                'reason'         => $reason,
                'auto_generated' => 1,
                'violation_date' => date('Y-m-d', strtotime('-' . $i . ' days')),
            ));
        }
    }

    private function countViolations($reason = null)
    {
        if ($reason === null) {
            return $this->db->count('db_lms_violations', '1=1');
        }
        return $this->db->count('db_lms_violations', 'reason = ?', array($reason));
    }

    // ===================== closePastSessions =====================

    public function testClosePastSessionsClosesPastUpcoming()
    {
        $pastId   = $this->createSession(date('Y-m-d', strtotime('-3 days')));
        $futureId = $this->createSession(date('Y-m-d', strtotime('+3 days')));

        $stats = $this->svc->closePastSessions();

        $this->assertSame(1, $stats['closed']);

        $past = $this->db->safeFetchOne('select status from db_lms_sessions where id = ?', array($pastId));
        $this->assertSame('finished', (string) $past['status']);

        $future = $this->db->safeFetchOne('select status from db_lms_sessions where id = ?', array($futureId));
        $this->assertSame('upcoming', (string) $future['status']);
    }

    public function testClosePastSessionsSkipsCancelledAndFinished()
    {
        $this->createSession(date('Y-m-d', strtotime('-3 days')), 'cancelled');
        $this->createSession(
            date('Y-m-d', strtotime('-3 days')),
            'finished',
            array(
                'start_time' => '19:00',
                'summary_submitted_at' => date('Y-m-d H:i:s'),
            )
        );

        $stats = $this->svc->closePastSessions();

        $this->assertSame(0, $stats['closed']);
    }

    public function testClosePastSessionsDryRunDoesNotModify()
    {
        $pastId = $this->createSession(date('Y-m-d', strtotime('-3 days')));

        $stats = $this->svc->closePastSessions(true);

        $this->assertTrue($stats['dry_run']);
        $this->assertSame(1, $stats['closed']);
        $row = $this->db->safeFetchOne('select status from db_lms_sessions where id = ?', array($pastId));
        $this->assertSame('upcoming', (string) $row['status']);
    }

    // ===================== BR-TICK-01: giao bài trễ =====================

    public function testScanLateLessonSummaryTicks()
    {
        $sessionId = $this->createSession(date('Y-m-d', strtotime('-3 days')), 'finished');

        $stats = $this->svc->scanLateLessonSummary();

        $this->assertSame(1, $stats['candidates']);
        $this->assertSame(1, $stats['ticked']);
        $this->assertSame(1, $this->countViolations('late_lesson_summary'));

        $session = $this->db->safeFetchOne('select is_summary_late from db_lms_sessions where id = ?', array($sessionId));
        $this->assertSame(1, (int) $session['is_summary_late']);

        $v = $this->db->safeFetchOne('select * from db_lms_violations where reason = ?', array('late_lesson_summary'));
        $this->assertSame((string) $this->teacherId, (string) $v['teacher_id']);
        $this->assertSame((string) $sessionId, (string) $v['session_id']);
        $this->assertSame(1, (int) $v['tick_amount']);
        $this->assertSame(1, (int) $v['auto_generated']);
        // violation_date = session_date + 1 ngày (deterministic → idempotent)
        $this->assertSame(date('Y-m-d', strtotime('-2 days')), (string) $v['violation_date']);
    }

    public function testScanLateLessonSummaryIsIdempotent()
    {
        $this->createSession(date('Y-m-d', strtotime('-3 days')), 'finished');

        $this->svc->scanLateLessonSummary();
        $stats = $this->svc->scanLateLessonSummary();

        $this->assertSame(0, $stats['candidates'], 'is_summary_late=1 → không quét lại');
        $this->assertSame(0, $stats['ticked']);
        $this->assertSame(1, $this->countViolations('late_lesson_summary'));
    }

    public function testScanLateLessonSummarySkipsNotYetDueAndHasSummary()
    {
        // Chưa tới hạn: session_date ngày mai → hạn giao bài là ngày kia 12:00
        $this->createSession(date('Y-m-d', strtotime('+1 day')), 'finished');
        // Đã giao bài rồi
        $this->createSession(
            date('Y-m-d', strtotime('-3 days')),
            'finished',
            array('summary_submitted_at' => date('Y-m-d H:i:s'))
        );

        $stats = $this->svc->scanLateLessonSummary();

        $this->assertSame(0, $stats['candidates']);
        $this->assertSame(0, $this->countViolations());
    }

    public function testScanLateLessonSummaryDryRun()
    {
        $this->createSession(date('Y-m-d', strtotime('-3 days')), 'finished');

        $stats = $this->svc->scanLateLessonSummary(true);

        $this->assertTrue($stats['dry_run']);
        $this->assertSame(1, $stats['candidates']);
        $this->assertSame(0, $stats['ticked']);
        $this->assertSame(0, $this->countViolations());
    }

    // ===================== BR-TICK-02: chấm BTVN trễ =====================

    public function testScanLateHwGradingTicks()
    {
        $sessionId = $this->createSession(date('Y-m-d', strtotime('-3 days')), 'finished');
        $this->submitHw($sessionId, $this->studentId, date('Y-m-d H:i:s', strtotime('-48 hours')));

        $stats = $this->svc->scanLateHwGrading();

        $this->assertSame(1, $stats['candidates']);
        $this->assertSame(1, $stats['ticked']);
        $this->assertSame(1, $this->countViolations('late_hw_grading'));

        $ss = $this->db->safeFetchOne(
            'select is_graded_late from db_lms_session_students where session_id = ?',
            array($sessionId)
        );
        $this->assertSame(1, (int) $ss['is_graded_late']);
    }

    public function testScanLateHwGradingOneTickPerSession()
    {
        $sessionId = $this->createSession(date('Y-m-d', strtotime('-3 days')), 'finished');
        $student2  = $this->createStudent('hv_tick2', 'TS002');
        $this->submitHw($sessionId, $this->studentId, date('Y-m-d H:i:s', strtotime('-48 hours')));
        $this->submitHw($sessionId, $student2, date('Y-m-d H:i:s', strtotime('-30 hours')));

        $stats = $this->svc->scanLateHwGrading();

        $this->assertSame(1, $stats['candidates']);
        $this->assertSame(1, $stats['ticked'], 'Nhiều HV cùng buổi → chỉ 1 tick/session');
        $this->assertSame(1, $this->countViolations('late_hw_grading'));

        // Cả 2 row đều được đánh dấu trễ
        $late = $this->db->count(
            'db_lms_session_students',
            'session_id = ? and is_graded_late = 1',
            array($sessionId)
        );
        $this->assertSame(2, $late);
    }

    public function testScanLateHwGradingSkipsNotYetDue()
    {
        $sessionId = $this->createSession(date('Y-m-d', strtotime('-3 days')), 'finished');
        // Nộp 12h trước, hạn chấm 24h → chưa tới hạn
        $this->submitHw($sessionId, $this->studentId, date('Y-m-d H:i:s', strtotime('-12 hours')));

        $stats = $this->svc->scanLateHwGrading();

        $this->assertSame(0, $stats['candidates']);
        $this->assertSame(0, $this->countViolations());
    }

    // ===================== BR-TICK-03: warn / demote =====================

    public function testEvaluateTickPenaltyNoWarnBelowThreshold()
    {
        // 3 tick, miễn 2 → 1 thực tế < warn(5) → không notify
        $this->insertTicks(3);

        $stats = $this->svc->evaluateTickPenalty();

        $this->assertSame(1, $stats['evaluated']);
        $this->assertSame(0, $stats['warned']);
        $this->assertSame(0, $this->db->count('db_lms_notifications', 'type = ?', array('tick_warning')));
    }

    public function testEvaluateTickPenaltyWarnsAtThreshold()
    {
        // 7 tick, miễn 2 → 5 thực tế = warn(5) → notify
        $this->insertTicks(7);

        $stats = $this->svc->evaluateTickPenalty();

        $this->assertSame(1, $stats['warned']);

        $notifs = $this->db->safeFetch('select * from db_lms_notifications where type = ?', array('tick_warning'));
        $this->assertCount(1, $notifs);
        $this->assertSame((string) $this->teacherId, (string) $notifs[0]['user_id']);
        $this->assertStringContainsString('5 tick', (string) $notifs[0]['message']);
    }

    public function testEvaluateTickPenaltyIsIdempotentPerMonth()
    {
        $this->insertTicks(7);

        $this->svc->evaluateTickPenalty();
        $stats = $this->svc->evaluateTickPenalty();

        $this->assertSame(0, $stats['warned'], 'Đã cảnh báo trong tháng → không gửi lại');
        $this->assertSame(1, $this->db->count('db_lms_notifications', 'type = ?', array('tick_warning')));
    }

    public function testEvaluateTickPenaltyDemoteFee()
    {
        // 10 tick, miễn 2 → 8 thực tế = demote(8)
        $this->insertTicks(10);

        $stats = $this->svc->evaluateTickPenalty();

        $this->assertSame(1, $stats['warned']);
        $this->assertSame(1, $stats['demote_reached']);
        $audit = $this->db->count('db_lms_audit_logs', 'action = ?', array('tick_demote_fee_reached'));
        $this->assertSame(1, $audit);
    }

    public function testEvaluateTickPenaltyDryRun()
    {
        $this->insertTicks(7);

        $stats = $this->svc->evaluateTickPenalty(true);

        $this->assertTrue($stats['dry_run']);
        $this->assertSame(1, $stats['warned']);
        $this->assertSame(0, $this->db->count('db_lms_notifications', 'type = ?', array('tick_warning')));
    }

    // ===================== Admin (ADM-06) =====================

    public function testAddManualTickAndListViolations()
    {
        $ok = $this->svc->addManualTick($this->teacherId, 'manual_penalty', 1, 'Test phạt', 0);
        $this->assertTrue($ok);
        $this->assertSame(1, $this->countViolations('manual_penalty'));

        // Sai dấu tick so với lý do → lỗi, không insert
        $ok = $this->svc->addManualTick($this->teacherId, 'manual_penalty', -1, '', 0);
        $this->assertFalse($ok);
        $this->assertSame(1, $this->countViolations('manual_penalty'));

        // Miễn giảm -1
        $ok = $this->svc->addManualTick($this->teacherId, 'manual_exempt', -1, 'Bù trừ', 0);
        $this->assertTrue($ok);

        // GV không tồn tại
        $ok = $this->svc->addManualTick(999999, 'manual_penalty', 1, '', 0);
        $this->assertFalse($ok);

        $res = $this->svc->listViolations(array('reason' => 'manual_penalty'));
        $this->assertSame(1, (int) $res['total']);
        $this->assertCount(1, $res['items']);

        $res = $this->svc->listViolations(array('teacher_id' => $this->teacherId));
        $this->assertSame(2, (int) $res['total']);

        $res = $this->svc->listViolations(array('month' => date('Y-m')));
        $this->assertSame(2, (int) $res['total']);

        $res = $this->svc->listViolations(array('auto' => '0'));
        $this->assertSame(2, (int) $res['total']);
    }

    public function testManualExemptClampsRealTicksToZero()
    {
        // 2 tick tự sinh + miễn 2 → 0 thực tế
        $this->insertTicks(2);
        $this->assertSame(0, $this->svc->teacherRealTicks($this->teacherId, 2));

        // Thêm 1 miễn giảm → tổng âm nhưng clamp 0
        $this->svc->addManualTick($this->teacherId, 'manual_exempt', -1, '', 0);
        $this->assertSame(0, $this->svc->teacherRealTicks($this->teacherId, 2));
    }

    public function testDeleteTickBlocksAutoGenerated()
    {
        $this->insertTicks(1);
        $autoId = (int) $this->db->insertId;

        $ok = $this->svc->deleteTick($autoId);
        $this->assertFalse($ok, 'Không xoá tick do cron tạo');
        $this->assertSame(1, $this->countViolations());

        // Manual tick thì xoá được
        $this->svc->addManualTick($this->teacherId, 'manual_penalty', 1, '', 0);
        // Không dùng $this->db->insertId: addManualTick đã ghi audit → insertId
        // là id của audit log, không phải violation.
        $row = $this->db->safeFetchOne(
            "select id from db_lms_violations where reason = ? order by id desc limit 1",
            array('manual_penalty')
        );
        $manualId = (int) $row['id'];
        $ok = $this->svc->deleteTick($manualId);
        $this->assertTrue($ok);
        $this->assertSame(1, $this->countViolations());
    }

    public function testSaveThresholdValidatesAndCreates()
    {
        // demote < warn → lỗi
        $ok = $this->svc->saveThreshold(array(
            'min_classes'              => 4,
            'max_classes'              => 7,
            'ticks_to_warn'            => 5,
            'ticks_to_demote_fee'      => 3,
            'fee_percentage_after_demote' => 50,
            'ticks_exempted'           => 3,
            'effective_from'           => date('Y-m-01'),
        ));
        $this->assertFalse($ok);
        $this->assertTrue($this->svc->hasErrors());

        // Trùng khoảng 1-3 đang hiệu lực → lỗi
        $ok = $this->svc->saveThreshold(array(
            'min_classes'              => 2,
            'max_classes'              => 5,
            'ticks_to_warn'            => 5,
            'ticks_to_demote_fee'      => 8,
            'fee_percentage_after_demote' => 50,
            'ticks_exempted'           => 3,
            'effective_from'           => date('Y-m-01'),
        ));
        $this->assertFalse($ok);

        // Hợp lệ (khoảng mới) → tạo
        $ok = $this->svc->saveThreshold(array(
            'min_classes'              => 4,
            'max_classes'              => 7,
            'ticks_to_warn'            => 5,
            'ticks_to_demote_fee'      => 8,
            'fee_percentage_after_demote' => 50,
            'ticks_exempted'           => 3,
            'effective_from'           => date('Y-m-01'),
        ));
        $this->assertTrue($ok);
        $this->assertCount(2, $this->svc->listThresholds());
    }

    public function testFindThresholdMatchesClassCount()
    {
        // 1-3 lớp (GV này có 1 lớp active)
        $t = $this->svc->findThreshold(1);
        $this->assertSame(1, (int) $t['min_classes']);
        $this->assertSame(2, (int) $t['ticks_exempted']);
        $this->assertSame(1, (int) $this->svc->countActiveClasses($this->teacherId));
    }
}
