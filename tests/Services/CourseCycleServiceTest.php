<?php
namespace Tests\Services;

use Lms\ClassService;
use Lms\CourseCycleService;
use Lms\SessionService;
use Tests\LmsTestCase;

class CourseCycleServiceTest extends LmsTestCase
{
    private $programId;
    private $subjectId;
    private $teacherId;
    private $studentId;
    private $classId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db->insert('db_lms_programs', array('name' => 'Cycle program'));
        $this->programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $this->programId, 'name' => 'Cycle subject'));
        $this->subjectId = (int) $this->db->insertId;
        $this->db->insert('db_lms_topics', array('subject_id' => $this->subjectId, 'name' => 'Cycle topic 1', 'max_sessions' => 1, 'order_index' => 1));
        $topic1 = (int) $this->db->insertId;
        $this->db->insert('db_lms_topics', array('subject_id' => $this->subjectId, 'name' => 'Cycle topic 2', 'max_sessions' => 1, 'order_index' => 2));
        $topic2 = (int) $this->db->insertId;
        $users = new \Lms\UserService($this->db);
        $this->teacherId = $users->createUser('cycle_teacher', 'password123', 'teacher');
        $this->db->insert('db_lms_teachers', array('user_id' => $this->teacherId, 'teacher_code' => 'CYC01', 'full_name' => 'Cycle Teacher', 'status' => 'active', 'max_classes' => 10));
        $this->studentId = $users->createUser('cycle_student', 'password123', 'student');
        $this->db->insert('db_lms_students', array('user_id' => $this->studentId, 'student_code' => 'CYC01', 'full_name' => 'Cycle Student', 'status' => 'studying'));
        $this->classId = (new ClassService($this->db))->createClass(array(
            'program_id' => $this->programId, 'subject_id' => $this->subjectId, 'teacher_id' => $this->teacherId,
            'class_type' => 'one_on_one', 'name' => 'Cycle class', 'total_sessions' => 2,
            'student_ids' => array($this->studentId), 'topic_ids' => array($topic1, $topic2),
        ));
        $this->assertGreaterThan(0, $this->classId);
    }

    public function testInitialCycleSeedsFirstTopicOnlyUnlocked()
    {
        $cycle = $this->db->safeFetchOne('select id from db_lms_course_cycles where class_id = ?', array($this->classId));
        $rows = $this->db->safeFetch('select status from db_lms_cycle_student_topics where cycle_id = ? and student_id = ? order by id', array($cycle['id'], $this->studentId));
        $this->assertSame(array('unlocked', 'locked'), array_column($rows, 'status'));
    }

    public function testDemoDatabaseWithoutTopicsCreatesNoUnlocksUntilCurriculumExists()
    {
        $this->db->insert('db_lms_subjects', array('program_id' => $this->programId, 'name' => 'Empty curriculum subject'));
        $emptySubjectId = (int) $this->db->insertId;
        $emptyClassId = (new ClassService($this->db))->createClass(array(
            'program_id' => $this->programId,
            'subject_id' => $emptySubjectId,
            'teacher_id' => $this->teacherId,
            'class_type' => 'one_on_one',
            'name' => 'Class awaiting curriculum',
            'total_sessions' => 1,
            'student_ids' => array($this->studentId),
        ));
        $this->assertGreaterThan(0, $emptyClassId);
        $cycleSvc = new CourseCycleService($this->db);
        $cycleId = $cycleSvc->resolveActiveCycleId($emptyClassId);
        $this->assertSame(0, (int) $this->db->count('db_lms_cycle_topics', 'cycle_id = ?', array($cycleId)));
        $this->assertSame(0, (int) $this->db->count('db_lms_cycle_student_topics', 'cycle_id = ?', array($cycleId)));

        $this->db->insert('db_lms_topics', array(
            'subject_id' => $emptySubjectId,
            'name' => 'Temporary curriculum topic 1',
            'max_sessions' => 1,
            'order_index' => 1,
        ));
        $topicId = (int) $this->db->insertId;
        $this->db->insert('db_lms_cycle_topics', array(
            'cycle_id' => $cycleId, 'topic_id' => $topicId,
            'order_index' => 1, 'max_sessions' => 1,
        ));
        $this->db->insert('db_lms_cycle_student_topics', array(
            'cycle_id' => $cycleId, 'student_id' => $this->studentId, 'topic_id' => $topicId,
            'status' => 'unlocked', 'unlocked_at' => date('Y-m-d H:i:s'), 'unlock_reason' => 'smoke fixture',
        ));
        $courseSvc = new \Lms\StudentCourseService($this->db);
        $data = $courseSvc->listTopicsForClass($emptyClassId, $this->studentId);
        $this->assertCount(1, $data['topics']);
        $this->assertSame($topicId, (int) $data['topics'][0]['id']);
        $this->assertSame('unlocked', $data['topics'][0]['unlock_status']);
    }

    public function testCannotCreateSecondCycleUntilCurrentClosed()
    {
        $svc = new CourseCycleService($this->db);
        $id = $svc->createCycleFromClass($this->classId, array('start_date' => date('Y-m-d'), 'total_sessions' => 2));
        $this->assertSame(0, $id);
        $this->assertArrayHasKey('cycle', $svc->errors());
    }

    public function testCloseThenCreateNextCycleKeepsSnapshotsSeparate()
    {
        $svc = new CourseCycleService($this->db);
        $cycle1 = $svc->resolveActiveCycleId($this->classId);
        $this->assertTrue($svc->closeCycle($cycle1));
        $cycle2 = $svc->createCycleFromClass($this->classId, array('start_date' => date('Y-m-d'), 'total_sessions' => 2));
        $this->assertGreaterThan(0, $cycle2);
        $this->assertNotSame((int) $cycle1, (int) $cycle2);
        $this->assertSame(1, (int) $this->db->count('db_lms_cycle_students', 'cycle_id = ?', array($cycle2)));
    }

    public function testFinishedCycleSessionConsumesAllowanceOnlyOnce()
    {
        $cycleId = (new CourseCycleService($this->db))->resolveActiveCycleId($this->classId);
        $this->db->insert('db_lms_sessions', array(
            'class_id' => $this->classId,
            'cycle_id' => $cycleId,
            'teacher_id' => $this->teacherId,
            'session_date' => date('Y-m-d'),
            'start_time' => '19:00',
            'end_time' => '20:00',
            'status' => 'finished',
        ));
        $sessionId = (int) $this->db->insertId;
        $svc = new CourseCycleService($this->db);
        $this->assertSame(1, $svc->recordFinishedSessions(array($sessionId)));
        $this->assertSame(0, $svc->recordFinishedSessions(array($sessionId)));
        $cycle = $this->db->safeFetchOne('select remaining_sessions from db_lms_course_cycles where id = ?', array($cycleId));
        $this->assertSame(1, (int) $cycle['remaining_sessions']);
    }

    public function testFinishedSessionClosesCycleWhenAllowanceReachesZero()
    {
        $cycleId = (new CourseCycleService($this->db))->resolveActiveCycleId($this->classId);
        $this->db->update('db_lms_course_cycles', array('total_sessions' => 1, 'remaining_sessions' => 1), 'id = ?', array($cycleId));
        $this->db->insert('db_lms_sessions', array(
            'class_id' => $this->classId,
            'cycle_id' => $cycleId,
            'teacher_id' => $this->teacherId,
            'session_date' => date('Y-m-d'),
            'start_time' => '20:00',
            'end_time' => '21:00',
            'status' => 'finished',
        ));
        (new CourseCycleService($this->db))->recordFinishedSessions(array((int) $this->db->insertId));
        $cycle = $this->db->safeFetchOne('select status, remaining_sessions from db_lms_course_cycles where id = ?', array($cycleId));
        $this->assertSame('completed', (string) $cycle['status']);
        $this->assertSame(0, (int) $cycle['remaining_sessions']);
    }

    public function testCycleSessionGenerationRespectsTotalSessionLimit()
    {
        $cycleId = (new CourseCycleService($this->db))->resolveActiveCycleId($this->classId);
        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+8 weeks', strtotime($start)));
        $svc = new SessionService($this->db);
        $created = $svc->generateForCycle($this->classId, $cycleId, $this->teacherId, $start, $end);
        $this->assertLessThanOrEqual(2, $created);
        $this->assertSame($created, (int) $this->db->count('db_lms_sessions', 'cycle_id = ?', array($cycleId)));
    }

    public function testEnrollStudentInActiveCycleIsIdempotent()
    {
        $svc = new CourseCycleService($this->db);
        $this->assertTrue($svc->enrollStudentInActiveCycle($this->classId, $this->studentId));
        $cycleId = $svc->resolveActiveCycleId($this->classId);
        $this->assertSame(1, (int) $this->db->count('db_lms_cycle_students', 'cycle_id = ? and student_id = ?', array($cycleId, $this->studentId)));
        $this->assertSame(2, (int) $this->db->count('db_lms_cycle_student_topics', 'cycle_id = ? and student_id = ?', array($cycleId, $this->studentId)));
    }

    public function testTeacherUnlocksNextTopicOnlyAfterRequiredFinishedSessions()
    {
        $svc = new CourseCycleService($this->db);
        $cycleId = $svc->resolveActiveCycleId($this->classId);
        $this->assertFalse($svc->teacherUnlockNextTopic($this->classId, $cycleId, $this->studentId, $this->teacherId));

        $this->db->insert('db_lms_sessions', array(
            'class_id' => $this->classId, 'cycle_id' => $cycleId, 'teacher_id' => $this->teacherId,
            'session_date' => date('Y-m-d'), 'start_time' => '17:00', 'end_time' => '18:00',
            'status' => 'finished', 'topic_id' => (int) $this->db->safeFetchOne(
                'select topic_id from db_lms_cycle_topics where cycle_id = ? order by order_index limit 1', array($cycleId)
            )['topic_id'],
        ));
        $this->assertTrue($svc->teacherUnlockNextTopic($this->classId, $cycleId, $this->studentId, $this->teacherId));
        $states = $this->db->safeFetch(
            'select st.status from db_lms_cycle_student_topics st join db_lms_cycle_topics ct'
            . ' on ct.cycle_id = st.cycle_id and ct.topic_id = st.topic_id'
            . ' where st.cycle_id = ? and st.student_id = ? order by ct.order_index',
            array($cycleId, $this->studentId)
        );
        $this->assertSame(array('unlocked', 'unlocked'), array_column($states, 'status'));
    }

    public function testClosedCycleDataIsNotResolvedAsActiveStudentClass()
    {
        $svc = new CourseCycleService($this->db);
        $cycle1 = $svc->resolveActiveCycleId($this->classId);
        $this->assertTrue($svc->closeCycle($cycle1));
        $studentCourse = new \Lms\StudentCourseService($this->db);
        $this->assertSame(array(), $studentCourse->listMyClasses($this->studentId));
    }

    public function testCycleMigrationBackfillStatementsAreIdempotentForLegacyClass()
    {
        $this->db->safeExec('delete from db_lms_course_cycles where class_id = ?', array($this->classId));
        $this->db->insert('db_lms_sessions', array(
            'class_id' => $this->classId, 'teacher_id' => $this->teacherId,
            'session_date' => date('Y-m-d', strtotime('-1 day')), 'start_time' => '17:00',
            'end_time' => '18:00', 'status' => 'finished',
        ));

        $this->db->safeExec(
            'insert into db_lms_course_cycles (class_id, cycle_no, name, status, teacher_id, price_per_session, total_sessions, remaining_sessions)'
            . ' select c.id, 1, ?, ?, c.teacher_id, c.price_per_session, c.total_sessions, c.remaining_sessions'
            . ' from db_lms_classes c left join db_lms_course_cycles cc on cc.class_id=c.id and cc.cycle_no=1'
            . ' where c.id=? and cc.id is null',
            array('Khóa 1', 'active', $this->classId)
        );
        $cycle = $this->db->safeFetchOne('select id, remaining_sessions from db_lms_course_cycles where class_id = ?', array($this->classId));
        $this->assertNotEmpty($cycle);
        $cycleId = (int) $cycle['id'];
        $this->db->safeExec('update db_lms_sessions set cycle_id = ? where class_id = ? and cycle_id is null', array($cycleId, $this->classId));
        $this->db->safeExec(
            'insert ignore into db_lms_cycle_session_completions (session_id, cycle_id)'
            . ' select id, cycle_id from db_lms_sessions where class_id=? and cycle_id=? and status=?',
            array($this->classId, $cycleId, 'finished')
        );
        $this->db->safeExec('update db_lms_course_cycles set remaining_sessions=GREATEST(0,total_sessions-(select count(*) from db_lms_cycle_session_completions x where x.cycle_id=db_lms_course_cycles.id)) where id=?', array($cycleId));
        $this->db->safeExec('update db_lms_sessions set cycle_id = ? where class_id = ? and cycle_id is null', array($cycleId, $this->classId));
        $this->db->safeExec(
            'insert ignore into db_lms_cycle_session_completions (session_id, cycle_id)'
            . ' select id, cycle_id from db_lms_sessions where class_id=? and cycle_id=? and status=?',
            array($this->classId, $cycleId, 'finished')
        );
        $cycle = $this->db->safeFetchOne('select remaining_sessions from db_lms_course_cycles where id = ?', array($cycleId));
        $this->assertSame(1, (int) $this->db->count('db_lms_course_cycles', 'class_id = ?', array($this->classId)));
        $this->assertSame(1, (int) $this->db->count('db_lms_cycle_session_completions', 'cycle_id = ?', array($cycleId)));
        $this->assertSame(1, (int) $cycle['remaining_sessions']);
    }
}
