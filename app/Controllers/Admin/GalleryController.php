<?php
namespace App\Controllers\Admin;

use App\Models\GalleryModel;
use App\Models\CategoryModel;
use App\Services\GalleryService;
use App\Core\Request;

class GalleryController extends BaseAdminController {
    
    private GalleryService $galleryService;
    private array $langs;
    private string $primaryLang;
    
    public function __construct() {
        parent::__construct();
        $this->galleryService = new GalleryService();
        $this->langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        $this->primaryLang = config('locale', 'vi');
    }
    
    public function index(Request $request) {
        $keyword = $request->get('keyword');
        $currentLang = $request->get('lang', $this->primaryLang);
        $categoryId = (int)$request->get('category_id', 0);
        
        $query = GalleryModel::adminQuery()
            ->where('lang', $currentLang)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc');
        
        if (!empty($keyword)) {
            $query->whereLike('title', $keyword);
        }
        
        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }
        
        $albums = $query->paginate(20);
        
        // Decode gallery JSON for thumbnail preview
        foreach ($albums as $album) {
            $decodedGallery = json_decode($album->gallery, true) ?? [];
            if (is_array($decodedGallery) && (isset($decodedGallery['vi']) || isset($decodedGallery['en']))) {
                $album->gallery = $decodedGallery[$currentLang] ?? $decodedGallery['vi'] ?? [];
            } else {
                $album->gallery = $decodedGallery;
            }
        }

        // Fetch translations for all albums in this page
        $idCodes = array_map(function($a) { return $a->id_code; }, $albums->items());
        $translations = [];
        if (!empty($idCodes)) {
            $allTrans = GalleryModel::adminQuery()
                ->whereIn('id_code', $idCodes)
                ->get();
            foreach ($allTrans as $t) {
                $translations[$t->id_code][$t->lang] = $t->id;
            }
        }
        
        $langs = $this->langs;
        $categories = $this->getCategories();
        return view('admin.gallery.index', compact('albums', 'keyword', 'currentLang', 'langs', 'translations', 'categories', 'categoryId'));
    }
    
    public function create(Request $request) {
        return $this->getFormData($request);
    }
    
    private function getCategories() {
        return CategoryModel::where('status', 1)
            ->where('module', config('modules.album', 15))
            ->orderBy('sort_order', 'asc')
            ->get();
    }
    
    public function edit(Request $request, $id) {
        return $this->getFormData($request, $id);
    }
    
    private function getFormData(Request $request, $id = null) {
        $langCode = $request->get('lang', $this->primaryLang);
        
        $item = [];
        
        if ($id) {
            $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
            $itemModel = GalleryModel::adminQuery()->qbFind($id);
            
            if (!$itemModel) {
                return $this->redirect(route('admin.gallery.index'))->with('error', 'Không tìm thấy Album!');
            }
            
            $item = $itemModel->toArray();
            $langCode = $item['lang'];
            
        } else {
            $sourceId = $request->get('source_id'); // id_code of the original item
            if ($sourceId) {
                $sourceItem = GalleryModel::adminQuery()->where('id_code', $sourceId)->first();
                if ($sourceItem) {
                    $item['id_code'] = $sourceItem->id_code;
                    $item['category_id'] = $sourceItem->category_id;
                    $item['image'] = $sourceItem->image;
                    $item['gallery'] = $sourceItem->gallery;
                    $item['status'] = $sourceItem->status;
                    $item['sort_order'] = $sourceItem->sort_order;
                    $item['is_featured'] = $sourceItem->is_featured;
                }
            }
        }
        
        if (isset($item['gallery'])) {
            $decodedGallery = json_decode($item['gallery'], true) ?? [];
            if (is_array($decodedGallery) && (isset($decodedGallery['vi']) || isset($decodedGallery['en']))) {
                $item['gallery'] = $decodedGallery[$langCode] ?? $decodedGallery['vi'] ?? [];
            } else {
                $item['gallery'] = $decodedGallery;
            }
        }
        
        $langs = $this->langs;
        $categories = $this->getCategories();
        
        $currentLangName = 'Unknown';
        foreach ($langs as $l) {
            if ($l['code'] === $langCode) {
                $currentLangName = $l['name'];
                break;
            }
        }
        
        $translations = [];
        if (!empty($item['id_code'])) {
            $allTrans = GalleryModel::adminQuery()->where('id_code', $item['id_code'])->get();
            foreach ($allTrans as $t) {
                $translations[$t->lang] = $t->id;
            }
        }
        
        return view('admin.gallery.form', compact('langs', 'categories', 'langCode', 'item', 'translations', 'currentLangName'));
    }
    
    public function store(Request $request) {
        $modelId = $this->galleryService->saveGallery($request->all(), user()->id);
        
        session('success', 'Đã lưu Album thành công!');
        return $this->handleSaveRedirect($request, $modelId);
    }

    public function update(Request $request, $id) {
        $id = (int)$id;
        
        $firstGallery = GalleryModel::adminQuery()->qbFind($id);
        
        if (!$this->canModify($firstGallery)) {
            session('error', 'Bạn không có quyền chỉnh sửa Album này!');
            return $this->redirect(route('admin.gallery.index'));
        }

        $inputData = $request->all();
        $inputData['id'] = $id;
        
        $modelId = $this->galleryService->saveGallery($inputData, user()->id);
        
        session('success', 'Cập nhật Album thành công!');
        return $this->handleSaveRedirect($request, $modelId);
    }
    
    private function handleSaveRedirect(Request $request, $insertedId) {
        if ($request->input('save_action') === 'continue') {
            return $this->redirect(route('admin.gallery.edit', ['id' => $insertedId]));
        }
        return $this->redirect(route('admin.gallery.index'));
    }

    public function destroyAjax(Request $request) {
        $id = $request->input('id');
        $album = GalleryModel::adminQuery()->qbFind($id);
        
        if (!$album) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy Album!']);
        }
        
        if (!$this->canModify($album)) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xóa Album này!']);
        }

        GalleryModel::adminQuery()->where('id_code', $album->id_code)->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa Album thành công!']);
    }

    public function bulkDeleteAjax(Request $request) {
        $ids = $request->input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'Không có mục nào được chọn!']);
        }
        
        $albums = GalleryModel::adminQuery()->whereIn('id', $ids)->get();
        if (count($albums) === 0) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy mục nào để xóa!']);
        }
        
        $idCodes = [];
        $unauthorizedCount = 0;
        
        foreach ($albums as $a) {
            if ($this->canModify($a)) {
                $idCodes[] = $a->id_code;
            } else {
                $unauthorizedCount++;
            }
        }
        
        $idCodes = array_unique($idCodes);
        
        if (empty($idCodes)) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xóa các mục đã chọn!']);
        }
        
        GalleryModel::adminQuery()->whereIn('id_code', $idCodes)->delete();
        
        $msg = 'Đã xóa ' . count($idCodes) . ' Album và các bản dịch thành công!';
        if ($unauthorizedCount > 0) {
            $msg .= " Đã bỏ qua $unauthorizedCount mục do không có quyền.";
        }
        
        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function updateStatusAjax(Request $request) {
        $id    = (int)$request->input('id');
        $field = $request->input('field', 'status');
        $value = (int)$request->input('value', 0);

        if (!in_array($field, ['status', 'is_featured'])) {
            return $this->jsonError('Trường dữ liệu không hợp lệ');
        }

        $album = GalleryModel::adminQuery()->where('id_code', $id)->first();
        if (!$album) return $this->jsonError('ID không hợp lệ');

        if (!$this->canModify($album)) {
            return $this->jsonError('Bạn không có quyền sửa Album này!');
        }

        GalleryModel::adminQuery()
            ->where('id_code', $album->id_code)
            ->update([$field => $value]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!'
        ]);
    }
}
