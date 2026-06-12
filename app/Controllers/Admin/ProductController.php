<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Validator;
use CategoryModel;
use App\Models\ProductModel;
use App\Services\ProductService;

class ProductController extends BaseAdminController {
    
    private ProductService $productService;
    private array $langs;
    private string $primaryLang;
    private int $moduleId;

    public function __construct() {
        parent::__construct();
        $this->productService = new ProductService();
        $this->langs       = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        $this->primaryLang = config('locale', 'vi');
        $this->moduleId    = config('modules.product', 4);
    }

    /**
     * Hiển thị danh sách sản phẩm
     */
    public function index(Request $request) {
        $keyword    = trim($request->input('keyword', ''));
        $status     = $request->input('status', '');
        $categoryId = (int)$request->input('category_id', 0);
        $page       = max(1, (int)$request->input('page', 1));

        $query = ProductModel::query()->where('lang', $this->primaryLang);

        if ($status !== '')      $query->where('status', $status);
        if ($categoryId > 0)     $query->where('category_id', $categoryId);
        if ($keyword !== '')     $query->whereLike('title', $keyword);

        $items = $query->with('variants')
                       ->orderBy('updated_at', 'DESC')
                       ->orderBy('id', 'DESC')
                       ->paginate(10);

        $categories = $this->getCategories();

        return $this->render('admin.product.index', compact('items', 'keyword', 'status', 'categoryId', 'categories'));
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        $langs      = $this->langs;
        $categories = $this->getCategories();
        $primaryLang = $this->primaryLang;
        $attributes = \App\Models\AttributeModel::query()
            ->where('lang', $primaryLang)
            ->with([
                'values' => function($q) use ($primaryLang) {
                    $q->where('lang', $primaryLang);
                }
            ])->get();
        
        return $this->render('admin.product.form', compact('langs', 'categories', 'attributes'));
    }

    /**
     * Mở form chỉnh sửa
     */
    public function edit(Request $request, $id) {
        $id = $this->parseId($id);
        
        $item = $this->productService->getProductForEdit($id);
        
        if (!$item) {
            return $this->redirect(route('admin.product.index'));
        }
        
        $langs      = $this->langs;
        $categories = $this->getCategories();
        $primaryLang = $this->primaryLang;
        $attributes = \App\Models\AttributeModel::query()
            ->where('lang', $primaryLang)
            ->with([
                'values' => function($q) use ($primaryLang) {
                    $q->where('lang', $primaryLang);
                }
            ])->get();
        
        return $this->render('admin.product.form', compact('langs', 'item', 'categories', 'attributes'));
    }

    /**
     * Lưu dữ liệu thêm mới
     */
    public function store(Request $request) {
        if (!$this->validateProduct($request)) {
            return $this->redirect(route('admin.product.create'));
        }

        $insertedId = $this->productService->saveProduct($request->all(), $this->langs, user()->id);

        if ($insertedId) {
            session('success', 'Thêm sản phẩm thành công!');
        } else {
            session('error', 'Có lỗi xảy ra khi tạo sản phẩm.');
        }
        
        return $this->handleSaveRedirect($request, $insertedId);
    }

    /**
     * Lưu dữ liệu cập nhật
     */
    public function update(Request $request, $id) {
        $id = $this->parseId($id);
        
        if (!$this->validateProduct($request)) {
            return $this->redirect(route('admin.product.edit', ['id' => $id]));
        }

        $this->productService->saveProduct($request->all(), $this->langs, user()->id, $id);
        
        session('success', 'Cập nhật sản phẩm thành công!');
        return $this->handleSaveRedirect($request, $id);
    }

    /**
     * Cập nhật trạng thái hiển thị qua AJAX
     */
    public function updateStatusAjax(Request $request) {
        $id    = (int)$request->input('id');
        $field = $request->input('field', 'status');
        $value = (int)$request->input('value', 0);

        if (!in_array($field, ['status', 'is_featured', 'is_new', 'is_hot', 'is_sale'])) {
            return $this->jsonError('Trường dữ liệu không hợp lệ');
        }

        $query = ProductModel::query();
        $query->use_lang = false;
        $product = clone $query;
        $product = $product->where('id_code', $id)->first();
        if (!$product) {
            return $this->json(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
        }

        $updateVal = ($product->{$field} == 1) ? 0 : 1;
        $query->where('id_code', $id)->update([$field => $updateVal]);
        
        return $this->jsonSuccess('Trạng thái đã được cập nhật!');
    }

    /**
     * Xóa 1 dòng
     */
    public function destroy(Request $request, $id) {
        $id = $this->parseId($id);
        
        if ($this->productService->deleteProduct($id)) {
            session('success', 'Đã xóa sản phẩm thành công!');
        }
        
        return $this->redirect(route('admin.product.index'));
    }

    /**
     * Xóa hàng loạt
     */
    public function destroyMultiple(Request $request) {
        $ids = $request->input('ids', []);
        
        if (!empty($ids) && is_array($ids)) {
            $deletedCount = 0;
            foreach ($ids as $id) {
                if ($this->productService->deleteProduct($id)) {
                    $deletedCount++;
                }
            }
            return $this->json(['success' => true, 'message' => "Đã xóa thành công {$deletedCount} sản phẩm."]);
        }
        return $this->json(['success' => false, 'message' => 'Chưa chọn bản ghi nào']);
    }

    // ============================================================
    //  HELPER METHODS
    // ============================================================

    private function validateProduct(Request $request): bool {
        $validator = new Validator($request->all(), [
            "title.{$this->primaryLang}" => 'required|max:255'
        ], [
            "title.{$this->primaryLang}.required" => 'Tên sản phẩm không được để trống.',
            "title.{$this->primaryLang}.max"      => 'Tên sản phẩm không được vượt quá 255 ký tự.'
        ]);
        
        if ($validator->fails()) {
            session('error', $validator->firstError());
            // Flash data (old/errors) is handled automatically inside Validator::fails()
            return false;
        }

        return true;
    }

    private function getCategories() {
        return CategoryModel::getTreeForAdminByModule($this->moduleId);
    }

    private function parseId($id): int {
        return (int)(is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id);
    }

    private function handleSaveRedirect(Request $request, $id) {
        if ($request->input('save_action') === 'continue') {
            return $this->redirect(route('admin.product.edit', ['id' => $id]));
        }
        return $this->redirect(route('admin.product.index'));
    }
}
