---
name: admin-module-blueprint
description: >
  Blueprint chuẩn để tạo hoặc sửa một Admin CRUD Controller trong dự án này.
  Kích hoạt khi: tạo module admin mới, refactor controller cũ, thêm chức năng CRUD.
---

# Admin Module Blueprint

`CategoryController` là **Golden Template** cho tất cả các Admin CRUD Controller.
File: `app/Controllers/Admin/CategoryController.php`

---

## Cấu trúc bắt buộc của một Admin Controller

```
class XxxController extends BaseAdminController {
    // 1. index()       — Danh sách + Tìm kiếm + Phân trang
    // 2. create()      — Mở form tạo mới
    // 3. store()       — Lưu dữ liệu mới (POST)
    // 4. edit()        — Mở form chỉnh sửa
    // 5. update()      — Lưu cập nhật (POST/PUT)
    // 6. destroy()     — Xóa 1 dòng
    // 7. destroyMultiple() — Xóa hàng loạt (AJAX)
    // 8. updateStatusAjax() — Bật/tắt trạng thái (AJAX)
    // --- Private Helpers ---
    // 9.  getFormData()       — Tập hợp data cho form (langs, modules, item...)
    // 10. extractModelData()  — Build array data cho bảng chính (DRY)
    // 11. extractTranslationData() — Build array data cho bảng dịch (DRY)
}
```

---

## 1. index() — Danh sách

```php
public function index(Request $request) {
    $keyword = trim($request->input('keyword', ''));
    $status  = $request->input('status', '');
    $page    = max(1, (int)$request->input('page', 1));
    $limit   = 10;

    if ($keyword !== '' || $status !== '') {
        // SEARCH: Dùng JOIN để đẩy logic lọc xuống SQL
        $query = XxxModel::query()
            ->select('#_xxx.*')
            ->join('#_xxx_translations as t', 't.xxx_id', '=', '#_xxx.id')
            ->where('t.lang', $this->primaryLang);

        if ($keyword !== '') {
            $query->whereRaw("t.title LIKE ?", ["%$keyword%"]);
        }
        if ($status !== '') {
            $query->where('#_xxx.status', $status);
        }

        // Dùng with() để eager load — tránh N+1
        $items = $query->with('translations')
                       ->orderBy('id', 'DESC')
                       ->paginate($limit, $page);
        $isSearch   = true;
        $totalRows  = $items->total();
        $totalPages = $items->lastPage();
    } else {
        // DEFAULT: Lấy toàn bộ (Model đã eager load translations nếu cần)
        $items      = XxxModel::query()->with('translations')->orderBy('id', 'DESC')->paginate($limit, $page);
        $totalRows  = $items->total();
        $totalPages = $items->lastPage();
        $isSearch   = false;
    }

    return $this->render('admin.xxx.index', compact(
        'items', 'isSearch', 'keyword', 'status', 'page', 'totalPages', 'totalRows', 'limit'
    ));
}
```

---

## 2. store() — Luôn dùng DB::transaction()

```php
use App\Core\Database\DB;

public function store(XxxRequest $request) {
    $data = $request->validated();
    $lang = $data['lang'] ?? $this->primaryLang;

    $id = DB::transaction(function () use ($data, $lang) {
        $id = XxxModel::insertGetId($this->extractModelData($data));
        XxxTranslationModel::updateOrCreate(
            ['xxx_id' => $id, 'lang' => $lang],
            $this->extractTranslationData($data, $id, $lang)
        );
        return $id;
    });

    if (($data['save_action'] ?? '') === 'continue') {
        return $this->redirect(route('admin.xxx.edit', ['id' => $id]));
    }
    return $this->redirect(route('admin.xxx.index'));
}
```

---

## 3. update() — Kiểm tra tồn tại trước, rồi Transaction

```php
public function update(XxxRequest $request, $id) {
    $id   = (int)$id;
    $data = $request->validated();
    $lang = $data['lang'] ?? $this->primaryLang;

    if (!XxxModel::find($id)) {
        return $this->redirect(route('admin.xxx.index'));
    }

    DB::transaction(function () use ($data, $id, $lang) {
        XxxModel::where('id', $id)->update($this->extractModelData($data));
        XxxTranslationModel::updateOrCreate(
            ['xxx_id' => $id, 'lang' => $lang],
            $this->extractTranslationData($data, $id, $lang)
        );
    });

    if (($data['save_action'] ?? '') === 'continue') {
        return $this->redirect(route('admin.xxx.edit', ['id' => $id]));
    }
    return $this->redirect(route('admin.xxx.index'));
}
```

---

## 4. edit() — Điền thông tin dịch thuật và tránh đè ID

```php
public function edit(Request $request, $id) {
    $itemObj = XxxModel::query()->with('translations')->find((int)$id);
    if (!$itemObj) return $this->redirect(route('admin.xxx.index'));

    $item = $itemObj->toArray();
    $langCode = $request->input('lang', $this->primaryLang);
    $translation = $itemObj->getTranslation($langCode);

    if ($translation) {
        $translationData = $translation->toArray();
        unset($translationData['id']); // BẮT BUỘC: Ngăn đè ID của model bằng ID của bản dịch
        $item = array_merge($item, $translationData);
    } else {
        foreach ($itemObj->getTranslatedAttributesArray() as $k => $v) {
            $item[$k] = '';
        }
    }

    // Build map ['vi' => id, 'en' => id] để polylang widget hoạt động chính xác
    $translationsMap = [];
    foreach ($itemObj->translations ?? [] as $t) {
        $translationsMap[$t->lang] = $t->id;
    }

    return $this->render('admin.xxx.form', $this->getFormData($request, $item, $translationsMap));
}
```

---

## 5. destroy() & destroyMultiple() — Transaction bắt buộc

```php
public function destroy(Request $request, $id) {
    $id = (int)$id;
    DB::transaction(function () use ($id) {
        XxxTranslationModel::where('xxx_id', $id)->delete();
        XxxModel::where('id', $id)->delete();
    });
    return $this->redirect(route('admin.xxx.index'));
}

public function destroyMultiple(Request $request) {
    $ids = $request->input('ids', []);
    if (is_string($ids)) $ids = explode(',', $ids);
    if (empty($ids) || !is_array($ids)) {
        return $this->json(['success' => false, 'message' => 'Chưa chọn bản ghi nào']);
    }
    try {
        DB::transaction(function () use ($ids) {
            XxxTranslationModel::whereIn('xxx_id', $ids)->delete();
            XxxModel::whereIn('id', $ids)->delete();
        });
        return $this->json(['success' => true]);
    } catch (\Exception $e) {
        return $this->json(['success' => false, 'message' => 'Lỗi khi xóa']);
    }
}
```

---

## 5. Private Helper Methods — DRY

```php
// Data cho bảng chính
private function extractModelData(array $data): array {
    return [
        'image'      => $data['image'] ?? '',
        'status'     => isset($data['status']) ? 1 : 0,
        'sort_order' => (int)($data['sort_order'] ?? 0),
        // ... các field của bảng chính
    ];
}

// Data cho bảng dịch
private function extractTranslationData(array $data, int $id, string $lang): array {
    return [
        'xxx_id'          => $id,
        'lang'            => $lang,
        'title'           => $data['title'] ?? '',
        'slug'            => empty($data['slug']) ? str_slug($data['title'] ?? '') : $data['slug'],
        'description'     => $data['description'] ?? '',
        'seo_title'       => $data['seo_title'] ?? '',
        'seo_description' => $data['seo_description'] ?? '',
    ];
}

// Data cho form (gọi từ create() và edit())
private function getFormData(Request $request, $item = []) {
    $langCode        = $request->input('lang', $this->primaryLang);
    $currentLangName = $this->getLangName($langCode); // Helper từ BaseAdminController
    $modules         = $this->getActiveModules();      // Helper từ BaseAdminController

    return compact('item', 'langCode', 'currentLangName', 'modules');
}
```

---

## 6. Checklist trước khi viết một module mới

- [ ] Controller extend `BaseAdminController`
- [ ] Có FormRequest riêng ở `app/Requests/Admin/`
- [ ] `store()` và `update()` dùng `DB::transaction()`
- [ ] `destroy()` và `destroyMultiple()` dùng `DB::transaction()`
- [ ] `index()` dùng `->with('translations')` để tránh N+1
- [ ] Có 2 helper: `extractModelData()` và `extractTranslationData()`
- [ ] Logic dùng chung nằm trong `BaseAdminController`, không copy-paste
- [ ] Không import `CategoryModel::getConnection()` hay dùng `$pdo` trực tiếp
