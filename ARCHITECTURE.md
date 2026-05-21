# ARCHITECTURE.md — Tài Liệu Kiến Trúc Hệ Thống CMS (Technical Reference)

> **DÀNH CHO AI AGENT:** Đây là tài liệu phân tích chi tiết 100% các file trong thư mục `app/`. Hãy đọc để hiểu logic thực thi của hệ thống.

---

## 0. Thông Tin Hệ Thống (System Overview)

| Thông số | Chi tiết |
|---|---|
| **Ngôn ngữ lập trình** | PHP 7.4+ (Yêu cầu hỗ trợ typed properties, arrow functions). |
| **Cơ sở dữ liệu** | MySQL 5.7+. Table Prefix: `db_` (Dùng `#_` trong code). |
| **Kiến trúc** | Custom MVC (Inspired by Laravel). |
| **Đa ngôn ngữ** | Hỗ trợ mặc định: `vi` (Tiếng Việt), `en` (Tiếng Anh). |
| **Quy ước URL** | SEO Friendly: `URLPATH/lang/slug.html`. |
| **Layout chính** | `resources/views/layouts/main.php`. |
| **Thư mục Assets** | `/assets/` (CSS, JS, Fonts, Images tĩnh). |
| **Thư mục Data** | `/img_data/` (Ảnh dữ liệu người dùng tải lên). |
| **Môi trường** | Laragon / XAMPP (Localhost:81). |

---

## 1. Tổng Quan Nhân Hệ Thống (app/Core)
Đây là "bộ não" điều khiển mọi hoạt động của CMS.

| File | Chức năng chi tiết | Các hàm/thuộc tính quan trọng |
|---|---|---|
| `App.php` | **Kernel hệ thống**. Khởi tạo toàn bộ ứng dụng. | `boot()`: Khởi động DB, nạp Middleware, chạy Router. |
| `Router.php` | **Bộ điều hướng**. Ánh xạ URL tới Controller. | `dispatch()`: Xử lý route tĩnh và route có tham số (Laravel-style). Hỗ trợ đặt tên (Named Routes). |
| `Model.php` | **Base ORM**. Xử lý truy vấn Database (Query Builder). | `query()`, `with()`, `where()`, `get()`, `first()`. Hỗ trợ Lazy Discovery cho cột `lang`. |
| `View.php` | **Bộ dựng giao diện (Renderer)**. | `render()`: Nạp layout và template; `share()`: Chia sẻ biến toàn cục cho view. |
| `Request.php` | **Quản lý Yêu cầu**. Bao bọc các biến toàn cục của PHP. | `all()`, `input()`, `isMethod()`, `getUri()`. |
| `Response.php` | **Quản lý Phản hồi**. Thiết lập Header và HTTP Status. | `json()`, `header()`, `status()`. |
| `Config.php` | **Quản lý Cấu hình**. | `get($key)`: Lấy dữ liệu từ thư mục `config/`. |
| `ExceptionHandler.php` | **Xử lý lỗi**. Hiển thị thông báo lỗi thân thiện hoặc log lỗi. | `handleException()`, `handleError()`. |
| `Logger.php` | **Ghi log**. Lưu vết hoạt động và lỗi hệ thống. | `info()`, `error()`, `warning()`. |

---

## 2. Các Lớp Lọc Request (app/Middleware)
Các lớp này được thực thi trước khi request chạm tới Controller.

| File | Chức năng |
|---|---|
| `StartSession.php` | Đảm bảo `session_start()` được gọi đúng cách và an toàn. |
| `LanguageMiddleware.php` | Phân tích URL (vd: `/vi/`, `/en/`) để thiết lập hằng số `_lang` và `_where_lang`. |
| `SitePasswordMiddleware.php` | Chặn toàn bộ website bằng mật khẩu nếu cấu hình `protection => true`. |
| `Middleware.php` | Lớp cơ sở (Abstract) định nghĩa cấu trúc cho mọi Middleware. |

---

## 3. Các Lớp Dịch Vụ (app/Services)
Chứa logic nghiệp vụ phức tạp, tách biệt khỏi Controller.

| File | Chức năng |
|---|---|
| `SiteInfoService.php` | Tập hợp mọi thông tin cấu hình website (Logo, Hotline, SEO) để dùng chung toàn trang. |
| `Service.php` | Lớp cơ sở cho các Service sau này. |

---

## 4. Các Lớp Điều Khiển (app/Controllers)
Xử lý logic từng trang cụ thể.

| File | Chức năng |
|---|---|
| `Controller.php` | Lớp cơ sở chứa các hàm dùng chung cho mọi Controller (vd: `validate`). |
| `HomeController.php` | Xử lý dữ liệu phức tạp cho trang chủ (Slide, Sản phẩm theo tab, Tin tức). |
| `ProductController.php` | Xử lý danh sách (index) và chi tiết (show) sản phẩm. Hỗ trợ Eager Loading quan hệ. |
| `CategoryController.php`| Xử lý danh sách sản phẩm hoặc bài viết theo chuyên mục. |
| `NewsController.php` | Xử lý danh sách và chi tiết bài viết/tin tức. |
| `PageController.php` | Xử lý các trang thông tin tĩnh lưu trong DB. Xử lý qua catch-all route `/{slug}`. |
| `CartController.php` | Xử lý giỏ hàng (thêm/sửa/xóa, coupon) và tính toán tổng tiền. |
| `CheckoutController.php` | Xử lý quy trình đặt hàng và gửi email thông báo. |
| `LocationController.php`| Quản lý AJAX truy xuất dữ liệu địa lý (Tỉnh/Huyện/Xã). |
| `ContactController.php` | Tiếp nhận và xử lý dữ liệu từ Form liên hệ. |
| `ReviewController.php` | Quản lý đánh giá sao, bình luận và tải lên hình ảnh AJAX. |
| `SearchController.php` | Thực hiện tìm kiếm Full-text trên nhiều bảng dữ liệu. |
| `AuthController.php` | Quản lý Đăng nhập, Đăng ký, Quên mật khẩu. |

---

## 5. Các Lớp Dữ Liệu (app/Models)
Tương tác trực tiếp với từng bảng Database cụ thể (23+ Models).

- **Nhóm Sản phẩm**: `ProductModel`, `ProductVariantModel`, `ProductAlbumModel`.
- **Nhóm Nội dung**: `CategoryModel`, `NewsModel`, `PageModel`, `BinhLuanModel`.
- **Nhóm Hệ thống**: `SettingModel`, `TextModel` (Dịch), `LanguageModel`, `MenuModel`, `ModuleModel`.
- **Nhóm Khác**: `ContactModel`, `OrderModel`, `CouponModel`, `VideoModel`, `AlbumModel`, `ButtonContactModel`.

---

## 6. Các Hàm Tiện Ích (app/Helpers)

| File | Các hàm "Vàng" |
|---|---|
| `core.php` | `__($key)`, `view()`, `config()`, `asset()`, `session()`. |
| `ui.php` | `Img()`, `renderPrice()`, `createAlias()`, `get_json()`. |
| `url.php` | `route($name)`, `redirect()`, `back()`, `url()`, `getCurrentUrl()`. |
| `string.php` | `str_slug()`, `str_random()`, `limit_text()`. |

---

## 7. Quy Tắc "Thiết Giáp" Cho AI (AI Hard Rules)

1.  **Cấm Hard-code**: Tuyệt đối không viết trực tiếp URL hay Text tiếng Việt. Dùng `asset()` và `__()`.
2.  **Object Only**: Dữ liệu từ Model LUÔN là Object. Cấm dùng `$row['key']`.
3.  **Controller Logic**: Không bao giờ viết logic SQL trong View. View chỉ dùng để hiển thị.
4.  **Middleware**: Mọi kiểm tra quyền truy cập phải nằm ở Middleware, không viết trong Controller.

---

## 8. Cấu Trúc Dữ Liệu Quan Trọng (Database Schema)

AI Agent phải đặc biệt lưu ý cơ chế khóa ngoại trong hệ thống này:

| Bảng chính | Bảng liên quan | Khóa nối (Join Key) | Ghi chú |
|---|---|---|---|
| `db_sanpham` | `db_category` | `id_loai` -> `id_code` | Nối sản phẩm với danh mục. |
| `db_sanpham` | `db_sanpham_bienthe` | `id_code` -> `id_sanpham` | Lấy các biến thể (màu, size). |
| `db_sanpham` | `db_sanpham_hinhanh` | `id_code` -> `id_sanpham` | Lấy album ảnh sản phẩm. |
| `db_tintuc` | `db_category` | `id_loai` -> `id_code` | Nối bài viết với danh mục. |
| `db_menu` | (Chính nó) | `id_loai` -> `id_code` | Cấu trúc menu đa cấp. |

> **⚠️ CẢNH BÁO:** Luôn sử dụng **`id_code`** để định danh thực thể. Cột `id` chỉ dùng cho mục đích kỹ thuật của từng dòng ngôn ngữ.

---

## 9. Quy Trình Phát Triển Tính Năng Mới (Workflow)

Khi AI Agent nhận yêu cầu tạo thêm một trang hoặc module mới, hãy tuân thủ 4 bước:

1.  **Model**: Kiểm tra xem đã có Model cho bảng tương ứng chưa (tại `app/Models/`). Nếu chưa, hãy tạo mới kế thừa từ `Model`.
2.  **Controller**: Tạo Controller mới tại `app/Controllers/`. Xử lý dữ liệu và trả về `view()`.
3.  **Route**: Đăng ký đường dẫn URL trong `routes/web.php`.
4.  **View**: Tạo file giao diện tại `resources/views/`. Sử dụng `layout('main')` để giữ đồng nhất.

---

## 10. Hệ Thống AJAX & API

Hệ thống đang hỗ trợ song song hai cách tiếp cận:

*   **Legacy AJAX**: Nằm tại `sources/ajax/ajax.php`. Dùng cho các tính năng cũ.
*   **Modern AJAX**: 
    *   Đăng ký route trong `routes/web.php` với method `POST`.
    *   Controller trả về `Response::json($data)`.
    *   **Ưu tiên**: Luôn sử dụng cách này cho các tính năng mới để đảm bảo tính đóng gói.

---

## 11. Các Trạng Thái Lỗi & Debug
*   **Logs**: Kiểm tra tại `storage/logs/` (nếu có) hoặc log lỗi của PHP.
*   **Maintenance**: Bật/Tắt chế độ bảo trì qua `config('app.protection')`.

---

## 12. Quy Ước View & Giao Diện (Frontend Guidelines)

Hệ thống sử dụng cấu trúc thư mục phân lớp để quản lý giao diện chuyên nghiệp:

### 12.1 Cấu trúc thư mục `resources/views/`
*   **`layouts/`**: Chứa các file khung chính (vd: `main.php`).
*   **`pages/`**: Chứa giao diện của từng trang, phân nhóm theo tính năng:
    *   `home/`: Trang chủ.
    *   `products/`: Danh sách (`index`) và chi tiết (`detail`) sản phẩm.
    *   `news/`: Danh sách (`index`) và chi tiết (`detail`) tin tức.
    *   `auth/`: Đăng nhập, đăng ký, thông tin tài khoản.
    *   `cart/`: Giỏ hàng, thanh toán, thông báo thành công.
*   **`partials/`**: Chứa các thành phần nhỏ (Components):
    *   `header/`, `footer/`: Đầu và cuối trang.
    *   `components/`: Các khối nhỏ như `card-product`, `card-post`, `slider`.
    *   `home/`: Các khối riêng cho trang chủ.

### 12.2 Cách sử dụng hàm `view()`
*   Trong **Controller**: Trả về view theo dấu chấm hoặc gạch chéo:
    ```php
    return view('pages.products.index', $data);
    ```
*   Trong **View (Include)**: Sử dụng helper `view()` để nạp các mảnh ghép:
    ```php
    <?php include view('partials.header'); ?>
    <?php include view('partials.components.card-product'); ?>
    ```

### 12.3 Assets Mapping
*   CSS: `/assets/css/` | JS: `/assets/script/` | Fonts: `/assets/fonts/`
*   Luôn gọi qua helper `asset('path/to/file')`.

---

## 13. Hệ Thống SEO & Sitemap

Hệ thống được thiết kế để tối ưu cho công cụ tìm kiếm:

*   **Sitemap chính**: `sitemap.xml` tại thư mục gốc.
*   **Sitemap phân mảnh**: Nằm trong thư mục `sitemap/` (vd: `product-sitemap.xml`, `post-sitemap.xml`) giúp quản lý hàng nghìn URL dễ dàng hơn.
*   **Robots.txt**: Cấu hình các quy tắc chặn/cho phép bot tìm kiếm tại file `robots.txt` ở gốc.
*   **SEO Metadata**: Được quản lý tập trung trong bảng `db_thongtin` và Model `SettingModel`.

---

## 14. Hệ Thống Gửi Email (SMTP)

Dự án tích hợp sẵn thư viện **PHPMailer** để xử lý các tác vụ gửi mail:

*   **Vị trí**: Thư mục `smtp/` chứa các class lõi của PHPMailer.
*   **Cấu hình**: Thông tin tài khoản SMTP (Host, Port, User, Password) thường được cấu hình trong Admin hoặc file `config/mail.php` (nếu có).
*   **Cách dùng**: Sử dụng các lớp trong `smtp/` để gửi mail xác nhận đơn hàng hoặc thông báo liên hệ mới.

---

## 15. Các Mẫu Thiết Kế Mới Đã Áp Dụng (Modernization Patterns)

Hệ thống vừa trải qua quá trình tái cấu trúc để loại bỏ hoàn toàn việc gọi Database từ View (Legacy `$d->o_fet`). Yêu cầu sử dụng các mẫu thiết kế này cho mọi module tiếp theo:

### 15.1 Kiến Trúc View - Controller Hiện Đại
*   **Controller Chuẩn Bị Dữ Liệu**: Mọi logic truy vấn phức tạp (tính toán giá, gom nhóm thuộc tính biến thể, eager loading) BẮT BUỘC phải thực hiện ở Controller. (Xem mẫu tại `ProductController@show`).
*   **View Tĩnh (Dumb View)**: View (như `detail.php`) CHỈ làm nhiệm vụ render HTML. Tuyệt đối KHÔNG chứa câu lệnh truy vấn SQL.
*   **Đa Ngôn Ngữ & Helper**: Chuỗi tĩnh (hardcode) trên giao diện phải bọc trong hàm `__()`. Sử dụng các Helper chuyên dụng (`renderPrice()`, `renderStars()`, `getImageUrl()`) thay vì tự code định dạng thủ công.

### 15.2 Bảo Mật Form & Token (CSRF)
*   **Cấm gọi Session**: Tuyệt đối không dùng trực tiếp `$_SESSION['token']` trong các thẻ form HTML.
*   **Sử dụng Helper**: Luôn dùng hàm `<?= csrf_field() ?>` để tự động sinh thẻ input chứa mã CSRF bảo vệ cho mọi form (như form giỏ hàng, bình luận). Mã token thô được lấy qua `csrf_token()`.

### 15.3 Router Hiện Đại & Named Routes
*   **Named Routes**: Mọi route đều nên được đặt tên (ví dụ: `->name('product.show')`). Ở View bắt buộc dùng helper `route('product.show', $slug)` để in URL thay vì nối chuỗi tĩnh, giúp việc đổi cấu trúc URL hàng loạt (từ `/san-pham/` thành `/sp/`) chỉ tốn 1 giây tại file config.
*   **Typed URLs**: Phân chia URL minh bạch có tiền tố (ví dụ `/san-pham/{slug}`, `/tin-tuc/{slug}`) thay vì dựa dẫm vào cơ chế Catch-all (Tìm trong DB xem nó là loại gì). Catch-all route `/{slug}` hiện chỉ dùng chuyên biệt cho `PageController` (trang tĩnh).
*   **Response Wrapping**: Khi nâng cấp, nhiều Controller cũ trả về `String`. Để không làm lỗi hàm `send()`, hàm `run()` trong `App.php` sẽ tự bọc chuỗi trả về vào lớp `Response` (tương thích ngược 100%).

### 15.4 Nâng Cấp Model & Eager Loading
*   **Eager Loading Mảng Thành Object**: Khi nạp quan hệ qua `withMedia()` hoặc `withReplies()`, dữ liệu thô (raw arrays) tự động được ép kiểu thành Model Objects bằng `array_map()`, ngăn lỗi truy cập thuộc tính (property of non-object) trong View.
*   **Hỗ trợ Magic Properties**: Bổ sung `__isset()` trong lõi Base `Model` để hàm `empty($model->relation)` của PHP làm việc chuẩn xác với Eager Loading Relation.

---

## 16. Quy Chuẩn Viết Code & Định Dạng (Coding Style & Formatting)

AI Agent phải tuân thủ bộ quy tắc này để đảm bảo code sạch, đẹp và dễ đọc:

### 16.1 Đặt tên (Naming Convention)
*   **Classes**: Luôn dùng `PascalCase` (vd: `ProductController`, `TextModel`).
*   **Methods**: Luôn dùng `camelCase` (vd: `getFeaturedProducts()`, `translate()`).
*   **Variables & Table Columns**: Luôn dùng `snake_case` (vd: `product_id`, `is_active`).
*   **Constants**: Luôn dùng `UPPER_CASE` (vd: `URLPATH`, `LANG`).

### 16.2 Cấu trúc & Định dạng
*   **Indentation**: Sử dụng **4 khoảng trắng** (spaces), không dùng Tab.
*   **Braces**:
    *   Mở ngoặc `{` của Class và Method ở **dòng mới**.
    *   Mở ngoặc `{` của các cấu trúc điều khiển (`if`, `foreach`) ở **cùng dòng**.
*   **Spacing**: Luôn có 1 khoảng trắng sau các từ khóa `if`, `for`, `foreach`.

### 16.3 Tư duy viết code (Best Practices)
*   **Early Return**: Ưu tiên xử lý lỗi và thoát hàm sớm để tránh lồng `if` quá sâu.
    ```php
    // TỐT
    if (!$product) return null;
    return $product->name;

    // XẤU
    if ($product) {
        return $product->name;
    } else {
        return null;
    }
    ```
*   **Type Hinting**: Khai báo kiểu dữ liệu cho tham số và giá trị trả về của hàm.
    ```php
    public function getById(int $id): ?ProductModel { ... }
    ```
*   **PHPDoc**: Luôn viết comment mô tả cho các hàm phức tạp.
*   **No Magic Numbers**: Không dùng các con số khó hiểu trực tiếp. Hãy đặt biến hoặc hằng số.

### 16.4 Format View (HTML/PHP)
*   Code PHP trong View phải ngắn gọn. Ưu tiên dùng cú pháp rút gọn:
    *   `<?= $var ?>` thay cho `<?php echo $var; ?>`.
    *   `<?php if (...): ?> ... <?php endif; ?>` thay cho dùng ngoặc nhọn `{}`.

---

## 17. Kết Luận & Duy Trì

Tài liệu này là "Kim chỉ nam" cho mọi hoạt động phát triển. AI Agent khi thực hiện nhiệm vụ phải:
1.  Đọc mục tương ứng với nhiệm vụ.
2.  Kiểm tra xem tính năng định viết có vi phạm các "Gold Rules" không.
3.  Tuân thủ tuyệt đối quy chuẩn định dạng tại Mục 16.

---

> **Lời nhắn cuối cho AI:** CMS này là sự kết hợp giữa sự ổn định của quá khứ và sự linh hoạt của hiện tại. Hãy luôn ưu tiên viết code **"Modern"** nhưng đừng phá vỡ các quy tắc **"Legacy"** về dữ liệu (`id_code`).

---

## 18. Ranh Giới Giữa "Lõi Framework" và "Ứng Dụng" (Core vs App)

Một phần quan trọng của việc duy trì và phát triển hệ thống này là phân biệt rõ ràng giữa **Phần Lõi (Core Framework)** - không nên thay đổi tùy tiện, và **Phần Ứng Dụng (User App Space)** - nơi bạn tự do phát triển các tính năng.

### 18.1. Vùng Lõi Framework (Do NOT Touch Unless Necessary)
Đây là các thành phần cốt lõi của "Engine", tạo nền tảng cho CMS. Chỉ nên chỉnh sửa khi thực sự muốn thay đổi cách Framework hoạt động.
- **`app/Core/`**: Chứa toàn bộ các class cấu thành framework (`App.php`, `Router.php`, `Model.php`, `Request.php`, `Response.php`, `View.php`...).
- **`app/Helpers/`**: Các hàm trợ giúp chung (`core.php`, `url.php`, `string.php`).
- **`app/Middleware/`**: Các lớp trung gian kiểm soát request toàn cục (Session, Language).
- **`index.php`**: File khởi tạo duy nhất (Entry point) để kích hoạt `App::getInstance()->boot()`.
- **`bootstrap/`**: Thư mục chuẩn bị môi trường và tự động nạp (autoload).

### 18.2. Vùng Ứng Dụng (App Implementation)
Đây là không gian làm việc của lập trình viên, nơi chứa logic nghiệp vụ và giao diện.
- **`app/Controllers/`**: Nơi viết logic cho từng module (vd: `ProductController`, `CartController`).
- **`app/Models/`**: Nơi ánh xạ tới các bảng CSDL cụ thể (vd: `ProductModel`, `CategoryModel`).
- **`app/Services/`**: Nơi đặt các logic phức tạp, gọi API bên ngoài (nếu có).
- **`routes/web.php`**: Khai báo các đường dẫn URL và ánh xạ tới Controller.
- **`resources/views/`**: Nơi chứa toàn bộ mã HTML/PHP giao diện hiển thị cho người dùng.
- **`config/`**: Các file cấu hình hệ thống (như Database, App parameters).

### 18.3. Vùng Legacy & Quản Trị (Admin & Sources)
Hệ thống này được nâng cấp từ một CMS thuần Procedural cũ, nên vẫn giữ lại một số vùng cho Admin Panel.
- **`admin/`**: Giao diện và mã nguồn quản lý Dashboard CMS (Thường ít can thiệp bằng OOP).
- **`sources/`**: Một số module cũ hoặc file ajax thuần tuý (đang được gỡ bỏ và chuyển dần sang App/Controllers).

**Nhiệm vụ của Developer:** Tập trung phát triển ở khu vực `18.2` (Controllers, Models, Views, Routes). Hạn chế chạm vào `18.1` trừ khi phải vá lỗi nghiêm trọng ở mức Framework.
