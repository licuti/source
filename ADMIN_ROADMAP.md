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
| 27 | **Quản lý user** (`ql-user`) | `AdminUserController` | 🔴 Chưa làm |

### 🟢 Chi tiết: AuthController (Đăng nhập & Xác thực)
- **`login()`**: Hiển thị trang đăng nhập Admin.
- **`loginPost()`**: Xác thực thông tin đăng nhập, so sánh mật khẩu đã hash, tạo Session `admin_logged_in`.
- **`logout()`**: Hủy toàn bộ Session, redirect về trang đăng nhập.
- **`BaseAdminController`**: Lớp cha tự động kiểm tra Session trong constructor. Nếu chưa đăng nhập, tự động redirect về `/admin/login`. Tất cả Controller Admin đều phải kế thừa lớp này.

---

## 2. 🎛️ CẤU HÌNH HỆ THỐNG (Settings)

| ID | Chức năng (Alias DB) | Controller MVC | Trạng thái |
|---|---|---|---|
| 103 | **Cấu hình Ngôn ngữ** (`language`) | `LanguageSettingController` | 🟢 Hoàn thành |
| 26 | **Dịch chuỗi ngôn ngữ** (`text`) | `TextTranslationController` | 🟢 Hoàn thành |
| 101 | **Cấu hình Email / SMTP** (`email-smtp`) | `EmailController` | 🟡 Đang PT |
| 102 | **Tích hợp API / Scripts** (`api-integration`) | `ApiIntegrationController` | 🟡 Đang PT |
| 104 | **Sao lưu & Cache** (`backup-cache`) | `BackupController` | 🟡 Đang PT |
| 105 | **Chế độ bảo trì** (`maintenance`) | `MaintenanceController` | 🟡 Đang PT |
| 106 trong group 43 | **Cổng thanh toán** (`payment`) | `PaymentSettingController` | 🟡 Đang PT |
| 25 | **Thông tin website - liên hệ** (`thongtin-lienhe`) | `CompanyInfoController` | 🔴 Chưa làm |
| 28 | **Cấu hình SEO cơ bản** (`seo-co-ban`) | `SeoConfigController` | 🔴 Chưa làm |
| 39 | **Sitemap** (`sitemap`) | `SitemapController` | 🔴 Chưa làm |
| 53 | **Button Contact** (`button-contact`) | `ContactButtonController` | 🔴 Chưa làm |
| 54 | **Menu** (`menu`) | `MenuController` | 🔴 Chưa làm |

### 🟢 Chi tiết: LanguageSettingController (Cấu hình Ngôn ngữ)
- **`index()`**: Hiển thị danh sách ngôn ngữ, sắp xếp theo `sort_order`.
- **`create()`**: Hiển thị form thêm ngôn ngữ mới.
- **`store()`**: Lưu ngôn ngữ mới vào bảng `db_lang`. Validate `code` không được trống và không được trùng. Nếu đánh dấu là `is_default` thì ép `is_active = 1` và tự động tắt `is_default` của tất cả ngôn ngữ khác. Sau khi lưu, gọi `generateConfigFile()`.
- **`edit()`**: Hiển thị form chỉnh sửa, load dữ liệu ngôn ngữ theo ID.
- **`update()`**: Cập nhật ngôn ngữ theo ID. Validate trùng `code` với bản ghi khác. Logic xử lý `is_default` tương tự `store()`. Sau khi lưu, gọi `generateConfigFile()`.
- **`destroy()`**: Xóa ngôn ngữ theo ID. **Ngăn chặn xóa ngôn ngữ mặc định** (`is_default = 1`). Sau khi xóa, gọi `generateConfigFile()`.
- **`generateConfigFile()` (private)**: Lấy toàn bộ ngôn ngữ đang `is_active = 1` từ DB, tự động ghi đè lên file `config/languages.php`. File này được ứng dụng đọc để biết danh sách ngôn ngữ hỗ trợ mà không cần query DB.

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

---

## 3. 📦 QUẢN LÝ SẢN PHẨM (Catalog)

| ID | Chức năng (Alias DB) | Controller MVC | Trạng thái |
|---|---|---|---|
| 22 | **Loại danh mục** (`category`) | `CategoryController` | 🟢 Hoàn thành |
| 49 | **Nhóm thuộc tính** (`attribute`) | `AttributeController` | 🟢 Hoàn thành |
| 30 | **Sản phẩm** (`san-pham`) | `ProductController` | 🔴 Chưa làm |

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

---

## 4. 🛒 THƯƠNG MẠI ĐIỆN TỬ (E-Commerce)

| ID | Chức năng (Alias DB) | Controller MVC | Trạng thái |
|---|---|---|---|
| 106 | **Cổng thanh toán** (`payment`) | `PaymentSettingController` | 🟡 Đang PT |
| 32 | **Quản lý đơn hàng** (`quan-ly-don-hang`) | `OrderController` | 🔴 Chưa làm |
| 45 | **Thống kê doanh thu** (`doanh-thu`) | `RevenueController` | 🔴 Chưa làm |
| 55 | **Cấu hình vận chuyển** (`quan-ly-van-chuyen`) | `ShippingController` | 🔴 Chưa làm |
| 56 | **Quản lý thuế** (`quan-ly-thue`) | `TaxController` | 🔴 Chưa làm |
| 46 | **Mã khuyến mãi** (`ma-khuyen-mai`) | `PromoCodeController` | 🔴 Chưa làm |
| 47 | **Flash Sale** (`flash-sale`) | `FlashSaleController` | 🔴 Chưa làm |
| 51 | **Đăng nhận nhận ưu đãi** (`coupon`) | `CouponController` | 🔴 Chưa làm |

---

## 5. 📰 QUẢN LÝ BÀI VIẾT (Content)

| ID | Chức năng (Alias DB) | Controller MVC | Trạng thái |
|---|---|---|---|
| 23 | **Bài viết** (`bai-viet`) | `PostController` | 🔴 Chưa làm |
| 24 | **Nội dung** (`noi-dung`) | `PageController` | 🔴 Chưa làm |
| 36 | **Album ảnh** (`gallery`) | `GalleryController` | 🔴 Chưa làm |
| 37 | **Videos** (`video`) | `VideoController` | 🔴 Chưa làm |
| 38 | **Upload file** (`upload-file`) | `FileManagerController` | 🔴 Chưa làm |

---

## 6. 👥 KHÁCH HÀNG & TƯƠNG TÁC (Customers & CRM)

| ID | Chức năng (Alias DB) | Controller MVC | Trạng thái |
|---|---|---|---|
| 41 | **Thành viên** (`thanh-vien`) | `CustomerController` | 🔴 Chưa làm |
| 42 | **Công tác viên** (`cong-tac-vien`) | `AffiliateController` | 🔴 Chưa làm |
| 33 | **Khách hàng liên hệ** (`lien-he`) | `ContactController` | 🔴 Chưa làm |
| 48 | **Quản lý bình luận** (`binh-luan`) | `CommentController` | 🔴 Chưa làm |
| 50 | **Đăng ký nhận tin** (`newsletter`) | `NewsletterController` | 🔴 Chưa làm |

---

> **📊 Tổng kết tiến độ:** Hoàn thành **5/31 module** (16%). Ưu tiên tiếp theo: Module Sản phẩm và Đơn hàng là cốt lõi của hệ thống E-commerce.
