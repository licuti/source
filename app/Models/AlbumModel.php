<?php
namespace App\Models;

class AlbumModel extends \Model {
    public $table = '#_album';
    public bool $timestamps = true;
    protected string $createdAt = 'ngay_dang';
    protected string $updatedAt = 'cap_nhat';
}
?>
