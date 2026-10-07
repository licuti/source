<?php 
if(!isset($_SESSION)){
    session_set_cookie_params(['SameSite' => 'None']);
    session_start();
}
ob_start();
error_reporting(0);
define('_source_lib','../lib/');
global $d;
global $lang;
require_once __DIR__ . '/../../admin/lib/bootstrap.php';
$d = new func_index($config['database']);
include_once _source_lib."lang.php";
include_once _source_lib."info.php";
include_once _source_lib."function.php";
$do = validate_content($_POST['do']);

if($do=='change_so_luong'){
    $id_code_sp = (int)$_POST['id'];
    $so_luong = (int)$_POST['sl'];
    $_SESSION['cart'][$id_code_sp]['so_luong'] = $so_luong;
}
elseif($do=='get_huyen'){
    $code_tinh= addslashes($_POST['code_tinh']);
    echo '<option value="" >Chọn Quận / Huyện</option>';
    foreach ($d->getHuyen($code_tinh,'code, ten') as $key => $value) {
        echo '<option value="'.$value['code'].'" >'.$value['ten'].'</option>';
    }
}elseif($do=='get_xa'){
    $code_huyen = addslashes($_POST['code_huyen']);
    echo '<option value="" >Chọn phường/xã</option>';
    foreach ($d->getXa($code_huyen,'code,ten') as $key => $value) {
        echo '<option value="'.$value['code'].'" >'.$value['ten'].'</option>';
    }
}elseif($do=='quick_view'){
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode([]); exit; }

    $sp = $d->safeFetchOne("SELECT sp.*, " .
        "COALESCE((SELECT MIN(IF(v.khuyen_mai>0,v.khuyen_mai,v.gia)) FROM #_sanpham_bienthe v WHERE v.id_sanpham=sp.id_code), IF(sp.khuyen_mai>0,sp.khuyen_mai,sp.gia)) as min_price," .
        "COALESCE((SELECT MAX(IF(v.khuyen_mai>0,v.khuyen_mai,v.gia)) FROM #_sanpham_bienthe v WHERE v.id_sanpham=sp.id_code), IF(sp.khuyen_mai>0,sp.khuyen_mai,sp.gia)) as max_price" .
        " FROM #_sanpham sp WHERE sp.id_code = ? AND sp.hien_thi = 1 LIMIT 1", array($id));

    if (!$sp) { echo json_encode([]); exit; }

    // Biến thể chi tiết để map linh hoạt trong JS
    $variants = $d->safeFetch("SELECT * FROM #_sanpham_bienthe WHERE id_sanpham = ? ORDER BY id ASC", array($id));
    foreach($variants as &$v) {
        $v['attributes'] = $d->safeFetch(
            "SELECT id_thuoctinh, id_thuoctinh_giatri FROM #_sanpham_bienthe_thuoctinh WHERE id_bienthe = ?",
            array($v['id'])
        );
    }
    unset($v); // Giải phóng tham chiếu để tránh ghi đè dữ liệu ở vòng lặp sau

    // Thuộc tính nhóm
    $attrs_raw = $d->safeFetch("SELECT btt.id_bienthe, btt.id_thuoctinh, btt.id_thuoctinh_giatri,
        tt.ten as attr_name, tt.loai, ttg.ten as val_name, ttg.gia_tri
        FROM #_sanpham_bienthe_thuoctinh btt
        JOIN #_sanpham_bienthe bt ON bt.id=btt.id_bienthe
        JOIN #_thuoctinh tt ON tt.id_code=btt.id_thuoctinh
        JOIN #_thuoctinh_giatri ttg ON ttg.id_code=btt.id_thuoctinh_giatri
        WHERE bt.id_sanpham = ? ORDER BY tt.id, ttg.id", array($id));

    $grouped_attrs = [];
    foreach ($attrs_raw as $a) {
        $aid = $a['id_thuoctinh'];
        if (!isset($grouped_attrs[$aid])) {
            $grouped_attrs[$aid] = ['id'=>$aid,'name'=>$a['attr_name'],'loai'=>$a['loai'],'values'=>[]];
        }
        $found_attr = false;
        foreach ($grouped_attrs[$aid]['values'] as $v_item) { 
            if ($v_item['id'] == $a['id_thuoctinh_giatri']) { 
                $found_attr = true; 
                break; 
            } 
        }
        if (!$found_attr) {
            $grouped_attrs[$aid]['values'][] = [
                'id'    => $a['id_thuoctinh_giatri'],
                'name'  => $a['val_name'],
                'val'   => $a['gia_tri']
            ];
        }
    }

    $result = [
        'id'          => $sp['id_code'],
        'ten'         => $sp['ten'],
        'alias'       => $sp['alias'],
        'slug'        => $sp['slug'] ?? '',
        'hinh_anh'    => $sp['hinh_anh'],
        'mo_ta'       => $sp['mo_ta'],
        'gia'         => $sp['gia'],
        'khuyen_mai'  => $sp['khuyen_mai'],
        'min_price'   => $sp['min_price'],
        'max_price'   => $sp['max_price'],
        'so_luong'    => $sp['so_luong'],
        'ma_sp'       => $sp['ma_sp'],
        'variants'    => $variants,
        'attrs'       => array_values($grouped_attrs),
        'urlpath'     => URLPATH,
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}elseif($do=='recently_viewed'){
    $ids = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : [];
    $ids = array_filter($ids);
    if (empty($ids)) { echo json_encode([]); exit; }

    $ids_str = implode(',', $ids);
    $products = $d->safeFetch("SELECT sp.id_code, sp.ten, sp.alias, sp.slug, sp.hinh_anh, sp.sp_moi, sp.sp_hot, sp.sp_sale, sp.sp_top, sp.sp_hot as noi_bat,
        (SELECT ten FROM #_category WHERE id_code = sp.id_loai and lang = ?) as category_name,
        COALESCE((SELECT MIN(IF(v.khuyen_mai>0,v.khuyen_mai,v.gia)) FROM #_sanpham_bienthe v WHERE v.id_sanpham=sp.id_code), IF(sp.khuyen_mai>0,sp.khuyen_mai,sp.gia)) as min_price,
        COALESCE((SELECT MAX(IF(v.khuyen_mai>0,v.khuyen_mai,v.gia)) FROM #_sanpham_bienthe v WHERE v.id_sanpham=sp.id_code), IF(sp.khuyen_mai>0,sp.khuyen_mai,sp.gia)) as max_price,
        sp.gia, sp.khuyen_mai
        FROM #_sanpham sp
        WHERE sp.id_code IN ($ids_str) AND sp.hien_thi=1 " . _where_lang,
        array(@$_SESSION['lang']));

    // Keep user-defined order (same as localStorage array)
    $map = [];
    foreach ($products as $p) $map[$p['id_code']] = $p;
    $ordered = [];
    foreach ($ids as $id) { if (isset($map[$id])) $ordered[] = $map[$id]; }

    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['urlpath'=>URLPATH, 'products'=>$ordered], JSON_UNESCAPED_UNICODE);
    exit;
}elseif($do=='live_search'){
    $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : "";
    $id_code = isset($_POST['id_code']) ? (int)$_POST['id_code'] : 0;
    
    if($keyword == ""){ echo ""; exit; }
    
    // Include frontend functions for Img() and createAlias()
    if(!function_exists('Img')){
        include_once _source_lib."function.php";
    }
    
    // escape ký tự đại diện của LIKE để '%' người dùng nhập không thành wildcard
    $like   = '%' . addcslashes($keyword, '%_\\') . '%';
    $params = array($like);
    $where  = " ten like ? and hien_thi = 1 "._where_lang;

    if($id_code > 0){
        // getIdsub() trả về ",1,2,3" — các id đã được ép (int) bên trong
        $list_id = array_filter(explode(',', $id_code . $d->getIdsub($id_code)), 'strlen');
        $list_id = array_map('intval', $list_id);

        if($list_id){
            $where .= " and id_loai in (" . implode(',', array_fill(0, count($list_id), '?')) . ")";
            $params = array_merge($params, $list_id);
        }
    }

    $products = $d->safeFetch(
        "select * from #_sanpham where $where order by so_thu_tu asc, id desc limit 6",
        $params
    );
    
    $html = "";
    if($products){
        foreach($products as $p){
            $price_html = "";
            if($p['khuyen_mai'] > 0){
                $price_html = '<span class="ls-price">'.number_format($p['khuyen_mai'],0,',','.').'đ</span> <span class="ls-old-price">'.number_format($p['gia'],0,',','.').'đ</span>';
            }else{
                $price_html = '<span class="ls-price">'.($p['gia'] > 0 ? number_format($p['gia'],0,',','.').'đ' : 'Liên hệ').'</span>';
            }
            
            $html .= '<a href="'.createAlias($p).'" class="ls-item">
                <div class="ls-img"><img src="'.Img($p['hinh_anh']).'" alt="'.htmlspecialchars($p['ten'], ENT_QUOTES, 'UTF-8').'"></div>
                <div class="ls-info">
                    <div class="ls-name">'.htmlspecialchars($p['ten'], ENT_QUOTES, 'UTF-8').'</div>
                    <div class="ls-prices">'.$price_html.'</div>
                </div>
            </a>';
        }
        $html .= '<a href="search.html?keyword='.urlencode($keyword).'&id_code='.$id_code.'" class="ls-view-all">'
            . 'Xem tất cả kết quả cho "'.htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8').'"</a>';
    } else {
        $html = '<div class="ls-no-result">Không tìm thấy sản phẩm nào.</div>';
    }
    
    echo $html;
    exit;
}elseif($do=='get_wishlist_products'){
    $ids = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : [];
    $ids = array_filter($ids);
    if (empty($ids)) { echo json_encode([]); exit; }

    $ids_str = implode(',', $ids);
    $products = $d->safeFetch("SELECT sp.id_code, sp.ten, sp.alias, sp.slug, sp.hinh_anh, sp.sp_hot as noi_bat,
        (SELECT ten FROM #_category WHERE id_code = sp.id_loai and lang = ?) as category_name,
        COALESCE((SELECT MIN(IF(v.khuyen_mai>0,v.khuyen_mai,v.gia)) FROM #_sanpham_bienthe v WHERE v.id_sanpham=sp.id_code), IF(sp.khuyen_mai>0,sp.khuyen_mai,sp.gia)) as min_price,
        COALESCE((SELECT MAX(IF(v.khuyen_mai>0,v.khuyen_mai,v.gia)) FROM #_sanpham_bienthe v WHERE v.id_sanpham=sp.id_code), IF(sp.khuyen_mai>0,sp.khuyen_mai,sp.gia)) as max_price,
        sp.gia, sp.khuyen_mai
        FROM #_sanpham sp
        WHERE sp.id_code IN ($ids_str) AND sp.hien_thi=1 " . _where_lang,
        array(@$_SESSION['lang']));

    // Keep user-defined order
    $map = [];
    foreach ($products as $p) $map[$p['id_code']] = $p;
    $ordered = [];
    foreach ($ids as $id) { if (isset($map[$id])) $ordered[] = $map[$id]; }

    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['urlpath'=>URLPATH, 'products'=>$ordered], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
