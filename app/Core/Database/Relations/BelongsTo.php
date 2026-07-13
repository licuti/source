<?php
namespace App\Core\Database\Relations;

use App\Core\Database\QueryBuilder;
use App\Core\Database\Model;

class BelongsTo extends Relation {
    protected string $relationName;

    public function __construct(QueryBuilder $query, Model $child, string $foreignKey, string $ownerKey, string $relationName) {
        $this->relationName = $relationName;
        parent::__construct($query, $child, $foreignKey, $ownerKey);
    }

    protected function addConstraints() {
        if ($this->parent->{$this->foreignKey} !== null) {
            $this->query->where($this->localKey, $this->parent->{$this->foreignKey});
        }
    }

    public function addEagerConstraints(array $models) {
        $keys = $this->getEagerModelKeys($models);
        $this->query->whereIn($this->localKey, $keys);
    }

    protected function getEagerModelKeys(array $models) {
        $keys = [];
        foreach ($models as $model) {
            $val = $model->{$this->foreignKey};
            if ($val !== null) $keys[] = $val;
        }
        return array_values(array_unique($keys));
    }

    public function initRelation(array $models, string $relation) {
        foreach ($models as $model) {
            $model->setRelation($relation, null);
        }
        return $models;
    }

    public function match(array $models, array $results, string $relation) {
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{$this->localKey}] = $result;
        }

        foreach ($models as $model) {
            $key = $model->{$this->foreignKey};
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }
        return $models;
    }

    public function getResults() {
        return $this->query->first();
    }
}