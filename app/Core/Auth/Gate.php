<?php

namespace App\Core\Auth;

use App\Models\ModuleAdminModel;
use App\Models\RolePermissionModel;

/**
 * Gate - Trung tâm kiểm tra phân quyền RBAC
 *
 * Cả Middleware (bảo vệ route) và hasPermission() helper (ẩn/hiện UI)
 * đều delegate về đây — chỉ một nguồn logic duy nhất (DRY).
 *
 * Gate không biết session là gì — nó chỉ giao tiếp qua Auth service.
 */
class Gate {
    /**
     * Cache module_id theo route prefix trong một request để tránh query lặp
     */
    private static array $moduleCache = [];

    /**
     * Kiểm tra quyền truy cập
     *
     * @param string $modulePrefix VD: 'admin.category'
     * @param string $action  'index'|'show'|'create'|'store'|'edit'|'update'|'destroy'|...
     */
    public static function check(string $modulePrefix, string $action): bool {
        // 1. Super Admin vượt qua mọi check
        if (Auth::isSuperAdmin()) {
            return true;
        }

        // 2. Resolve module_id (có cache trong request)
        $moduleId = self::resolveModuleId($modulePrefix);
        if (!$moduleId) {
            return false;
        }

        // 3. Lấy permission object
        $perm = self::resolvePerm($moduleId);
        if (!$perm) {
            return false;
        }

        // 4. Map action -> cột quyền
        return self::isActionAllowed($perm, $action);
    }

    // ──────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────

    private static function resolveModuleId(string $prefix): ?int {
        if (!array_key_exists($prefix, self::$moduleCache)) {
            $module = ModuleAdminModel::where('route_name', 'LIKE', $prefix . '.%')->first();
            self::$moduleCache[$prefix] = $module ? $module->id : null;
        }
        return self::$moduleCache[$prefix];
    }

    private static function resolvePerm(int $moduleId): ?object {
        // Ưu tiên dùng cache trong session
        $cache = Auth::permissionsCache();
        if (isset($cache[$moduleId])) {
            return (object) $cache[$moduleId];
        }

        // Fallback query DB
        $roleId = Auth::roleId();
        if (!$roleId) return null;

        return RolePermissionModel::where('role_id', $roleId)
                                   ->where('module_id', $moduleId)
                                   ->first();
    }

    private static function isActionAllowed(object $perm, string $action): bool {
        $viewActions   = ['index', 'show', 'view'];
        $addActions    = ['create', 'store', 'add'];
        $editActions   = ['edit', 'update', 'updateStatusAjax', 'toggle_status', 'updateSortAjax'];
        $deleteActions = ['destroy', 'destroy_multiple', 'delete'];

        if (in_array($action, $viewActions))   return $perm->can_view   == 1;
        if (in_array($action, $addActions))    return $perm->can_add    == 1;
        if (in_array($action, $editActions))   return $perm->can_edit   == 1;
        if (in_array($action, $deleteActions)) return $perm->can_delete == 1;

        // Fallback: action không xác định → xem có quyền view không
        return $perm->can_view == 1;
    }
}
