# 🎨 ADMIN UI/UX STYLE GUIDE

Tài liệu quy định phong cách giao diện thống nhất cho toàn bộ Admin Panel. Mọi module mới và chỉnh sửa cũ **BẮT BUỘC** phải tuân theo tài liệu này để đảm bảo tính đồng bộ.

> **Stack:** Bootstrap 5.3 + AdminLTE 3 + Font Awesome 6 (Đã có sẵn trong layout)

---

## 1. 📐 CẤU TRÚC TRANG DANH SÁCH (Index Page)

Mọi trang danh sách phải tuân theo cấu trúc 3 phần sau:

### 1.1 Phần Header (Breadcrumb + Nút hành động)

```php
<?php $title = 'Tiêu đề module'; ?>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Tên module', 'url' => route('admin.module.index')],
        ['name' => 'Trang hiện tại', 'url' => '']
    ],
    'actions' => [
        // Nút chính (Thêm mới) có thể đặt ở đây hoặc trong card-tools của bảng dữ liệu
        // ['label' => 'Thêm mới', 'icon' => 'fa-plus', 'url' => route('admin.module.create'), 'class' => 'btn-primary'],
        // Nút phụ (Scan, Export...)
        ['label' => 'Xuất Excel', 'icon' => 'fa-file-excel', 'url' => '#', 'class' => 'btn-success'],
    ]
]) ?>
```

### 1.2 Phần Bộ lọc & Tìm kiếm (BẮT BUỘC có trên mọi trang danh sách)

```html
```html
<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm">
            
            <!-- HEADER: Bulk Action, Filter, Search -->
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    
                    <!-- TRÁI: Hành động hàng loạt (Bulk actions) -->
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <option value="delete" data-url="<?= route('admin.module.destroy_multiple') ?>" data-confirm="Bạn có chắc chắn muốn xóa các mục đã chọn?">Xóa</option>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>Áp dụng</button>
                    </div>

                    <!-- PHẢI: Bộ lọc, Tìm kiếm & Nút Thêm mới -->
                    <form action="<?= route('admin.module.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        
                        <!-- Lọc theo Trạng thái -->
                        <select name="hien_thi" class="form-select form-select-sm w-auto">
                            <option value="">Tất cả trạng thái</option>
                            <option value="1" <?= ($hien_thi ?? '') === '1' ? 'selected' : '' ?>>Hiển thị</option>
                            <option value="0" <?= ($hien_thi ?? '') === '0' ? 'selected' : '' ?>>Đã ẩn</option>
                        </select>

                        <!-- Ô tìm kiếm -->
                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                        
                        <!-- Nút Xóa lọc -->
                        <?php if (!empty($keyword) || $hien_thi !== ''): ?>
                            <a href="<?= route('admin.module.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>

                        <!-- Nút Thêm mới -->
                        <a href="<?= route('admin.module.create') ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Thêm mới
                        </a>
                    </form>
                </div>
            </div>
            <!-- /HEADER -->

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">
                                    <div class="form-check d-flex justify-content-center mb-0">
                                        <input class="form-check-input check-all" type="checkbox" title="Chọn tất cả">
                                    </div>
                                </th>
                                <th style="width: 60px;" class="text-center">ID</th>
                                <th>Tên</th>
                                <th style="width: 120px;" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $item): ?>
                                <tr class="wp-row">
                                    <th scope="row" class="text-center align-middle">
                                        <div class="form-check d-flex justify-content-center mb-0">
                                            <input class="form-check-input row-check" type="checkbox" value="<?= $item->id ?>">
                                        </div>
                                    </th>
                                    <td class="text-center text-muted fw-bold align-middle"><?= $item->id ?></td>
                                    <td class="align-middle">
                                        <strong><a href="<?= route('admin.module.edit', ['id' => $item->id]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->ten) ?></a></strong>
                                        
                                        <!-- WP-Style Row Actions -->
                                        <?php 
                                        $actions = [
                                            'edit' => [
                                                'label' => 'Chỉnh sửa', 
                                                'url' => route('admin.module.edit', ['id' => $item->id]), 
                                                'class' => 'text-primary'
                                            ],
                                            'delete' => [
                                                'label' => 'Xóa', 
                                                'url' => route('admin.module.destroy', ['id' => $item->id]), 
                                                'class' => 'text-danger btn-delete',
                                                'attributes' => 'onclick="return confirm(\'Bạn có chắc muốn xóa?\')"'
                                            ]
                                        ];
                                        echo view('admin.components.row_actions', ['actions' => $actions]);
                                        ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <!-- Cột Toggle Status bằng AJAX -->
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input ajax-toggle-status" type="checkbox" 
                                                data-id="<?= $item->id ?>" data-field="status" 
                                                data-url="<?= route('admin.module.updateStatusAjax') ?>" 
                                                <?= $item->hien_thi ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa-regular fa-file-lines fs-1 mb-2"></i><br>
                                        Chưa có dữ liệu nào.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FOOTER: PHÂN TRANG -->
            <div class="card-footer bg-white clearfix py-3">
                <div class="row align-items-center">
                    <div class="col-md-4 text-muted small">
                        Hiển thị <?= count($items ?? []) ?> / <?= $items->total() ?? 0 ?> mục
                    </div>
                    <div class="col-md-8 text-end pagination-right-sm">
                        <?= $items->links() ?? '' ?>
                    </div>
                </div>
            </div>
            <!-- /FOOTER -->

        </div>
    </div>
</div>
```
```

---

## 2. 📝 CẤU TRÚC TRANG FORM (Create/Edit Page)

Mọi trang thêm/sửa phải theo bố cục **2 cột: 9-3 (col-md-9 + col-md-3)**

### 2.1 Bố cục cơ bản (Module không đa ngôn ngữ)

```html
<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $action ?>" method="POST">

            <div class="row">
                <!-- CỘT TRÁI: Nội dung chính -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Thông tin chính</h5>
                        </div>
                        <div class="card-body">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Tên <span class="text-danger">*</span></label>
                                <input type="text" name="ten" class="form-control form-control-sm"
                                    value="<?= htmlspecialchars($item->ten ?? '') ?>" required>
                            </div>

                            <!-- Sử dụng Component CKEditor -->
                            <?= view('admin.components.ckeditor', [
                                'name' => 'mo_ta',
                                'value' => $item->mo_ta ?? '',
                                'label' => 'Mô tả'
                            ]) ?>

                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: Cấu hình & Hành động -->
                <div class="col-md-3">
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập</h5>
                        </div>
                        <div class="card-body bg-light">

                            <div class="form-check form-switch mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" name="hien_thi" id="hien_thi" <?= (!isset($item) || !empty($item->hien_thi)) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold" for="hien_thi">Cho phép hiển thị</label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Số thứ tự</label>
                                <input type="number" name="so_thu_tu" class="form-control form-control-sm"
                                    value="<?= $item->so_thu_tu ?? 0 ?>">
                                <div class="form-text">Số nhỏ hiển thị trước.</div>
                            </div>

                            <!-- Sử dụng Component Image Upload -->
                            <?= view('admin.components.image_upload', [
                                'name' => 'hinh_anh',
                                'value' => $item->hinh_anh ?? '',
                                'label' => 'Hình đại diện'
                            ]) ?>

                        </div>
                        <div class="card-footer d-flex justify-content-end gap-1 flex-wrap">
                            <a href="<?= route('admin.module.index') ?>" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-arrow-left"></i> Quay lại
                            </a>
                            <button type="submit" name="save_action" value="exit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-save"></i> Lưu
                            </button>
                            <button type="submit" name="save_action" value="continue" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-pen-to-square"></i> Lưu và sửa
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>
```

### 2.2 Bố cục đa ngôn ngữ (Module có dữ liệu theo ngôn ngữ)

Thêm tab ngôn ngữ vào cột trái:

```html
<div class="card card-outline card-primary mb-4">
    <!-- Tab ngôn ngữ -->
    <div class="card-header p-0 pt-1 border-bottom-0 bg-white">
        <ul class="nav nav-tabs" id="langTabs" role="tablist">
            <?php $i = 0; foreach($langs as $lang): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $i === 0 ? 'active fw-bold' : '' ?>"
                    data-bs-toggle="tab" data-bs-target="#content-<?= $lang['code'] ?>"
                    type="button" role="tab">
                    <i class="fa-solid fa-language text-primary"></i> <?= htmlspecialchars($lang['name']) ?>
                </button>
            </li>
            <?php $i++; endforeach; ?>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <?php $i = 0; foreach($langs as $lang): ?>
            <?php $c = $lang['code']; ?>
            <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                 id="content-<?= $c ?>" role="tabpanel">

                <div class="mb-3">
                    <label class="form-label fw-bold">Tên (<?= strtoupper($c) ?>) <span class="text-danger">*</span></label>
                    <input type="text" name="ten[<?= $c ?>]" class="form-control form-control-sm"
                        value="<?= htmlspecialchars($item['ten'][$c] ?? '') ?>"
                        <?= $i === 0 ? 'required' : '' ?>>
                </div>

                <div class="mb-3">
                    <label class="form-label">Slug / Alias</label>
                    <input type="text" name="alias[<?= $c ?>]" class="form-control form-control-sm text-muted"
                        placeholder="tu-dong-tao-neu-de-trong"
                        value="<?= htmlspecialchars($item['alias'][$c] ?? '') ?>">
                </div>

            </div>
            <?php $i++; endforeach; ?>
        </div>
    </div>
</div>
```

---

## 3. 🧩 DANH SÁCH COMPONENT CHUẨN

Các component chuẩn dưới đây giúp thống nhất hóa các phần giao diện lặp đi lặp lại. Tất cả form thêm mới hoặc chỉnh sửa bắt buộc sử dụng các component này thay vì viết code HTML thô.

### 3.1 Nút lưu Form (Save Buttons)
Render ra block các nút ở chân card chứa form (Quay lại, Lưu, Lưu và sửa).
```php
<?= view('admin.components.save_buttons', [
    'back_url' => route('admin.module.index')
]) ?>
```
**Các tham số chính:**
- `back_url` (string, bắt buộc): Đường dẫn quay lại danh sách.
- `action_name` (string, tùy chọn, mặc định: `'save_action'`): Tên của thẻ input submit.
- `exit_value` (string, tùy chọn, mặc định: `'exit'`): Giá trị khi nhấn nút Lưu.
- `continue_value` (string, tùy chọn, mặc định: `'continue'`): Giá trị khi nhấn nút Lưu và sửa.
- `buttons` (array, tùy chọn): Dùng để truyền danh sách nút tùy biến nếu không muốn dùng 3 nút mặc định.

### 3.2 Nhập dữ liệu tổng quát (Input)
Tự động tích hợp cơ chế giữ lại dữ liệu cũ khi validate lỗi (old value) và hiển thị thông báo lỗi tương ứng.
```php
<?= view('admin.components.input', [
    'type' => 'text',
    'name' => 'title',
    'value' => $item['title'] ?? '',
    'label' => 'Tiêu đề'
]) ?>
```
**Các tham số chính:**
- `type` (string, mặc định: `'text'`): Loại input (text, number, email, color...).
- `name` (string, bắt buộc): Tên input.
- `value` (string, bắt buộc): Giá trị ban đầu.
- `label` (string, tùy chọn): Nhãn hiển thị bên trên.
- `help_text` (string, tùy chọn): Chú thích hiển thị mờ bên dưới.
- `attrs` (array, tùy chọn): Các thuộc tính bổ sung như `['required' => true, 'placeholder' => '...']`.

### 3.3 Công tắc Bật/Tắt (Switch)
Công tắc toggle trạng thái đẹp mắt dựa trên Bootstrap Switch.
```php
<?= view('admin.components.switch', [
    'name' => 'is_active',
    'checked' => !isset($item) || !empty($item['is_active']),
    'label' => 'Cho phép hiển thị'
]) ?>
```

### 3.4 Soạn thảo văn bản (CKEditor)
Tích hợp trình soạn thảo văn bản CKEditor có sẵn.
```php
<?= view('admin.components.ckeditor', [
    'name' => 'content',
    'value' => $item['content'] ?? '',
    'label' => 'Nội dung chi tiết'
]) ?>
```

### 3.5 Tải ảnh (Image Upload)
Giao diện chọn file ảnh và hiển thị ảnh xem trước (preview) tích hợp sẵn CKFinder.
```php
<?= view('admin.components.image_upload', [
    'name' => 'image',
    'value' => $item['image'] ?? '',
    'label' => 'Hình đại diện'
]) ?>
```

### 3.6 Ngày giờ (Datetime)
Trường chọn ngày giờ chuẩn dạng `datetime-local` thân thiện.
```php
<?= view('admin.components.datetime', [
    'name' => 'created_at',
    'value' => $item['created_at'] ?? date('Y-m-d H:i:s'),
    'label' => 'Ngày tạo'
]) ?>
```

### 3.7 Cấu hình SEO đa ngôn ngữ (SEO Tabs)
Tự động render form điền Title, Description, Keywords, Tags, Noindex, Nofollow và khối xem trước kết quả tìm kiếm trên Google (Google Search Snippet Preview).
```php
<?= view('admin.components.seo', [
    'c' => $langCode,
    'item' => $item ?? []
]) ?>
```

---

## 4. 🔘 QUY TẮC BUTTON & INPUT

### 4.1 Kích thước (Size)

| Vị trí sử dụng | Class Bootstrap |
|---|---|
| Tất cả input/select trong form | `form-control-sm`, `form-select-sm` |
| Nút bên trong bảng (Thao tác) | `btn btn-sm` |
| Nút trong toolbar card header | `btn btn-sm` |
| Nút Submit chính trong form (card-footer) | `btn btn-sm` |
| Nút trong Breadcrumb header trang | `btn btn-sm` |
| Phân trang | Dùng `->links()` của Laravel/Paginator (nếu có) hoặc tự style nhỏ gọn |

> ⚠️ **KHÔNG dùng** `btn` to mặc định (không có `-sm`) ở bất cứ đâu trong admin trừ khi có lý do đặc biệt.

### 4.2 Màu sắc Button theo ngữ nghĩa

| Hành động | Class | Icon |
|---|---|---|
| Thêm mới | `btn-primary` | `fa-plus` |
| Lưu / Cập nhật | `btn-primary` | `fa-save` |
| Chỉnh sửa (trong bảng) | `btn-outline-info` | `fa-pen-to-square` |
| Xóa (trong bảng) | `btn-outline-danger` | `fa-trash` |
| Xóa hàng loạt | `btn-danger` | `fa-trash` |
| Trở về / Hủy | `btn-light border` | `fa-arrow-left` |
| Tìm kiếm / Lọc | `btn-primary` | `fa-search` |
| Xóa bộ lọc | `btn-outline-secondary` | `fa-xmark` |
| Quét / Đồng bộ | `btn-warning` | `fa-rotate` |
| Xuất dữ liệu | `btn-success` | `fa-file-excel` |

---

## 5. 🃏 QUY TẮC CARD

### 5.1 Bảng dữ liệu (Index)

```html
<div class="card card-outline card-primary">
```
*(Viền trên màu xanh primary)*

### 5.2 Form thêm/sửa - Cột nội dung chính

```html
<div class="card card-outline card-primary mb-4">
```

### 5.3 Form thêm/sửa - Cột thiết lập phải (Sidebar)

```html
<div class="card card-outline card-secondary mb-4">
```
*(Viền màu xám)*

### 5.4 Bộ lọc (Filter)

```html
<div class="card card-outline card-secondary mb-3">
```

---

## 6. 📊 QUY TẮC BẢNG DỮ LIỆU (Table)

```html
<table class="table table-bordered table-hover table-striped align-middle mb-0">
```

**Quy tắc cột:**

| Cột | Căn lề | Ghi chú |
|---|---|---|
| Checkbox | `text-center` | Width: 40px |
| ID | `text-center` | Width: 60px, `text-muted fw-bold` |
| Hình ảnh (nếu có) | `text-center` | Width: 100px, `img-thumbnail` h45px |
| Tên / Tiêu đề chính | Trái | Dùng `<strong>`, thẻ `<tr>` phải có class `.wp-row`, và chèn component `row_actions` ở dưới tiêu đề này |
| Số thứ tự | `text-center` | |
| Trạng thái | `text-center` | Dùng `<badge>` |

**Trạng thái hiển thị (Badge):**

```html
<!-- Hiển thị -->
<span class="badge bg-success"><i class="fa-solid fa-check"></i> Hiển thị</span>

<!-- Đã ẩn -->
<span class="badge bg-secondary"><i class="fa-solid fa-eye-slash"></i> Đã ẩn</span>
```

**Hàng trống:**

```html
<tr>
    <td colspan="N" class="text-center py-5 text-muted">
        <i class="fa-solid fa-inbox fs-1 d-block mb-2"></i>
        Chưa có dữ liệu nào.
    </td>
</tr>
```

---

## 7. 🔔 THÔNG BÁO (Alerts & Toast)

### 7.1 Flash Message (Session - sau khi redirect)

Luôn đặt ở đầu nội dung, trước card bộ lọc:

```php
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fa-solid fa-check-circle"></i>
        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
```

### 7.2 Toast AJAX (Lưu ngầm không reload trang)

```html
<!-- Đặt cuối file, trước </div> đóng cuối -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
    <div id="saveToast" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa-solid fa-check-circle me-2"></i> <span id="toastMessage">Đã lưu thành công!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
```

---

## 8. 📄 PHÂN TRANG (Pagination)

Luôn đặt trong `card-footer` và dùng style `bg-white clearfix py-3`. Sử dụng hàm `->links()` thay vì tự viết vòng lặp:

```php
<div class="card-footer bg-white clearfix py-3">
    <div class="row align-items-center">
        <div class="col-md-4 text-muted small">
            Hiển thị <?= count($items ?? []) ?> / <?= $items->total() ?? 0 ?> mục
        </div>
        <div class="col-md-8 text-end pagination-right-sm">
            <?= $items->links() ?? '' ?>
        </div>
    </div>
</div>
```

---

## 9. 🗑️ XÁC NHẬN XÓA (Delete Confirmation)

### 9.1 Xóa đơn (inline)

```html
<a href="..." class="btn btn-sm btn-outline-danger"
   onclick="return confirm('Bạn có chắc muốn xóa mục này không?')">
    <i class="fa-solid fa-trash"></i>
</a>
```

### 9.2 Xóa phức tạp (nên dùng modal)

Dùng Bootstrap Modal thay cho `confirm()` khi xóa có ảnh hưởng dây chuyền (xóa danh mục kéo theo bài viết con...).

---

## 10. ✅ CHECKLIST KHI LÀM MODULE MỚI

Trước khi hoàn thành một module, hãy kiểm tra lại:

- [ ] Trang Index có dùng **wp-toolbar** bao gồm Bulk Action (trái) và Filter/Search (phải) không?
- [ ] Trang Index có cột Trạng thái dạng **Ajax Toggle Switch** không?
- [ ] Form thêm/sửa đã dùng toàn bộ Component chuẩn (`input`, `switch`, `ckeditor`, `image_upload`, `datetime`, `seo`) thay cho thẻ HTML chay chưa?
- [ ] Footer của Form đã dùng Component `save_buttons` (truyền biến `back_url`) chưa?
- [ ] Nút thao tác trong bảng có nằm ngay dưới Tên/Tiêu đề qua component `row_actions` không?
- [ ] File giao diện đã bỏ thẻ form độc lập ở khung lọc cũ và gộp chung vào form trên header chưa?

---

## 11. 🛡️ QUẢN LÝ QUYỀN TRUY CẬP (RBAC) TRÊN GIAO DIỆN

Tất cả các nút chức năng (Thêm, Sửa, Xóa) phải được kiểm tra quyền hiển thị bằng hàm `hasPermission()`:

```php
<?php if (hasPermission('admin.module', 'can_add')): ?>
    <a href="..." class="btn btn-primary">Thêm mới</a>
<?php endif; ?>
```
- Các action được hỗ trợ: `can_view`, `can_add`, `can_edit`, `can_delete`.
- Đối với các nút thao tác bên trong bảng (`row_actions`), hãy bọc bằng khối IF tương ứng trước khi đưa vào mảng `$actions`.
