<?php
/**
 * Script đồng bộ địa chỉ từ Pancake POS vào Local DB
 */

// Đảm bảo chạy không giới hạn thời gian (vì phải request API nhiều lần cho từng Huyện)
set_time_limit(0);

require_once __DIR__ . '/../../../lib/bootstrap.php';

try {
    $d = new func_index($config['database']);
    $pdo = $d->db;
} catch (Exception $e) {
    die("Lỗi kết nối DB: " . $e->getMessage());
}

// Hàm chuẩn hóa tên (Bỏ các từ khóa Tỉnh, Thành phố, Quận, Huyện...)
function normalizeLocationName($name) {
    $name = mb_strtolower(trim($name), 'UTF-8');
    // Thay thế một số lỗi chính tả hoặc từ địa phương
    $name = str_replace('tp.', 'thành phố', $name);
    $prefixes = ['thành phố', 'tỉnh', 'quận', 'huyện', 'thị xã', 'phường', 'xã', 'thị trấn'];
    foreach ($prefixes as $prefix) {
        if (strpos($name, $prefix) === 0) {
            $name = trim(mb_substr($name, mb_strlen($prefix)));
            break;
        }
    }
    // Remove all whitespace for fuzzy match
    $name = preg_replace('/\s+/', '', $name);
    return $name;
}

$api_key = '2173896ad6cded63e4b60373c4963754';
$baseUrl = 'https://pos.pages.fm/api/v1/geo';

function fetchApi($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $json = curl_exec($ch);
    curl_close($ch);
    $res = json_decode($json, true);
    return isset($res['data']) ? $res['data'] : [];
}

echo "Bắt đầu đồng bộ Tỉnh/Thành...\n";
$provinces = fetchApi("$baseUrl/provinces?country_code=84&api_key=$api_key");

$dbProvinces = $pdo->query("SELECT * FROM db_thanhpho")->fetchAll(PDO::FETCH_ASSOC);

$matchedProvinces = 0;
foreach ($provinces as $p) {
    $panName = normalizeLocationName($p['name']);
    foreach ($dbProvinces as $dbP) {
        $dbName = normalizeLocationName($dbP['ten']);
        if ($panName == $dbName || strpos($panName, $dbName) !== false || strpos($dbName, $panName) !== false) {
            $stmt = $pdo->prepare("UPDATE db_thanhpho SET pancake_id = ? WHERE code = ?");
            $stmt->execute([$p['id'], $dbP['code']]);
            $matchedProvinces++;
            
            // Xử lý Huyện của Tỉnh này
            $districts = fetchApi("$baseUrl/districts?province_id=" . $p['id'] . "&api_key=$api_key");
            $dbDistricts = $pdo->prepare("SELECT * FROM db_huyen WHERE code_tinh = ?");
            $dbDistricts->execute([$dbP['code']]);
            $dbDistrictsList = $dbDistricts->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($districts as $d) {
                $panDName = normalizeLocationName($d['name']);
                foreach ($dbDistrictsList as $dbD) {
                    $dbDName = normalizeLocationName($dbD['ten']);
                    if ($panDName == $dbDName || strpos($panDName, $dbDName) !== false || strpos($dbDName, $panDName) !== false) {
                        $stmt2 = $pdo->prepare("UPDATE db_huyen SET pancake_id = ? WHERE code = ?");
                        $stmt2->execute([$d['id'], $dbD['code']]);
                        
                        // Xử lý Xã của Huyện này
                        $communes = fetchApi("$baseUrl/communes?district_id=" . $d['id'] . "&province_id=" . $p['id'] . "&api_key=$api_key");
                        $dbCommunes = $pdo->prepare("SELECT * FROM db_xa WHERE code_huyen = ?");
                        $dbCommunes->execute([$dbD['code']]);
                        $dbCommunesList = $dbCommunes->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($communes as $c) {
                            $panCName = normalizeLocationName($c['name']);
                            foreach ($dbCommunesList as $dbC) {
                                $dbCName = normalizeLocationName($dbC['ten']);
                                if ($panCName == $dbCName) {
                                    $stmt3 = $pdo->prepare("UPDATE db_xa SET pancake_id = ? WHERE code = ?");
                                    $stmt3->execute([$c['id'], $dbC['code']]);
                                    break;
                                }
                            }
                        }
                        
                        break;
                    }
                }
            }
            
            break;
        }
    }
}

echo "Hoàn thành ánh xạ xong địa chỉ!\n";
