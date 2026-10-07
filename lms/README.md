# LMS MasterStudy — thư mục `lms/`

Portal LMS (Admin / Giáo viên / Học viên) — **tách biệt hoàn toàn** khỏi e-commerce
trong `admin/` (quyết định D2 + D3, plan `1791257567462-lms-masterstudy-php-extend.md`).

> **Nguyên tắc:** KHÔNG sửa/chỉnh file trong `admin/`, KHÔNG đụng bảng `db_*`
> e-commerce. Mọi bảng LMS dùng prefix `db_lms_`.

## Cấu trúc

```
lms/
  lib/
    lms_bootstrap.php   autoload Lms\ + load .env + connection global (lms_db())
    lms_db.php          data layer tối giản (LmsDb: safeFetch/safeExec/insert/update/txn)
    lms_auth.php        bcrypt + CSRF + rate-limit + remember-me + lms_check_permission
  services/Lms/         business layer (PascalCase, namespace Lms\)
    LmsService.php      base: db(), errors(), audit()
    UserService.php     auth 3 portal, D9 (sai portal → từ chối)
    ClassService.php    ADM-03 tạo lớp, enroll
    ScheduleService.php BR-SCHED-01 (chống trùng lịch GV)
    SessionService.php  sinh sessions từ lịch (idempotent), hạn BTVN
    NotificationService.php chuông thông báo (db_lms_notifications)
  admin/                (Phase 1.4) sources/lms-*.php + templates/
  teacher/              (Phase 1.4)
  student/              (Phase 1.4)
```

Phase 1.4 sẽ thêm `lms/admin.php`, `lms/teacher.php`, `lms/student.php` (entry point)
+ `lms/lib/lms_router.php` + `lms/lib/lms_layout.php`.

## Data layer — tại sao không dùng `func_index`

`func_index` (`admin/lib/class.php`, 979 dòng) là God class gắn chặt e-commerce
(danh mục, menu, địa chỉ, prefix `#_`, API stateful `setTable/setWhere/delete`).
LMS copy **bản tối giản** (`LmsDb`) để:
- không kéo phụ thuộc e-commerce vào LMS (D2);
- giữ API quen thuộc: `safeFetch` / `safeFetchOne` / `safeExec` / `insert` / `update`
  + thêm `count()` + transaction tường minh;
- charset `utf8mb4`, `PDO::ERRMODE_EXCEPTION`, bind tham số mọi nơi.

```php
$db = lms_db();                                    // hoặc new LmsDb($config)
$rows = $db->safeFetch('select * from db_lms_users where role = ?', array('teacher'));
$db->insert('db_lms_notifications', array('user_id' => 1, 'type' => 'system'));
```

## Phân quyền

Theo `Tai-lieu-phat-trien/Huong-Dan-Phat-Trien-Page.md` (mirror cơ chế
`db_module_admin` + `checkPermission` của e-commerce, nhưng gán theo **role**):

- `db_lms_modules` — module theo portal (admin 14 / teacher 6 / student 5).
- `db_lms_role_permissions` — `can_view` / `can_edit` / `can_delete` theo role.
- `lms_check_permission($db, $user, $alias, $action, $portal)` — sai portal →
  luôn false (D9); `super_admin` bypass (portal admin).

```php
if (!lms_check_permission($db, $user, 'lms-hoc-vien', 'edit', 'admin')) {
    /* 403 */
}
```

Phân quyền **cấp dòng** (GV chỉ thấy vi phạm của mình) làm trong service, không
phải trong `lms_check_permission` (xem guide §3.5).

## Quy ước code

- **PHP 7.3**: không `fn()`, `??=`, `str_contains()`, `?->`, named args.
- Lint: `C:\laragon\bin\php\php-7.3.33-nts-Win32-VC15-x64\php.exe -l <file>`.
- Mọi query bind tham số; mọi output HTML escape `e()`.
- Mỗi file source có guard `!defined('_source')` (Phase 1.4).
- Template hậu tố `_tpl.php`.
- Mọi POST có CSRF (`lms_csrf_verify()`).

## Test + seed

```bash
# PHPUnit (dùng DB test phuongnamv_db_lms_test — KHÔNG đụng DB thật)
php vendor/phpunit/phpunit/phpunit

# Seed module/permission (chạy 1 lần)
php database/seeds/... (xem file SQL trong database/seeds/)

# Seed dữ liệu demo (idempotent; mặc định phuongnamv_db)
php database/seeds/2026_10_07_lms_demo_seed.php
php database/seeds/2026_10_07_lms_demo_seed.php phuongnamv_db_lms_test
```

Tài khoản demo sau khi seed: `admin/admin123`, `gv01/gv01123456`, `hv01/hv01123456`…
(chi tiết xem đầu ra script seed).
