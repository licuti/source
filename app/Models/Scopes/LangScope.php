<?php

namespace App\Models\Scopes;

use App\Core\Database\Scopes\ScopeInterface;

class LangScope implements ScopeInterface {
    protected $langCode;

    public function __construct(string $langCode) {
        $this->langCode = $langCode;
    }

    /**
     * @param \App\Core\Database\QueryBuilder $builder
     * @return void
     */
    public function apply($builder) {
        $builder->where('lang', $this->langCode);
    }
}
