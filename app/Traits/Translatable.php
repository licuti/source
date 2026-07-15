<?php

namespace App\Traits;

trait Translatable {
    /**
     * Kiểm tra xem key có nằm trong danh sách các trường được dịch không.
     */
    public function isTranslatedAttribute(string $key): bool {
        return property_exists($this, 'translatedAttributes') && in_array($key, $this->translatedAttributes);
    }

    /**
     * Lấy giá trị dịch cho ngôn ngữ hiện tại.
     */
    public function getTranslatedAttribute(string $key) {
        $translation = $this->getTranslation();
        return $translation ? $translation->$key : null;
    }

    /**
     * Cập nhật giá trị dịch (trong RAM)
     */
    public function setTranslatedAttribute(string $key, $value) {
        $translation = $this->getTranslation();
        if ($translation) {
            $translation->$key = $value;
        }
    }

    /**
     * Lấy mảng các trường dịch (dành cho hàm toArray của Model)
     */
    public function getTranslatedAttributesArray(): array {
        $data = [];
        if (!property_exists($this, 'translatedAttributes')) {
            return $data;
        }
        $translation = $this->getTranslation();
        if ($translation) {
            foreach ($this->translatedAttributes as $key) {
                $data[$key] = $translation->$key;
            }
        }
        return $data;
    }

    /**
     * Lấy bản dịch cho một ngôn ngữ cụ thể (mặc định là ngôn ngữ hiện tại)
     */
    public function getTranslation(?string $lang = null) {
        $lang = $lang ?: config('app.locale', 'vi');
        
        // Nếu đã eager load `translations`, tìm trong mảng đó (tránh N+1)
        if ($this->relationLoaded('translations')) {
            $translations = $this->getRelation('translations');
            if (is_array($translations)) {
                foreach ($translations as $t) {
                    if ($t->lang === $lang) return $t;
                }
            }
            return null; // Đã load nhưng không có ngôn ngữ này
        }

        // Nếu chưa load, truy vấn trực tiếp từ DB
        $class = $this->getTranslationClass();
        $foreignKey = $this->getForeignKey();
        $instance = new $class;
        $t = $instance->newQuery()
            ->where($foreignKey, $this->attributes['id'] ?? 0)
            ->where('lang', $lang)
            ->first();

        return $t;
    }

    /**
     * Quan hệ 1-N: Một bảng chính có nhiều bản dịch
     */
    public function translations() {
        return $this->hasMany($this->getTranslationClass(), $this->getForeignKey(), 'id');
    }

    /**
     * Quan hệ 1-1: Chỉ load ngôn ngữ hiện hành (Hữu ích khi cần query có join/with nhanh)
     */
    public function translation() {
        $lang = config('app.locale', 'vi');
        return $this->hasOne($this->getTranslationClass(), $this->getForeignKey(), 'id')
                    ->where('lang', $lang);
    }

    /**
     * Lấy tên class Model dịch. Mặc định là TênModel + 'TranslationModel'
     */
    protected function getTranslationClass(): string {
        $class = static::class;
        if (substr($class, -5) === 'Model') {
            return substr($class, 0, -5) . 'TranslationModel';
        }
        return $class . 'TranslationModel';
    }
}
