<?php
namespace Tests\Services;
use Lms\ClassRulesService;
use Lms\UserService;
use Tests\LmsTestCase;

class ClassRulesServiceTest extends LmsTestCase
{
    public function testSaveAndGetRules()
    {
        $users = new UserService($this->db);
        $admin = $users->createUser('rules_admin', 'password123', 'admin');
        $this->db->insert('db_lms_admins', array('user_id' => $admin, 'full_name' => 'Rules Admin'));
        $svc = new ClassRulesService($this->db);
        $this->assertSame('', $svc->getRules()['content']);
        $this->assertTrue($svc->saveRules("Đi học đúng giờ.\nTôn trọng lớp học.", $admin));
        $rules = $svc->getRules();
        $this->assertSame("Đi học đúng giờ.\nTôn trọng lớp học.", $rules['content']);
        $this->assertNotEmpty($rules['updated_at']);
    }

    public function testRejectEmptyOrOversizedRules()
    {
        $svc = new ClassRulesService($this->db);
        $this->assertFalse($svc->saveRules('   ', 0));
        $this->assertArrayHasKey('content', $svc->errors());
        $this->assertFalse($svc->saveRules(str_repeat('x', 50001), 0));
        $this->assertArrayHasKey('content', $svc->errors());
    }
}
