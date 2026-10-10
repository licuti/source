<?php
/**
 * Tests cho StudentCourseService — portal Student (STU-01/02/03).
 *
 * Row-level mọi nơi: HV chỉ thấy lớp/session/test của mình; nộp BTVN/test
 * đúng trạng thái on_time/late theo deadline, các case lỗi theo plan.
 */

namespace Tests\Services;

use Lms\ClassService;
use Lms\ScheduleService;
use Lms\StudentCourseService;
use Tests\LmsTestCase;

class StudentCourseServiceTest extends LmsTestCase
{
    /** @var StudentCourseService */
    private $svc;

    /** @var int */
    private $classId;

    /** @var int */
    private $teacherId;

    /** @var int */
    private $studentId;

    /** @var int */
    private $otherStudentId;

    /** @var int */
    private $topicId;

    /** @var int session hôm nay (hw_deadline tương lai → on_time) */
    private $sessionTodayId;

    /** @var int session hôm nay (hw_deadline quá khứ → late) */
    private $sessionLateId;

    /** @var int session tương lai */
    private $sessionFutureId;

    /** @var array $_FILES giả (file PDF tạm) */
    private $filesFixture = array();

    /** @var array đường dẫn file tạm cần xoá */
    private $tempFiles = array();

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = new StudentCourseService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'Chương trình student'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Môn student'));
        $subjectId = (int) $this->db->insertId;
        $this->db->insert('db_lms_topics', array(
            'subject_id'   => $subjectId,
            'name'         => 'Chuyên đề student',
            'has_test'     => 1,
            'has_minitest' => 1,
            'max_sessions' => 4,
            'order_index'  => 1,
        ));
        $this->topicId = (int) $this->db->insertId;

        $userSvc = new \Lms\UserService($this->db);
        $this->teacherId = $userSvc->createUser('gv_stu', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->teacherId,
            'teacher_code' => 'TEST01',
            'full_name'    => 'GV Student',
            'status'       => 'active',
            'max_classes'  => 10,
        ));

        $this->studentId = $userSvc->createUser('hv_stu', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $this->studentId,
            'student_code' => 'STS01',
            'full_name'    => 'HV Student',
            'status'       => 'studying',
        ));
        $this->otherStudentId = $userSvc->createUser('hv_stu2', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $this->otherStudentId,
            'student_code' => 'STS02',
            'full_name'    => 'HV Student 2',
            'status'       => 'studying',
        ));

        $schedSvc = new ScheduleService($this->db);
        $classSvc = new ClassService($this->db, $schedSvc);

        $this->classId = $classSvc->createClass(array(
            'program_id'        => $programId,
            'subject_id'        => $subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp student',
            'price_per_session' => 500000,
            'total_sessions'    => 16,
            'student_ids'       => array($this->studentId),
            'schedules'         => array(
                array('day' => 2, 'start' => '18:00', 'end' => '19:30'),
            ),
            'start_date'        => date('Y-m-d', strtotime('monday this week')),
        ));

        $topicIdsJson = json_encode(array($this->topicId), JSON_UNESCAPED_UNICODE);

        // Session hôm nay, hạn nộp còn 2 tiếng → on_time
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'session_date' => date('Y-m-d'),
            'start_time'   => '18:00',
            'end_time'     => '19:30',
            'status'       => 'finished',
            'topic_id'     => $this->topicId,
            'topic_ids'    => $topicIdsJson,
            'hw_content'   => 'Làm bài tập trang 5',
            'hw_deadline'  => date('Y-m-d H:i:s', strtotime('+2 hours')),
        ));
        $this->sessionTodayId = (int) $this->db->insertId;

        // Session hôm nay, hạn nộp đã qua 2 tiếng → late
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'session_date' => date('Y-m-d'),
            'start_time'   => '19:30',
            'end_time'     => '21:00',
            'status'       => 'finished',
            'topic_id'     => $this->topicId,
            'topic_ids'    => $topicIdsJson,
            'hw_deadline'  => date('Y-m-d H:i:s', strtotime('-2 hours')),
        ));
        $this->sessionLateId = (int) $this->db->insertId;

        // Session tương lai → chưa nộp được
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'session_date' => date('Y-m-d', strtotime('+3 days')),
            'start_time'   => '18:00',
            'end_time'     => '19:30',
            'status'       => 'upcoming',
            'topic_id'     => $this->topicId,
            'topic_ids'    => $topicIdsJson,
        ));
        $this->sessionFutureId = (int) $this->db->insertId;

        $this->filesFixture = $this->makeFilesFixture('baitap.pdf');
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $tmp) {
            @unlink($tmp);
        }
        // Dọn file do lms_save_files ghi ra (chỉ tồn tại khi test nộp bài)
        $this->removeUploadSubdirs();
        parent::tearDown();
    }

    /**
     * Xoá các thư mục con hw/ + test/ trong uploads (chỉ do test tạo ra).
     */
    private function removeUploadSubdirs()
    {
        $base = dirname(__DIR__, 2) . '/lms/public/uploads';
        foreach (array('hw', 'test') as $sub) {
            $dir = $base . '/' . $sub;
            if (!is_dir($dir)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) {
                $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath());
            }
            @rmdir($dir);
        }
    }

    /**
     * Tạo $_FILES giả dạng multi-file với 1 file PDF thật (finfo MIME hợp lệ).
     *
     * @param string $name
     * @return array
     */
    private function makeFilesFixture($name)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'lmsstu');
        file_put_contents($tmp, "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n");
        $this->tempFiles[] = $tmp;
        return array(
            'name'     => array($name),
            'tmp_name' => array($tmp),
            'error'    => array(UPLOAD_ERR_OK),
            'size'     => array(filesize($tmp)),
        );
    }

    // ===================== listMyClasses =====================

    public function testListMyClassesReturnsOwnActiveClass()
    {
        $classes = $this->svc->listMyClasses($this->studentId);
        $this->assertCount(1, $classes);
        $this->assertSame((string) $this->classId, (string) $classes[0]['id']);
        $this->assertSame('Lớp student', (string) $classes[0]['name']);
        $this->assertArrayHasKey('progress_percent', $classes[0]);
    }

    public function testListMyClassesRowLevelOtherStudentSeesNothing()
    {
        $this->assertSame(array(), $this->svc->listMyClasses($this->otherStudentId));
    }

    public function testListMyClassesIgnoresInactiveEnrolment()
    {
        // Rút HV khỏi lớp (status != active)
        $this->db->update(
            'db_lms_class_students',
            array('status' => 'cancelled'),
            'class_id = ? and student_id = ?',
            array($this->classId, $this->studentId)
        );
        $this->assertSame(array(), $this->svc->listMyClasses($this->studentId));
    }

    // ===================== listTopicsForClass =====================

    public function testListTopicsForClassReturnsTopicWithProgress()
    {
        $data = $this->svc->listTopicsForClass($this->classId, $this->studentId);
        $this->assertCount(1, $data['topics']);
        $this->assertSame('Chuyên đề student', (string) $data['topics'][0]['name']);
        // 2 session hôm nay có topic → done_sessions = 2
        $this->assertSame(2, (int) $data['topics'][0]['done_sessions']);
        $this->assertSame(4, (int) $data['topics'][0]['max_sessions']);
    }

    public function testListTopicsForClassRowLevel()
    {
        $data = $this->svc->listTopicsForClass($this->classId, $this->otherStudentId);
        $this->assertSame(array('topics' => array(), 'tests' => array()), $data);
    }

    // ===================== listSessionsForTopic =====================

    public function testListSessionsForTopicReturnsOwnSessions()
    {
        $rows = $this->svc->listSessionsForTopic($this->classId, $this->topicId, $this->studentId);
        // 2 session hôm nay (1 on_time + 1 late), session tương lai cũng thuộc topic
        $this->assertGreaterThanOrEqual(3, count($rows));
        foreach ($rows as $r) {
            $this->assertSame((string) $this->classId, (string) $r['class_id']);
        }
    }

    public function testListSessionsForTopicRowLevel()
    {
        $this->assertSame(
            array(),
            $this->svc->listSessionsForTopic($this->classId, $this->topicId, $this->otherStudentId)
        );
    }

    // ===================== getSessionForStudent =====================

    public function testGetSessionForStudentRowLevel()
    {
        $row = $this->svc->getSessionForStudent($this->sessionTodayId, $this->studentId);
        $this->assertIsArray($row);
        $this->assertSame('Làm bài tập trang 5', (string) $row['hw_content']);

        $this->assertNull($this->svc->getSessionForStudent($this->sessionTodayId, $this->otherStudentId));
    }

    // ===================== submitHomework =====================

    public function testSubmitHomeworkOnTime()
    {
        $ok = $this->svc->submitHomework(
            $this->sessionTodayId,
            $this->studentId,
            $this->filesFixture,
            false,
            array('cli' => true)
        );
        $this->assertTrue($ok, (string) json_encode($this->svc->errors()));

        $ss = $this->db->safeFetchOne(
            'select * from db_lms_session_students where session_id = ? and student_id = ? limit 1',
            array($this->sessionTodayId, $this->studentId)
        );
        $this->assertIsArray($ss);
        $this->assertSame('submitted_on_time', (string) $ss['hw_submission_status']);
        $this->assertSame('submitted', (string) $ss['status']);
        $this->assertNotEmpty($ss['hw_submitted_at']);
        $files = \Lms\UserService::decodeJsonArray($ss['hw_submission_files']);
        $this->assertCount(1, $files);
    }

    public function testSubmitHomeworkLate()
    {
        $ok = $this->svc->submitHomework(
            $this->sessionLateId,
            $this->studentId,
            $this->filesFixture,
            false,
            array('cli' => true)
        );
        $this->assertTrue($ok, (string) json_encode($this->svc->errors()));

        $ss = $this->db->safeFetchOne(
            'select hw_submission_status from db_lms_session_students'
            . ' where session_id = ? and student_id = ? limit 1',
            array($this->sessionLateId, $this->studentId)
        );
        $this->assertSame('submitted_late', (string) $ss['hw_submission_status']);
    }

    public function testSubmitHomeworkFutureSessionFails()
    {
        $ok = $this->svc->submitHomework(
            $this->sessionFutureId,
            $this->studentId,
            $this->filesFixture,
            false,
            array('cli' => true)
        );
        $this->assertFalse($ok);
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('session_id', $errors);

        // Không có bài nộp nào được ghi
        $count = $this->db->count(
            'db_lms_session_students',
            'session_id = ? and student_id = ?',
            array($this->sessionFutureId, $this->studentId)
        );
        $this->assertSame(0, (int) $count);
    }

    public function testSubmitHomeworkRowLevelFails()
    {
        $ok = $this->svc->submitHomework(
            $this->sessionTodayId,
            $this->otherStudentId,
            $this->filesFixture,
            false,
            array('cli' => true)
        );
        $this->assertFalse($ok);
        $this->assertArrayHasKey('session_id', $this->svc->errors());
    }

    public function testSubmitHomeworkRequiresAtLeastOneFile()
    {
        $ok = $this->svc->submitHomework(
            $this->sessionTodayId,
            $this->studentId,
            array('name' => array(), 'tmp_name' => array(), 'error' => array(), 'size' => array()),
            false,
            array('cli' => true)
        );
        $this->assertFalse($ok);
        $this->assertArrayHasKey('hw_files', $this->svc->errors());
    }

    public function testSubmitHomeworkAudited()
    {
        $this->svc->submitHomework(
            $this->sessionTodayId,
            $this->studentId,
            $this->filesFixture,
            false,
            array('cli' => true)
        );
        $count = $this->db->count(
            'db_lms_audit_logs',
            'action = ? and target_type = ? and actor_type = ?',
            array('submit_homework', 'session_students', 'student')
        );
        $this->assertGreaterThanOrEqual(1, (int) $count);
    }

    // ===================== submitTest =====================

    /**
     * Tạo bài test cho class.
     *
     * @param string $testType
     * @param string $status
     * @param string $deadlineModifier
     * @return int test id
     */
    private function makeTest($testType, $status, $deadlineModifier)
    {
        $this->db->insert('db_lms_tests', array(
            'class_id'    => $this->classId,
            'topic_id'    => $this->topicId,
            'test_type'   => $testType,
            'title'       => 'Test ' . $testType,
            'deadline'    => date('Y-m-d H:i:s', strtotime($deadlineModifier)),
            'max_score'   => 20.00,
            'status'      => $status,
            'created_by'  => $this->teacherId,
        ));
        return (int) $this->db->insertId;
    }

    public function testSubmitTestOnTime()
    {
        $testId = $this->makeTest('test', 'published', '+3 days');

        $ok = $this->svc->submitTest($testId, $this->studentId, $this->filesFixture, array('cli' => true));
        $this->assertTrue($ok, (string) json_encode($this->svc->errors()));

        $sub = $this->db->safeFetchOne(
            'select * from db_lms_test_submissions where test_id = ? and student_id = ? limit 1',
            array($testId, $this->studentId)
        );
        $this->assertIsArray($sub);
        $this->assertSame('submitted_on_time', (string) $sub['submission_status']);
        $this->assertNotEmpty($sub['submitted_at']);
    }

    public function testSubmitTestLate()
    {
        $testId = $this->makeTest('test', 'published', '-1 days');

        $ok = $this->svc->submitTest($testId, $this->studentId, $this->filesFixture, array('cli' => true));
        $this->assertTrue($ok, (string) json_encode($this->svc->errors()));

        $sub = $this->db->safeFetchOne(
            'select submission_status from db_lms_test_submissions where test_id = ? limit 1',
            array($testId)
        );
        $this->assertSame('submitted_late', (string) $sub['submission_status']);
    }

    public function testSubmitTestDraftFails()
    {
        $testId = $this->makeTest('test', 'draft', '+3 days');

        $ok = $this->svc->submitTest($testId, $this->studentId, $this->filesFixture, array('cli' => true));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('test_id', $this->svc->errors());
    }

    public function testSubmitMinitestDraftNotApprovedFails()
    {
        $testId = $this->makeTest('minitest', 'draft', '+3 days');

        $ok = $this->svc->submitTest($testId, $this->studentId, $this->filesFixture, array('cli' => true));
        $this->assertFalse($ok);
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('test_id', $errors);
        $this->assertStringContainsString('minitest', (string) $errors['test_id']);
    }

    public function testSubmitTestClosedFails()
    {
        $testId = $this->makeTest('test', 'closed', '+3 days');

        $ok = $this->svc->submitTest($testId, $this->studentId, $this->filesFixture, array('cli' => true));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('test_id', $this->svc->errors());
    }

    public function testSubmitTestGradedFails()
    {
        $testId = $this->makeTest('test', 'published', '+3 days');
        // Tạo bài nộp đã chấm
        $this->db->insert('db_lms_test_submissions', array(
            'test_id'           => $testId,
            'student_id'        => $this->studentId,
            'submission_status' => 'submitted_on_time',
            'score_x'           => 18.00,
            'score_y_percent'   => 90.00,
            'graded_at'         => date('Y-m-d H:i:s'),
        ));

        $ok = $this->svc->submitTest($testId, $this->studentId, $this->filesFixture, array('cli' => true));
        $this->assertFalse($ok);
        $errors = $this->svc->errors();
        $this->assertStringContainsString('chấm', (string) reset($errors));
    }

    public function testSubmitTestRowLevelFails()
    {
        $testId = $this->makeTest('test', 'published', '+3 days');

        $ok = $this->svc->submitTest($testId, $this->otherStudentId, $this->filesFixture, array('cli' => true));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('test_id', $this->svc->errors());
    }

    // ===================== hwDeadline + countdown =====================

    public function testHwDeadlineUsesSessionDeadlineWhenSet()
    {
        $session = $this->svc->getSessionForStudent($this->sessionTodayId, $this->studentId);
        $deadline = $this->svc->hwDeadline($session);
        $this->assertSame(
            date('Y-m-d H:i:s', strtotime('+2 hours')),
            (string) $deadline
        );
    }

    public function testHwDeadlineFallsBackToClassDefault()
    {
        // Session tương lai không có hw_deadline → session_date + 1 ngày + 12:00
        $session = $this->svc->getSessionForStudent($this->sessionFutureId, $this->studentId);
        $deadline = $this->svc->hwDeadline($session);
        $expected = date('Y-m-d', strtotime('+3 days +1 day')) . ' 12:00:00';
        $this->assertSame($expected, (string) $deadline);
    }

    public function testDeadlineCountdownFuture()
    {
        $cd = $this->svc->deadlineCountdown(date('Y-m-d H:i:s', strtotime('+3 hours')));
        $this->assertFalse($cd['overdue']);
        $this->assertStringContainsString('còn', (string) $cd['text']);
    }

    public function testDeadlineCountdownOverdue()
    {
        $cd = $this->svc->deadlineCountdown(date('Y-m-d H:i:s', strtotime('-3 hours')));
        $this->assertTrue($cd['overdue']);
        $this->assertStringContainsString('trễ', (string) $cd['text']);
    }
}
