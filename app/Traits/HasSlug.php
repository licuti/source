<?php

namespace App\Traits;

trait HasSlug {
    public function generateSlug(string $sourceString, string $separator = '-'): string {
        $slug = mb_strtolower($sourceString, 'UTF-8');
        
        $unicodeMap = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        ];
        
        foreach ($unicodeMap as $nonUnicode => $unicodePattern) {
            $slug = preg_replace("/($unicodePattern)/i", $nonUnicode, $slug);
        }
        
        $slug = preg_replace('/[^a-z0-9\-]/', ' ', $slug);
        $slug = preg_replace('/\s+/', $separator, trim($slug));
        
        return $slug;
    }

    public function setSlugAttribute($value) {
        if (empty($value) && !empty($this->attributes['title'])) {
            $this->attributes['slug'] = $this->generateSlug($this->attributes['title']);
        } else {
            $this->attributes['slug'] = $this->generateSlug($value);
        }
    }
}
