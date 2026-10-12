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
        $this->db->insert('db_lms_session_students', array(
            'session_id' => $sessionId,
            'student_id' => $this->studentId,
            'hw_submission_status' => 'submitted_on_time',
            'status' => 'submitted',
        ));
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

    public function testTopicListFilters()
    {
        $all = $this->svc->topicList(array('q' => 'Progress'));
        $this->assertSame(1, $all['total']);
        $this->assertSame('Report subject', $all['items'][0]['subject_name']);
        $this->assertSame(1, (int) $all['items'][0]['has_test']);
        $none = $this->svc->topicList(array('has_test' => '0'));
        $this->assertSame(0, $none['total']);
    }

    public function testLessonStatsByStudentAndDate()
    {
        $data = $this->svc->lessonStats('RPS01');
        $this->assertSame('Report Student', $data['student']['full_name']);
        $this->assertGreaterThan(0, $data['total']);
        $this->assertSame(1, $data['finished_count']);
        $this->assertSame('RPT01', $data['items'][0]['teacher_code']);
        $this->assertSame(0, $this->svc->lessonStats('unknown-code')['total']);
        $this->assertSame(0, $this->svc->lessonStats('RPS01', '2099-01-01')['total']);
    }
}
