<?php
/**
 * Tests cho MatchingService — Sắp xếp lớp (ADM-04, Phase 3).
 *
 * Bao gồm: danh sách HV chờ xếp (filter + loại HV đang có lớp), hard filter
 * match (môn/quận/giới tính/online/sức chứa/trạng thái), tính điểm + xếp hạng,
 * chi tiết lịch tuần GV, tạo lớp tự động từ match (+ xoá lịch trống + đổi status).
 */

namespace Tests\Services;

use Lms\ClassService;
use Lms\MatchingService;
use Lms\ScheduleService;
use Lms\UserService;
use Tests\LmsTestCase;

class MatchingServiceTest extends LmsTestCase
{
    /** @var MatchingService */
    private $svc;

    /** @var ClassService */
    private $classSvc;

    /** @var UserService */
    private $userSvc;

    private $subjectMath;
    private $subjectEnglish;
    private $progMath;

    private $tGreen;   // female, green, Quận 1, dạy Toán, online
    private $tGold;    // male, gold, Quận 1+3, dạy Toán+Anh
    private $tGoldF;   // female, gold, Quận 1, dạy Toán — match 1/2 slot
    private $tBronze;  // female, bronze, Quận 10, chỉ dạy Anh
    private $tPaused;  // paused
    private $tFull;    // max_classes = 1, đã có 1 lớp active

    private $sMain;    // offline Quận 1, môn Toán, 2 slot T2+T4, thích GV nữ
    private $sOnline;  // online, môn Toán
    private $sNoDistrict;
    private $sStudying;
    private $sNoSlots;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc      = new MatchingService($this->db);
        $this->classSvc = new ClassService($this->db);
        $this->userSvc  = new UserService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'Toán'));
        $this->progMath = (int) $this->db->insertId;
        $this->db->insert('db_lms_programs', array('name' => 'Tiếng Anh'));
        $progEnglish = (int) $this->db->insertId;

        $this->db->insert('db_lms_subjects', array('program_id' => $this->progMath, 'name' => 'Toán Đại số'));
        $this->subjectMath = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $progEnglish, 'name' => 'Anh văn'));
        $this->subjectEnglish = (int) $this->db->insertId;

        $this->tGreen  = $this->createTeacher('gv_match_1', 'TE001', array(
            'gender' => 'female', 'rank' => 'green', 'max_classes' => 3,
            'districts' => array('Quận 1'), 'subjects' => array($this->subjectMath),
            'can_online' => 1,
        ));
        $this->tGold    = $this->createTeacher('gv_match_2', 'TE002', array(
            'gender' => 'male', 'rank' => 'gold', 'max_classes' => 2,
            'districts' => array('Quận 1', 'Quận 3'),
            'subjects' => array($this->subjectMath, $this->subjectEnglish),
        ));
        $this->tGoldF   = $this->createTeacher('gv_match_3', 'TE003', array(
            'gender' => 'female', 'rank' => 'gold', 'max_classes' => 5,
            'districts' => array('Quận 1'), 'subjects' => array($this->subjectMath),
        ));
        $this->tBronze  = $this->createTeacher('gv_match_4', 'TE004', array(
            'gender' => 'female', 'rank' => 'bronze', 'max_classes' => 4,
            'districts' => array('Quận 10'), 'subjects' => array($this->subjectEnglish),
        ));
        $this->tPaused  = $this->createTeacher('gv_match_5', 'TE005', array(
            'status' => 'paused', 'districts' => array('Quận 1'),
            'subjects' => array($this->subjectMath),
        ));
        $this->tFull    = $this->createTeacher('gv_match_6', 'TE006', array(
            'gender' => 'female', 'rank' => 'gold', 'max_classes' => 1,
            'districts' => array('Quận 1'), 'subjects' => array($this->subjectMath),
        ));

        // Lịch trống
        $this->addFreeSlot($this->tGreen, 2, '17:00', '19:00');   // giao T2 18:00-19:00
        $this->addFreeSlot($this->tGreen, 4, '18:00', '20:00');   // trọn vẹn T4
        $this->addFreeSlot($this->tGold, 2, '20:00', '21:00');    // không giao T2 18:00-19:30
        $this->addFreeSlot($this->tGoldF, 2, '18:00', '19:30');   // match 1/2 slot
        $this->addFreeSlot($this->tBronze, 2, '18:00', '19:30');
        $this->addFreeSlot($this->tPaused, 2, '18:00', '19:30');
        $this->addFreeSlot($this->tFull, 2, '18:00', '19:30');

        // GV tFull đã đầy max_classes
        $fullClass = $this->classSvc->createClass(array(
            'program_id' => $this->progMath,
            'subject_id' => $this->subjectMath,
            'teacher_id' => $this->tFull,
            'class_type' => 'one_on_one',
            'name'       => 'Lớp đầy',
            'student_ids' => array($this->createStudent('hv_full_1', 'STF01', 'HV Full')),
            'schedules'  => array(array('day' => 3, 'start' => '18:00', 'end' => '19:00')),
        ));
        $this->assertNotNull($fullClass);

        // Học viên
        $this->sMain = $this->createStudent('hv_match_1', 'ST001', 'HV Một', 'pending_arrange', array(
            'district' => 'Quận 1',
            'subject_id' => $this->subjectMath,
            'preferred_teacher_gender' => 'female',
            'slots' => array(
                array('day' => 2, 'start' => '18:00', 'end' => '19:30'),
                array('day' => 4, 'start' => '18:00', 'end' => '19:30'),
            ),
        ));
        $this->sOnline = $this->createStudent('hv_match_2', 'ST002', 'HV Hai', 'pending_arrange', array(
            'learning_type' => 'online',
            'subject_id' => $this->subjectMath,
            'slots' => array(array('day' => 7, 'start' => '08:00', 'end' => '09:30')),
        ));
        $this->sNoDistrict = $this->createStudent('hv_match_3', 'ST003', 'HV Ba', 'pending_arrange', array(
            'subject_id' => $this->subjectMath,
            'slots' => array(array('day' => 2, 'start' => '18:00', 'end' => '19:30')),
        ));
        $this->sStudying = $this->createStudent('hv_match_4', 'ST004', 'HV Bốn', 'studying', array(
            'district' => 'Quận 1',
            'subject_id' => $this->subjectMath,
            'slots' => array(array('day' => 2, 'start' => '18:00', 'end' => '19:30')),
        ));
        $this->sNoSlots = $this->createStudent('hv_match_5', 'ST005', 'HV Năm', 'pending_arrange', array(
            'district' => 'Quận 1',
            'subject_id' => $this->subjectMath,
        ));
    }

    private function createTeacher($username, $code, array $opts = array())
    {
        $id = $this->userSvc->createUser($username, 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'             => $id,
            'teacher_code'        => $code,
            'full_name'           => 'GV ' . $code,
            'gender'              => isset($opts['gender']) ? $opts['gender'] : null,
            'rank'                => isset($opts['rank']) ? $opts['rank'] : 'bronze',
            'max_classes'         => isset($opts['max_classes']) ? $opts['max_classes'] : 1,
            'status'              => isset($opts['status']) ? $opts['status'] : 'active',
            'can_teach_online'    => isset($opts['can_online']) ? $opts['can_online'] : 0,
            'teachable_districts' => !empty($opts['districts'])
                ? json_encode($opts['districts'], JSON_UNESCAPED_UNICODE) : null,
            'teachable_subjects'  => !empty($opts['subjects'])
                ? json_encode($opts['subjects'], JSON_UNESCAPED_UNICODE) : null,
        ));
        return $id;
    }

    private function createStudent($username, $code, $name, $status = 'pending_arrange', array $opts = array())
    {
        $id = $this->userSvc->createUser($username, 'matkhau123', 'student');
        $row = array(
            'user_id'      => $id,
            'student_code' => $code,
            'full_name'    => $name,
            'grade'        => 'Lớp 5',
            'status'       => $status,
            'district'     => isset($opts['district']) ? $opts['district'] : null,
            'learning_type' => isset($opts['learning_type']) ? $opts['learning_type'] : 'offline',
            'subject_id'   => isset($opts['subject_id']) ? (int) $opts['subject_id'] : null,
            'preferred_teacher_gender' => isset($opts['preferred_teacher_gender'])
                ? $opts['preferred_teacher_gender'] : null,
        );
        if (!empty($opts['slots'])) {
            $row['free_time_slots'] = json_encode($opts['slots'], JSON_UNESCAPED_UNICODE);
        }
        $this->db->insert('db_lms_students', $row);
        return $id;
    }

    private function addFreeSlot($teacherId, $day, $start, $end)
    {
        $this->db->insert('db_lms_teacher_free_slots', array(
            'teacher_id'     => (int) $teacherId,
            'day_of_week'    => (int) $day,
            'start_time'     => $start,
            'end_time'       => $end,
            'effective_from' => date('Y-m-d', strtotime('-1 day')),
        ));
        return (int) $this->db->insertId;
    }

    // ===================== listPendingStudents =====================

    public function testListPendingStudentsOnlyPendingWithoutActiveClass()
    {
        $res = $this->svc->listPendingStudents();
        $ids = array();
        foreach ($res['items'] as $it) {
            $ids[] = (int) $it['user_id'];
        }
        $this->assertEquals(4, $res['total']);
        $this->assertContains($this->sMain, $ids);
        $this->assertContains($this->sOnline, $ids);
        $this->assertContains($this->sNoDistrict, $ids);
        $this->assertContains($this->sNoSlots, $ids);
        $this->assertNotContains($this->sStudying, $ids);
    }

    public function testListPendingStudentsFilterBySubject()
    {
        $res = $this->svc->listPendingStudents(array('subject_id' => $this->subjectMath));
        $this->assertEquals(4, $res['total']);

        $res = $this->svc->listPendingStudents(array('subject_id' => $this->subjectEnglish));
        $this->assertEquals(0, $res['total']);
    }

    public function testListPendingStudentsFilterByQuery()
    {
        $res = $this->svc->listPendingStudents(array('q' => 'ST001'));
        $this->assertEquals(1, $res['total']);
        $this->assertSame($this->sMain, (int) $res['items'][0]['user_id']);

        $res = $this->svc->listPendingStudents(array('q' => 'hv_match_2'));
        $this->assertEquals(1, $res['total']);
    }

    public function testListPendingStudentsExcludesStudentWithActiveClass()
    {
        // HV đang pending_arrange nhưng đã thuộc 1 lớp active → không hiện
        $classId = $this->classSvc->createClass(array(
            'program_id' => $this->progMath,
            'subject_id' => $this->subjectMath,
            'teacher_id' => $this->tBronze,
            'class_type' => 'group',
            'name'       => 'Nhóm test',
            'student_ids' => array($this->sNoDistrict),
            'schedules'  => array(array('day' => 3, 'start' => '18:00', 'end' => '19:00')),
        ));
        $this->assertNotNull($classId);

        $res = $this->svc->listPendingStudents();
        $ids = array();
        foreach ($res['items'] as $it) {
            $ids[] = (int) $it['user_id'];
        }
        $this->assertNotContains($this->sNoDistrict, $ids);
        $this->assertEquals(3, $res['total']);
    }

    // ===================== matchTeachers: hard filters =====================

    public function testMatchTeachersHardFilters()
    {
        $matched = $this->svc->matchTeachers($this->sMain);
        $ids = array();
        foreach ($matched as $m) {
            $ids[] = (int) $m['teacher_id'];
        }

        // Chỉ tGreen (nữ, Quận 1, dạy Toán, còn slot, khớp lịch) và tGoldF
        $this->assertContains($this->tGreen, $ids);
        $this->assertContains($this->tGoldF, $ids);
        $this->assertNotContains($this->tGold, $ids);       // sai giới tính
        $this->assertNotContains($this->tBronze, $ids);     // không dạy Toán + sai quận
        $this->assertNotContains($this->tPaused, $ids);     // không hoạt động
        $this->assertNotContains($this->tFull, $ids);       // đã đầy max_classes
    }

    public function testMatchTeachersRequiresScheduleOverlap()
    {
        // tGold: T2 19:00-20:00 không giao 18:00-19:30 → không match sMain
        $matched = $this->svc->matchTeachers($this->sMain);
        foreach ($matched as $m) {
            $this->assertNotSame($this->tGold, (int) $m['teacher_id']);
        }

        // HV chỉ có T2 18:00-19:30 → tGold vẫn không match
        $matched = $this->svc->matchTeachers($this->sNoDistrict);
        $ids = array();
        foreach ($matched as $m) {
            $ids[] = (int) $m['teacher_id'];
        }
        $this->assertNotContains($this->tGold, $ids);
    }

    public function testMatchTeachersNoFreeSlotsReturnsEmpty()
    {
        $matched = $this->svc->matchTeachers($this->sNoSlots);
        $this->assertSame(array(), $matched);
        $this->assertTrue($this->svc->hasErrors());
    }

    public function testMatchTeachersNotPendingReturnsEmpty()
    {
        $matched = $this->svc->matchTeachers($this->sStudying);
        $this->assertSame(array(), $matched);
        $this->assertTrue($this->svc->hasErrors());
    }

    public function testMatchTeachersOnlineRequiresCanTeachOnline()
    {
        $matched = $this->svc->matchTeachers($this->sOnline);
        $ids = array();
        foreach ($matched as $m) {
            $ids[] = (int) $m['teacher_id'];
        }
        // sOnline học online ngày 7 — không GV nào đăng ký lịch trống T7 → rỗng
        $this->assertSame(array(), $matched);

        // Bổ sung slot T7 cho tGreen (can_teach_online=1) và tGold (offline-only)
        $this->addFreeSlot($this->tGreen, 7, '08:00', '09:30');
        $this->addFreeSlot($this->tGold, 7, '08:00', '09:30');

        $matched = $this->svc->matchTeachers($this->sOnline);
        $ids = array();
        foreach ($matched as $m) {
            $ids[] = (int) $m['teacher_id'];
        }
        $this->assertContains($this->tGreen, $ids);
        $this->assertNotContains($this->tGold, $ids); // can_teach_online = 0
    }

    // ===================== matchTeachers: điểm + xếp hạng =====================

    public function testMatchScoreAndRanking()
    {
        $matched = $this->svc->matchTeachers($this->sMain);

        // Top 1: tGreen — 2/2 slot (50) + Quận 1 (15) + green (20) + sức chứa đầy (15) = 100
        $this->assertSame($this->tGreen, (int) $matched[0]['teacher_id']);
        $this->assertSame(100, (int) $matched[0]['score']);
        $this->assertSame(100, (int) $matched[0]['schedule_pct']);

        // tGoldF: 1/2 slot (25) + Quận 1 (15) + gold (12) + sức chứa (15) = 67
        $this->assertSame($this->tGoldF, (int) $matched[1]['teacher_id']);
        $this->assertSame(67, (int) $matched[1]['score']);
        $this->assertSame(50, (int) $matched[1]['schedule_pct']);
    }

    public function testMatchedSlotsAreIntersection()
    {
        $matched = $this->svc->matchTeachers($this->sMain);
        $top = $matched[0];

        $slots = $top['matched_slots'];
        usort($slots, function ($a, $b) {
            return strcmp((string) $a['day'], (string) $b['day']);
        });

        // T2: HV 18:00-19:30 ∩ GV 17:00-19:00 = 18:00-19:00
        $this->assertSame(2, (int) $slots[0]['day']);
        $this->assertSame('18:00', (string) $slots[0]['start']);
        $this->assertSame('19:00', (string) $slots[0]['end']);

        // T4: HV 18:00-19:30 ∩ GV 18:00-20:00 = 18:00-19:30
        $this->assertSame(4, (int) $slots[1]['day']);
        $this->assertSame('18:00', (string) $slots[1]['start']);
        $this->assertSame('19:30', (string) $slots[1]['end']);
    }

    // ===================== teacherWeekDetail =====================

    public function testTeacherWeekDetail()
    {
        $detail = $this->svc->teacherWeekDetail($this->tGreen);
        $this->assertSame($this->tGreen, (int) $detail['teacher']['user_id']);
        $this->assertSame(0, (int) $detail['teacher']['current_classes']);
        $this->assertCount(7, $detail['days']);
        $this->assertCount(2, $detail['free_slots']);

        // GV tFull có 1 lớp active → lịch cố định hiện trong chi tiết
        $detailFull = $this->svc->teacherWeekDetail($this->tFull);
        $this->assertSame(1, (int) $detailFull['teacher']['current_classes']);
        $this->assertCount(1, $detailFull['schedules']);
        $this->assertStringContainsString('HV Full', (string) $detailFull['schedules'][0]['student_names']);
    }

    public function testTeacherWeekDetailInvalidId()
    {
        $this->assertSame(array(), $this->svc->teacherWeekDetail(0));
        $this->assertTrue($this->svc->hasErrors());
    }

    public function testMatchedSlotsSubtractFixedSchedule()
    {
        // tGreen đã có lớp T2 18:30-20:00, lịch trống T2 17:00-19:00 (từ setUp)
        $classId = $this->classSvc->createClass(array(
            'program_id' => $this->progMath,
            'subject_id' => $this->subjectMath,
            'teacher_id' => $this->tGreen,
            'class_type' => 'one_on_one',
            'name'       => 'Lớp chiếm T2',
            'student_ids' => array($this->createStudent('hv_fixed_1', 'STF02', 'HV Fixed')),
            'schedules'  => array(array('day' => 2, 'start' => '18:30', 'end' => '20:00')),
        ));
        $this->assertNotNull($classId);

        // sNoDistrict muốn T2 18:00-19:30 → ∩ trống 17:00-19:00 = 18:00-19:00,
        // trừ lịch cố định 18:30-20:00 còn 18:00-18:30
        $matched = $this->svc->matchTeachers($this->sNoDistrict);
        $found = null;
        foreach ($matched as $m) {
            if ((int) $m['teacher_id'] === $this->tGreen) {
                $found = $m;
            }
        }
        $this->assertNotNull($found);
        $this->assertCount(1, $found['matched_slots']);
        $this->assertSame(2, (int) $found['matched_slots'][0]['day']);
        $this->assertSame('18:00', (string) $found['matched_slots'][0]['start']);
        $this->assertSame('18:30', (string) $found['matched_slots'][0]['end']);

        // Tạo lớp không bị BR-SCHED-01 từ chối
        $newClass = $this->svc->createClassFromMatch($this->sNoDistrict, $this->tGreen);
        $this->assertNotNull($newClass);
        $class = $this->classSvc->getClass($newClass);
        $this->assertCount(1, $class['schedules']);
        $this->assertSame('18:00', substr((string) $class['schedules'][0]['start_time'], 0, 5));
        $this->assertSame('18:30', substr((string) $class['schedules'][0]['end_time'], 0, 5));
    }

    // ===================== createClassFromMatch =====================

    public function testCreateClassFromMatch()
    {
        $classId = $this->svc->createClassFromMatch($this->sMain, $this->tGreen);
        $this->assertNotNull($classId);
        $this->assertFalse($this->svc->hasErrors());

        $class = $this->classSvc->getClass($classId);
        $this->assertSame('one_on_one', (string) $class['class_type']);
        $this->assertSame($this->tGreen, (int) $class['teacher_id']);
        $this->assertSame($this->subjectMath, (int) $class['subject_id']);
        $this->assertSame(array($this->sMain), $class['student_ids']);
        $this->assertSame('Toán Đại số 1-1 - HV Một', (string) $class['name']);
        $this->assertSame(MatchingService::DEFAULT_HW_DEADLINE, substr((string) $class['hw_deadline_time'], 0, 5));
        $this->assertSame(MatchingService::DEFAULT_HW_CHECK_HOURS, (int) $class['hw_check_hours']);

        // 2 khung giờ giao nhau được tạo thành lịch cố định
        $this->assertCount(2, $class['schedules']);

        // Sessions 4 tuần được sinh ra
        $sessions = $this->db->safeFetch(
            'select id from db_lms_sessions where class_id = ? and status = ?',
            array($classId, 'upcoming')
        );
        $this->assertGreaterThan(0, count($sessions));

        // HV chuyển sang chờ thu phí + không còn trong danh sách chờ
        $student = $this->userSvc->getStudent($this->sMain);
        $this->assertSame(MatchingService::STATUS_AFTER_ARRANGE, (string) $student['student_status']);

        $res = $this->svc->listPendingStudents();
        $ids = array();
        foreach ($res['items'] as $it) {
            $ids[] = (int) $it['user_id'];
        }
        $this->assertNotContains($this->sMain, $ids);

        // Lịch trống bị chiếm đã xoá (T2 + T4), GV không còn match HV khác cùng giờ
        $remaining = $this->db->safeFetch(
            'select day_of_week, start_time, end_time from db_lms_teacher_free_slots where teacher_id = ?',
            array($this->tGreen)
        );
        $this->assertSame(array(), $remaining);

        $matched = $this->svc->matchTeachers($this->sNoDistrict);
        foreach ($matched as $m) {
            $this->assertNotSame($this->tGreen, (int) $m['teacher_id']);
        }
    }

    public function testCreateClassFromMatchUsesOverriddenPriceAndSessions()
    {
        $classId = $this->svc->createClassFromMatch($this->sMain, $this->tGreen, array(
            'price_per_session' => 500000,
            'total_sessions'    => 24,
            'name'              => 'Lớp riêng',
        ));
        $this->assertNotNull($classId);

        $class = $this->classSvc->getClass($classId);
        $this->assertSame('Lớp riêng', (string) $class['name']);
        $this->assertEquals(500000.0, (float) $class['price_per_session']);
        $this->assertSame(24, (int) $class['total_sessions']);
        $this->assertSame(24, (int) $class['remaining_sessions']);
    }

    public function testCreateClassFromMatchAlreadyEnrolledRejected()
    {
        $classId = $this->svc->createClassFromMatch($this->sMain, $this->tGreen);
        $this->assertNotNull($classId);

        // HV cùng slot khác nhưng sMain giờ đã có lớp
        $result = $this->svc->createClassFromMatch($this->sMain, $this->tGoldF);
        $this->assertNull($result);
        $this->assertTrue($this->svc->hasErrors());
    }

    public function testCreateClassFromMatchWithoutSubjectRejected()
    {
        $noSubject = $this->createStudent('hv_match_7', 'ST007', 'HV Bảy', 'pending_arrange', array(
            'district' => 'Quận 1',
            'slots' => array(array('day' => 2, 'start' => '18:00', 'end' => '19:30')),
        ));
        $this->assertNull($this->svc->createClassFromMatch($noSubject, $this->tGreen));
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('subject_id', $errors);
    }

    public function testCreateClassFromMatchNonMatchingTeacherRejected()
    {
        // tGold không match sMain (sai giới tính) → từ chối
        $result = $this->svc->createClassFromMatch($this->sMain, $this->tGold);
        $this->assertNull($result);
        $errors = $this->svc->errors();
        $this->assertTrue(isset($errors['teacher_id']) || isset($errors['system']));
    }

    public function testCreateClassFromMatchQuitStudentRejected()
    {
        $quitId = $this->createStudent('hv_match_6', 'ST006', 'HV Sáu', 'quit', array(
            'district' => 'Quận 1',
            'subject_id' => $this->subjectMath,
            'slots' => array(array('day' => 2, 'start' => '18:00', 'end' => '19:30')),
        ));
        $this->assertNull($this->svc->createClassFromMatch($quitId, $this->tGreen));
        $this->assertTrue($this->svc->hasErrors());
    }
}
