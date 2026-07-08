<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Response;

class BaseAdminController extends Controller {
    public function __construct() {
        // Không gọi parent::__construct() vì class cha không có
    }

    /**
     * Render giao diện kèm dữ liệu chung cho Admin
     */
    protected function render($view, $data = []) {
        // Có thể load các dữ liệu dùng chung cho view ở đây
        if (!isset($data['admin_user'])) {
            $data['admin_user'] = $_SESSION['name'] ?? 'Administrator';
        }
        
        return view($view, $data);
    }

    /**
     * Xác thực quyền chỉnh sửa/xóa bài viết hoặc object
     * Kiểm tra user hiện tại có phải là người tạo hoặc là admin (is_admin = 1)
     */
    protected function canModify($item): bool {
        if (!$item) return false;
        
        $createdBy = is_array($item) ? ($item['created_by'] ?? 0) : ($item->created_by ?? 0);
        return ($createdBy == user()->id || user()->is_admin == 1);
    }
}
