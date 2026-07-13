<?php
namespace App\Models;

class CouponModel extends \App\Core\Database\Model {
    use \App\Traits\HasLanguage;
    public $table = '#_khuyenmai';

    // TODO: Phát triển các hàm xử lý Mã giảm giá (Kiểm tra hết hạn, áp dụng mã...)
}
?>
