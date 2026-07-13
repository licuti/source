<?php

namespace App\Core\Auth;

/**
 * Auth - Lớp truy xuất thông tin người dùng hiện tại
 *
 * Đây là lớp trung gian giữa Gate và nguồn lưu trữ session.
 * Nếu sau này chuyển sang Token (JWT) thì chỉ cần sửa ở đây,
 * Gate và mọi nơi gọi Auth::xxx() sẽ không bị ảnh hưởng.
 */
class Auth {
    /**
     * Đảm bảo session đã được khởi tạo
     */
    private static function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Kiểm tra user đã đăng nhập chưa
     */
    public static function check(): bool {
        self::ensureSession();
        return isset($_SESSION['id_user']);
    }

    /**
     * Lấy ID của user hiện tại
     */
    public static function id(): ?int {
        self::ensureSession();
        return isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : null;
    }

    /**
     * Kiểm tra có phải Super Admin không
     */
    public static function isSuperAdmin(): bool {
        self::ensureSession();
        return (($_SESSION['is_admin'] ?? 0) == 1);
    }

    /**
     * Lấy role_id của user hiện tại
     * Tự động fallback query DB nếu chưa có trong session
     */
    public static function roleId(): int {
        self::ensureSession();

        $roleId = $_SESSION['role_id'] ?? 0;
        if (!empty($roleId)) {
            return (int)$roleId;
        }

        $userId = self::id();
        if (!$userId) return 0;

        $user = \App\Models\UserModel::find($userId);
        if ($user) {
            $_SESSION['role_id'] = $user->role_id;
            return (int)$user->role_id;
        }

        return 0;
    }

    /**
     * Lấy permission cache từ session
     * @return array<int, array{can_view: int, can_add: int, can_edit: int, can_delete: int}>
     */
    public static function permissionsCache(): array {
        self::ensureSession();
        return $_SESSION['role_permissions'] ?? [];
    }
}
