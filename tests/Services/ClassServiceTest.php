<?php
/**
 * Tests cho ClassService — quản lý lớp học (ADM-03, Phase 1.2).
 *
 * Bao gồm: tạo lớp 1-1/nhóm + sinh sessions, BR max_classes, BR-SCHED-01,
 * parseClassSchedules, updateClass, deleteClass (soft-delete), setClassStatus,
 * listClasses filters.
 */

namespace Tests\Services;

use Lms\ClassService;
use Tests\LmsTestCase;

class ClassServiceTest extends LmsTestCase
{
    /** @var ClassService */
    private $svc;

    /** @var int */
    private $programId;

    /** @var int */
    private $subjectId;

    /** @var int */
    private $teacherId;

    /** @var int */
    private $student1;

    /** @var int */
    private $student2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = new ClassService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'Chương trình Toán'));
        $this->programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $this->programId, 'name' => 'Toán Đại số'));
        $this->subjectId = (int) $this->db->insertId;

        $userSvc = new \Lms\UserService($this->db);
        $this->teacherId = $userSvc->createUser('gv_lop', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->teacherId,
            'teacher_code' => 'TE001',
            'full_name'    => 'GV Lớp',
            'status'       => 'active',
            'max_classes'  => 10,
        ));

        $this->student1 = $userSvc->createUser('hv_lop1', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $this->student1,
            'student_code' => 'ST001',
            'full_name'    => 'HV Một',
            'status'       => 'studying',
        ));
        $this->student2 = $userSvc->createUser('hv_lop2', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $this->student2,
            'student_code' => 'ST002',
            'full_name'    => 'HV Hai',
            'status'       => 'studying',
        ));
    }

    /**
     * Tạo lớp one_on_one đầy đủ: class_code, 1 student, schedules → sessions.
     */
    public function testCreateOneOnOneClassGeneratesSessions()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Toán 1-1 HV Một',
            'price_per_session' => 500000,
            'total_sessions'    => 12,
            'hw_deadline_time'  => '12:00',
            'schedules'         => array(
                array('day' => 2, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => array($this->student1),
        ));

        $this->assertGreaterThan(0, $classId);
        $this->assertSame(array(), $this->svc->errors());

        $row = $this->db->safeFetchOne('select * from db_lms_classes where id = ?', array($classId));
        $this->assertStringStartsWith('CLS11-', (string) $row['class_code']);
        $this->assertSame('one_on_one', $row['class_type']);
        $this->assertSame(12, (int) $row['total_sessions']);
        $this->assertSame(12, (int) $row['remaining_sessions']);
        $this->assertSame('12:00:00', (string) $row['hw_deadline_time']);

        // 1 học viên enrolled
        $this->assertSame(1, (int) $this->db->count('db_lms_class_students', 'class_id = ?', array($classId)));

        // 1 lịch cố định
        $this->assertSame(1, (int) $this->db->count('db_lms_schedules', 'class_id = ?', array($classId)));

        // Sessions được sinh trong 4 tuần, status upcoming
        $sessionCount = (int) $this->db->count(
            'db_lms_sessions',
            'class_id = ? and status = ?',
            array($classId, 'upcoming')
        );
        $this->assertGreaterThan(0, $sessionCount);

        // session_students có đủ cho từng session (1 HV/session)
        $ssCount = (int) $this->db->count(
            'db_lms_session_students',
            'session_id in (select id from db_lms_sessions where class_id = ?)',
            array($classId)
        );
        $this->assertSame($sessionCount, $ssCount);
    }

    public function testCreateGroupClassWithMultipleStudents()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'group',
            'name'              => 'Toán nhóm A',
            'price_per_session' => 300000,
            'schedules'         => array(
                array('day' => 3, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => array($this->student1, $this->student2),
        ));

        $this->assertGreaterThan(0, $classId);
        $row = $this->db->safeFetchOne('select class_code from db_lms_classes where id = ?', array($classId));
        $this->assertStringStartsWith('CLSGR-', (string) $row['class_code']);
        $this->assertSame(2, (int) $this->db->count('db_lms_class_students', 'class_id = ?', array($classId)));
    }

    public function testOneOnOneRejectsTwoStudents()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp sai sĩ số',
            'price_per_session' => 500000,
            'student_ids'       => array($this->student1, $this->student2),
        ));

        $this->assertNull($classId);
        $this->assertArrayHasKey('student_ids', $this->svc->errors());
        $this->assertSame(0, (int) $this->db->count('db_lms_classes', '1=1'));
    }

    public function testGroupRejectsZeroStudents()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'group',
            'name'              => 'Lớp rỗng',
            'price_per_session' => 300000,
            'student_ids'       => array(),
        ));

        $this->assertNull($classId);
        $this->assertArrayHasKey('student_ids', $this->svc->errors());
    }

    public function testRejectsNonExistentStudent()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp HV lạ',
            'price_per_session' => 500000,
            'student_ids'       => array(99999),
        ));

        $this->assertNull($classId);
        $this->assertArrayHasKey('student_ids', $this->svc->errors());
    }

    public function testRejectsInvalidFields()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp sai giá',
            'price_per_session' => -100,
            'teacher_percentage' => 150,
            'total_sessions'    => -1,
            'hw_deadline_time'  => '25:99',
            'hw_check_hours'    => 9999,
            'start_date'        => '08-10-2026',
            'student_ids'       => array($this->student1),
        ));

        $this->assertNull($classId);
        $errors = $this->svc->errors();
        foreach (array('price_per_session', 'teacher_percentage', 'total_sessions',
                     'hw_deadline_time', 'hw_check_hours', 'start_date') as $field) {
            $this->assertArrayHasKey($field, $errors);
        }
    }

    public function testTeacherPercentageEmptyInheritsAsNull()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp kế thừa %',
            'price_per_session' => 500000,
            'student_ids'       => array($this->student1),
        ));
        $this->assertGreaterThan(0, $classId);

        $row = $this->db->safeFetchOne('select teacher_percentage from db_lms_classes where id = ?', array($classId));
        $this->assertNull($row['teacher_percentage']);
    }

    public function testBrMaxClassesRejectsSecondClass()
    {
        $userSvc = new \Lms\UserService($this->db);
        $teacherId = $userSvc->createUser('gv_max', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $teacherId,
            'teacher_code' => 'TE002',
            'full_name'    => 'GV Max',
            'status'       => 'active',
            'max_classes'  => 1,
        ));

        $first = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp đầu',
            'price_per_session' => 500000,
            'student_ids'       => array($this->student1),
        ));
        $this->assertGreaterThan(0, $first);

        $second = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp thứ 2',
            'price_per_session' => 500000,
            'student_ids'       => array($this->student2),
        ));

        $this->assertNull($second);
        $this->assertArrayHasKey('teacher_id', $this->svc->errors());
        $this->assertSame(1, (int) $this->db->count('db_lms_classes', 'teacher_id = ?', array($teacherId)));
    }

    public function testBrSched01RejectsConflictAndRollsBack()
    {
        $classA = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp A',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => 3, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => array($this->student1),
        ));
        $this->assertGreaterThan(0, $classA);

        $classB = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp B trùng lịch',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => 3, 'start' => '19:30', 'end' => '21:00'),
            ),
            'student_ids'       => array($this->student2),
        ));

        $this->assertNull($classB);
        $errors = $this->svc->errors();
        $this->assertArrayHasKey('schedules', $errors);
        $this->assertStringContainsString('BR-SCHED-01', $errors['schedules']);

        // RollBack: lớp B không được insert
        $this->assertSame(1, (int) $this->db->count('db_lms_classes', 'teacher_id = ?', array($this->teacherId)));
    }

    public function testParseClassSchedulesRejectsOverlapSameDay()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp trùng lịch trong lớp',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => 3, 'start' => '19:00', 'end' => '20:30'),
                array('day' => 3, 'start' => '20:00', 'end' => '21:00'),
            ),
            'student_ids'       => array($this->student1),
        ));

        $this->assertNull($classId);
        $this->assertArrayHasKey('schedules', $this->svc->errors());
        $this->assertStringContainsString('trùng nhau', $this->svc->errors()['schedules']);
    }

    public function testParseClassSchedulesRejectsDuplicateRowsKeepingOne()
    {
        // 2 dòng trùng hoàn toàn → giữ 1, không lỗi
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp lịch trùng lặp',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => 3, 'start' => '19:00', 'end' => '20:30'),
                array('day' => 3, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => array($this->student1),
        ));

        $this->assertGreaterThan(0, $classId);
        $this->assertSame(1, (int) $this->db->count('db_lms_schedules', 'class_id = ?', array($classId)));
    }

    public function testParseClassSchedulesRejectsReversedTime()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp giờ ngược',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => 5, 'start' => '20:00', 'end' => '19:00'),
            ),
            'student_ids'       => array($this->student1),
        ));

        $this->assertNull($classId);
        $this->assertArrayHasKey('schedules', $this->svc->errors());
    }

    public function testParseClassSchedulesIgnoresEmptyRows()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp dòng rỗng',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => '', 'start' => '', 'end' => ''),
                array('day' => 2, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => array($this->student1),
        ));

        $this->assertGreaterThan(0, $classId);
        $this->assertSame(1, (int) $this->db->count('db_lms_schedules', 'class_id = ?', array($classId)));
    }

    public function testCreateClassWithoutSchedulesOrStudentsBackwardCompat()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp tương thích ngược',
            'price_per_session' => 500000,
            'total_sessions'    => 12,
        ));

        $this->assertGreaterThan(0, $classId);
        $this->assertSame(0, (int) $this->db->count('db_lms_schedules', 'class_id = ?', array($classId)));
        $this->assertSame(0, (int) $this->db->count('db_lms_sessions', 'class_id = ?', array($classId)));
        $this->assertSame(0, (int) $this->db->count('db_lms_class_students', 'class_id = ?', array($classId)));
    }

    public function testUpdateClassChangesPriceTeacherSchedulesAndRemaining()
    {
        $userSvc = new \Lms\UserService($this->db);
        $teacher2 = $userSvc->createUser('gv_up', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $teacher2,
            'teacher_code' => 'TE003',
            'full_name'    => 'GV Up',
            'status'       => 'active',
            'max_classes'  => 10,
        ));

        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp cũ',
            'price_per_session' => 500000,
            'total_sessions'    => 12,
            'schedules'         => array(
                array('day' => 2, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => array($this->student1),
        ));
        $this->assertGreaterThan(0, $classId);

        $ok = $this->svc->updateClass($classId, array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $teacher2,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp mới',
            'price_per_session' => 600000,
            'total_sessions'    => 16,
            'schedules'         => array(
                array('day' => 4, 'start' => '18:00', 'end' => '19:30'),
            ),
            'student_ids'       => array($this->student1),
        ));

        $this->assertTrue($ok);
        $this->assertSame(array(), $this->svc->errors());

        $row = $this->db->safeFetchOne('select * from db_lms_classes where id = ?', array($classId));
        $this->assertSame(600000.0, (float) $row['price_per_session']);
        $this->assertSame(16, (int) $row['total_sessions']);
        $this->assertSame(16, (int) $row['remaining_sessions']); // 12 + delta 4
        $this->assertSame($teacher2, (int) $row['teacher_id']);

        // Lịch thay nguyên khối
        $schedules = $this->db->safeFetch('select * from db_lms_schedules where class_id = ?', array($classId));
        $this->assertCount(1, $schedules);
        $this->assertSame(4, (int) $schedules[0]['day_of_week']);

        // Sessions upcoming regen với GV mới
        $sessions = $this->db->safeFetch(
            'select teacher_id, status from db_lms_sessions where class_id = ?',
            array($classId)
        );
        $this->assertNotEmpty($sessions);
        foreach ($sessions as $s) {
            $this->assertSame($teacher2, (int) $s['teacher_id']);
            $this->assertSame('upcoming', $s['status']);
        }
    }

    public function testUpdateClassWithoutOptionalKeysKeepsSchedulesAndStudents()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp cũ',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => 2, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => array($this->student1),
        ));
        $this->assertGreaterThan(0, $classId);

        // updateClass không truyền schedules/student_ids → giữ nguyên lịch + enrolment
        $ok = $this->svc->updateClass($classId, array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp đã đổi tên',
            'price_per_session' => 550000,
        ));

        $this->assertTrue($ok);
        $this->assertSame(1, (int) $this->db->count('db_lms_schedules', 'class_id = ?', array($classId)));
        $this->assertSame(1, (int) $this->db->count('db_lms_class_students', 'class_id = ?', array($classId)));
    }

    public function testUpdateClassRejectsScheduleConflictAndDoesNotChange()
    {
        $classA = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp A',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => 3, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => array($this->student1),
        ));
        $classB = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp B',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => 5, 'start' => '18:00', 'end' => '19:30'),
            ),
            'student_ids'       => array($this->student2),
        ));
        $this->assertGreaterThan(0, $classA);
        $this->assertGreaterThan(0, $classB);

        $ok = $this->svc->updateClass($classB, array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp B',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => 3, 'start' => '19:30', 'end' => '21:00'), // trùng lớp A
            ),
            'student_ids'       => array($this->student2),
        ));

        $this->assertFalse($ok);
        $this->assertArrayHasKey('schedules', $this->svc->errors());

        // Class B giữ nguyên lịch cũ
        $schedules = $this->db->safeFetch('select day_of_week from db_lms_schedules where class_id = ?', array($classB));
        $this->assertCount(1, $schedules);
        $this->assertSame(5, (int) $schedules[0]['day_of_week']);
    }

    public function testUpdateClassSyncsEnrollments()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'group',
            'name'              => 'Lớp nhóm',
            'price_per_session' => 300000,
            'schedules'         => array(
                array('day' => 6, 'start' => '09:00', 'end' => '10:30'),
            ),
            'student_ids'       => array($this->student1, $this->student2),
        ));
        $this->assertGreaterThan(0, $classId);

        $ok = $this->svc->updateClass($classId, array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'group',
            'name'              => 'Lớp nhóm',
            'price_per_session' => 300000,
            'schedules'         => array(
                array('day' => 6, 'start' => '09:00', 'end' => '10:30'),
            ),
            'student_ids'       => array($this->student2),
        ));

        $this->assertTrue($ok);

        // student1 bị bỏ, chỉ còn student2
        $rows = $this->db->safeFetch(
            'select student_id from db_lms_class_students where class_id = ?',
            array($classId)
        );
        $this->assertCount(1, $rows);
        $this->assertSame($this->student2, (int) $rows[0]['student_id']);

        // session_students của các session upcoming cũng chỉ còn student2
        $ss = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_session_students ss'
            . ' join db_lms_sessions s on s.id = ss.session_id'
            . ' where s.class_id = ? and ss.student_id = ?',
            array($classId, $this->student1)
        );
        $this->assertSame(0, (int) $ss['c']);
    }

    public function testDeleteClassSoftDeletesAndCleansUpcomingSessions()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp xoá',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => 2, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => array($this->student1),
        ));
        $this->assertGreaterThan(0, $classId);

        // Insert tay 1 session finished trong quá khứ (lịch sử giao dịch)
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $classId,
            'teacher_id'   => $this->teacherId,
            'session_date' => date('Y-m-d', strtotime('-7 days')),
            'start_time'   => '19:00',
            'end_time'     => '20:30',
            'status'       => 'finished',
        ));

        $this->assertTrue($this->svc->deleteClass($classId));

        $row = $this->db->safeFetchOne('select deleted_at from db_lms_classes where id = ?', array($classId));
        $this->assertNotNull($row['deleted_at']);

        $this->assertSame(0, (int) $this->db->count('db_lms_schedules', 'class_id = ?', array($classId)));
        $this->assertSame(0, (int) $this->db->count('db_lms_class_students', 'class_id = ?', array($classId)));
        $this->assertSame(0, (int) $this->db->count('db_lms_sessions', 'class_id = ? and status = ?', array($classId, 'upcoming')));
        $this->assertSame(1, (int) $this->db->count('db_lms_sessions', 'class_id = ? and status = ?', array($classId, 'finished')));
    }

    public function testDeleteNonExistentClassFails()
    {
        $this->assertFalse($this->svc->deleteClass(99999));
        $this->assertArrayHasKey('system', $this->svc->errors());
    }

    public function testSetClassStatusActivePausedCycle()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp trạng thái',
            'price_per_session' => 500000,
            'student_ids'       => array($this->student1),
        ));

        $this->assertTrue($this->svc->setClassStatus($classId, 'paused'));
        $row = $this->db->safeFetchOne('select status from db_lms_classes where id = ?', array($classId));
        $this->assertSame('paused', $row['status']);

        $this->assertTrue($this->svc->setClassStatus($classId, 'active'));
        $row = $this->db->safeFetchOne('select status from db_lms_classes where id = ?', array($classId));
        $this->assertSame('active', $row['status']);
    }

    public function testSetClassStatusRejectsInvalidValue()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp trạng thái',
            'price_per_session' => 500000,
            'student_ids'       => array($this->student1),
        ));

        $this->assertFalse($this->svc->setClassStatus($classId, 'finished'));
        $this->assertFalse($this->svc->setClassStatus($classId, 'cancelled'));
        $this->assertArrayHasKey('system', $this->svc->errors());
    }

    public function testGetClassReturnsSchedulesAndStudentIds()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp xem',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => 2, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => array($this->student1),
        ));

        $row = $this->svc->getClass($classId);
        $this->assertSame($classId, (int) $row['id']);
        $this->assertSame('Chương trình Toán', (string) $row['program_name']);
        $this->assertSame('Toán Đại số', (string) $row['subject_name']);
        $this->assertSame('TE001', (string) $row['teacher_code']);
        $this->assertCount(1, $row['schedules']);
        $this->assertSame(array($this->student1), $row['student_ids']);
    }

    public function testGetClassIgnoresDeleted()
    {
        $classId = $this->svc->createClass(array(
            'program_id'        => $this->programId,
            'subject_id'        => $this->subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp xoá',
            'price_per_session' => 500000,
            'student_ids'       => array($this->student1),
        ));
        $this->assertTrue($this->svc->deleteClass($classId));
        $this->assertSame(array(), $this->svc->getClass($classId));
    }

    public function testListClassesFiltersAndPagination()
    {
        $userSvc = new \Lms\UserService($this->db);
        $teacher2 = $userSvc->createUser('gv_list', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $teacher2,
            'teacher_code' => 'TE004',
            'full_name'    => 'GV List',
            'status'       => 'active',
            'max_classes'  => 10,
        ));
        $this->db->insert('db_lms_programs', array('name' => 'Chương trình Anh'));
        $program2 = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $program2, 'name' => 'Tiếng Anh'));
        $subject2 = (int) $this->db->insertId;

        $c1 = $this->svc->createClass(array(
            'program_id' => $this->programId, 'subject_id' => $this->subjectId, 'teacher_id' => $this->teacherId,
            'class_type' => 'one_on_one', 'name' => 'Toán 1-1 Một', 'price_per_session' => 500000,
            'schedules'  => array(array('day' => 2, 'start' => '19:00', 'end' => '20:30')),
            'student_ids' => array($this->student1),
        ));
        $c2 = $this->svc->createClass(array(
            'program_id' => $program2, 'subject_id' => $subject2, 'teacher_id' => $teacher2,
            'class_type' => 'group', 'name' => 'Anh nhóm Hai', 'price_per_session' => 300000,
            'schedules'  => array(array('day' => 4, 'start' => '19:00', 'end' => '20:30')),
            'student_ids' => array($this->student1, $this->student2),
        ));
        $c3 = $this->svc->createClass(array(
            'program_id' => $program2, 'subject_id' => $subject2, 'teacher_id' => $this->teacherId,
            'class_type' => 'group', 'name' => 'Anh nhóm Ba', 'price_per_session' => 300000,
            'student_ids' => array($this->student2),
        ));
        $this->assertGreaterThan(0, $c3);
        $this->assertTrue($this->svc->setClassStatus($c3, 'paused'));

        // Tất cả
        $res = $this->svc->listClasses(array(), 1, 20);
        $this->assertSame(3, (int) $res['total']);
        $this->assertCount(3, $res['items']);

        // Filter q
        $res = $this->svc->listClasses(array('q' => 'nhóm'), 1, 20);
        $this->assertSame(2, (int) $res['total']);

        // Filter teacher_id
        $res = $this->svc->listClasses(array('teacher_id' => $teacher2), 1, 20);
        $this->assertSame(1, (int) $res['total']);

        // Filter program_ids[]
        $res = $this->svc->listClasses(array('program_ids' => array($program2)), 1, 20);
        $this->assertSame(2, (int) $res['total']);
        $res = $this->svc->listClasses(array('program_ids' => array($this->programId, $program2)), 1, 20);
        $this->assertSame(3, (int) $res['total']);

        // Filter status
        $res = $this->svc->listClasses(array('status' => 'paused'), 1, 20);
        $this->assertSame(1, (int) $res['total']);

        // Filter class_type
        $res = $this->svc->listClasses(array('class_type' => 'group'), 1, 20);
        $this->assertSame(2, (int) $res['total']);

        // Filter day_of_week ("Tìm theo TG")
        $res = $this->svc->listClasses(array('day_of_week' => 2), 1, 20);
        $this->assertSame(1, (int) $res['total']);
        $res = $this->svc->listClasses(array('day_of_week' => 4), 1, 20);
        $this->assertSame(1, (int) $res['total']);
        $res = $this->svc->listClasses(array('day_of_week' => 6), 1, 20);
        $this->assertSame(0, (int) $res['total']);

        // Phân trang
        $res = $this->svc->listClasses(array(), 1, 2);
        $this->assertSame(3, (int) $res['total']);
        $this->assertCount(2, $res['items']);
        $res = $this->svc->listClasses(array(), 2, 2);
        $this->assertCount(1, $res['items']);

        // Schedules được gộp 1 query (không N+1)
        foreach ($res['items'] as $item) {
            $this->assertArrayHasKey('schedules', $item);
        }
    }

    public function testListClassesExcludesDeleted()
    {
        $classId = $this->svc->createClass(array(
            'program_id' => $this->programId, 'subject_id' => $this->subjectId, 'teacher_id' => $this->teacherId,
            'class_type' => 'one_on_one', 'name' => 'Lớp xoá', 'price_per_session' => 500000,
            'student_ids' => array($this->student1),
        ));
        $this->assertTrue($this->svc->deleteClass($classId));

        $res = $this->svc->listClasses(array(), 1, 20);
        $this->assertSame(0, (int) $res['total']);
    }
}
