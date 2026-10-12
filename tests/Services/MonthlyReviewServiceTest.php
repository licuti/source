<?php
/**
 * Tests cho MonthlyReviewService — portal Student (STU-04).
 *
 * Row-level qua db_lms_monthly_reviews.student_id; chỉ trả nhận xét đã published
 * (published_at is not null) của các lớp HV đang học; filter theo tháng.
 */

namespace Tests\Services;

use Lms\ClassService;
use Lms\MonthlyReviewService;
use Lms\ScheduleService;
use Tests\LmsTestCase;

class MonthlyReviewServiceTest extends LmsTestCase
{
    /** @var MonthlyReviewService */
    private $svc;

    /** @var int */
    private $classId;

    /** @var int */
    private $teacherId;

    /** @var int */
    private $studentId;

    /** @var int */
    private $otherStudentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = new MonthlyReviewService($this->db);

        $this->db->insert('db_lms_programs', array('name' => 'Chương trình review'));
        $programId = (int) $this->db->insertId;
        $this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Môn review'));
        $subjectId = (int) $this->db->insertId;

        $userSvc = new \Lms\UserService($this->db);
        $this->teacherId = $userSvc->createUser('gv_rev', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->teacherId,
            'teacher_code' => 'TER01',
            'full_name'    => 'GV Review',
            'status'       => 'active',
            'max_classes'  => 10,
        ));

        $this->studentId = $userSvc->createUser('hv_rev', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $this->studentId,
            'student_code' => 'STR01',
            'full_name'    => 'HV Review',
            'status'       => 'studying',
        ));
        $this->otherStudentId = $userSvc->createUser('hv_rev2', 'matkhau123', 'student');
        $this->db->insert('db_lms_students', array(
            'user_id'      => $this->otherStudentId,
            'student_code' => 'STR02',
            'full_name'    => 'HV Review 2',
            'status'       => 'studying',
        ));

        $schedSvc = new ScheduleService($this->db);
        $classSvc = new ClassService($this->db, $schedSvc);

        $this->classId = $classSvc->createClass(array(
            'program_id'        => $programId,
            'subject_id'        => $subjectId,
            'teacher_id'        => $this->teacherId,
            'class_type'        => 'one_on_one',
            'name'              => 'Lớp review',
            'price_per_session' => 500000,
            'total_sessions'    => 16,
            'student_ids'       => array($this->studentId),
            'schedules'         => array(
                array('day' => 2, 'start' => '19:00', 'end' => '20:30'),
            ),
            'start_date'        => date('Y-m-d', strtotime('monday this week')),
        ));
    }

    /**
     * Tạo nhận xét tháng.
     *
     * @param string      $reviewMonth 'YYYY-MM'
     * @param string|null $publishedAt null = chưa publish
     * @return int
     */
    private function makeReview($reviewMonth, $publishedAt)
    {
        $this->db->insert('db_lms_monthly_reviews', array(
            'student_id'      => $this->studentId,
            'class_id'        => $this->classId,
            'teacher_id'      => $this->teacherId,
            'review_month'    => (string) $reviewMonth,
            'overall_rating'  => 'good',
            'content'         => 'Nội dung nhận xét ' . $reviewMonth,
            'progress_percent'=> 75.00,
            'published_at'    => $publishedAt,
        ));
        return (int) $this->db->insertId;
    }

    public function testListForStudentReturnsOnlyPublished()
    {
        $this->makeReview('2026-08', date('Y-m-d H:i:s'));
        $this->makeReview('2026-09', null); // chưa publish → bị ẩn

        $data = $this->svc->listForStudent($this->studentId);
        $this->assertCount(1, $data['items']);
        $this->assertCount(1, $data['items'][0]['reviews']);
        $this->assertSame('2026-08', (string) $data['items'][0]['reviews'][0]['review_month']);
        $this->assertSame(array('2026-08' => 'Tháng 8 / 2026'), $data['months']);
    }

    public function testListForStudentFiltersByMonth()
    {
        $this->makeReview('2026-08', date('Y-m-d H:i:s'));
        $this->makeReview('2026-09', date('Y-m-d H:i:s'));

        $data = $this->svc->listForStudent($this->studentId, '2026-08');
        $this->assertCount(1, $data['items']);
        $this->assertCount(1, $data['items'][0]['reviews']);
        $this->assertSame('2026-08', (string) $data['items'][0]['reviews'][0]['review_month']);
        // months liệt kê tháng có trong kết quả đã filter
        $this->assertSame(array('2026-08' => 'Tháng 8 / 2026'), $data['months']);
    }

    public function testListForStudentRowLevel()
    {
        $this->makeReview('2026-08', date('Y-m-d H:i:s'));

        // HV khác không enrol lớp này → không có items
        $data = $this->svc->listForStudent($this->otherStudentId);
        $this->assertSame(array('items' => array(), 'months' => array()), $data);
    }

    public function testListForStudentShowsClassCardEvenWithoutReviews()
    {
        $data = $this->svc->listForStudent($this->studentId);
        $this->assertCount(1, $data['items']);
        $this->assertSame(array(), $data['items'][0]['reviews']);
        $this->assertSame(array(), $data['months']);
    }

    public function testListForStudentIncludesScheduleText()
    {
        $data = $this->svc->listForStudent($this->studentId);
        $this->assertStringContainsString('19:00-20:30', (string) $data['items'][0]['schedules_text']);
    }

    public function testMonthLabel()
    {
        $this->assertSame('Tháng 7 / 2026', $this->svc->monthLabel('2026-07'));
    }

    public function testTeacherCanSaveDraftAndPublishOnlyOwnClassReview()
    {
        $data = array(
            'class_id' => $this->classId,
            'student_id' => $this->studentId,
            'review_month' => '2026-09',
            'overall_rating' => 'good',
            'content' => 'Tiến bộ tốt trong tháng.',
            'progress_percent' => '75',
        );
        $this->assertTrue($this->svc->saveForTeacher($this->teacherId, $data, false));
        $rows = $this->svc->listForTeacher($this->teacherId, '2026-09');
        $this->assertNotEmpty($rows);
        $this->assertNull($rows[0]['published_at']);

        $this->assertTrue($this->svc->saveForTeacher($this->teacherId, $data, true));
        $rows = $this->svc->listForTeacher($this->teacherId, '2026-09');
        $this->assertNotEmpty($rows[0]['published_at']);
        $this->assertSame('Tiến bộ tốt trong tháng.', $rows[0]['content']);
    }

    public function testTeacherCannotReviewStudentOutsideOwnClass()
    {
        $this->assertFalse($this->svc->saveForTeacher($this->teacherId, array(
            'class_id' => $this->classId,
            'student_id' => $this->otherStudentId,
            'review_month' => '2026-09',
            'overall_rating' => 'good',
            'content' => 'Should be rejected',
            'progress_percent' => 50,
        ), true));
        $this->assertArrayHasKey('system', $this->svc->errors());
    }
}
