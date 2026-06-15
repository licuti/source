# Hướng dẫn mở rộng Module Menu

Tài liệu này hướng dẫn các bước để lập trình viên có thể mở rộng tính năng cho Module Menu, bao gồm:
1. Thêm một nguồn dữ liệu mới (Menu Source) để người dùng có thể chọn.
2. Thêm một trường dữ liệu nâng cao mới (Menu Item Field) vào từng mục menu.

---

## 1. Hướng dẫn thêm Nguồn dữ liệu mới (Menu Source)

Giả sử bạn vừa viết xong chức năng **Trang tĩnh (Page)** hoặc **Thương hiệu (Brand)** và muốn Admin có thể chọn nhanh các Page/Brand đó ghép vào Menu. Bạn cần thực hiện 3 bước:

### Bước 1.1: Giao diện (Frontend)
Mở file `resources/views/admin/menu/index.php`. 
Copy một khối html `<div class="source-box source-ajax">...</div>` có sẵn (ví dụ của phần Sản phẩm) và dán xuống dưới.

Sửa lại các thuộc tính: `data-type` thành mã (alias) của nguồn mới (vd: `brand`), và tiêu đề `data-title`.

```html
<!-- THƯƠNG HIỆU -->
<div class="source-box source-ajax" data-type="brand" data-title="Thương hiệu">
    <h3>Thương hiệu</h3>
    <div class="source-content">
        <input type="text" class="source-search" placeholder="Tìm kiếm...">
        <div class="source-items"></div>
        <hr style="border: 0; border-top: 1px solid #c3c4c7; margin: 10px 0;">
        <button type="button" class="btn btn-outline-secondary btn-sm w-100 add-selected">Thêm vào menu</button>
        <div class="select-all-wrap">
            <label><input type="checkbox" class="select-all"> Chọn tất cả</label>
        </div>
    </div>
</div>
```

### Bước 1.2: Backend xử lý dữ liệu (MenuService)
Mở file `app/Services/MenuService.php`.
Tìm đến hàm `searchSourceItems($type, $keyword, $lang)`.
Thêm một `case` mới tương ứng với `data-type` bạn vừa định nghĩa ở Bước 1.1.

```php
case 'brand':
    $query = BrandModel::query(); // Model tương ứng của bạn
    $query->use_lang = false;
    if ($lang !== 'all') $query->where('lang', $lang);
    if ($keyword) $query->whereLike('title', $keyword); // Trường tên của bạn
    
    $items = $query->limit($limit)->get();
    foreach ($items as $item) {
        $results[] = [
            'id' => $item->id,
            'label' => $item->title,
            'url' => $item->slug, // Đường dẫn tĩnh
            'lang' => $item->lang ?? '',
            'type' => 'Thương hiệu', // Tiêu đề hiển thị trên thẻ menu
            'object_type' => 'brand' // Bắt buộc phải giống hệt data-type
        ];
    }
    break;
```

### Bước 1.3: Hook đồng bộ URL tự động (Tùy chọn)
Để mỗi khi người quản trị vào sửa đường dẫn (slug) của Thương hiệu, đường dẫn gắn trên Menu sẽ tự cập nhật theo mà không bị đứt gãy.
Mở Model chứa dữ liệu đó (vd `app/Models/BrandModel.php`), bổ sung hàm `saved()`:

```php
public function saved() {
    if (!empty($this->attributes['slug']) && !empty($this->id)) {
        $menuItemModel = new \App\Models\MenuItemModel();
        $menuItemModel->where('object_type', 'brand') // object_type quy định ở trên
                      ->where('object_id', $this->id)
                      ->update(['url' => $this->attributes['slug']]);
    }
}
```

---

## 2. Hướng dẫn thêm trường dữ liệu nâng cao (Menu Item Field)

Giả sử bạn muốn mỗi mục Menu có thêm cấu hình **"Màu nền" (background_color)** hoặc một tham số bất kì. Quy trình thêm field mới chỉ bao gồm 4 bước:

### Bước 2.1: Thêm Cột vào cơ sở dữ liệu
Chạy truy vấn SQL để tạo trường mới trong bảng `db_menu_items`.
```sql
ALTER TABLE db_menu_items ADD COLUMN background_color VARCHAR(50) DEFAULT NULL;
```

### Bước 2.2: Cập nhật giao diện (Frontend Template)
Mở file `resources/views/admin/menu/index.php`.
Tìm đến `<template id="menu-item-template">`. 
Thêm giao diện cho field mới vào bên trong phần cài đặt nâng cao. Bắt buộc phải gắn thẻ class `.item-input` và thuộc tính `data-name="tên_trường_db"`.

```html
<div class="col-md-6 mb-2">
    <label class="form-label" style="font-size: 12px;">Màu nền</label>
    <input type="color" class="form-control form-control-sm item-input" data-name="background_color">
</div>
```

### Bước 2.3: Đăng ký field để Javascript thu thập
Vẫn ở trong `index.php`, kéo xuống tìm mảng khai báo `MENU_FIELDS`.
Bổ sung tên field của bạn vào mảng này để JS biết tự động đọc/ghi giá trị đó khi tải và lưu Menu.

```javascript
// Thêm 'background_color' vào mảng
const MENU_FIELDS = ['label', 'url', 'class', 'rel', 'style', 'block', 'target', 'image', 'type', 'object_type', 'object_id', 'background_color'];
```

### Bước 2.4: Đăng ký field ở Backend (MenuService)
Mở file `app/Services/MenuService.php`.
Tìm hàm `flattenMenuTree(...)`.
Khai báo trường của bạn vào mảng `$itemData` để hệ thống tự động thu nhận từ Ajax và lưu vào DB.

```php
$itemData = [
    // ... các trường cũ
    'background_color' => $item['background_color'] ?? '', // Thêm dòng này
];
```

Chỉ cần làm đủ 4 bước này, hệ thống sẽ tự động hiển thị, truyền dữ liệu từ form về server và lưu CSDL hoàn chỉnh mà không cần chỉnh sửa gì thêm về mặt code Javascript lõi hay câu truy vấn SQL.
