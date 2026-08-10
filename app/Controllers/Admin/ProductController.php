<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Auth\AuthManager;
use App\Models\AttributeModel;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Services\ProductService;
use App\Requests\Admin\ProductRequest;

class ProductController extends BaseAdminController {
    
    private ProductService $productService;
    private int $moduleId;
    protected string $routePrefix = 'admin.product';
    protected int $perPage = 10;
    protected array $allowedToggleFields = ['status', 'is_featured', 'is_new', 'is_hot', 'is_sale'];

    public function __construct() {
        parent::__construct();
        $this->productService = new ProductService();
        $this->moduleId    = config('modules.product', 4);
    }

    /**
     * Hiển thị danh sách sản phẩm
     */
    public function index(Request $request) {
        $keyword    = trim($request->input('keyword', ''));
        $status     = $request->input('status', '');
        $categoryId = (int)$request->input('category_id', 0);
        $stockFilter = $request->input('stock_filter', 'all');

        $query = ProductModel::where('lang', $this->primaryLang)->filter($request->all());

        $items = $query->with('variants')
                       ->orderBy('updated_at', 'DESC')
                       ->orderBy('id', 'DESC')
                       ->paginate($this->perPage);

        $categories = $this->getCategories();

        return $this->render('admin.product.index', compact('items', 'keyword', 'status', 'categoryId', 'stockFilter', 'categories'));
    }

    private function getFormData($item = null) {
        $primaryLang = $this->primaryLang;
        $attributes  = AttributeModel::where('lang', $primaryLang)
            ->with(['values' => fn($q) => $q->where('lang', $primaryLang)])
            ->get();

        return compact('attributes', 'item') + [
            'langs'      => $this->langs,
            'categories' => $this->getCategories(),
        ];
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        return $this->render('admin.product.form', $this->getFormData());
    }

    /**
     * Mở form chỉnh sửa
     */
    public function edit(Request $request, $id) {
        $id = $this->parseId($id);
        $item = $this->productService->getProductForEdit($id);
        if (!$item) {
            return $this->redirect(route($this->routePrefix . '.index'));
        }
        
        return $this->render('admin.product.form', $this->getFormData($item));
    }

    public function store(ProductRequest $request) {
        $validatedData = $request->validated();
        $insertedId = $this->productService->saveProduct($validatedData, $this->langs, AuthManager::user()->id);

        if ($insertedId) {
            session('success', 'Thêm sản phẩm thành công!');
        } else {
            session('error', 'Có lỗi xảy ra khi tạo sản phẩm.');
        }
        
        return $this->handleSaveRedirect($request, $insertedId);
    }

    public function update(ProductRequest $request, $id) {
        $id = $this->parseId($id);
        $validatedData = $request->validated();

        $this->productService->saveProduct($validatedData, $this->langs, AuthManager::user()->id, $id);
        
        session('success', 'Cập nhật sản phẩm thành công!');
        return $this->handleSaveRedirect($request, $id);
    }

    public function updateStatusAjax(Request $request) {
        try {
            $id    = (int)$request->input('id');
            $field = $request->input('field', 'status');

            if (!in_array($field, $this->allowedToggleFields)) {
                return $this->jsonError('Trường dữ liệu không hợp lệ');
            }

            if ($this->productService->updateProductStatus($id, $field)) {
                return $this->jsonSuccess('Trạng thái đã được cập nhật!');
            }
            
            return $this->jsonError('Không tìm thấy sản phẩm');
        } catch (\Exception $e) {
            return $this->jsonError('Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    /**
     * Xóa 1 dòng
     */
    public function destroy(Request $request, $id) {
        $id = $this->parseId($id);
        
        if ($this->productService->deleteProduct($id)) {
            session('success', 'Đã xóa sản phẩm thành công!');
        }
        
        return $this->redirect(route($this->routePrefix . '.index'));
    }

    /**
     * Xóa hàng loạt
     */
    public function destroyMultiple(Request $request) {
        try {
            $ids = $request->input('ids', []);
            
            if (!empty($ids) && is_array($ids)) {
                // Đảm bảo chỉ xử lý các số nguyên dương hợp lệ
                $cleanIds = array_filter(array_map('intval', $ids), fn($id) => $id > 0);
                
                if (empty($cleanIds)) {
                    return $this->jsonError('Danh sách ID không hợp lệ');
                }
                
                $deletedCount = count($cleanIds);
                if ($this->productService->deleteProducts($cleanIds)) {
                    return $this->jsonSuccess("Đã xóa thành công {$deletedCount} sản phẩm.");
                }
            }
            return $this->jsonError('Lỗi xóa bản ghi hoặc chưa chọn bản ghi');
        } catch (\Exception $e) {
            return $this->jsonError('Lỗi hệ thống: ' . $e->getMessage());
        }
    }


    private function getCategories() {
        return CategoryModel::getTreeForAdminByModule($this->moduleId);
    }

    private function parseId($id): int {
        return (int)(is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id);
    }

    private function handleSaveRedirect(Request $request, $id) {
        if ($request->input('save_action') === 'continue') {
            return $this->redirect(route($this->routePrefix . '.edit', ['id' => $id]));
        }
        return $this->redirect(route($this->routePrefix . '.index'));
    }
}
