<?php

namespace App\Core\Database;

/**
 * DB Facade — Truy cập nhanh vào PDO connection và QueryBuilder.
 *
 * Được thiết kế để mô phỏng cú pháp quen thuộc của Laravel:
 *   DB::transaction(function() { ... })
 *   DB::beginTransaction() / DB::commit() / DB::rollBack()
 *   DB::table('post_category')->where(...)->get()
 */
class DB {

    // ── Transaction API ──────────────────────────────────────

    /**
     * Chạy một Closure trong transaction. Tự động commit hoặc rollBack.
     * Tương đương: DB::transaction(fn() => ...) trong Laravel.
     *
     * @param  callable $callback
     * @return mixed  Giá trị trả về của $callback
     * @throws \Exception
     */
    public static function transaction(callable $callback) {
        $pdo = Model::getConnection();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Bắt đầu transaction thủ công.
     */
    public static function beginTransaction(): bool {
        return Model::getConnection()->beginTransaction();
    }

    /**
     * Xác nhận transaction.
     */
    public static function commit(): bool {
        return Model::getConnection()->commit();
    }

    /**
     * Huỷ transaction.
     */
    public static function rollBack(): bool {
        return Model::getConnection()->rollBack();
    }

    // ── Query Builder API ────────────────────────────────────

    /**
     * Tạo QueryBuilder cho một bảng bất kỳ (không cần Model class).
     * Tương đương: DB::table('my_table') trong Laravel.
     *
     * @param  string $table  Tên bảng (không có prefix — QueryBuilder tự xử lý prefix)
     * @return QueryBuilder
     */
    public static function table(string $table): QueryBuilder {
        // Tạo một Model trống, ép buộc tên bảng để QueryBuilder có thể hoạt động
        $anonymousModel = new class extends Model {
            // Model trống, không có bảng mặc định
        };

        $builder = new QueryBuilder($anonymousModel);
        $builder->setTable($table);
        return $builder;
    }

    // ── Raw Query API ────────────────────────────────────────

    /**
     * Chạy câu SQL thô và trả về mảng kết quả.
     *
     * @param  string $sql
     * @param  array  $bindings
     * @return array
     */
    public static function select(string $sql, array $bindings = []): array {
        $pdo  = Model::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Chạy câu SQL thô dạng ghi (INSERT / UPDATE / DELETE).
     *
     * @param  string $sql
     * @param  array  $bindings
     * @return int  Số dòng bị ảnh hưởng
     */
    public static function statement(string $sql, array $bindings = []): int {
        $pdo  = Model::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    /**
     * Trả về đối tượng PDO gốc để dùng trong trường hợp cần thiết.
     *
     * @return \PDO
     */
    public static function getPdo(): \PDO {
        return Model::getConnection();
    }
}
