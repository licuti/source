<?php
namespace App\Models;

class PostTranslationModel extends \App\Core\Database\Model {
    public $table = '#_post_translations';

    protected array $fillable = [
        'post_id', 'lang', 'title', 'slug', 'description', 
        'content', 'seo_title', 'seo_description', 'keyword', 'tags'
    ];

    public function post() {
        return $this->belongsTo(PostModel::class, 'post_id', 'id');
    }
}
