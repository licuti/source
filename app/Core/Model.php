<?php
/**
 * ============================================================
 *  CLASS Model — Version 3.2 (Eloquent-Compatible)
 *  Phase 1: Query Builder (where, join, groupBy, pluck...)
 *  Phase 2: CRUD & Security (fillable, casts, toArray...)
 *  Phase 3: Events (creating, saving...), Accessors/Mutators,
 *           Advanced Relationships (belongsToMany...)
 * ============================================================
 */
class Model implements \JsonSerializable {

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

    // ── Hằng số Trạng thái Dùng chung ───────────────────────
    public const STATUS_PUBLISH = 1;
    public const STATUS_DRAFT   = 0;

    public const STATUS_LABELS = [
        self::STATUS_PUBLISH => ['label' => 'Hiển thị',  'icon' => '✅', 'badge' => 'success'],
        self::STATUS_DRAFT   => ['label' => 'Đang ẩn',  'icon' => '🔒', 'badge' => 'secondary'],
    ];

    // ── Dữ liệu Instance ──────────────────────────────────
    public $attributes = [];

    /**
     * Lưu trữ các quan hệ (relations) đã được eager-loaded.
     * Tách biệt khỏi $attributes để toArray() luôn chính xác.
     */
    protected array $relations = [];

    // ── Mass Assignment & Serialization ──────────────────────
    /** Các cột được phép mass assign. Rỗng = cho phép tất cả */
    protected array $fillable = [];
    /** Các cột BỊ CHẶN mass assign (ngược với fillable) */
    protected array $guarded  = ['id'];
    /** Các cột bị ẩn khi gọi toArray()/toJson() */
    protected array $hidden   = [];
    /** Nếu set, chỉ những cột này được xuất ra */
    protected array $visible  = [];
    /** Tự ép kiểu giá trị khi đọc thuộc tính */
    protected array $casts    = [];
    /** Flag: record vừa được INSERT (chưa UPDATE) */
    public bool $wasRecentlyCreated = false;

    // ── Trạng thái Query Builder ─────────────────────────────
    protected array  $qb_where     = [];   // AND conditions
    protected array  $qb_or_where  = [];   // OR conditions
    protected array  $qb_where_raw = [];
    protected array  $qb_params    = [];
    protected array  $qb_order     = [];
    protected array  $qb_with      = [];
    protected array  $qb_joins     = [];   // JOIN clauses
    protected array  $qb_group_by  = [];
    protected string $qb_having    = '';
    protected string $qb_select    = '*'; // SELECT columns
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

    public static function getGlobalConstraint(): string {
        return self::$globalConstraint ?? '';
    }

    public function __construct($attributes = []) {
        if (self::$pdo === null && class_exists('func_index') && func_index::$shared_db !== null) {
            self::$pdo    = func_index::$shared_db;
            self::$prefix = func_index::$shared_config['refix'] ?? 'db_';
        }
        // Dùng forceFill để bỷ qua $fillable khi khởi tạo từ DB
        $this->forceFill($attributes);
    }

    public function __get($key) {
        // 1. Kiểm tra relations trước (eager-loaded data)
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        // 2. Accessors: getXxxAttribute()
        $accessor = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->$accessor($this->attributes[$key] ?? null);
        }

        // 3. Lấy giá trị từ attributes, áp dụng casts nếu có
        $val = $this->attributes[$key] ?? null;
        if ($val !== null && isset($this->casts[$key])) {
            return $this->castValue($val, $this->casts[$key]);
        }
        return $val;
    }

    public function __set($key, $value) {
        // Mutators: setXxxAttribute()
        $mutator = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $this->$mutator($value);
        } else {
            $this->attributes[$key] = $value;
        }
    }

    public function __isset($key) {
        return array_key_exists($key, $this->relations) || isset($this->attributes[$key]);
    }

    // ============================================================
    //  RELATIONS MANAGEMENT
    // ============================================================

    /**
     * Gán dữ liệu relation đã load — thay thế cho dynamic property assignment.
     */
    public function setRelation(string $name, $value): self {
        $this->relations[$name] = $value;
        return $this;
    }

    /**
     * Lấy relation đã load, trả về $default nếu chưa có.
     */
    public function getRelation(string $name, $default = null) {
        return $this->relations[$name] ?? $default;
    }

    /**
     * Trả về toàn bộ mảng relations đã được load.
     */
    public function getRelations(): array {
        return $this->relations;
    }

    /**
     * Kiểm tra relation có được load chưa.
     */
    public function relationLoaded(string $name): bool {
        return array_key_exists($name, $this->relations);
    }

    public static function tableName(): string {
        $instance = new static();
        return str_replace('#_', self::$prefix, $instance->table);
    }

    // ============================================================
    //  THAO TÁC DỮ LIỆU HÀNG LOẠT (MASS ASSIGNMENT)
    // ============================================================

    /**
     * Đổ dữ liệu từ mảng vào attributes — tôn trọng $fillable/$guarded
     */
    public function fill(array $attributes) {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Đổ dữ liệu không kiểm tra fillable (dùng nội bộ khi lấy từ DB)
     */
    protected function forceFill(array $attributes) {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Kiểm tra cột có được phép mass assign không
     */
    protected function isFillable(string $key): bool {
        // Có $fillable cụ thể: chỉ cho phép các key trong danh sách
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }
        // Không có $fillable: kiểm tra $guarded
        return !in_array($key, $this->guarded);
    }


    /**
     * Triển khai JsonSerializable — tương thích PHP 7.4 và PHP 8.x.
     * Không khai báo return type ':mixed' vì mixed chỉ có từ PHP 8.0.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        return $this->toArray();
    }

    /**
     * Chuyển model thành mảng thuần, bao gồm cả relations đã load.
     * Relations được chuyển đổi đệ quy (toArray() lồng nhau).
     */
    public function toArray(): array {
        $data = $this->attributes;

        // Merge relations — chuyển đổi đệ quy để JSON hoạt động đúng
        foreach ($this->relations as $key => $val) {
            if (is_array($val)) {
                $data[$key] = array_map(
                    fn($v) => ($v instanceof self) ? $v->toArray() : $v,
                    $val
                );
            } elseif ($val instanceof self) {
                $data[$key] = $val->toArray();
            } else {
                $data[$key] = $val;
            }
        }

        // Áp dụng $visible / $hidden
        if (!empty($this->visible)) {
            $data = array_intersect_key($data, array_flip($this->visible));
        }
        if (!empty($this->hidden)) {
            $data = array_diff_key($data, array_flip($this->hidden));
        }
        return $data;
    }

    /**
     * Xuất ra chuỗi JSON (UTF-8 safe)
     */
    public function toJson(int $options = 0): string {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | $options);
    }

    

    /**
     * Tạo mới và lưu ngay lập tức — áp dụng $fillable
     */
    public static function create(array $attributes) {
        $model = new static();
        $model->fill($attributes); // Áp dụng fillable
        $model->save();
        return $model;
    }

    /**
     * Cập nhật mảng dữ liệu và lưu ngay
     */
    public function update(array $attributes) {
        // Nếu đang trong ngữ cảnh Query Builder (có điều kiện WHERE) -> Mass Update
        if (!empty($this->qb_where) || !empty($this->qb_where_raw) || !empty($this->qb_or_where)) {
            return $this->qbUpdate($attributes);
        }
        
        // Ngược lại, cập nhật instance hiện tại
        return $this->fill($attributes)->save();
    }

    /**
     * Tìm record khớp $search, nếu không có thì tạo mới với ($search + $extra)
     * Ví dụ: ContactModel::firstOrCreate(['email' => $email], ['name' => $name])
     */
    public static function firstOrCreate(array $search, array $extra = []) {
        $qb = static::query();
        foreach ($search as $col => $val) {
            $qb->where($col, $val);
        }
        $found = $qb->first();
        if ($found) return $found;
        return static::create(array_merge($search, $extra));
    }

    /**
     * Tìm và cập nhật, hoặc tạo mới nếu không có
     * Ví dụ: OrderModel::updateOrCreate(['order_no' => $no], ['status' => 'paid'])
     */
    public static function updateOrCreate(array $search, array $data = []) {
        $qb = static::query();
        foreach ($search as $col => $val) {
            $qb->where($col, $val);
        }
        $found = $qb->first();
        if ($found) {
            $found->fill($data)->save();
            return $found;
        }
        return static::create(array_merge($search, $data));
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


    public static function __callStatic($method, $parameters) {
        return static::query()->$method(...$parameters);
    }

    public function __call($method, $parameters) {
        $qbMethod = 'qb' . ucfirst($method);
        if (method_exists($this, $qbMethod)) {
            return $this->$qbMethod(...$parameters);
        }

        // Hỗ trợ Query Scope (Ví dụ: scopeOwnedByUser -> ownedByUser)
        $scopeMethod = 'scope' . ucfirst($method);
        if (method_exists($this, $scopeMethod)) {
            array_unshift($parameters, $this);
            return $this->$scopeMethod(...$parameters) ?? $this;
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

    protected function qbWhere($column, $value = null, $operator = '=') {
        // Hỗ trợ Eloquent-style: where('age', '>=', 18)
        // Phát hiện: nếu $value là toán tử chuỗi và $operator là giá trị thực
        $knownOps = ['=','!=','<>','>','<','>=','<=','LIKE','NOT LIKE','IN','NOT IN','IS NULL','IS NOT NULL'];
        if (is_string($value) && in_array(strtoupper(trim($value)), $knownOps) && $operator !== '=') {
            [$value, $operator] = [$operator, $value]; // Hoán đổi
        }
        $operator = strtoupper(trim($operator));
        if (in_array($operator, ['IN', 'NOT IN'])) {
            return $this->qbWhereIn($column, (array)$value, $operator === 'NOT IN');
        }
        $this->qb_where[] = "`$column` $operator ?";
        $this->qb_params[] = $value;
        return $this;
    }

    protected function qbOrWhere($column, $value = null, $operator = '=') {
        $knownOps = ['=','!=','<>','>','<','>=','<=','LIKE','NOT LIKE'];
        if (is_string($value) && in_array(strtoupper(trim($value)), $knownOps) && $operator !== '=') {
            [$value, $operator] = [$operator, $value];
        }
        $operator = strtoupper(trim($operator));
        $this->qb_or_where[] = "`$column` $operator ?";
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

    /**
     * Tìm kiếm theo LIKE — tự động thêm % nếu chưa có
     * Ví dụ:
     *   ->whereLike('title', 'php')          → WHERE `title` LIKE '%php%'
     *   ->whereLike('title', 'php%')         → WHERE `title` LIKE 'php%'
     *   ->whereLike('title', '%php')         → WHERE `title` LIKE '%php'
     */
    protected function qbWhereLike(string $column, string $value) {
        // Nếu chưa có ký tự % nào, bọc hai đầu
        if (strpos($value, '%') === false) {
            $value = '%' . $value . '%';
        }
        $this->qb_where[] = "`$column` LIKE ?";
        $this->qb_params[] = $value;
        return $this;
    }

    /**
     * Ngược lại với whereLike
     * Ví dụ: ->whereNotLike('title', 'draft')  → WHERE `title` NOT LIKE '%draft%'
     */
    protected function qbWhereNotLike(string $column, string $value) {
        if (strpos($value, '%') === false) {
            $value = '%' . $value . '%';
        }
        $this->qb_where[] = "`$column` NOT LIKE ?";
        $this->qb_params[] = $value;
        return $this;
    }

    // ── Điều kiện NULL ────────────────────────────────────────

    protected function qbWhereNull(string $column) {
        $this->qb_where[] = "`$column` IS NULL";
        return $this;
    }

    protected function qbWhereNotNull(string $column) {
        $this->qb_where[] = "`$column` IS NOT NULL";
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

    protected function qbOldest(string $column = 'id') {
        return $this->qbOrderBy($column, 'ASC');
    }

    protected function qbLimit(int $limit, int $offset = 0) {
        $this->qb_limit = $offset > 0 ? "$offset, $limit" : (string)$limit;
        return $this;
    }

    /** Alias của limit offset — giống Laravel skip()->take() */
    protected function qbSkip(int $offset) {
        // Lưu offset tạm, take() sẽ hoàn thiện
        $this->_skip = $offset;
        return $this;
    }

    protected function qbTake(int $limit) {
        $offset = $this->_skip ?? 0;
        return $this->qbLimit($limit, $offset);
    }

    // ── SELECT columns ────────────────────────────────────────

    protected function qbSelect(...$columns) {
        $cols = [];
        foreach ($columns as $c) {
            $cols = array_merge($cols, is_array($c) ? $c : [$c]);
        }
        $this->qb_select = implode(', ', $cols);
        return $this;
    }

    // ── GROUP BY & HAVING ─────────────────────────────────────

    protected function qbGroupBy(string ...$columns) {
        foreach ($columns as $col) {
            $this->qb_group_by[] = "`$col`";
        }
        return $this;
    }

    protected function qbHaving(string $rawCondition) {
        $this->qb_having = $rawCondition;
        return $this;
    }

    // ── JOIN ──────────────────────────────────────────────────

    protected function qbJoin(string $table, string $first, string $operator, string $second, string $type = 'INNER') {
        $table = str_replace('#_', self::$prefix, $table);
        $this->qb_joins[] = "$type JOIN $table ON $first $operator $second";
        return $this;
    }

    protected function qbLeftJoin(string $table, string $first, string $operator, string $second) {
        return $this->qbJoin($table, $first, $operator, $second, 'LEFT');
    }

    // ── CONDITIONAL CHAINING ─────────────────────────────────

    protected function qbWhen(bool $condition, callable $callback) {
        if ($condition) $callback($this);
        return $this;
    }

    // ── DEBUG ─────────────────────────────────────────────────

    public function toSql(): string {
        $tableName = $this->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        $sql = "SELECT {$this->qb_select} FROM $tableName";
        if (!empty($this->qb_joins)) $sql .= ' ' . implode(' ', $this->qb_joins);
        if ($whereSql) $sql .= " WHERE $whereSql";
        if (!empty($this->qb_group_by)) $sql .= ' GROUP BY ' . implode(', ', $this->qb_group_by);
        if ($this->qb_having !== '') $sql .= ' HAVING ' . $this->qb_having;
        if (!empty($this->qb_order)) $sql .= ' ORDER BY ' . implode(', ', $this->qb_order);
        if ($this->qb_limit !== '') $sql .= ' LIMIT ' . $this->qb_limit;
        // Replace ? với giá trị thực để dễ đọc
        foreach ($params as $p) {
            $p = is_string($p) ? "'$p'" : $p;
            $sql = preg_replace('/\?/', (string)$p, $sql, 1);
        }
        return $sql;
    }

    protected function qbSetTable(string $table) {
        $this->table = $table;
        return $this;
    }

    protected function qbWith($relations) {
        if (is_string($relations)) $relations = func_get_args();
        foreach ($relations as $key => $value) {
            if (is_int($key)) {
                $this->qb_with[$value] = null; // Tên quan hệ không có constraint
            } else {
                $this->qb_with[$key] = $value; // Có closure constraint
            }
        }
        return $this;
    }

    // ============================================================
    //  THỰC THI TRUY VẤN
    // ============================================================

    protected static array $noLangModels = [];

    public function qbGet(string $columns = '*'): array {
        // Nếu đã biết model này không có cột lang, tắt nó đi
        if (isset(self::$noLangModels[get_called_class()])) {
            $this->use_lang = false;
        }
        // Nếu đã dùng ->select() chain thì ưu tiên qb_select hơn param
        if ($this->qb_select !== '*') $columns = $this->qb_select;

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

        // Ưu tiên qb_select nếu đã chain select(), ngược lại dùng $columns param
        $selectCols = ($this->qb_select !== '*') ? $this->qb_select : $columns;

        $statement = "SELECT $selectCols FROM $tableName";
        if (!empty($this->qb_joins))    $statement .= ' ' . implode(' ', $this->qb_joins);
        if ($whereSql)                  $statement .= " WHERE $whereSql";
        if (!empty($this->qb_group_by)) $statement .= ' GROUP BY ' . implode(', ', $this->qb_group_by);
        if ($this->qb_having !== '')    $statement .= ' HAVING ' . $this->qb_having;
        if (!empty($this->qb_order))    $statement .= ' ORDER BY ' . implode(', ', $this->qb_order);
        if ($this->qb_limit !== '')     $statement .= ' LIMIT ' . $this->qb_limit;

        $stmt = self::$pdo->prepare($statement);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $models = array_map(fn($row) => new static($row), $rows);

        if (!empty($this->qb_with) && !empty($models)) {
            foreach ($this->qb_with as $relationName => $constraint) {
                // Phân tách nested relations (VD: 'variants.thuoctinh')
                $nested = [];
                if (strpos($relationName, '.') !== false) {
                    list($relationName, $nestedRel) = explode('.', $relationName, 2);
                    $nested[$nestedRel] = $constraint;
                    $constraint = null; // Constraint áp dụng cho bảng con
                }

                if (method_exists($this, $relationName)) {
                    $relation = $this->$relationName();
                    if (is_array($relation) && isset($relation['type'])) {
                        // Kế thừa constraint vào config nội bộ
                        $relation['constraint'] = $constraint;
                        $relation['nested'] = $nested;
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

    public function qbCount(): int {
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
        if (!empty($this->qb_joins)) $statement .= ' ' . implode(' ', $this->qb_joins);
        if ($whereSql) $statement .= " WHERE $whereSql";
        if (!empty($this->qb_group_by)) $statement .= ' GROUP BY ' . implode(', ', $this->qb_group_by);
        $stmt = self::$pdo->prepare($statement);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->resetQB();
        return (int)$result['total'];
    }

    public function qbFind($id, string $columns = '*') {
        return $this->qbWhere('id', $id)->first($columns);
    }

    public function qbFirst(string $columns = '*') {
        $results = $this->qbLimit(1)->get($columns);
        return $results[0] ?? null;
    }

    /**
     * Gi\u1ed1ng first() nh\u01b0ng n\u00e9m Exception n\u1ebfu kh\u00f4ng t\u00ecm th\u1ea5y
     */
    public function qbFirstOrFail(string $columns = '*') {
        $result = $this->first($columns);
        if (!$result) {
            throw new \RuntimeException(static::class . ': Record not found.');
        }
        return $result;
    }

    /**
     * Gi\u1ed1ng find() nh\u01b0ng n\u00e9m Exception n\u1ebfu kh\u00f4ng t\u00ecm th\u1ea5y
     */
    public static function findOrFail($id, string $columns = '*') {
        $result = static::find($id, $columns);
        if (!$result) {
            throw new \RuntimeException(static::class . ": Record #$id not found.");
        }
        return $result;
    }

    /**
     * L\u1ea5y m\u1ea3ng c\u00e1c gi\u00e1 tr\u1ecb c\u1ee7a 1 c\u1ed9t \u2014 gi\u1ed1ng Laravel pluck()
     * V\u00ed d\u1ee5: ProductModel::pluck('id_code') => [1, 5, 12, ...]
     */
    public function qbPluck(string $column, ?string $keyBy = null): array {
        $cols   = $keyBy ? "$column, $keyBy" : $column;
        $rows   = $this->get($cols);
        $result = [];
        foreach ($rows as $row) {
            $val = $row->$column;
            if ($keyBy) {
                $result[$row->$keyBy] = $val;
            } else {
                $result[] = $val;
            }
        }
        return $result;
    }

    /**
     * Lấy giá trị của 1 field ở record đầu tiên — giống Laravel value()
     * Ví dụ: ProductModel::where('id_code', 5)->columnValue('ten')
     */
    public function columnValue(string $column) {
        $result = $this->first($column);
        return $result ? $result->$column : null;
    }

    /**
     * Ki\u1ec3m tra t\u1ed3n t\u1ea1i \u2014 nhanh h\u01a1n count() > 0
     * V\u00ed d\u1ee5: if (ProductModel::where('alias', $slug)->exists()) { ... }
     */
    public function qbExists(): bool {
        $tableName = $this->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        $sql = "SELECT 1 FROM $tableName";
        if (!empty($this->qb_joins)) $sql .= ' ' . implode(' ', $this->qb_joins);
        if ($whereSql) $sql .= " WHERE $whereSql";
        $sql .= ' LIMIT 1';
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        $this->resetQB();
        return (bool)$stmt->fetch();
    }

    /**
     * X\u1eed l\u00fd d\u1eef li\u1ec7u l\u1edbn theo t\u1eebng l\u00f4 \u2014 gi\u1ed1ng Laravel chunk()
     * V\u00ed d\u1ee5: ProductModel::chunk(100, function($products) { ... });
     */
    public function qbChunk(int $size, callable $callback): void {
        $page = 1;
        // Snapshot QB \u0111\u1ec3 d\u00f9ng l\u1ea1i nhi\u1ec1u l\u1ea7n
        $snap = [
            'where'     => $this->qb_where,
            'or_where'  => $this->qb_or_where,
            'where_raw' => $this->qb_where_raw,
            'params'    => $this->qb_params,
            'joins'     => $this->qb_joins,
            'order'     => $this->qb_order,
            'group_by'  => $this->qb_group_by,
        ];
        do {
            foreach ($snap as $k => $v) $this->{'qb_' . $k} = $v;
            $results = $this->limit($size, ($page - 1) * $size)->get();
            if (empty($results)) break;
            if ($callback($results) === false) break;
            $page++;
        } while (count($results) === $size);
    }

    public function qbPaginate(int $perPage = 15, int $page = 0, string $pageName = 'page') {
        // Nếu không truyền $page trực tiếp — đọc từ $_GET (tương thích ngược)
        if ($page < 1) $page = max(1, (int)($_GET[$pageName] ?? 1));

        // Lưu lại trạng thái QB để chạy count() mà không mất where
        $snap = [
            'where'     => $this->qb_where,
            'or_where'  => $this->qb_or_where,
            'where_raw' => $this->qb_where_raw,
            'params'    => $this->qb_params,
            'joins'     => $this->qb_joins,
            'group_by'  => $this->qb_group_by,
            'with'      => $this->qb_with,
        ];
        $total = $this->count();
        // Restore
        foreach ($snap as $k => $v) $this->{'qb_' . $k} = $v;

        $lastPage = max(1, (int)ceil($total / $perPage));
        $results  = $this->limit($perPage, ($page - 1) * $perPage)->get();

        return new \App\Core\Paginator($results, $total, $perPage, $page);
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

    protected function belongsToMany($relatedModel, $pivotTable, $foreignPivotKey, $relatedPivotKey, $parentKey = 'id', $relatedKey = 'id') {
        return [
            'type'            => 'belongsToMany',
            'model'           => $relatedModel,
            'pivotTable'      => $pivotTable,
            'foreignPivotKey' => $foreignPivotKey,
            'relatedPivotKey' => $relatedPivotKey,
            'parentKey'       => $parentKey,
            'relatedKey'      => $relatedKey,
        ];
    }

    protected function hasManyThrough($relatedModel, $throughModel, $firstKey, $secondKey, $localKey = 'id', $secondLocalKey = 'id') {
        return [
            'type'           => 'hasManyThrough',
            'model'          => $relatedModel,
            'through'        => $throughModel,
            'firstKey'       => $firstKey,
            'secondKey'      => $secondKey,
            'localKey'       => $localKey,
            'secondLocalKey' => $secondLocalKey,
        ];
    }

    protected function handleRelation(&$models, $name, $config) {
        if ($config['type'] === 'belongsToMany') {
            return $this->loadBelongsToMany($models, $name, $config);
        }
        if ($config['type'] === 'hasManyThrough') {
            foreach ($models as &$model) $model->attributes[$name] = [];
            return;
        }

        $isMultiple = ($config['type'] === 'hasMany');
        $foreignKey = ($config['type'] === 'belongsTo') ? $config['foreignKey'] : $config['localKey'];
        $relatedKey = ($config['type'] === 'belongsTo') ? $config['ownerKey']   : $config['foreignKey'];
        $relatedClass = $config['model'];
        
        $this->loadRelation(
            $models, 
            $relatedClass::tableName(), 
            $foreignKey, 
            $relatedKey, 
            $name, 
            $isMultiple, 
            '', 
            $config['constraint'] ?? null, 
            $config['nested'] ?? [], 
            $relatedClass
        );
        
        foreach ($models as &$model) {
            $val = $model->getRelation($name);
            if ($isMultiple && is_array($val)) {
                $model->setRelation($name, array_map(
                    fn($row) => is_object($row) ? $row : new $relatedClass($row),
                    $val
                ));
            } elseif (!$isMultiple && is_array($val)) {
                $model->setRelation($name, is_object($val) ? $val : new $relatedClass($val));
            }
        }
    }

    protected function loadBelongsToMany(&$models, $name, $config) {
        if (empty($models)) return;
        
        $parentKey       = $config['parentKey'];
        $foreignPivotKey = $config['foreignPivotKey'];
        $relatedPivotKey = $config['relatedPivotKey'];
        $relatedKey      = $config['relatedKey'];
        $pivotTable      = str_replace('#_', self::$prefix, $config['pivotTable']);
        $relatedClass    = $config['model'];
        $relatedTable    = $relatedClass::tableName();

        $ids = [];
        foreach ($models as $m) {
            $val = $m->$parentKey ?? null;
            if ($val !== null) $ids[] = $val;
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            foreach ($models as &$m) $m->attributes[$name] = [];
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        // Prepared statements chống SQL Injection + backtick bọc tên cột/bảng
        $sql = "
            SELECT r.*, p.`$foreignPivotKey` as pivot_$foreignPivotKey, p.`$relatedPivotKey` as pivot_$relatedPivotKey
            FROM `$relatedTable` r
            INNER JOIN `$pivotTable` p ON p.`$relatedPivotKey` = r.`$relatedKey`
            WHERE p.`$foreignPivotKey` IN ($placeholders)
        ";
        
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute(array_values($ids));
        $relatedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $relMap = [];
        foreach ($relatedRows as $row) {
            $pid = $row["pivot_$foreignPivotKey"];
            unset($row["pivot_$foreignPivotKey"], $row["pivot_$relatedPivotKey"]); // Dọn dẹp
            $relMap[$pid][] = new $relatedClass($row);
        }

        foreach ($models as &$m) {
            $key = $m->$parentKey ?? null;
            $m->setRelation($name, $relMap[$key] ?? []);
        }
    }

    // ============================================================
    //  DATA PERSISTENCE (CRUD)
    // ============================================================

    public function save() {
        if (!$this->fireEvent('saving')) return false;

        $tableName = $this->tableName();
        $data = $this->attributes;
        $id = $data['id'] ?? null;
        $isUpdate = $id && (int)$id > 0;

        if ($isUpdate) {
            if (!$this->fireEvent('updating')) return false;
        } else {
            if (!$this->fireEvent('creating')) return false;
        }

        // Xử lý Timestamps tự động
        if ($this->timestamps) {
            $currentTime = date('Y-m-d H:i:s');
            if (!$id) {
                $this->attributes[$this->createdAt] = $currentTime;
                $data[$this->createdAt] = $currentTime;
            }
            $this->attributes[$this->updatedAt] = $currentTime;
            $data[$this->updatedAt] = $currentTime;
        }

        if ($isUpdate) {
            unset($data['id']);
            $fields = []; $values = [];
            foreach ($data as $col => $val) { $fields[] = "`$col` = ?"; $values[] = $val; }
            $values[] = $id;
            $sql = "UPDATE $tableName SET " . implode(', ', $fields) . " WHERE id = ?";
            $result = self::$pdo->prepare($sql)->execute($values);
            if ($result) {
                $this->fireEvent('updated');
                $this->fireEvent('saved');
            }
            return $result;
        } else {
            $cols = implode('`, `', array_keys($data));
            $placeholders = implode(',', array_fill(0, count($data), '?'));
            $sql = "INSERT INTO $tableName (`$cols`) VALUES ($placeholders)";
            $stmt = self::$pdo->prepare($sql);
            $result = $stmt->execute(array_values($data));
            if ($result) {
                $this->id = self::$pdo->lastInsertId();
                $this->attributes['id'] = $this->id;
                $this->wasRecentlyCreated = true; // ✔ Flag INSERT mới
                $this->fireEvent('created');
                $this->fireEvent('saved');
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
            $currentTime = date('Y-m-d H:i:s');
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
     * Alias for insert() - Trả về ID vừa insert (giống Laravel)
     * @param array $data
     * @return int|false
     */
    public static function insertGetId(array $data) {
        return self::insert($data);
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
     * Cập nhật hàng loạt (Mass Update) qua Query Builder
     * Ví dụ: Model::where('status', 0)->update(['status' => 1]);
     */
    protected function qbUpdate(array $data) {
        if (empty($data)) return false;
        
        $tableName = $this->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        
        if ($this->timestamps) {
            $data[$this->updatedAt] = date('Y-m-d H:i:s');
        }

        $fields = [];
        $updateParams = [];
        foreach ($data as $col => $val) {
            $fields[] = "`$col` = ?";
            $updateParams[] = $val;
        }
        
        $sql = "UPDATE $tableName SET " . implode(', ', $fields);
        if ($whereSql) {
            $sql .= " WHERE $whereSql";
            $updateParams = array_merge($updateParams, $params);
        }
        
        $stmt = self::$pdo->prepare($sql);
        $result = $stmt->execute($updateParams);
        $this->resetQB();
        return $result;
    }

    /**
     * Giảm giá trị cột số
     */
    protected function qbDecrement(string $column, int $amount = 1) {
        return $this->qbIncrement($column, -$amount);
    }

    /**
     * Xóa record:
     * - Gọi trên instance ($model->delete()) => xóa theo id
     * - Gọi sau where() (Model::where()->delete()) => xóa theo điều kiện QB
     */
    public function delete() {
        // Nếu có điều kiện QB (gọi sau where()), dùng điều kiện đó
        if (!empty($this->qb_where) || !empty($this->qb_where_raw)) {
            $tableName = $this->tableName();
            list($whereSql, $params) = $this->buildWhereClause();
            $sql = "DELETE FROM $tableName";
            if ($whereSql) $sql .= " WHERE $whereSql";
            $stmt = self::$pdo->prepare($sql);
            $result = $stmt->execute($params);
            $this->resetQB();
            return $result;
        }
        // Fallback: xóa theo id của instance
        if (!isset($this->attributes['id'])) return false;
        
        if (!$this->fireEvent('deleting')) return false;

        $sql = "DELETE FROM " . $this->tableName() . " WHERE id = ?";
        $result = self::$pdo->prepare($sql)->execute([$this->id]);
        
        if ($result) {
            $this->fireEvent('deleted');
        }
        return $result;
    }

    /**
     * Xóa theo ID (static) — giống Laravel Model::destroy($id)
     */
    public static function destroy($id): bool {
        $ids = is_array($id) ? $id : [$id];
        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) return false;
        $tableName = (new static())->tableName();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return self::$pdo->prepare("DELETE FROM $tableName WHERE id IN ($placeholders)")
                         ->execute(array_values($ids));
    }

    // ============================================================
    //  INTERNAL HELPERS
    // ============================================================

    /**
     * Gọi các sự kiện lifecycle của Model
     */
    protected function fireEvent(string $event): bool {
        if (method_exists($this, $event)) {
            return $this->$event() !== false; // Nếu hook return false => Cancel
        }
        return true;
    }

    protected function buildWhereClause(): array {
        // AND conditions
        $andParts = array_merge($this->qb_where, $this->qb_where_raw);
        $andSql   = implode(' AND ', $andParts);

        // OR conditions — gom chúng lại, bao trong ngoặc
        $orSql = '';
        if (!empty($this->qb_or_where)) {
            $orSql = '(' . implode(' OR ', $this->qb_or_where) . ')';
        }

        // Kết hợp AND và OR
        if ($andSql && $orSql) {
            $whereSql = "$andSql OR $orSql";
        } else {
            $whereSql = $andSql ?: $orSql;
        }

        // Global constraint (lọc ngôn ngữ)
        if ($this->use_lang && self::$globalConstraint !== '') {
            $whereSql = $whereSql
                ? "($whereSql) " . self::$globalConstraint
                : '1=1 ' . self::$globalConstraint;
        }

        return [$whereSql, $this->qb_params];
    }

    protected function resetQB() {
        $this->qb_where    = [];
        $this->qb_or_where = [];
        $this->qb_where_raw= [];
        $this->qb_params   = [];
        $this->qb_order    = [];
        $this->qb_limit    = '';
        $this->qb_with     = [];
        $this->qb_joins    = [];
        $this->qb_group_by = [];
        $this->qb_having   = '';
        $this->qb_select   = '*';
    }

    public function loadRelation(array &$rows, string $table, string $foreignKey, string $relatedKey, string $alias, bool $isMultiple = true, string $extraCondition = '', ?callable $constraint = null, array $nested = [], ?string $relatedClass = null) {
        if (empty($rows)) return $rows;

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

        // Apply relationship constraints
        $nestedWith = $nested;
        if ($constraint && $relatedClass) {
            $dummy = new $relatedClass();
            $constraint($dummy);
            list($wSql, $wParams) = $dummy->buildWhereClause();
            if ($wSql) {
                $sql .= " AND ($wSql)";
                $ids = array_merge($ids, $wParams);
            }
            if (!empty($dummy->qb_order)) $sql .= ' ORDER BY ' . implode(', ', $dummy->qb_order);
            if ($dummy->qb_limit !== '')  $sql .= ' LIMIT ' . $dummy->qb_limit;
            if (!empty($dummy->qb_with))  $nestedWith = array_merge($nestedWith, $dummy->qb_with);
        }

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute(array_values($ids));
        $related = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pre-hydrate nested relations recursively
        if (!empty($nestedWith) && $relatedClass && !empty($related)) {
            $relatedModels = array_map(fn($r) => new $relatedClass($r), $related);
            $dummyModel = new $relatedClass();
            foreach ($nestedWith as $nestedName => $nestedConstraint) {
                if (method_exists($dummyModel, $nestedName)) {
                    $nestedRelation = $dummyModel->$nestedName();
                    if (is_array($nestedRelation)) {
                        $nestedRelation['constraint'] = $nestedConstraint;
                        $dummyModel->handleRelation($relatedModels, $nestedName, $nestedRelation);
                    }
                }
            }
            // Serialize về mảng thuần — phải dùng toArray() để bao gồm cả relations
            $related = array_map(fn($m) => $m->toArray(), $relatedModels);
        }

        $relMap = [];
        foreach ($related as $item) {
            // Check key again from attributes if nested hydrated
            $k = is_array($item) ? $item[$relatedKey] : $item->$relatedKey;
            if ($isMultiple) { $relMap[$k][] = $item; }
            else { $relMap[$k] = $item; }
        }

        foreach ($rows as &$model) {
            $key = ($model instanceof self)
                ? ($model->attributes[$foreignKey] ?? null)
                : ($model[$foreignKey] ?? null);
            if ($model instanceof self) {
                $model->setRelation($alias, $relMap[$key] ?? ($isMultiple ? [] : null));
            } else {
                // Fallback cho array rows (legacy)
                $model[$alias] = $relMap[$key] ?? ($isMultiple ? [] : null);
            }
        }

        return $rows;
    }

    // ============================================================
    //  CASTS — Ép kiểu thuộc tính
    // ============================================================

    /**
     * Ép kiểu giá trị theo $casts config
     * Ví dụ: protected array $casts = ['is_active' => 'bool', 'meta' => 'array'];
     */
    protected function castValue($value, string $type) {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'string':
                return (string) $value;
            case 'array':
            case 'json':
                return is_string($value) ? (json_decode($value, true) ?? []) : (array)$value;
            case 'object':
                return is_string($value) ? json_decode($value) : (object)$value;
            case 'timestamp':
                return is_numeric($value) ? (int)$value : strtotime($value);
            default:
                return $value;
        }
    }

    // ============================================================
    //  SOFT DELETES — Xóa mềm (đánh dấu thay vì xóa thật)
    // ============================================================

    /**
     * Cột lưu thời điểm xóa mềm (ghi đè ở class con nếu cần)
     */
    protected string $deletedAt = 'deleted_at';

    /**
     * Xóa mềm: set deleted_at thay vì DELETE khỏi DB
     * Yêu cầu: bảng phải có cột `deleted_at` (INT hoặc TIMESTAMP)
     */
    public function softDelete(): bool {
        $this->attributes[$this->deletedAt] = time();
        return (bool) $this->save();
    }

    /**
     * Khôi phục bản ghi đã xóa mềm
     */
    public function restore(): bool {
        $this->attributes[$this->deletedAt] = null;
        return (bool) $this->save();
    }

    /**
     * Tắt tự động lọc deleted_at cho query tiếp theo
     * Dùng khi muốn lấy cả bản ghi đã xóa mềm:
     *   Model::withTrashed()->where(...)->get()
     */
    public function withTrashed() {
        // Thêm điều kiện IS NULL hoặc IS NOT NULL vào where raw
        // (Không thêm gì cả, tức là không lọc deleted_at)
        $this->_with_trashed = true;
        return $this;
    }

    /**
     * Chỉ lấy những bản ghi đã xóa mềm
     */
    public function onlyTrashed() {
        $this->qb_where[] = "`{$this->deletedAt}` IS NOT NULL";
        return $this;
    }
}
