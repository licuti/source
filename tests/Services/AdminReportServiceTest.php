<?php
namespace Tests\Services;

use Lms\AdminReportService;
use Lms\ClassService;
use Lms\UserService;
use Tests\LmsTestCase;

class AdminReportServiceTest extends LmsTestCase
{
    private $svc;
    private $studentId;
    private $teacherId;
    private $classId;
    private $subjectId;
    private $topicId;
    private $sessionId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new AdminReportService($this->db);
        $users = new UserService($this->db);
        $this->db->insert('db_lms_programs', array('name' => 'Report program'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Report subject'));
        $this->subjectId = (int) $this->db->insertId;
        $this->db->insert('db_lms_topics', array('subject_id' => $this->subjectId, 'name' => 'Fractions', 'has_test' => 1, 'max_sessions' => 8, 'report_template' => 'Progress note'));
        $this->topicId = (int) $this->db->insertId;
        $this->teacherId = $users->createUser('report_teacher', 'password123', 'teacher');
        $this->db->insert('db_lms_teachers', array('user_id' => $this->teacherId, 'teacher_code' => 'RPT01', 'full_name' => 'Report Teacher', 'max_classes' => 2));
        $this->studentId = $users->createUser('report_student', 'password123', 'student');
        $this->db->insert('db_lms_students', array('user_id' => $this->studentId, 'student_code' => 'RPS01', 'full_name' => 'Report Student', 'status' => 'studying'));
        $this->classId = (new ClassService($this->db))->createClass(array(
            'program_id' => $programId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacherId,
            'class_type' => 'one_on_one',
            'name' => 'Report class',
            'student_ids' => array($this->studentId),
            'schedules' => array(array('day' => 2, 'start' => '17:00', 'end' => '18:00')),
            'start_date' => date('Y-m-d', strtotime('-2 weeks')),
        ));
        $this->assertNotNull($this->classId);
        $this->db->insert('db_lms_sessions', array(
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'topic_id' => $this->topicId,
            'session_date' => date('Y-m-d'),
            'start_time' => '17:00',
            'end_time' => '18:00',
            'status' => 'finished',
            'lesson_summary' => 'Summary text',
            'hw_content' => 'Worksheet 1',
            'summary_submitted_at' => date('Y-m-d H:i:s'),
        ));
        $sessionId = (int) $this->db->insertId;
        $this->sessionId = $sessionId;
        $this->db->insert('db_lms_session_students', array(
            'session_id' => $sessionId,
            'student_id' => $this->studentId,
            'hw_submission_status' => 'submitted_on_time',
            'status' => 'submitted',
        ));
    }

    public function testAdminCanUpdateSessionContentAndAuditChange()
    {
        $ok = $this->svc->adminUpdateSession($this->sessionId, array(
            'lesson_summary' => 'Updated summary',
            'hw_content' => 'Updated worksheet',
            'hw_deadline' => date('Y-m-d') . 'T20:00',
            'topic_ids' => array($this->topicId),
        ));
        $this->assertTrue($ok);
        $row = $this->db->safeFetchOne('select lesson_summary, hw_content, hw_deadline from db_lms_sessions where id = ?', array($this->sessionId));
        $this->assertSame('Updated summary', (string) $row['lesson_summary']);
        $this->assertSame('Updated worksheet', (string) $row['hw_content']);
        $this->assertSame(1, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('admin_update_session')));
    }

    public function testAdminGradeRequiresReasonWhenOverwritingGrade()
    {
        $ss = $this->db->safeFetchOne('select id from db_lms_session_students where session_id = ? limit 1', array($this->sessionId));
        $this->assertTrue($this->svc->adminGradeHomework((int) $ss['id'], array('grade' => '17')));
        $this->assertFalse($this->svc->adminGradeHomework((int) $ss['id'], array('grade' => '18')));
        $this->assertArrayHasKey('reason', $this->svc->errors());
        $this->assertTrue($this->svc->adminGradeHomework((int) $ss['id'], array('grade' => '18'), null, 'Điều chỉnh theo biên bản'));
        $this->assertSame(2, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('admin_grade_homework')));
    }

    public function testHomeworkReportAndFilters()
    {
        $all = $this->svc->homeworkList();
        $this->assertSame(1, $all['total']);
        $this->assertSame('Worksheet 1', $all['items'][0]['hw_content']);
        $this->assertSame('Fractions', $all['items'][0]['topic_name']);
        $this->assertSame(1, (int) $all['items'][0]['submissions_count']);
        $filtered = $this->svc->homeworkList(array('date_from' => '2099-01-01'));
        $this->assertSame(0, $filtered['total']);
    }

    public function testHomeworkListShowsUnreportedFinishedSessionsAndSkipsCancelled()
    {
        $this->db->insert('db_lms_sessions', array(
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'session_date' => date('Y-m-d', strtotime('-1 day')),
            'start_time' => '17:00',
            'end_time' => '18:00',
            'status' => 'finished',
        ));
        $unreported = (int) $this->db->insertId;
        $this->db->insert('db_lms_sessions', array(
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'session_date' => date('Y-m-d', strtotime('-1 day')),
            'start_time' => '19:00',
            'end_time' => '20:00',
            'status' => 'cancelled',
        ));

        $res = $this->svc->homeworkList();
        $this->assertSame(2, $res['total']);
        $ids = array_map(function ($r) { return (int) $r['session_id']; }, $res['items']);
        $this->assertContains($unreported, $ids);
        foreach ($res['items'] as $r) {
            $this->assertNotSame('cancelled', (string) $r['session_status']);
        }
    }

    public function testHomeworkListReturnsAllTopicsOfSession()
    {
        $this->db->insert('db_lms_topics', array('subject_id' => $this->subjectId, 'name' => 'Decimals'));
        $second = (int) $this->db->insertId;
        $this->db->update('db_lms_sessions', array(
            'topic_id'  => $this->topicId,
            'topic_ids' => json_encode(array($this->topicId, $second)),
        ), 'id = ?', array($this->sessionId));

        $res = $this->svc->homeworkList();
        $this->assertSame('Fractions, Decimals', $res['items'][0]['topic_name']);
    }

    public function testTopicListFilters()
    {
        $all = $this->svc->topicList(array('q' => 'Progress'));
        $this->assertSame(1, $all['total']);
        $this->assertSame('Report subject', $all['items'][0]['subject_name']);
        $this->assertSame(1, (int) $all['items'][0]['has_test']);
        $none = $this->svc->topicList(array('has_test' => '0'));
        $this->assertSame(0, $none['total']);
    }

    public function testSaveUpdateDuplicateAndDeleteTopic()
    {
        $newId = $this->svc->saveTopic(array(
            'name' => 'Unit 1 - Flower',
            'subject_id' => '__new__',
            'new_subject_name' => 'Reading',
            'has_test' => '0',
            'content' => 'Vocabulary about flowers',
            'max_sessions' => '5',
            'report_template' => '<p>Nhận xét mẫu</p>',
        ));
        $this->assertNotFalse($newId);
        $topic = $this->svc->getTopic($newId);
        $this->assertSame('Unit 1 - Flower', $topic['name']);
        $this->assertSame('Reading', $topic['subject_name']);
        $this->assertSame(0, (int) $topic['has_test']);
        $this->assertSame('Vocabulary about flowers', $topic['content']);
        $this->assertSame(5, (int) $topic['max_sessions']);
        $this->assertSame('<p>Nhận xét mẫu</p>', $topic['report_template']);

        // Update to Có test -> report_template cleared and has_minitest = 1
        $updated = $this->svc->saveTopic(array(
            'name' => 'Unit 1 - Flower Advanced',
            'subject_id' => (string) $topic['subject_id'],
            'has_test' => '1',
            'content' => 'Advanced reading',
            'max_sessions' => '6',
            'report_template' => '<p>Should be cleared</p>',
        ), $newId);
        $this->assertSame($newId, $updated);
        $afterUpdate = $this->svc->getTopic($newId);
        $this->assertSame(1, (int) $afterUpdate['has_test']);
        $this->assertSame(1, (int) $afterUpdate['has_minitest']);
        $this->assertNull($afterUpdate['report_template']);

        // Duplicate topic
        $dupId = $this->svc->duplicateTopic($newId);
        $this->assertNotFalse($dupId);
        $dupTopic = $this->svc->getTopic($dupId);
        $this->assertSame('Unit 1 - Flower Advanced (Bản sao)', $dupTopic['name']);

        // Delete duplicated and newly created topics
        $this->assertTrue($this->svc->deleteTopic($dupId));
        $this->assertTrue($this->svc->deleteTopic($newId));
        $this->assertEmpty($this->svc->getTopic($newId));
    }

    public function testLessonStatsByStudentAndDate()
    {
        $this->db->update('db_lms_students', array(
            'parent_phone' => '0909123456',
            'avatar' => 'uploads/student/avatar.png',
        ), 'user_id = ?', array($this->studentId));

        $data = $this->svc->lessonStats('RPS01');
        $this->assertSame('Report Student', $data['student']['full_name']);
        $this->assertSame('0909123456', $data['student']['phone']);
        $this->assertSame('uploads/student/avatar.png', $data['student']['avatar']);
        $this->assertGreaterThan(0, $data['total']);
        $this->assertSame(1, $data['finished_count']);
        $this->assertSame('RPT01', $data['items'][0]['teacher_code']);

        // Lookup by username and auto-swap reversed date range
        $byUsername = $this->svc->lessonStats('report_student', date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('-1 month')));
        $this->assertSame('RPS01', $byUsername['student']['student_code']);
        $this->assertGreaterThan(0, $byUsername['total']);

        $this->assertSame(0, $this->svc->lessonStats('unknown-code')['total']);
        $this->assertSame(0, $this->svc->lessonStats('RPS01', '2099-01-01')['total']);
    }
}
