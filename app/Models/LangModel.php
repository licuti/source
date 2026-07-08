<?php
namespace App\Models;

class LangModel extends \App\Core\Model {
    public $table = 'db_lang';
    public bool $use_lang = false;
    public bool $timestamps = false;

    /**
     * Cache the active languages so we don't query the DB multiple times per request
     */
    protected static $activeLanguages = null;

    /**
     * Get all active languages
     * 
     * @return array
     */
    public static function getActiveLanguages()
    {
        if (self::$activeLanguages === null) {
            self::$activeLanguages = self::where('is_active', 1)->orderBy('sort_order', 'ASC')->get();
        }
        return self::$activeLanguages;
    }
}
