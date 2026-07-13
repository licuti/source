<?php

namespace App\Traits;

trait HasLanguage {
    public static function bootHasLanguage() {
        // Tự động lấy ngôn ngữ hiện tại từ config hoặc mặc định là vi
        $langCode = config('app.locale', 'vi');
        static::addGlobalScope('lang', new \App\Models\Scopes\LangScope($langCode));
    }
}
