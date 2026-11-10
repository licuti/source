# TÀI LIỆU TÍNH NĂNG: SAO CHÉP ĐƯỜNG DẪN MEDIA (URL & PATH)

> **Mục đích:** Cung cấp tính năng sao chép đường dẫn file ảnh, video, tài liệu trong Media để quản trị viên dễ dàng lấy link nhúng vào bài viết, cấu hình, mã nguồn hoặc chia sẻ.  
> **Ngày cập nhật:** 21/10/2026.  
> **Mục tiêu tài liệu:** Ghi nhận toàn bộ các vị trí đã can thiệp để sau này nếu muốn **chỉnh sửa**, **tạm tắt** hoặc **gỡ bỏ hoàn toàn (rollback)** thì thực hiện rất nhanh chóng và an toàn.

---

## 1. Tổng quan các vị trí can thiệp

Tính năng này gồm 3 nhóm thành phần chính:
1. **Plugin CKFinder** (Thanh Quick Action Bar & nút Copy Link khi chọn file trong CKFinder).
2. **Topbar Admin** (Nút truy cập nhanh "Thư viện Media").
3. **Form Pickers & Clipboard JS** (Nút copy trên các ô chọn ảnh, video, bằng cấp, album).

| STT | File | Loại thay đổi | Chức năng |
|:---:|:---|:---:|:---|
| 1 | `admin/js/ckfinder-copy-url.js` | **Tạo mới** | Script tạo thanh Quick Action Bar (Copy URL đầy đủ, Copy Path, Mở file) trong CKFinder |
| 2 | `admin/ckfinder/ckfinder.html` | Chỉnh sửa | Nhúng `ckfinder-copy-url.js` vào CKFinder standalone |
| 3 | `admin/index.php` | Chỉnh sửa | Nhúng `js/ckfinder-copy-url.js` vào trang quản trị |
| 4 | `admin/js/ckfinder-adapter.js` | Chỉnh sửa | Kích hoạt `CKFinderCopyUrl.init(finder)` trong modal và hiển thị nút copy khi chọn file |
| 5 | `admin/templates/header_tpl.php` | Chỉnh sửa | Thêm nút "Thư viện Media" trên thanh topbar |
| 6 | `admin/templates/components/form/image_picker.php` | Chỉnh sửa | Thêm nút copy cạnh nút xóa ảnh |
| 7 | `admin/templates/components/form/file_picker.php` | Chỉnh sửa | Thêm nút copy cho ảnh, video, tài liệu |
| 8 | `admin/templates/components/form/image_repeater.php` | Chỉnh sửa | Thêm nút copy trên từng dòng bằng cấp |
| 9 | `admin/templates/components/form/image_gallery.php` | Chỉnh sửa | Thêm nút copy trên từng ảnh album |
| 10 | `admin/js/admin-ui.js` | Chỉnh sửa | Hàm `initCopyFileUrlActions()` xử lý clipboard và Toast |

---

## 2. Chi tiết từng file & vị trí code

### 2.1. File tạo mới: `admin/js/ckfinder-copy-url.js`
- **Đường dẫn:** `admin/js/ckfinder-copy-url.js`
- **Nhiệm vụ:**
  - Tự động bắt sự kiện `finder.on('files:selected')`.
  - Hiển thị thanh Action Bar bo tròn màu tối kính mờ (glassmorphism) ở đáy màn hình CKFinder:
    - Nút **Sao chép URL đầy đủ**: `window.location.origin + file.getUrl()` (Ví dụ: `http://domain.com/media/videos/intro.mp4`).
    - Nút **Sao chép Path**: `file.getUrl().replace(/^\//, '')` (Ví dụ: `media/videos/intro.mp4`).
    - Nút **Mở**: Mở file trong tab mới.
  - Tự động ẩn thanh khi bỏ chọn file hoặc chuyển thư mục (`toolbar:reset:Main:empty`, `folder:getFiles:after`).
  - Hỗ trợ copy cả qua Clipboard API hiện đại lẫn fallback `document.execCommand('copy')`.

### 2.2. Nhúng vào CKFinder standalone: `admin/ckfinder/ckfinder.html`
- **Vị trí:** Khoảng dòng 15-24:
  ```html
  <script src="ckfinder.js"></script>
  <script src="../js/ckfinder-copy-url.js"></script>
  <script>
      CKFinder.start({
          onInit: function( finder ) {
              window.finder = finder;
              if (window.CKFinderCopyUrl) {
                  window.CKFinderCopyUrl.init(finder);
              }
          }
      });
  </script>
  ```

### 2.3. Nhúng vào Admin Index: `admin/index.php`
- **Vị trí:** Khoảng dòng 104-108:
  ```html
      <script src="js/admin-ui.js"></script>
      <script>window.ADMIN_FM_CONFIG = <?= fm_client_config_json() ?>;</script>
      <script src="js/ckfinder-copy-url.js"></script>
      <script src="js/ckfinder-adapter.js"></script>
  ```

### 2.4. Adapter modal: `admin/js/ckfinder-adapter.js`
- **Vị trí 1 (Hiển thị nút copy khi chọn file thành công):** Khoảng dòng 148-163:
  ```javascript
  var copyBtn = picker.querySelector('.js-copy-file-url');
  if (copyBtn) {
      copyBtn.style.display = '';
      copyBtn.classList.remove('d-none');
  }
  ```
- **Vị trí 2 (Thẻ card album động có nút copy):** Khoảng dòng 81-89:
  ```javascript
  '<button type="button" class="btn btn-sm btn-outline-secondary js-copy-file-url flex-grow-1" data-copy-val="' + mediaUrl(url, preview) + '" title="Sao chép đường dẫn"><i class="fa-solid fa-copy me-1"></i>Copy</button>'
  ```
- **Vị trí 3 (Kích hoạt thanh copy trong modal):** Khoảng dòng 325-330:
  ```javascript
  onInit: function (finder) {
      if (window.CKFinderCopyUrl && typeof window.CKFinderCopyUrl.init === 'function') {
          window.CKFinderCopyUrl.init(finder);
      }
      ...
  ```

### 2.5. Nút trên thanh Topbar Admin: `admin/templates/header_tpl.php`
- **Vị trí:** Khoảng dòng 48-54:
  ```html
  <li class="nav-item">
      <a class="nav-link iframe-btn js-fm-open" href="ckfinder/ckfinder.html" data-fm-type="all" data-fm-resource="" title="Mở thư viện Media (Quản lý &amp; Sao chép đường dẫn file)">
          <i class="fa-solid fa-photo-film me-1 text-primary"></i>Thư viện Media
      </a>
  </li>
  ```
  *(Để `data-fm-type="all"` và `data-fm-resource=""` giúp CKFinder hiển thị toàn bộ cây thư mục gồm Images, Videos, Files, Assets thay vì chỉ riêng thư mục Files).*

### 2.6. Component chọn hình: `admin/templates/components/form/image_picker.php`
- **Vị trí:** Khoảng dòng 36-47:
  ```html
  <div class="position-absolute top-0 end-0 m-1 d-flex gap-1">
      <button type="button"
              class="btn btn-sm btn-light border shadow-sm js-copy-file-url <?= $hasImage ? '' : 'd-none' ?>"
              title="Sao chép đường dẫn hình ảnh"
              data-target="<?= e($id) ?>"
              data-preview="image">
          <i class="fa-solid fa-copy"></i>
      </button>
      <button type="button" class="btn btn-sm btn-danger js-file-picker-delete ...">
  ```

### 2.7. Component chọn video/file: `admin/templates/components/form/file_picker.php`
- **Vị trí:**
  - Chế độ `image`: Nút `.js-copy-file-url` bên cạnh nút xóa.
  - Chế độ `video`: Nút `.js-copy-file-url` bên cạnh nút xóa.
  - Chế độ `file`: Nút `.js-copy-file-url` bên cạnh nút xóa.

### 2.8. Component bằng cấp (Repeater): `admin/templates/components/form/image_repeater.php`
- **Vị trí:**
  - Dòng 88-95: Nút copy trên từng item có sẵn:
    ```html
    <button type="button" class="btn btn-sm btn-outline-info js-copy-file-url py-1 px-2 <?= $hasImg ? '' : 'd-none' ?>" ...>
        <i class="fa-solid fa-copy me-1"></i>Copy
    </button>
    ```
  - Dòng 190-200: Nút copy trong thẻ `<template id="tmpl_..._item">` khi bấm thêm dòng mới.
  - Dòng 290-300: Khi bấm nút bỏ ảnh (`js-clear-repeater-img`), tự động ẩn nút copy.

### 2.9. Component album: `admin/templates/components/form/image_gallery.php`
- **Vị trí:** Khoảng dòng 42-53: Nút `Copy` cạnh nút Xóa trên từng ảnh album.

### 2.10. JS điều khiển chung: `admin/js/admin-ui.js`
- **Vị trí 1 (Ẩn nút copy khi người dùng bấm xóa ảnh/file):** Khoảng dòng 830-838:
  ```javascript
  var pickerEl = btn.closest('.form-file-picker') || btn.closest('[data-file-picker]');
  if (pickerEl) {
      var copyBtnEl = pickerEl.querySelector('.js-copy-file-url');
      if (copyBtnEl) {
          copyBtnEl.style.display = '';
          copyBtnEl.classList.add('d-none');
      }
  }
  ```
- **Vị trí 2 (Hàm `initCopyFileUrlActions()`):** Khoảng dòng 903-1015.
- **Vị trí 3 (Gọi hàm trong `DOMContentLoaded`):** Dòng 1035:
  ```javascript
  initCopyFileUrlActions();
  ```

---

## 3. Hướng dẫn gỡ bỏ nhanh (Rollback) nếu không dùng nữa

Nếu sau này không muốn dùng tính năng này nữa, có thể chọn 1 trong 2 mức độ:

### Mức độ A: Tạm tắt tính năng (nhanh nhất - dưới 1 phút)
1. Trong `admin/js/admin-ui.js`: Bỏ dòng gọi `initCopyFileUrlActions();` ở cuối file.
2. Trong `admin/index.php`: Bỏ dòng `<script src="js/ckfinder-copy-url.js"></script>`.
3. Trong `admin/ckfinder/ckfinder.html`: Bỏ dòng gọi `CKFinderCopyUrl.init(finder)`.
4. Trong `admin/templates/header_tpl.php`: Xóa khối `<li>` chứa nút "Thư viện Media".
*(Các nút copy trên form lúc này sẽ không hoạt động hoặc có thể ẩn bằng CSS `.js-copy-file-url { display: none !important; }`)*

### Mức độ B: Gỡ bỏ hoàn toàn sạch sẽ (Clean Rollback)
1. Xóa file `admin/js/ckfinder-copy-url.js`.
2. Trong `admin/ckfinder/ckfinder.html`: Trả về lại nguyên bản chỉ có `CKFinder.start(...)`.
3. Trong `admin/index.php`: Xóa dòng nạp `ckfinder-copy-url.js`.
4. Trong `admin/templates/header_tpl.php`: Xóa nút `Thư viện Media`.
5. Trong `admin/js/ckfinder-adapter.js`: Xóa khối gọi `CKFinderCopyUrl.init(finder)` và biến `copyBtn`.
6. Trong `admin/js/admin-ui.js`: Xóa hàm `initCopyFileUrlActions()` và dòng gọi ở `DOMContentLoaded`.
7. Trong các component (`image_picker.php`, `file_picker.php`, `image_repeater.php`, `image_gallery.php`): Xóa các nút có class `js-copy-file-url`.
