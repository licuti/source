# ARCHITECTURE.md — Tài Liệu Kiến Trúc Hệ Thống CMS (Bản Đầy Đủ & Chính Xác Nhất)

> **DÀNH CHO LẬP TRÌNH VIÊN & AI AGENT:** Tài liệu này mô tả chi tiết toàn bộ kiến trúc hạ tầng (Core), các quy ước ứng dụng (Application), giao diện (Views), cơ sở dữ liệu (Database Schema), và quy tắc bảo mật của hệ thống CMS sau khi được nâng cấp lên mô hình OOP / Modern MVC. Hãy đọc kỹ trước khi viết code.

---

## 0. Thông Tin Hệ Thống (System Overview)

| Thông số | Chi tiết |
|---|---|
| **Ngôn ngữ lập trình** | PHP 8.2+ (Typed properties, Reflection, Union Types). |
| **Cơ sở dữ liệu** | MySQL 5.7+. Table Prefix: `db_` (Dùng `#_` trong code). |
| **Kiến trúc** | Custom MVC (Inspired by Laravel Architecture). |
| **Đa ngôn ngữ** | Hỗ trợ mặc định: `vi` (Tiếng Việt), `en` (Tiếng Anh). |
| **Quy ước URL** | SEO Friendly: `URLPATH/lang/slug.html`. |
| **Quản lý Layout** | Layout động (Theme-ready) ở mức Controller qua `getLayout()`. |
| **Thư mục Assets** | `/assets/` (CSS, JS, Fonts, Images tĩnh). |
| **Thư mục Data** | `/img_data/` (Ảnh dữ liệu người dùng tải lên). |
| **Môi trường chạy** | Laragon / XAMPP (Localhost). |

---

## 1. Bản Đồ Phân Phối Trách Nhiệm (Core vs App)

Hệ thống được chia làm hai khu vực rạch ròi:
1. **Phần Lõi Framework (`app/Core/`):** Không chứa bất kỳ logic nghiệp vụ nào (Không giỏ hàng, không sản phẩm). Đảm nhận các tác vụ hạ tầng: DI Container, Routing, Base ORM, Request/Response, Exception, Logging, Auth, Mail.
2. **Phần Ứng Dụng (`app/Controllers/`, `app/Models/`, `routes/`, `resources/views/`):** Chứa toàn bộ nghiệp vụ thực tế của CMS (Tin tức, sản phẩm, giỏ hàng, giao diện).

---

## 2. Tổng Quan Tầng Lõi Framework (`app/Core/`)

| File / Thư mục | Chức năng chi tiết | Các hàm/thuộc tính quan trọng |
|---|---|---|
| `App.php` | **Kernel hệ thống**. Khởi tạo toàn bộ ứng dụng. | `boot()`: Khởi động DB, nạp Middleware, chạy Router; `run()`: Thực thi. |
| `Router.php` | **Bộ điều hướng**. Ánh xạ URL tới Controller. | `dispatch()`: Xử lý định tuyến; `execute()`: Thực thi callback qua `Container::call()`. |
| `Container.php` | **Dependency Injection Container**. | `make()`: Khởi tạo dependency; `singleton()`: Đăng ký singleton; `call()`: Gọi method và tự động tiêm tham số (Method Injection). |
| `FormRequest.php` | **Base Request tự động Validate**. | `validateResolved()`: Chạy kiểm tra dữ liệu đầu vào tự động khi Container resolve request cụ thể. |
| `Model.php` | **Base ORM**. Xử lý truy vấn Database. | `query()`, `with()`, `where()`, `get()`, `first()`. Hỗ trợ Global Scopes (`withoutGlobalScope`). |
| `View.php` | **Bộ dựng giao diện (Renderer)**. | `render()`: Dựng view; `setLayout()`: Gán layout. |
| `Request.php` | **Quản lý Yêu cầu**. Bao bọc các biến toàn cục. | `all()`, `input()`, `expectsJson()`, `isAjax()`, `file()`. |
| `Response.php` | **Quản lý Phản hồi**. Thiết lập Header và HTTP Status. | `json()`, `download()`, `stream()`, `redirect()`. |
| `ExceptionHandler.php` | **Bộ xử lý Ngoại lệ trung tâm**. | `handle()`: Catch lỗi toàn cục. Xử lý lỗi 404 (301 Redirect), ValidationException (JSON 422 hoặc Redirect Back), và TokenMismatchException (Lỗi 419). |
| `Logger.php` | **Ghi log chuyên sâu (PSR-like)**. | Hỗ trợ log levels và cơ chế **Log Rotation** tự động dọn dẹp log cũ hơn 30 ngày. |
| `Config/Repository.php`| **Quản lý Cấu hình**. | Hỗ trợ truy cập mảng cấu hình bằng Dot Notation (`config('mail.host')`). |
| `Auth/AuthManager.php` | **Quản lý Xác thực & Phân quyền**. | `user()`: Lấy thông tin user đăng nhập; `can($permission)`: Kiểm tra quyền phân cấp hệ thống. |
| `Auth/Auth.php` | **Lớp truy xuất thông tin xác thực**. | `check()`, `id()`, `isSuperAdmin()`, `roleId()`, `permissionsCache()`. Độc lập với session storage. |
| `Auth/Gate.php` | **Trung tâm kiểm tra phân quyền RBAC**. | `check($modulePrefix, $action)`: Kiểm tra quyền duy nhất cho cả Middleware và View. |
| `Mail/Mailer.php` | **Gửi email thông qua PHPMailer**. | `send($to, $subject, $body)`: Tự động dùng cấu hình từ `config/` để gửi email. |

---

## 3. Các Lớp Lọc Request (app/Middleware)

Middleware can thiệp vào vòng đời Request trước khi nó chạm tới Controller.

| Middleware | Chức năng |
|---|---|
| `StartSession.php` | Khởi tạo Session PHP một cách an toàn và đúng thời điểm. |
| `CsrfMiddleware.php` | Chặn các request POST, PUT, DELETE để kiểm tra token. Chống tấn công giả mạo (CSRF). |
| `LanguageMiddleware.php`| Phân tích URL (vd: `/vi/`, `/en/`) để thiết lập ngôn ngữ hiển thị và các biến hằng liên quan. |
| `SitePasswordMiddleware.php`| Chặn truy cập toàn trang bằng mật khẩu bảo vệ khi cấu hình `protection => true`. |
| `AdminAuthMiddleware.php`| Bảo vệ khu vực Admin, thực hiện xác thực và phân quyền RBAC (Role-Based Access Control) cho từng module. |

---

## 4. Danh Sách Controllers & Models của CMS

### 4.1. Controllers (`app/Controllers/`)
Kiến trúc Layout được quy định theo Base Controller:
- **`FrontendController`**: Base cho mảng Frontend (Mặc định bọc giao diện bằng layout: `layouts.main`).
- **`Admin\BaseAdminController`**: Base cho mảng Admin (Mặc định bọc giao diện bằng layout: `admin.layouts.main`).

**Các Controller Nghiệp Vụ:**
- `HomeController`: Xử lý trang chủ (Slide, Sản phẩm theo tab, Tin tức).
- `ProductController`: Hiển thị danh sách và chi tiết sản phẩm.
- `CategoryController`: Hiển thị sản phẩm hoặc tin tức theo danh mục.
- `NewsController`: Quản lý hiển thị tin tức / bài viết.
- `PageController`: Xử lý các trang thông tin tĩnh qua Catch-all route `/{slug}`.
- `CartController`: Xử lý giỏ hàng (thêm/sửa/xóa, coupon).
- `CheckoutController`: Xử lý quy trình đặt hàng và gửi email xác nhận.
- `LocationController`: AJAX phục vụ lấy Tỉnh -> Huyện -> Xã.
- `ContactController`: Xử lý form liên hệ.
- `ReviewController`: Đánh giá sao, bình luận sản phẩm.
- `SearchController`: Tìm kiếm Full-text đa bảng.
- `AuthController`: Đăng ký, đăng nhập, quên mật khẩu.

### 4.2. Models (`app/Models/`)
Hệ thống quản lý dữ liệu thông qua hơn 48 Model thuần OOP kế thừa từ `App\Core\Database\Model`.
- **Nhóm Sản phẩm**: `ProductModel`, `ProductVariantModel`, `ProductVariantAttributeModel`, `ProductAlbumModel`, `AttributeModel`, `AttributeValueModel`.
- **Nhóm Nội dung & Tương tác**: `CategoryModel`, `PostModel`, `PageModel`, `BinhLuanModel`, `GalleryModel`.
- **Nhóm Marketplace & Bán hàng**: `ShopModel`, `OrderModel`, `OrderItemModel`, `OrderHistoryModel`, `CouponModel`, `PaymentMethodModel`, `ShippingMethodModel`, `ShippingRateModel`.
- **Nhóm Cấu hình & Hệ thống**: `SettingModel`, `TextModel` (Dịch thuật), `LanguageModel`, `MenuModel`, `MenuItemModel`, `MenuLocationModel`, `ModuleModel`, `ModuleAdminModel`, `RoleModel`, `RolePermissionModel`, `UserModel`, `RedirectModel`.

---

## 5. Thư Viện Hàm Trợ Giúp (Helpers)

Dự án chia nhỏ các hàm trợ giúp để dễ quản lý:

### 5.1. `app/Helpers/core.php` (Lõi Framework)
Chỉ chứa các hàm nền tảng, không chứa nghiệp vụ:
- `config($key)`: Lấy cấu hình bằng dot notation.
- `request()`, `response()`: Truy xuất nhanh Request/Response.
- `view($template, $data)`: Render giao diện.
- `session($key)`: Đọc/ghi flash session.
- `old($key)`, `errors($key)`: Lấy old input và lỗi validation.
- `csrf_token()`, `csrf_field()`: Sinh token bảo vệ form.
- `hasPermission($module, $action)`: Kiểm tra nhanh quyền truy cập trong View qua `Gate::check()`.
- `__($key)`: Dịch nhanh chuỗi tĩnh thông qua `TextModel::translate()`.
- `dd($data)`: Dump và die (Debug nhanh).

### 5.2. `app/Helpers/url.php` (Đường Dẫn & Link)
- `url($path)`: Sinh URL tuyệt đối.
- `asset($path)`: Đường dẫn trỏ tới assets tĩnh.
- `admin_url($path)`: Đường dẫn vào trang quản trị Admin.
- `getImageUrl($filename)`, `Img($img)`: Trả về link ảnh chuẩn từ `img_data/`.
- `route($name, $params)`: Sinh URL từ tên Route (Tự động dịch slug theo ngôn ngữ và chèn locale prefix `/en/`).
- `url_lang($langCode)`: Tạo URL chuyển đổi ngôn ngữ cho trang hiện hành.

### 5.3. `app/Helpers/ui.php` (Hiển Thị Giao Diện)
- Chứa các hàm hiển thị giá tiền (`renderPrice`), sao đánh giá, và sinh alias slug (`createAlias`).

### 5.4. `app/Helpers/string.php` (Xử Lý Chuỗi)
- `str_slug($str)`: Chuyển đổi chuỗi thành slug không dấu.
- `str_random($length)`: Sinh chuỗi ngẫu nhiên.
- `limit_text($text, $limit)`: Cắt ngắn đoạn văn bản.

---

## 6. Quy Tắc Bảo Mật & Xác Thực Dữ Liệu

### 6.1. Bảo Mật Form (CSRF Protection)
- Mọi Form POST trên giao diện đều **bắt buộc** phải chèn thẻ token bảo vệ:
  ```html
  <form action="<?= route('contact.store') ?>" method="POST">
      <?= csrf_field() ?>
      ...
  </form>
  ```
- Nếu thiếu hoặc sai token, hệ thống sẽ chặn đứng và trả về lỗi `419` (hoặc JSON `419` với AJAX).

### 6.2. Xác Thực FormRequest tự động & Bảo Vệ Mass Assignment
Thay vì viết if/else kiểm tra dữ liệu trong Controller, hãy kế thừa `App\Core\FormRequest`:
```php
namespace App\Requests;

use App\Core\FormRequest;

class StoreContactRequest extends FormRequest {
    public function rules(): array {
        return [
            'name'  => 'required|max:100',
            'email' => 'required|email',
            'notes' => 'required'
        ];
    }
}
```
Và tiêm vào Controller:
```php
public function store(StoreContactRequest $request) {
    // CHỈ lấy những trường đã được định nghĩa trong rules() và đã pass qua validation
    $validatedData = $request->validated();
    
    // An toàn lưu vào DB, không sợ bị bơm các trường lạ (Mass Assignment)
    ContactModel::insert($validatedData);
}
```

---

## 7. Cấu Trúc Dữ Liệu Đặc Thù Của CMS (Database Schema & Logic)

### 7.1. Cơ Chế Đa Ngôn Ngữ (Two-Table Translatable Architecture)
Hệ thống sử dụng kiến trúc hai bảng (Two-Table) để lưu trữ đa ngôn ngữ, hoàn toàn thay thế cho cơ chế `id_code` lỗi thời.
- **Bảng gốc (Ví dụ: `db_categories`):** Chỉ lưu trữ các trường dữ liệu dùng chung (Không phụ thuộc ngôn ngữ) như: `id`, `parent_id`, `status`, `image`, `created_at`.
- **Bảng dịch (Ví dụ: `db_category_translations`):** Lưu trữ các trường phụ thuộc ngôn ngữ như: `id`, `category_id` (Khóa ngoại), `lang` (Mã ngôn ngữ: `vi`, `en`), `title`, `slug`, `content`.
- **Tích hợp Model:** Lớp Model (Ví dụ: `CategoryModel`) chỉ cần `use \App\Traits\Translatable` và khai báo biến mảng `$translatedAttributes`. 
- **Truy xuất thông minh:** Hệ thống tự động load bản dịch thông qua hook Magic Method `__get()`. Truy xuất `$category->title` sẽ tự động lấy `title` từ bảng translation theo ngôn ngữ hiện tại của Request. Model giải quyết vấn đề Eager Loading `->with('translations')` để tránh N+1 Query.

### 7.2. Cơ Chế Tách Đơn Hàng (Order Splitting)
CMS hỗ trợ mô hình Marketplace (Nhiều cửa hàng):
- Khi khách hàng mua nhiều món từ nhiều Shop khác nhau, hệ thống sinh ra **1 `db_checkout_sessions`** để thanh toán tổng tiền.
- Đồng thời tự động tách ra thành **nhiều `db_orders`** (Mỗi shop sở hữu 1 order riêng biệt để tự giao vận và quản lý trạng thái).
- **Chú ý:** Tuyệt đối không được chèn toàn bộ sản phẩm của nhiều shop vào cùng một dòng order duy nhất.

---

## 8. Quy Chuẩn Thư Mục Giao Diện (`resources/views/`)

*   **`layouts/`**: Chứa các layout khung chính (`main.php`, `admin/layouts/main.php`).
*   **`pages/`**: Chứa giao diện cụ thể của từng trang được phân nhóm rõ ràng (vd: `pages/home/index.php`, `pages/products/detail.php`).
*   **`partials/`**: Các mảnh ghép giao diện nhỏ (`partials/header.php`, `partials/footer.php`).
*   **`components/`**: Các thành phần giao diện dùng chung (vd: `components/card-product.php`, các components render form admin).

---

## 9. Bảng Quy Tắc Sống Còn Cho Lập Trình Viên (The Hard Rules)

1.  **Cấm viết logic Database trong View:** View chỉ dùng để render dữ liệu đã được Controller chuẩn bị sẵn.
2.  **Cấm truy cập Session trực tiếp cho Old/Errors:** Sử dụng helper `old('field')` và `errors('field')` để hiển thị lỗi validation.
3.  **Tận dụng Dependency Injection:** Hạn chế dùng `new Request()`. Hãy khai báo `Request $request` hoặc `MyFormRequest $request` làm tham số của method trong Controller để Container tự động xử lý.
4.  **Sử dụng Named Routes:** Luôn gọi `route('name')` thay vị viết cứng URL tĩnh `/vi/san-pham/...`.
5.  **Dùng Tiện Ích Core:** Nếu cần lấy Auth user, dùng `App\Core\Auth\AuthManager::user()` hoặc `App\Core\Auth\Auth`. Nếu cần kiểm tra quyền, dùng `hasPermission()` hoặc `Gate::check()`. Nếu cần cấu hình, dùng `config()`. Đừng tự viết câu SQL select bảng config hay users!

---

> **TỔNG KẾT:** Hãy tuân thủ cấu trúc này để giữ cho CMS luôn sạch sẽ, bảo mật và dễ mở rộng. Chúc bạn code vui vẻ!
