<?php
namespace App\Core\Database\Relations;

use App\Core\Database\QueryBuilder;
use App\Core\Database\Model;

abstract class Relation {
    protected QueryBuilder $query;
    protected Model $parent;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(QueryBuilder $query, Model $parent, string $foreignKey, string $localKey) {
        $this->query = $query;
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->addConstraints();
    }

    public function getQuery(): QueryBuilder {
        return $this->query;
    }

    // Proxy method calls to QueryBuilder
    public function __call($method, $parameters) {
        $result = $this->query->$method(...$parameters);
        if ($result === $this->query) {
            return $this;
        }
        return $result;
    }

    abstract protected function addConstraints();
    abstract public function addEagerConstraints(array $models);
    abstract public function initRelation(array $models, string $relation);
    abstract public function match(array $models, array $results, string $relation);
    abstract public function getResults();
}