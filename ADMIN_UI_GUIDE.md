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
        // Nút chính (Thêm mới)
        ['label' => 'Thêm mới', 'icon' => 'fa-plus', 'url' => route('admin.module.create'), 'class' => 'btn-primary'],
        // Nút phụ (Scan, Export...)
        ['label' => 'Xuất Excel', 'icon' => 'fa-file-excel', 'url' => '#', 'class' => 'btn-success'],
    ]
]) ?>
```

### 1.2 Phần Bộ lọc & Tìm kiếm (BẮT BUỘC có trên mọi trang danh sách)

```html
<div class="app-content">
    <div class="container-fluid">

        <!-- KHUNG BỘ LỌC - BẮT BUỘC -->
        <div class="card card-outline card-secondary mb-3">
            <div class="card-body py-2">
                <form action="<?= route('admin.module.index') ?>" method="GET" class="row g-2 align-items-end">

                    <!-- Ô tìm kiếm text -->
                    <div class="col-md-4">
                        <label class="form-label form-label-sm mb-1">Tìm kiếm</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="keyword" class="form-control form-control-sm"
                                placeholder="Nhập từ khóa..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                        </div>
                    </div>

                    <!-- Dropdown lọc theo nhóm/loại -->
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Trạng thái</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- Tất cả --</option>
                            <option value="1" <?= ($status ?? '') == '1' ? 'selected' : '' ?>>Hiển thị</option>
                            <option value="0" <?= ($status ?? '') == '0' ? 'selected' : '' ?>>Đã ẩn</option>
                        </select>
                    </div>

                    <!-- Nút hành động của form lọc -->
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-search"></i> Lọc
                        </button>
                        <?php if (!empty($keyword) || !empty($status)): ?>
                            <a href="<?= route('admin.module.index') ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fa-solid fa-xmark"></i> Xóa lọc
                            </a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </div>
        <!-- /KHUNG BỘ LỌC -->

        <!-- BẢNG DỮ LIỆU -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    Danh sách <span class="badge bg-secondary ms-1"><?= $totalRows ?? 0 ?></span>
                </h3>
                <div class="card-tools">
                    <!-- Nút thêm mới nhỏ trong header bảng (tùy chọn) -->
                    <a href="<?= route('admin.module.create') ?>" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> Thêm mới
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Thanh Bulk Action (ẩn mặc định, hiện khi có checkbox được chọn) -->
                <div id="bulkActionPanel" class="px-3 py-2 bg-light border-bottom d-none">
                    <span class="fw-bold me-2"><span id="selectedCount">0</span> mục đã chọn:</span>
                    <button type="button" class="btn btn-danger btn-sm" id="btnBulkDelete">
                        <i class="fa-solid fa-trash"></i> Xóa đã chọn
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="checkAll">
                                </th>
                                <th style="width: 60px;" class="text-center">ID</th>
                                <th>Tên</th>
                                <th style="width: 100px;" class="text-center">Trạng thái</th>
                                <th style="width: 100px;" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input row-check" value="<?= $item->id ?>">
                                    </td>
                                    <td class="text-center text-muted fw-bold"><?= $item->id ?></td>
                                    <td><strong><?= htmlspecialchars($item->ten) ?></strong></td>
                                    <td class="text-center">
                                        <?php if ($item->hien_thi): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-check"></i> Hiển thị</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fa-solid fa-eye-slash"></i> Đã ẩn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= route('admin.module.edit', ['id' => $item->id]) ?>"
                                           class="btn btn-sm btn-outline-info" title="Chỉnh sửa">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="<?= route('admin.module.destroy', ['id' => $item->id]) ?>"
                                           class="btn btn-sm btn-outline-danger" title="Xóa"
                                           onclick="return confirm('Bạn có chắc muốn xóa?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-inbox fs-1 d-block mb-2"></i>
                                        Chưa có dữ liệu nào.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FOOTER: PHÂN TRANG - BẮT BUỘC -->
            <div class="card-footer clearfix">
                <div class="row align-items-center">
                    <div class="col-md-6 text-muted small">
                        Hiển thị <?= count($items ?? []) ?> / <?= $totalRows ?? 0 ?> bản ghi
                    </div>
                    <div class="col-md-6">
                        <?php if (($totalPages ?? 1) > 1): ?>
                        <ul class="pagination pagination-sm m-0 float-end">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword ?? '') ?>">«</a>
                            </li>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword ?? '') ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword ?? '') ?>">»</a>
                            </li>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- /FOOTER -->

        </div>
        <!-- /BẢNG DỮ LIỆU -->
    </div>
</div>
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
                        <div class="card-header">
                            <h3 class="card-title">Thông tin chính</h3>
                        </div>
                        <div class="card-body">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Tên <span class="text-danger">*</span></label>
                                <input type="text" name="ten" class="form-control form-control-sm"
                                    value="<?= htmlspecialchars($item->ten ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea name="mo_ta" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($item->mo_ta ?? '') ?></textarea>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: Cấu hình & Hành động -->
                <div class="col-md-3">
                    <div class="card card-outline card-secondary mb-4 sticky-top" style="top: 70px;">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập</h3>
                        </div>
                        <div class="card-body bg-light">

                            <div class="mb-3">
                                <label class="form-label">Số thứ tự</label>
                                <input type="number" name="so_thu_tu" class="form-control form-control-sm"
                                    value="<?= $item->so_thu_tu ?? 0 ?>">
                                <div class="form-text">Số nhỏ hiển thị trước.</div>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="hien_thi" id="hien_thi"
                                    <?= (!isset($item) || !empty($item->hien_thi)) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="hien_thi">Cho phép hiển thị</label>
                            </div>

                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-save"></i> <?= $isEdit ? 'Lưu cập nhật' : 'Thêm mới' ?>
                                </button>
                                <a href="<?= route('admin.module.index') ?>" class="btn btn-light border btn-sm">
                                    <i class="fa-solid fa-arrow-left"></i> Trở về
                                </a>
                            </div>
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

## 3. 🔘 QUY TẮC BUTTON & INPUT

### 3.1 Kích thước (Size)

| Vị trí sử dụng | Class Bootstrap |
|---|---|
| Tất cả input/select trong form | `form-control-sm`, `form-select-sm` |
| Nút bên trong bảng (Thao tác) | `btn btn-sm` |
| Nút trong header Card (card-tools) | `btn btn-sm` |
| Nút Submit chính trong form (card-footer) | `btn btn-sm` (với `d-grid` để full width) |
| Nút trong Breadcrumb header trang | `btn btn-sm` |
| Phân trang | `pagination pagination-sm` |

> ⚠️ **KHÔNG dùng** `btn` to mặc định (không có `-sm`) ở bất cứ đâu trong admin trừ khi có lý do đặc biệt.

### 3.2 Màu sắc Button theo ngữ nghĩa

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

## 4. 🃏 QUY TẮC CARD

### 4.1 Bảng dữ liệu (Index)

```html
<div class="card card-outline card-primary">
```
*(Viền trên màu xanh primary)*

### 4.2 Form thêm/sửa - Cột nội dung chính

```html
<div class="card card-outline card-primary mb-4">
```

### 4.3 Form thêm/sửa - Cột thiết lập phải (Sidebar)

```html
<div class="card card-outline card-secondary mb-4 sticky-top" style="top: 70px;">
```
*(Viền màu xám, sticky để luôn hiển thị khi cuộn)*

### 4.4 Bộ lọc (Filter)

```html
<div class="card card-outline card-secondary mb-3">
```

---

## 5. 📊 QUY TẮC BẢNG DỮ LIỆU (Table)

```html
<table class="table table-bordered table-hover table-striped align-middle mb-0">
```

**Quy tắc cột:**

| Cột | Căn lề | Ghi chú |
|---|---|---|
| Checkbox | `text-center` | Width: 40px |
| ID | `text-center` | Width: 60px, `text-muted fw-bold` |
| Hình ảnh (nếu có) | `text-center` | Width: 100px, `img-thumbnail` h45px |
| Tên / Tiêu đề chính | Trái | Dùng `<strong>` |
| Số thứ tự | `text-center` | |
| Trạng thái | `text-center` | Dùng `<badge>` |
| Thao tác | `text-center` | Width: 100px, chỉ icon, chỉ `btn-outline-*` |

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

## 6. 🔔 THÔNG BÁO (Alerts & Toast)

### 6.1 Flash Message (Session - sau khi redirect)

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

### 6.2 Toast AJAX (Lưu ngầm không reload trang)

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

## 7. 📄 PHÂN TRANG (Pagination)

Luôn đặt trong `card-footer`:

```php
<div class="card-footer clearfix">
    <div class="row align-items-center">
        <div class="col-md-6 text-muted small">
            Hiển thị <?= count($items) ?> / <?= $totalRows ?> bản ghi
        </div>
        <div class="col-md-6">
            <?php if ($totalPages > 1): ?>
            <ul class="pagination pagination-sm m-0 float-end">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword ?? '') ?>">«</a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword ?? '') ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword ?? '') ?>">»</a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
```

---

## 8. 🗑️ XÁC NHẬN XÓA (Delete Confirmation)

### 8.1 Xóa đơn (inline)

```html
<a href="..." class="btn btn-sm btn-outline-danger"
   onclick="return confirm('Bạn có chắc muốn xóa mục này không?')">
    <i class="fa-solid fa-trash"></i>
</a>
```

### 8.2 Xóa phức tạp (nên dùng modal)

Dùng Bootstrap Modal thay cho `confirm()` khi xóa có ảnh hưởng dây chuyền (xóa danh mục kéo theo bài viết con...).

---

## 9. ✅ CHECKLIST KHI LÀM MODULE MỚI

Trước khi hoàn thành một module, hãy kiểm tra lại:

- [ ] Trang Index có **bộ lọc tìm kiếm** không?
- [ ] Trang Index có **phân trang** (`pagination-sm`) trong `card-footer` không?
- [ ] Trang Index có **hàng trống** (`Chưa có dữ liệu nào`) khi không có bản ghi không?
- [ ] Tất cả input/select trong form đã dùng **`-sm` size** chưa?
- [ ] Button Thao tác trong bảng chỉ dùng **icon** (không có chữ) chưa?
- [ ] Cột phải của form có **`sticky-top`** không?
- [ ] Flash message (`$_SESSION['success']` / `$_SESSION['error']`) đã được hiển thị chưa?
- [ ] Breadcrumb đã hiển thị đúng cây điều hướng chưa?

---

## 10. 🛡️ QUẢN LÝ QUYỀN TRUY CẬP (RBAC) TRÊN GIAO DIỆN

Tất cả các nút chức năng (Thêm, Sửa, Xóa) phải được kiểm tra quyền hiển thị bằng hàm `hasPermission()`:

```php
<?php if (hasPermission('admin.module', 'can_add')): ?>
    <a href="..." class="btn btn-primary">Thêm mới</a>
<?php endif; ?>
```
- Các action được hỗ trợ: `can_view`, `can_add`, `can_edit`, `can_delete`.
- Đối với các nút thao tác bên trong bảng (`row_actions`), hãy bọc bằng khối IF tương ứng trước khi đưa vào mảng `$actions`.
