<?php
/**
 * Tests cho EnrolmentService — Học sinh − Lớp học (ADM-04, Phase 1.3).
 *
 * Bao gồm: enrol (flip + sĩ số 1-1 + lớp/deleted/quit), transfer (2 chiều sync
 * session_students, giữ lịch sử có dữ liệu), unenroll, listMatrix (cột động +
 * filters + tab), audit log.
 */

namespace Tests\Services;

use Lms\ClassService;
use Lms\EnrolmentService;
use Lms\UserService;
use Tests\LmsTestCase;

class EnrolmentServiceTest extends LmsTestCase
{
    /** @var ClassService */
    private $classSvc;

    /** @var EnrolmentService */
    private $svc;

    /** @var UserService */
    private $userSvc;

    private $programMath;
    private $programEnglish;
    private $teacher1;
    private $teacher2;
    private $student1;
    private $student2;
    private $studentQuit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classSvc = new ClassService($this->db);
        $this->svc      = new EnrolmentService($this->db);
        $this->userSvc  = new UserService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'Toán'));
        $this->programMath = (int) $this->db->insertId;
        $this->db->insert('db_lms_programs', array('name' => 'Tiếng Anh'));
        $this->programEnglish = (int) $this->db->insertId;

        $this->db->insert('db_lms_subjects', array('program_id' => $this->programMath, 'name' => 'Toán Đại số'));
        $subjectMath = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $this->programEnglish, 'name' => 'Anh văn'));
        $subjectEnglish = (int) $this->db->insertId;

        $this->teacher1 = $this->createTeacher('te_enrol_1', 'TE001');
        $this->teacher2 = $this->createTeacher('te_enrol_2', 'TE002');

        $this->student1 = $this->createStudent('hv_enrol_1', 'ST001', 'HV Một', 'studying');
        $this->student2 = $this->createStudent('hv_enrol_2', 'ST002', 'HV Hai', 'studying');
        $this->studentQuit = $this->createStudent('hv_enrol_3', 'ST003', 'HV Ba', 'quit');

        $this->subjectMath = $subjectMath;
        $this->subjectEnglish = $subjectEnglish;
    }

    private function createTeacher($username, $code)
    {
        $id = $this->userSvc->createUser($username, 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $id,
            'teacher_code' => $code,
            'full_name'    => 'GV ' . $code,
            'status'       => 'active',
            'max_classes'  => 10,
        ));
        return $id;
    }

    private function createStudent($username, $code, $name, $status)
    {
        $id = $this->userSvc->createUser($username, 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $id,
            'student_code' => $code,
            'full_name'    => $name,
            'grade'        => 'Lớp 5',
            'status'       => $status,
        ));
        return $id;
    }

    /**
     * Tạo lớp group qua ClassService (có lịch → sinh sessions upcoming).
     */
    private function createGroupClass($programId, $teacherId, array $studentIds, $day = 2)
    {
        return $this->classSvc->createClass(array(
            'program_id'        => $programId,
            'subject_id'        => $programId === $this->programMath ? $this->subjectMath : $this->subjectEnglish,
            'teacher_id'        => $teacherId,
            'class_type'        => 'group',
            'name'              => 'Lớp nhóm ' . $day,
            'price_per_session' => 300000,
            'schedules'         => array(
                array('day' => $day, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => $studentIds,
        ));
    }

    private function createOneOnOneClass($programId, $teacherId, $studentId, $day = 4)
    {
        return $this->classSvc->createClass(array(
            'program_id'        => $programId,
            'subject_id'        => $programId === $this->programMath ? $this->subjectMath : $this->subjectEnglish,
            'teacher_id'        => $teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp 1-1',
            'price_per_session' => 500000,
            'schedules'         => array(
                array('day' => $day, 'start' => '19:00', 'end' => '20:30'),
            ),
            'student_ids'       => array($studentId),
        ));
    }

    // ===================== enroll =====================

    public function testEnrollStudentCreatesActiveRowAndSyncsSessions()
    {
        $classId = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student2), 2);
        $this->assertGreaterThan(0, $classId);

        $this->assertTrue($this->svc->enrollStudent($classId, $this->student1));
        $this->assertSame(array(), $this->svc->errors());

        $row = $this->db->safeFetchOne(
            'select status from db_lms_class_students where class_id = ? and student_id = ? limit 1',
            array($classId, $this->student1)
        );
        $this->assertSame('active', (string) $row['status']);

        // session_students được thêm cho mọi session upcoming của lớp
        $sessions = (int) $this->db->count(
            'db_lms_sessions',
            'class_id = ? and status = ?',
            array($classId, 'upcoming')
        );
        $ss = (int) $this->db->count(
            'db_lms_session_students',
            'student_id = ? and session_id in (select id from db_lms_sessions where class_id = ?)',
            array($this->student1, $classId)
        );
        $this->assertGreaterThan(0, $sessions);
        $this->assertSame($sessions, $ss);
    }

    public function testEnrollRejectsActiveEnrolment()
    {
        $classId = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);

        $this->assertFalse($this->svc->enrollStudent($classId, $this->student1));
        $this->assertSame('Học viên đang học lớp này.', $this->svc->errors()['student_id']);
    }

    public function testEnrollRejectsFullOneOnOneClass()
    {
        $classId = $this->createOneOnOneClass($this->programMath, $this->teacher1, $this->student2, 4);

        $this->assertFalse($this->svc->enrollStudent($classId, $this->student1));
        $this->assertSame('Lớp 1 kèm 1 chỉ có 1 học viên.', $this->svc->errors()['class_id']);
    }

    public function testEnrollRejectsPausedClass()
    {
        $classId = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student2), 2);
        $this->assertTrue($this->classSvc->setClassStatus($classId, 'paused'));

        $this->assertFalse($this->svc->enrollStudent($classId, $this->student1));
        $this->assertSame('Lớp không hoạt động.', $this->svc->errors()['class_id']);
    }

    public function testEnrollRejectsDeletedClass()
    {
        $classId = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student2), 2);
        $this->assertTrue($this->classSvc->deleteClass($classId));

        $this->assertFalse($this->svc->enrollStudent($classId, $this->student1));
        $this->assertSame('Lớp không hoạt động.', $this->svc->errors()['class_id']);
    }

    public function testEnrollRejectsQuitStudent()
    {
        $classId = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student2), 2);

        $this->assertFalse($this->svc->enrollStudent($classId, $this->studentQuit));
        $this->assertSame('Học viên đã nghỉ học.', $this->svc->errors()['student_id']);
    }

    public function testEnrollFlipsTransferredOutRow()
    {
        $classA = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);
        $classB = $this->createGroupClass($this->programEnglish, $this->teacher2, array($this->student2), 3);
        $this->assertTrue($this->svc->transferStudent($this->student1, $classA, $classB));

        // Row cũ ở A đang transferred_out → enrol lại flip active (không insert mới)
        $before = $this->db->safeFetchOne(
            'select id, status from db_lms_class_students where class_id = ? and student_id = ? limit 1',
            array($classA, $this->student1)
        );
        $this->assertSame('transferred_out', (string) $before['status']);

        $this->assertTrue($this->svc->enrollStudent($classA, $this->student1));

        $after = $this->db->safeFetchOne(
            'select id, status from db_lms_class_students where class_id = ? and student_id = ? limit 1',
            array($classA, $this->student1)
        );
        $this->assertSame('active', (string) $after['status']);
        $this->assertSame((int) $before['id'], (int) $after['id']);
    }

    // ===================== transfer =====================

    public function testTransferStudentMovesEnrolmentAndSyncsSessions()
    {
        $classA = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);
        $classB = $this->createGroupClass($this->programEnglish, $this->teacher2, array($this->student2), 3);

        $this->assertTrue($this->svc->transferStudent($this->student1, $classA, $classB));
        $this->assertSame(array(), $this->svc->errors());

        $rowA = $this->db->safeFetchOne(
            'select status from db_lms_class_students where class_id = ? and student_id = ? limit 1',
            array($classA, $this->student1)
        );
        $this->assertSame('transferred_out', (string) $rowA['status']);

        $rowB = $this->db->safeFetchOne(
            'select status from db_lms_class_students where class_id = ? and student_id = ? limit 1',
            array($classB, $this->student1)
        );
        $this->assertSame('active', (string) $rowB['status']);

        // Session upcoming của A: session_students của HV bị xoá
        $ssA = (int) $this->db->count(
            'db_lms_session_students',
            'student_id = ? and session_id in (select id from db_lms_sessions where class_id = ? and status = ?)',
            array($this->student1, $classA, 'upcoming')
        );
        $this->assertSame(0, $ssA);

        // Session upcoming của B: HV được thêm vào đủ
        $sessionsB = (int) $this->db->count(
            'db_lms_sessions',
            'class_id = ? and status = ?',
            array($classB, 'upcoming')
        );
        $ssB = (int) $this->db->count(
            'db_lms_session_students',
            'student_id = ? and session_id in (select id from db_lms_sessions where class_id = ? and status = ?)',
            array($this->student1, $classB, 'upcoming')
        );
        $this->assertGreaterThan(0, $sessionsB);
        $this->assertSame($sessionsB, $ssB);
    }

    public function testTransferRejectsActiveInTargetClass()
    {
        $classA = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);
        $classB = $this->createGroupClass($this->programEnglish, $this->teacher2, array($this->student1, $this->student2), 3);

        $this->assertFalse($this->svc->transferStudent($this->student1, $classA, $classB));
        $this->assertSame('Học viên đang học lớp này.', $this->svc->errors()['to_class_id']);

        // Không có gì thay đổi
        $rowA = $this->db->safeFetchOne(
            'select status from db_lms_class_students where class_id = ? and student_id = ? limit 1',
            array($classA, $this->student1)
        );
        $this->assertSame('active', (string) $rowA['status']);
    }

    public function testTransferRejectsSameClass()
    {
        $classA = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);

        $this->assertFalse($this->svc->transferStudent($this->student1, $classA, $classA));
        $this->assertSame('Vui lòng chọn lớp khác lớp hiện tại.', $this->svc->errors()['to_class_id']);
    }

    public function testTransferRejectsFullOneOnOneTarget()
    {
        $classA = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);
        $classB = $this->createOneOnOneClass($this->programEnglish, $this->teacher2, $this->student2, 4);

        $this->assertFalse($this->svc->transferStudent($this->student1, $classA, $classB));
        $this->assertSame('Lớp 1 kèm 1 chỉ có 1 học viên.', $this->svc->errors()['to_class_id']);
    }

    public function testTransferRejectsInactiveSourceEnrolment()
    {
        $classA = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student2), 2);
        $classB = $this->createGroupClass($this->programEnglish, $this->teacher2, array($this->student2), 3);

        // student1 không ở lớp A
        $this->assertFalse($this->svc->transferStudent($this->student1, $classA, $classB));
        $this->assertSame('Học viên không có trong lớp này.', $this->svc->errors()['from_class_id']);
    }

    public function testTransferKeepsSessionStudentsWithData()
    {
        $classA = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);
        $classB = $this->createGroupClass($this->programEnglish, $this->teacher2, array($this->student2), 3);

        // Session upcoming của A đã nộp bài → giữ lại sau transfer
        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $classA,
            'teacher_id'   => $this->teacher1,
            'session_date' => date('Y-m-d', strtotime('+1 day')),
            // Avoid the class's generated 19:00 recurring slot so this test
            // stays deterministic regardless of the current weekday.
            'start_time'   => '19:15',
            'end_time'     => '20:45',
            'status'       => 'upcoming',
        ));
        $sessionId = (int) $this->db->insertId;
        $this->db->insert('db_lms_session_students', array(
            'session_id'      => $sessionId,
            'student_id'      => $this->student1,
            'status'          => 'submitted',
            'hw_submitted_at' => date('Y-m-d H:i:s'),
        ));

        $this->assertTrue($this->svc->transferStudent($this->student1, $classA, $classB));

        $kept = (int) $this->db->count(
            'db_lms_session_students',
            'session_id = ? and student_id = ?',
            array($sessionId, $this->student1)
        );
        $this->assertSame(1, $kept);
    }

    // ===================== unenroll =====================

    public function testRemoveStudentFromClassDeletesRowAndEmptySessions()
    {
        $classId = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);

        $this->assertTrue($this->svc->removeStudentFromClass($classId, $this->student1));

        $this->assertSame(0, (int) $this->db->count(
            'db_lms_class_students',
            'class_id = ? and student_id = ?',
            array($classId, $this->student1)
        ));
        $this->assertSame(0, (int) $this->db->count(
            'db_lms_session_students',
            'student_id = ? and session_id in (select id from db_lms_sessions where class_id = ? and status = ?)',
            array($this->student1, $classId, 'upcoming')
        ));
    }

    public function testRemoveKeepsSessionStudentsWithData()
    {
        $classId = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);

        $this->db->insert('db_lms_sessions', array(
            'class_id'     => $classId,
            'teacher_id'   => $this->teacher1,
            'session_date' => date('Y-m-d', strtotime('+2 days')),
            // Avoid the class schedule's generated recurring slot.
            'start_time'   => '19:15',
            'end_time'     => '20:45',
            'status'       => 'upcoming',
        ));
        $sessionId = (int) $this->db->insertId;
        $this->db->insert('db_lms_session_students', array(
            'session_id'      => $sessionId,
            'student_id'      => $this->student1,
            'status'          => 'graded',
            'hw_submitted_at' => date('Y-m-d H:i:s'),
            'grade'           => 8.5,
        ));

        $this->assertTrue($this->svc->removeStudentFromClass($classId, $this->student1));

        $kept = (int) $this->db->count(
            'db_lms_session_students',
            'session_id = ? and student_id = ?',
            array($sessionId, $this->student1)
        );
        $this->assertSame(1, $kept);
    }

    public function testRemoveRejectsStudentNotInClass()
    {
        $classId = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student2), 2);

        $this->assertFalse($this->svc->removeStudentFromClass($classId, $this->student1));
        $this->assertSame('Học viên không có trong lớp này.', $this->svc->errors()['student_id']);
    }

    // ===================== listMatrix =====================

    public function testListMatrixDynamicProgramColumns()
    {
        $c1 = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1, $this->student2), 2);
        $c2 = $this->createGroupClass($this->programEnglish, $this->teacher2, array($this->student1), 3);

        $res = $this->svc->listMatrix(array(), 1, 20);

        // 2 cột chương trình (sắp xếp theo tên)
        $this->assertCount(2, $res['programs']);
        $programNames = array();
        foreach ($res['programs'] as $p) {
            $programNames[(int) $p['id']] = (string) $p['name'];
        }
        $this->assertSame('Toán', $programNames[$this->programMath]);
        $this->assertSame('Tiếng Anh', $programNames[$this->programEnglish]);

        // 2 HV (mỗi HV ≥1 enrolment active của tab group)
        $this->assertSame(2, (int) $res['total']);
        $this->assertCount(2, $res['items']);

        $byCode = array();
        foreach ($res['items'] as $it) {
            $byCode[(string) $it['student_code']] = $it;
        }
        $this->assertArrayHasKey('ST001', $byCode);
        $this->assertArrayHasKey('ST002', $byCode);

        // HV Một: 1 lớp Toán + 1 lớp Tiếng Anh
        $this->assertCount(1, $byCode['ST001']['classes'][$this->programMath]);
        $this->assertCount(1, $byCode['ST001']['classes'][$this->programEnglish]);
        $this->assertSame((int) $c1, (int) $byCode['ST001']['classes'][$this->programMath][0]['class_id']);
        $this->assertSame((int) $c2, (int) $byCode['ST001']['classes'][$this->programEnglish][0]['class_id']);
        $this->assertSame('Lớp 5', (string) $byCode['ST001']['grade']);

        // HV Hai: chỉ có lớp Toán
        $this->assertCount(1, $byCode['ST002']['classes'][$this->programMath]);
        $this->assertArrayNotHasKey($this->programEnglish, $byCode['ST002']['classes']);
    }

    public function testListMatrixStudentWithTwoClassesSameProgram()
    {
        $c1 = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);
        $c2 = $this->createGroupClass($this->programMath, $this->teacher2, array($this->student1), 3);

        $res = $this->svc->listMatrix(array(), 1, 20);
        $this->assertSame(1, (int) $res['total']);
        $this->assertCount(2, $res['items'][0]['classes'][$this->programMath]);
    }

    public function testListMatrixFilters()
    {
        $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1, $this->student2), 2);
        $this->createGroupClass($this->programEnglish, $this->teacher2, array($this->student1), 3);

        // Theo HV
        $res = $this->svc->listMatrix(array('q_student' => 'Một'), 1, 20);
        $this->assertSame(1, (int) $res['total']);
        $this->assertSame('HV Một', (string) $res['items'][0]['full_name']);

        // Theo mã HV
        $res = $this->svc->listMatrix(array('q_student' => 'ST002'), 1, 20);
        $this->assertSame(1, (int) $res['total']);

        // Theo lớp: mã lớp Toán (class_code bắt đầu CLSGR)
        $res = $this->svc->listMatrix(array('q_class' => 'CLSGR'), 1, 20);
        $this->assertSame(2, (int) $res['total']);

        // Theo tên lớp không khớp
        $res = $this->svc->listMatrix(array('q_class' => 'KhôngTồnTại'), 1, 20);
        $this->assertSame(0, (int) $res['total']);
    }

    public function testListMatrixOneOnOneTab()
    {
        $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);
        $this->createOneOnOneClass($this->programEnglish, $this->teacher2, $this->student2, 4);

        $res = $this->svc->listMatrix(array('class_type' => 'one_on_one'), 1, 20);
        $this->assertSame(1, (int) $res['total']);
        $this->assertSame('HV Hai', (string) $res['items'][0]['full_name']);
        $this->assertCount(1, $res['programs']);
        $this->assertSame('Tiếng Anh', (string) $res['programs'][0]['name']);
    }
    public function testListMatrixExcludesTransferredOut()
    {
        $classA = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);
        $classB = $this->createGroupClass($this->programEnglish, $this->teacher2, array($this->student2), 3);
        $this->assertTrue($this->svc->transferStudent($this->student1, $classA, $classB));

        // Tab group: HV Một vẫn còn (đang học B), nhưng không còn lớp Toán
        $res = $this->svc->listMatrix(array(), 1, 20);
        $this->assertSame(2, (int) $res['total']);
        foreach ($res['items'] as $it) {
            if ((int) $it['user_id'] === $this->student1) {
                $this->assertArrayNotHasKey($this->programMath, $it['classes']);
                $this->assertCount(1, $it['classes'][$this->programEnglish]);
            }
        }
    }

    // ===================== studentClassOptions =====================

    public function testStudentClassOptionsExcludesActiveAndExcludedClass()
    {
        $c1 = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student1), 2);
        $c2 = $this->createGroupClass($this->programEnglish, $this->teacher2, array($this->student1), 3);
        $c3 = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student2), 5);
        $this->assertGreaterThan(0, $c3);

        $options = $this->svc->studentClassOptions($this->student1, 'group');
        $ids = array();
        foreach ($options as $o) {
            $ids[] = (int) $o['id'];
            $this->assertNotSame('', (string) $o['class_code']);
        }
        // c1, c2 đang active → loại; chỉ còn c3
        $this->assertSame(array($c3), $ids);

        // Exclude class khi chuyển
        $options = $this->svc->studentClassOptions($this->student1, 'group', $c3);
        $this->assertSame(array(), $options);
    }

    // ===================== Audit =====================

    public function testAuditLogPerAction()
    {
        $classA = $this->createGroupClass($this->programMath, $this->teacher1, array($this->student2), 2);
        $classB = $this->createGroupClass($this->programEnglish, $this->teacher2, array($this->student2), 3);

        $base = (int) $this->db->count('db_lms_audit_logs', '1=1');

        $this->assertTrue($this->svc->enrollStudent($classA, $this->student1));
        $this->assertSame(1, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('enroll_student')));

        $this->assertTrue($this->svc->transferStudent($this->student1, $classA, $classB));
        $this->assertSame(1, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('transfer_student')));

        $this->assertTrue($this->svc->removeStudentFromClass($classB, $this->student1));
        $this->assertSame(1, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('unenroll_student')));

        // 3 audit rows mới nói chung
        $this->assertSame(3, (int) $this->db->count('db_lms_audit_logs', '1=1') - $base);
    }
}
