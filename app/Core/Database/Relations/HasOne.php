<?php
namespace App\Core\Database\Relations;

class HasOne extends HasOneOrMany {
    public function getResults() {
        return $this->query->first();
    }

    public function initRelation(array $models, string $relation) {
        foreach ($models as $model) {
            $model->setRelation($relation, null);
        }
        return $models;
    }

    public function match(array $models, array $results, string $relation) {
        return $this->matchOneOrMany($models, $results, $relation, 'one');
    }
}