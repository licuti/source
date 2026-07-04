# 🗺️ ADMIN PANEL DEVELOPMENT ROADMAP

Tài liệu theo dõi tiến độ chuyển đổi toàn bộ chức năng từ hệ thống cũ sang kiến trúc MVC mới. Các chức năng được phân nhóm **bám sát 100% theo dữ liệu của bảng `db_module_admin`** trong database.

**Chú thích trạng thái:**
- 🟢 **Hoàn thành:** Controller + View + Logic đầy đủ, đã hoạt động ổn định.
- 🟡 **Đang PT:** Đã có Route + Controller cơ bản, chưa có giao diện View hoặc logic thực tế.
- 🔴 **Chưa làm:** Chưa có Controller, chưa có Route trong `routes/admin.php`.

---

## 1. ⚙️ HỆ THỐNG & PHÂN QUYỀN (System & Auth)

| ID | Chức năng (Alias DB) | Controller MVC | Trạng thái |
|---|---|---|---|
| N/A | **Đăng nhập & Xác thực** | `AuthController.php` | 🟢 Hoàn thành |
| N/A | **Nhóm quyền (Role)** (`role`) | `RoleAdminController.php` | 🟢 Hoàn thành |
| 27 | **Tài khoản quản trị** (`ql-user`) | `UserAdminController.php` | 🟢 Hoàn thành |

### 🟢 Chi tiết: AuthController (Đăng nhập & Xác thực)
- **`login()`**: Hiển thị trang đăng nhập Admin.
- **`loginPost()`**: Xác thực thông tin đăng nhập, so sánh mật khẩu đã hash, tạo Session `admin_logged_in`. Caching toàn bộ ma trận phân quyền vào `$_SESSION['role_permissions']`.
- **`logout()`**: Hủy toàn bộ Session, redirect về trang đăng nhập.
- **`BaseAdminController`**: Lớp cha tự động kiểm tra Session.
- **`AdminAuthMiddleware`**: Middleware kiểm tra quyền truy cập (Role-Based Access Control - RBAC) dựa trên cấu hình nhóm quyền, ngăn chặn cả HTTP và AJAX không hợp lệ.

### 🟢 Chi tiết: RoleAdminController (Quản lý Nhóm quyền)
- **Cấu trúc DB mới:** Bảng `db_roles` chứa danh sách nhóm, `db_role_permissions` chứa ma trận quyền.
- **Chức năng:** Thêm, Sửa, Xóa nhóm quyền. Phân quyền chi tiết (Xem, Thêm, Sửa, Xóa) cho từng Module.
- **Bảo mật:** Gắn cờ `is_system` cho các nhóm mặc định không thể sửa tên/xóa.

### 🟢 Chi tiết: UserAdminController (Tài khoản quản trị)
- **Chức năng:** CRUD tài khoản quản trị viên.
- **Giao diện:** Gắn danh sách tài khoản với nhóm quyền tương ứng, tự động lọc qua Pagination eager-loading (`qbPaginate`).

---

## 2. 🎛️ CẤU HÌNH HỆ THỐNG (Settings)

| ID | Chức năng (Alias DB) | Controller MVC | Trạng thái |
|---|---|---|---|
| 103 | **Cấu hình Ngôn ngữ** (`language`) | `LanguageSettingController` | 🟢 Hoàn thành |
| 26 | **Dịch chuỗi ngôn ngữ** (`text`) | `TextTranslationController` | 🟢 Hoàn thành |
| 54 | **Quản lý Menu Website** (`menu`) | `MenuController` | 🟢 Hoàn thành |
| N/A | **Menu Hệ thống (Admin Sidebar)** (`system-menu`) | `MenuAdminController` | 🟢 Hoàn thành |
| 101 | **Cấu hình Email / SMTP** (`email-smtp`) | `EmailController` | 🟢 Hoàn thành |
| 102 | **Tích hợp API / Scripts** (`api-integration`) | `ApiIntegrationController` | 🟢 Hoàn thành |
| 104 | **Sao lưu & Cache** (`backup-cache`) | `BackupController` | 🟢 Hoàn thành |
| 105 | **Chế độ bảo trì** (`maintenance`) | `MaintenanceController` | 🟢 Hoàn thành |
| 25 | **Cấu hình Website** (`setting`) | `SettingController` | 🟢 Hoàn thành |
| 28 | **Cấu hình SEO cơ bản** (`seo-co-ban`) | `SeoConfigController` | 🔴 Chưa làm |
| 39 | **Sitemap** (`sitemap`) | `SitemapController` | 🔴 Chưa làm |
| 53 | **Button Contact** (`button-contact`) | `ContactButtonController` | 🔴 Chưa làm |

### 🟢 Chi tiết: LanguageSettingController (Cấu hình Ngôn ngữ)
- **`index()`**: Hiển thị danh sách ngôn ngữ, sắp xếp theo `sort_order`.
- **`create()`**: Hiển thị form thêm ngôn ngữ mới.
- **`store()`**: Lưu ngôn ngữ mới vào bảng `db_lang`. Validate `code` không được trống và không được trùng. Nếu đánh dấu là `is_default` thì ép `is_active = 1` và tự động tắt `is_default` của tất cả ngôn ngữ khác. Sau khi lưu, gọi `generateConfigFile()`.
- **`edit()`**: Hiển thị form chỉnh sửa, load dữ liệu ngôn ngữ theo ID.
- **`update()`**: Cập nhật ngôn ngữ theo ID. Validate trùng `code` với bản ghi khác. Logic xử lý `is_default` tương tự `store()`. Sau khi lưu, gọi `generateConfigFile()`.
- **`destroy()`**: Xóa ngôn ngữ theo ID. **Ngăn chặn xóa ngôn ngữ mặc định** (`is_default = 1`). Sau khi xóa, gọi `generateConfigFile()`.
- **`generateConfigFile()` (private)**: Lấy toàn bộ ngôn ngữ đang `is_active = 1` từ DB, tự động ghi đè lên file `config/languages.php`. File này được ứng dụng đọc để biết danh sách ngôn ngữ hỗ trợ mà không cần query DB.

### 🟢 Chi tiết: BackupController (Sao lưu & Cache)
- **Quản lý Cache**: Hỗ trợ dọn dẹp linh hoạt từng phần (chỉ xóa Logs hoặc chỉ làm mới OPcache) hoặc xóa toàn bộ để tối ưu dung lượng và tốc độ web.
- **Quản lý Sao lưu DB**: Cho phép sao lưu an toàn Cơ sở dữ liệu ra file `.sql` và tải về trực tiếp. Tích hợp tính năng **Phục hồi 1-Click** (Restore) có xác nhận 2 lớp bằng chữ "RESTORE" để chống bấm nhầm gây mất dữ liệu.
- **Sao lưu Mã nguồn**: Nén toàn bộ web thành `.zip` để bảo lưu tài nguyên. Tự động loại trừ thông minh các thư mục hệ thống/tiến trình ngầm đang chạy (`storage`, `.git`, `.vscode`, `node_modules`, `.claude`, v.v) và xử lý mượt mà lỗi khóa file Windows bằng cơ chế nén trong RAM với `addFromString` cho file nhỏ (<5MB).
- **Tự động hóa (Cronjob)**: Gộp chung thành File Tổng Quản `cron.php` chạy ngầm bằng URL bảo mật Token. Tự động chia Log thành các file theo ngày (`app-YYYY-MM-DD.log`). Mỗi đêm sẽ tự động sao lưu Database, đồng thời dọn dẹp các file `.sql` và các file `app-*.log` cũ hơn N ngày để giữ server luôn nhẹ nhàng. Cấu hình được lưu qua file JSON riêng lẻ (`cron_settings.json`).

### 🟢 Chi tiết: TextTranslationController (Dịch chuỗi ngôn ngữ)
- **`index()`**: Hiển thị danh sách bản dịch có phân trang (50 bản ghi/trang), hỗ trợ tìm kiếm theo `keyword` (trên `key_name` và `text`), lọc theo `group_name`. Tự động load danh sách ngôn ngữ từ `config('lang')` để render cột theo từng ngôn ngữ. Liệt kê danh sách tất cả `group_name` hiện có để hiển thị dropdown lọc.
- **`updateAjax()`**: Nhận `id`, `lang`, `text` qua AJAX POST. Gọi `TextModel::updateTranslationAjax()` để giải mã JSON, cập nhật đúng ngôn ngữ trong cột `text`, rồi lưu lại. Trả về JSON `{success, message}`.
- **`updateKeyAjax()`**: Nhận `id`, `key_name` qua AJAX. Kiểm tra `key_name` không trống và không bị trùng với bản ghi khác. Cập nhật `key_name` vào DB. Trả về JSON.
- **`updateGroupAjax()`**: Cập nhật `group_name` cho một bản ghi theo `id`. Trả về JSON.
- **`updateBulkGroupAjax()`**: Nhận mảng `ids[]` và `group_name`. Sanitize tất cả ID bằng `intval()`. Cập nhật hàng loạt `group_name` cho nhiều bản ghi cùng lúc. Trả về JSON.
- **`renameGroupAjax()`**: Nhận `old_name`, `new_name`. Cập nhật tất cả các bản ghi đang có `group_name = old_name` sang `new_name`. Trả về JSON.
- **`deleteGroupAjax()`**: Nhận `group_name`. Chuyển tất cả bản ghi thuộc nhóm đó về `group_name = 'uncategorized'` (không xóa dữ liệu). Trả về JSON.
- **`store()`**: Thêm mới một cặp key/value dịch thuật. Validate `key_name` không trống và không trùng. Tự động tạo JSON `text` gồm tất cả ngôn ngữ đang active.
- **`destroy()`**: Xóa một bản ghi dịch thuật theo `id`.
- **`scan()`**: Quét toàn bộ file `.php` trong thư mục `app/` và `resources/views/`. Dùng Regex `/__\(\s*['"]([^'"]+)['"]\s*\)/` để tìm tất cả các lời gọi `__('keyword')`. So sánh với DB, tự động thêm các keyword chưa có vào bảng `db_text` với giá trị mặc định là chính key đó.
- **`scanDirectory()` (private)**: Hàm đệ quy (Recursive) để quét sâu vào tất cả các thư mục con.

### 🟢 Chi tiết: MenuController (Quản lý Menu Website - Frontend)
- **`index()`**: Hiển thị giao diện quản lý menu chính (kéo thả Nestable). Tải danh sách category, product, post và tổ chức thành cấu trúc cây đệ quy phục vụ kéo thả.
- **`ajaxCreate()`**: Tạo mới một menu name rỗng trong bảng `#_menus`.
- **`ajaxSave()`**: Lưu cấu trúc cây vào bảng `#_menu_items`, cập nhật vị trí `#_menu_locations`.
- **`ajaxDelete()`**: Xóa sạch menu, items và location liên quan.

### 🟢 Chi tiết: MenuAdminController (Quản lý Menu hệ thống Admin Sidebar)
- **`index()`**: Hiển thị cấu trúc cây menu đa cấp trực quan bằng thư viện kéo thả Nestable.
- **`store()` / `update()`**: Thêm mới hoặc chỉnh sửa thông tin của một node menu (tên, alias, route_name, icon, parent, permission_level, các thiết lập badge số lượng thông báo...).
- **`destroy()`**: Xóa một menu đồng thời tự động xóa các menu con trực thuộc nó.
- **`updateSortAjax()`**: Nhận JSON cấu trúc cây đã thay đổi từ giao diện Nestable kéo thả, cập nhật đồng loạt lại vị trí cha/con (`parent`) và thứ tự hiển thị (`sort_order`) thông qua AJAX.

### 🟢 Chi tiết: EmailController (Cấu hình Email / SMTP)
- **`index()`**: Hiển thị Giao diện đọc trực tiếp thông số hiện tại từ biến môi trường `.env`. Giao diện 2 khối (Máy chủ & Tài khoản), có nút tắt/bật mật khẩu và cảnh báo Gmail App Password.
- **`save()`**: Xử lý mảng `$request->post()`, quét file `.env` bằng Regex (`preg_replace`) để tìm và ghi đè trực tiếp các tham số `MAIL_*`. Nếu chưa có, tự động nối thêm vào dòng cuối cùng.
- **`testEmail()`**: (AJAX) Nhận thông số ngay từ Form (không cần lưu), trực tiếp gán vào thư viện PHPMailer cũ và bắn mail thử nghiệm. Trả về kết quả JSON để xuất thông báo Alert/SweetAlert2 cho Quản trị viên.
- **Hàm `send_email()` (Helper)**: Viết tiện ích đa năng ở `core.php` kết nối tự động tới cấu hình SMTP `.env`, có hỗ trợ truyền mảng email và file đính kèm, tái sử dụng trên toàn dự án.

### 🟢 Chi tiết: MaintenanceController (Chế độ bảo trì)
- **`index()` / `save()`**: Quản lý bật tắt chế độ bảo trì hệ thống. Cho phép tùy chỉnh giao diện thông báo (Tiêu đề, Nội dung qua Editor, Countdown hẹn giờ ETA, Logo, Màu nền).
- **Tính năng Bypass Nâng cao**: Xây dựng cơ chế vượt rào bảo trì bằng 2 cách độc lập:
  - (1) **IP Whitelist**: Cho phép admin chủ động cấp quyền truy cập theo địa chỉ IP (có nút thêm nhanh IP hiện tại).
  - (2) **Token Access (URL Bypass)**: Tự động sinh Link chia sẻ bảo mật chứa Token `/?bypass=TOKEN` phục vụ cho Khách / Tester. Hệ thống sẽ cấp Cookie Bypass có thời hạn tuỳ chỉnh.
- **Tính năng Xem trước (Preview)**: Tích hợp route `/admin/maintenance/preview` cho phép Admin xem thử trực tiếp giao diện bảo trì hiện tại mà không cần phải kích hoạt bảo trì hệ thống. Khắc phục triệt để lỗi xung đột Layout tĩnh bằng cách bypass `layouts/main`.
### 🟢 Chi tiết: SettingController (Cấu hình Website)
- **`index()`**: Lấy toàn bộ dữ liệu cấu hình chung (Company Name, Logo, Favicon...) mà không phân biệt ngôn ngữ. Giao diện được thiết kế tích hợp Component Upload ảnh trực tiếp. Cấu trúc Schema JSON được xây dựng sẵn để lưu trữ linh hoạt mọi tham số mở rộng.
- **`update()`**: Xử lý mảng đa chiều khi submit. Cập nhật cấu hình chung, logo, favicon, và thông tin dịch thuật (data_payload) độc lập cho từng ngôn ngữ `lang`. Hỗ trợ lưu trữ không giới hạn các trường Setting.

### 🟢 Chi tiết: ApiIntegrationController (Tích hợp API / Scripts)
- **`index()`**: Giao diện hiển thị 3 khu vực nhập mã: `<head>`, `<body>`, `<footer>`. Được trang bị Component IDE **CodeMirror 5** với giao diện Dracula cực đẹp, tự động highlight cú pháp HTML/CSS/JS, số dòng, và đóng mở ngoặc thông minh. Tối ưu load CDN linh hoạt với cơ chế biến cờ toàn cục `$GLOBALS`.
- **`save()`**: Trích xuất dữ liệu, lưu thẳng (Raw Text) vào bảng `#_options` sử dụng `OptionModel` thông qua 3 khóa (`api_head_scripts`, `api_body_scripts`, `api_footer_scripts`). Vô hiệu hóa tính năng Escape HTML để bảo toàn 100% nguyên bản mã của Script.
- **Tối ưu Hiệu năng**: Nhờ cơ chế `Autoload Cache` (tải trước bằng 1 query duy nhất trên hệ thống OptionModel), toàn bộ Script đều được ghim trực tiếp vào RAM, giúp Frontend xuất mã cực nhanh mà không gây trễ băng thông cơ sở dữ liệu.

---

## 3. 📰 QUẢN LÝ BÀI VIẾT (Content)

| ID | Chức năng (Alias DB) | Controller MVC | Trạng thái |
|---|---|---|---|
| 23 | **Bài viết** (`bai-viet`) | `PostController` | 🟢 Hoàn thành |
| 24 | **Khối Giao Diện (Blocks)** (`noi-dung`) | `BlockController` | 🟢 Hoàn thành |
| 36 | **Album ảnh** (`gallery`) | `GalleryController` | 🔴 Chưa làm |
| 37 | **Videos** (`video`) | `VideoController` | 🔴 Chưa làm |
| 38 | **Upload file** (`upload-file`) | `FileManagerController` | 🔴 Chưa làm |

### 🟢 Chi tiết: PostController (Quản lý Bài viết)
- **`index()`**: Liệt kê danh sách bài viết theo ngôn ngữ, phân trang, cho phép tìm kiếm theo từ khóa, lọc theo trạng thái và danh mục. Hỗ trợ phân quyền sở hữu bài viết (`ownedByUser`).
- **`create()` / `edit()`**: Form thêm/sửa có đầy đủ Tab ngôn ngữ và tab cấu hình chung.
- **`store()` / `update()`**: Lưu trữ bài viết thông qua `PostService`.
- **`destroy()` / `destroyMultiple()`**: Xóa đơn lẻ hoặc xóa hàng loạt các bài viết.
- **`updateStatusAjax()`**: Toggle trạng thái hiển thị nhanh qua Ajax.

---

## 4. 📦 QUẢN LÝ SẢN PHẨM (Catalog)

| ID | Chức năng (Alias DB) | Controller MVC | Trạng thái |
|---|---|---|---|
| 22 | **Loại danh mục** (`category`) | `CategoryController` | 🟢 Hoàn thành |
| 49 | **Nhóm thuộc tính** (`attribute`) | `AttributeController` | 🟢 Hoàn thành |
| 30 | **Sản phẩm** (`san-pham`) | `ProductController` | 🟢 Hoàn thành |

### 🟢 Chi tiết: CategoryController (Loại danh mục)
- **`index()`**: Hiển thị toàn bộ danh mục ngôn ngữ `vi`, sắp xếp theo `so_thu_tu`. Tạo `parentMap` (mảng `id_code => ten`) để View dễ dàng hiển thị tên danh mục cha.
- **`create()`**: Hiển thị form thêm mới. Load danh sách ngôn ngữ từ config, load cây danh mục (`CategoryModel::getTree()`) và danh sách Module từ `db_module` để người dùng chọn loại danh mục (Sản phẩm / Bài viết / ...).
- **`store()`**: Lưu danh mục mới theo quy trình 2 bảng:
  - **Bước 1**: Insert vào bảng `cf_code` (bảng gốc chứa ID dùng chung), lấy `id_code` trả về.
  - **Bước 2**: Loop qua tất cả ngôn ngữ, insert một bản ghi dịch thuật vào bảng `db_loai` cho mỗi ngôn ngữ (tự động tạo `alias` từ `ten` nếu để trống).
- **`edit()`**: Load dữ liệu để sửa. Lấy thông tin gốc từ `cf_code`, lấy tất cả bản dịch (bỏ qua bộ lọc ngôn ngữ toàn cục bằng `use_lang = false`). Gộp dữ liệu thành mảng `$item` theo cấu trúc đa ngôn ngữ để form render đúng.
- **`update()`**: Cập nhật theo quy trình 2 bảng: Cập nhật `cf_code`, sau đó loop ngôn ngữ để cập nhật hoặc Insert mới nếu chưa có bản dịch cho ngôn ngữ đó (cơ chế Upsert thủ công).
- **`destroy()`**: Xóa bản ghi trong `cf_code` và tất cả bản dịch trong `db_loai` theo `id_code`.
- **Tính năng đặc biệt**: Xóa nhiều danh mục cùng lúc (`destroyMultiple`) qua route riêng.

### 🟢 Chi tiết: AttributeController (Nhóm thuộc tính)
- **`index()`**: Hiển thị danh sách thuộc tính ngôn ngữ `vi`. Với mỗi thuộc tính, đếm số lượng giá trị (`value_count`) và lấy xem trước 5 giá trị đầu tiên (`values_preview`) để hiển thị trong bảng.
- **`create()`**: Form thêm mới. Load danh sách ngôn ngữ, cung cấp 2 danh sách cấu hình:
  - `data_type_variation`: Loại hiển thị (`select`, `color`, `image`, `label`).
  - `data_type_sort`: Cách sắp xếp giá trị (`id`, `ten`).
- **`store()`**: Lưu theo quy trình 3 bước:
  - **Bước 1**: Insert vào `cf_code` (gốc), lấy `id_code`.
  - **Bước 2**: Insert bản dịch thuộc tính vào bảng `#_thuoctinh` cho mỗi ngôn ngữ.
  - **Bước 3**: Gọi `saveValues()` để xử lý các giá trị thuộc tính trong Repeater.
- **`edit()`**: Load dữ liệu thuộc tính và tất cả giá trị (bỏ `use_lang`). Nhóm giá trị theo `id_code` để render đúng cấu trúc Repeater đa ngôn ngữ.
- **`update()`**: Cập nhật theo quy trình 3 bước tương tự `store()`, nhưng truyền `isUpdate = true` vào `saveValues()`.
- **`destroy()`**: Xóa toàn bộ thuộc tính và tất cả giá trị liên quan (dùng raw PDO query cho `whereIn` để đảm bảo an toàn).
- **`saveValues()` (private - hàm dùng chung)**: Xử lý toàn bộ logic Repeater giá trị thuộc tính:
  - Loop qua từng dòng được submit.
  - Nếu `id_code > 0`: Cập nhật giá trị cũ (Upsert bản dịch cho từng ngôn ngữ).
  - Nếu `id_code = 0`: Thêm mới giá trị (insert vào `cf_code` rồi insert bản dịch).
  - Nếu `isUpdate = true`: So sánh danh sách giá trị cũ và mới, tự động xóa những giá trị đã bị người dùng xóa khỏi form Repeater.

### 🟢 Chi tiết: ProductController (Quản lý Sản phẩm)
- **`index()`**: Hiển thị danh sách sản phẩm với bộ lọc từ khóa, trạng thái, danh mục, và lọc tồn kho (tất cả, còn hàng, sắp hết hàng, hết hàng) kèm theo phân trang.
- **`create()` / `edit()`**: Form thêm/sửa tích hợp các Tab ngôn ngữ, cây danh mục, và danh sách các thuộc tính biến thể (Attributes) lấy từ DB để cấu hình biến thể.
- **`store()` / `update()`**: Gọi qua `ProductService` xử lý lưu trữ dữ liệu sản phẩm, thông tin Flash Sale (giá flash sale, thời gian bắt đầu/kết thúc), mức cảnh báo tồn kho thấp (`low_stock_amount`), và tự động cập nhật trạng thái kho hàng (`stock_status`).
- **`destroy()` / `destroyMultiple()`**: Xóa một hoặc nhiều sản phẩm kèm theo đồng bộ dọn dẹp các biến thể liên quan.
- **`updateStatusAjax()`**: Hỗ trợ toggle nhanh trạng thái (`status`, `is_featured`, `is_new`, `is_hot`, `is_sale`) qua AJAX.

---

## 5. 🛒 THƯƠNG MẠI ĐIỆN TỬ (E-Commerce)

| ID | Chức năng (Alias DB) | Controller MVC | Trạng thái |
|---|---|---|---|
| 106 | **Cổng thanh toán** (`payment`) | `PaymentController` | 🟢 Hoàn thành |
| 32 | **Quản lý đơn hàng** (`quan-ly-don-hang`) | `OrderController` | 🟢 Hoàn thành |
| 45 | **Thống kê doanh thu** (`doanh-thu`) | `RevenueController` | 🟢 Hoàn thành |
| 55 | **Cấu hình vận chuyển** (`quan-ly-van-chuyen`) | `ShippingController` | 🟢 Hoàn thành |
| 56 | **Quản lý thuế** (`quan-ly-thue`) | `TaxClassController`, `TaxRateController` | 🟢 Hoàn thành |
| 46 | **Mã giảm giá** (`promo-code`) | `PromoCodeController` | 🟢 Hoàn thành |
| 47 | **Flash Sale** (`flash-sale`) | `FlashSaleController` | 🔴 Chưa làm |
| 51 | **Đăng ký nhận ưu đãi** (`coupon`) | `CouponController` | 🔴 Chưa làm |

### 🟢 Chi tiết: PaymentController (Cổng thanh toán)
- **Kiến trúc giao diện:** Form thiết kế tối ưu trải nghiệm chia thành 2 phần rõ rệt: (1) Cấu hình nội dung đa ngôn ngữ (Tên, Mô tả ngắn, Hướng dẫn thanh toán) dùng tab chuyển đổi linh hoạt. (2) Cấu hình hệ thống dùng chung (Logo, Tỷ lệ phí giao dịch, Cấu hình kết nối API).
- **Quản lý API Keys động:** Cho phép Admin tự do tạo thêm hoặc xóa bớt các cặp Key - Value cấu hình kết nối API (ví dụ: `TMN_CODE`, `SECRET_KEY`, `ENDPOINT`) theo dạng Repeater field. Dữ liệu được mã hóa thành chuỗi JSON và lưu vào một trường duy nhất, giúp linh hoạt tích hợp bất kỳ cổng thanh toán nào mà không cần sửa cấu trúc Database.
- **`store()` / `update()`:** Xử lý lưu trữ theo mô hình đa ngôn ngữ 2 cấp chuẩn của dự án: Lưu thông tin cấu hình chung và sinh `id_code`, sau đó duyệt qua danh sách ngôn ngữ để lưu các bản dịch độc lập.
- Tích hợp tính năng bật/tắt trạng thái (`is_active`) nhanh qua AJAX trên danh sách.

### 🟢 Chi tiết: ShippingController (Cấu hình vận chuyển)
- **Kiến trúc dữ liệu:** Tách biệt hoàn toàn Phương thức/Đơn vị vận chuyển (Shipping Method) và Bảng giá theo vùng (Shipping Rates). Một phương thức (VD: Giao hàng tiêu chuẩn) có thể có nhiều bảng giá áp dụng cho các khu vực khác nhau.
- **`rates()` / `createRate()`:** Quản lý không giới hạn số lượng biểu phí cho mỗi phương thức. Form thiết lập bảng giá hỗ trợ phân vùng chi tiết từ cấp Quốc gia -> Tỉnh/Thành phố -> Quận/Huyện -> Phường/Xã với các Dropdown tự động load qua AJAX động theo cấp bậc.
- **Cấu hình linh hoạt:** Cho phép thiết lập Phí cơ bản, Phụ thu quá ký (`extra_fee_per_kg`), Số kg miễn phí (`free_weight_kg`), Thời gian giao hàng dự kiến (`estimated_time`).
- **Độ ưu tiên (Priority):** Cho phép gắn chỉ số ưu tiên (`priority`). Khi khách hàng thanh toán, hệ thống sẽ đối chiếu địa chỉ giao hàng với các bảng giá theo mức độ phủ sóng (Xã -> Huyện -> Tỉnh -> Quốc gia) và ưu tiên chọn biểu phí có mức độ khớp sâu nhất hoặc `priority` cao nhất để tính ra mức cước chính xác.

### 🟢 Chi tiết: TaxClassController & TaxRateController (Quản lý Thuế)
- **Kiến trúc dữ liệu:** Tách biệt Nhóm Thuế (Tax Class - ví dụ: Thuế giá trị gia tăng, Thuế tiêu thụ đặc biệt) và Biểu phí Thuế (Tax Rate - phần trăm thuế theo từng khu vực địa lý cụ thể).
- **Quản lý Biểu phí Thuế:** Cho phép định nghĩa phần trăm thuế suất áp dụng cụ thể cho từng Quốc gia, Tỉnh/Thành, Quận/Huyện, Phường/Xã. 
- **Tính năng Thuế nâng cao:** Hỗ trợ tính thuế lồng nhau (`is_compound`) đối với các loại thuế chồng lên nhau, và cấu hình độ ưu tiên (`priority`) để xác định thứ tự tính thuế trong trường hợp một sản phẩm áp dụng nhiều loại biểu phí thuế cùng một khu vực.
- Giao diện thiết lập địa lý thông minh tự động load thông qua Component `Location Selector` chuẩn hệ thống.

### 🟢 Chi tiết: PromoCodeController (Mã giảm giá)
- **Kiến trúc dữ liệu:** Xây dựng lại hoàn toàn cấu trúc Database (`db_promo_codes`, `db_promo_code_usage`) để đáp ứng chuẩn E-commerce hiện đại.
- **Tính năng nâng cao:** Hỗ trợ đa dạng loại giảm giá (%, tiền mặt, freeship), giới hạn giảm tối đa, yêu cầu đơn tối thiểu, hẹn giờ có hiệu lực chuẩn xác đến phút.
- **Bảo mật và kiểm soát:** Tích hợp logic giới hạn tổng lượt dùng hệ thống và giới hạn lượt dùng trên mỗi User. Ngăn chặn xóa các mã đã phát sinh lịch sử sử dụng để bảo toàn dữ liệu đối soát đơn hàng.
- **Phát triển tương lai (Checkout/Cart Integration):** Sẽ được gọi trong quá trình xử lý thanh toán (Kiểm tra `is_active`, thời hạn, điều kiện min order, và tính toán số tiền giảm cuối cùng, đồng thời ghi log vào `db_promo_code_usage`).
- **Phát triển tương lai (Apply To):** Mở rộng áp dụng mã giảm giá cho Từng Sản phẩm cụ thể hoặc Danh mục cụ thể (Hiện tại đang áp dụng cho Tổng đơn hàng).

---

## 6. 👥 KHÁCH HÀNG & TƯƠNG TÁC (Customers & CRM)

| ID | Chức năng (Alias DB) | Controller MVC | Trạng thái |
|---|---|---|---|
| 41 | **Thành viên** (`thanh-vien`) | `CustomerController` | 🟢 Hoàn thành |
| 42 | **Cộng tác viên** (`cong-tac-vien`) | `AffiliateController` | 🔴 Chưa làm |
| 33 | **Khách hàng liên hệ** (`lien-he`) | `FormBuilderController` | 🟢 Hoàn thành |
| 48 | **Quản lý bình luận** (`binh-luan`) | `CommentController` | 🔴 Chưa làm |
| 50 | **Đăng ký nhận tin** (`newsletter`) | `NewsletterController` | 🔴 Chưa làm |

---

### 🟢 Chi tiết: RevenueController (Thống kê doanh thu)
- **`index()`**: Hiển thị Dashboard toàn diện với các chỉ số báo cáo nâng cao (Doanh thu, Lợi nhuận ước tính, Đơn thành công, Số lượng SP bán ra, Thiệt hại đơn hủy). Tính toán và so sánh tự động Tỷ lệ phần trăm tăng/giảm so với kỳ trước.
- **Biểu đồ (Charts):** Tích hợp 3 biểu đồ Chart.js (Line chart Doanh thu, Doughnut Trạng thái đơn, Doughnut Phương thức thanh toán).
- **Bộ lọc (Filters):** Lấy tham số `date_range`, hỗ trợ Quick Filters (Hôm nay, Tuần này, Tháng này).
- **Danh sách:** Top 10 sản phẩm bán chạy nhất, Top 10 khách hàng VIP chi tiêu nhiều nhất.
- **Export:** Cung cấp tính năng xuất báo cáo `.csv` với định dạng UTF-8 BOM chuẩn để đọc trên Excel.

### 🟢 Chi tiết: FormBuilderController (Dynamic Form Builder / Quản lý liên hệ)
- **Kiến trúc dữ liệu:** Xóa bỏ bảng `db_lienhe` cứng nhắc cũ, thay bằng mô hình 3 bảng `db_forms`, `db_form_fields`, `db_form_submissions` linh hoạt, lưu trữ dữ liệu dưới dạng JSON siêu nhẹ.
- **Trình tạo Form (Builder):** Giao diện kéo thả (Drag & Drop) tích hợp jQuery UI Sortable. Cho phép Admin tự do thiết kế các Form (Khảo sát, Liên hệ, Báo giá) với nhiều loại trường dữ liệu (Text, Email, Phone, Textarea, Select, Radio, Checkbox, File).
- **Thuộc tính Nâng cao (Advanced Settings):** Tích hợp 10 thuộc tính nâng cao cho mỗi Field (Help text, Value mặc định, CSS Class, Icon, Min/Max length, Allowed extensions...).
- **Logic Hiển thị (Conditional Logic):** Tích hợp Javascript Engine cho phép cấu hình Ẩn/Hiện một trường dữ liệu bất kỳ dựa trên giá trị (Value) người dùng nhập vào một trường khác (Ví dụ: Nếu chọn "Khác" thì hiện thêm ô nhập lý do).
- **Cấu hình Email Nâng cao (Autoresponder):** Chức năng cấu hình nội dung Email tự động sử dụng biến linh động (ví dụ `{ho_ten}`, `{email}`). Hỗ trợ 2 luồng riêng biệt: Gửi thông báo cho Admin & Gửi thư cảm ơn tự động cho Khách hàng. Hỗ trợ soạn thảo bằng thẻ HTML.
- **Quản lý Inbox (Submissions):** Hộp thư đến tự động phân tích JSON tạo dòng preview ngắn gọn. Thay đổi trạng thái (Mới, Đã đọc, Đã phản hồi), Ghi chú nội bộ, và có huy hiệu đếm thư chưa đọc trên Sidebar.
- **Tích hợp Frontend (🟢 Hoàn thành):** Đã hoàn thiện toàn bộ tính năng Endpoint nhận submit an toàn, kiểm tra giới hạn file (đuôi file + dung lượng), lưu trữ JSON thông minh. Tích hợp bẫy Honeypot ẩn (chống Spam cơ bản) và Hệ thống Captcha theo kiến trúc Strategy Pattern (Hỗ trợ Google reCAPTCHA v3 & Cloudflare Turnstile vô hình). Tính năng trích xuất dữ liệu Excel CSV thông minh.

> **⚠️ Lưu ý Kỹ thuật (Scalability bottlenecks):**
> 1. *Lưu trữ File:* Đang lưu cục bộ tại `public/uploads/`. Nếu scale (nhiều user upload file lớn), ổ cứng sẽ đầy nhanh và giảm băng thông Server. Cần tính toán dời qua Amazon S3/Cloudflare R2.
> 2. *Truy vấn Dữ liệu:* Submission được lưu dưới dạng JSON. Nếu đạt hàng triệu bản ghi và cần query/search theo 1 field cụ thể bên trong JSON, hiệu năng sẽ bị giảm sút trầm trọng do MySQL không đánh index tự nhiên được. Cần dùng Virtual Columns hoặc Elasticsearch.
> 3. *Đồng bộ Email:* Gửi mail thông báo hiện đang chạy đồng bộ (Synchronous). Khách phải chờ SMTP Server phản hồi (vài giây) mới hoàn thành gửi Form. Quá nhiều user cùng lúc sẽ sập PHP Worker. Cần chuyển sang cơ chế Background Queue (RabbitMQ/Redis).

> **📊 Tổng kết tiến độ:** Hoàn thành **18/33 module** (54%). Hệ thống phân quyền (RBAC), Quản lý User, Cấu hình chung, Dynamic Form Builder, Quản lý Bài viết, Sản phẩm, Thanh toán, Vận chuyển, Mã giảm giá, Đơn hàng, Thống kê Doanh thu đã hoàn thiện.
