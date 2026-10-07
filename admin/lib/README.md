# Ghi chú nâng cấp `class.php` & `function.php`

Hai file này là **trái tim** của hệ thống. Mức độ phụ thuộc (đo thực tế):

| Hàm | Số lần được gọi |
|---|---|
| `o_fet()` | 205 |
| `simple_fetch()` | 170 |
| `checkPermission*()` | 157 |
| `o_que()` | 90 |
| `insert()` / `update()` | 116 |

> ⚠️ **KHÔNG đổi tên hoặc chữ ký các hàm hiện có.** Chỉ sửa bên trong,
> hoặc *thêm* hàm mới. Đổi chữ ký = vỡ toàn site.

---

## Đã sửa (đợt 1)

### Lỗi nghiêm trọng

| # | Lỗi | Hậu quả trước khi sửa |
|---|---|---|
| 1 | `echo $d->sql;` trong `array_category()` (2 chỗ) | Code debug bị bỏ quên; `$d` không tồn tại trong scope → Notice ở **mọi form thêm/sửa danh mục**. Bị `error_reporting(0)` che đi |
| 2 | `$lis_id` chưa khởi tạo trong `getIdsub()` | Notice mỗi lần **xoá danh mục** |
| 3 | `count($query>0)` trong `findIdSub()` | So sánh mảng với 0 *rồi mới* đếm → điều kiện **luôn đúng** (viết đúng: `count($query) > 0`) |
| 4 | `get_magic_quotes_gpc()` trong `clean()` | Hàm **đã bị xoá từ PHP 8.0** → Fatal Error ngay tại hàm dùng cho đăng nhập. Đã bọc `function_exists()` |
| 5 | `query($sql)` bắt buộc tham số nhưng `select()` gọi không truyền | Lỗi nếu dùng `select()`. Đã cho mặc định `$sql = ''` |
| 6 | `connect()` `echo` message của PDO | Lộ tên database/user cho kẻ tấn công. Nay ghi `error_log()` và chỉ hiện thông báo chung |
| 7 | `catchuoi()` dùng `strlen`/`substr` | **Cắt giữa ký tự UTF-8** → chữ tiếng Việt bị lỗi. Đã đổi sang `mb_*` |

### Bảo mật mật khẩu

`md5()`/`sha1()` không có salt → dễ bị dò bằng bảng rainbow.
Đã thêm 4 hàm mới (giữ nguyên `getPassHash()` cũ để verify dữ liệu cũ):

```php
$d->hashPassword($password);                                  // tạo hash bcrypt
$d->verifyPassword($password, $hash, $token);                 // bcrypt + sha1 + md5
$d->needsRehash($hash);                                       // hash cũ -> true
$d->upgradePasswordIfNeeded($id_user, $password, $hash);      // nâng cấp tại chỗ
```

**Các file đã cập nhật:**

| File | Thay đổi |
|---|---|
| `login.php` | Bỏ so sánh mật khẩu trong SQL; xác thực bằng `verifyPassword()` rồi tự nâng cấp hash |
| `sources/ql-user.php` | 3 chỗ lưu mật khẩu (thêm user / sửa user / đổi mật khẩu) dùng `hashPassword()` |
| `templates/thongtin-user/hienthi_tpl.php` | Xác thực mật khẩu cũ bằng `verifyPassword()`; lưu mật khẩu mới bằng `hashPassword()` |

> ⚠️ Lỗi đã sửa: trang `thongtin-user` trước đây so sánh `sha1($old) != $current_pass`.
> Sau khi hash được nâng lên bcrypt, phép so sánh này **luôn sai** → không ai đổi
> được mật khẩu nữa.

Đã bỏ `addslashes()` quanh mật khẩu: bcrypt chỉ sinh ký tự `[A-Za-z0-9./$]` nên
không cần escape, mà `addslashes()` còn **làm sai mật khẩu** nếu người dùng nhập
dấu `'` hoặc `\`.

Cột `pass_hash` là `TEXT` nên đủ chỗ cho bcrypt (60 ký tự) — **không cần sửa DB**.

Người dùng **không phải đặt lại mật khẩu**: hash cũ vẫn đăng nhập được và tự
động chuyển sang bcrypt ngay lần đăng nhập kế tiếp.

### Bảo mật SQL

`insert()` / `update()` nay **bind tham số** thay vì nối chuỗi. Chữ ký không
đổi nên **116 lời gọi hiện tại không phải sửa**.

```php
$d->safeFetch("select * from #_user where email = ? and hien_thi = ?", [$email, 1]);
$d->safeFetchOne("select * from #_user where id = ?", [$id]);   // 1 dòng
$d->safeExec("delete from #_lienhe where id = ?", [$id]);
```

> ⚠️ Module cũ truyền giá trị đã qua `addslashes()`. Trước đây MySQL tự "tháo"
> dấu `\` khi nối chuỗi; với bind thì **không** tháo nữa, nên `bindValues()`
> phải `stripslashes()` để dữ liệu lưu xuống giống hệt như trước.
> Code mới **không cần** `addslashes()`.

Đã ép `(int)` cho `array_category()`, `getIdsub()`, `findIdSub()`.

### Hiệu năng & dữ liệu

| Việc | Trước | Sau |
|---|---|---|
| `array_category()` / `getIdsub()` | N+1 query (12 query với cây 7 node) | **1 query**, dựng cây trong PHP |
| `redirect()` | `<script>window.location` | `header('Location')` khi chưa gửi output |
| `xss_filter()` | Xoá cả `( ) ' " ? [ ]` → *"Công ty (TNHH)"* thành *"Công ty TNHH"* | Chỉ bỏ thẻ HTML + ký tự điều khiển |
| `xss_filterContent()` | Xoá dấu ngoặc nhưng **vẫn để lại** `<script>` | Chặn `script/iframe/object/embed/form`, `on*`, `javascript:`, `data:` |

> Nguyên tắc: chống XSS khi **XUẤT** ra HTML (`e()` / `htmlspecialchars`),
> không phá dữ liệu khi **NHẬN** vào.

### Phân quyền & endpoint AJAX

**`aj_checkbox.php` — lỗ hổng nghiêm trọng nhất đã vá.** Trước đây file này:

| Vấn đề | Hậu quả |
|---|---|
| **Không kiểm tra đăng nhập** | Bất kỳ ai biết URL đều gọi được — không cần tài khoản |
| Tên bảng + tên cột lấy từ `$_POST` | Chỉ dùng `addslashes()`, mà identifier thì escape kiểu này vô nghĩa → ghi được vào **bảng bất kỳ, cột bất kỳ** |
| `id` nối thẳng vào SQL | SQL injection |
| Tên file ảnh không lọc | Xoá file ngoài thư mục upload (path traversal) |

Nay: trả **403** nếu chưa đăng nhập, đối chiếu **whitelist** 15 bảng / 14 cột,
bind tham số, `basename()` cho tên file. Đã kiểm chứng bằng `curl`:

```
Chua dang nhap        -> HTTP 403
Bang khong hop le     -> HTTP 403
```

> 🐞 Khi đối chiếu whitelist với schema thật, phát hiện `#_sanpham_ctv`
> **không có cột `id_code`** — bản cũ dùng `where id_code = ...` nên câu update
> ở bảng này **luôn thất bại mà không báo lỗi**. Đã chuyển sang khoá `id`.

`checkPermission()` / `checkPermission_view|edit|dele()` (**157 lời gọi**) trước
đây nối `$id_page`, `$id_user` trực tiếp vào SQL. Nay bind tham số; riêng
`IN (...)` không bind được nên dùng `intList()` ép từng phần tử về số nguyên.

`check_shell()` trước đây chặn cả từ `'php'`, `'base64'`, `'chr('` → không nhập
được nội dung bình thường như *"Khóa học lập trình PHP"*. Nay dùng regex nhận
diện mã thực thi thật (thẻ `<?php`, `eval(`, `$_GET`, `on*=`, `javascript:`...).

---

## Đợt 2 — Vá SQL ở FRONTEND (bề mặt công khai)

Quét toàn dự án thấy **402 chỗ SQL còn nối chuỗi**. Ưu tiên frontend vì admin
còn có cửa đăng nhập chắn, còn `sources/` thì **ai cũng gọi được**.

| File | Chỗ | Vấn đề đã sửa |
|---|---|---|
| `sources/lib/file_router_index.php` | 17 | Router của **toàn site**, chạy ở mọi request. Alias từ URL nhúng thẳng vào 6 câu SELECT |
| `sources/ajax/ajax_cart.php` | 8 | Mã giảm giá, email, điện thoại, mã tỉnh/huyện/xã chỉ có `addslashes()` |
| `sources/ajax/ajax.php` | 7 | `live_search`: **SQL injection + XSS phản chiếu** |
| `sources/quen-mat-khau.php` | 4 | Truy vấn token đặt lại mật khẩu |
| `sources/dang-nhap.php` | 2 | Xem mục lỗi bên dưới |

### Lỗi phát hiện thêm trong lúc sửa

| Lỗi | Hậu quả |
|---|---|
| `echo $row_tv = $d->num_rows(...)` ở `dang-nhap.php` dòng 25 | Code debug bị bỏ quên: **in một con số ra ngay trang đăng nhập** của khách. Kèm theo là 2 query giống nhau chạy liên tiếp (đếm rồi lấy lại) → đã gộp còn 1 |
| `live_search` in `$keyword` thẳng vào HTML | **XSS phản chiếu**: gửi `<script>alert(1)</script>` là chạy được script. Tên sản phẩm cũng in raw. Nay dùng `htmlspecialchars()` + `urlencode()` |
| `$keyword` không escape `%` `_` | Người dùng nhập `%` là khớp toàn bộ sản phẩm. Nay `addcslashes($kw, '%_\\')` |
| Biến `$where_lang` | **Không được gán ở bất kỳ đâu** trong toàn dự án (đúng ra là hằng `_where_lang`) → lọc theo ngôn ngữ **chưa bao giờ hoạt động**. Đã giữ nguyên hành vi để không đổi kết quả truy vấn, chỉ ghi chú lại |

> `sources/ajax/ajax_reviews.php` đã kiểm tra: mọi giá trị đều `(int)` và có
> clamp `1–50` → **an toàn, không cần sửa**.

### Tiến độ

| Khu vực | Trước | Sau |
|---|---|---|
| `sources/ajax` (công khai) | 12 | **5** |
| `sources` (trang frontend) | 75 | **61** |
| `sources/module` | 11 | 11 |
| `admin/sources` | 95 | 95 |
| `admin/templates` | 209 | 209 |
| **Tổng** | **402** | **381** |

> ⚠️ **CSRF**: trong 4 endpoint frontend chỉ `ajax_review_media.php` kiểm tra
> token. `ajax_cart.php`, `ajax.php`, `ajax_reviews.php` **chưa có** — cần sửa
> cả JS phía client nên tách thành một đợt riêng.

---

## Đã xoá 2 thư viện (150 KB, nạp ở **mọi** request)

> Xem thêm: tối ưu từng module admin được ghi ở
> `admin/templates/components/README.md`.

| File | Xử lý |
|---|---|
| `simple_html_dom.php` (75 KB) | `validate_content()` viết lại bằng `DOMDocument` có sẵn của PHP |
| `resize-class.php` (9.5 KB) | `resize_img()` không còn nơi nào gọi → xoá cả hàm |

Bản gốc: `admin/_backup_ui/lib/removed-libs.zip`

`validate_content()` giữ **nguyên chữ ký và hành vi**, đã test cả 4 chế độ
(13 lời gọi hiện tại — gồm 6 file frontend — không phải sửa):

| `$type` | Hành vi |
|---|---|
| `''` | Bỏ mọi thẻ HTML; nếu có link/URL → trả rỗng (chống spam form) |
| `'0'` | Chỉ bỏ thẻ HTML, cho phép URL |
| khác | Giữ HTML nhưng xoá `<img>`, `<script>`, `<a>` |

> 📌 `sources/lib/function.php` **không** định nghĩa `validate_content()` —
> frontend dùng chung bản trong `admin/lib/`. Nên bản viết lại phục vụ cả hai.

---

## Kết quả kiểm thử

- `validate_content()` 4 chế độ: đúng như bản cũ
- `catchuoi()` tiếng Việt: không còn lỗi font
- Mật khẩu: **10/10** test đạt (`_backup_ui/test-password.php` — tạo user tạm
  trên DB thật, chạy đủ vòng đời rồi tự xoá):
  - Đăng nhập bằng hash sha1 cũ, từ chối mật khẩu sai
  - Tự nâng cấp sang bcrypt sau khi đăng nhập, không nâng cấp lặp lại
  - Đổi mật khẩu khi hash đã là bcrypt (ca lỗi vừa sửa)
  - Mật khẩu chứa `'` `\` `"` vẫn xác thực đúng
- Trang đăng nhập + trang chủ: hiển thị bình thường
- SQL: **13/13** đạt (`_backup_ui/test-sql.php`) — ký tự `'` `"` `\`, tiếng Việt,
  chống injection, `insert`/`update` rỗng trả `false`
- Cây danh mục: **11/11** đạt (`_backup_ui/test-tree.php`) — output **giống hệt**
  bản cũ ở 6 tổ hợp tham số, số query giảm **12 → 1**
- Core bảo mật: **28/28** đạt (`_backup_ui/test-core.php`)
  - `check_shell()` cho qua nội dung hợp lệ (có chữ "php", "base64", `(`, `%`)
  - `check_shell()` chặn `<?php`, `eval(`, `shell_exec(`, `$_GET`, `on*=`, `javascript:`
  - `xss_filter()` giữ nguyên `(TNHH)`, `O'Brien`, `100%`
  - `xss_filterContent()` giữ thẻ định dạng, bỏ `script`/`iframe`/`onerror`
  - Phân quyền từ chối payload `1 or 1=1`, `999 union select 1`

**Tổng: 62 test, 0 lỗi.** Lint PHP 7.3: 264 file, 0 lỗi.

### Đợt 2

- Router: **21/21** (`test-router.php`) — so **trực tiếp** với bản cũ trên
  17 URL thật (category / tintuc / sanpham / album / video / route tĩnh),
  cùng `$source` và `$type`; 3 payload injection đều ra 404
- Giỏ hàng: **12/12** (`test-cart.php`) — qua **HTTP thật**, giữ session bằng
  cookie: thêm giỏ, sửa số lượng, lấy mã giảm giá, tính phí ship, và 3 payload
  injection vào mã giảm giá
- AJAX: **13/13** (`test-ajax.php`) — `quick_view`, `live_search`, wishlist;
  kiểm chứng chặn `<script>`, `onerror=`, và `%` không thành wildcard

**Tổng cộng: 108 test, 0 lỗi.** Lint PHP 7.3: **269 file, 0 lỗi**.

---

## ⚠️ Lưu ý môi trường

**Web server chạy PHP 7.3** dù PHP CLI có thể là 8.x. Luôn lint bằng:

```
C:\laragon\bin\php\php-7.3.33-nts-Win32-VC15-x64\php.exe -l <file>
```

Không dùng: arrow function `fn()`, `??=` (7.4+), `str_contains()`,
nullsafe `?->`, named arguments (8.0+).

---

## Việc CHƯA làm (đề xuất đợt sau)

| # | Việc | Lý do nên làm |
|---|---|---|
| 1 | `getProduct`, `getCate`, `getData`, `getTinh/Huyen/Xa`... | Vẫn nối `$id_code`, `$col`, tên bảng vào SQL. Nên chuyển dần sang `safeFetch()` + whitelist cột |
| 2 | `o_fet()`/`simple_fetch()` dùng `FETCH_ASSOC` | Hiện trả cả index số lẫn tên cột → tốn gấp đôi RAM. **Rủi ro cao**: 375 lời gọi, một số nơi có thể đang đọc theo `$row[0]` |
| 3 | Tách class thành trait | 1 class đang ôm DB + phân quyền + XSS + menu + địa chỉ + YouTube... |
| 4 | Bỏ dần `addslashes()` ở module | Sau khi `insert`/`update` đã bind, lớp `addslashes` ở module trở thành thừa |
| 5 | `admin/sources/ajax.php` (645 dòng, 24 chỗ) | Đã có kiểm tra session, nhưng còn nhiều câu SQL nối chuỗi |
| 6 | CSRF token cho `ajax_cart.php`, `ajax.php`, `ajax_reviews.php` | Cần sửa cả JS phía client |
| 7 | `sources/module` (11 chỗ) + `sources` (61 chỗ còn lại) | Tiếp tục đợt vá frontend |

### Script kiểm thử

```
php admin/_backup_ui/test-password.php   # vòng đời mật khẩu (10 test)
php admin/_backup_ui/test-sql.php        # insert/update + injection (13 test)
php admin/_backup_ui/test-tree.php       # so cây danh mục mới vs cũ (11 test)
php admin/_backup_ui/test-core.php       # check_shell, xss_filter, phân quyền (28 test)
php admin/_backup_ui/test-router.php     # router mới vs cũ (21 test)
php admin/_backup_ui/test-cart.php       # giỏ hàng qua HTTP thật (12 test)
php admin/_backup_ui/test-ajax.php       # ajax.php: injection + XSS (13 test)
php admin/_backup_ui/check-keys.php      # đối chiếu whitelist với schema thật
php admin/_backup_ui/scan-toggle.php     # liệt kê bảng/cột dùng cho công tắc
php admin/_backup_ui/scan-sql.php        # đếm chỗ SQL còn nối chuỗi
php admin/_backup_ui/render-test2.php    # render module đã migrate
```

⚠️ **Xoá thư mục `admin/_backup_ui/` khi lên production** — các script này chạy
được mà không cần đăng nhập.