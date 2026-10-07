<?php
/**
 * Tests cho ScheduleService — BR-SCHED-01 (chống trùng lịch GV).
 */

namespace Tests\Services;

use Lms\ClassService;
use Lms\ScheduleService;
use Tests\LmsTestCase;

class ScheduleServiceTest extends LmsTestCase
{
    /** @var ScheduleService */
    private $svc;

    /** @var ClassService */
    private $classSvc;

    /** @var int */
    private $classId;

    /** @var int */
    private $teacherId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = new ScheduleService($this->db);
        $this->classSvc = new ClassService($this->db, $this->svc);

        $this->db->insert('db_lms_programs', array('name' => 'Chương trình test'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Môn test'));
        $subjectId = (int) $this->db->insertId;

        $userSvc = new \Lms\UserService($this->db);
        $this->teacherId = $userSvc->createUser('gv_sched', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->teacherId,
            'teacher_code' => 'TES01',
            'full_name'    => 'GV Sched',
            'status'       => 'active',
        ));

        $this->classId = $this->classSvc->createClass(array(
            'program_id'        => $programId,
            'subject_id'        => $subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp test',
            'price_per_session' => 400000,
            'total_sessions'    => 12,
        ));
    }

    public function testAddScheduleSuccess()
    {
        $id = $this->svc->addSchedule($this->classId, 2, '17:30', '19:00', date('Y-m-d'));
        $this->assertGreaterThan(0, $id);

        $rows = $this->svc->listForClass($this->classId);
        $this->assertCount(1, $rows);
        $this->assertSame('17:30:00', $rows[0]['start_time']);
    }

    public function testBrSched01RejectsOverlappingSchedule()
    {
        $from = date('Y-m-d');
        $this->assertGreaterThan(0, $this->svc->addSchedule($this->classId, 2, '17:30', '19:00', $from));

        // Cùng GV, cùng thứ, giao nhau 17:45-18:15 → phải bị từ chối
        $id = $this->svc->addSchedule($this->classId, 2, '17:45', '18:15', $from);
        $this->assertNull($id);
        $this->assertArrayHasKey('schedule', $this->svc->errors());
        $this->assertStringContainsString('BR-SCHED-01', $this->svc->errors()['schedule']);
    }

    public function testBrSched01AllowsAdjacentTime()
    {
        $from = date('Y-m-d');
        $this->assertGreaterThan(0, $this->svc->addSchedule($this->classId, 2, '17:30', '19:00', $from));
        // 19:00-20:30: giao tại đúng điểm biên (start = end cũ) → cho phép
        $this->assertGreaterThan(0, $this->svc->addSchedule($this->classId, 2, '19:00', '20:30', $from));
    }

    public function testBrSched01AllowsDifferentDay()
    {
        $from = date('Y-m-d');
        $this->assertGreaterThan(0, $this->svc->addSchedule($this->classId, 2, '17:30', '19:00', $from));
        $this->assertGreaterThan(0, $this->svc->addSchedule($this->classId, 4, '17:30', '19:00', $from));
    }

    public function testHasConflictAcrossClasses()
    {
        $from = date('Y-m-d');
        $this->assertGreaterThan(0, $this->svc->addSchedule($this->classId, 3, '18:00', '19:30', $from));

        // Tạo lớp 2 cùng GV
        $class2 = $this->classSvc->createClass(array(
            'program_id'        => 1,
            'subject_id'        => 1,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'group',
            'name'              => 'Lớp test 2',
            'price_per_session' => 400000,
        ));

        $id = $this->svc->addSchedule($class2, 3, '19:00', '20:00', $from);
        $this->assertNull($id, 'Lớp 2 trùng lịch GV phải bị từ chối');
    }

    public function testRejectsInvalidTimeRange()
    {
        $this->assertNull($this->svc->addSchedule($this->classId, 2, '19:00', '17:30', date('Y-m-d')));
    }

    public function testRejectsInvalidDayOfWeek()
    {
        $this->assertNull($this->svc->addSchedule($this->classId, 1, '17:30', '19:00', date('Y-m-d')));
    }
}
