<?php

namespace App\Core\Auth;

use App\Models\ModuleAdminModel;

class AuthManager {
    protected static $user = null;

    /**
     * Lấy thông tin user hiện tại
     */
    public static function user() {
        if (self::$user !== null) {
            return self::$user;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_user'])) {
            return null;
        }

        $user = new \stdClass();
        $user->id = $_SESSION['id_user'];
        $user->username = $_SESSION['user_admin'] ?? '';
        $user->fullname = $_SESSION['name'] ?? '';
        $user->role_id = $_SESSION['role_id'] ?? $_SESSION['quyen'] ?? 0;
        $user->is_admin = $_SESSION['is_admin'] ?? 0;

        self::$user = $user;
        return $user;
    }

    /**
     * Kiểm tra user đã đăng nhập chưa
     */
    public static function check(): bool {
        return self::user() !== null;
    }

    /**
     * Đăng xuất
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['id_user']);
        unset($_SESSION['user_admin']);
        unset($_SESSION['role_id']);
        unset($_SESSION['is_admin']);
        self::$user = null;
    }

    /**
     * Kiểm tra quyền hiện tại của User
     * 
     * @param string $permission VD: 'admin.category.view'
     * @return bool
     */
    public static function can(string $permission): bool {
        $user = self::user();
        if (!$user) return false;
        
        if ($user->is_admin == 1) return true; // Super Admin
        if ($user->role_id == 0) return false;

        // Tách chuỗi permission, ví dụ: 'admin.category.view' => route_name='admin.category', action='view'
        $parts = explode('.', $permission);
        if (count($parts) < 3) {
            // Định dạng không chuẩn
            return false;
        }

        $action = array_pop($parts); // Lấy phần tử cuối cùng làm action
        $routePrefix = implode('.', $parts);

        // Map action tới field trong database
        $actionMap = [
            'view' => 'can_view',
            'add' => 'can_add',
            'edit' => 'can_edit',
            'delete' => 'can_delete'
        ];
        
        $dbAction = $actionMap[$action] ?? 'can_view';

        // Lấy danh sách quyền từ Session cache (hoặc query DB nếu cần)
        $permsCache = $_SESSION['role_permissions'] ?? [];

        // Tìm module_id từ tên route
        // Lưu ý: Cần refactor lại cách load module để không query liên tục
        $moduleId = self::getModuleIdByRoute($routePrefix);

        if (!$moduleId || !isset($permsCache[$moduleId])) {
            return false;
        }

        return !empty($permsCache[$moduleId][$dbAction]);
    }

    /**
     * Hàm hỗ trợ nội bộ: Tìm module_id dựa trên route_prefix
     */
    protected static function getModuleIdByRoute($routePrefix) {
        static $moduleMapping = null;
        if ($moduleMapping === null) {
            $moduleMapping = [];
            try {
                $modules = ModuleAdminModel::where('is_active', 1)->get();
                foreach ($modules as $mod) {
                    if (!empty($mod->route_name)) {
                        $parts = explode('.', $mod->route_name);
                        if (count($parts) >= 2) {
                            $prefix = $parts[0] . '.' . $parts[1];
                            $moduleMapping[$prefix] = $mod->id;
                        }
                    }
                }
            } catch (\Exception $e) {}
        }

        return $moduleMapping[$routePrefix] ?? null;
    }
}
