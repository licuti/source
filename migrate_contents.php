<?php
$pdo = new PDO("mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->exec("RENAME TABLE db_content TO db_contents");
    echo "Renamed table.\n";
} catch(Exception $e) { echo "Rename error: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE db_contents 
        ADD COLUMN `name` VARCHAR(255) DEFAULT NULL AFTER `lang`,
        ADD COLUMN `alias` VARCHAR(255) DEFAULT NULL AFTER `name`,
        ADD COLUMN `schema_config` JSON DEFAULT NULL AFTER `alias`,
        ADD COLUMN `data_payload` JSON DEFAULT NULL AFTER `schema_config`,
        ADD COLUMN `created_at` DATETIME DEFAULT NULL,
        ADD COLUMN `updated_at` DATETIME DEFAULT NULL
    ");
    echo "Added new columns.\n";
} catch(Exception $e) { echo "Add error: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE db_contents CHANGE COLUMN `id_loai` `category_id` INT NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE db_contents CHANGE COLUMN `hien_thi` `is_active` TINYINT(1) NOT NULL DEFAULT 1");
    $pdo->exec("ALTER TABLE db_contents CHANGE COLUMN `so_thu_tu` `sort_order` INT NOT NULL DEFAULT 0");
    echo "Renamed standard columns.\n";
} catch(Exception $e) { echo "Standard rename error: " . $e->getMessage() . "\n"; }

try {
    $stmt = $pdo->query("SELECT * FROM db_contents");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $legacySchema = [
        ["name" => "title", "label" => "Tiêu đề", "type" => "text"],
        ["name" => "subtitle", "label" => "Tiêu đề phụ", "type" => "text"],
        ["name" => "image", "label" => "Hình ảnh", "type" => "image"],
        ["name" => "content", "label" => "Nội dung", "type" => "richtext"],
        ["name" => "url", "label" => "Link", "type" => "url"],
        ["name" => "heading_tag", "label" => "Thẻ Heading", "type" => "select", "options" => [
            "div" => "Bình thường (DIV)", "h1" => "H1", "h2" => "H2", "h3" => "H3", "h4" => "H4", "h5" => "H5"
        ]],
        ["name" => "target", "label" => "Mở cửa sổ mới (_blank)", "type" => "checkbox"],
        ["name" => "nofollow", "label" => "Nofollow", "type" => "checkbox"],
        ["name" => "video_url", "label" => "Video URL", "type" => "url"],
        ["name" => "video_id", "label" => "Mã Video", "type" => "text"]
    ];
    $schemaJson = json_encode($legacySchema, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    $updateStmt = $pdo->prepare("UPDATE db_contents SET `name` = ?, `alias` = ?, `schema_config` = ?, `data_payload` = ?, `created_at` = ? WHERE `id` = ?");

    foreach ($rows as $row) {
        $payload = [
            "title" => $row["ten"] ?? "",
            "subtitle" => $row["ten_phu"] ?? "",
            "image" => $row["hinh_anh"] ?? "",
            "content" => $row["noi_dung"] ?? "",
            "url" => $row["link"] ?? "",
            "heading_tag" => $row["heading"] ?? "div",
            "target" => isset($row["target"]) && $row["target"] == 1,
            "nofollow" => isset($row["nofollow"]) && $row["nofollow"] == 1,
            "video_url" => $row["video"] ?? "",
            "video_id" => $row["ma_video"] ?? ""
        ];
        
        array_walk_recursive($payload, function(&$item) {
            if (is_string($item)) {
                $item = mb_convert_encoding($item, "UTF-8", "UTF-8");
            }
        });

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        $name = "Khối nội dung " . $row["id_code"];
        $alias = "content_" . $row["id_code"];
        
        $timestamp = (int)$row["ngay_tao"];
        $createdAt = $timestamp > 0 ? date("Y-m-d H:i:s", $timestamp) : null;

        $updateStmt->execute([$name, $alias, $schemaJson, $payloadJson, $createdAt, $row["id"]]);
    }
    echo "Migrated data to JSON payload and schema.\n";
} catch(Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit;
}

try {
    $pdo->exec("ALTER TABLE db_contents 
        DROP COLUMN `ten`,
        DROP COLUMN `ten_phu`,
        DROP COLUMN `hinh_anh`,
        DROP COLUMN `noi_dung`,
        DROP COLUMN `link`,
        DROP COLUMN `heading`,
        DROP COLUMN `target`,
        DROP COLUMN `nofollow`,
        DROP COLUMN `video`,
        DROP COLUMN `ma_video`,
        DROP COLUMN `ngay_tao`
    ");
    echo "Dropped legacy columns.\n";
} catch(Exception $e) { echo "Drop error: " . $e->getMessage() . "\n"; }

echo "Done.\n";
?>
