<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use TextModel;

class TextTranslationController extends BaseAdminController
{
    public function index(Request $request)
    {
        // Phân trang đơn giản
        $page = (int)($request->get('page') ?? 1);
        if ($page < 1) $page = 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $keyword = $request->get('keyword') ?? '';

        // Xây dựng query
        $query = TextModel::orderBy('id', 'DESC');
        if ($keyword) {
            $query->where('key_name', 'LIKE', "%{$keyword}%")
                  ->orWhere('text', 'LIKE', "%{$keyword}%");
        }

        // Lấy dữ liệu
        $translations = $query->limit($limit, $offset)->get();
        
        // Đếm tổng để phân trang
        $countQuery = TextModel::where('id', '>', 0);
        if ($keyword) {
            $countQuery->where('key_name', 'LIKE', "%{$keyword}%")
                       ->orWhere('text', 'LIKE', "%{$keyword}%");
        }
        $totalRows = count($countQuery->get('id'));
        $totalPages = ceil($totalRows / $limit);

        // Lấy danh sách ngôn ngữ đang active từ config
        $languages = config('lang') ?? [];

        return $this->render('admin.translation.index', [
            'translations' => $translations,
            'languages' => $languages,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword
        ]);
    }

    public function updateAjax(Request $request)
    {
        $data = $request->all();
        $id = $data['id'] ?? null;
        $lang = $data['lang'] ?? null;
        $text = $data['text'] ?? '';

        if (!$id || !$lang) {
            return $this->json(['success' => false, 'message' => 'Thiếu dữ liệu']);
        }

        $success = TextModel::updateTranslationAjax($id, $lang, $text);

        if ($success) {
            return $this->json(['success' => true, 'message' => 'Đã lưu']);
        }
        return $this->json(['success' => false, 'message' => 'Lỗi lưu dữ liệu']);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $keyName = trim($data['key_name'] ?? '');
        
        if (empty($keyName)) {
            $_SESSION['error'] = 'Mã từ khóa không được để trống!';
            return $this->redirect(route('admin.translation.index'));
        }

        if (TextModel::where('key_name', $keyName)->first()) {
            $_SESSION['error'] = 'Mã từ khóa đã tồn tại!';
            return $this->redirect(route('admin.translation.index'));
        }

        $languages = config('lang') ?? [];
        $texts = [];
        foreach ($languages as $code => $langInfo) {
            $texts[$code] = $data['text_' . $code] ?? '';
        }

        TextModel::create([
            'key_name' => $keyName,
            'text' => json_encode($texts, JSON_UNESCAPED_UNICODE)
        ]);

        $_SESSION['success'] = 'Thêm khóa ngôn ngữ thành công!';
        return $this->redirect(route('admin.translation.index'));
    }

    public function destroy(Request $request, $id)
    {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        TextModel::where('id', $id)->delete();
        $_SESSION['success'] = 'Xóa thành công!';
        return $this->redirect(route('admin.translation.index'));
    }

    public function scan(Request $request)
    {
        // Đường dẫn cần quét
        $directories = [
            base_path('app'),
            base_path('resources/views')
        ];

        $keysFound = [];

        foreach ($directories as $dir) {
            $this->scanDirectory($dir, $keysFound);
        }

        $keysFound = array_unique($keysFound);
        
        // Lấy keys hiện có
        $existingKeys = [];
        foreach (TextModel::all() as $item) {
            if ($item->key_name) {
                $existingKeys[] = $item->key_name;
            }
        }

        $newCount = 0;
        foreach ($keysFound as $key) {
            if (!in_array($key, $existingKeys) && !is_numeric($key)) {
                TextModel::create([
                    'key_name' => $key,
                    'text' => json_encode(['vi' => $key, 'en' => $key], JSON_UNESCAPED_UNICODE)
                ]);
                $newCount++;
            }
        }

        $_SESSION['success'] = "Đã quét xong. Tìm thấy và thêm mới {$newCount} từ khóa chưa có trong dữ liệu.";
        return $this->redirect(route('admin.translation.index'));
    }

    private function scanDirectory($dir, &$keysFound)
    {
        if (!is_dir($dir)) return;

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->scanDirectory($path, $keysFound);
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($path);
                // Biểu thức chính quy tìm các chuỗi dạng __('chuoi_dich') hoặc __("chuoi_dich")
                preg_match_all('/__\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches);
                if (!empty($matches[1])) {
                    $keysFound = array_merge($keysFound, $matches[1]);
                }
            }
        }
    }
}
