<?php
namespace App\Models;

class VideoModel extends \App\Core\Model {
    public $table = '#_video';
    public bool $timestamps = true;
    protected string $createdAt = 'ngay_dang';
    protected string $updatedAt = 'cap_nhat';
}
?>
