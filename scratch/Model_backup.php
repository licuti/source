<?php
/**
 * ============================================================
 *  CLASS Model (Version 2.3 - Productivity Powerhouse)
 *  Hỗ trợ Parameter Binding, Relationships, Pagination,
 *  Auto Timestamps và Mass Assignment.
 * ============================================================
 */
class Model {

    // ── Kết nối PDO dùng chung (Singleton) ──────────────────
    protected static $pdo    = null;
    protected static $prefix = 'db_';
    public static $globalConstraint = '';

    // ── Cấu hình Model (Có thể ghi đè ở class con) ─────────
    public $table     = '';
    public bool $use_lang = true;
    public bool $timestamps = true;
    protected string $createdAt = 'ngay_dang';
    protected string $updatedAt = 'cap_nhat';

    // ── Dữ liệu Instance ──────────────────────────────────
    public $attributes = [];

    // ── Trạng thái Query Builder ─────────────────────────────
    protected array  $qb_where     = [];
    protected array  $qb_where_raw = [];
    protected array  $qb_params    = []; 
    protected array  $qb_order     = [];
    protected array  $qb_with      = [];
    protected string $qb_limit     = '';

    // ============================================================
    //  KHỞI TẠO & KẾT NỐI
    // ============================================================

    public static function boot(array $config) {
        if (self::$pdo !== null) return;
        $dsn        = "mysql:host={$config['servername']};dbname={$config['database']};charset=utf8";
        self::$pdo  = new PDO($dsn, $config['username'], $config['password']);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec("set names utf8");
        self::$prefix = $config['refix'] ?? 'db_';
    }

    /**
     * Lấy đối tượng kết nối PDO hiện tại
     */
    public static function getConnection() {
        return self::$pdo;
    }

    public static function setGlobalConstraint(string $sql) {
        self::$globalConstraint = $sql;
    }

    public function __construct($attributes = []) {
        if (self::$pdo === null && class_exists('func_index') && func_index::$shared_db !== null) {
            self::$pdo    = func_index::$shared_db;
            self::$prefix = func_index::$shared_config['refix'] ?? 'db_';
        }
        $this->fill($attributes);
    }

    public function __get($key) {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value) {
        $this->attributes[$key] = $value;
    }

    public function __isset($key) {
        return isset($this->attributes[$key]);
    }

    public static function tableName(): string {
        $instance = new static();
        return str_replace('#_', self::$prefix, $instance->table);
    }

    // ============================================================
    //  THAO TÁC DỮ LIỆU HÀNG LOẠT (MASS ASSIGNMENT)
    // ============================================================

    /**
     * Đổ dữ liệu từ mảng vào attributes
     */
    public function fill(array $attributes) {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Tạo mới và lưu ngay lập tức
     */
    public static function create(array $attributes) {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Cập nhật mảng dữ liệu và lưu ngay
     */
    public function update(array $attributes) {
        return $this->fill($attributes)->save();
    }

    // ============================================================
    //  QUERY BUILDER — ENTRY POINT
    // ============================================================

    public static function query() {
        return new static();
    }

    public static function all($columns = '*') {
        return static::query()->get($columns);
    }

    public static function find($id, string $columns = '*') {
        return static::query()->qbFind($id, $columns);
    }

    public static function where($column, $value = null, $operator = '=') {
        return static::query()->qbWhere($column, $value, $operator);
    }

    public static function __callStatic($method, $parameters) {
        return static::query()->$method(...$parameters);
    }

    public function __call($method, $parameters) {
        $qbMethod = 'qb' . ucfirst($method);
        if (method_exists($this, $qbMethod)) {
            return $this->$qbMethod(...$parameters);
        }

        // Hỗ trợ magic method: withCategory() -> with('category')
        if (strpos($method, 'with') === 0 && strlen($method) > 4) {
            $relation = lcfirst(substr($method, 4));
            if (method_exists($this, $relation)) {
                return $this->qbWith($relation);
            }
        }

        throw new \BadMethodCallException("Method $method does not exist.");
    }

    // ── Điều kiện lọc (Internal Query Builder) ──────────────

    protected function qbWhere(string $column, $value, string $operator = '=') {
        $operator = strtoupper(trim($operator));
        if (in_array($operator, ['IN', 'NOT IN'])) {
            return $this->qbWhereIn($column, (array)$value, $operator === 'NOT IN');
        }
        $this->qb_where[] = "`$column` $operator ?";
        $this->qb_params[] = $value;
        return $this;
    }

    protected function qbWhereIn(string $column, array $values, bool $notIn = false) {
        if (empty($values)) {
            $this->qb_where_raw[] = "1=0";
            return $this;
        }
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $operator = $notIn ? 'NOT IN' : 'IN';
        $this->qb_where[] = "`$column` $operator ($placeholders)";
        $this->qb_params = array_merge($this->qb_params, array_values($values));
        return $this;
    }

    protected function qbWhereNotIn(string $column, array $values) {
        return $this->qbWhereIn($column, $values, true);
    }

    protected function qbWhereBetween(string $column, array $range) {
        $this->qb_where[] = "`$column` BETWEEN ? AND ?";
        $this->qb_params[] = $range[0];
        $this->qb_params[] = $range[1];
        return $this;
    }

    protected function qbWhereRaw(string $condition, array $params = []) {
        $this->qb_where_raw[] = $condition;
        if (!empty($params)) {
            $this->qb_params = array_merge($this->qb_params, array_values($params));
        }
        return $this;
    }

    // ── Sắp xếp & Giới hạn ───────────────────────────────────

    protected function qbOrderBy(string $column, string $direction = 'ASC') {
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $column)) return $this;
        $direction        = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->qb_order[] = "`$column` $direction";
        return $this;
    }

    protected function qbLatest(string $column = 'id') {
        return $this->qbOrderBy($column, 'DESC');
    }

    protected function qbLimit(int $limit, int $offset = 0) {
        $this->qb_limit = $offset > 0 ? "$offset, $limit" : (string)$limit;
        return $this;
    }

    protected function qbSetTable(string $table) {
        $this->table = $table;
        return $this;
    }

    protected function qbWith($relations) {
        if (is_string($relations)) $relations = func_get_args();
        $this->qb_with = array_merge($this->qb_with, $relations);
        return $this;
    }

    // ============================================================
    //  THỰC THI TRUY VẤN
    // ============================================================

    protected static array $noLangModels = [];

    public function get(string $columns = '*'): array {
        $tableName = $this->tableName();
        
        // Nếu đã biết model này không có cột lang, tắt nó đi
        if (isset(self::$noLangModels[get_called_class()])) {
            $this->use_lang = false;
        }

        try {
            return $this->executeGet($columns);
        } catch (PDOException $e) {
            // Lỗi 1054: Unknown column 'lang'
            if ($this->use_lang && $e->getCode() == '42S22' && strpos($e->getMessage(), "'lang'") !== false) {
                $this->use_lang = false;
                self::$noLangModels[get_called_class()] = true;
                return $this->executeGet($columns);
            }
            throw $e;
        }
    }

    /**
     * Thực thi query lấy dữ liệu (Hàm nội bộ)
     */
    protected function executeGet(string $columns = '*'): array {
        $tableName = $this->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        
        $statement = "SELECT $columns FROM $tableName";
        if ($whereSql) $statement .= " WHERE $whereSql";
        if (!empty($this->qb_order)) $statement .= ' ORDER BY ' . implode(', ', $this->qb_order);
        if ($this->qb_limit !== '') $statement .= ' LIMIT ' . $this->qb_limit;

        $stmt = self::$pdo->prepare($statement);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $models = array_map(fn($row) => new static($row), $rows);

        if (!empty($this->qb_with) && !empty($models)) {
            foreach ($this->qb_with as $relationName) {
                if (method_exists($this, $relationName)) {
                    $relation = $this->$relationName();
                    if (is_array($relation) && isset($relation['type'])) {
                        $this->handleRelation($models, $relationName, $relation);
                    } else {
                        $this->$relationName($models);
                    }
                }
            }
        }
        $this->resetQB();
        return $models;
    }

    public function count(): int {
        if (isset(self::$noLangModels[get_called_class()])) {
            $this->use_lang = false;
        }

        try {
            return $this->executeCount();
        } catch (PDOException $e) {
            if ($this->use_lang && $e->getCode() == '42S22' && strpos($e->getMessage(), "'lang'") !== false) {
                $this->use_lang = false;
                self::$noLangModels[get_called_class()] = true;
                return $this->executeCount();
            }
            throw $e;
        }
    }

    protected function executeCount(): int {
        $tableName = $this->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        $statement = "SELECT COUNT(*) as total FROM $tableName";
        if ($whereSql) $statement .= " WHERE $whereSql";
        $stmt = self::$pdo->prepare($statement);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->resetQB();
        return (int)$result['total'];
    }

    public function qbFind($id, string $columns = '*') {
        return $this->qbWhere('id', $id)->first($columns);
    }

    public function first(string $columns = '*') {
        $results = $this->qbLimit(1)->get($columns);
        return $results[0] ?? null;
    }

    public function paginate(int $perPage = 15, string $pageName = 'page') {
        $page = isset($_GET[$pageName]) ? (int)$_GET[$pageName] : 1;
        if ($page < 1) $page = 1;
        $tempWhere = $this->qb_where; $tempWhereRaw = $this->qb_where_raw; $tempParams = $this->qb_params;
        $total = $this->count();
        $this->qb_where = $tempWhere; $this->qb_where_raw = $tempWhereRaw; $this->qb_params = $tempParams;
        $lastPage = ceil($total / $perPage);
        $results = $this->limit($perPage, ($page - 1) * $perPage)->get();
        return (object)['data' => $results, 'total' => $total, 'per_page' => $perPage, 'current_page' => $page, 'last_page' => $lastPage];
    }

    // ============================================================
    //  QUAN HỆ (RELATIONSHIPS)
    // ============================================================

    protected function hasOne($relatedModel, $foreignKey, $localKey = 'id') {
        return ['type' => 'hasOne', 'model' => $relatedModel, 'foreignKey' => $foreignKey, 'localKey' => $localKey];
    }

    protected function hasMany($relatedModel, $foreignKey, $localKey = 'id') {
        return ['type' => 'hasMany', 'model' => $relatedModel, 'foreignKey' => $foreignKey, 'localKey' => $localKey];
    }

    protected function belongsTo($relatedModel, $foreignKey, $ownerKey = 'id') {
        return ['type' => 'belongsTo', 'model' => $relatedModel, 'foreignKey' => $foreignKey, 'ownerKey' => $ownerKey];
    }

    protected function handleRelation(&$models, $name, $config) {
        $isMultiple = ($config['type'] === 'hasMany');
        $foreignKey = ($config['type'] === 'belongsTo') ? $config['foreignKey'] : $config['localKey'];
        $relatedKey = ($config['type'] === 'belongsTo') ? $config['ownerKey']   : $config['foreignKey'];
        $relatedClass = $config['model'];
        $this->loadRelation($models, $relatedClass::tableName(), $foreignKey, $relatedKey, $name, $isMultiple);
        foreach ($models as &$model) {
            // Dùng array_key_exists thay vì !empty() vì empty() không hoạt động
            // đúng với magic __get nếu không có __isset() hoặc khi value là array rỗng
            $val = $model->attributes[$name] ?? null;
            if ($isMultiple && is_array($val)) {
                $model->attributes[$name] = array_map(
                    fn($row) => is_array($row) ? new $relatedClass($row) : $row,
                    $val
                );
            } elseif (!$isMultiple && is_array($val)) {
                $model->attributes[$name] = new $relatedClass($val);
            }
        }
    }

    // ============================================================
    //  DATA PERSISTENCE (CRUD)
    // ============================================================

    public function save() {
        $tableName = $this->tableName();
        $data = $this->attributes;
        $id = $data['id'] ?? null;

        // Xử lý Timestamps tự động
        if ($this->timestamps) {
            $currentTime = time();
            if (!$id) {
                $this->attributes[$this->createdAt] = $currentTime;
                $data[$this->createdAt] = $currentTime;
            }
            $this->attributes[$this->updatedAt] = $currentTime;
            $data[$this->updatedAt] = $currentTime;
        }

        if ($id && (int)$id > 0) {
            unset($data['id']);
            $fields = []; $values = [];
            foreach ($data as $col => $val) { $fields[] = "`$col` = ?"; $values[] = $val; }
            $values[] = $id;
            $sql = "UPDATE $tableName SET " . implode(', ', $fields) . " WHERE id = ?";
            return self::$pdo->prepare($sql)->execute($values);
        } else {
            $cols = implode('`, `', array_keys($data));
            $placeholders = implode(',', array_fill(0, count($data), '?'));
            $sql = "INSERT INTO $tableName (`$cols`) VALUES ($placeholders)";
            $stmt = self::$pdo->prepare($sql);
            $result = $stmt->execute(array_values($data));
            if ($result) {
                $this->id = self::$pdo->lastInsertId();
                $this->attributes['id'] = $this->id;
            }
            return $result;
        }
    }

    /**
     * Insert dữ liệu trực tiếp (Static, không cần instance)
     * @param array $data  ['column' => 'value', ...]
     * @return int|false   ID vừa insert hoặc false nếu lỗi
     */
    public static function insert(array $data) {
        $instance = new static();
        $tableName = $instance->tableName();

        // Auto timestamps
        if ($instance->timestamps) {
            $currentTime = time();
            if (!isset($data[$instance->createdAt])) {
                $data[$instance->createdAt] = $currentTime;
            }
            if (!isset($data[$instance->updatedAt])) {
                $data[$instance->updatedAt] = $currentTime;
            }
        }

        $cols = implode('`, `', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $tableName (`$cols`) VALUES ($placeholders)";
        $stmt = self::$pdo->prepare($sql);
        $result = $stmt->execute(array_values($data));

        return $result ? (int) self::$pdo->lastInsertId() : false;
    }

    /**
     * Tăng giá trị cột số (ví dụ: view count)
     */
    protected function qbIncrement(string $column, int $amount = 1) {
        $tableName = $this->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        $sql = "UPDATE $tableName SET `$column` = `$column` + $amount";
        if ($whereSql) $sql .= " WHERE $whereSql";
        $stmt = self::$pdo->prepare($sql);
        $result = $stmt->execute($params);
        $this->resetQB();
        return $result;
    }

    /**
     * Giảm giá trị cột số
     */
    protected function qbDecrement(string $column, int $amount = 1) {
        return $this->qbIncrement($column, -$amount);
    }

    public function delete() {
        if (!isset($this->attributes['id'])) return false;
        $sql = "DELETE FROM " . $this->tableName() . " WHERE id = ?";
        return self::$pdo->prepare($sql)->execute([$this->id]);
    }

    // ============================================================
    //  INTERNAL HELPERS
    // ============================================================

    protected function buildWhereClause(): array {
        $all = array_merge($this->qb_where, $this->qb_where_raw);
        $whereSql = implode(' AND ', $all);
        if ($this->use_lang && self::$globalConstraint !== '') {
            $whereSql = $whereSql ? "($whereSql) " . self::$globalConstraint : "1=1 " . self::$globalConstraint;
        }
        return [$whereSql, $this->qb_params];
    }

    protected function resetQB() {
        $this->qb_where = []; $this->qb_where_raw = []; $this->qb_params = []; $this->qb_order = []; $this->qb_limit = ''; $this->qb_with = [];
    }

    public function loadRelation(array &$rows, string $table, string $foreignKey, string $relatedKey, string $alias, bool $isMultiple = true, string $extraCondition = '') {
        if (empty($rows)) return $rows;

        // Dùng vòng lặp thay vì array_column vì Model dùng magic __get,
        // array_column() không thể đọc thuộc tính qua magic method.
        $ids = [];
        foreach ($rows as $model) {
            $val = ($model instanceof self) ? ($model->attributes[$foreignKey] ?? null) : ($model[$foreignKey] ?? null);
            if (!empty($val)) $ids[] = $val;
        }
        $ids = array_unique($ids);
        if (empty($ids)) return $rows;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM $table WHERE `$relatedKey` IN ($placeholders)";
        if (!empty($extraCondition)) {
            $sql .= " " . $extraCondition;
        }
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute(array_values($ids));
        $related = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $relMap = [];
        foreach ($related as $item) {
            if ($isMultiple) { $relMap[$item[$relatedKey]][] = $item; }
            else { $relMap[$item[$relatedKey]] = $item; }
        }

        foreach ($rows as &$model) {
            $key = ($model instanceof self) ? ($model->attributes[$foreignKey] ?? null) : ($model[$foreignKey] ?? null);
            $model->$alias = $relMap[$key] ?? ($isMultiple ? [] : null);
        }

        return $rows;
    }
}
