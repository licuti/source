<?php
/**
 * Base test case: LmsDb trỏ tới DB test + dọn sạch bảng LMS trước mỗi test.
 *
 * CHÚ Ý: TRUNCATE xoá toàn bộ dữ liệu db_lms_* trong DB test — chỉ dùng cho
 * phuongnamv_db_lms_test, KHÔNG bao giờ chạy với DB thật.
 */

namespace Tests;

use LmsDb;
use PHPUnit\Framework\TestCase;

abstract class LmsTestCase extends TestCase
{
    /** @var LmsDb */
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();

        $dbName = getenv('LMS_TEST_DB');
        if (!$dbName) {
            $dbName = 'phuongnamv_db_lms_test';
        }
        if (strpos($dbName, 'phuongnamv_db_lms_test') === false) {
            $this->fail('LMS_TEST_DB phải là DB test (phuongnamv_db_lms_test), không được dùng DB thật.');
        }

        $this->db = new LmsDb(array(
            'host' => '127.0.0.1',
            'name' => $dbName,
            'user' => 'root',
            'pass' => '',
        ));

        $this->truncateLmsTables();
    }

    protected function tearDown(): void
    {
        // PHPUnit giữ mọi TestCase trong bộ nhớ tới hết run → mỗi test giữ 1
        // connection PDO. Trả về pool khi test xong (MySQL mặc định 151 conn).
        if ($this->db !== null) {
            $this->db->disconnect();
            $this->db = null;
        }
        parent::tearDown();
    }

    /**
     * Xoá toàn bộ dữ liệu trong các bảng db_lms_* (theo thứ tự FK).
     */
    protected function truncateLmsTables()
    {
        $this->db->safeExec('SET FOREIGN_KEY_CHECKS = 0');
        $tables = $this->db->safeFetch("SHOW TABLES");
        $col = 'Tables_in_' . getenv('LMS_TEST_DB');
        foreach ($tables as $row) {
            $name = isset($row[$col]) ? $row[$col] : reset($row);
            if (strpos((string) $name, 'db_lms_') === 0) {
                $this->db->safeExec('TRUNCATE TABLE `' . $name . '`');
            }
        }
        $this->db->safeExec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
