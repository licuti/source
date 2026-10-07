<?php
/**
 * Module Danh mục — điều phối các thao tác xem / thêm / sửa / xoá.
 *
 * Dữ liệu nằm ở 2 bảng:
 *   - cf_code     : bản ghi gốc, mỗi danh mục 1 dòng (KHÔNG có prefix #_)
 *   - #_category  : nội dung theo từng ngôn ngữ, nối với cf_code qua id_code
 */

if (!defined('_source')) die("Error");

/** Cột được phép tìm kiếm — khớp với 'searchable' trong hienthi_tpl.php */
$SEARCHABLE_COLUMNS = array('ten', 'id_code');

$a = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';

switch ($a) {
    case "man":
        showdulieu();
        $template = @$_REQUEST['p'] . "/hienthi";
        break;
    case "add":
    case "edit":
        showdulieu();
        $template = @$_REQUEST['p'] . "/them";
        break;
    case "save":
        luudulieu($id_module);
        break;
    case "delete":
        xoadulieu($id_module);
        break;
    case "delete_image":
        xoadulieu_image($id_module);
        break;
    case "delete_all":
        xoadulieu_mang($id_module);
        break;
    default:
        $template = "index";
}

// Gỡ cột/dữ liệu không còn dùng: icon, home, menu (chạy 1 lần, idempotent)
// Gỡ cột/dữ liệu không còn dùng: icon, home, menu.
// ⚠️ SCHEMA CHANGE (ALTER TABLE): mặc định KHÔNG chạy trong request runtime.
// Bật tạm bằng RUNTIME_MIGRATIONS=true trong .env, chạy 1 lần rồi TẮT lại.
// Khuyến nghị: chạy tay file
//   database/migrations/2026_09_11_drop_category_unused_columns.sql
if (!empty($GLOBALS['config']['runtime_migrations'])) {
    category_drop_unused_columns();
}

/**
 * Xóa dữ liệu và DROP cột icon / home / menu trên #_category nếu còn tồn tại.
 * Idempotent: gọi nhiều lần an toàn (kiểm tra INFORMATION_SCHEMA).
 */
function category_drop_unused_columns()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    global $d;
    if (!is_object($d) || empty($d->db)) {
        return;
    }

    if (!empty($_SESSION['category_cols_cleaned'])) {
        return;
    }

    $prefix = isset($d->refix) ? $d->refix : 'db_';
    $table  = $prefix . 'category';
    $cols   = array('icon', 'home', 'menu');

    try {
        $stmt = $d->db->query('select database()');
        $dbName = $stmt ? (string) $stmt->fetchColumn() : '';
        if ($dbName === '') {
            return;
        }

        $check = $d->db->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS'
            . ' WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );

        foreach ($cols as $col) {
            $check->execute(array($dbName, $table, $col));
            if ((int) $check->fetchColumn() <= 0) {
                continue;
            }

            $t = str_replace('`', '``', $table);
            $c = str_replace('`', '``', $col);

            if ($col === 'icon') {
                $d->db->exec("UPDATE `{$t}` SET `{$c}` = '' WHERE `{$c}` IS NOT NULL AND `{$c}` != ''");
            } else {
                $d->db->exec("UPDATE `{$t}` SET `{$c}` = 0");
            }

            $d->db->exec("ALTER TABLE `{$t}` DROP COLUMN `{$c}`");
        }

        $_SESSION['category_cols_cleaned'] = 1;
    } catch (Exception $e) {
        if (function_exists('error_log')) {
            error_log('category_drop_unused_columns: ' . $e->getMessage());
        }
    }
}

/** Nạp dữ liệu cho màn hình danh sách hoặc form thêm/sửa. */
function showdulieu()
{
    global $d, $items, $paging, $loai, $limit, $loaibv, $totalPages, $module, $SEARCHABLE_COLUMNS;

    $module = $d->o_fet("select * from #_module where hide = 1 order by stt asc, id desc");

    if (isset($_REQUEST['a']) && $_REQUEST['a'] == 'man') {
        $loaibv = $d->array_category(0, '', '', 0);

        $col = isset($_GET['search']) ? $_GET['search'] : '';
        $key = isset($_GET['key']) ? trim($_GET['key']) : '';

        // Tên cột không thể bind tham số -> phải đối chiếu whitelist
        if ($key !== '' && in_array($col, $SEARCHABLE_COLUMNS, true)) {
            // escape ký tự đại diện để '%' người dùng nhập không thành wildcard
            $items = $d->safeFetch(
                "select * from #_category where $col like ? and lang = ? order by so_thu_tu asc, id desc",
                array('%' . addcslashes($key, '%_\\') . '%', LANG)
            );
        } else {
            $items = $d->safeFetch(
                "select * from #_category where id_loai = 0 and lang = ? order by so_thu_tu asc, id desc",
                array(LANG)
            );
        }
    } else {
        $items = array();

        if (isset($_REQUEST['id'])) {
            $items = $d->safeFetch("select * from cf_code where id = ?", array((int) $_REQUEST['id']));
        }

        $idLoai = isset($items[0]['id_loai']) ? $items[0]['id_loai'] : '';
        $idSelf = isset($items[0]['id']) ? $items[0]['id'] : 0;

        $loaibv = $d->array_category(0, '', $idLoai, 0, $idSelf);
    }
}

/**
 * Dựng mảng dữ liệu cho MỘT ngôn ngữ của bảng #_category.
 *
 * Trước đây nhánh thêm và nhánh sửa lặp lại y hệt ~20 trường, sửa một bên
 * rất dễ quên bên kia. Nay gom về một chỗ.
 *
 * @param  int   $key    Chỉ số ngôn ngữ trong mảng $_POST[...]
 * @param  array $shared Giá trị dùng chung: id_loai, module, so_thu_tu, hien_thi, hinh_anh, banner
 * @param  mixed $rowId  id của dòng đang sửa (để loại chính nó khi kiểm tra trùng alias)
 * @return array
 */
function buildCategoryData($key, array $shared, $rowId = '')
{
    global $d;

    $post = function ($name) use ($key) {
        return isset($_POST[$name][$key]) ? $_POST[$name][$key] : '';
    };

    $data = array();

    // Xác định danh mục cha cao nhất
    if ((int) $shared['id_loai'] === 0) {
        $data['parent'] = 0;
    } else {
        $rowParent = $d->safeFetchOne(
            "select id_code, parent from #_category where id_code = ?",
            array((int) $shared['id_loai'])
        );
        $data['parent'] = empty($rowParent['parent']) ? @$rowParent['id_code'] : $rowParent['parent'];
    }

    $alias = $d->createAlias($d->clear($post('alias')));
    if ($d->checkLink($alias, $rowId) == 0) {
        $alias .= '-' . rand(0, 9);
    }

    $data['ten']       = $post('ten');
    $data['slug']      = $post('slug');
    $data['url']       = $post('url');
    $data['alias']     = $alias;
    $data['id_loai']   = $shared['id_loai'];
    $data['hinh_anh']  = $shared['hinh_anh'];
    $data['mo_ta']     = $post('mo_ta');
    $data['noi_dung']  = $post('noi_dung');
    $data['so_thu_tu'] = $shared['so_thu_tu'];
    $data['hien_thi']  = $shared['hien_thi'];
    $data['nofollow']  = isset($_POST['nofollow']) ? 1 : 0;
    $data['noindex']   = isset($_POST['noindex']) ? 1 : 0;
    $data['module']    = $shared['module'];
    $data['title']     = $post('title');
    $data['keyword']   = $post('keyword');
    $data['des']       = $post('des');
    $data['seo_head']  = $post('seo_head');
    $data['seo_body']  = $post('seo_body');
    $data['cap_nhat']  = time();

    return $data;
}

/** Lưu dữ liệu: có $id thì cập nhật, không thì thêm mới. */
function luudulieu($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        $d->redirect("index.php?p=category&a=man");
        return;
    }

    $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

    $shared = array(
        'id_loai'   => (int) @$_POST['id_loai'],
        'module'    => (int) @$_POST['module'],
        'so_thu_tu' => (isset($_POST['so_thu_tu']) && $_POST['so_thu_tu'] !== '') ? (int) $_POST['so_thu_tu'] : 0,
        'hien_thi'  => isset($_POST['hien_thi']) ? 1 : 0,
        'hinh_anh'  => isset($_POST['hinh_anh']) ? $_POST['hinh_anh'] : '',
        'banner'    => isset($_POST['banner']) ? $_POST['banner'] : '',
    );

    // Bản ghi gốc trong cf_code
    $data0 = array(
        'ten'       => isset($_POST['ten'][0]) ? $_POST['ten'][0] : '',
        'id_loai'   => $shared['id_loai'],
        'module'    => $shared['module'],
        'so_thu_tu' => $shared['so_thu_tu'],
        'hien_thi'  => $shared['hien_thi'],
    );

    if ($id > 0) {
        $d->reset();
        $d->setTable('cf_code');
        $d->setWhere('id', $id);

        if (!$d->update($data0)) {
            $d->redirect("index.php?p=category&a=man");
            return;
        }

        foreach (get_json('lang') as $key => $value) {
            $rowId = isset($_POST['id_row'][$key]) ? $_POST['id_row'][$key] : '';
            $data  = buildCategoryData($key, $shared, $rowId);

            // Banner chỉ ghi đè khi người dùng thực sự chọn ảnh mới
            if ($shared['banner'] !== '') {
                $data['banner'] = $shared['banner'];
            }

            $d->reset();
            $d->setTable('#_category');
            $d->setWhere('id', $rowId);
            $d->update($data);

            // Đồng bộ URL trên menu (chỉ đường dẫn)
            if ($rowId !== '' && $rowId !== null && !empty($data['alias'])) {
                sync_menu_item_url('category', (int) $rowId, $data['alias']);
            }
        }

        $target = (@$_POST['capnhat'] == 'capnhat_sua')
            ? "index.php?p=category&a=edit&id=" . $id . "&page="
            : "index.php?p=category&a=man&page=" . urlencode(@$_REQUEST['page']);

        $d->redirect($target);
        return;
    }

    // Thêm mới
    $d->reset();
    $d->setTable('cf_code');
    $id_code = $d->insert($data0);

    if (!$id_code) {
        $d->redirect("index.php?p=category&a=man");
        return;
    }

    foreach (get_json('lang') as $key => $value) {
        $data = buildCategoryData($key, $shared);

        $data['banner']  = $shared['banner'];
        $data['id_code'] = $id_code;
        $data['lang']    = $value['code'];

        $d->reset();
        $d->setTable('#_category');
        $d->insert($data);
    }

    $target = (@$_POST['capnhat'] == 'capnhat_sua')
        ? "index.php?p=category&a=edit&id=" . $id_code . "&page="
        : "index.php?p=category&a=man";

    $d->redirect($target);
}

/**
 * Xoá ảnh của các danh mục sắp bị xoá.
 *
 * @param array $rows Mảng bản ghi #_category
 */
function xoaAnhDanhMuc(array $rows)
{
    foreach ($rows as $row) {
        foreach (array('hinh_anh', 'banner') as $col) {
            if (!empty($row[$col])) {
                // basename() chặn path traversal qua tên file lưu trong DB
                @unlink('../uploads/images/' . basename($row[$col]));
            }
        }
    }
}

/**
 * Xoá danh mục và toàn bộ danh mục con.
 *
 * @param array $ids Danh sách id_code (đã ép số nguyên)
 */
function xoaDanhMucTheoIds(array $ids)
{
    global $d;

    if (empty($ids)) {
        return;
    }

    // Gom cả cây con
    $all = $ids;
    foreach ($ids as $id) {
        $sub = trim($d->getIdsub($id), ',');
        if ($sub !== '') {
            foreach (explode(',', $sub) as $s) {
                if ($s !== '') {
                    $all[] = (int) $s;
                }
            }
        }
    }
    $all = array_unique($all);

    $holders = implode(',', array_fill(0, count($all), '?'));

    xoaAnhDanhMuc($d->safeFetch("select hinh_anh, banner from #_category where id_code in ($holders)", $all));

    $d->safeExec("delete from #_category where id_code in ($holders)", $all);
    $d->safeExec("delete from cf_code where id in ($holders)", $all);
}

function xoadulieu($id_module)
{
    global $d;

    if ($d->checkPermission_dele($id_module) != 1 || !isset($_GET['id'])) {
        $d->redirect("index.php?p=category&a=man&page=" . urlencode(@$_REQUEST['page']));
        return;
    }

    xoaDanhMucTheoIds(array((int) $_GET['id']));

    $d->redirect("index.php?p=category&a=man&page=" . urlencode(@$_REQUEST['page']));
}

function xoadulieu_mang($id_module)
{
    global $d;

    if ($d->checkPermission_dele($id_module) != 1 || empty($_POST['chk_child'])) {
        $d->redirect("index.php?p=category&a=man&page=" . urlencode(@$_REQUEST['page']));
        return;
    }

    // Ép số nguyên từng phần tử: trước đây ghép thẳng $_POST vào lệnh DELETE
    $ids = array_filter(array_map('intval', (array) $_POST['chk_child']));

    xoaDanhMucTheoIds($ids);

    $d->redirect("index.php?p=category&a=man&page=" . urlencode(@$_REQUEST['page']));
}
