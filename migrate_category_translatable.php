<?php
// Thêm hàm env() dummy để parse database.php
function env($key, $default = null) {
    if ($key == 'DB_HOST') return 'localhost';
    if ($key == 'DB_DATABASE') return 'phuongnamv_db_new';
    if ($key == 'DB_USERNAME') return 'root';
    if ($key == 'DB_PASSWORD') return '';
    return $default;
}

$config = include __DIR__ . '/config/database.php';
$dsn = "mysql:host={$config['servername']};dbname={$config['database']};charset=utf8mb4";
$pdo = new PDO($dsn, $config['username'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$prefix = $config['refix'] ?? 'db_';
$catTable = $prefix . 'categories';
$transTable = $prefix . 'category_translations';

echo "Bắt đầu migration...\n";

// 1. Tạo bảng category_translations
$sql = "CREATE TABLE IF NOT EXISTS `$transTable` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `lang` VARCHAR(10) NOT NULL,
    `title` VARCHAR(255) DEFAULT '',
    `slug` VARCHAR(255) DEFAULT '',
    `description` TEXT,
    `content` LONGTEXT,
    `seo_title` VARCHAR(255) DEFAULT '',
    `keyword` VARCHAR(255) DEFAULT '',
    `seo_description` TEXT,
    `seo_head` TEXT,
    `seo_body` TEXT,
    `seo_schema` TEXT,
    `seo_canonical` VARCHAR(255) DEFAULT '',
    UNIQUE KEY `unique_category_lang` (`category_id`, `lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$pdo->exec($sql);
echo "- Đã tạo bảng $transTable\n";

// Xóa dữ liệu cũ nếu chạy lại
$pdo->exec("TRUNCATE TABLE `$transTable`");

// 2. Migrate data
$stmt = $pdo->query("SELECT * FROM `$catTable`");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$insertTransStmt = $pdo->prepare("INSERT INTO `$transTable` 
    (`category_id`, `lang`, `title`, `slug`, `description`, `content`, `seo_title`, `keyword`, `seo_description`, `seo_head`, `seo_body`, `seo_schema`, `seo_canonical`) 
    VALUES 
    (:category_id, :lang, :title, :slug, :description, :content, :seo_title, :keyword, :seo_description, :seo_head, :seo_body, :seo_schema, :seo_canonical)");

$processedIdCodes = [];

foreach ($categories as $cat) {
    $categoryId = $cat['id_code'];
    
    // Đảm bảo có category_id hợp lệ
    if (empty($categoryId)) {
        $categoryId = $cat['id'];
    }

    $insertTransStmt->execute([
        ':category_id' => $categoryId,
        ':lang' => $cat['lang'] ?? 'vi',
        ':title' => $cat['title'],
        ':slug' => $cat['slug'],
        ':description' => $cat['description'],
        ':content' => $cat['content'],
        ':seo_title' => $cat['seo_title'],
        ':keyword' => $cat['keyword'],
        ':seo_description' => $cat['seo_description'],
        ':seo_head' => $cat['seo_head'],
        ':seo_body' => $cat['seo_body'],
        ':seo_schema' => $cat['seo_schema'],
        ':seo_canonical' => $cat['seo_canonical']
    ]);

    $processedIdCodes[] = $categoryId;
}

echo "- Đã migrate " . count($categories) . " bản ghi sang $transTable\n";

// 3. Dọn dẹp bảng chính (Xóa các dòng trùng lặp id_code, chỉ giữ lại 1 dòng làm gốc)
// Dòng được giữ lại sẽ là dòng có id = id_code (nếu có), hoặc dòng đầu tiên của id_code đó
$processedIdCodes = array_unique($processedIdCodes);
foreach ($processedIdCodes as $idCode) {
    // Tìm ID cần giữ lại (ưu tiên id = id_code, hoặc min(id))
    $stmt = $pdo->prepare("SELECT id FROM `$catTable` WHERE id_code = :id_code ORDER BY (id = id_code) DESC, id ASC LIMIT 1");
    $stmt->execute([':id_code' => $idCode]);
    $keepId = $stmt->fetchColumn();

    if ($keepId) {
        // Xóa các row khác có cùng id_code
        $delStmt = $pdo->prepare("DELETE FROM `$catTable` WHERE id_code = :id_code AND id != :keep_id");
        $delStmt->execute([':id_code' => $idCode, ':keep_id' => $keepId]);
        
        // Update id = id_code cho row giữ lại (để parent_id và ID chuẩn xác)
        if ($keepId != $idCode) {
            // Có thể xảy ra lỗi duplicate primary key nếu id_code đã được dùng, 
            // nhưng id_code được tạo ra từ id của bản ghi đầu tiên nên thường là an toàn.
            // Để an toàn, chúng ta update ignore.
            $updStmt = $pdo->prepare("UPDATE IGNORE `$catTable` SET id = :id_code WHERE id = :keep_id");
            $updStmt->execute([':id_code' => $idCode, ':keep_id' => $keepId]);
        }
    }
}
echo "- Đã loại bỏ các dòng dư thừa trong $catTable\n";

// 4. Xóa các cột ngôn ngữ khỏi bảng chính
$colsToDrop = [
    'id_code', 'lang', 'title', 'slug', 'description', 'content', 
    'seo_title', 'keyword', 'seo_description', 'seo_head', 'seo_body', 'seo_schema', 'seo_canonical'
];

foreach ($colsToDrop as $col) {
    // Kiểm tra xem cột có tồn tại không trước khi drop
    $checkCols = $pdo->prepare("SHOW COLUMNS FROM `$catTable` LIKE '$col'");
    $checkCols->execute();
    if ($checkCols->rowCount() > 0) {
        $pdo->exec("ALTER TABLE `$catTable` DROP COLUMN `$col`");
        echo "- Đã drop cột $col\n";
    }
}

echo "Hoàn tất migration!\n";
