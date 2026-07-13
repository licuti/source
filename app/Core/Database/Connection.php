<?php
namespace App\Core\Database;

use PDO;
use PDOException;
use Exception;

class Connection {
    protected $pdo;
    protected $prefix;

    public function __construct(array $config) {
        $dsn = "mysql:host={$config['servername']};dbname={$config['database']};charset=utf8";
        $this->pdo = new PDO($dsn, $config['username'], $config['password']);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Cấu hình chuẩn để tránh lỗi string thay vì int khi dùng limit() (nếu emulate prepares được bật)
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); 
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec("set names utf8");
        $this->prefix = $config['refix'] ?? 'db_';
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }

    public function getPrefix(): string {
        return $this->prefix;
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }
}
