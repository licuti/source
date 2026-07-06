<?php
namespace App\Controllers\Admin;

use App\Models\GalleryModel;
use App\Models\CategoryModel;
use App\Services\GalleryService;
use App\Core\Request;

class GalleryController extends BaseAdminController {
    
    private GalleryService $galleryService;
    private array $langs;
    
    public function __construct() {
        parent::__construct();
        $this->galleryService = new GalleryService();
        $this->langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
    }
    
    public function index(Request $request) {
        $keyword = $request->get('keyword');
        $query = GalleryModel::orderBy('sort_order', 'asc')->orderBy('id', 'desc');
        
        if (!empty($keyword)) {
            // Lọc theo JSON: MySQL hỗ trợ JSON_EXTRACT hoặc dùng LIKE
            $query->whereRaw("JSON_EXTRACT(title, '$.vi') LIKE ?", ["%{$keyword}%"]);
        }
        
        $albums = $query->qbPaginate(20);
        
        // Decode json manually for displaying title
        foreach ($albums as $album) {
            $album->title = json_decode($album->title, true) ?? [];
            $album->gallery = json_decode($album->gallery, true) ?? [];
        }
        
        return view('admin.gallery.index', compact('albums', 'keyword'));
    }
    
    public function create() {
        $langs = $this->langs;
        $categories = CategoryModel::where('status', 1)->orderBy('sort_order', 'asc')->get();
        return view('admin.gallery.form', compact('langs', 'categories'));
    }
    
    public function edit($id) {
        $item = GalleryModel::find($id);
        if (!$item) {
            return $this->redirect(route('admin.gallery.index'))->with('error', 'Không tìm thấy Album!');
        }
        
        // Convert object to array and decode JSON fields
        $itemArr = (array)$item;
        $jsonFields = ['title', 'slug', 'description', 'content', 'seo_title', 'seo_description', 'keyword', 'tags', 'noindex', 'nofollow', 'seo_head', 'seo_body', 'gallery'];
        foreach ($jsonFields as $field) {
            if (isset($itemArr[$field])) {
                $itemArr[$field] = json_decode($itemArr[$field], true) ?? [];
            }
        }
        
        $item = $itemArr;
        $langs = $this->langs;
        $categories = CategoryModel::where('status', 1)->orderBy('sort_order', 'asc')->get();
        return view('admin.gallery.form', compact('item', 'langs', 'categories'));
    }
    
    public function store(Request $request) {
        $id = $request->input('id');
        $user = user();
        
        $this->galleryService->saveGallery($request->all(), $this->langs, $user->id, $id);
        
        return $this->redirect(route('admin.gallery.index'))->with('success', 'Đã lưu Album thành công!');
    }
    
    public function destroyAjax(Request $request) {
        $id = $request->input('id');
        $album = GalleryModel::find($id);
        if ($album) {
            $album->delete();
            return response()->json(['success' => true, 'message' => 'Đã xóa Album thành công!']);
        }
        return response()->json(['success' => false, 'message' => 'Không tìm thấy Album!']);
    }
}
