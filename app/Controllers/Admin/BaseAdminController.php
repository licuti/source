<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Response;
use App\Models\LanguageModel;

class BaseAdminController extends Controller {
    protected $layout = 'admin.layouts.main';
    protected array $langs = [];
    protected string $primaryLang = 'vi';

    public function __construct() {
        // Lấy danh sách ngôn ngữ kích hoạt
        $activeLangs = LanguageModel::getActive();
        foreach ($activeLangs as $l) {
            $this->langs[] = ['code' => $l->code, 'name' => $l->name, 'image' => $l->image];
        }
        
        // Lấy ngôn ngữ mặc định
        $defaultLang = LanguageModel::getDefault();
        if ($defaultLang) {
            $this->primaryLang = $defaultLang->code;
        } elseif (!empty($this->langs)) {
            $this->primaryLang = $this->langs[0]['code'];
        }
    }

    /**
     * Render giao diện kèm dữ liệu chung cho Admin
     */
    protected function render($view, $data = []) {
        // Tự động inject ngôn ngữ vào View
        if (!isset($data['langs'])) {
            $data['langs'] = $this->langs;
        }
        if (!isset($data['primaryLang'])) {
            $data['primaryLang'] = $this->primaryLang;
        }

        // Có thể load các dữ liệu dùng chung cho view ở đây
        if (!isset($data['admin_user'])) {
            $data['admin_user'] = $_SESSION['name'] ?? 'Administrator';
        }
        
        return parent::render($view, $data);
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

    /**
     * Lấy tên ngôn ngữ dựa theo mã code (Helper dùng chung cho mọi Controller)
     */
    protected function getLangName(string $code): string {
        foreach ($this->langs as $l) {
            if ($l['code'] === $code) {
                return $l['name'];
            }
        }
        return 'Unknown';
    }

    /**
     * Lấy danh sách Module đang kích hoạt và đã được sắp xếp (Helper dùng chung)
     */
    protected function getActiveModules(): array {
        $allModules = config('modules.settings', []);
        $activeModules = [];
        foreach ($allModules as $m) {
            if (isset($m['status']) && $m['status'] == 1) {
                $activeModules[] = (object)$m;
            }
        }
        
        // Sắp xếp theo thứ tự hiển thị
        usort($activeModules, function($a, $b) {
            return ($a->sort_order ?? 0) <=> ($b->sort_order ?? 0);
        });
        
        return $activeModules;
    }
}
