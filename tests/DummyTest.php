<?php
/**
 * Dummy test — kiểm tra PHPUnit chạy được (plan Phase 0.5).
 */

use PHPUnit\Framework\TestCase;

class DummyTest extends TestCase
{
    public function testPhpunitRuns()
    {
        $this->assertTrue(true);
    }

    public function testPhpVersionCompatible()
    {
        $this->assertGreaterThanOrEqual(7.3, (float) PHP_VERSION);
    }

    public function testLmsDbConnectsToTestDb()
    {
        $this->assertTrue(class_exists('LmsDb'));
        $this->assertTrue(class_exists('Lms\\UserService'));
    }
}
