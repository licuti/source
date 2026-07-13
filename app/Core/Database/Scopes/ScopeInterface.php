<?php

namespace App\Core\Database\Scopes;

interface ScopeInterface {
    /**
     * @param mixed $builder
     * @return void
     */
    public function apply($builder);
}
