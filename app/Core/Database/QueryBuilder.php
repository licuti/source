<?php
namespace App\Core\Database;

use PDO;
use PDOException;

class QueryBuilder {
    protected Model $model;
    protected int $qb_skip = 0;

        protected array  $qb_where     = [];
    protected array  $scopes        = [];
    protected array  $removedScopes = [];
    protected array  $qb_or_where  = [];
    protected array  $qb_where_raw = [];
    protected array  $qb_params    = [];
    protected array  $qb_order     = [];
    protected array  $qb_with      = [];
    protected array  $qb_joins     = [];
    protected array  $qb_group_by  = [];
    protected string $qb_having    = '';
    protected string $qb_select    = '*';
    protected string $qb_limit     = '';

    public function __construct(Model $model) {
        $this->model = $model;
    }

    public function getModel(): Model { return $this->model; }
    public function getOrders() { return $this->qb_order; }
    public function getLimit() { return $this->qb_limit; }
    public function getWith() { return $this->qb_with; }
    public function getWhere() { return $this->qb_where; }

    public function __call($method, $parameters) {
        if (method_exists($this->model, $method)) {
            return $this->model->$method(...$parameters);
        }
        throw new \BadMethodCallException("Method $method does not exist on Builder or Model.");
    }

        public function withGlobalScope(string $name, $scope) {
        $this->scopes[$name] = $scope;
        return $this;
    }

    public function withoutGlobalScope(string $name) {
        $this->removedScopes[] = $name;
        return $this;
    }

    protected function applyScopes() {
        foreach ($this->scopes as $name => $scope) {
            if (!in_array($name, $this->removedScopes)) {
                $scope->apply($this);
            }
        }
        $this->scopes = []; // Ngăn apply lại nhiều lần nếu gọi hàm nhiều lần
    }

    // ── Where Conditions ─────────────────────────────────────

    public function where($column, $value = null, $operator = '=') {
        $knownOps = ['=','!=','<>','>','<','>=','<=','LIKE','NOT LIKE','IN','NOT IN','IS NULL','IS NOT NULL'];
        if (is_string($value) && in_array(strtoupper(trim($value)), $knownOps) && $operator !== '=') {
            [$value, $operator] = [$operator, $value];
        }
        $operator = strtoupper(trim($operator));
        if (in_array($operator, ['IN', 'NOT IN'])) {
            return $this->whereIn($column, (array)$value, $operator === 'NOT IN');
        }
        $this->qb_where[] = "`$column` $operator ?";
        $this->qb_params[] = $value;
        return $this;
    }

    public function orWhere($column, $value = null, $operator = '=') {
        $knownOps = ['=','!=','<>','>','<','>=','<=','LIKE','NOT LIKE'];
        if (is_string($value) && in_array(strtoupper(trim($value)), $knownOps) && $operator !== '=') {
            [$value, $operator] = [$operator, $value];
        }
        $operator = strtoupper(trim($operator));
        $this->qb_or_where[] = "`$column` $operator ?";
        $this->qb_params[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values, bool $notIn = false) {
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

    public function whereNotIn(string $column, array $values) {
        return $this->whereIn($column, $values, true);
    }

    public function whereBetween(string $column, array $range) {
        $this->qb_where[] = "`$column` BETWEEN ? AND ?";
        $this->qb_params[] = $range[0];
        $this->qb_params[] = $range[1];
        return $this;
    }

    public function whereRaw(string $condition, array $params = []) {
        $this->qb_where_raw[] = $condition;
        if (!empty($params)) {
            $this->qb_params = array_merge($this->qb_params, array_values($params));
        }
        return $this;
    }

    public function whereLike(string $column, string $value) {
        if (strpos($value, '%') === false) { $value = '%' . $value . '%'; }
        $this->qb_where[] = "`$column` LIKE ?";
        $this->qb_params[] = $value;
        return $this;
    }

    public function whereNotLike(string $column, string $value) {
        if (strpos($value, '%') === false) { $value = '%' . $value . '%'; }
        $this->qb_where[] = "`$column` NOT LIKE ?";
        $this->qb_params[] = $value;
        return $this;
    }

    public function whereNull(string $column) {
        $this->qb_where[] = "`$column` IS NULL";
        return $this;
    }

    public function whereNotNull(string $column) {
        $this->qb_where[] = "`$column` IS NOT NULL";
        return $this;
    }

    // ── Ordering & Pagination ────────────────────────────────

    public function orderBy(string $column, string $direction = 'ASC') {
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $column)) return $this;
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->qb_order[] = "`$column` $direction";
        return $this;
    }

    public function latest(string $column = 'id') { return $this->orderBy($column, 'DESC'); }
    public function oldest(string $column = 'id') { return $this->orderBy($column, 'ASC'); }

    public function limit(int $limit, int $offset = 0) {
        $this->qb_limit = $offset > 0 ? "$offset, $limit" : (string)$limit;
        return $this;
    }

    public function skip(int $offset) { $this->qb_skip = $offset; return $this; }
    public function take(int $limit) { return $this->limit($limit, $this->qb_skip); }

    // ── Select & Group ───────────────────────────────────────

    public function select(...$columns) {
        $cols = [];
        foreach ($columns as $c) {
            $cols = array_merge($cols, is_array($c) ? $c : [$c]);
        }
        $this->qb_select = implode(', ', $cols);
        return $this;
    }

    public function groupBy(string ...$columns) {
        foreach ($columns as $col) { $this->qb_group_by[] = "`$col`"; }
        return $this;
    }

    public function having(string $rawCondition) {
        $this->qb_having = $rawCondition;
        return $this;
    }

    // ── Joins ────────────────────────────────────────────────

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER') {
        $table = str_replace('#_', Model::getPrefix(), $table);
        $this->qb_joins[] = "$type JOIN $table ON $first $operator $second";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second) {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    // ── Conditional & Debug ──────────────────────────────────

    public function qbWhen(bool $condition, callable $callback) {
        if ($condition) $callback($this);
        return $this;
    }

    public function toSql(): string {
        $tableName = $this->model->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        $sql = "SELECT {$this->qb_select} FROM $tableName";
        if (!empty($this->qb_joins))    $sql .= ' ' . implode(' ', $this->qb_joins);
        if ($whereSql)                  $sql .= " WHERE $whereSql";
        if (!empty($this->qb_group_by)) $sql .= ' GROUP BY ' . implode(', ', $this->qb_group_by);
        if ($this->qb_having !== '')    $sql .= ' HAVING ' . $this->qb_having;
        if (!empty($this->qb_order))    $sql .= ' ORDER BY ' . implode(', ', $this->qb_order);
        if ($this->qb_limit !== '')     $sql .= ' LIMIT ' . $this->qb_limit;
        foreach ($params as $p) {
            $p = is_string($p) ? "'$p'" : $p;
            $sql = preg_replace('/\?/', (string)$p, $sql, 1);
        }
        return $sql;
    }

    public function setTable(string $table) {
        $this->model->table = $table;
        return $this;
    }

    // ── Eager Loading ────────────────────────────────────────

    public function with($relations) {
        if (is_string($relations)) $relations = func_get_args();
        foreach ($relations as $key => $value) {
            if (is_int($key)) {
                $this->qb_with[$value] = null;
            } else {
                $this->qb_with[$key] = $value;
            }
        }
        return $this;
    }

    // ── Query Execution ──────────────────────────────────────

    public function get(string $columns = '*'): array {
        $tableName = $this->model->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        $selectCols = ($this->qb_select !== '*') ? $this->qb_select : $columns;

        $sql = "SELECT $selectCols FROM $tableName";
        if (!empty($this->qb_joins))    $sql .= ' ' . implode(' ', $this->qb_joins);
        if ($whereSql)                  $sql .= " WHERE $whereSql";
        if (!empty($this->qb_group_by)) $sql .= ' GROUP BY ' . implode(', ', $this->qb_group_by);
        if ($this->qb_having !== '')    $sql .= ' HAVING ' . $this->qb_having;
        if (!empty($this->qb_order))    $sql .= ' ORDER BY ' . implode(', ', $this->qb_order);
        if ($this->qb_limit !== '')     $sql .= ' LIMIT ' . $this->qb_limit;

        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $models = array_map(fn($row) => $this->model->newInstance($row), $rows);

        if (!empty($this->qb_with) && !empty($models)) {
            $this->eagerLoadRelations($models);
        }

        $this->resetQB();
        return $models;
    }

    protected function eagerLoadRelations(array &$models): void {
        foreach ($this->qb_with as $relationName => $constraint) {
            if (!method_exists($this->model, $relationName)) continue;

            $relation = $this->model->$relationName();
            if (!($relation instanceof Relations\Relation)) continue;

            $relation->addEagerConstraints($models);
            if (is_callable($constraint)) {
                $constraint($relation->getQuery());
            }
            $models = $relation->initRelation($models, $relationName);
            $results = $relation->getResults();
            if (!is_array($results)) {
                $results = $results ? [$results] : [];
            }
            $models = $relation->match($models, $results, $relationName);
        }
    }

    public function count(): int {
        $tableName = $this->model->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        $sql = "SELECT COUNT(*) as total FROM $tableName";
        if (!empty($this->qb_joins)) $sql .= ' ' . implode(' ', $this->qb_joins);
        if ($whereSql) $sql .= " WHERE $whereSql";
        if (!empty($this->qb_group_by)) $sql .= ' GROUP BY ' . implode(', ', $this->qb_group_by);
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->resetQB();
        return (int)$result['total'];
    }

    public function qbSum(string $column): float {
        $tableName = $this->model->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        $sql = "SELECT SUM(`$column`) as total FROM $tableName";
        if (!empty($this->qb_joins)) $sql .= ' ' . implode(' ', $this->qb_joins);
        if ($whereSql) $sql .= " WHERE $whereSql";
        if (!empty($this->qb_group_by)) $sql .= ' GROUP BY ' . implode(', ', $this->qb_group_by);
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->resetQB();
        return (float)($result['total'] ?? 0);
    }

    // ── Single-Record Retrieval ──────────────────────────────

    public function find($id, string $columns = '*') {
        return $this->where('id', $id)->first($columns);
    }

    public function first(string $columns = '*') {
        $results = $this->limit(1)->get($columns);
        return $results[0] ?? null;
    }

    public function findOrFail($id, string $columns = '*') {
        $result = $this->find($id, $columns);
        if (!$result) throw new \RuntimeException("Record #{$id} not found.");
        return $result;
    }

    public function firstOrFail(string $columns = '*') {
        $result = $this->first($columns);
        if (!$result) throw new \RuntimeException('Record not found.');
        return $result;
    }

    // ── Collection Retrieval ─────────────────────────────────

    public function pluck(string $column, ?string $keyBy = null): array {
        $cols = $keyBy ? "$column, $keyBy" : $column;
        $rows = $this->get($cols);
        $result = [];
        foreach ($rows as $row) {
            $val = $row->$column;
            if ($keyBy) { $result[$row->$keyBy] = $val; }
            else { $result[] = $val; }
        }
        return $result;
    }

    public function columnValue(string $column) {
        $result = $this->first($column);
        return $result ? $result->$column : null;
    }

    public function exists(): bool {
        $tableName = $this->model->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        $sql = "SELECT 1 FROM $tableName";
        if (!empty($this->qb_joins)) $sql .= ' ' . implode(' ', $this->qb_joins);
        if ($whereSql) $sql .= " WHERE $whereSql";
        $sql .= ' LIMIT 1';
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute($params);
        $this->resetQB();
        return (bool)$stmt->fetch();
    }

    public function qbChunk(int $size, callable $callback): void {
        $page = 1;
        $snap = [
            'where' => $this->qb_where, 'or_where' => $this->qb_or_where,
            'where_raw' => $this->qb_where_raw, 'params' => $this->qb_params,
            'joins' => $this->qb_joins, 'order' => $this->qb_order, 'group_by' => $this->qb_group_by,
        ];
        do {
            foreach ($snap as $k => $v) $this->{'qb_' . $k} = $v;
            $results = $this->limit($size, ($page - 1) * $size)->get();
            if (empty($results)) break;
            if ($callback($results) === false) break;
            $page++;
        } while (count($results) === $size);
    }

    public function paginate(int $perPage = 15, int $page = 0, string $pageName = 'page') {
        if ($page < 1) $page = max(1, (int)($_GET[$pageName] ?? 1));
        $snap = [
            'where' => $this->qb_where, 'or_where' => $this->qb_or_where,
            'where_raw' => $this->qb_where_raw, 'params' => $this->qb_params,
            'joins' => $this->qb_joins, 'order' => $this->qb_order,
            'group_by' => $this->qb_group_by, 'with' => $this->qb_with,
        ];
        $total = $this->count();
        foreach ($snap as $k => $v) $this->{'qb_' . $k} = $v;
        $results = $this->limit($perPage, ($page - 1) * $perPage)->get();
        return new \App\Core\Paginator($results, $total, $perPage, $page);
    }

    // ── Mass Operations (WHERE-based) ────────────────────────

    public function increment(string $column, int $amount = 1) {
        $tableName = $this->model->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        $sql = "UPDATE $tableName SET `$column` = `$column` + $amount";
        if ($whereSql) $sql .= " WHERE $whereSql";
        $stmt = Model::getConnection()->prepare($sql);
        $result = $stmt->execute($params);
        $this->resetQB();
        return $result;
    }

    public function decrement(string $column, int $amount = 1) {
        return $this->increment($column, -$amount);
    }

    public function update(array $data) {
        if (empty($data)) return false;
        $tableName = $this->model->tableName();
        list($whereSql, $params) = $this->buildWhereClause();

        if ($this->model->timestamps) {
            $data[$this->model->getUpdatedAtColumn()] = date('Y-m-d H:i:s');
        }

        $fields = []; $updateParams = [];
        foreach ($data as $col => $val) {
            $fields[] = "`$col` = ?";
            $updateParams[] = $val;
        }
        $sql = "UPDATE $tableName SET " . implode(', ', $fields);
        if ($whereSql) {
            $sql .= " WHERE $whereSql";
            $updateParams = array_merge($updateParams, $params);
        }
        $stmt = Model::getConnection()->prepare($sql);
        $result = $stmt->execute($updateParams);
        $this->resetQB();
        return $result;
    }

    public function delete() {
        $tableName = $this->model->tableName();
        list($whereSql, $params) = $this->buildWhereClause();
        $sql = "DELETE FROM $tableName";
        if ($whereSql) $sql .= " WHERE $whereSql";
        $stmt = Model::getConnection()->prepare($sql);
        $result = $stmt->execute($params);
        $this->resetQB();
        return $result;
    }

    // ── Internal ─────────────────────────────────────────────

        public function buildWhereClause(): array {
        $this->applyScopes();
        $andParts = array_merge($this->qb_where, $this->qb_where_raw);
        $andSql   = implode(' AND ', $andParts);

        $orSql = '';
        if (!empty($this->qb_or_where)) {
            $orSql = '(' . implode(' OR ', $this->qb_or_where) . ')';
        }

        if ($andSql && $orSql) {
            $whereSql = "($andSql) OR $orSql";
        } else {
            $whereSql = $andSql ?: $orSql;
        }

        return [$whereSql, $this->qb_params];
    }

    public function resetQB() {
        $this->qb_where     = [];
        $this->qb_or_where  = [];
        $this->qb_where_raw = [];
        $this->qb_params    = [];
        $this->qb_order     = [];
        $this->qb_limit     = '';
        $this->qb_with      = [];
        $this->qb_joins     = [];
        $this->qb_group_by  = [];
        $this->qb_having    = '';
        $this->qb_select    = '*';
    }
}
