<?php
require __DIR__ . '/index.php';

use App\Models\CategoryModel;
use App\Models\CategoryTranslationModel;

try {
    echo "--- Kiểm tra Category Translatable ---\n";
    
    // Tạo category mới
    $catData = [
        'module' => 3,
        'parent_id' => 0,
        'status' => 1
    ];
    $id = CategoryModel::insertGetId($catData);
    echo "Tạo category mới với ID: $id\n";

    // Tạo bản dịch tiếng Việt
    CategoryTranslationModel::insert([
        'category_id' => $id,
        'lang' => 'vi',
        'title' => 'Tin tức Công nghệ',
        'slug' => 'tin-tuc-cong-nghe'
    ]);
    
    // Tạo bản dịch tiếng Anh
    CategoryTranslationModel::insert([
        'category_id' => $id,
        'lang' => 'en',
        'title' => 'Tech News',
        'slug' => 'tech-news'
    ]);

    // Test load bằng ngôn ngữ mặc định (vi)
    config(['app.locale' => 'vi']);
    $cat = CategoryModel::with('translations')->find($id);
    echo "Tên (vi): " . $cat->title . "\n";
    echo "Slug (vi): " . $cat->slug . "\n";
    
    // Lấy toArray xem có merge đúng không
    $array = $cat->toArray();
    echo "toArray có chứa translated attribute: " . (isset($array['title']) ? 'Có' : 'Không') . "\n";

    // Test getTranslation thủ công
    $enTrans = $cat->getTranslation('en');
    echo "Tên (en): " . $enTrans->title . "\n";

    // Lấy danh sách tree
    $tree = CategoryModel::getTreeForAdmin();
    echo "Số lượng danh mục gốc trong Tree: " . count($tree) . "\n";
    
    // Dọn dẹp
    CategoryModel::query()->where('id', $id)->delete();
    CategoryTranslationModel::query()->where('category_id', $id)->delete();

    echo "Thành công!\n";

} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
