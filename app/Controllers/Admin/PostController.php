<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\ModuleModel;
use CategoryModel;
use PostModel;
use CfCodeModel;

class PostController extends BaseAdminController {
    
    /**
     * Helper kiểm tra quyền sở hữu hoặc quyền admin
     */
    private function canEditPost($created_by) {
        $user = user();
        if ($user->is_admin == 1) return true;
        if ($user->id == $created_by) return true;
        return false;
    }

    /**
     * Helper lọc query theo quyền sở hữu
     */
    private function applyOwnershipFilter($query) {
        $user = user();
        if ($user->is_admin != 1) {
            $query->where('created_by', $user->id);
        }
        return $query;
    }

    /**
     * Hiển thị danh sách bài viết
     */
    public function index(Request $request) {
        $keyword = trim($request->input('keyword', ''));
        $status  = $request->input('status', '');
        $category_id = (int)$request->input('category_id', 0);
        $page    = (int)$request->input('page', 1);
        if ($page < 1) $page = 1;
        $limit = 10;

        // 1. Lấy danh sách ID master từ bảng cf_code để đếm tổng số
        $cfQuery = CfCodeModel::query()->where('module', 3);
        
        $user = user();
        if ($user->is_admin != 1) {
            // Lấy danh sách post_id do user này tạo
            $myPostIds = PostModel::query()
                ->where('created_by', $user->id)
                ->use_lang(false)
                ->get('id_code');
            $allowedIds = array_unique(array_column($myPostIds, 'id_code'));
            
            if (empty($allowedIds)) {
                $cfQuery->where('id', -1); // Force empty
            } else {
                $cfQuery->whereIn('id', $allowedIds);
            }
        }

        if ($status !== '') {
            $cfQuery->where('hien_thi', (int)$status);
        }
        
        if ($category_id > 0) {
            $cfQuery->where('id_loai', $category_id);
        }

        if ($keyword !== '') {
            $cfQuery->whereLike('ten', '%' . $keyword . '%');
        }

        $totalRows = count($cfQuery->get('id'));
        $totalPages = max(1, ceil($totalRows / $limit));
        $offset = ($page - 1) * $limit;

        $cfQuery->orderBy('so_thu_tu', 'ASC')->orderBy('id', 'DESC');
        $cfQuery->limit($limit, $offset);
        $masters = $cfQuery->get();

        $posts = [];
        if (!empty($masters)) {
            $masterIds = array_column($masters, 'id');
            // Lấy bản dịch tiếng Việt mặc định (hoặc lang hiện tại)
            $translations = PostModel::query()
                ->whereIn('id_code', $masterIds)
                ->get();
                
            $transMap = [];
            foreach ($translations as $t) {
                $transMap[$t->id_code] = $t;
            }

            foreach ($masters as $m) {
                if (isset($transMap[$m->id])) {
                    $post = $transMap[$m->id];
                    // Map lại các trường gốc nếu cần
                    $post->hien_thi = $m->hien_thi;
                    $post->so_thu_tu = $m->so_thu_tu;
                    $post->id_loai = $m->id_loai;
                    $posts[] = $post;
                }
            }
        }

        $categories = CategoryModel::getTreeForAdmin();

        return $this->render('admin.post.index', compact('posts', 'keyword', 'status', 'category_id', 'page', 'totalPages', 'totalRows', 'categories'));
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        $categories = CategoryModel::getTreeForAdmin();
        return $this->render('admin.post.form', compact('langs', 'categories'));
    }

    /**
     * Mở form chỉnh sửa
     */
    public function edit(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        
        $cfCode = CfCodeModel::query()->where('id', $id)->first();
        if (!$cfCode) return $this->redirect(route('admin.post.index'));
        
        $postQuery = PostModel::query();
        $postQuery->use_lang = false; // Lấy tất cả ngôn ngữ
        $translations = $postQuery->where('id_code', $id)->get();
        
        if (count($translations) > 0 && !$this->canEditPost($translations[0]->created_by)) {
            session('error', 'Bạn không có quyền chỉnh sửa bài viết này!');
            return $this->redirect(route('admin.post.index'));
        }

        // Chuyển hóa dữ liệu để render lên form
        $item = [
            'id'        => $id, 
            'id_loai'   => $cfCode->id_loai, 
            'so_thu_tu' => $cfCode->so_thu_tu, 
            'hien_thi'  => $cfCode->hien_thi,
            'is_featured' => 0,
            'hinh_anh'  => ''
        ];
        
        foreach ($translations as $t) {
            $lang = $t->lang;
            $item["ten"][$lang] = $t->title;
            $item["alias"][$lang] = $t->alias;
            $item["mo_ta"][$lang] = $t->description;
            $item["noi_dung"][$lang] = $t->content;
            $item["is_featured"] = $t->is_featured;
            if (empty($item["hinh_anh"])) {
                $item["hinh_anh"] = $t->image;
            }
        }
        
        $categories = CategoryModel::getTreeForAdmin();
        
        return $this->render('admin.post.form', compact('langs', 'item', 'categories'));
    }

    /**
     * Lưu dữ liệu thêm mới
     */
    public function store(Request $request) {
        $id_loai = (int)$request->input('id_loai', 0);
        $so_thu_tu = (int)$request->input('so_thu_tu', 0);
        $hien_thi = $request->input('hien_thi') !== null ? 1 : 0;
        
        $tenInput = $request->input('ten', []);
        $ten_vi = $tenInput['vi'] ?? '';

        // 1. Lưu vào bảng gốc
        $id_code = CfCodeModel::insert([
            'ten'       => $ten_vi,
            'hinh_anh'  => $request->input('hinh_anh') ?? '',
            'id_loai'   => $id_loai,
            'module'    => 3,
            'so_thu_tu' => $so_thu_tu,
            'hien_thi'  => $hien_thi
        ]);

        if ($id_code) {
            $user_id = user()->id;
            $now = date('Y-m-d H:i:s');
            
            // 2. Lưu vào bảng ngôn ngữ
            $langs = config('lang', [['code' => 'vi']]);
            foreach ($langs as $l) {
                $c = $l['code'];
                PostModel::insert([
                    'id_code'     => $id_code,
                    'lang'        => $c,
                    'title'       => $request->input('ten')[$c] ?? '',
                    'alias'       => empty($request->input('alias')[$c]) ? str_slug($request->input('ten')[$c] ?? '') : $request->input('alias')[$c],
                    'description' => $request->input('mo_ta')[$c] ?? '',
                    'content'     => $request->input('noi_dung')[$c] ?? '',
                    'image'       => $request->input('hinh_anh') ?? '',
                    'category_id' => $id_loai,
                    'sort_order'  => $so_thu_tu,
                    'is_active'   => $hien_thi,
                    'is_featured' => $request->input('is_featured') !== null ? 1 : 0,
                    'created_by'  => $user_id,
                    'created_at'  => $now,
                    'updated_at'  => $now
                ]);
            }
            session('success', 'Thêm bài viết thành công!');
        }
        
        return $this->redirect(route('admin.post.index'));
    }

    /**
     * Lưu dữ liệu cập nhật
     */
    public function update(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        
        $postQuery = PostModel::query();
        $postQuery->use_lang = false;
        $translations = $postQuery->where('id_code', $id)->get();
        
        if (count($translations) > 0 && !$this->canEditPost($translations[0]->created_by)) {
            session('error', 'Bạn không có quyền chỉnh sửa bài viết này!');
            return $this->redirect(route('admin.post.index'));
        }

        $id_loai = (int)$request->input('id_loai', 0);
        $so_thu_tu = (int)$request->input('so_thu_tu', 0);
        $hien_thi = $request->input('hien_thi') !== null ? 1 : 0;
        
        $tenInput = $request->input('ten', []);
        $ten_vi = $tenInput['vi'] ?? '';

        // 1. Cập nhật bảng gốc
        CfCodeModel::query()->where('id', $id)->update([
            'ten'       => $ten_vi,
            'hinh_anh'  => $request->input('hinh_anh') ?? '',
            'id_loai'   => $id_loai,
            'so_thu_tu' => $so_thu_tu,
            'hien_thi'  => $hien_thi
        ]);

        // 2. Cập nhật hoặc tạo mới bản dịch
        $langs = config('lang', [['code' => 'vi']]);
        $user_id = user()->id;
        $now = date('Y-m-d H:i:s');
        
        foreach ($langs as $l) {
            $c = $l['code'];
            $postQuery = PostModel::query();
            $postQuery->use_lang = false;
            
            $exists = $postQuery->where('id_code', $id)->where('lang', $c)->first();
            
            $data = [
                'title'       => $request->input('ten')[$c] ?? '',
                'alias'       => empty($request->input('alias')[$c]) ? str_slug($request->input('ten')[$c] ?? '') : $request->input('alias')[$c],
                'description' => $request->input('mo_ta')[$c] ?? '',
                'content'     => $request->input('noi_dung')[$c] ?? '',
                'image'       => $request->input('hinh_anh') ?? '',
                'category_id' => $id_loai,
                'sort_order'  => $so_thu_tu,
                'is_active'   => $hien_thi,
                'is_featured' => $request->input('is_featured') !== null ? 1 : 0,
                'updated_by'  => $user_id,
                'updated_at'  => $now
            ];
            
            if ($exists) {
                $updateQuery = PostModel::query();
                $updateQuery->use_lang = false;
                $updateQuery->where('id', $exists->id)->update($data);
            } else {
                $data['id_code'] = $id;
                $data['lang'] = $c;
                $data['created_by'] = $user_id;
                $data['created_at'] = $now;
                PostModel::insert($data);
            }
        }
        
        session('success', 'Cập nhật bài viết thành công!');
        return $this->redirect(route('admin.post.index'));
    }

    /**
     * Cập nhật trạng thái hiển thị qua AJAX
     */
    public function updateStatusAjax(Request $request) {
        $id = (int)$request->input('id');
        $field = $request->input('field', 'is_active');
        $value = (int)$request->input('value', 0);

        $allowedFields = ['is_active', 'hien_thi', 'is_featured']; 
        if (!in_array($field, $allowedFields)) {
            return $this->json(['success' => false, 'message' => 'Trường dữ liệu không hợp lệ']);
        }

        if ($id > 0) {
            $postQuery = PostModel::query();
            $postQuery->use_lang = false;
            $post = $postQuery->where('id_code', $id)->first();
            
            if ($post && !$this->canEditPost($post->created_by)) {
                return $this->json(['success' => false, 'message' => 'Bạn không có quyền sửa bài viết này!']);
            }

            $cfField = $field == 'is_active' ? 'hien_thi' : $field;
            CfCodeModel::query()->where('id', $id)->update([$cfField => $value]);
            
            $updateQuery = PostModel::query();
            $updateQuery->use_lang = false;
            $updateQuery->where('id_code', $id)->update([$field => $value]);

            return $this->json(['success' => true]);
        }
        return $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
    }

    /**
     * Xóa 1 dòng
     */
    public function destroy(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        
        $postQuery = PostModel::query();
        $postQuery->use_lang = false;
        $post = $postQuery->where('id_code', $id)->first();
        
        if ($post && !$this->canEditPost($post->created_by)) {
            session('error', 'Bạn không có quyền xóa bài viết này!');
            return $this->redirect(route('admin.post.index'));
        }

        if ($id > 0) {
            CfCodeModel::query()->where('id', $id)->delete();
            
            $delQuery = PostModel::query();
            $delQuery->use_lang = false;
            $delQuery->where('id_code', $id)->delete();
            
            session('success', 'Đã xóa bài viết thành công!');
        }
        
        return $this->redirect(route('admin.post.index'));
    }

    /**
     * Xóa hàng loạt
     */
    public function destroyMultiple(Request $request) {
        $ids = $request->input('ids', []);
        
        if (!empty($ids) && is_array($ids)) {
            $deletedCount = 0;
            foreach ($ids as $id) {
                $postQuery = PostModel::query();
                $postQuery->use_lang = false;
                $post = $postQuery->where('id_code', $id)->first();
                
                if ($post && $this->canEditPost($post->created_by)) {
                    CfCodeModel::query()->where('id', $id)->delete();
                    
                    $delQuery = PostModel::query();
                    $delQuery->use_lang = false;
                    $delQuery->where('id_code', $id)->delete();
                    $deletedCount++;
                }
            }
            return $this->json(['success' => true, 'message' => "Đã xóa thành công {$deletedCount} bài viết."]);
        }
        return $this->json(['success' => false, 'message' => 'Chưa chọn bản ghi nào']);
    }
}
