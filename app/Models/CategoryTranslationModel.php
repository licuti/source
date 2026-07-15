<?php
namespace App\Models;

use App\Core\Database\Model;

class CategoryTranslationModel extends Model {
    public $table = '#_category_translations';
    public bool $timestamps = false;

    protected array $fillable = [
        'category_id',
        'lang',
        'title',
        'slug',
        'description',
        'content',
        'seo_title',
        'keyword',
        'seo_description',
        'seo_head',
        'seo_body',
        'seo_schema',
        'seo_canonical'
    ];
}
