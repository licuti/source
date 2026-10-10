<?php
/**
 * Tests cho UserService — hồ sơ học viên (ADM-01) + giáo viên (ADM-02).
 */

namespace Tests\Services;

use Lms\UserService;
use Tests\LmsTestCase;

class UserServiceProfileTest extends LmsTestCase
{
    /** @var UserService */
    private $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new UserService($this->db);
    }

    private function seedSubject()
    {
        $this->db->insert('db_lms_programs', array('name' => 'Toán THCS'));
        $progId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $progId, 'name' => 'Toán Đại số'));
        return (int) $this->db->insertId;
    }

    /**
     * Tạo 1 lớp thật (cần cho FK của db_lms_class_students).
     *
     * @param int $teacherId
     * @return int class id
     */
    private function seedClass($teacherId)
    {
        $subjectId = $this->seedSubject();
        $this->db->insert('db_lms_classes', array(
            'class_code'        => 'CLS-T' . $teacherId,
            'program_id'        => (int) $this->db->safeFetchOne('select program_id from db_lms_subjects where id = ?', array($subjectId))['program_id'],
            'subject_id'        => $subjectId,
            'teacher_id'        => (int) $teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp test',
            'price_per_session' => 300000,
            'total_sessions'    => 10,
            'remaining_sessions' => 10,
            'status'            => 'active',
            'start_date'        => date('Y-m-d'),
        ));
        return (int) $this->db->insertId;
    }

    // ===================== Học viên (ADM-01) =====================

    public function testCreateStudentCreatesUserAndProfile()
    {
        $id = $this->svc->createStudent(array(
            'username'  => 'hv_new',
            'password'  => 'matkhau123',
            'full_name' => 'Nguyễn Văn A',
            'phone'     => '0901234567',
            'grade'     => 'Lớp 5',
        ));

        $this->assertGreaterThan(0, (int) $id);

        $user = $this->db->safeFetchOne('select * from db_lms_users where id = ?', array($id));
        $this->assertSame('student', $user['role']);
        $this->assertSame('active', $user['status']);
        $this->assertSame('0901234567', $user['phone']);

        $profile = $this->db->safeFetchOne('select * from db_lms_students where user_id = ?', array($id));
        $this->assertSame('Nguyễn Văn A', $profile['full_name']);
        $this->assertSame('Lớp 5', $profile['grade']);
        $this->assertSame('pending_arrange', $profile['status']);
        $this->assertNotEmpty($profile['student_code']);
    }

    public function testCreateStudentAutoGeneratesCode()
    {
        $id1 = $this->svc->createStudent(array('username' => 'hv_a', 'password' => 'matkhau123', 'full_name' => 'A'));
        $id2 = $this->svc->createStudent(array('username' => 'hv_b', 'password' => 'matkhau123', 'full_name' => 'B'));

        $c1 = $this->db->safeFetchOne('select student_code from db_lms_students where user_id = ?', array($id1));
        $c2 = $this->db->safeFetchOne('select student_code from db_lms_students where user_id = ?', array($id2));

        $this->assertNotSame($c1['student_code'], $c2['student_code']);
        $this->assertStringStartsWith('Master', $c1['student_code']);
    }

    public function testCreateStudentRejectsInvalidPhone()
    {
        $this->svc->createStudent(array(
            'username'  => 'hv_bad',
            'password'  => 'matkhau123',
            'full_name' => 'Bad',
            'phone'     => '123',
        ));
        $this->assertArrayHasKey('phone', $this->svc->errors());
        $this->assertSame(0, (int) $this->db->count('db_lms_users', "role = 'student'"));
    }

    public function testCreateStudentRejectsBadParentPhone()
    {
        $this->svc->createStudent(array(
            'username'     => 'hv_bad2',
            'password'     => 'matkhau123',
            'full_name'    => 'Bad',
            'parent_phone' => 'abc',
        ));
        $this->assertArrayHasKey('parent_phone', $this->svc->errors());
    }

    public function testCreateStudentRejectsDuplicateUsername()
    {
        $this->svc->createStudent(array('username' => 'hv_dup', 'password' => 'matkhau123', 'full_name' => 'Dup'));
        $this->svc->createStudent(array('username' => 'hv_dup', 'password' => 'matkhau123', 'full_name' => 'Dup 2'));
        $this->assertArrayHasKey('username', $this->svc->errors());
        $this->assertSame(1, (int) $this->db->count('db_lms_users', "username = 'hv_dup'"));
    }

    public function testCreateStudentRejectsBadDistrict()
    {
        $this->svc->createStudent(array(
            'username'  => 'hv_d',
            'password'  => 'matkhau123',
            'full_name' => 'D',
            'district'  => 'Quận 999',
        ));
        $this->assertArrayHasKey('district', $this->svc->errors());
    }

    public function testCreateStudentStoresValidFreeTimeSlots()
    {
        $id = $this->svc->createStudent(array(
            'username'       => 'hv_slot',
            'password'       => 'matkhau123',
            'full_name'      => 'Slot',
            'free_time_slots' => array(
                array('day' => 2, 'start' => '17:30', 'end' => '19:00'),
                array('day' => 4, 'start' => '18:00', 'end' => '19:30'),
                array('day' => '', 'start' => '', 'end' => ''), // dòng rỗng bị bỏ qua
            ),
        ));
        $this->assertGreaterThan(0, (int) $id);

        $profile = $this->db->safeFetchOne('select free_time_slots from db_lms_students where user_id = ?', array($id));
        $slots = json_decode((string) $profile['free_time_slots'], true);
        $this->assertCount(2, $slots);
        $this->assertSame(2, (int) $slots[0]['day']);
        $this->assertSame('17:30', $slots[0]['start']);
    }

    public function testCreateStudentRejectsReversedTimeSlot()
    {
        $this->svc->createStudent(array(
            'username'       => 'hv_slot2',
            'password'       => 'matkhau123',
            'full_name'      => 'Slot2',
            'free_time_slots' => array(
                array('day' => 2, 'start' => '19:00', 'end' => '17:30'),
            ),
        ));
        $this->assertArrayHasKey('free_time_slots', $this->svc->errors());
    }

    public function testUpdateStudentKeepsPasswordWhenBlank()
    {
        $id = $this->svc->createStudent(array('username' => 'hv_u', 'password' => 'matkhau123', 'full_name' => 'U'));
        $hashBefore = $this->db->safeFetchOne('select password from db_lms_users where id = ?', array($id));
        $ok = $this->svc->updateStudent($id, array(
            'username'  => 'hv_u',
            'full_name' => 'U Đổi tên',
            'grade'     => 'Lớp 6',
        ));

        $this->assertTrue($ok);
        $hashAfter = $this->db->safeFetchOne('select password from db_lms_users where id = ?', array($id));
        $this->assertSame($hashBefore['password'], $hashAfter['password']);

        $profile = $this->db->safeFetchOne('select * from db_lms_students where user_id = ?', array($id));
        $this->assertSame('U Đổi tên', $profile['full_name']);
        $this->assertSame('Lớp 6', $profile['grade']);
    }

    public function testUpdateStudentChangesPasswordWhenProvided()
    {
        $id = $this->svc->createStudent(array('username' => 'hv_p', 'password' => 'matkhau123', 'full_name' => 'P'));
        $ok = $this->svc->updateStudent($id, array('username' => 'hv_p', 'full_name' => 'P', 'password' => 'matkhaumoi456'));
        $this->assertTrue($ok);

        $res = $this->svc->authenticate('hv_p', 'matkhaumoi456', 'student');
        $this->assertTrue($res['ok']);
    }

    public function testUpdateStudentRejectsUsernameTakenByOther()
    {
        $this->svc->createStudent(array('username' => 'hv_x', 'password' => 'matkhau123', 'full_name' => 'X'));
        $id2 = $this->svc->createStudent(array('username' => 'hv_y', 'password' => 'matkhau123', 'full_name' => 'Y'));

        $ok = $this->svc->updateStudent($id2, array('username' => 'hv_x', 'full_name' => 'Y'));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('username', $this->svc->errors());
    }

    public function testUpdateStudentAllowsKeepingOwnUsername()
    {
        $id = $this->svc->createStudent(array('username' => 'hv_z', 'password' => 'matkhau123', 'full_name' => 'Z'));
        $ok = $this->svc->updateStudent($id, array('username' => 'hv_z', 'full_name' => 'Z Z'));
        $this->assertTrue($ok);
    }

    public function testListStudentsFiltersByKeyword()
    {
        $this->svc->createStudent(array('username' => 'kw01', 'password' => 'matkhau123', 'full_name' => 'Trần Tìm Kiếm'));
        $this->svc->createStudent(array('username' => 'kw02', 'password' => 'matkhau123', 'full_name' => 'Người Khác'));

        $res = $this->svc->listStudents(array('q' => 'Tìm Kiếm'), 1, 20);
        $this->assertSame(1, (int) $res['total']);
        $this->assertCount(1, $res['items']);
        $this->assertSame('Trần Tìm Kiếm', $res['items'][0]['full_name']);
    }

    public function testListStudentsFiltersByStatus()
    {
        $this->svc->createStudent(array('username' => 'st01', 'password' => 'matkhau123', 'full_name' => 'A', 'status' => 'studying'));
        $this->svc->createStudent(array('username' => 'st02', 'password' => 'matkhau123', 'full_name' => 'B', 'status' => 'quit'));

        $res = $this->svc->listStudents(array('status' => 'studying'), 1, 20);
        $this->assertSame(1, (int) $res['total']);
        $this->assertSame('studying', $res['items'][0]['student_status']);
    }

    public function testSetUserStatusLocksAndUnlocks()
    {
        $id = $this->svc->createStudent(array('username' => 'hv_l', 'password' => 'matkhau123', 'full_name' => 'L'));

        $this->assertTrue($this->svc->setUserStatus($id, 'locked'));
        $row = $this->db->safeFetchOne('select status from db_lms_users where id = ?', array($id));
        $this->assertSame('locked', $row['status']);

        // Tài khoản bị khoá không đăng nhập được
        $res = $this->svc->authenticate('hv_l', 'matkhau123', 'student');
        $this->assertFalse($res['ok']);

        $this->assertTrue($this->svc->setUserStatus($id, 'active'));
        $res = $this->svc->authenticate('hv_l', 'matkhau123', 'student');
        $this->assertTrue($res['ok']);
    }

    public function testDeleteStudentRemovesProfileAndUser()
    {
        $id = $this->svc->createStudent(array('username' => 'hv_del', 'password' => 'matkhau123', 'full_name' => 'Del'));
        $this->assertTrue($this->svc->deleteStudent($id));

        $this->assertSame(0, (int) $this->db->count('db_lms_users', 'id = ' . (int) $id));
        $this->assertSame(0, (int) $this->db->count('db_lms_students', 'user_id = ' . (int) $id));
    }

    public function testDeleteStudentRefusesWhenEnrolled()
    {
        $id = $this->svc->createStudent(array('username' => 'hv_enroll', 'password' => 'matkhau123', 'full_name' => 'Enroll'));
        $tid = $this->svc->createTeacher(array('username' => 'gv_enroll', 'password' => 'matkhau123', 'full_name' => 'GV Enroll'));
        $classId = $this->seedClass($tid);
        $this->db->insert('db_lms_class_students', array(
            'class_id'   => $classId,
            'student_id' => $id,
            'status'     => 'active',
        ));

        $ok = $this->svc->deleteStudent($id);
        $this->assertFalse($ok);
        $this->assertArrayHasKey('system', $this->svc->errors());
        $this->assertSame(1, (int) $this->db->count('db_lms_users', 'id = ' . (int) $id));
    }

    // ===================== Giáo viên (ADM-02) =====================

    public function testCreateTeacherCreatesUserAndProfile()
    {
        $subjectId = $this->seedSubject();
        $id = $this->svc->createTeacher(array(
            'username'      => 'gv_new',
            'password'      => 'matkhau123',
            'full_name'     => 'Phạm Giáo Viên',
            'phone'         => '0707676299',
            'cccd'          => '079556565656',
            'tax_code'      => '0795565656',
            'rank'          => 'gold',
            'max_classes'   => 3,
            'teachable_subjects' => array($subjectId),
            'teachable_districts' => array('Quận 1', 'Quận 3'),
            'service_fee_percentage' => '45',
            'is_commit_08'  => 1,
        ));

        $this->assertGreaterThan(0, (int) $id);
        $user = $this->db->safeFetchOne('select * from db_lms_users where id = ?', array($id));
        $this->assertSame('teacher', $user['role']);

        $profile = $this->db->safeFetchOne('select * from db_lms_teachers where user_id = ?', array($id));
        $this->assertSame('Phạm Giáo Viên', $profile['full_name']);
        $this->assertSame('gold', $profile['rank']);
        $this->assertSame(3, (int) $profile['max_classes']);
        $this->assertSame('45.00', $profile['service_fee_percentage']);
        $this->assertSame(1, (int) $profile['is_commit_08']);
        $this->assertSame(array($subjectId), json_decode((string) $profile['teachable_subjects'], true));
        $this->assertSame(array('Quận 1', 'Quận 3'), json_decode((string) $profile['teachable_districts'], true));
    }

    public function testCreateTeacherRejectsBadCccd()
    {
        $this->svc->createTeacher(array(
            'username'  => 'gv_bad',
            'password'  => 'matkhau123',
            'full_name' => 'Bad',
            'cccd'      => '123',
        ));
        $this->assertArrayHasKey('cccd', $this->svc->errors());
        $this->assertSame(0, (int) $this->db->count('db_lms_users', "role = 'teacher'"));
    }

    public function testCreateTeacherRejectsBadTaxCode()
    {
        $this->svc->createTeacher(array(
            'username'  => 'gv_bad2',
            'password'  => 'matkhau123',
            'full_name' => 'Bad',
            'tax_code'  => '123',
        ));
        $this->assertArrayHasKey('tax_code', $this->svc->errors());
    }

    public function testCreateTeacherRejectsFeeOutOfRange()
    {
        $this->svc->createTeacher(array(
            'username'               => 'gv_fee',
            'password'               => 'matkhau123',
            'full_name'              => 'Fee',
            'service_fee_percentage' => '150',
        ));
        $this->assertArrayHasKey('service_fee_percentage', $this->svc->errors());
    }

    public function testCreateTeacherRejectsDuplicateCccd()
    {
        $this->svc->createTeacher(array(
            'username'  => 'gv_c1',
            'password'  => 'matkhau123',
            'full_name' => 'C1',
            'cccd'      => '079556565656',
        ));
        $this->svc->createTeacher(array(
            'username'  => 'gv_c2',
            'password'  => 'matkhau123',
            'full_name' => 'C2',
            'cccd'      => '079556565656',
        ));
        $this->assertArrayHasKey('cccd', $this->svc->errors());
    }

    public function testCreateTeacherRejectsInvalidSubject()
    {
        $this->svc->createTeacher(array(
            'username'             => 'gv_sub',
            'password'             => 'matkhau123',
            'full_name'            => 'Sub',
            'teachable_subjects'   => array(99999),
        ));
        $this->assertArrayHasKey('teachable_subjects', $this->svc->errors());
    }

    public function testCreateTeacherRejectsInvalidDistrict()
    {
        $this->svc->createTeacher(array(
            'username'             => 'gv_dis',
            'password'             => 'matkhau123',
            'full_name'            => 'Dis',
            'teachable_districts'  => array('Hà Nội'),
        ));
        $this->assertArrayHasKey('teachable_districts', $this->svc->errors());
    }

    public function testUpdateTeacherUpdatesProfile()
    {
        $subjectId = $this->seedSubject();
        $id = $this->svc->createTeacher(array(
            'username'  => 'gv_upd',
            'password'  => 'matkhau123',
            'full_name' => 'Upd',
        ));

        $ok = $this->svc->updateTeacher($id, array(
            'username'             => 'gv_upd',
            'full_name'            => 'Upd Đổi',
            'rank'                 => 'green',
            'max_classes'          => 5,
            'teachable_subjects'   => array($subjectId),
            'service_fee_percentage' => '50',
        ));
        $this->assertTrue($ok);

        $profile = $this->db->safeFetchOne('select * from db_lms_teachers where user_id = ?', array($id));
        $this->assertSame('Upd Đổi', $profile['full_name']);
        $this->assertSame('green', $profile['rank']);
        $this->assertSame(5, (int) $profile['max_classes']);
        $this->assertSame(array($subjectId), json_decode((string) $profile['teachable_subjects'], true));
    }
    public function testListTeachersCountsActiveClassesAndMonthTicks()
    {
        $tid = $this->svc->createTeacher(array('username' => 'gv_cnt', 'password' => 'matkhau123', 'full_name' => 'Cnt'));
        $this->seedClass($tid);
        $this->db->insert('db_lms_violations', array(
            'teacher_id' => $tid, 'tick_amount' => 2, 'reason' => 'manual_penalty',
            'violation_date' => date('Y-m-d'),
        ));

        $res = $this->svc->listTeachers(array(), 1, 20);
        $this->assertSame(1, (int) $res['total']);
        $this->assertSame(1, (int) $res['items'][0]['active_classes']);
        $this->assertSame(2, (int) $res['items'][0]['month_ticks']);
    }

    public function testDeleteTeacherRefusesWhenHasActiveClass()
    {
        $tid = $this->svc->createTeacher(array('username' => 'gv_del', 'password' => 'matkhau123', 'full_name' => 'Del'));
        $this->seedClass($tid);

        $this->assertFalse($this->svc->deleteTeacher($tid));
        $this->assertSame(1, (int) $this->db->count('db_lms_users', 'id = ' . (int) $tid));
    }

    public function testDeleteTeacherSucceedsWhenNoActiveClass()
    {
        $tid = $this->svc->createTeacher(array('username' => 'gv_del2', 'password' => 'matkhau123', 'full_name' => 'Del2'));
        $this->assertTrue($this->svc->deleteTeacher($tid));
        $this->assertSame(0, (int) $this->db->count('db_lms_users', 'id = ' . (int) $tid));
    }

    // ===================== Validate helpers =====================

    public function testPhoneValidation()
    {
        $this->assertTrue(UserService::isValidPhoneVn('0901234567'));
        $this->assertTrue(UserService::isValidPhoneVn('0281234567'));
        $this->assertFalse(UserService::isValidPhoneVn('123456'));
        $this->assertFalse(UserService::isValidPhoneVn('090123456'));
    }

    public function testCccdAndTaxCodeValidation()
    {
        $this->assertTrue(UserService::isValidCccd('079556565656'));
        $this->assertFalse(UserService::isValidCccd('07955656565'));
        $this->assertTrue(UserService::isValidTaxCode('0795565656'));
        $this->assertFalse(UserService::isValidTaxCode('079556565'));
    }
}
