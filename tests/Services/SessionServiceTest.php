<?php
/**
 * Tests cho SessionService — sinh sessions từ lịch, idempotency, hạn BTVN.
 */

namespace Tests\Services;

use Lms\ClassService;
use Lms\ScheduleService;
use Lms\SessionService;
use Tests\LmsTestCase;

class SessionServiceTest extends LmsTestCase
{
    /** @var SessionService */
    private $svc;

    /** @var int */
    private $classId;

    /** @var int */
    private $teacherId;

    /** @var int */
    private $studentId;

    protected function setUp(): void
    {
        parent::setUp();

        $schedSvc = new ScheduleService($this->db);
        $classSvc = new ClassService($this->db, $schedSvc);
        $this->svc = new SessionService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'Chương trình test'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Môn test'));
        $subjectId = (int) $this->db->insertId;

        $userSvc = new \Lms\UserService($this->db);
        $this->teacherId = $userSvc->createUser('gv_sess', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->teacherId,
            'teacher_code' => 'TE001',
            'full_name'    => 'GV Session',
            'status'       => 'active',
            // BR max_classes (ADM-03): test tạo 2 lớp cùng GV nên để giới hạn rộng
            'max_classes'  => 10,
        ));

        $this->studentId = $userSvc->createUser('hv_sess', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $this->studentId,
            'student_code' => 'ST001',
            'full_name'    => 'HV Session',
            'status'       => 'studying',
        ));

        $this->classId = $classSvc->createClass(array(
            'program_id'        => $programId,
            'subject_id'        => $subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp test',
            'price_per_session' => 500000,
            'total_sessions'    => 16,
            'hw_deadline_time'  => '12:00',
        ));
        $classSvc->enrollStudent($this->classId, $this->studentId);

        // Lịch cố định: thứ 2 + thứ 4
        $monday = date('Y-m-d', strtotime('monday this week'));
        $schedSvc->addSchedule($this->classId, 2, '17:30', '19:00', $monday);
        $schedSvc->addSchedule($this->classId, 4, '17:30', '19:00', $monday);
    }

    public function testGenerateForClassCreatesSessions()
    {
        $from = date('Y-m-d', strtotime('monday this week'));
        $to = date('Y-m-d', strtotime('+4 weeks'));
        $created = $this->svc->generateForClass($this->classId, $this->teacherId, $from, $to);
        // 4 tuần x 2 buổi = 8 (trừ các buổi đã qua nếu tuần này đã qua thứ 2/4)
        $this->assertGreaterThanOrEqual(1, $created);

        $count = $this->db->count('db_lms_sessions', 'class_id = ?', array($this->classId));
        $this->assertSame($created, $count);
    }

    public function testGenerateForClassIsIdempotent()
    {
        $from = date('Y-m-d', strtotime('monday this week'));
        $to = date('Y-m-d', strtotime('+4 weeks'));
        $first = $this->svc->generateForClass($this->classId, $this->teacherId, $from, $to);
        $second = $this->svc->generateForClass($this->classId, $this->teacherId, $from, $to);

        $this->assertSame(0, $second, 'Chạy lại không được sinh thêm session');
        $this->assertSame($first, (int) $this->db->count('db_lms_sessions', 'class_id = ?', array($this->classId)));
    }

    public function testGeneratedSessionsAreUpcoming()
    {
        $from = date('Y-m-d', strtotime('monday this week'));
        $this->svc->generateForClass($this->classId, $this->teacherId, $from, date('Y-m-d', strtotime('+4 weeks')));

        $rows = $this->db->safeFetch('select status from db_lms_sessions where class_id = ?', array($this->classId));
        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertSame('upcoming', $row['status']);
        }
    }

    public function testComputeHwDeadline()
    {
        $this->assertSame('2026-10-08 12:00:00', $this->svc->computeHwDeadline('2026-10-07', '12:00'));
        $this->assertSame('2026-10-08 12:00:00', $this->svc->computeHwDeadline('2026-10-07', null));
        $this->assertSame('2026-10-08 18:00:00', $this->svc->computeHwDeadline('2026-10-07', '18:00'));
    }

    public function testEnsureSessionStudents()
    {
        $from = date('Y-m-d', strtotime('monday this week'));
        $this->svc->generateForClass($this->classId, $this->teacherId, $from, date('Y-m-d', strtotime('+4 weeks')));

        $session = $this->db->safeFetchOne('select id from db_lms_sessions where class_id = ? limit 1', array($this->classId));
        $this->assertNotEmpty($session);

        $n = $this->svc->ensureSessionStudents((int) $session['id']);
        $this->assertSame(1, $n);

        // Chạy lại không tạo trùng
        $this->assertSame(1, $this->svc->ensureSessionStudents((int) $session['id']));
        $this->assertSame(1, (int) $this->db->count('db_lms_session_students', 'session_id = ?', array((int) $session['id'])));
    }

    public function testGenerateUpcomingAcrossActiveClasses()
    {
        // Tạo thêm 1 lớp active không có lịch → không sinh session
        $classSvc = new ClassService($this->db);
        $class2 = $classSvc->createClass(array(
            'program_id'        => 1,
            'subject_id'        => 1,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'group',
            'name'              => 'Lớp rỗng',
            'price_per_session' => 500000,
        ));

        $created = $this->svc->generateUpcoming(4);
        $this->assertGreaterThanOrEqual(1, $created);

        // Lớp không có lịch → 0 session
        $this->assertSame(0, (int) $this->db->count('db_lms_sessions', 'class_id = ?', array($class2)));
    }

    // ===================== TCH-01/02 — listWeek / summary / grading =====================

    /**
     * Tạo 1 session trong tuần hiện tại cho class của setUp.
     *
     * @param string $date
     * @return int session id
     */
    private function makeSession($date)
    {
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'session_date' => (string) $date,
            'start_time'   => '17:30',
            'end_time'     => '19:00',
            'status'       => 'upcoming',
        ));
        return (int) $this->db->insertId;
    }

    public function testListWeekReturnsOnlyOwnSessions()
    {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $wednesday = date('Y-m-d', strtotime('+2 days', strtotime($monday)));
        $this->makeSession($wednesday);

        // GV khác + session khác
        $userSvc = new \Lms\UserService($this->db);
        $otherTeacher = $userSvc->createUser('gv_sess2', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $otherTeacher,
            'teacher_code' => 'TE002',
            'full_name'    => 'GV Session 2',
            'status'       => 'active',
            'max_classes'  => 10,
        ));
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $this->classId,
            'teacher_id'   => $otherTeacher,
            'session_date' => $wednesday,
            'start_time'   => '20:00',
            'end_time'     => '21:00',
            'status'       => 'upcoming',
        ));

        $map = $this->svc->listWeek($this->teacherId, $monday, 1);
        $this->assertArrayHasKey($wednesday, $map);
        foreach ($map[$wednesday] as $row) {
            $this->assertSame((string) $this->teacherId, (string) $row['teacher_id']);
        }
    }

    // ===================== STU-01..04 — listWeekForStudent =====================

    public function testListWeekForStudentReturnsOnlyOwnSessions()
    {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $wednesday = date('Y-m-d', strtotime('+2 days', strtotime($monday)));
        $this->makeSession($wednesday);

        // HV khác không thuộc lớp → không thấy session nào
        $userSvc = new \Lms\UserService($this->db);
        $otherStudent = $userSvc->createUser('hv_sess2', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $otherStudent,
            'student_code' => 'ST002',
            'full_name'    => 'HV Session 2',
            'status'       => 'studying',
        ));

        $map = $this->svc->listWeekForStudent($this->studentId, $monday, 1);
        $this->assertArrayHasKey($wednesday, $map);
        $this->assertSame((string) $this->classId, (string) $map[$wednesday][0]['class_id']);

        $this->assertSame(array(), $this->svc->listWeekForStudent($otherStudent, $monday, 1));
    }

    public function testListWeekForStudentIgnoresInactiveEnrolment()
    {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $wednesday = date('Y-m-d', strtotime('+2 days', strtotime($monday)));
        $this->makeSession($wednesday);

        $this->db->update(
            'db_lms_class_students',
            array('status' => 'cancelled'),
            'class_id = ? and student_id = ?',
            array($this->classId, $this->studentId)
        );

        $this->assertSame(array(), $this->svc->listWeekForStudent($this->studentId, $monday, 1));
    }

    public function testUpdateLessonSummarySuccess()
    {
        $today = date('Y-m-d');
        $sessionId = $this->makeSession($today);

        // Topic thuộc subject của lớp
        $class = $this->db->safeFetchOne('select subject_id from db_lms_classes where id = ?', array($this->classId));
        $this->db->insert('db_lms_topics', array('subject_id' => (int) $class['subject_id'], 'name' => 'Chuyên đề 1'));
        $topicId = (int) $this->db->insertId;
        $this->db->insert('db_lms_topics', array('subject_id' => (int) $class['subject_id'], 'name' => 'Chuyên đề 2'));
        $topicId2 = (int) $this->db->insertId;

        $ok = $this->svc->updateLessonSummary($sessionId, $this->teacherId, array(
            'topic_ids'     => array($topicId2, $topicId),
            'lesson_summary' => 'Học chuyên đề 1',
            'hw_content'     => 'Làm bài trong sách',
        ));
        $this->assertTrue($ok);

        $row = $this->db->safeFetchOne('select * from db_lms_sessions where id = ?', array($sessionId));
        $this->assertSame('finished', $row['status']);
        $this->assertNotEmpty($row['summary_submitted_at']);
        $this->assertSame('Học chuyên đề 1', $row['lesson_summary']);
        $this->assertSame('Làm bài trong sách', $row['hw_content']);
        $this->assertSame(array($topicId2, $topicId), json_decode((string) $row['topic_ids'], true));
        $this->assertSame((string) $topicId2, (string) $row['topic_id'], 'topic_id = phần tử đầu topic_ids');
        $this->assertSame(1, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('update_lesson_summary')));
    }

    public function testUpdateLessonSummaryRowLevel()
    {
        $today = date('Y-m-d');
        $sessionId = $this->makeSession($today);

        $userSvc = new \Lms\UserService($this->db);
        $otherTeacher = $userSvc->createUser('gv_sess3', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $otherTeacher,
            'teacher_code' => 'TE003',
            'full_name'    => 'GV Session 3',
            'status'       => 'active',
        ));

        $ok = $this->svc->updateLessonSummary($sessionId, $otherTeacher, array(
            'lesson_summary' => 'Sửa của người khác',
        ));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('session_id', $this->svc->errors());
    }

    public function testUpdateLessonSummaryDeadlineLock()
    {
        // Session 2 ngày trước → chắc chắn đã quá 12h trưa hôm sau
        $past = date('Y-m-d', strtotime('-2 days'));
        $sessionId = $this->makeSession($past);

        $ok = $this->svc->updateLessonSummary($sessionId, $this->teacherId, array(
            'lesson_summary' => 'Quá hạn',
        ));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('summary', $this->svc->errors());
    }

    public function testUpdateLessonSummaryRejectsForeignTopic()
    {
        $today = date('Y-m-d');
        $sessionId = $this->makeSession($today);

        // Topic thuộc subject khác
        $this->db->insert('db_lms_subjects', array('program_id' => 1, 'name' => 'Môn khác'));
        $otherSubject = (int) $this->db->insertId;
        $this->db->insert('db_lms_topics', array('subject_id' => $otherSubject, 'name' => 'Chuyên đề lạ'));
        $otherTopic = (int) $this->db->insertId;

        $ok = $this->svc->updateLessonSummary($sessionId, $this->teacherId, array(
            'topic_ids' => array($otherTopic),
        ));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('topic_ids', $this->svc->errors());
    }

    public function testGradeHomeworkSuccess()
    {
        $today = date('Y-m-d');
        $sessionId = $this->makeSession($today);

        // HV nộp bài 2 ngày trước, class hw_check_hours mặc định 24 → đã chậm
        $this->db->insert('db_lms_session_students', array(
            'session_id'           => $sessionId,
            'student_id'           => $this->studentId,
            'hw_submitted_at'      => date('Y-m-d H:i:s', strtotime('-2 days')),
            'hw_submission_status' => 'submitted_on_time',
            'status'               => 'submitted',
        ));
        $ssId = (int) $this->db->insertId;

        $ok = $this->svc->gradeHomework($ssId, $this->teacherId, array(
            'grade'                => '18.5',
            'teacher_feedback_text' => 'Làm tốt',
        ));
        $this->assertTrue($ok);

        $row = $this->db->safeFetchOne('select * from db_lms_session_students where id = ?', array($ssId));
        $this->assertSame('graded', $row['status']);
        $this->assertSame('18.50', (string) $row['grade']);
        $this->assertSame('1', (string) $row['is_graded_late'], 'Quá hw_check_hours → is_graded_late = 1');
        $this->assertNotEmpty($row['hw_graded_at']);
        $this->assertSame(1, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('grade_homework')));
    }

    public function testGradeHomeworkRejectsInvalidGrade()
    {
        $today = date('Y-m-d');
        $sessionId = $this->makeSession($today);

        $this->db->insert('db_lms_session_students', array(
            'session_id'      => $sessionId,
            'student_id'      => $this->studentId,
            'hw_submitted_at' => date('Y-m-d H:i:s'),
            'status'          => 'submitted',
        ));
        $ssId = (int) $this->db->insertId;

        $ok = $this->svc->gradeHomework($ssId, $this->teacherId, array('grade' => '25'));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('grade', $this->svc->errors());
    }

    public function testGradeHomeworkRowLevel()
    {
        $today = date('Y-m-d');
        $sessionId = $this->makeSession($today);

        $this->db->insert('db_lms_session_students', array(
            'session_id'      => $sessionId,
            'student_id'      => $this->studentId,
            'hw_submitted_at' => date('Y-m-d H:i:s'),
            'status'          => 'submitted',
        ));
        $ssId = (int) $this->db->insertId;

        $userSvc = new \Lms\UserService($this->db);
        $otherTeacher = $userSvc->createUser('gv_sess4', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $otherTeacher,
            'teacher_code' => 'TE004',
            'full_name'    => 'GV Session 4',
            'status'       => 'active',
        ));

        $ok = $this->svc->gradeHomework($ssId, $otherTeacher, array('grade' => '10'));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('system', $this->svc->errors());
    }
}
