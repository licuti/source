# Hệ thống Component & Lớp cầu nối UI

Tài liệu cho developer khi làm việc với giao diện admin mới
(**AdminLTE 4 + Bootstrap 5 + Font Awesome 6**).

---

## 1. Nguyên tắc quan trọng nhất

> **KHÔNG gọi trực tiếp thư viện UI trong module.**
> Luôn đi qua `AdminUI` (JS) và `component()` (PHP).

Nhờ vậy khi cần thay thư viện, ta chỉ sửa **1 chỗ** thay vì hàng trăm file.

---

## 2. `AdminUI` — lớp cầu nối JavaScript

File: `admin/js/ui-adapter.js`

### 2.1 Thông báo (toast)

```js
AdminUI.notify.success('Cập nhật thành công');
AdminUI.notify.error('Lưu thất bại');
AdminUI.notify.warning('Vui lòng chọn ít nhất 1 dòng');
AdminUI.notify.info('Đang xử lý...');

// Tự chọn thông báo theo kết quả
AdminUI.notify.result(res.ok, 'Đã lưu', 'Lỗi khi lưu');
```

**Thư viện đang dùng:** Toastify. Thứ tự dự phòng:
`toastify` → `jqueryNotify` → `native (console)`

### 2.2 Hộp thoại xác nhận

```js
// Xác nhận xoá, có cảnh báo số mục con
AdminUI.confirm.delete({ name: 'Danh mục A', childCount: 3 })
    .then(function (ok) { if (ok) { /* thực hiện xoá */ } });

// Hộp thoại tuỳ ý
AdminUI.confirm.ask({ title: 'Chắc chưa?', message: 'Nội dung...' });
```

**Thư viện đang dùng:** SweetAlert2. Dự phòng: `confirm()` của browser.

> ⚠️ Trả về **Promise** (bất đồng bộ) nên KHÔNG dùng được kiểu
> `onclick="if(!confirm(...)) return false"`. Hãy dùng data-attribute
> `data-confirm-delete` (mục 4).

### 2.3 Gọi HTTP

```js
AdminUI.http.post('./sources/ajax.php', { do: 'update_stt', id: 5, val: 2 })
    .then(function (res) {
        // res.ok, res.status, res.text, res.json
    });
```

Dùng `fetch`, gửi dạng `form-urlencoded` để khớp các endpoint PHP sẵn có.

### 2.4 Lưu trữ cục bộ

```js
AdminUI.storage.set('key', { a: 1 });
AdminUI.storage.get('key', {});     // tham số 2 là giá trị mặc định
```

Đã bọc try/catch nên không lỗi khi localStorage bị tắt.

### 2.5 Đổi sang thư viện khác

Chỉ cần thêm 1 driver trong `ui-adapter.js`:

```js
var notifyDrivers = {
    notyf: {
        isAvailable: function () { return typeof Notyf !== 'undefined'; },
        show: function (message, type) { new Notyf().open({ type: type, message: message }); }
    },
    // ...các driver cũ
};
// rồi thêm 'notyf' vào đầu danh sách ưu tiên
```

**Không cần sửa bất kỳ module nào.**

### 2.6 Code cũ vẫn chạy

`admin/js/notify-bridge.js` ghi đè `$.notify()` để chuyển tiếp sang
`AdminUI.notify`. Nhờ đó ~30 module cũ tự động dùng toast mới mà không
phải sửa dòng nào.

---

## 3. `component()` — hệ thống component PHP

```php
component('form/input', [
    'label' => 'Tên danh mục',
    'name'  => 'ten[]',
    'value' => $row['ten'],
]);

// Cần lấy ra chuỗi thay vì in ngay:
$html = component_render('toolbar', [...]);
```

### Danh sách component

**Bố cục & danh sách**

| Component | Công dụng |
|---|---|
| `page_header` | Tiêu đề trang + breadcrumb |
| `list_bar` | Thanh tìm kiếm + bulk-action + thêm mới gộp 1 hàng — đặt NGOÀI card_open, ngay sau page_header |
| `card_open` / `card_close` | Khung `.card` (thay `.box` của AdminLTE 2) |
| `toolbar` | Tác vụ hàng loạt + tìm kiếm + nút Thêm mới |
| `empty_state` | Trạng thái không có dữ liệu |
| `row_check` | Checkbox chọn dòng (`chk_child[]`) |
| `stt_input` | Ô nhập số thứ tự (tự lưu AJAX) |
| `switch_toggle` | Công tắc bật/tắt (tự lưu + hoàn tác nếu lỗi) |
| `thumb` | Ảnh thumbnail + ảnh mặc định |
| `seo_status` | Badge Index/Noindex, Dofollow/Nofollow |
| `row_actions` | Nhóm nút Sửa / Xóa / Xem |

**Form**

| Component | Công dụng |
|---|---|
| `form/field_wrap` | Khung label + control (thay `.form-horizontal`) |
| `form/input` | Input 1 dòng (hỗ trợ prefix/suffix) |
| `form/textarea` | Textarea (hỗ trợ đếm ký tự SEO) |
| `form/select` | Dropdown (nhận `options` hoặc `options_html`) |
| `form/checkbox` | Checkbox / switch trong form |
| `form/editor` | CKEditor 4 + cấu hình filemanager dùng chung |
| `form/image_picker` | Chọn ảnh + xem trước + xoá |
| `form/lang_tabs` | Tabs đa ngôn ngữ |
| `form/actions_bar` | Nút Lưu / Lưu và Sửa / Quay lại (dính đáy) |

### Helper

| Hàm | Công dụng |
|---|---|
| `e($v)` | Escape HTML — **BẮT BUỘC** cho mọi dữ liệu từ DB/`$_GET` |
| `attrs([...])` | Dựng chuỗi HTML attribute |
| `admin_url([...])` | Tạo URL `index.php?p=...&a=...` |
| `img_url($file)` | URL ảnh trong `media/images`, có ảnh mặc định |

---


| `admin_csrf_field()` / `admin_csrf_input()` | Hidden input CSRF (`csrf_token`) |

**Bảo mật form (trong `admin/lib/function.php`):**

```php
admin_csrf_token();           // lấy / tạo token session
admin_csrf_verify();          // true nếu $_POST['csrf_token'] khớp
admin_flash('success', '...');
admin_sanitize_image_path($p); // chặn traversal / URL lạ
admin_rate_allow($key, 5, 900);
admin_rate_hit($key);
admin_rate_clear($key);
admin_login_flash_set('pwd_ok'); // cookie flash sang login.php
admin_module_available('quan-ly-don-hang'); // hien_thi + permission
```

Trong form POST:

```php
<?= admin_csrf_field() ?>
```

Trong controller `save`:

```php
if (!admin_csrf_verify()) { admin_flash('error', 'CSRF'); /* redirect */ }
```

## 4. Hành vi khai báo bằng data-attribute

Không cần viết JS, chỉ thêm attribute (xử lý trong `admin/js/admin-ui.js`):

| Attribute | Tác dụng |
|---|---|
| `data-confirm-delete` + `data-name` + `data-child-count` | Hỏi xác nhận (SweetAlert2) rồi mới điều hướng |
| `data-bulk-action` + `data-form="#form"` | Tác vụ hàng loạt, tự đếm số dòng đã chọn |
| `data-tree-table="category"` | Bật thu gọn/mở rộng cây, lưu trạng thái |
| `data-sortable` + `data-table` | Kéo thả đổi thứ tự (SortableJS) |
| `data-quick-filter` | Lọc nhanh client-side, giữ dòng cha |
| `data-warn-unsaved` | Cảnh báo khi rời trang chưa lưu |
| `data-datatable` | Bật DataTables (BS5). Tuỳ chọn: `data-page-length`, `data-order-col`, `data-order-dir`, `data-no-sort="0,4"` |
| `class="js-row-check"` | Checkbox chọn dòng (dùng cho "chọn tất cả") |
| `class="js-toggle"` + `data-table/col/id` | Công tắc lưu AJAX, tự hoàn tác nếu lỗi |

> DataTables BS5 đã được nạp sẵn ở `index.php` — module **không cần** tự thêm
> thẻ `<script>`/`<link>` như code cũ.

---

## 5. Lưu ý về môi trường

### PHP 7.3
Web server đang chạy **PHP 7.3** (dù PHP CLI có thể là 8.x). **KHÔNG** dùng:
- Arrow function `fn() =>` (7.4+)
- Toán tử `??=` (7.4+)
- `str_contains()`, nullsafe `?->`, named arguments (8.0+)

Kiểm tra trước khi commit:
```
C:\laragon\bin\php\php-7.3.33-nts-Win32-VC15-x64\php.exe -l <file>
```

### Encoding
**KHÔNG** sửa file PHP tiếng Việt bằng PowerShell 5.1 (`Get-Content`/`Set-Content`)
— sẽ làm hỏng dấu và thêm BOM. Xem `admin/_backup_ui/README.md`.

---

## 6. Tiến độ migrate

| Module | Trạng thái |
|---|---|
| Layout (header/sidebar/footer) | ✅ AdminLTE 4 |
| Shell `index.php` + router | ✅ Whitelist p, logout sạch, meta CSRF, admin-shell.js |
| Dashboard (`index_tpl`) | ✅ BS5 + component + stats/link |
| `login.php` | ✅ Bootstrap 5 |
| `category` | ✅ Dùng component đầy đủ |
| `cau-hinh` | ✅ Form select + editor |
| `lien-he` | ✅ Danh sách + DataTables + modal BS5 |
| `thongtin-lienhe` | ✅ Form cấu hình + component |
| `thongtin-user` | ✅ Hồ sơ + đổi mật khẩu (component) |
| Module còn lại | ⏳ Vẫn Bootstrap 3, chạy nhờ `css/bs3-compat.css` + `js/bs3-compat.js` |

---

## 7. Module `category` — tối ưu controller

`admin/sources/category.php` đã được viết lại (297 → 318 dòng, nhưng bỏ được
phần trùng lặp và thêm chú thích). Các vấn đề tìm thấy:

| Loại | Vấn đề | Hậu quả |
|---|---|---|
| 🔴 | `where $col like '%$value%'` — **tên cột lấy từ URL** | `addslashes()` vô nghĩa với identifier. Nay whitelist `ten`/`id_code` (khớp `searchable` trong template) |
| 🔴 | `delete ... in ($chuoi)` — ghép thẳng từ `$_POST['chk_child']` | Không ép kiểu → chèn gì cũng vào được lệnh DELETE. Nay `array_map('intval')` |
| 🔴 | `delete from cf_code where id = $id` | Chưa ép `(int)` |
| 🐞 | `$d->transfer(...)` — **method KHÔNG tồn tại** | 3 lời gọi ở nhánh xoá lỗi sẽ gây **fatal error**, bị `error_reporting(0)` che. Đã thay bằng `redirect()` |
| 🐞 | `$data['alias'] .= "-".rand()` khi `$data` chưa khai báo, lại bị ghi đè ngay sau | **Chống trùng alias ở nhánh sửa chưa bao giờ chạy**. Nay xử lý trong `buildCategoryData()` |
| 🐞 | `if($hinh_anh!=''){ }` — thân rỗng | Điều kiện thừa, đã bỏ |
| ♻️ | Nhánh thêm và sửa lặp ~20 trường `$data[...]` y hệt | Sửa một bên dễ quên bên kia → gom vào `buildCategoryData()` |
| 🔴 | `them_tpl.php` nối `$editId` + mã ngôn ngữ vào SQL | Đã chuyển sang `safeFetchOne()` |

Phần xoá gom vào `xoaDanhMucTheoIds()` dùng chung cho xoá 1 và xoá hàng loạt —
trước đây 2 nhánh xử lý cây con theo 2 cách khác nhau. Ảnh xoá qua `basename()`
để chặn path traversal.

**Comment**: `hienthi_tpl.php` 52 → 27 dòng, `them_tpl.php` 37 → 28 dòng.
Bỏ phần mô tả "bản cũ có gì" (thuộc lịch sử commit, không phải code), giữ chú
thích giải thích **tại sao** — nhất là ràng buộc tên trường giữa template và
controller.

**Kiểm thử**: `php admin/_backup_ui/test-category.php` — **15/15 đạt**
(tạo danh mục cha/con tạm trên DB thật, kiểm tra whitelist cột, chặn payload
xoá hàng loạt, ký tự `(` `'` giữ nguyên, rồi tự dọn dữ liệu).

### Điều chỉnh card-header (ảnh hưởng MỌI module)

Xem thực tế trên trình duyệt thấy thanh công cụ **tràn xuống 2 hàng** và nút
"Thêm mới" bị đẩy vào giữa. Đã sửa ở 2 component dùng chung:

| File | Thay đổi |
|---|---|
| `toolbar.php` | Gom tác vụ + tìm kiếm + làm mới vào 1 nhóm `flex-grow-1`; nút "Thêm mới" tách riêng, `flex-shrink-0` nên **luôn nằm sát phải** thay vì phụ thuộc `ms-auto` |
| `card_open.php` | `.card-tools` thêm `flex-grow-1 justify-content-end` để toolbar rộng vẫn nằm cùng hàng với tiêu đề |

Ô lọc chuyển vào card-header (qua prop `extra` của toolbar) nên phần đầu bảng
gọn 1 hàng. Đã kiểm chứng trên trình duyệt: gõ "Kim chi" lọc đúng và **vẫn giữ
dòng cha** "Sản phẩm" để không mất ngữ cảnh cây.

> Ban đầu tôi để **2 ô** (tìm CSDL + lọc trên trang) và phân biệt bằng icon
> kính lúp / phễu. Sau đó nhận ra ô tìm CSDL là dư — xem mục 8.4.

---

## 8. Chuẩn hoá kích thước & dọn CSS cũ

### 8.1 Mọi control dùng dạng *small*

| Component | Class |
|---|---|
| `form/input`, `form/textarea`, `form/editor` | `form-control-sm` |
| `form/select` | `form-select-sm` |
| `toolbar` (ô tìm + select cột) | `form-control-sm` / `form-select-sm` |
| `form/actions_bar` (Lưu / Lưu và Sửa / Quay lại) | `btn-sm` |

Vì các module đều đi qua component nên chỉ cần sửa ở đây là **toàn bộ admin
đồng bộ**, không phải đụng vào từng module.

### 8.2 Không dùng class tự custom từ thời Bootstrap 3

| Trước | Sau |
|---|---|
| `.form-actions-sticky` (CSS tự viết) | `sticky-bottom bg-body border-top pt-3 mt-3` — toàn bộ là class sẵn có của BS5 |

> ⚠️ **`.a_stt` được giữ lại có chủ đích.** Đây KHÔNG phải class trang trí mà
> là **hook JS**: `admin.js` (2 chỗ) và `admin-ui.js` đang bắt sự kiện qua
> selector `.a_stt` để tự lưu số thứ tự. Bỏ đi là mất tính năng. Phần định
> dạng đã chuyển sang class BS5, chỉ giữ tên class làm điểm neo cho JS.

### 8.3 `admin/css/admin.css`: 11.6 KB → **6.0 KB** (giảm 48%)

Xoá **39 class không còn nơi nào dùng**. Đáng chú ý: phần lớn là CSS của **một
dự án tuyển dụng khác lẫn vào** — `box-job`, `thongso_job`, `thongtinungvien`,
`timeline-job`, `emb-cv`, `salary`, `ten_congty`, `name-job`... cùng với CSS
của `control-sidebar-*` và `treeview-menu` (AdminLTE 2 đã bỏ).

Đã đối chiếu trước khi xoá: các class module cũ **vẫn đang dùng** thì giữ
nguyên (`box-title`, `box-img2`, `img_object50`, `list-tags`, `box-thuoctinh`,
`item-album`, `delinfo`...).

> 📌 Số **dòng** của file lại tăng (232 → 391) vì trình định dạng của IDE tách
> mỗi thuộc tính CSS ra 1 dòng riêng. Phải so **dung lượng** mới đúng.

### 8.4 Bỏ form tìm kiếm dư ở trang danh mục

Trang này nạp **toàn bộ** danh mục trong 1 query rồi render hết ra cây, nên mọi
bản ghi đã có sẵn trên trang. Việc có thêm form tìm phía server là dư: chậm hơn
(tải lại trang) mà kết quả lại **đổ ra danh sách phẳng**, mất cấu trúc cây.

Nay chỉ còn **1 ô lọc client-side**. Controller vẫn xử lý `?search=&key=`
(có whitelist cột) để **link cũ không bị vỡ**.

### Kiểm thử

```
php admin/_backup_ui/scan-css.php    # đối chiếu class CSS vs class đang dùng
php admin/_backup_ui/verify-ui.php   # xác minh 5 điều chỉnh trên (38 test)
```

**Tổng: 161 test đạt / 0 lỗi.** Lint PHP 7.3: 273 file, 0 lỗi.

> 🐞 Ghi chú: lần đầu chạy `verify-ui.php` bị **8 FAIL giả** — phần chú thích ở
> đầu `admin.css` có liệt kê tên các class đã xoá nên `strpos()` khớp vào chính
> comment đó. Đã sửa: bỏ comment trước khi đối chiếu.

Khi migrate module mới: dùng `category` làm mẫu, và **xoá** các file
`dataTables.bootstrap.min.*` (bản BS3) khi không còn module nào dùng.

Thứ tự đề xuất cho các module còn lại (từ nhỏ đến lớn, theo số dòng):
`newsletter` · `text` · `coupon` · `binh-luan` · `flash-sale` · `thanh-vien` ·
`module` · `sitemap` · `ma-khuyen-mai` · `button-contact` · `seo-co-ban` ·
`quan-ly-van-chuyen` · `quan-ly-don-hang` ·
`doanh-thu` · `video` · `upload-file` · `cong-tac-vien` · `gallery` · `filter` ·
`noi-dung` · `ql-user` · `bai-viet` · `menu` (1.279) · `san-pham` (2.445)
### File Manager adapter

- PHP: `admin/lib/filemanager.php` (`fm_driver()`, `fm_button_attrs()`, `fm_ckeditor_options()`)
- JS: `admin/js/ckfinder-adapter.js` (`AdminFM.open`)
- Driver duy nhất: **CKFinder** (`admin/ckfinder/`)
- `admin/filemanager/` (Responsive File Manager) đã được loai bo
- Tính năng **Sao chép đường dẫn Media (URL & Path)**: Xem chi tiết vị trí sửa và cách gỡ bỏ tại [`admin/HUONG-DAN-TINH-NANG-COPY-MEDIA.md`](../HUONG-DAN-TINH-NANG-COPY-MEDIA.md)
