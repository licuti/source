<?php
/**
 * Tests cho TeacherProfileService — hồ sơ GV + ghi chú tuần (§4.1/§4.4).
 */

namespace Tests\Services;

use Lms\TeacherProfileService;
use Tests\LmsTestCase;

class TeacherProfileServiceTest extends LmsTestCase
{
    /** @var TeacherProfileService */
    private $svc;

    /** @var int */
    private $teacherId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc = new TeacherProfileService($this->db);

        $userSvc = new \Lms\UserService($this->db);
        $this->teacherId = $userSvc->createUser('gv_prof', 'matkhau123', 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $this->teacherId,
            'teacher_code' => 'TEP01',
            'full_name'    => 'GV Profile',
            'status'       => 'active',
            'max_classes'  => 3,
        ));
    }

    public function testGetProfile()
    {
        $profile = $this->svc->getProfile($this->teacherId);
        $this->assertSame((string) $this->teacherId, (string) $profile['user_id']);
        $this->assertSame('GV Profile', $profile['full_name']);
        $this->assertSame(array(), $profile['teachable_districts']);
    }

    public function testUpdateProfileSuccess()
    {
        $ok = $this->svc->updateProfile($this->teacherId, array(
            'current_address'      => '123 Lê Lợi, Quận 1',
            'teachable_districts'  => array('Quận 1', 'Quận 3'),
            'can_teach_online'     => 1,
            'max_classes'          => 5,
        ));

        $this->assertTrue($ok);

        $row = $this->db->safeFetchOne('select * from db_lms_teachers where user_id = ?', array($this->teacherId));
        $this->assertSame('123 Lê Lợi, Quận 1', $row['current_address']);
        $this->assertSame('1', (string) $row['can_teach_online']);
        $this->assertSame('5', (string) $row['max_classes']);
        $this->assertSame(array('Quận 1', 'Quận 3'), json_decode((string) $row['teachable_districts'], true));

        $this->assertSame(1, (int) $this->db->count('db_lms_audit_logs', 'action = ?', array('update_teacher_profile')));
    }

    public function testUpdateProfileRejectsInvalidDistrict()
    {
        $ok = $this->svc->updateProfile($this->teacherId, array(
            'teachable_districts' => array('Hà Nội'),
        ));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('teachable_districts', $this->svc->errors());
    }

    public function testUpdateProfileRejectsNegativeMaxClasses()
    {
        $ok = $this->svc->updateProfile($this->teacherId, array(
            'max_classes' => -1,
        ));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('max_classes', $this->svc->errors());
    }

    public function testUpdateProfileRejectsTooLongAddress()
    {
        $ok = $this->svc->updateProfile($this->teacherId, array(
            'current_address' => str_repeat('a', 256),
        ));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('current_address', $this->svc->errors());
    }

    public function testWeeklyNoteUpsert()
    {
        $monday = date('Y-m-d', strtotime('monday this week'));

        $this->assertTrue($this->svc->upsertWeeklyNote($this->teacherId, $monday, 'Ghi chú tuần 1'));
        $this->assertSame(1, (int) $this->db->count('db_lms_teacher_notes', 'teacher_id = ?', array($this->teacherId)));

        // Upsert lại cùng tuần → vẫn 1 row (unique)
        $this->assertTrue($this->svc->upsertWeeklyNote($this->teacherId, $monday, 'Ghi chú tuần 1 (sửa)'));
        $this->assertSame(1, (int) $this->db->count('db_lms_teacher_notes', 'teacher_id = ?', array($this->teacherId)));

        $row = $this->svc->getWeeklyNote($this->teacherId, $monday);
        $this->assertSame('Ghi chú tuần 1 (sửa)', $row['note']);
    }

    public function testWeeklyNoteSeparateWeeks()
    {
        $monday1 = date('Y-m-d', strtotime('monday this week'));
        $monday2 = date('Y-m-d', strtotime('+7 days', strtotime($monday1)));

        $this->assertTrue($this->svc->upsertWeeklyNote($this->teacherId, $monday1, 'Tuần 1'));
        $this->assertTrue($this->svc->upsertWeeklyNote($this->teacherId, $monday2, 'Tuần 2'));

        $this->assertSame(2, (int) $this->db->count('db_lms_teacher_notes', 'teacher_id = ?', array($this->teacherId)));
        $this->assertSame('Tuần 2', $this->svc->getWeeklyNote($this->teacherId, $monday2)['note']);
    }

    public function testWeeklyNoteRejectsNonMonday()
    {
        $tuesday = date('Y-m-d', strtotime('tuesday this week'));
        $ok = $this->svc->upsertWeeklyNote($this->teacherId, $tuesday, 'Sai tuần');
        $this->assertFalse($ok);
        $this->assertArrayHasKey('week_start', $this->svc->errors());
    }

    public function testWeeklyNoteRejectsTooLongNote()
    {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $ok = $this->svc->upsertWeeklyNote($this->teacherId, $monday, str_repeat('a', 5001));
        $this->assertFalse($ok);
        $this->assertArrayHasKey('note', $this->svc->errors());
    }
}
