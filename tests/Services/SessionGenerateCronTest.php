<?php
/**
 * Tests cho cron lms_session_generate — sinh sessions + đồng bộ session_students.
 *
 * Cron chạy qua CLI (PHP_BINARY) trỏ vào DB test, nên test này chậm hơn test
 * service thông thường (mỗi test spawn 1 process PHP).
 */

namespace Tests\Services;

use Lms\ClassService;
use Lms\ScheduleService;
use Lms\SessionService;
use Tests\LmsTestCase;

class SessionGenerateCronTest extends LmsTestCase
{
    /** @var SessionService */
    private $svc;

    /** @var int */
    private $classId;

    /** @var int */
    private $teacherId;

    /** @var int */
    private $studentId;

    /** Đường dẫn tuyệt đối tới script cron. */
    private $cronPath;

    /** Tên DB test (cron cần --db=...). */
    private $testDb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cronPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database'
            . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'lms_session_generate.php';
        $this->testDb = getenv('LMS_TEST_DB');
        if (!$this->testDb) {
            $this->testDb = 'phuongnamv_db_lms_test';
        }

        $schedSvc = new ScheduleService($this->db);
        $classSvc = new ClassService($this->db, $schedSvc);
        $this->svc = new SessionService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'Chương trình cron'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Môn cron'));
        $subjectId = (int) $this->db->insertId;

        $userSvc = new \Lms\UserService($this->db);
        $this->teacherId = $userSvc->createUser('gv_cron', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->teacherId,
            'teacher_code' => 'TE001',
            'full_name'    => 'GV Cron',
            'status'       => 'active',
            'max_classes'  => 10,
        ));

        $this->studentId = $userSvc->createUser('hv_cron', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $this->studentId,
            'student_code' => 'ST001',
            'full_name'    => 'HV Cron',
            'status'       => 'studying',
        ));

        $this->classId = $classSvc->createClass(array(
            'program_id'        => $programId,
            'subject_id'        => $subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp cron',
            'price_per_session' => 500000,
            'total_sessions'    => 16,
            'hw_deadline_time'  => '12:00',
        ));
        $classSvc->enrollStudent($this->classId, $this->studentId);

        // Lịch cố định: thứ 2 + thứ 4 (createClass tự sinh 4 tuần, xoá để test
        // cron sinh từ đầu).
        $monday = date('Y-m-d', strtotime('monday this week'));
        $schedSvc->addSchedule($this->classId, 2, '17:30', '19:00', $monday);
        $schedSvc->addSchedule($this->classId, 4, '17:30', '19:00', $monday);
        $this->db->safeExec('delete from db_lms_sessions');
        $this->db->safeExec('delete from db_lms_session_students');
    }

    /**
     * Chạy cron qua CLI, trả array decode từ JSON output.
     *
     * @param array $args tham số thêm (VD array('--dry-run'))
     * @return array
     */
    private function runCron(array $args = array())
    {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($this->cronPath);
        foreach ($args as $a) {
            $cmd .= ' ' . escapeshellarg($a);
        }
        $cmd .= ' --db=' . escapeshellarg($this->testDb) . ' 2>&1';

        $out = shell_exec($cmd);
        $this->assertNotEmpty($out, 'cron phải in JSON');
        $json = json_decode((string) $out, true);
        $this->assertIsArray($json, 'output cron phải là JSON hợp lệ: ' . $out);
        return $json;
    }

    public function testDryRunDoesNotModifyDatabase()
    {
        $stats = $this->runCron(array('--dry-run'));

        $this->assertTrue($stats['dry_run']);
        $this->assertSame('ok', $stats['result']);
        $this->assertGreaterThan(0, (int) $stats['sessions_created'], 'phải thấy session sẽ sinh');
        $this->assertGreaterThan(0, (int) $stats['session_students_created'], 'dry-run phải đếm cả session_students ảo');

        // Dry-run không ghi DB
        $this->assertSame(0, (int) $this->db->count('db_lms_sessions'));
        $this->assertSame(
            0,
            (int) $this->db->count('db_lms_cron_runs', 'job_name = ?', array('session_generate')),
            'dry-run không được ghi cron_runs'
        );
    }

    public function testDryRunSessionStudentEstimateMatchesWriteRun()
    {
        $dry = $this->runCron(array('--dry-run'));
        $actual = $this->runCron();

        $this->assertSame(
            (int) $dry['sessions_created'],
            (int) $actual['sessions_created'],
            'dry-run phải dự đoán đúng số session mới'
        );
        $this->assertSame(
            (int) $dry['session_students_created'],
            (int) $actual['session_students_created'],
            'dry-run phải dự đoán đúng số session_students mới'
        );
    }

    public function testCreatesSessionsAndSessionStudents()
    {
        $stats = $this->runCron();

        $this->assertFalse($stats['dry_run']);
        $this->assertSame('ok', $stats['result']);
        $this->assertGreaterThan(0, (int) $stats['sessions_created']);

        $sessionCount = (int) $this->db->count('db_lms_sessions', 'class_id = ?', array($this->classId));
        $this->assertSame((int) $stats['sessions_created'], $sessionCount);

        // Mỗi session có đúng 1 session_students (1 HV enrolled)
        $ssRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_session_students ss'
            . ' join db_lms_sessions s on s.id = ss.session_id where s.class_id = ?',
            array($this->classId)
        );
        $ssCount = isset($ssRow['c']) ? (int) $ssRow['c'] : 0;
        $this->assertSame($sessionCount, $ssCount);

        // Toàn bộ session sinh ra là upcoming
        $rows = $this->db->safeFetch(
            'select status from db_lms_sessions where class_id = ?',
            array($this->classId)
        );
        foreach ($rows as $row) {
            $this->assertSame('upcoming', $row['status']);
        }
    }

    public function testIsIdempotent()
    {
        $first = $this->runCron();
        $second = $this->runCron();

        $this->assertGreaterThan(0, (int) $first['sessions_created']);
        $this->assertSame(0, (int) $second['sessions_created'], 'chạy lại không sinh thêm session');
        $this->assertSame(0, (int) $second['session_students_created']);

        $this->assertSame(
            (int) $first['sessions_created'],
            (int) $this->db->count('db_lms_sessions', 'class_id = ?', array($this->classId))
        );
    }

    public function testLogsCronRun()
    {
        $this->runCron();

        $row = $this->db->safeFetchOne(
            'select * from db_lms_cron_runs where job_name = ? order by id desc limit 1',
            array('session_generate')
        );
        $this->assertNotEmpty($row);
        $this->assertSame('success', $row['status']);
        $this->assertNotEmpty($row['finished_at']);
        $this->assertGreaterThan(0, (int) $row['rows_affected']);
    }

    public function testRespectsWeeksOption()
    {
        $stats = $this->runCron(array('--weeks=1'));
        $this->assertSame(1, (int) $stats['weeks']);

        // 1 tuần [hôm nay, +7 ngày] tối đa 3 buổi (VD thứ 2→thứ 4→thứ 2 kế)
        $count = (int) $this->db->count('db_lms_sessions', 'class_id = ?', array($this->classId));
        $this->assertLessThanOrEqual(3, $count);
    }

    public function testSkipsInactiveClasses()
    {
        $this->db->update(
            'db_lms_classes',
            array('status' => 'paused'),
            'id = ?',
            array($this->classId)
        );
        $this->db->safeExec('delete from db_lms_sessions');

        $stats = $this->runCron();
        $this->assertSame(0, (int) $stats['sessions_created']);
        $this->assertSame(
            0,
            (int) $this->db->count('db_lms_sessions', 'class_id = ?', array($this->classId))
        );
    }

    public function testNonCliRequestIsRejected()
    {
        // Cron guard: php_sapi_name() !== 'cli' → exit(1). Test này chạy qua CLI
        // nên không thể simulate web; verify guard tồn tại ở đầu file.
        $src = file_get_contents($this->cronPath);
        $this->assertStringContainsString("php_sapi_name() !== 'cli'", $src);
    }

    public function testSessionFixedSlotHasDatabaseUniqueConstraint()
    {
        $row = $this->db->safeFetchOne(
            'select count(*) as c from information_schema.statistics'
            . ' where table_schema = database() and table_name = "db_lms_sessions"'
            . ' and index_name = "uniq_session_fixed_slot"'
        );
        $this->assertGreaterThanOrEqual(3, (int) $row['c']);
    }

    public function testInvalidFromDateIsRejectedByCron()
    {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($this->cronPath)
            . ' --from=2026-02-30 --db=' . escapeshellarg($this->testDb) . ' 2>&1';
        $out = shell_exec($cmd);
        $this->assertStringContainsString('Invalid --from date', (string) $out);
    }
}
