<?php
/**
 * Tests cho RequestService — 3 loại request TCH-01/02 (BR-SCHED-02).
 */

namespace Tests\Services;

use Lms\ClassService;
use Lms\RequestService;
use Lms\ScheduleService;
use Lms\SessionService;
use Tests\LmsTestCase;

class RequestServiceTest extends LmsTestCase
{
    /** @var RequestService */
    private $svc;

    /** @var int */
    private $teacherId;

    /** @var int */
    private $otherTeacherId;

    /** @var int */
    private $classId;

    /** @var int */
    private $otherClassId;

    /** @var int */
    private $studentId;

    /** @var int */
    private $sessionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = new RequestService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'Chương trình test'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Môn test'));
        $subjectId = (int) $this->db->insertId;

        $userSvc = new \Lms\UserService($this->db);
        $this->teacherId = $userSvc->createUser('gv_req1', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->teacherId,
            'teacher_code' => 'TER01',
            'full_name'    => 'GV Request 1',
            'status'       => 'active',
            'max_classes'  => 10,
        ));
        $this->otherTeacherId = $userSvc->createUser('gv_req2', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->otherTeacherId,
            'teacher_code' => 'TER02',
            'full_name'    => 'GV Request 2',
            'status'       => 'active',
            'max_classes'  => 10,
        ));

        $this->studentId = $userSvc->createUser('hv_req', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $this->studentId,
            'student_code' => 'STR01',
            'full_name'    => 'HV Request',
            'status'       => 'studying',
        ));

        $schedSvc = new ScheduleService($this->db);
        $classSvc = new ClassService($this->db, $schedSvc);

        $monday = date('Y-m-d', strtotime('monday this week'));

        $this->classId = $classSvc->createClass(array(
            'program_id'        => $programId,
            'subject_id'        => $subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp request 1',
            'price_per_session' => 500000,
            'total_sessions'    => 16,
            'student_ids'       => array($this->studentId),
            'schedules'         => array(
                array('day' => 2, 'start' => '18:00', 'end' => '19:30'),
            ),
            'start_date'        => $monday,
        ));

        $this->otherClassId = $classSvc->createClass(array(
            'program_id'        => $programId,
            'subject_id'        => $subjectId,
            'teacher_id'        => $this->otherTeacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp request 2',
            'price_per_session' => 500000,
            'total_sessions'    => 16,
            'student_ids'       => array($this->studentId),
            'schedules'         => array(
                array('day' => 3, 'start' => '18:00', 'end' => '19:30'),
            ),
            'start_date'        => $monday,
        ));

        $session = $this->db->safeFetchOne(
            'select id from db_lms_sessions where class_id = ? and session_date >= ? order by session_date limit 1',
            array($this->classId, date('Y-m-d'))
        );
        $this->sessionId = !empty($session) ? (int) $session['id'] : 0;
    }

    public function testAddSessionRequestSuccess()
    {
        $date = date('Y-m-d', strtotime('+10 days'));
        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'add_session',
            'class_id'     => $this->classId,
            'student_id'   => $this->studentId,
            'session_date' => $date,
            'start_time'   => '20:00',
            'end_time'     => '21:30',
            'note'         => 'Buổi bù',
        ));

        $this->assertGreaterThan(0, $id);

        $row = $this->db->safeFetchOne('select * from db_lms_requests where id = ?', array($id));
        $this->assertSame('add_session', $row['request_type']);
        $this->assertSame('pending', $row['status']);
        $this->assertSame((string) $this->teacherId, (string) $row['teacher_id']);
        $this->assertSame((string) $this->studentId, (string) $row['student_id']);

        $payload = json_decode((string) $row['payload'], true);
        $this->assertSame($date, $payload['session_date']);
        $this->assertSame('20:00', $payload['start_time']);
        $this->assertSame('21:30', $payload['end_time']);
        $this->assertSame('Buổi bù', $payload['note']);

        // 1 row request pending + 1 row audit
        $this->assertSame(1, (int) $this->db->count('db_lms_requests', 'status = ?', array('pending')));
        $this->assertSame(1, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('create_request')));
    }

    public function testRequestLeaveSuccess()
    {
        $this->assertGreaterThan(0, $this->sessionId, 'Phải có session upcoming');

        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'request_leave',
            'class_id'     => $this->classId,
            'session_id'   => $this->sessionId,
            'reason'       => 'Bệnh',
            'makeup_date'  => date('Y-m-d', strtotime('+5 days')),
        ));

        $this->assertGreaterThan(0, $id);
        $row = $this->db->safeFetchOne('select * from db_lms_requests where id = ?', array($id));
        $this->assertSame('request_leave', $row['request_type']);
        $this->assertSame('pending', $row['status']);
        $this->assertSame((string) $this->sessionId, (string) $row['session_id']);

        $payload = json_decode((string) $row['payload'], true);
        $this->assertSame('Bệnh', $payload['reason']);
    }

    public function testChangeScheduleSuccess()
    {
        $nextMonday = date('Y-m-d', strtotime('+7 days', strtotime('monday this week')));
        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'change_schedule',
            'class_id'     => $this->classId,
            'slots'        => array(
                array('date' => $nextMonday, 'start' => '19:00', 'end' => '20:30'),
                array('date' => date('Y-m-d', strtotime('+1 day', strtotime($nextMonday))), 'start' => '19:00', 'end' => '20:30'),
            ),
        ));

        $this->assertGreaterThan(0, $id);
        $row = $this->db->safeFetchOne('select * from db_lms_requests where id = ?', array($id));
        $this->assertSame('change_schedule', $row['request_type']);

        $payload = json_decode((string) $row['payload'], true);
        $this->assertCount(2, $payload['slots']);
    }

    public function testRejectsClassOfOtherTeacher()
    {
        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'add_session',
            'class_id'     => $this->otherClassId,
            'student_id'   => $this->studentId,
            'session_date' => date('Y-m-d', strtotime('+10 days')),
            'start_time'   => '20:00',
            'end_time'     => '21:30',
        ));

        $this->assertNull($id);
        $this->assertArrayHasKey('class_id', $this->svc->errors());
        $this->assertSame(0, (int) $this->db->count('db_lms_requests'));
    }

    public function testRejectsAddSessionInPast()
    {
        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'add_session',
            'class_id'     => $this->classId,
            'student_id'   => $this->studentId,
            'session_date' => date('Y-m-d', strtotime('-5 days')),
            'start_time'   => '20:00',
            'end_time'     => '21:30',
        ));

        $this->assertNull($id);
        $this->assertArrayHasKey('session_date', $this->svc->errors());
    }

    public function testRejectsChangeScheduleInCurrentWeek()
    {
        // BR-SCHED-02: lịch mới phải từ tuần kế tiếp
        $thisMonday = date('Y-m-d', strtotime('monday this week'));
        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'change_schedule',
            'class_id'     => $this->classId,
            'slots'        => array(
                array('date' => date('Y-m-d', strtotime('+3 days', strtotime($thisMonday))), 'start' => '19:00', 'end' => '20:30'),
            ),
        ));

        $this->assertNull($id);
        $this->assertArrayHasKey('slots', $this->svc->errors());
    }

    public function testRejectsLeaveForPastSession()
    {
        // Tạo session đã qua
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $this->classId,
            'teacher_id'   => $this->teacherId,
            'session_date' => date('Y-m-d', strtotime('-10 days')),
            'start_time'   => '18:00',
            'end_time'     => '19:30',
            'status'       => 'finished',
        ));
        $pastId = (int) $this->db->insertId;

        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'request_leave',
            'class_id'     => $this->classId,
            'session_id'   => $pastId,
            'reason'       => 'Bệnh',
        ));

        $this->assertNull($id);
        $this->assertArrayHasKey('session_id', $this->svc->errors());
    }

    public function testRejectsInvalidRequestType()
    {
        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'send_test',
            'class_id'     => $this->classId,
        ));

        $this->assertNull($id);
        $this->assertArrayHasKey('request_type', $this->svc->errors());
    }

    public function testRejectsAddSessionStudentNotInClass()
    {
        $userSvc = new \Lms\UserService($this->db);
        $otherStudent = $userSvc->createUser('hv_req2', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $otherStudent,
            'student_code' => 'STR02',
            'full_name'    => 'HV Request 2',
            'status'       => 'studying',
        ));

        $id = $this->svc->createRequest($this->teacherId, array(
            'request_type' => 'add_session',
            'class_id'     => $this->classId,
            'student_id'   => $otherStudent,
            'session_date' => date('Y-m-d', strtotime('+10 days')),
            'start_time'   => '20:00',
            'end_time'     => '21:30',
        ));

        $this->assertNull($id);
        $this->assertArrayHasKey('student_id', $this->svc->errors());
    }
}
