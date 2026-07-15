<?php
namespace App\Core\Database;

use PDO;

class Model implements \JsonSerializable {

    protected static $pdo = null;
    protected static $prefix = 'db_';
        protected static array $globalScopes = [];
    protected static array $booted = [];
    protected static $container = null;

    public $table = '';
    public bool $timestamps = true;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    public $attributes = [];
    protected array $relations = [];
    protected array $fillable = [];
    protected array $guarded  = ['id'];
    protected array $hidden   = [];
    protected array $visible  = [];
    protected array $casts    = [];
    public bool $wasRecentlyCreated = false;

    // ── Connection & Container ───────────────────────────────

    public static function boot(array $config) {
        if (self::$pdo !== null) return;
        $dsn = "mysql:host={$config['servername']};dbname={$config['database']};charset=utf8";
        self::$pdo = new PDO($dsn, $config['username'], $config['password']);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec("set names utf8");
        self::$prefix = $config['refix'] ?? 'db_';
    }

    public static function getConnection() {
        return self::$pdo;
    }

    public static function getPrefix(): string {
        return self::$prefix;
    }

    public static function setContainer($container) { self::$container = $container; }
    public static function getContainer() { return self::$container; }

        public static function addGlobalScope($name, Scopes\ScopeInterface $scope) {
        static::$globalScopes[static::class][$name] = $scope;
    }

    // ── Lifecycle ────────────────────────────────────────────

        public function __construct($attributes = []) {
        $this->bootIfNotBooted();
        $this->forceFill($attributes);
    }

    protected function bootIfNotBooted() {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;
            static::bootModel();
        }
    }

    protected static function bootModel() {
        static::bootTraits();
    }

    protected static function bootTraits() {
        $class = static::class;
        // Mặc dù class_uses chỉ quét trait ở class hiện tại (không quét class cha), 
        // nhưng với cấu trúc của mình như vậy là đủ
        foreach (class_uses($class) as $trait) {
            $method = 'boot' . basename(str_replace('\\', '/', $trait));
            if (method_exists($class, $method)) {
                forward_static_call([$class, $method]);
            }
        }
    }

    public function newInstance($attributes = [], $exists = false) {
        $model = new static((array)$attributes);
        $model->wasRecentlyCreated = !$exists;
        return $model;
    }

    public function newQuery(): QueryBuilder {
        $builder = new QueryBuilder($this);
        $scopes = static::$globalScopes[static::class] ?? [];
        foreach ($scopes as $name => $scope) {
            $builder->withGlobalScope($name, $scope);
        }
        return $builder;
    }

    public static function tableName(): string {
        $instance = new static();
        return str_replace('#_', self::$prefix, $instance->table);
    }

    public function getCreatedAtColumn(): string { return $this->createdAt; }
    public function getUpdatedAtColumn(): string { return $this->updatedAt; }

    // ── Magic Properties (Accessors / Mutators / Casts) ──────

    public function __get($key) {
        if (method_exists($this, 'isTranslatedAttribute') && $this->isTranslatedAttribute($key)) {
            return $this->getTranslatedAttribute($key);
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }
        $accessor = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->$accessor($this->attributes[$key] ?? null);
        }
        $val = $this->attributes[$key] ?? null;
        if ($val !== null && isset($this->casts[$key])) {
            return $this->castValue($val, $this->casts[$key]);
        }
        return $val;
    }

    public function __set($key, $value) {
        if (method_exists($this, 'isTranslatedAttribute') && $this->isTranslatedAttribute($key)) {
            $this->setTranslatedAttribute($key, $value);
            return;
        }

        $mutator = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $this->$mutator($value);
        } else {
            $this->attributes[$key] = $value;
        }
    }

    public function __isset($key) {
        if (method_exists($this, 'isTranslatedAttribute') && $this->isTranslatedAttribute($key)) {
            return $this->getTranslatedAttribute($key) !== null;
        }
        return array_key_exists($key, $this->relations) || isset($this->attributes[$key]);
    }

    // ── Relations ────────────────────────────────────────────

    public function setRelation(string $name, $value): self {
        $this->relations[$name] = $value;
        return $this;
    }

    public function getRelation(string $name, $default = null) {
        return $this->relations[$name] ?? $default;
    }

    public function getRelations(): array { return $this->relations; }

    public function relationLoaded(string $name): bool {
        return array_key_exists($name, $this->relations);
    }

    public function hasOne($related, $foreignKey = null, $localKey = null) {
        $instance = new $related;
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: 'id';
        return new Relations\HasOne($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    public function hasMany($related, $foreignKey = null, $localKey = null) {
        $instance = new $related;
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: 'id';
        return new Relations\HasMany($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null) {
        if (is_null($relation)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $relation = $trace[1]['function'];
        }
        $instance = new $related;
        $foreignKey = $foreignKey ?: $instance->getForeignKey();
        $ownerKey = $ownerKey ?: 'id';
        return new Relations\BelongsTo($instance->newQuery(), $this, $foreignKey, $ownerKey, $relation);
    }

    public function getForeignKey() {
        $classParts = explode('\\', static::class);
        $class = end($classParts);
        $name = str_replace('Model', '', $class);
        return strtolower($name) . '_id';
    }

    // ── Mass Assignment ──────────────────────────────────────

    public function fill(array $attributes) {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    protected function forceFill(array $attributes) {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    protected function isFillable(string $key): bool {
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }
        return !in_array($key, $this->guarded);
    }

    // ── Serialization ────────────────────────────────────────

    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        return $this->toArray();
    }

    public function toArray(): array {
        $data = $this->attributes;
        if (method_exists($this, 'getTranslatedAttributesArray')) {
            $data = array_merge($data, $this->getTranslatedAttributesArray());
        }
        foreach ($this->relations as $key => $val) {
            if (is_array($val)) {
                $data[$key] = array_map(fn($v) => ($v instanceof self) ? $v->toArray() : $v, $val);
            } elseif ($val instanceof self) {
                $data[$key] = $val->toArray();
            } else {
                $data[$key] = $val;
            }
        }
        if (!empty($this->visible)) {
            $data = array_intersect_key($data, array_flip($this->visible));
        }
        if (!empty($this->hidden)) {
            $data = array_diff_key($data, array_flip($this->hidden));
        }
        return $data;
    }

    public function toJson(int $options = 0): string {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | $options);
    }

    // ── Casts ────────────────────────────────────────────────

    protected function castValue($value, string $type) {
        switch ($type) {
            case 'int':
            case 'integer':   return (int) $value;
            case 'float':
            case 'double':    return (float) $value;
            case 'bool':
            case 'boolean':   return (bool) $value;
            case 'string':    return (string) $value;
            case 'array':
            case 'json':      return is_string($value) ? (json_decode($value, true) ?? []) : (array)$value;
            case 'object':    return is_string($value) ? json_decode($value) : (object)$value;
            case 'timestamp': return is_numeric($value) ? (int)$value : strtotime($value);
            default:          return $value;
        }
    }

    // ── CRUD (Single Record) ─────────────────────────────────

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

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            if (!$id) {
                $this->attributes[$this->createdAt] = $now;
                $data[$this->createdAt] = $now;
            }
            $this->attributes[$this->updatedAt] = $now;
            $data[$this->updatedAt] = $now;
        }

        if ($isUpdate) {
            unset($data['id']);
            $fields = []; $values = [];
            foreach ($data as $col => $val) { $fields[] = "`$col` = ?"; $values[] = $val; }
            $values[] = $id;
            $sql = "UPDATE $tableName SET " . implode(', ', $fields) . " WHERE id = ?";
            $result = self::getConnection()->prepare($sql)->execute($values);
            if ($result) { $this->fireEvent('updated'); $this->fireEvent('saved'); }
            return $result;
        }

        $cols = implode('`, `', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $tableName (`$cols`) VALUES ($placeholders)";
        $stmt = self::getConnection()->prepare($sql);
        $result = $stmt->execute(array_values($data));
        if ($result) {
            $this->id = self::getConnection()->lastInsertId();
            $this->attributes['id'] = $this->id;
            $this->wasRecentlyCreated = true;
            $this->fireEvent('created');
            $this->fireEvent('saved');
        }
        return $result;
    }

    public static function create(array $attributes) {
        $model = new static();
        $model->fill($attributes);
        $model->save();
        return $model;
    }

    public function update(array $attributes) {
        return $this->fill($attributes)->save();
    }

    public static function insert(array $data) {
        $instance = new static();
        $tableName = $instance->tableName();
        if ($instance->timestamps) {
            $now = date('Y-m-d H:i:s');
            $data[$instance->createdAt] = $data[$instance->createdAt] ?? $now;
            $data[$instance->updatedAt] = $data[$instance->updatedAt] ?? $now;
        }
        $cols = implode('`, `', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $tableName (`$cols`) VALUES ($placeholders)";
        $stmt = self::getConnection()->prepare($sql);
        $result = $stmt->execute(array_values($data));
        return $result ? (int) self::getConnection()->lastInsertId() : false;
    }

    public static function insertGetId(array $data) {
        return self::insert($data);
    }

    public function delete() {
        if (!isset($this->attributes['id'])) return false;
        if (!$this->fireEvent('deleting')) return false;
        $sql = "DELETE FROM " . $this->tableName() . " WHERE id = ?";
        $result = self::getConnection()->prepare($sql)->execute([$this->id]);
        if ($result) { $this->fireEvent('deleted'); }
        return $result;
    }

    public static function destroy($id): bool {
        $ids = is_array($id) ? $id : [$id];
        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) return false;
        $tableName = (new static())->tableName();
        $ph = implode(',', array_fill(0, count($ids), '?'));
        return self::getConnection()->prepare("DELETE FROM $tableName WHERE id IN ($ph)")
                     ->execute(array_values($ids));
    }

    public static function firstOrCreate(array $search, array $extra = []) {
        $qb = static::query();
        foreach ($search as $col => $val) { $qb->where($col, $val); }
        $found = $qb->first();
        return $found ?: static::create(array_merge($search, $extra));
    }

    public static function updateOrCreate(array $search, array $data = []) {
        $qb = static::query();
        foreach ($search as $col => $val) { $qb->where($col, $val); }
        $found = $qb->first();
        if ($found) {
            $found->fill($data)->save();
            return $found;
        }
        return static::create(array_merge($search, $data));
    }

    // ── Events ───────────────────────────────────────────────

    protected function fireEvent(string $event): bool {
        if (method_exists($this, $event)) {
            return $this->$event() !== false;
        }
        return true;
    }

    // ── Query Entry Points ───────────────────────────────────

    public static function query() {
        return (new static())->newQuery();
    }

    public static function all($columns = '*') {
        return static::get($columns);
    }

    public static function find($id, string $columns = '*') {
        return (new static())->newQuery()->find($id, $columns);
    }

    public static function __callStatic($method, $parameters) {
        return (new static())->newQuery()->$method(...$parameters);
    }

    public function __call($method, $parameters) {
        // Query Scopes: scopeActive() → active()
        $scopeMethod = 'scope' . ucfirst($method);
        if (method_exists($this, $scopeMethod)) {
            array_unshift($parameters, $this->newQuery());
            return $this->$scopeMethod(...$parameters) ?? $this;
        }
        // Magic: withCategory() → with('category')
        if (strpos($method, 'with') === 0 && strlen($method) > 4) {
            $relation = lcfirst(substr($method, 4));
            if (method_exists($this, $relation)) {
                return $this->newQuery()->with($relation);
            }
        }
        return $this->newQuery()->$method(...$parameters);
    }
}