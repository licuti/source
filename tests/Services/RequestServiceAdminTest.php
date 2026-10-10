<?php
/**
 * Tests cho RequestService — phần admin duyệt/từ chối (ADM-05).
 *
 * Xem plan .kilo/plans/1791466000-adm05-yeu-cau.md Task 4.
 */

namespace Tests\Services;

use Lms\ClassService;
use Lms\RequestService;
use Lms\ScheduleService;
use Lms\SessionService;
use Tests\LmsTestCase;

class RequestServiceAdminTest extends LmsTestCase
{
    /** @var RequestService */
    private $svc;

    /** @var int admin user id (db_lms_admins.user_id) */
    private $adminId;

    /** @var int */
    private $teacherId;

    /** @var int */
    private $classId;

    /** @var int */
    private $studentId;

    /** @var int session upcoming đầu tiên của lớp */
    private $sessionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = new RequestService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'Chương trình admin test'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Môn admin test'));
        $subjectId = (int) $this->db->insertId;

        $userSvc = new \Lms\UserService($this->db);

        // Admin (processed_by FK → db_lms_admins.user_id)
        $this->adminId = $userSvc->createUser('admin_adm05', 'matkhau123', 'admin');
        $this->db->insert('db_lms_admins', array(
            'user_id'    => $this->adminId,
            'full_name'  => 'Admin ADM-05',
            'role_level' => 'super_admin',
        ));

        $this->teacherId = $userSvc->createUser('gv_adm05', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->teacherId,
            'teacher_code' => 'TEA05',
            'full_name'    => 'GV ADM-05',
            'status'       => 'active',
            'max_classes'  => 10,
        ));

        $this->studentId = $userSvc->createUser('hv_adm05', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $this->studentId,
            'student_code' => 'STA05',
            'full_name'    => 'HV ADM-05',
            'status'       => 'studying',
        ));

        // Lớp có lịch cố định T2 18:00-19:30 → sinh sessions 4 tuần tới.
        // Lưu ý: các buổi sinh ra nằm vào thứ 2 → test dùng các ngày khác để
        // không nhầm với session cố định khi đếm.
        $classSvc = new ClassService($this->db, new ScheduleService($this->db));
        $this->classId = $classSvc->createClass(array(
            'program_id'        => $programId,
            'subject_id'        => $subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp ADM-05',
            'price_per_session' => 500000,
            'total_sessions'    => 16,
            'student_ids'       => array($this->studentId),
            'schedules'         => array(
                array('day' => 2, 'start' => '18:00', 'end' => '19:30'),
            ),
            'start_date'        => date('Y-m-d'),
        ));
        $this->assertGreaterThan(0, $this->classId);

        $session = $this->db->safeFetchOne(
            'select id from db_lms_sessions where class_id = ? and session_date >= ?'
            . ' order by session_date, start_time limit 1',
            array($this->classId, date('Y-m-d'))
        );
        $this->sessionId = !empty($session) ? (int) $session['id'] : 0;
    }

    /**
     * @return int remaining_sessions hiện tại của lớp test
     */
    private function remainingSessions()
    {
        $row = $this->db->safeFetchOne(
            'select remaining_sessions from db_lms_classes where id = ?',
            array($this->classId)
        );
        return isset($row['remaining_sessions']) ? (int) $row['remaining_sessions'] : -1;
    }

    /**
     * Tạo request add_session (khung giờ không trùng lịch cố định 18:00-19:30).
     *
     * @param string $date
     * @param string $start
     * @param string $end
     * @return int request id
     */
    private function createAddSessionRequest($date, $start = '20:00', $end = '21:30')
    {
        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'add_session',
            'class_id'     => $this->classId,
            'student_id'   => $this->studentId,
            'session_date' => $date,
            'start_time'   => $start,
            'end_time'     => $end,
            'note'         => 'Buổi bù',
        ));
        $this->assertGreaterThan(0, $id);
        return $id;
    }

    /**
     * Ngày không phải thứ 2 (tránh session cố định T2 18:00-19:30).
     *
     * @param int $days
     * @return string 'Y-m-d'
     */
    private function nonMonday($days)
    {
        $date = date('Y-m-d', strtotime('+' . (int) $days . ' days'));
        while ((int) date('N', strtotime($date)) === 1) {
            $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
        }
        return $date;
    }

    // ===================== listRequestsForAdmin =====================

    public function testListFiltersByStatusAndType()
    {
        $this->createAddSessionRequest($this->nonMonday(10));
        $this->createAddSessionRequest($this->nonMonday(11));
        $this->assertGreaterThan(0, $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'request_leave',
            'class_id'     => $this->classId,
            'session_id'   => $this->sessionId,
            'reason'       => 'Bệnh',
        )));

        // Không filter: 3 requests
        $res = $this->svc->listRequestsForAdmin();
        $this->assertSame(3, (int) $res['total']);
        $this->assertCount(3, $res['items']);

        // Filter theo type
        $res = $this->svc->listRequestsForAdmin(array('request_type' => 'add_session'));
        $this->assertSame(2, (int) $res['total']);
        foreach ($res['items'] as $row) {
            $this->assertSame('add_session', (string) $row['request_type']);
        }

        // Filter theo type + status
        $res = $this->svc->listRequestsForAdmin(array(
            'request_type' => 'request_leave',
            'status'       => 'pending',
        ));
        $this->assertSame(1, (int) $res['total']);

        // Filter status không khớp
        $res = $this->svc->listRequestsForAdmin(array('status' => 'approved'));
        $this->assertSame(0, (int) $res['total']);
        $this->assertCount(0, $res['items']);

        // Filter type không hợp lệ → bỏ qua (không crash)
        $res = $this->svc->listRequestsForAdmin(array('request_type' => 'unknown_type'));
        $this->assertSame(3, (int) $res['total']);
    }

    public function testListPagination()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createAddSessionRequest($this->nonMonday(10 + $i));
        }

        $res = $this->svc->listRequestsForAdmin(array(), 2, 2);
        $this->assertSame(5, (int) $res['total']);
        $this->assertCount(2, $res['items']);

        // Trang 3 (lẻ) → 1 dòng
        $res = $this->svc->listRequestsForAdmin(array(), 3, 2);
        $this->assertCount(1, $res['items']);
    }

    public function testListJoinsTeacherClassStudent()
    {
        $this->createAddSessionRequest($this->nonMonday(10));

        $res = $this->svc->listRequestsForAdmin();
        $row = $res['items'][0];

        $this->assertSame('GV ADM-05', (string) $row['teacher_name']);
        $this->assertSame('TEA05', (string) $row['teacher_code']);
        $this->assertSame('Lớp ADM-05', (string) $row['class_name']);
        $this->assertSame('HV ADM-05', (string) $row['student_name']);
        $this->assertSame('STA05', (string) $row['student_code']);
    }

    // ===================== getRequestForAdmin =====================

    public function testGetDetailDecodesPayload()
    {
        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'change_schedule',
            'class_id'     => $this->classId,
            'slots'        => array(
                array('date' => date('Y-m-d', strtotime('+7 days', strtotime('monday this week'))), 'start' => '20:00', 'end' => '21:30'),
            ),
        ));

        $row = $this->svc->getRequestForAdmin($id);
        $this->assertIsArray($row);
        $this->assertSame('change_schedule', (string) $row['request_type']);
        $this->assertIsArray($row['payload']);
        $this->assertCount(1, $row['payload']['slots']);
        $this->assertTrue($row['can_approve']);
    }

    public function testGetDetailReturnsNullWhenMissing()
    {
        $this->assertNull($this->svc->getRequestForAdmin(9999));
        $this->assertNull($this->svc->getRequestForAdmin(0));
    }

    public function testCountPending()
    {
        $this->assertSame(0, $this->svc->countPending());
        $this->createAddSessionRequest($this->nonMonday(10));
        $this->assertSame(1, $this->svc->countPending());
    }

    // ===================== approve add_session =====================

    public function testApproveAddSessionInsertsSessionAndDecreasesRemaining()
    {
        $date = $this->nonMonday(10);
        $id = $this->createAddSessionRequest($date);

        $this->assertSame(16, $this->remainingSessions());

        $ok = $this->svc->approveRequest($id, $this->adminId);
        $this->assertTrue($ok, 'approve phải thành công; errors=' . json_encode($this->svc->errors()));

        $req = $this->db->safeFetchOne('select * from db_lms_requests where id = ?', array($id));
        $this->assertSame('approved', (string) $req['status']);
        $this->assertSame((string) $this->adminId, (string) $req['processed_by']);
        $this->assertNotEmpty($req['processed_at']);

        // Session mới status upcoming, teacher_id từ request, không có topic
        $sessions = $this->db->safeFetch(
            'select * from db_lms_sessions where class_id = ? and session_date = ? and start_time = ?',
            array($this->classId, $date, '20:00:00')
        );
        $this->assertCount(1, $sessions);
        $this->assertSame('upcoming', (string) $sessions[0]['status']);
        $this->assertSame((string) $this->teacherId, (string) $sessions[0]['teacher_id']);
        $this->assertNull($sessions[0]['topic_id']);

        // remaining_sessions giảm 1
        $this->assertSame(15, $this->remainingSessions());

        // session_students được ensure cho học viên active
        $ss = $this->db->safeFetchOne(
            'select id from db_lms_session_students where session_id = ? and student_id = ?',
            array((int) $sessions[0]['id'], $this->studentId)
        );
        $this->assertNotEmpty($ss);

        // Audit row
        $this->assertSame(1, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('approve_request')));
    }

    public function testApproveAddSessionFailsWhenNoRemaining()
    {
        $date = $this->nonMonday(10);
        $id = $this->createAddSessionRequest($date);

        $this->db->update('db_lms_classes', array('remaining_sessions' => 0), 'id = ?', array($this->classId));

        $ok = $this->svc->approveRequest($id, $this->adminId);
        $this->assertFalse($ok);
        $this->assertTrue($this->svc->hasErrors());

        // Rollback: request vẫn pending, không có session mới 20:00 ở ngày đó
        $req = $this->db->safeFetchOne('select status from db_lms_requests where id = ?', array($id));
        $this->assertSame('pending', (string) $req['status']);
        $count = (int) $this->db->count(
            'db_lms_sessions',
            'class_id = ? and session_date = ? and start_time = ?',
            array($this->classId, $date, '20:00:00')
        );
        $this->assertSame(0, $count);
    }

    public function testApproveAddSessionConflictWithTeacherSchedule()
    {
        $date = $this->nonMonday(9);

        // Tạo session khác cho cùng GV trùng ngày+giờ với request
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'session_date' => $date,
            'start_time'   => '20:00',
            'end_time'     => '21:30',
            'status'       => 'upcoming',
        ));

        $id = $this->createAddSessionRequest($date);

        $ok = $this->svc->approveRequest($id, $this->adminId);
        $this->assertFalse($ok);
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('payload', $errors);
        $this->assertStringContainsStringIgnoringCase('trùng', $errors['payload']);

        $req = $this->db->safeFetchOne('select status from db_lms_requests where id = ?', array($id));
        $this->assertSame('pending', (string) $req['status']);
    }

    // ===================== approve request_leave =====================

    public function testApproveRequestLeaveCancelsSession()
    {
        $this->assertGreaterThan(0, $this->sessionId);
        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'request_leave',
            'class_id'     => $this->classId,
            'session_id'   => $this->sessionId,
            'reason'       => 'Bệnh có đơn bác sĩ',
        ));
        $this->assertGreaterThan(0, $id);

        $ok = $this->svc->approveRequest($id, $this->adminId);
        $this->assertTrue($ok, 'errors=' . json_encode($this->svc->errors()));

        $session = $this->db->safeFetchOne('select id, status from db_lms_sessions where id = ?', array($this->sessionId));
        $this->assertSame('cancelled', (string) $session['status']);

        // Giữ lịch sử: session_students vẫn còn
        $ss = (int) $this->db->count('db_lms_session_students', 'session_id = ?', array($this->sessionId));
        $this->assertGreaterThan(0, $ss);

        // remaining_sessions không đổi
        $this->assertSame(16, $this->remainingSessions());

        $req = $this->db->safeFetchOne('select status from db_lms_requests where id = ?', array($id));
        $this->assertSame('approved', (string) $req['status']);
    }

    public function testApproveRequestLeavePastSessionFails()
    {
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'session_date' => date('Y-m-d', strtotime('-10 days')),
            'start_time'   => '18:00',
            'end_time'     => '19:30',
            'status'       => 'finished',
        ));
        $pastSessionId = (int) $this->db->insertId;

        // Tạo request trực tiếp (createRequest chặn session quá khứ)
        $this->db->insert('db_lms_requests', array(
            'request_type' => 'request_leave',
            'class_id'     => $this->classId,
            'session_id'   => $pastSessionId,
            'teacher_id'   => $this->teacherId,
            'title'        => 'Xin nghỉ buổi quá khứ',
            'status'       => 'pending',
        ));
        $id = (int) $this->db->insertId;

        $ok = $this->svc->approveRequest($id, $this->adminId);
        $this->assertFalse($ok);

        $session = $this->db->safeFetchOne('select status from db_lms_sessions where id = ?', array($pastSessionId));
        $this->assertSame('finished', (string) $session['status']);
    }

    // ===================== approve change_schedule =====================

    public function testApproveChangeScheduleInsertsAllSlots()
    {
        $nextMonday = date('Y-m-d', strtotime('+7 days', strtotime('monday this week')));
        $slots = array(
            array('date' => $nextMonday, 'start' => '20:00', 'end' => '21:30'),
            array('date' => date('Y-m-d', strtotime('+1 day', strtotime($nextMonday))), 'start' => '20:00', 'end' => '21:30'),
        );

        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'change_schedule',
            'class_id'     => $this->classId,
            'slots'        => $slots,
        ));
        $this->assertGreaterThan(0, $id);

        $ok = $this->svc->approveRequest($id, $this->adminId);
        $this->assertTrue($ok, 'errors=' . json_encode($this->svc->errors()));

        // 2 session mới, mỗi slot 1 buổi (20:00 — khác lịch cố định 18:00)
        foreach ($slots as $slot) {
            $count = (int) $this->db->count(
                'db_lms_sessions',
                'class_id = ? and session_date = ? and start_time = ?',
                array($this->classId, $slot['date'], '20:00:00')
            );
            $this->assertSame(1, $count, 'Thiếu session cho slot ' . $slot['date']);
        }

        // remaining_sessions giảm theo số slot (2)
        $this->assertSame(14, $this->remainingSessions());
    }

    public function testApproveChangeScheduleConflictRollsBackAll()
    {
        // Slot 2 trùng lịch cố định T2 18:00-19:30 (cùng GV)
        $nextMonday = date('Y-m-d', strtotime('+7 days', strtotime('monday this week')));
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'session_date' => date('Y-m-d', strtotime('+1 day', strtotime($nextMonday))),
            'start_time'   => '19:00',
            'end_time'     => '20:30',
            'status'       => 'upcoming',
        ));

        $slots = array(
            array('date' => $nextMonday, 'start' => '20:00', 'end' => '21:30'),
            array('date' => date('Y-m-d', strtotime('+1 day', strtotime($nextMonday))), 'start' => '19:30', 'end' => '21:00'),
        );

        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'change_schedule',
            'class_id'     => $this->classId,
            'slots'        => $slots,
        ));
        $this->assertGreaterThan(0, $id);

        $ok = $this->svc->approveRequest($id, $this->adminId);
        $this->assertFalse($ok);
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('payload', $errors);

        // All-or-nothing: không có session 20:00 nào cho slot 1
        $count = (int) $this->db->count(
            'db_lms_sessions',
            'class_id = ? and session_date = ? and start_time = ?',
            array($this->classId, $nextMonday, '20:00:00')
        );
        $this->assertSame(0, $count, 'Slot 1 không được insert khi slot 2 trùng');

        // remaining không đổi
        $this->assertSame(16, $this->remainingSessions());

        $req = $this->db->safeFetchOne('select status from db_lms_requests where id = ?', array($id));
        $this->assertSame('pending', (string) $req['status']);
    }

    // ===================== idempotency / trạng thái =====================

    public function testApproveTwiceFailsSecondTime()
    {
        $date = $this->nonMonday(10);
        $id = $this->createAddSessionRequest($date);

        $this->assertTrue($this->svc->approveRequest($id, $this->adminId));

        // Lần 2: request đã approved → lỗi
        $ok = $this->svc->approveRequest($id, $this->adminId);
        $this->assertFalse($ok);
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('status', $errors);

        // DB không đổi thêm: vẫn 16-1 remaining, 1 session 20:00 ở ngày đó
        $this->assertSame(15, $this->remainingSessions());

        $count = (int) $this->db->count(
            'db_lms_sessions',
            'class_id = ? and session_date = ? and start_time = ?',
            array($this->classId, $date, '20:00:00')
        );
        $this->assertSame(1, $count);
    }

    public function testApproveRejectedRequestFails()
    {
        $id = $this->createAddSessionRequest($this->nonMonday(10));
        $this->assertTrue($this->svc->rejectRequest($id, $this->adminId, array('admin_note' => 'Lịch không phù hợp')));

        $ok = $this->svc->approveRequest($id, $this->adminId);
        $this->assertFalse($ok);

        $req = $this->db->safeFetchOne('select status from db_lms_requests where id = ?', array($id));
        $this->assertSame('rejected', (string) $req['status']);
    }

    public function testApproveMissingRequestFails()
    {
        $this->assertFalse($this->svc->approveRequest(9999, $this->adminId));
        $this->assertArrayHasKey('system', $this->svc->errors());
    }

    public function testApproveFailsWhenClassDeleted()
    {
        $date = $this->nonMonday(10);
        $id = $this->createAddSessionRequest($date);

        $this->db->update(
            'db_lms_classes',
            array('deleted_at' => date('Y-m-d H:i:s')),
            'id = ?',
            array($this->classId)
        );

        $ok = $this->svc->approveRequest($id, $this->adminId);
        $this->assertFalse($ok);
        $this->assertArrayHasKey('class_id', $this->svc->errors());

        $count = (int) $this->db->count(
            'db_lms_sessions',
            'class_id = ? and session_date = ? and start_time = ?',
            array($this->classId, $date, '20:00:00')
        );
        $this->assertSame(0, $count);
    }

    public function testApproveUnsupportedTypeFails()
    {
        // createRequest chặn type lạ → insert trực tiếp (giống data cũ)
        $this->db->insert('db_lms_requests', array(
            'request_type' => 'send_test',
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'title'        => 'Gửi đề test',
            'payload'      => json_encode(array('note' => 'test'), JSON_UNESCAPED_UNICODE),
            'status'       => 'pending',
        ));
        $id = (int) $this->db->insertId;

        $ok = $this->svc->approveRequest($id, $this->adminId);
        $this->assertFalse($ok);
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('request_type', $errors);
        $this->assertStringContainsStringIgnoringCase('chưa hỗ trợ', $errors['request_type']);

        $req = $this->db->safeFetchOne('select status from db_lms_requests where id = ?', array($id));
        $this->assertSame('pending', (string) $req['status']);
    }

    // ===================== rejectRequest =====================

    public function testRejectSuccess()
    {
        $date = $this->nonMonday(10);
        $id = $this->createAddSessionRequest($date);

        $ok = $this->svc->rejectRequest($id, $this->adminId, array('admin_note' => 'Lịch không phù hợp'));
        $this->assertTrue($ok);

        $req = $this->db->safeFetchOne('select * from db_lms_requests where id = ?', array($id));
        $this->assertSame('rejected', (string) $req['status']);
        $this->assertSame((string) $this->adminId, (string) $req['processed_by']);
        $this->assertSame('Lịch không phù hợp', (string) $req['admin_note']);
        $this->assertNotEmpty($req['processed_at']);

        // Không sinh session
        $count = (int) $this->db->count(
            'db_lms_sessions',
            'class_id = ? and session_date = ? and start_time = ?',
            array($this->classId, $date, '20:00:00')
        );
        $this->assertSame(0, $count);

        $this->assertSame(1, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('reject_request')));
    }

    public function testRejectRequiresAdminNote()
    {
        $id = $this->createAddSessionRequest($this->nonMonday(10));

        // Thiếu note
        $this->assertFalse($this->svc->rejectRequest($id, $this->adminId, array()));
        $this->assertArrayHasKey('admin_note', $this->svc->errors());

        // Note quá ngắn
        $this->assertFalse($this->svc->rejectRequest($id, $this->adminId, array('admin_note' => 'ok')));
        $this->assertArrayHasKey('admin_note', $this->svc->errors());

        // Vẫn pending
        $req = $this->db->safeFetchOne('select status from db_lms_requests where id = ?', array($id));
        $this->assertSame('pending', (string) $req['status']);
    }

    public function testRejectUnsupportedTypeAllowed()
    {
        $this->db->insert('db_lms_requests', array(
            'request_type' => 'request_bonus',
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'title'        => 'Yêu cầu thưởng',
            'status'       => 'pending',
        ));
        $id = (int) $this->db->insertId;

        $ok = $this->svc->rejectRequest($id, $this->adminId, array('admin_note' => 'Chưa đạt điều kiện'));
        $this->assertTrue($ok);

        $req = $this->db->safeFetchOne('select status from db_lms_requests where id = ?', array($id));
        $this->assertSame('rejected', (string) $req['status']);
    }

    // ===================== hasTeacherScheduleConflict =====================

    public function testHasTeacherScheduleConflict()
    {
        $sessSvc = new SessionService($this->db);
        $nextMonday = date('Y-m-d', strtotime('+7 days', strtotime('monday this week')));

        // Lịch cố định đã sinh session T2 18:00-19:30 → trùng
        $this->assertTrue($sessSvc->hasTeacherScheduleConflict($this->teacherId, $nextMonday, '18:30', '20:00'));

        // Không trùng
        $this->assertFalse($sessSvc->hasTeacherScheduleConflict($this->teacherId, $nextMonday, '20:00', '21:30'));

        // Exclude chính session đó
        $session = $this->db->safeFetchOne(
            'select id from db_lms_sessions where class_id = ? and session_date = ? and start_time = ?',
            array($this->classId, $nextMonday, '18:00:00')
        );
        if (!empty($session)) {
            $this->assertFalse(
                $sessSvc->hasTeacherScheduleConflict($this->teacherId, $nextMonday, '18:30', '20:00', (int) $session['id'])
            );
        }

        // GV khác → không tính
        $this->assertFalse($sessSvc->hasTeacherScheduleConflict(999999, $nextMonday, '18:30', '20:00'));

        // Ngày không hợp lệ → false
        $this->assertFalse($sessSvc->hasTeacherScheduleConflict($this->teacherId, 'bad-date', '18:30', '20:00'));
    }
}
