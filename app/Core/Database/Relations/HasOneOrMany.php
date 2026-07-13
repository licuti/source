<?php
namespace App\Core\Database\Relations;

abstract class HasOneOrMany extends Relation {
    protected function addConstraints() {
        if ($this->parent->{$this->localKey} !== null) {
            $this->query->where($this->foreignKey, $this->parent->{$this->localKey});
            $this->query->whereNotNull($this->foreignKey);
        }
    }

    public function addEagerConstraints(array $models) {
        $keys = $this->getKeys($models, $this->localKey);
        $this->query->whereIn($this->foreignKey, $keys);
    }

    protected function getKeys(array $models, string $key) {
        $keys = [];
        foreach ($models as $model) {
            $val = $model->{$key};
            if ($val !== null) $keys[] = $val;
        }
        return array_values(array_unique($keys));
    }

    protected function matchOneOrMany(array $models, array $results, string $relation, string $type) {
        $dictionary = $this->buildDictionary($results);
        foreach ($models as $model) {
            $key = $model->{$this->localKey};
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $type === 'one' ? reset($dictionary[$key]) : $dictionary[$key]);
            } else {
                $model->setRelation($relation, $type === 'one' ? null : []);
            }
        }
        return $models;
    }

    protected function buildDictionary(array $results) {
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{$this->foreignKey}][] = $result;
        }
        return $dictionary;
    }
}