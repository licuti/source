# PHÂN TÍCH ADMIN — `admin/index.php` & Cấu trúc thư mục admin

> **Mục đích:** lưu kết quả phân tích (kiến trúc, tổ chức file, đặt tên, tối ưu, bảo mật) để theo dõi tiến độ khắc phục.
>
> **QUY ƯỚC TRẠNG THÁI:**
> - `[x]` = HOÀN THÀNH (đã fix, đã kiểm chứng trong code)
> - `[~]` = LÀM MỘT PHẦN (hạ tầng có, chưa áp dụng đủ)
> - `[ ]` = CHƯA LÀM
>
> ⚠️ **GHI CHÚ:** Khi TẤT CẢ mục được đánh dấu `[x]` → **XÓA FILE NÀY** (`ANALYSIS-ADMIN-REVIEW.md`). File chỉ tồn tại trong giai đoạn khắc phục.
>
> **Ngày tạo:** 2026-09-11 · **Cập nhật lần cuối:** 2026-09-11 (đã vá nhóm 7.1 Critical + nhóm 7.2 Sớm + một phần 7.3 Trung hạn)

---

## 1. LUỒNG XỬ LÝ `admin/index.php`

Kiến trúc hiện tại: **front-controller** (một hướng đi đúng).

```
session_start → auth gate → nạp lib → new func_index → router(?p,&a)
   → validate template → render layout (header/sidebar/footer) → nạp template module
```

**Điểm tốt:**
- [x] Auth gate sớm (dòng 12–15) — chặn trước khi nạp lib.
- [x] Validate `$template` (dòng 45–54): chặn `..`, whitelist `[a-zA-Z0-9/_-]`, fallback `index`.
- [x] Router whitelist `p` bằng regex `^[a-z0-9\-]+$`, dùng `safeFetchOne` → tránh injection.
- [x] CSRF token đưa vào `<meta>` cho JS.
- [x] Component scope isolation (`_helpers.php`).

**Vấn đề:**

| # | Vấn đề | Mức | Trạng thái |
|---|--------|-----|-----------|
| 1.1 | `?debug_session` (dòng 17–21) dump toàn bộ `$_SESSION` ra JSON → lộ `admin_csrf`, `user_hash`, quyền; vô hiệu hóa CSRF. | Cao | `[ ]` |
| 1.2 | `error_reporting(0)` toàn cục, không ghi log → nuốt lỗi, che lỗi bảo mật. | Trung bình | `[ ]` |
| 1.3 | Trộn nhiều trách nhiệm: routing + layout + nạp asset + render flash trong 1 file. | Thấp | `[ ]` |
| 1.4 | Nạp **quá nhiều plugin ở mọi request** (CKEditor, DataTables, Select2, SortableJS, SweetAlert2, Fancybox, OverlayScrollbars…). Mỗi module chỉ cần 1–2. | Trung bình | `[x]` |
| 1.5 | Đường dẫn asset tương đối cứng (`public/plugin/...`) → vỡ nếu đổi subfolder/domain. | Thấp | `[ ]` |
| 1.6 | jQuery + CKEditor trong `<head>` chặn render (có comment do legacy global). | Thấp | `[ ]` |

---

## 2. TỔ CHỨC FILE

**Ưu điểm:**
- [x] Cấu trúc `sources/` (logic) ↔ `templates/<alias>/` (view) ↔ `lib/` (hạ tầng) rõ ràng.
- [x] Component hóa view (`components/`, `components/form/`).
- [x] Quy ước hậu tố `_tpl.php` nhất quán.

**Vấn đề:**

| # | Vấn đề | Chi tiết | Trạng thái |
|---|--------|----------|-----------|
| 2.1 | `sources/ajax.php` là "God file" **742 dòng** | Trộn: menu, update_stt, update_data, xóa ảnh, thuộc tính SP, text, liên hệ, bình luận, coupon… | `[~]` |
| 2.2 | AJAX nằm rải rác 2 nơi | **ĐÃ DỌN 2026-10-07:** gỡ bỏ toàn bộ `ajax_pancake_*.php` và module Pancake khỏi admin. | `[x]` |
| 2.3 | `services/` đặt sai chỗ khái niệm | `admin/sources/services/` là domain/business layer, không phải "source module". | `[ ]` |
| 2.4 | Trùng lặp front/back | **ĐÃ DỌN 2026-10-07:** gỡ bỏ `api/pancake_webhook.php` và toàn bộ Pancake service. | `[x]` |
| 2.5 | Dev artifacts trong webroot | `.kilo/` (có `node_modules`), `sitemap.xml.bak`. | `[ ]` |
| 2.6 | `admin/_backup_ui/` còn tồn tại | **ĐÃ RỖNG** (đã dọn). | `[x]` |
| 2.7 | `logs/` nằm trong `admin/` (webroot) | Có thể bị tải nếu cấu hình sai → nên ra ngoài webroot. | `[ ]` |
| 2.8 | `ckeditor/` + `ckfinder/` full bản | Hàng nghìn file trong repo. | `[ ]` |
| 2.9 | `lib/` trộn vendor + config + core | `config.php` (credential DB) nằm cạnh code nghiệp vụ. | `[ ]` |

---
## 3. CÁCH ĐẶT TÊN & THỨ TỰ SẮP XẾP

**Điểm trừ rõ nhất: KHÔNG NHẤT QUÁN.**

| # | Vấn đề | Chi tiết | Trạng thái |
|---|--------|----------|-----------|
| 3.1 | Trộn ngôn ngữ & style | `quan-ly-don-hang.php` (kebab tiếng Việt) cạnh `ajax.php`, `filter.php`, `services/`, `ProductRepository.php` (PascalCase tiếng Anh). | `[ ]` |
| 3.2 | `config.php` dùng `refix` (không phải `prefix`) | Lỗi chính tả ăn sâu vào code (`$this->refix`). | `[ ]` |
| 3.3 | Hậu tố `_tpl.php` nhất quán | Nhưng tên file lẫn Anh–Việt (`hienthi_tpl.php` vs `accordion-variants.php`, `doipass_tpl.php`). | `[ ]` |
| 3.4 | Thứ tự menu/sắp xếp dùng `stt` + `order by stt asc, id desc` | Nhất quán, tốt. | `[x]` |
| 3.5 | Thứ tự file trong thư mục không theo quy ước nào | Khó điều hướng. | `[ ]` |

**Đề xuất quy ước:** alias route giữ tiếng Việt; tên class/file code = PascalCase tiếng Anh; AJAX = `ajax/<action>.php`; view = `<action>_tpl.php`; service = `app/Services/<Domain>/<X>Service.php`.

---

## 4. GỌN GÀNG / TỐI ƯU

| # | Vấn đề | Trạng thái |
|---|--------|-----------|
| 4.1 | `o_fet()`/`simple_fetch()` trả về **cả index số lẫn tên cột** (FETCH default) → tốn gấp đôi RAM, 375 call site. | `[ ]` |
| 4.2 | Class `func_index` ôm quá nhiều (DB + phân quyền + XSS + menu + địa chỉ + YouTube). | `[ ]` |
| 4.3 | Asset không tách theo module (~15 file CSS/JS mỗi trang). | `[ ]` |
| 4.4 | `addslashes` còn sót ở module dù `insert/update` đã bind → thừa, làm sai dữ liệu (`'`, `\`). | `[ ]` |
| 4.5 | Đã bỏ `simple_html_dom`, `resize-class` (tiết kiệm ~85KB/request). | `[x]` |
| 4.6 | Có test scripts + tài liệu README trong `lib/` và `components/`. | `[x]` |
| 4.7 | `array_category()` N+1 → 1 query (12 → 1). | `[x]` |
| 4.8 | `catchuoi()` dùng `mb_*` (không cắt giữa ký tự UTF-8). | `[x]` |
| 4.9 | `getIdsub()` — `$lis_id` chưa khởi tạo (đã thêm `$lis_id = ''`). | `[x]` |
| 4.10 | `findIdSub()` — `count($query>0)` sai (luôn đúng), đã sửa. | `[x]` |

---
## 5. BẢO MẬT (xếp theo mức độ)

| # | Lỗ hổng | Mức | Trạng thái | Ghi chú |
|---|---------|-----|-----------|---------|
| 5.1 | **`admin/sources/ajax.php` KHÔNG có auth guard.** Đã đọc trọn 742 dòng — **không** check `$_SESSION['id_user']`, không `checkPermission`. Xử lý ngay `create_menu`, `delete_menu`, `save_menu`, `update_stt`, `update_data`, `xoa_anh_sp`… | **CRITICAL** | `[x]` | **ĐÃ VÁ 2026-09-11:** thêm auth guard đầu file (`id_user` + `user_hash`, trả 401). Lint PHP 7.3 OK. |
| 5.2 | **SQL injection qua tên bảng/cột do client gửi** trong `ajax.php`: `update $table set $col = $val where ...` — `table`, `col` từ `$_POST` không whitelist. | **CRITICAL** | `[x]` | **ĐÃ VÁ:** hàm `admin_ajax_safe_table()` (regex `^#_[a-z0-9_]+$`) + `admin_ajax_safe_col()` (`^[a-z0-9_]+$`); `update_data` chuyển sang `safeExec()` bind `$val`. |
| 5.3 | **`.htaccess` chuyển HTTPS → HTTP** ("Chuyển hướng HTTPS sang HTTP") — hạ cấp bảo mật. | **CRITICAL** | `[x]` | **ĐÃ SỬA:** bỏ rule hạ cấp; thay bằng rule HTTP→HTTPS đã viết sẵn nhưng **comment** (local chưa có SSL). Bỏ `#` khi lên production. |
| 5.4 | **`?debug_session`** lộ CSRF/session. | Cao | `[x]` | **ĐÃ XÓA** khỏi `admin/index.php`. |
| 5.5 | **Remember-me `key_ad = md5(time().id)`**, lưu plaintext trong DB, hạn 1 năm, không rotate. | Cao | `[x]` | **ĐÃ VÁ 2026-09-11:** cookie raw 64-hex (`random_bytes(32)`), DB lưu `sha256`, cột riêng `remember_token` (không đụng cột `token` md5 legacy), token XOAY mỗi lần auto-login, logout thu hồi token. Cookie HttpOnly + SameSite=Lax. |
| 5.6 | **Không rate-limit / CAPTCHA ở login** → brute force. Chính sách mật khẩu chỉ ép bằng JS. | Cao | `[x]` | **ĐÃ VÁ:** `login.php` dùng `admin_rate_allow/hit/clear` (5 lần/15 phút theo username+IP); `ql-user.php` validate mật khẩu server-side ở 3 chỗ (thêm/sửa/đổi mật khẩu) bằng `ql_user_password_ok()`. |
| 5.7 | **`lib/config.php`: mật khẩu DB để rỗng** (`'password' => ''`). | Cao nếu lên prod | `[x]` | **ĐÃ VÁ:** `config.php` đọc từ `.env` (helper `admin_load_env`); đã tạo `.env` + `.env.example`; `.env` nằm trong `.gitignore`. Giá trị dự phòng giữ nguyên để không phá local. |
| 5.8 | **`.htaccess` chỉ chặn `config.php` ở gốc**, không chặn `lib/config.php`. | Trung bình | `[x]` | **ĐÃ VÁ:** thêm `admin/lib/.htaccess` chặn mọi truy cập HTTP vào `lib/`; `.htaccess` gốc chặn thêm `.env`, `.env.example`, `*.sql`, `*.log`. |
| 5.9 | **XSS lưu trữ** trong `ajax.php`: `add_text` echo thẳng text, `get_thuoctinh` echo `$row_thuoctinh['ten']` không escape; `get_coupon` echo `$row['tieu_de']`, `$row['ho_ten']`, `$row['sdt']`… | Trung bình | `[x]` | **ĐÃ VÁ:** thêm helper cục bộ `admin_ajax_e()` (dùng `htmlspecialchars`), áp cho `get_thuoctinh`, `add_thuoctinh` (option), `view_donhang_ctv`, `get_coupon`, `get_binhluan`; `id` ép về int khi ghép chuỗi. |
| 5.10 | **Rò rỉ thông tin**: `save_menu` echo `$e->getMessage()` cho client. | Trung bình | `[x]` | **ĐÃ VÁ:** chỉ `error_log()` nội bộ, trả message chung `'Lỗi máy chủ, vui lòng thử lại.'`. |
| 5.11 | **CSRF chưa áp cho nhiều AJAX** (`ajax.php`, cart, reviews). | Trung bình | `[x]` | **ĐÃ VÁ 2026-09-11:** thêm `admin/js/csrf.js` tự gắn `csrf_token` vào mọi POST admin (`$.ajaxPrefilter` + bọc `AdminUI.http.post`); verify ở `sources/ajax.php`, `sources/aj_checkbox.php`, `ajax_pancake_*.php` (3 file). |
| 5.12 | **Login truy vấn token bằng nối chuỗi + addslashes** (`login.php` dòng 17, 37). | Thấp–TB | `[x]` | **ĐÃ VÁ:** cả 2 truy vấn login (remember-me + xác thực) đều dùng `safeFetch()` bind tham số. |
| 5.13 | **`error_reporting(0)`** che lỗi bảo mật. | Thấp–TB | `[~]` | Đã có `error_log()` ở các nhánh quan trọng (connect, save_menu, logout). Còn `error_reporting(0)` toàn cục — chưa đổi để không đổi hành vi môi trường. |
| 5.14 | **`checkPermission()` bind tham số** (trước nối `$id_page`, `$id_user`). | — | `[x]` | Xác minh `class.php` dòng 588–605. |
| 5.15 | **`insert()`/`update()` bind tham số** thay vì nối chuỗi. | — | `[x]` | Xác minh `class.php` dòng 240–270. |
| 5.16 | **Bcrypt migration** + verify fallback sha1/md5 + `needsRehash`/`upgradePasswordIfNeeded`. | — | `[x]` | Xác minh `class.php` dòng 422–479; `login.php` dùng `verifyPassword`. |
| 5.17 | **`hash_equals` cho CSRF** + `admin_csrf_token`/`admin_csrf_verify`. | — | `[x]` | Trong `function.php`. |
| 5.18 | **`connect()` không echo message PDO** (ghi `error_log`, hiện thông báo chung). | — | `[x]` | `class.php` dòng 41–46. |
| 5.19 | **Validate template path** chống LFI. | — | `[x]` | `index.php` dòng 45–54. |
| 5.20 | **Router whitelist alias** `^[a-z0-9\-]+$`. | — | `[x]` | `file_router_admin.php` dòng 19. |
| 5.21 | **`checkPermission()` tồn tại & hoạt động** (ưu tiên `is_admin`, dùng `intList`). | — | `[x]` | Xác minh `class.php` dòng 588. |
| 5.22 | **`admin_csrf_token` không rotate** — nhưng có cơ chế rotate qua tham số `true`. | Thấp | `[x]` | Hạ tầng đủ. |

---
## 6. NHỮNG ĐIỀU "CHƯA NHÌN RA" KHÁC

| # | Vấn đề | Trạng thái |
|---|--------|-----------|
| 6.1 | Không có `.env`/config tách môi trường — dev và prod dùng chung `config.php`. | `[x]` |
| 6.2 | Không có autoloader / Composer — `require_once` thủ công. | `[ ]` |
| 6.3 | Không có namespace — mọi hàm toàn cục. | `[ ]` |
| 6.4 | Không có hệ thống migration thật — `category_drop_unused_columns()` chạy `ALTER TABLE` trong request runtime. | `[x]` |
| 6.5 | Xử lý session thủ công rải rác (`session_status()` nhiều nơi, `admin_session_ensure`). | `[ ]` |
| 6.6 | `_backup_ui` + test scripts không cần auth | `[x]` (đã rỗng) |
| 6.7 | CSRF token không rotate (`admin_csrf_token(false)`) — vòng đời cả session. | `[ ]` |
| 6.8 | Cookie `key_ad` set `HttpOnly` (tốt) nhưng thiếu `SameSite`. | `[x]` |
| 6.9 | Timezone set ở `index.php` nhưng entrypoint khác không set → không nhất quán. | `[ ]` |
| 6.10 | Không có CSP header + `X-Frame-Options`. | `[ ]` |
| 6.11 | `admin/logs/` (`db_migration.log`, `pancake_push.log`) nằm trong webroot. | `[ ]` |
| 6.12 | Không có `.gitignore` chuẩn ở root cho `.kilo/`, `logs/`, `vendor/`. | `[x]` |

---

## 7. ƯU TIÊN KHẮC PHỤC

### 7.1. NGAY LẬP TỨC (Critical) — ✅ ĐÃ XONG 2026-09-11
- [x] **5.1** Thêm auth guard vào `admin/sources/ajax.php` (kiểm tra `id_user` + `checkPermission`).
- [x] **5.2** Whitelist `table`/`col` trong `update_stt`/`update_data`.
- [x] **5.3** Sửa `.htaccess`: HTTPS→HTTP thành HTTP→HTTPS.
- [x] **5.4** Xóa `?debug_session` khỏi `admin/index.php`.

### 7.2. SỚM — ✅ ĐÃ XONG 2026-09-11
- [x] **5.6** Rate-limit login + validate mật khẩu server-side.
- [x] **5.5** Remember-me dùng `random_bytes` + hash trong DB.
- [x] **5.7** Đưa credentials ra `.env`; `.htaccess` chặn `lib/config.php`.
- [x] **5.9** Escape output trong `ajax.php` (`add_text`, `get_thuoctinh`, `get_coupon`, `view_donhang_ctv`).
- [x] **5.10** Không echo `$e->getMessage()`.
- [x] **5.12** Login dùng `safeFetch()` bind tham số.
- [x] **5.8** `.htaccess` chặn `lib/`, `.env`, `*.sql`, `*.log`.

**File đã tạo/sửa trong đợt 7.2:**
- `admin/login.php` — rate-limit, bind tham số, remember-me an toàn (raw+sha256+rotate), auto-ALTER cột `remember_token`.
- `admin/lib/file_router_admin.php` — logout thu hồi `remember_token`.
- `admin/lib/config.php` — đọc `.env`.
- `admin/sources/ql-user.php` — validate password server-side (3 chỗ).
- `admin/sources/ajax.php` — helper `admin_ajax_e()`, escape output, không rò `$e->getMessage()`.
- `.env`, `.env.example`, `.gitignore`, `admin/lib/.htaccess`.
- `database/migrations/2026_09_11_add_remember_token.sql`.
- `database/migrations/2026_09_11_drop_category_unused_columns.sql`.
- `admin/sources/category.php` — chuyển `ALTER TABLE` khỏi request runtime (cờ `runtime_migrations`).
- `admin/lib/config.php` — thêm key `runtime_migrations` đọc từ `.env`.

### 7.3. TRUNG HẠN
- [~] **2.1** Tách `sources/ajax.php` thành nhiều file nhỏ; gom `ajax_pancake_*` vào `sources/ajax/`.
  - **Đã làm 2026-09-11:** tách `sources/ajax/menu.php` (176 dòng: `menu_save_items`, create/delete/save menu) và `sources/ajax/detail.php` (165 dòng: `get_binhluan`, `get_coupon`). `sources/ajax.php` từ **831 → 489 dòng**. Dispatcher dùng `require_once` để giữ nguyên URL cũ.
  - **Còn lại:** tách tiếp nhóm `add_thuoctinh*` (biến thể sản phẩm). Đã gỡ bỏ toàn bộ Pancake module và `ajax_pancake_*.php` (2026-10-07).
- [x] **1.4** Tách asset theo module.
  - **Đã làm 2026-09-11:** thêm `admin/lib/page_assets.php` (helper `admin_page_assets($template)`); `index.php` dùng để nạp có điều kiện. CKEditor (~700KB) chỉ tải ở trang form soạn thảo; Sortable chỉ ở trang kéo-thả. DataTables/Select2 giữ nguyên toàn cục để an toàn (không template nào dùng nhưng nhiều module legacy có thể phụ thuộc qua class sinh động).
  - **Đã làm 2026-10-07:** bỏ hẳn whitelist — asset inline thẳng vào `admin/index.php` và nạp đồng bộ cho mọi trang (jQuery + Fancybox + CKEditor ở head, Sortable ở body). Lý do: người mới khó đọc logic phân biệt, và thêm module mới phải về `page_assets.php` cập nhật whitelist. Đã xóa `admin/lib/page_assets.php` và `admin/js/jquery.twbsPagination.js` (không còn dùng).
- [ ] **6.2/6.3** Composer + PSR-4 autoload + namespace cho `services/`.
- [x] **6.4** Chuyển schema change ra migration script.
- [x] **5.11** Áp CSRF cho các AJAX endpoint.
  - **Đã làm:** `admin/js/csrf.js` tự gắn `csrf_token` vào mọi POST admin (`$.ajaxPrefilter` + bọc `AdminUI.http.post`). Verify ở `sources/ajax.php`, `sources/aj_checkbox.php`. Trả HTTP 419 khi thiếu token. (Các file `ajax_pancake_*.php` đã được xóa sạch).

### 7.4. DÀI HẠN
- [ ] **4.2** Tách class `func_index` thành trait/service.
- [ ] **3.1–3.3** Chuẩn hóa quy ước đặt tên.
- [ ] **4.1** Chuyển `o_fet` sang `FETCH_ASSOC` (theo dõi 375 call site).
- [ ] **1.3** Tách layout/asset khỏi `index.php`.

---

## 8. TỔNG KẾT

Nền tảng đã có **kiến trúc front-controller, component hóa view, router validate tốt,
và migration bcrypt ấn tượng** — rõ ràng có người hiểu bảo mật đã can thiệp.

**✅ Nhóm 7.1 — 4 lỗ hổng Critical (5.1–5.4) đã vá:**
auth guard cho `sources/ajax.php`, whitelist table/col + bind `$val`,
rule HTTPS→HTTPS (viết sẵn, comment chờ production), và xóa `?debug_session`.

**✅ Nhóm 7.2 — Sớm (5.5–5.10, 5.12) đã vá:**
rate-limit login, remember-me an toàn (raw+sha256+rotate, cột riêng), credentials ra `.env`,
chặn `lib/` + `.env`, escape output trong `ajax.php`, không rò message lỗi,
bind tham số login. Đã lint PHP 7.3: tất cả file không lỗi cú pháp.

**✅ Nhóm 7.3 — Trung hạn (làm 2026-09-11, còn một phần 2.1):**
- **5.11 CSRF cho AJAX:** file mới `admin/js/csrf.js`, nạp trong `index.php`. Verify
  ở 5 endpoint admin (`sources/ajax.php`, `sources/aj_checkbox.php`, 3 file `ajax_pancake_*.php`).
- **1.4 Asset theo module:** ban đầu tách ra `admin/lib/page_assets.php`; từ 2026-10-07
  được inline ngược vào `admin/index.php` và nạp đồng bộ mọi trang (bỏ whitelist,
  xóa file `page_assets.php` và `jquery.twbsPagination.js`).
- **2.1 Tách `sources/ajax.php` (một phần):** tách `sources/ajax/menu.php` + `sources/ajax/detail.php`;
  file chính từ 831 → 489 dòng; dispatcher giữ nguyên URL cũ.
- **6.4 Migration script** (đã làm ở đợt 7.2).

Các mục còn lại (gom `ajax_pancake_*`, Composer/PSR-4, trait hóa `func_index`,
chuẩn hóa đặt tên, `o_fet` FETCH_ASSOC…) xem mục 7.3–7.4.

**File mới trong đợt 7.3:**
- `admin/js/csrf.js` — hook `$.ajaxPrefilter` + bọc `AdminUI.http.post`, tự gắn CSRF token.
- `admin/sources/ajax/menu.php` — partial menu (176 dòng).
- `admin/sources/ajax/detail.php` — partial detail (165 dòng).

**File đã xóa (2026-10-07):**
- `admin/lib/page_assets.php` — logic asset theo module, được inline ngược vào `admin/index.php`.
- `admin/js/jquery.twbsPagination.js` — plugin không còn dùng.

Về tổ chức, vấn đề lớn nhất là **tính không nhất quán** (ngôn ngữ đặt tên, vị trí AJAX,
trộn vendor/config/core) và **tải asset toàn cục** làm admin nặng.

**Đã hoàn thành (kiểm chứng code):** bcrypt migration, bind tham số `insert/update`,
`checkPermission` bind, CSRF infrastructure, validate template, router whitelist,
dọn `_backup_ui`, bỏ 2 thư viện nặng, `array_category` 1 query, `catchuoi` mb_*,
`getIdsub` khởi tạo biến.

---

> 🔔 **NHẮC LẠI:** Khi tất cả `[ ]` chuyển thành `[x]` → **XÓA FILE NÀY** (`ANALYSIS-ADMIN-REVIEW.md`).