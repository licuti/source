<?php
namespace App\Models;

/**
 * Model: MenuItem
 */
class MenuItemModel extends \App\Core\Model {
    public $table = '#_menu_items';
    public bool $use_lang = false;
    public bool $timestamps = false;

    /**
     * Lấy các mục con
     */
    public function children() {
        return self::where('parent_id', $this->id)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }
}
