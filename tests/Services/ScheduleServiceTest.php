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
            // BR max_classes (ADM-03): test tạo 2 lớp cùng GV nên để giới hạn rộng
            'max_classes'  => 10,
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

    // ===================== TCH-01/02 — Lịch trống GV =====================

    public function testAddFreeSlotSuccess()
    {
        $id = $this->svc->addFreeSlot($this->teacherId, 6, '08:00', '10:00');
        $this->assertGreaterThan(0, $id);

        $rows = $this->svc->listFreeSlots($this->teacherId);
        $this->assertCount(1, $rows);
        $this->assertSame('08:00:00', $rows[0]['start_time']);
        $this->assertSame(1, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('add_free_slot')));
    }

    public function testAddFreeSlotRejectsOverlap()
    {
        $this->assertGreaterThan(0, $this->svc->addFreeSlot($this->teacherId, 6, '08:00', '10:00'));
        // 09:00-11:00 giao nhau → từ chối
        $this->assertNull($this->svc->addFreeSlot($this->teacherId, 6, '09:00', '11:00'));
        $this->assertArrayHasKey('slot', $this->svc->errors());
    }

    public function testAddFreeSlotAllowsAnotherDay()
    {
        $this->assertGreaterThan(0, $this->svc->addFreeSlot($this->teacherId, 6, '08:00', '10:00'));
        $this->assertGreaterThan(0, $this->svc->addFreeSlot($this->teacherId, 7, '08:00', '10:00'));
    }

    public function testAddFreeSlotRejectsInvalidTime()
    {
        $this->assertNull($this->svc->addFreeSlot($this->teacherId, 2, '10:00', '08:00'));
        $this->assertNull($this->svc->addFreeSlot($this->teacherId, 9, '08:00', '10:00'));
    }

    public function testDeleteFreeSlotRowLevel()
    {
        $id = $this->svc->addFreeSlot($this->teacherId, 6, '08:00', '10:00');
        $this->assertGreaterThan(0, $id);

        // GV khác xoá → lỗi
        $userSvc = new \Lms\UserService($this->db);
        $otherTeacher = $userSvc->createUser('gv_sched2', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $otherTeacher,
            'teacher_code' => 'TES02',
            'full_name'    => 'GV Sched 2',
            'status'       => 'active',
        ));
        $this->assertFalse($this->svc->deleteFreeSlot($otherTeacher, $id));
        $this->assertSame(1, (int) $this->db->count('db_lms_teacher_free_slots'));

        // GV chủ xoá → OK
        $this->assertTrue($this->svc->deleteFreeSlot($this->teacherId, $id));
        $this->assertSame(0, (int) $this->db->count('db_lms_teacher_free_slots'));
    }

    public function testFreeSlotsForWeekRespectsEffectiveRange()
    {
        $monday = date('Y-m-d', strtotime('monday this week'));

        // Slot hiệu lực từ tuần sau → không xuất hiện tuần này
        $nextMonday = date('Y-m-d', strtotime('+7 days', strtotime($monday)));
        $this->db->insert('db_lms_teacher_free_slots', array(
            'teacher_id'     => $this->teacherId,
            'day_of_week'    => 2,
            'start_time'     => '08:00',
            'end_time'       => '10:00',
            'effective_from' => $nextMonday,
        ));

        $map = $this->svc->freeSlotsForWeek($this->teacherId, $monday);
        $this->assertSame(array(), $map);

        // Tuần sau → xuất hiện
        $mapNext = $this->svc->freeSlotsForWeek($this->teacherId, $nextMonday);
        $this->assertArrayHasKey(2, $mapNext);
        $this->assertSame('08:00', $mapNext[2][0]['start']);
    }

    public function testFreeSlotsForWeekIgnoresExpired()
    {
        $monday = date('Y-m-d', strtotime('monday this week'));

        // Slot đã hết hiệu lực từ hôm qua (chọn thứ 7 của tuần này — sau effective_to)
        $this->db->insert('db_lms_teacher_free_slots', array(
            'teacher_id'     => $this->teacherId,
            'day_of_week'    => 7,
            'start_time'     => '08:00',
            'end_time'       => '10:00',
            'effective_from' => date('Y-m-d', strtotime('-30 days')),
            'effective_to'   => date('Y-m-d', strtotime('-1 day')),
        ));

        $map = $this->svc->freeSlotsForWeek($this->teacherId, $monday);
        $this->assertFalse(isset($map[7]));
    }
}
