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
        
        $query = GalleryModel::adminQuery()
            ->where('lang', $currentLang)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc');
        
        if (!empty($keyword)) {
            $query->whereLike('title', $keyword);
        }
        
        $albums = $query->qbPaginate(20);
        
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
        $categories = CategoryModel::where('status', 1)->where('module', config('modules.album', 15))->orderBy('sort_order', 'asc')->get();
        return view('admin.gallery.index', compact('albums', 'keyword', 'currentLang', 'langs', 'translations'));
    }
    
    public function create(Request $request) {
        $langCode = $request->get('lang', $this->primaryLang);
        $sourceId = $request->get('source_id'); // id_code of the original item
        
        $item = [];
        if ($sourceId) {
            $sourceItem = GalleryModel::adminQuery()->where('id_code', $sourceId)->first();
            if ($sourceItem) {
                $item['id_code'] = $sourceItem->id_code;
                $item['category_id'] = $sourceItem->category_id;
                $item['image'] = $sourceItem->image;
                $item['gallery'] = $sourceItem->gallery; // Already json string, will decode below
                $item['status'] = $sourceItem->status;
                $item['sort_order'] = $sourceItem->sort_order;
                $item['is_featured'] = $sourceItem->is_featured;
            }
        }
        
        if (isset($item['gallery'])) {
            $decoded = json_decode($item['gallery'], true) ?? [];
            // Support backward compatibility for old JSON format {"vi":["img1"], "en":[]}
            if (is_array($decoded) && (isset($decoded['vi']) || isset($decoded['en']))) {
                $item['gallery'] = $decoded[$langCode] ?? $decoded['vi'] ?? [];
            } else {
                $item['gallery'] = $decoded;
            }
        }
        
        $langs = $this->langs;
        $categories = CategoryModel::where('status', 1)->where('module', config('modules.album', 15))->orderBy('sort_order', 'asc')->get();
        return view('admin.gallery.form', compact('langs', 'categories', 'langCode', 'item'));
    }
    
    public function edit(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        $itemModel = GalleryModel::adminQuery()->qbFind($id);
        if (!$itemModel) {
            return $this->redirect(route('admin.gallery.index'))->with('error', 'Không tìm thấy Album!');
        }
        
        $item = $itemModel->toArray();
        $langCode = $item['lang'];
        
        $decodedGallery = json_decode($item['gallery'], true) ?? [];
        // Support backward compatibility for old JSON format {"vi":["img1"], "en":[]}
        if (is_array($decodedGallery) && (isset($decodedGallery['vi']) || isset($decodedGallery['en']))) {
            $item['gallery'] = $decodedGallery[$langCode] ?? $decodedGallery['vi'] ?? [];
        } else {
            $item['gallery'] = $decodedGallery;
        }
        
        // Fetch translations map
        $allTrans = GalleryModel::adminQuery()->where('id_code', $item['id_code'])->get();
        $translations = [];
        foreach ($allTrans as $t) {
            $translations[$t->lang] = $t->id;
        }

        $langs = $this->langs;
        $categories = CategoryModel::where('status', 1)->where('module', config('modules.album', 15))->orderBy('sort_order', 'asc')->get();
        
        return view('admin.gallery.form', compact('langs', 'categories', 'langCode', 'item', 'translations'));
    }
    
    public function store(Request $request) {
        $user = user();
        
        $modelId = $this->galleryService->saveGallery($request->all(), $user->id);
        
        if ($request->input('save_action') === 'continue') {
            return $this->redirect(route('admin.gallery.edit', ['id' => $modelId]))->with('success', 'Đã lưu Album thành công!');
        }
        
        return $this->redirect(route('admin.gallery.index'))->with('success', 'Đã lưu Album thành công!');
    }
    
    public function destroyAjax(Request $request) {
        $id = $request->input('id');
        $album = GalleryModel::adminQuery()->qbFind($id);
        if ($album) {
            // Delete all translations for this id_code
            GalleryModel::adminQuery()->where('id_code', $album->id_code)->delete();
            return response()->json(['success' => true, 'message' => 'Đã xóa Album thành công!']);
        }
        return response()->json(['success' => false, 'message' => 'Không tìm thấy Album!']);
    }
}
