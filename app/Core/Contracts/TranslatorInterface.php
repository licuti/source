<?php

namespace App\Core\Contracts;

interface TranslatorInterface {
    /**
     * Dịch một từ khóa sang ngôn ngữ hiện tại.
     *
     * @param string $key
     * @return string
     */
    public function translate(string $key): string;
}
