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
}
