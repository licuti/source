<?php
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
$postTable = $prefix . 'posts';
$transTable = $prefix . 'post_translations';
$pivotTable = $prefix . 'post_category';

echo "Bắt đầu migrate Post sang Translatable và Multi-Category...\n";

// 1. Tạo bảng translations
$sql1 = "CREATE TABLE IF NOT EXISTS `$transTable` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `post_id` int(11) NOT NULL,
    `lang` varchar(10) NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `slug` varchar(255) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `content` longtext DEFAULT NULL,
    `seo_title` varchar(255) DEFAULT NULL,
    `seo_description` text DEFAULT NULL,
    `keyword` varchar(255) DEFAULT NULL,
    `tags` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `post_lang_unique` (`post_id`, `lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$pdo->exec($sql1);
echo "- Đã tạo bảng $transTable.\n";

// 2. Tạo bảng pivot
$sql2 = "CREATE TABLE IF NOT EXISTS `$pivotTable` (
    `post_id` int(11) NOT NULL,
    `category_id` int(11) NOT NULL,
    PRIMARY KEY (`post_id`, `category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$pdo->exec($sql2);
echo "- Đã tạo bảng $pivotTable.\n";

// 3. Migrate dữ liệu
$stmt = $pdo->query("SELECT * FROM `$postTable` WHERE `lang` = 'vi'");
$viPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$insertPivot = $pdo->prepare("INSERT IGNORE INTO `$pivotTable` (`post_id`, `category_id`) VALUES (?, ?)");
$insertTrans = $pdo->prepare("INSERT IGNORE INTO `$transTable` (`post_id`, `lang`, `title`, `slug`, `description`, `content`, `seo_title`, `seo_description`, `keyword`, `tags`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$updateId = $pdo->prepare("UPDATE `$postTable` SET `id` = ? WHERE `id` = ?");

foreach ($viPosts as $post) {
    $realPostId = $post['id_code'];

    // Pivot
    if (!empty($post['category_id']) && $post['category_id'] > 0) {
        $insertPivot->execute([$realPostId, $post['category_id']]);
    }

    // Translations
    $stmtAll = $pdo->prepare("SELECT * FROM `$postTable` WHERE `id_code` = ?");
    $stmtAll->execute([$realPostId]);
    $allLangs = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allLangs as $langPost) {
        $insertTrans->execute([
            $realPostId,
            $langPost['lang'],
            $langPost['title'] ?? '',
            $langPost['slug'] ?? '',
            $langPost['description'] ?? '',
            $langPost['content'] ?? '',
            $langPost['seo_title'] ?? '',
            $langPost['seo_description'] ?? '',
            $langPost['keyword'] ?? '',
            $langPost['tags'] ?? ''
        ]);
    }

    // Update ID
    if ($post['id'] != $realPostId) {
        try {
            $updateId->execute([$realPostId, $post['id']]);
        } catch (Exception $e) {
            echo "Lỗi update ID: " . $e->getMessage() . "\n";
        }
    }
}

echo "- Đã migrate xong dữ liệu ngôn ngữ và quan hệ danh mục.\n";

// 4. Xóa các bản ghi không phải tiếng Việt ở bảng posts
$pdo->exec("DELETE FROM `$postTable` WHERE `lang` != 'vi'");
echo "- Đã dọn dẹp các bản ghi dư thừa trong bảng $postTable.\n";

// 5. Drop columns
$columnsToDrop = [
    'lang', 'id_code', 'category_id', 'title', 'slug', 'description', 
    'content', 'seo_title', 'seo_description', 'keyword', 'tags'
];

foreach ($columnsToDrop as $col) {
    try {
        $pdo->exec("ALTER TABLE `$postTable` DROP COLUMN `$col`");
        echo "- Đã xóa cột $col\n";
    } catch (Exception $e) {
        echo "- Cột $col có thể đã bị xóa (hoặc lỗi: " . $e->getMessage() . ").\n";
    }
}

echo "\n=> HOÀN TẤT MIGRATE POST!\n";
