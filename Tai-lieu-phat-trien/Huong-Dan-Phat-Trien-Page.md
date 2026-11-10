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
   ├─ admin/index.php            bootstrap: session check, config/function/class, asset shell
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

> Asset shell: từ 2026-10-07, `admin/index.php` nạp đồng bộ asset cho mọi trang (jQuery +
> Fancybox + CKEditor ở head, Sortable ở body) — không còn `admin_page_assets()` /
> `page_assets.php` (đã xóa). Thêm module mới không cần cập nhật gì.

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

### Bước 6 — Asset

Shell `admin/index.php` đã nạp đồng bộ cho mọi trang: jQuery + Fancybox + CKEditor
(head) và Sortable (body). Thêm module mới **không** cần cập nhật chỗ nào cả.
**Không** nạp `<script>`/`<link>` thư viện trực tiếp trong template — trừ JS inline
nhỏ của chính trang đó (xem `hienthi_tpl.php:201`).

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
- Include `lms/app/<portal>/sources/<alias>.php` (ví dụ `lms/app/admin/sources/lms-hoc-vien.php`).
  **Lưu ý:** portal dir nằm dưới `lms/app/`, KHÔNG phải `lms/<portal>/`. Nếu đặt
  `lms/admin/` thì URL `/lms/admin` trùng tên thư mục thật → mod_dir's
  DirectorySlash sinh ra 301 dư (`/lms/admin/?lms_route=`) mà mod_rewrite không
  ghi đè được (đã verify trên Laragon Apache 2.4). Đây cũng là lý do rule
  `.htaccess` dùng substitution path tuyệt đối `/lms/$1.php`.
- Alias LMS có prefix `lms-` để nhìn URL là biết.

#### 3.3.1. URL clean — không hiện `.php` (quyết định D10)

URL mặt ngoài dạng **path**, không có `.php` hay `?p=&a=`:

| URL mặt ngoài | Tương đương query cũ |
|---|---|
| `/lms/` | trang chọn vai trò (`lms/index.php`, qua `DirectoryIndex`) |
| `/lms/admin` | `lms/admin.php` — dashboard |
| `/lms/admin/login` | `lms/admin.php?act=login` |
| `/lms/admin/logout` | `lms/admin.php?act=logout` |
| `/lms/admin/lms-hoc-vien` | `lms/admin.php?p=lms-hoc-vien&a=man` |
| `/lms/admin/lms-hoc-vien/them` | `lms/admin.php?p=lms-hoc-vien&a=them` |
| `/lms/teacher/lms-lich-day` | `lms/teacher.php?p=lms-lich-day&a=man` |
| `/lms/student/lms-lich-hoc` | `lms/student.php?p=lms-lich-hoc&a=man` |

ID lẻ (`?id=5`), phân trang (`?page=2`)… **vẫn dùng query** — không biến thành slug.

**Thêm vào `.htaccess` (chèn SAU khối `-d`/`-s` đầu file, TRƯỚC các rule `.html` của storefront):**

```apache
# ---- LMS MasterStudy: /lms/<portal>[/<alias>[/<action>]] ----
# File/thư mục thật (admin.php, asset css/js) đã bị khối -d/-s bên trên bỏ qua.
RewriteRule ^lms/?$                                lms/index.php [L]
RewriteRule ^lms/(admin|teacher|student)/?$        lms/$1.php?lms_route=     [QSA,L]
RewriteRule ^lms/(admin|teacher|student)/(.+?)/?$  lms/$1.php?lms_route=$2   [QSA,L]
```

> Tại sao an toàn cho e-commerce:
> - Khối `-d`/`-s` đầu `.htaccess` đã `RewriteRule ^.*$ - [L]` với mọi file/thư mục
>   thật → `lms/admin.php`, `lms/admin/public/js/x.js`… chạy nguyên, không bị rewrite.
> - Rule LMS đặt **trước** rule storefront `^([a-z]+)/$` (dòng 55) nên `/lms/...` không
>   bị nuốt vào `index.php?lang=lms`.
> - `[QSA]` giữ nguyên `?id=` / `?page=`.

**Production nginx** (hosting không đọc `.htaccess`):

```nginx
location ~ ^/lms/(admin|teacher|student)(/.*)?$ {
    try_files $uri @lms_rewrite;
}
location @lms_rewrite {
    rewrite ^/lms/(admin|teacher|student)(/.*)?$ /lms/$1.php?lms_route=$2 last;
}
```

#### 3.3.2. Parse 2 dạng URL (mới + cũ)

Router đổ path về đúng `$p`/`$a` mà cơ chế whitelist + `lms_check_permission` đã dùng.
URL cũ `lms/admin.php?p=...&a=...` **vẫn hoạt động** (tiện debug, bookmark cũ):

```php
// 1) Ưu tiên path dạng /lms/<portal>/<alias>[/<action>]
$route  = isset($_GET['lms_route']) ? trim((string) $_GET['lms_route'], "/\r\n") : '';
$parts  = $route !== '' ? explode('/', $route) : array();

$reserved = array('login', 'logout', 'dashboard');   // action không thuộc module nào

if (!empty($parts) && in_array($parts[0], $reserved, true)) {
    $p = '';            // /lms/admin/login → a=login
    $a = $parts[0];
} elseif (!empty($parts)) {
    $p = $parts[0];     // /lms/admin/lms-hoc-vien[/them]
    $a = isset($parts[1]) ? $parts[1] : 'man';
}

// 2) Backward compat: ?p=&a= cũ
if (!isset($p)) { $p = isset($_GET['p']) ? (string) $_GET['p'] : ''; }
if (!isset($a)) { $a = isset($_GET['a']) ? (string) $_GET['a'] : ''; }

// 3) Chưa đăng nhập → redirect /lms/<portal>/login
// 4) Whitelist ^[a-z0-9-]+$ + lookup db_lms_modules (where portal = _LMS_PORTAL)
//    + lms_checkPermission() → require lms/<portal>/sources/<alias>.php
```

#### 3.3.3. Helper sinh link trong template

```php
/**
 * @param string $alias  alias module ('' = dashboard)
 * @param string $action
 * @param array  $params query thêm (id, page, search...)
 * @return string
 */
function lms_url($alias = '', $action = '', array $params = array())
{
    $base = '/lms/' . _LMS_PORTAL;              // /lms/admin | /lms/teacher | /lms/student
    if ($alias === '') {
        return $base . ($params ? '?' . http_build_query($params) : '');
    }
    $url = $base . '/' . $alias . ($action !== '' ? '/' . $action : '');
    return $params ? $url . '?' . http_build_query($params) : $url;
}

// lms_url('lms-hoc-vien', 'them', array('id' => 5))
//   → /lms/admin/lms-hoc-vien/them?id=5
```

> **Cấm** viết tay `href="/lms/admin/lms-hoc-vien"` trong template — luôn qua
> `lms_url()` để khi đổi scheme chỉ sửa 1 chỗ.

> **Không khuyến nghị** gom tất cả về 1 front controller `lms/index.php` (dù rule
> đơn giản hơn): D3 chốt 3 entry point đối xứng, tách file giúp lỗi 1 portal không
> hạ cả hệ thống.

### 3.4. Recipe tạo page LMS mới

Giống hệt §2 (6 bước), thay:

| E-commerce | LMS |
|---|---|
| `db_module_admin` | `db_lms_modules` (+ cột `portal`) |
| `db_user_permission_group` | `db_lms_role_permissions` (theo role) |
| `checkPermission*()` | `lms_checkPermission($alias, $action)` |
| `admin/index.php?p=&a=` | `lms/<portal>.php?p=&a=` (URL mặt ngoài: `/lms/<portal>/<alias>/<action>` — §3.3.1, D10) |
| `admin/sources/<alias>.php` | `lms/app/<portal>/sources/<alias>.php` |
| `admin/templates/<alias>/x_tpl.php` | `lms/app/<portal>/templates/<alias>/x_tpl.php` |
| `templates/components/*` | `lms/app/admin/templates/components/*` — gọi qua `lms_component('ten', [...])` hoặc `lms_component_render()` (`lms/lib/lms_admin_forms.php`) |
| `admin_csrf_*()` | `lms_csrf_*()` (đặt trong `lms/lib/lms_auth.php`, cùng pattern) |

**Ví dụ cụ thể** — ADM-01 quản lý học viên:
1. `INSERT INTO db_lms_modules (parent, name, alias, portal, icon, so_thu_tu, hien_thi) VALUES (0, 'Quản lý học viên', 'lms-hoc-vien', 'admin', 'fa-solid fa-user-graduate', 2, 1);`
2. `INSERT INTO db_lms_role_permissions (id_module, role, can_view, can_edit, can_delete) VALUES (<id>, 'admin', 1, 1, 1);`
3. `lms/app/admin/sources/lms-hoc-vien.php` (source: `man`/`add`/`edit`/`delete`, mỗi action check `lms_checkPermission('lms-hoc-vien', 'edit'|'delete', 'admin')`).
4. `lms/app/admin/templates/lms-hoc-vien/hienthi_tpl.php` + `form_tpl.php`.

**Component LMS dùng chung** (`lms/app/admin/templates/components/`):
`button`, `badge`, `file_grid`, `pagination`, `tabs`, `calendar_week`, `calendar_week_event`,
`form/input`, `form/select`,
`form/textarea`, `form/date_picker`, `form/addon_input`, `form/field_row`,
`form/actions_bar`, `form/submit_bar`, `form/button`, `form/file_picker`, `form/file_upload`,
`form/multi_select_tags`, `form/avatar_picker`.

- `tabs` — thanh tab điều hướng (CSS `.lms-tabs-nav`), nhận `items`:
  ```php
  $items = array();
  foreach (array('group' => 'Lớp đông người', 'one_on_one' => 'Lớp 1 kèm 1') as $type => $label) {
      $items[] = array(
          'label'  => $label,
          'href'   => lms_url($alias, 'man', array('type' => $type)),
          'active' => $filterClassType === $type,
      );
  }
  lms_component('tabs', array('aria_label' => 'Loại lớp học', 'items' => $items));
  ```
- `pagination` — **kiểu gọi duy nhất** là `lms_component('pagination', array(...))`; wrapper `lms_pagination()` trong `lms/lib/lms_admin_forms.php` đã xoá (nó chỉ forward lại component và không truyền được `size`/`align`/`window`). Component tự `return` khi `total_pages <= 1`, tự lọc param rỗng, và **tự thêm class `mt-4` cho `<nav>`** để tách khỏi bảng — chỉ truyền `class` khi muốn khác (VD `'mt-0'` khi phân trang nằm trong toolbar):
  ```php
  <?php lms_component('pagination', array(
      'base_url'    => lms_url($alias),
      'page'        => $page,
      'total_pages' => $totalPages,
      'params'      => array_filter(array('q' => $filterQ, 'tab' => $tab)),
  )); ?>
  ```
  Props: `page`, `total_pages`, `base_url`, `params` (KHÔNG dùng key `query`), `size` (`sm` mặc định), `align` (`center` mặc định), `window`, `class`.
- `form/*` — component form 2 cột (label `col-sm-4` + control `col-sm-8`), tự kế thừa `$row`/`$errors` từ template cha.
  `class` ở `form/input`, `form/textarea`, `form/select`, `form/date_picker`, `form/addon_input` là class **NỐI THÊM**, không thay class gốc: element luôn mang `lms-tf-input` / `lms-tf-select` / `lms-tf-addon-input` nên ô lọc (`class => 'form-control'`) và ô trong form có cùng một kiểu (cao 42px, bo 8px, viền `#ccd5de` — `lms.css` nạp sau Bootstrap nên thắng `.form-control`). Muốn bỏ hẳn style LMS (chỉ dùng cho control nội bộ, VD toolbar rich editor) truyền `'base_class' => false`.
- `calendar_week` — khung lưới lịch tuần dùng chung (Admin "Lịch học" + Teacher "Quản lý lịch" + Student "Lịch học"). Component chỉ render thead 7 ngày + tbody các hàng giờ; nội dung/class từng ô do template truyền callback:
  ```php
  lms_component('calendar_week', array(
      'monday'        => $weekMonday,
      'days'          => $daysOfWeek,   // hoặc 'weekdays' => array(2=>'Thứ 2', ...)
      'rows'          => $calRows,      // [['label','hour','cells'=>...]]
      'cell_class'    => function ($cell, $row) { return '...'; },
      'cell_attrs'    => function ($cell, $row) { return array('data-date' => $cell['date']); },
      'cell_renderer' => function ($cell, $row) { return '<div>...</div>'; },
  ));
  ```
- `calendar_week_event` — thẻ một buổi học / khung lịch trống đặt trong ô của `calendar_week`. Không template nào tự viết `<a class="lms-cal-link">` hay `<div class="lms-session-card">` nữa; mọi trang lịch gọi component này cùng helper trong `lms/lib/lms_calendar.php` (`lms_cal_variant`, `lms_cal_variant_label`, `lms_cal_missing_hw`, `lms_cal_offset_px`, `lms_cal_span_px`, `lms_cal_lanes_for_rows` + `lms_cal_lane_of`, `lms_cal_time_text`):
  ```php
  // Lane CHỈ tính cho thẻ buổi học; lịch trống là dải nền full ngang, z-index thấp hơn
  $calLanes = lms_cal_lanes_for_rows($calRows);
  $lane = lms_cal_lane_of($calLanes, (string) $ses['session_date'], 's' . (int) $ses['id']);

  $html .= lms_component_render('calendar_week_event', array(
      'variant' => lms_cal_variant($ses['status'], lms_cal_missing_hw($ses)), // upcoming|missing|finished|cancelled|free
      'title'   => $ses['class_name'],
      'start'   => $ses['start_time'],   // --lms-cal-top = phút lẻ/60 × LMS_CAL_HOUR_H (72px)
      'end'     => $ses['end_time'],     // --lms-cal-height = thời lượng/60 × 72px (1,5 giờ → 108px)
      'lane'    => $lane['lane'],        // chia ngang khi 2 thẻ chồng giờ trong cùng cột ngày
      'lanes'   => $lane['lanes'],
      'href'    => lms_url($alias, 'buoi', array('id' => $ses['id'])), // title = stretched link phủ cả thẻ
      'attrs'   => array('data-session-id' => $ses['id']),             // data-* cho modal (admin dùng div + JS click)
      'flag'    => '✗',                  // góc trên phải
      'footer'  => $nutChinhLich,        // 'Xem chi tiết' / nút — z-index cao hơn stretched link
  ));
  ```
  Thẻ `position: absolute` trong `<td>` (hàng giờ cố định 72px, `z-index: 2` nên thẻ dài ghi đè lên các ô phía dưới), ô có vạch đứt nửa giờ `.lms-cal-cell::before`. Class: `.lms-cal-event` + `.lms-cal-event--{variant}`, con `__title` (`__title--link` khi có href), `__time`, `__footer`, `__flag`; nền ô nhạt theo trạng thái qua `.lms-cal-cell--{variant}`. Riêng `--free` là dải nền: `z-index: 1`, full chiều ngang, viền đứt nét, không có `.lms-cal-cell--free` tương ứng.
- `form/file_upload` — vùng tải nhiều tệp dùng chung cho Student/Teacher (kéo thả, danh sách tệp đã chọn, giới hạn số lượng/dung lượng, hiển thị tệp đã nộp). JS khởi tạo trong `lms/public/js/lms.js` (`LMS.initFileUploads`), không viết script inline trong template:
  ```php
  lms_component('form/file_upload', array(
      'name'          => 'test_files[]',
      'existing'      => $submittedFiles,  // tệp đã nộp (mảng path)
      'max_files'     => 5,
      'required'      => true,
      'replace_hint'  => 'Nộp lại sẽ thay thế toàn bộ tệp đã nộp.',
      'browse_text'   => 'chọn tệp bài làm',
  ));
  ```
- `file_grid` — lưới chip tệp đã lưu (icon theo định dạng + link tải), dùng khi chỉ xem hoặc khi không cần vùng upload.
- **Ghim cột bảng khi cuộn ngang** — dùng `.lms-table-pin` (CSS trong `lms/public/css/lms.css`, mục "Ghim cột bảng khi cuộn ngang") + attribute `data-pin-left` / `data-pin-right` đặt trên **cả `<th>` lẫn `<td>`** của cột cần ghim. Toạ độ `left`/`right` do `LMS.initPinnedColumns()` (`lms/public/js/lms.js`) đọc bề rộng thật của cột (kèm `min-width` khai báo) rồi ghi, nên **không có số đo hardcode trong CSS/template** và không cần class thứ tự (`l1/l2/...`).
  ```php
  <table class="table table-bordered ... lms-table lms-table-pin">
      <th style="min-width: 150px;" data-pin-left>Họ và tên</th>
      <th style="width: 130px;" data-pin-right>Hành động</th>
      ...
      <td data-pin-left>…</td>
      <td class="text-center text-nowrap" data-pin-right>…</td>
  ```
  Quy tắc bắt buộc: (1) cột ghim trái phải liền mạch từ mép trái, cột ghim phải liền mạch từ mép phải (không cần là cột cuối); (2) **tổng bề rộng cụm ghim phải ≤ bề ngang container** — vượt là cột ghim thứ hai biến mất khỏi màn hình; (3) muốn cột có bề rộng cố định thì khai `min-width` trên `<th>` (đã đo: ở `table-layout:auto` chỉ `min-width` có hiệu lực, `width`/`max-width` bị bỏ qua), **không đặt width qua JS** vì ghi thuộc tính layout sau first paint sẽ làm layout giật; (4) bảng nằm trong tab/section ẩn phải gọi `LMS.refreshPinnedColumns()` khi hiện ra. Bảng `lms-tai-khoan` 2 tab đang áp dụng: tab học viên ghim "Số ưu tiên" + "Username" + "Hành động", tab giáo viên ghim "Họ và tên" + "Hành động".
- Layout chi tiết dùng chung (Student-ver2/Teacher-ver2): `.lms-page-head` + `.lms-page-title`, `.lms-panel` + `.lms-panel-head`/`.lms-panel-body`, `.lms-form-footer`, `.lms-file-grid`, `.lms-upload-*`. Các phần còn lại ưu tiên utility Bootstrap: breadcrumb (`nav > ol.breadcrumb`), tiêu đề phụ (`h6.fw-bold.fs-5`), chia cột (`row`/`col-md-6`), box (`border rounded-3 p-2`). Không tạo class riêng cho từng trang khi cùng một bố cục (trước đây là `lms-course-title`, `lms-topic-breadcrumb`, `lms-topic-info-card`, `lms-test-detail-card`).
- Trang Teacher "Chi tiết khóa học" (cá nhân + lớp nhóm) dùng lại `.lms-section-accordion` + `.lms-section-header`, `.lms-category-*`, `.lms-topic-card-v2`, `.lms-session-card-v2`, `.lms-hw-card`, `.lms-panel`; không dùng Bootstrap `.accordion`/`.lms-card` riêng. JS xoay chevron dùng chung nằm ở `LMS.initCollapseArrows` (`lms/public/js/lms.js`), không viết script inline trong template.

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
- [ ] Mọi link nội bộ qua `lms_url()` (không viết tay `/lms/<portal>/...`) — §3.3.3.
- [ ] Mọi POST có CSRF (`lms_csrf_verify()`); endpoint AJAX có auth guard + CSRF + quyền.
- [ ] Logic nghiệp vụ nằm trong `lms/services/Lms/*Service.php`, không trong template.
- [ ] Logic tiền bạc có PHPUnit test pass (Phase 2 trở đi).
- [ ] Asset shell `admin/index.php` đã đủ (jQuery/Fancybox/CKEditor/Sortable), không nhúng thư viện trực tiếp.
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
| Asset shell | `admin/index.php` (nạp đồng bộ mọi trang) |
| Module mẫu (nhỏ, đọc dễ) | `admin/sources/lien-he.php` + `admin/templates/lien-he/hienthi_tpl.php` |
| Component UI | `admin/templates/components/README.md`, `js/ui-adapter.js` |
| Bảo mật đã gia cố | `ANALYSIS-ADMIN-REVIEW.md`, `admin/lib/README.md` |
| Kế hoạch LMS | `1791257567462-lms-masterstudy-php-extend.md` (D3, D10, §4.1, §5.1) |
| RBAC LMS | `SRS_MasterStudy.md` §4 |
| URL clean LMS (D10) | `.htaccess` (rule `^lms/`) + mục §3.3.1 của file này |
