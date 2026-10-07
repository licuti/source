# Hướng dẫn phát triển module / page & phân quyền

> **Phiên bản:** 1.0 — dành cho implementation agent.
> Mục đích: làm rõ **cơ chế page + phân quyền của codebase hiện tại** (e-commerce) và
> cách **LMS mirror** cơ chế đó theo kế hoạch `1791257567462-lms-masterstudy-php-extend.md`
> (D3 + §5.1). Từ đó mọi page LMS mới đều theo đúng một recipe duy nhất.

---

## 0. Ràng buộc môi trường (QUAN TRỌNG)

Web server chạy **PHP 7.3** (xác minh tại `admin/lib/README.md` mục "Lưu ý môi trường").
Lint mọi file mới/sửa bằng:

```
C:\laragon\bin\php\php-7.3.33-nts-Win32-VC15-x64\php.exe -l <file>
```

**Không dùng:** arrow function `fn()`, `??=` (7.4+), `str_contains()`, nullsafe `?->`,
named arguments (8.0+). Dùng `isset($x) ? $x : ''` hoặc `$x ?? ''`.

---

## 1. Cơ chế hiện tại của admin e-commerce (tham chiếu — KHÔNG đụng)

> Theo D2, phần e-commerce chạy nguyên vẹn. Đoạn dưới đây là **mẫu** để LMS học theo,
> không phải chỗ để sửa.

### 1.1. Luồng một request

```
index.php?p=<alias>&a=<action>
   │
   ├─ admin/index.php            bootstrap: session check, config/function/class, page_assets
   │
   └─ admin/lib/file_router_admin.php
        │  1. Whitelist alias: preg_match('/^[a-z0-9-]+$/', $p)         (dòng 19)
        │  2. SELECT * FROM #_module_admin WHERE alias = ?              (dòng 68-77)
        │  3. checkPermission(id_user, id_module) == 0 → redirect       (dòng 88)
        │  4. require admin/sources/<alias>.php                         (dòng 96-105)
        │
        └─ source set $template = '<alias>/<action>'
              │
              └─ admin/index.php: include templates/<template>_tpl.php  (dòng 44)
                   (validate path: không chứa '..', chỉ [a-zA-Z0-9/_-])
```

### 1.2. Các bảng thật (đã dump từ `phuongnamv_db`)

**`db_module_admin`** — đăng ký module (menu + cổng vào):

```sql
CREATE TABLE `db_module_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent` int(11) NOT NULL,        -- 0 = mục cha, id của cha = nhóm
  `name` varchar(255) NOT NULL,     -- tên hiển thị trên sidebar
  `alias` varchar(100) NOT NULL,    -- khóa: phải khớp tên file sources/<alias>.php
  `so_thu_tu` int(11) NOT NULL,     -- thứ tự sắp xếp
  `hien_thi` int(11) NOT NULL,      -- 1 = hiện trên sidebar
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
```

**`db_user_permission_group`** — gán quyền theo user:

```sql
CREATE TABLE `db_user_permission_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_permission` int(11) NOT NULL,   -- = db_module_admin.id
  `action` int(11) NOT NULL,          -- 1 = xem, 2 = sửa, 3 = xoá (ghép: 12, 23, 123)
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### 1.3. Hàm phân quyền (`admin/lib/class.php`)

| Hàm | Dòng | Ý nghĩa |
|---|---|---|
| `checkPermission($id_user, $id_page)` | 588 | Có truy cập module không. `$_SESSION['is_admin'] == 1` → **bỏ qua mọi check** |
| `checkPermission_view($id_page)` | 607 | Quyền xem (action chứa '1') |
| `checkPermission_edit($id_page)` | 611 | Quyền sửa (action chứa '2' hoặc '3') |
| `checkPermission_dele($id_page)` | 615 | Quyền xoá (action chứa '3') |

> Cột `action` là **số nguyên** nhưng `hasPermissionAction()` (dòng 629) dùng `LIKE '%1%'`
> để khớp chuỗi ghép — giữ nguyên hành vi này khi mirror.

### 1.4. Helper infra (`admin/lib/function.php`)

| Hàm | Dòng | Dùng cho |
|---|---|---|
| `admin_csrf_token($rotate)` | 52 | Sinh token, tự đồng bộ `$_SESSION['token']` cũ |
| `admin_csrf_verify($token)` | 79 | `hash_equals()` với token session; `null` → lấy `$_POST['csrf_token']` |
| `admin_flash($type, $msg)` | 98 | Thông báo 1 lần qua session, shell tự render (`admin/index.php:136`) |
| `admin_page_assets($template)` | `page_assets.php` | Nạp JS/CSS theo module (CKEditor chỉ ở form, Sortable chỉ trang kéo-thả) |

### 1.5. UI component (`admin/templates/components/`)

> **Nguyên tắc (README của thư mục này): KHÔNG gọi trực tiếp thư viện UI trong module.**
> Luôn đi qua `component('ten', [...])` (PHP) và `AdminUI.*` (JS, `js/ui-adapter.js`).

Component hay dùng: `page_header`, `list_bar`, `toolbar`, `card_open`/`card_close`,
`pagination`, `row_check`, `row_actions`, `empty_state`, `switch_toggle`, `stt_input`.

---

## 2. Recipe tạo 1 page mới (ví dụ thật: module `lien-he`)

### Bước 1 — Đăng ký module

```sql
INSERT INTO db_module_admin (parent, name, alias, so_thu_tu, hien_thi)
VALUES (31, 'Khách hàng liên hệ', 'lien-he', 1, 1);
```

`alias` phải: `[a-z0-9-]+`, trùng tên file source, và **duy nhất**.

### Bước 2 — Tạo source `admin/sources/<alias>.php`

Ví dụ đầy đủ: `admin/sources/lien-he.php`. Khung rút gọn:

```php
<?php
if (!defined('_source')) {   // chống truy cập trực tiếp
    die('Error');
}

$a = isset($_REQUEST['a']) ? (string) $_REQUEST['a'] : '';

switch ($a) {
    case 'man':
        showdulieu();
        $template = @$_REQUEST['p'] . '/hienthi';
        break;
    case 'delete':
        xoadulieu($id_module);          // $id_module do router set
        break;
    case 'delete_all':
        xoadulieu_mang($id_module);
        break;
    default:
        $template = 'index';
}

function showdulieu()
{
    global $d, $items, $total_page, $total_records, $current_page, $per_page;

    // Whitelist cột tìm kiếm trước khi đưa vào SQL
    $allowed = array('ho_ten', 'email', 'so_dien_thoai');

    $limit  = 10;
    $page   = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $items = $d->o_fet('SELECT * FROM #_lienhe ORDER BY id DESC'
        . ' LIMIT ' . (int) $offset . ', ' . (int) $limit);
}

function xoadulieu($id_module)
{
    global $d;

    $back = 'index.php?p=lien-he&a=man';
    // 1. Check quyền theo action TRƯỚC khi làm gì
    if ($d->checkPermission_dele($id_module) != 1 || !isset($_GET['id'])) {
        $d->redirect($back);
        return;
    }
    $id = (int) $_GET['id'];
    if ($id <= 0) { $d->redirect($back); return; }

    // 2. Thực thi + redirect (Post/Redirect/Get)
    $d->reset();
    $d->setTable('#_lienhe');
    $d->setWhere('id', $id);
    $d->delete();
    $d->redirect($back);
}
```

Quy ước rút ra:
- Guard `!defined('_source')` đầu file.
- Mỗi giá trị `a` = 1 hàm riêng; đặt `$template` để shell biết view nào.
- **Mỗi action kiểm quyền ngay** (`checkPermission_edit`/`_dele`) — không tin router là đủ.
- Param user → ép kiểu `(int)` / whitelist cột / bind tham số (`safeFetch`, `safeExec`).
- Xong việc thì `redirect()` (PRG), không echo HTML trong source.

### Bước 3 — Tạo template `admin/templates/<alias>/<action>_tpl.php`

Ví dụ: `admin/templates/lien-he/hienthi_tpl.php`. Khung:

```php
<?php
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'lien-he';
$items     = is_array($items ?? null) ? array() : $items;
?>

<?php component('page_header', array(
    'title'      => 'Khách hàng liên hệ',
    'breadcrumb' => array(
        array('label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'),
        array('label' => 'Khách hàng liên hệ'),
    ),
)); ?>

<?php component('card_open', array('body' => 'none')); ?>
    <div class="table-responsive p-3">
        <table class="table table-hover table-bordered align-middle">
            <tbody>
            <?php foreach ($items as $row): ?>
                <tr>
                    <td><?= e((string) ($row['ho_ten'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php component('card_close', array('body' => 'none')); ?>
```

Quy ước:
- Hậu tố file **bắt buộc** `_tpl.php`.
- **Mọi output escape** bằng `e()` (helper trong `templates/components/_helpers.php`).
- Dùng `component(...)` thay vì viết raw HTML/AdminLTE — trang mới chỉ nên chứa logic trình bày + data từ source.
- Không query DB trong template (trừ count phụ nhỏ); data do source chuẩn bị.

### Bước 4 — Gán quyền cho user

```sql
INSERT INTO db_user_permission_group (id_user, id_permission, action)
VALUES (22, <id_module_mới>, 123);   -- 1=xem, 2=sửa, 3=xoá (ghép thành số)
```

User có `is_admin = 1` thì không cần dòng này (bypass toàn bộ).

### Bước 5 — Endpoint AJAX (nếu cần)

Thêm case vào `admin/sources/ajax.php`. Bắt buộc:
1. Auth guard (`$_SESSION['id_user']` + `user_hash`) — đã có sẵn đầu file, **không xoá**.
2. `admin_csrf_verify()` cho mọi POST (client gửi kèm qua `js/csrf.js` — dùng `AdminUI.http.post`).
3. `checkPermission*()` theo thao tác.
4. Bind tham số, không nối chuỗi user input.

```js
// Client
AdminUI.http.post('./sources/ajax.php', { do: 'get_lienhe', id: id })
    .then(function (res) { /* res.json, res.text, res.ok */ });
```

### Bước 6 — Asset theo module

Nếu page cần thư viện riêng (CKEditor, Sortable, chart…): thêm mapping vào
`admin/lib/page_assets.php` theo `$template`. **Không** nạp `<script>`/`<link>` trực tiếp
trong template — trừ JS inline nhỏ của chính trang đó (xem `hienthi_tpl.php:201`).

### Bước 7 — Kiểm tra

- [ ] `php -l` cả source lẫn template: 0 lỗi.
- [ ] Vào bằng account **không có quyền** → bị redirect về `index.php` + flash báo.
- [ ] Vào bằng account có quyền `view` nhưng không có `dele` → nút xoá ẩn (`$canDelete`).
- [ ] Gọi AJAX mà chưa đăng nhập → HTTP 401/403.
- [ ] POST thiếu CSRF → bị từ chối.

---

## 3. Phần LMS: mirror cơ chế này (D3 + §5.1)

> Khác biệt cốt lõi: e-commerce gán quyền **theo user** (`db_user_permission_group`);
> LMS gán quyền **theo role** (RBAC matrix ở `SRS_MasterStudy.md` §4) vì 3 portal đối xứng
> và role của user đã có sẵn trong `db_lms_users.role`.

### 3.1. Bảng đề xuất (thêm vào migration Phase 0.3)

**`db_lms_modules`** — đăng ký module LMS, tách theo portal:

```sql
CREATE TABLE `db_lms_modules` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent` int(11) unsigned NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `alias` varchar(100) NOT NULL,         -- [a-z0-9-]+, khớp sources/lms-<alias>.php
  `portal` enum('admin','teacher','student') NOT NULL,
  `icon` varchar(100) NOT NULL DEFAULT '',
  `so_thu_tu` int(11) NOT NULL DEFAULT 1,
  `hien_thi` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_alias_portal` (`alias`, `portal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**`db_lms_role_permissions`** — quyền theo role + module:

```sql
CREATE TABLE `db_lms_role_permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_module` int(11) unsigned NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_module_role` (`id_module`, `role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Seed mặc định từ RBAC matrix (SRS §4): admin = toàn quyền; teacher/student = từng module
theo mockup (GV 6 mục, HV 5 mục — xem plan §4.1c).

Ngoại lệ duy nhất ghi bằng cột, không qua bảng này: `db_lms_admins.role_level =
'super_admin'` → bypass toàn bộ (tương đương `is_admin` của e-commerce; RBAC dòng
"Audit Log - Xem" chỉ super_admin).

### 3.2. Hàm phân quyền LMS (`lms/lib/lms_auth.php`)

```php
/**
 * @param string $alias   alias module
 * @param string $action  'view' | 'edit' | 'delete'
 * @param string $portal  portal hiện tại (admin/teacher/student)
 * @return bool
 */
function lms_checkPermission($alias, $action = 'view', $portal = null)
{
    global $d, $_LMS_USER;   // $_LMS_USER = row db_lms_users đã xác thực

    if (!is_array($_LMS_USER) || empty($_LMS_USER['role'])) {
        return false;
    }
    if ($portal === null) {
        $portal = defined('_LMS_PORTAL') ? _LMS_PORTAL : 'admin';
    }

    // Sai portal → không bao giờ cho (D9)
    if ($_LMS_USER['role'] !== $portal) {
        return false;
    }

    // super_admin bypass (chỉ portal admin)
    if ($portal === 'admin'
        && !empty($_LMS_USER['is_super_admin']) && $_LMS_USER['is_super_admin'] == 1) {
        return true;
    }

    $row = $d->safeFetchOne(
        'select m.id, p.can_view, p.can_edit, p.can_delete'
        . ' from db_lms_modules m'
        . ' left join db_lms_role_permissions p'
        . ' on p.id_module = m.id and p.role = ?'
        . ' where m.alias = ? and m.portal = ? limit 1',
        array($_LMS_USER['role'], $alias, $portal)
    );
    if (!is_array($row)) {
        return false;
    }

    $map = array('view' => 'can_view', 'edit' => 'can_edit', 'delete' => 'can_delete');
    $col = isset($map[$action]) ? $map[$action] : 'can_view';
    return (int) ($row[$col] ?? 0) === 1;
}
```

Đặt `$_LMS_USER` + `_LMS_PORTAL` trong `lms/lib/lms_auth.php` ngay sau khi xác thực
session; mỗi entry point (`lms/admin.php`, `lms/teacher.php`, `lms/student.php`) định
`_LMS_PORTAL` riêng.

### 3.3. Router LMS (`lms/lib/lms_router.php`)

Nhân bản `file_router_admin.php` nhưng:
- Lookup `db_lms_modules` thêm điều kiện `portal = _LMS_PORTAL`.
- Dùng `lms_checkPermission($alias)` thay `checkPermission()`.
- Include `lms/<portal>/sources/<alias>.php` (ví dụ `lms/admin/sources/lms-hoc-vien.php`).
- Alias LMS có prefix `lms-` để nhìn URL là biết (URL: `lms/admin.php?p=lms-hoc-vien&a=man`).

### 3.4. Recipe tạo page LMS mới

Giống hệt §2 (6 bước), thay:

| E-commerce | LMS |
|---|---|
| `db_module_admin` | `db_lms_modules` (+ cột `portal`) |
| `db_user_permission_group` | `db_lms_role_permissions` (theo role) |
| `checkPermission*()` | `lms_checkPermission($alias, $action)` |
| `admin/index.php?p=&a=` | `lms/<portal>.php?p=&a=` |
| `admin/sources/<alias>.php` | `lms/<portal>/sources/<alias>.php` |
| `admin/templates/<alias>/x_tpl.php` | `lms/<portal>/templates/<alias>/x_tpl.php` |
| `templates/components/*` | `lms/lib/lms_components/*` (copy/component dùng chung) |
| `admin_csrf_*()` | `lms_csrf_*()` (đặt trong `lms/lib/lms_auth.php`, cùng pattern) |

**Ví dụ cụ thể** — ADM-01 quản lý học viên:
1. `INSERT INTO db_lms_modules (parent, name, alias, portal, icon, so_thu_tu, hien_thi) VALUES (0, 'Quản lý học viên', 'lms-hoc-vien', 'admin', 'fa-solid fa-user-graduate', 2, 1);`
2. `INSERT INTO db_lms_role_permissions (id_module, role, can_view, can_edit, can_delete) VALUES (<id>, 'admin', 1, 1, 1);`
3. `lms/admin/sources/lms-hoc-vien.php` (source: `man`/`add`/`edit`/`delete`, mỗi action check `lms_checkPermission('lms-hoc-vien', 'edit'|'delete', 'admin')`).
4. `lms/admin/templates/lms-hoc-vien/hienthi_tpl.php` + `form_tpl.php`.

### 3.5. Phân quyền cấp dòng (row-level) — KHÔNG làm trong router

RBAC matrix có nhiều quy tắc "Self only" (GV xem vi phạm/lương của mình, HV xem lớp mình).
Đây **không** phải việc của `lms_checkPermission` (cấp page) mà là **của service layer**:

```php
// lms/services/Lms/ViolationService.php
public function listForTeacher($teacherId)
{
    return $this->d->safeFetch(
        'select * from db_lms_violations where teacher_id = ? order by id desc',
        array($teacherId)
    );
}
```

Quy tắc: router/page permission quyết định **được vào trang không**; service quyết định
**thấy dòng nào**. Không trộn lẫn.

---

## 4. Checklist cho mỗi page mới (paste vào PR)

- [ ] Đăng ký `db_lms_modules` (đúng `portal`, `alias` `[a-z0-9-]+`, unique).
- [ ] Seed `db_lms_role_permissions` đúng RBAC matrix (SRS §4).
- [ ] Guard `!defined('_source')` đầu source.
- [ ] Mỗi action kiểm `lms_checkPermission($alias, $action, $portal)` trước khi xử lý.
- [ ] Query dùng `safeFetch`/`safeFetchOne`/`safeExec` (bind tham số); whitelist cột sort/search.
- [ ] Template hậu tố `_tpl.php`; mọi output qua `e()`.
- [ ] Mọi POST có CSRF (`lms_csrf_verify()`); endpoint AJAX có auth guard + CSRF + quyền.
- [ ] Logic nghiệp vụ nằm trong `lms/services/Lms/*Service.php`, không trong template.
- [ ] Logic tiền bạc có PHPUnit test pass (Phase 2 trở đi).
- [ ] Asset nạp qua `page_assets` tương đương, không nhúng thư viện trực tiếp.
- [ ] `php -l` 0 lỗi (PHP 7.3).
- [ ] Test 2 account: có quyền / không quyền + sai portal bị từ chối (D9).
- [ ] UI khớp mockup PNG ở `Tai-lieu-phat-trien/<role>/`.
- [ ] Không đụng `admin/`, `db_module_admin`, `db_user`, `db_thanhvien` (D2).

---

## 5. Tham chiếu nhanh

| Việc | Xem file |
|---|---|
| Router admin | `admin/lib/file_router_admin.php` |
| Shell admin (template + asset + CSRF) | `admin/index.php` |
| Hàm phân quyền | `admin/lib/class.php:588-650` |
| Helper CSRF/flash | `admin/lib/function.php:52-105` |
| Asset theo module | `admin/lib/page_assets.php` |
| Module mẫu (nhỏ, đọc dễ) | `admin/sources/lien-he.php` + `admin/templates/lien-he/hienthi_tpl.php` |
| Component UI | `admin/templates/components/README.md`, `js/ui-adapter.js` |
| Bảo mật đã gia cố | `ANALYSIS-ADMIN-REVIEW.md`, `admin/lib/README.md` |
| Kế hoạch LMS | `1791257567462-lms-masterstudy-php-extend.md` (D3, §4.1, §5.1) |
| RBAC LMS | `SRS_MasterStudy.md` §4 |
