<?php
/**
 * LMS data layer — bản tối giản của func_index (chỉ phần an toàn, dùng cho LMS).
 *
 * Quyết định (plan Phase 0.4): KHÔNG require admin/lib/class.php vì đó là God class
 * 979 dòng gắn chặt nghiệp vụ e-commerce (danh mục, menu, địa chỉ, refix `#_`...).
 * LMS chỉ cần: safeFetch / safeFetchOne / safeExec / insert / update / transaction.
 * Giữ nguyên tên hàm + chữ ký để service code quen thuộc với codebase hiện tại.
 *
 * Quy ước:
 *  - Mọi query dùng bind tham số (`?`), KHÔNG nối chuỗi dữ liệu user.
 *  - Tên bảng viết đầy đủ `db_lms_*` (không dùng placeholder `#_`).
 *  - Charset utf8mb4 (học viên có tên tiếng Việt).
 *  - PHP 7.3 compatible: không dùng fn(), ??=, str_contains(), ?->.
 */

if (!class_exists('LmsDb', false)) {
    class LmsDb
    {
        /** @var PDO */
        private $db;

        /** @var string Câu SQL cuối cùng (debug/log) */
        public $lastSql = '';

        /** @var string ID vừa insert */
        public $insertId = '';

        /**
         * @param array $config array(host, name, user, pass)
         */
        public function __construct(array $config = array())
        {
            if (empty($config)) {
                return;
            }
            $this->connect($config);
        }

        /**
         * Kết nối DB. Lỗi không in message PDO ra màn hình (tránh lộ thông tin).
         *
         * @param array $config
         * @return void
         */
        public function connect(array $config)
        {
            $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
            $name = isset($config['name']) ? $config['name'] : '';
            $user = isset($config['user']) ? $config['user'] : 'root';
            $pass = isset($config['pass']) ? $config['pass'] : '';

            try {
                $this->db = new PDO(
                    'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
                    $user,
                    $pass,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    )
                );
                $this->db->exec('set names utf8mb4');
            } catch (PDOException $e) {
                error_log('[LMS DB] Connection failed: ' . $e->getMessage());
                echo 'Không kết nối được cơ sở dữ liệu. Vui lòng thử lại sau.';
                exit;
            }
        }

        /** @return PDO|null */
        public function pdo()
        {
            return $this->db;
        }

        public function disconnect()
        {
            $this->db = null;
        }

        /**
         * SELECT trả về nhiều dòng (mảng rỗng nếu không có).
         *
         * @param string $sql    dùng `?` cho tham số
         * @param array  $params giá trị theo thứ tự dấu `?`
         * @return array
         */
        public function safeFetch($sql, array $params = array())
        {
            $this->lastSql = $sql;
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($params));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        /**
         * SELECT trả về đúng 1 dòng (mảng rỗng nếu không có).
         *
         * @param string $sql
         * @param array  $params
         * @return array
         */
        public function safeFetchOne($sql, array $params = array())
        {
            $rows = $this->safeFetch($sql, $params);
            return empty($rows) ? array() : $rows[0];
        }

        /**
         * INSERT/UPDATE/DELETE có tham số.
         *
         * @param string $sql
         * @param array  $params
         * @return bool
         */
        public function safeExec($sql, array $params = array())
        {
            $this->lastSql = $sql;
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute(array_values($params));
            if ($ok && preg_match('/^\s*insert\b/i', $sql)) {
                $this->insertId = (string) $this->db->lastInsertId();
            }
            return $ok;
        }

        /**
         * Helper INSERT.
         *
         * @param string $table
         * @param array  $data  cột => giá trị
         * @return bool
         */
        public function insert($table, array $data)
        {
            if (empty($data)) {
                return false;
            }
            $cols = array_keys($data);
            $placeholders = implode(', ', array_fill(0, count($cols), '?'));
            $sql = 'insert into `' . $table . '` (`' . implode('`, `', $cols)
                . '`) values (' . $placeholders . ')';
            return $this->safeExec($sql, array_values($data));
        }

        /**
         * Helper UPDATE.
         *
         * @param string $table
         * @param array  $data      cột => giá trị
         * @param string $where     điều kiện (có tham số `?`)
         * @param array  $whereParams
         * @return bool
         */
        public function update($table, array $data, $where, array $whereParams = array())
        {
            if (empty($data)) {
                return false;
            }
            $set = array();
            foreach (array_keys($data) as $col) {
                $set[] = '`' . $col . '` = ?';
            }
            $sql = 'update `' . $table . '` set ' . implode(', ', $set) . ' where ' . $where;
            return $this->safeExec($sql, array_merge(array_values($data), array_values($whereParams)));
        }

        /**
         * Đếm số dòng khớp điều kiện.
         *
         * @param string $table
         * @param string $where
         * @param array  $params
         * @return int
         */
        public function count($table, $where = '1=1', array $params = array())
        {
            $row = $this->safeFetchOne('select count(*) as c from `' . $table . '` where ' . $where, $params);
            return isset($row['c']) ? (int) $row['c'] : 0;
        }

        public function beginTransaction()
        {
            if ($this->db && !$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }
        }

        public function commit()
        {
            if ($this->db && $this->db->inTransaction()) {
                $this->db->commit();
            }
        }

        public function rollBack()
        {
            if ($this->db && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
        }
    }
}
