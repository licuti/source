<?php
/**
 * Tests cho AdminRequestService — 5 tab Quản lý yêu cầu (ADM-05).
 */

namespace Tests\Services;

use Lms\AdminRequestService;
use Lms\ClassService;
use Lms\ScheduleService;
use Tests\LmsTestCase;

class AdminRequestServiceTest extends LmsTestCase
{
    /** @var AdminRequestService */
    private $svc;

    /** @var int */
    private $adminId;

    /** @var int */
    private $teacherId;

    /** @var int */
    private $studentId;

    /** @var int */
    private $classId;

    /** @var int */
    private $subjectId;

    /** @var int */
    private $topicId;

    /** @var int */
    private $sessionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = new AdminRequestService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'Chương trình yêu cầu'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Môn yêu cầu'));
        $this->subjectId = (int) $this->db->insertId;

        $userSvc = new \Lms\UserService($this->db);

        $this->adminId = $userSvc->createUser('admin_yc', 'matkhau123', 'admin');
        $this->db->insert('db_lms_admins', array(
            'user_id'    => $this->adminId,
            'full_name'  => 'Admin yêu cầu',
            'role_level' => 'super_admin',
        ));

        $this->teacherId = $userSvc->createUser('gv_yc', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->teacherId,
            'teacher_code' => 'TEA06',
            'full_name'    => 'GV Yêu Cầu',
            'status'       => 'active',
            'max_classes'  => 10,
        ));

        $this->studentId = $userSvc->createUser('hv_yc', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $this->studentId,
            'student_code' => 'STA06',
            'full_name'    => 'HV Yêu Cầu',
            'parent_phone' => '0900000001',
            'status'       => 'studying',
        ));

        $classSvc = new ClassService($this->db, new ScheduleService($this->db));
        $this->classId = $classSvc->createClass(array(
            'program_id'        => $programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp yêu cầu',
            'price_per_session' => 500000,
            'total_sessions'    => 16,
            'student_ids'       => array($this->studentId),
            'schedules'         => array(
                array('day' => 2, 'start' => '18:00', 'end' => '19:30'),
            ),
            'start_date'        => date('Y-m-d'),
        ));
        $this->assertGreaterThan(0, $this->classId);

        $this->db->insert('db_lms_topics', array(
            'subject_id'   => $this->subjectId,
            'name'         => 'Chuyên đề yêu cầu',
            'has_test'     => 1,
            'has_minitest' => 1,
            'max_sessions' => 4,
        ));
        $this->topicId = (int) $this->db->insertId;

        // 1 buổi đã dạy thuộc chuyên đề → tiến độ chuyên đề 1/4.
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'topic_id'     => $this->topicId,
            'session_date' => date('Y-m-d', strtotime('-2 days')),
            'start_time'   => '08:00:00',
            'end_time'     => '09:30:00',
            'status'       => 'finished',
        ));

        $session = $this->db->safeFetchOne(
            'select id from db_lms_sessions where class_id = ? and session_date >= ?'
            . ' order by session_date, start_time limit 1',
            array($this->classId, date('Y-m-d'))
        );
        $this->sessionId = !empty($session) ? (int) $session['id'] : 0;
        $this->assertGreaterThan(0, $this->sessionId);
    }

    /**
     * @param string $type
     * @param array  $payload
     * @param string $status
     * @param array  $extra
     * @return int
     */
    private function insertRequest($type, array $payload, $status = 'pending', array $extra = array())
    {
        $row = array_merge(array(
            'request_type' => $type,
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'student_id'   => $this->studentId,
            'title'        => 'Yêu cầu test',
            'payload'      => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status'       => $status,
        ), $extra);
        $this->db->insert('db_lms_requests', $row);
        return (int) $this->db->insertId;
    }

    /**
     * @param string $status
     * @return int
     */
    private function insertTest($status = 'draft')
    {
        $this->db->insert('db_lms_tests', array(
            'class_id'     => $this->classId,
            'topic_id'     => $this->topicId,
            'test_type'    => 'test',
            'title'        => 'Đề test',
            'test_file_url' => 'test/202610/de.pdf',
            'deadline'     => date('Y-m-d H:i:s', strtotime('+7 days')),
            'max_score'    => 20,
            'status'       => $status,
        ));
        return (int) $this->db->insertId;
    }

    /**
     * @param int        $testId
     * @param string|null $submittedAt
     * @param string|null $gradedAt
     * @return int
     */
    private function insertSubmission($testId, $submittedAt = 'now', $gradedAt = null)
    {
        $this->db->insert('db_lms_test_submissions', array(
            'test_id'           => $testId,
            'student_id'        => $this->studentId,
            'submission_files'  => json_encode(array('test/202610/bai.pdf')),
            'submitted_at'      => $submittedAt === 'now' ? date('Y-m-d H:i:s') : $submittedAt,
            'submission_status' => 'submitted_on_time',
            'graded_at'         => $gradedAt,
        ));
        return (int) $this->db->insertId;
    }

    public function testListAddSessionFilters()
    {
        $this->insertRequest('add_session', array('session_date' => date('Y-m-d', strtotime('+10 days'))));
        $this->insertRequest('add_session', array('session_date' => date('Y-m-d', strtotime('+11 days'))));
        $this->insertRequest('add_session', array('session_date' => date('Y-m-d', strtotime('+12 days'))), 'approved');
        $this->insertRequest('request_leave', array('reason' => 'Bệnh'), 'pending', array('session_id' => $this->sessionId));

        $res = $this->svc->listAddSessionRequests();
        $this->assertSame(2, (int) $res['total']);
        $this->assertCount(2, $res['items']);
        $this->assertSame(16, (int) $res['items'][0]['total_sessions']);
        $this->assertSame('Chuyên đề yêu cầu', (string) $res['items'][0]['current_topic']);
        $this->assertSame(1, (int) $res['items'][0]['topic_done']);
        $this->assertSame(4, (int) $res['items'][0]['topic_max']);

        $res = $this->svc->listAddSessionRequests(array('q' => 'STA06'));
        $this->assertSame(2, (int) $res['total']);

        $res = $this->svc->listAddSessionRequests(array('q' => 'Yêu Cầu'));
        $this->assertSame(2, (int) $res['total']);

        $res = $this->svc->listAddSessionRequests(array('q' => 'không tồn tại'));
        $this->assertSame(0, (int) $res['total']);

        $res = $this->svc->listAddSessionRequests(array('phone' => '0900000001'));
        $this->assertSame(2, (int) $res['total']);

        $res = $this->svc->listAddSessionRequests(array('phone' => '0999999999'));
        $this->assertSame(0, (int) $res['total']);
    }

    public function testListTestRequestsOnlyDraft()
    {
        $this->insertTest('draft');
        $this->insertTest('published');

        $res = $this->svc->listTestRequests();
        $this->assertSame(1, (int) $res['total']);
        $this->assertSame('Chuyên đề yêu cầu', (string) $res['items'][0]['topic_name']);
        $this->assertSame(1, (int) $res['items'][0]['topic_done']);
        $this->assertSame(4, (int) $res['items'][0]['topic_max']);

        $classCode = (string) $this->db->safeFetchOne(
            'select class_code from db_lms_classes where id = ?',
            array($this->classId)
        )['class_code'];
        $res = $this->svc->listTestRequests(array('q' => $classCode));
        $this->assertSame(1, (int) $res['total']);

        $res = $this->svc->listTestRequests(array('q' => 'TEA06'));
        $this->assertSame(1, (int) $res['total']);

        $res = $this->svc->listTestRequests(array('q' => 'không tồn tại'));
        $this->assertSame(0, (int) $res['total']);
    }

    public function testListTestResultsOnlyUngradedSubmitted()
    {
        $testId = $this->insertTest('published');
        $this->insertSubmission($testId);
        $this->insertSubmission($this->insertTest('published'), 'now', date('Y-m-d H:i:s'));
        $this->insertSubmission($this->insertTest('published'), null);

        $res = $this->svc->listTestResults();
        $this->assertSame(1, (int) $res['total']);
        $this->assertSame('STA06', (string) $res['items'][0]['student_code']);
        $this->assertSame('HV Yêu Cầu', (string) $res['items'][0]['student_name']);
    }

    public function testListScheduleRequestsDecodesPayloadAndOldSchedule()
    {
        $this->insertRequest('change_schedule', array(
            'slots' => array(array('date' => date('Y-m-d', strtotime('+10 days')), 'start' => '18:00', 'end' => '19:30')),
        ));
        $this->insertRequest('request_leave', array('reason' => 'Bệnh'), 'pending', array('session_id' => $this->sessionId));
        $this->insertRequest('change_schedule', array('slots' => array()), 'approved');

        $res = $this->svc->listScheduleRequests();
        $this->assertSame(2, (int) $res['total']);

        $change = null;
        foreach ($res['items'] as $row) {
            if ((string) $row['request_type'] === 'change_schedule') {
                $change = $row;
            }
        }
        $this->assertNotNull($change);
        $this->assertIsArray($change['payload']);
        $this->assertCount(1, $change['payload']['slots']);
        $this->assertStringContainsString('T2 18:00-19:30', (string) $change['old_schedule']);
    }

    public function testListLowSessionClassesBelowThreshold()
    {
        $this->db->update('db_lms_classes', array('remaining_sessions' => 4), 'id = ?', array($this->classId));

        $res = $this->svc->listLowSessionClasses();
        $this->assertSame(1, (int) $res['total']);
        $this->assertSame(4, (int) $res['items'][0]['remaining_sessions']);

        $res = $this->svc->listLowSessionClasses(array('q' => 'TEA06'));
        $this->assertSame(1, (int) $res['total']);

        $this->db->update('db_lms_classes', array('remaining_sessions' => 5), 'id = ?', array($this->classId));
        $res = $this->svc->listLowSessionClasses();
        $this->assertSame(0, (int) $res['total']);
    }

    public function testPublishTestOnlyDraft()
    {
        $testId = $this->insertTest('draft');

        $this->assertTrue($this->svc->publishTest($testId, $this->adminId));
        $row = $this->db->safeFetchOne('select status from db_lms_tests where id = ?', array($testId));
        $this->assertSame('published', (string) $row['status']);

        $this->assertFalse($this->svc->publishTest($testId, $this->adminId));
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('status', $errors);

        $this->assertFalse($this->svc->publishTest(999999, $this->adminId));
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('system', $errors);
    }

    public function testDeleteTestRemovesSubmissions()
    {
        $testId = $this->insertTest('draft');
        $this->insertSubmission($testId);

        $this->assertTrue($this->svc->deleteTest($testId, $this->adminId));
        $row = $this->db->safeFetchOne('select id from db_lms_tests where id = ?', array($testId));
        $this->assertEmpty($row);
        $subs = $this->db->safeFetchOne('select count(*) as c from db_lms_test_submissions where test_id = ?', array($testId));
        $this->assertSame(0, (int) $subs['c']);

        $this->assertFalse($this->svc->deleteTest(999999, $this->adminId));
    }

    public function testUpdateSubmissionValidationAndApprove()
    {
        $testId = $this->insertTest('published');
        $subId  = $this->insertSubmission($testId);

        // Xác nhận khi chưa nhập điểm → lỗi
        $this->assertFalse($this->svc->updateSubmission($subId, $this->adminId, array(), null, true));
        $this->assertArrayHasKey('score_x', $this->svc->errors());

        // Điểm vượt max → lỗi
        $this->assertFalse($this->svc->updateSubmission($subId, $this->adminId, array(
            'score_x' => 25, 'score_y_percent' => 50,
        ), null, true));
        $this->assertArrayHasKey('score_x', $this->svc->errors());

        // Phần trăm > 100 → lỗi
        $this->assertFalse($this->svc->updateSubmission($subId, $this->adminId, array(
            'score_x' => 10, 'score_y_percent' => 120,
        ), null, true));
        $this->assertArrayHasKey('score_y_percent', $this->svc->errors());

        // Hợp lệ → lưu + graded_at
        $this->assertTrue($this->svc->updateSubmission($subId, $this->adminId, array(
            'score_x' => 15, 'score_y_percent' => 75,
        ), null, true));
        $row = $this->db->safeFetchOne('select score_x, score_y_percent, graded_at from db_lms_test_submissions where id = ?', array($subId));
        $this->assertSame('15.00', number_format((float) $row['score_x'], 2, '.', ''));
        $this->assertSame('75.00', number_format((float) $row['score_y_percent'], 2, '.', ''));
        $this->assertNotEmpty($row['graded_at']);

        // Cập nhật điểm (không xác nhận) vẫn giữ graded_at
        $this->assertTrue($this->svc->updateSubmission($subId, $this->adminId, array(
            'score_x' => 10, 'score_y_percent' => 50,
        )));
        $row = $this->db->safeFetchOne('select score_x, graded_at from db_lms_test_submissions where id = ?', array($subId));
        $this->assertSame('10.00', number_format((float) $row['score_x'], 2, '.', ''));
        $this->assertNotEmpty($row['graded_at']);
    }

    public function testDeleteSubmission()
    {
        $subId = $this->insertSubmission($this->insertTest('published'));

        $this->assertTrue($this->svc->deleteSubmission($subId, $this->adminId));
        $row = $this->db->safeFetchOne('select id from db_lms_test_submissions where id = ?', array($subId));
        $this->assertEmpty($row);

        $this->assertFalse($this->svc->deleteSubmission(999999, $this->adminId));
    }

    public function testDeleteRequestOnlyPending()
    {
        $pendingId  = $this->insertRequest('change_schedule', array('slots' => array()));
        $approvedId = $this->insertRequest('change_schedule', array('slots' => array()), 'approved');

        $this->assertTrue($this->svc->deleteRequest($pendingId, $this->adminId));
        $row = $this->db->safeFetchOne('select id from db_lms_requests where id = ?', array($pendingId));
        $this->assertEmpty($row);

        $this->assertFalse($this->svc->deleteRequest($approvedId, $this->adminId));
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('status', $errors);

        $this->assertFalse($this->svc->deleteRequest(999999, $this->adminId));
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('system', $errors);
    }
}
