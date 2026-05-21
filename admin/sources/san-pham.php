<?php
if (!defined('_source')) die("Error");
$a = (isset($_REQUEST['a'])) ? addslashes($_REQUEST['a']) : "";
$link_option = '';
if (isset($_GET['search'])) {
    $link_option .= '&search=' . addslashes($_GET['search']);
}
if (isset($_GET['key'])) {
    $link_option .= '&key=' . addslashes($_GET['key']);
}
if (isset($_GET['page'])) {
    $link_option .= '&page=' . addslashes($_GET['page']);
}
$row_setting = $d->simple_fetch("select setting from #_module where id = 3 ");
$setting = $row_setting['setting'];
$arrr_setting = json_decode($setting, true);

switch ($a) {
    case "man":
        showdulieu();
        $template = @$_REQUEST['p'] . "/hienthi";
        break;
    case "variation":
        show_variation();
        $template = @$_REQUEST['p'] . "/variation";
        break;
    case "variants":
        show_variants();
        $template = @$_REQUEST['p'] . "/variants";
        break;
    case "add":
        showdulieu();
        $template = @$_REQUEST['p'] . "/them";
        break;
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
    case "delete_all":
        xoadulieu_mang($id_module);
        break;
    default:
        $template = "index";
}

function show_variation()
{
    global $d, $data, $data_type_variation, $data_type_sort;
    $data = $d->o_fet("select * from #_thuoctinh where lang='".LANG."' order by id ASC");
    $data_type_variation = [
        'select' => 'Lựa chọn',
        'color' => 'Color',
        'image' => 'Image',
        'label' => 'Label',
    ];
    $data_type_sort = [
        'id' => 'ID',
        'ten' => 'Tên'
    ];
}

function show_variants()
{
    global $d, $variation, $data;
    $variation = $d->simple_fetch("select * from #_thuoctinh where id_code=" . $_GET['variation'] . " and lang='".LANG."' order by id ASC");
    $data = $d->o_fet("select * from #_thuoctinh_giatri where id_thuoctinh=" . $_GET['variation'] . " and lang='".LANG."' order by id ASC");
}

function save_variants($id_code, $variants, $d)
{
    if (empty($variants)) return;

    $id_code = (int)$id_code;
    
    // 1. Lấy danh sách ID biến thể cũ từ DB
    $old_variants = $d->o_fet("SELECT id FROM #_sanpham_bienthe WHERE id_sanpham = $id_code");
    $old_ids = array_column($old_variants, 'id');
    
    $submitted_ids = [];
    $updated_ids = [];
    $attributes_to_insert = [];

    // 2. Phân loại biến thể (Insert mới / Update cũ)
    foreach ($variants as $variant) {
        $variant_id = isset($variant['id']) ? (int)$variant['id'] : 0;
        
        $data_bienthe = [
            'id_sanpham' => $id_code,
            'ma_sp'      => trim($variant['ma_sp']),
            'gia'        => (int)$variant['gia'],
            'khuyen_mai' => (int)$variant['khuyen_mai'],
            'so_luong'   => (int)$variant['so_luong'],
            'weight'     => (float)$variant['weight'],
            'hinh_anh'   => trim($variant['hinh_anh']),
            'hien_thi'   => 1,
            'cap_nhat'   => date("Y-m-d H:i:s"),
        ];

        $id_bienthe = 0;

        if ($variant_id > 0 && in_array($variant_id, $old_ids)) {
            // Biến thể cũ -> Cập nhật
            $d->reset();
            $d->setTable('#_sanpham_bienthe');
            $d->setWhere('id', $variant_id);
            if ($d->update($data_bienthe)) {
                $id_bienthe = $variant_id;
                $submitted_ids[] = $variant_id;
                $updated_ids[] = $variant_id;
            }
        } else {
            // Biến thể mới -> Thêm mới
            $data_bienthe['ngay_dang'] = date("Y-m-d H:i:s");
            $d->reset();
            $d->setTable('#_sanpham_bienthe');
            $id_bienthe = $d->insert($data_bienthe);
            if ($id_bienthe) {
                $submitted_ids[] = $id_bienthe;
            }
        }

        // Gom các thuộc tính lại để lưu batch sau
        if ($id_bienthe && !empty($variant['attributes'])) {
            foreach ($variant['attributes'] as $thuoctinh_code => $giatri_code) {
                if (!empty($giatri_code)) {
                    $attributes_to_insert[] = "($id_bienthe, " . (int)$thuoctinh_code . ", " . (int)$giatri_code . ")";
                }
            }
        }
    }
    
    // 3. Xử lý batch cho thuộc tính
    // 3.1. Xóa thuộc tính nối cũ của các biến thể vừa cập nhật (một query duy nhất)
    if (!empty($updated_ids)) {
        $in_updated = implode(',', $updated_ids);
        $d->o_que("DELETE FROM #_sanpham_bienthe_thuoctinh WHERE id_bienthe IN ($in_updated)");
    }

    // 3.2. Chèn toàn bộ thuộc tính mới (một query duy nhất)
    if (!empty($attributes_to_insert)) {
        $sql_batch = "INSERT INTO #_sanpham_bienthe_thuoctinh (id_bienthe, id_thuoctinh, id_thuoctinh_giatri) VALUES " . implode(',', $attributes_to_insert);
        $d->o_que($sql_batch);
    }
    
    // 4. Xóa các biến thể cũ không còn nằm trong danh sách gửi lên (bị người dùng xóa)
    $ids_to_delete = array_diff($old_ids, $submitted_ids);
    if (!empty($ids_to_delete)) {
        $ids_str = implode(',', $ids_to_delete);
        $d->o_que("DELETE FROM #_sanpham_bienthe_thuoctinh WHERE id_bienthe IN ($ids_str)");
        $d->o_que("DELETE FROM #_sanpham_bienthe WHERE id IN ($ids_str)");
    }
}

function delete_variants_by_product($id_code, $d)
{
    $id_code = (int)$id_code;
    // Xóa tất cả thuộc tính của biến thể
    $d->o_que("DELETE FROM #_sanpham_bienthe_thuoctinh WHERE id_bienthe IN (SELECT id FROM #_sanpham_bienthe WHERE id_sanpham = $id_code)");
    
    // Xóa tất cả biến thể
    $d->o_que("DELETE FROM #_sanpham_bienthe WHERE id_sanpham = $id_code");
}

function showdulieu()
{
    global $d, $items, $limit, $loai, $total_page, $where_search;
    
    $loai = $d->array_category(0, '', $_GET['loaitin'], 3);

    if ($_REQUEST['a'] == 'man') {
        if (isset($_GET['search']) and $_GET['key'] != '' and $_GET['search'] != '') {
            if ($_GET['search'] == 'loai') {
                $id_code = $_GET['key'];
                $list_id = $id_code . CategoryModel::query()->getChildrenIds($id_code);
                $where_search = " and id_loai in ($list_id)";
                $loai = $d->array_category(0, '', $id_code, 4);
            } else {
                $col = addslashes($_GET['search']);
                $value = addslashes($_GET['key']);
                $where_search = " and $col like '%" . $value . "%' ";
            }
        }

        $limit = 12;
        $items = $d->o_fet("select * from #_sanpham where lang ='" . LANG . "' $where_search order by so_thu_tu asc, cap_nhat desc, id desc limit 0, $limit");
        $total_records = $d->num_rows("select * from #_sanpham where lang ='" . LANG . "' $where_search order by so_thu_tu asc, cap_nhat desc, id desc");
        $total_page = ceil($total_records / $limit);
    } else {
        if (isset($_REQUEST['id'])) {
            $id = addslashes($_REQUEST['id']);
            $items = $d->o_fet("select * from #_sanpham where id_code = '" . $id . "'");
            $loai = $d->array_category(0, '', $items[0]['id_loai'], 4);
            if (!empty($items[0]['product_attributes'])) {
                $product_attributes = json_decode($items[0]['product_attributes'], true);
            } else {
                $product_attributes = [];
            }
        }
    }
}

function luudulieu($id_module)
{
    global $d, $link_option, $arrr_setting;
    
    if ($arrr_setting['option_resize'] != '') {
        $option_resize = $arrr_setting['option_resize'];
    } else {
        $option_resize = "auto";
    }
    
    if ($d->checkPermission_edit($id_module) == 1) {
        $id = (isset($_REQUEST['id'])) ? addslashes($_REQUEST['id']) : "";
        $id_sp = $id ? intval($id) : 0;
        $file_name = $d->fns_Rand_digit(0, 9, 12);

        try {
            $d->db->beginTransaction();

            // Validate và xử lý product_attributes
            $product_attributes = isset($_POST['product_attributes']) && is_array($_POST['product_attributes']) ? $_POST['product_attributes'] : [];
            $processed_attributes = [];

            foreach ($product_attributes as $attr) {
                if (empty($attr['attr'])) {
                    continue;
                }
                if (!isset($attr['values']) || !is_array($attr['values'])) {
                    $attr['values'] = [];
                }

                $attr_id = $attr['attr'];
                $is_new_attr = isset($attr['is_new']) && $attr['is_new'] == 1;
                $new_attr_name = isset($attr['new_attr_name']) ? $d->clear($attr['new_attr_name']) : '';

                if ($is_new_attr && $new_attr_name) {
                    // Kiểm tra xem thuộc tính mới đã tồn tại chưa
                    $existing_attr = $d->simple_fetch("SELECT id_code FROM #_thuoctinh WHERE ten = '$new_attr_name' AND id_sanpham = $id_sp AND lang = '".LANG."'");
                    if (!$existing_attr) {
                        // Lưu thuộc tính mới vào cf_parent
                        $data0 = [
                            'ten' => $new_attr_name,
                            'type' => 4
                        ];
                        $d->reset();
                        $d->setTable('cf_parent');
                        $attr_id = $d->insert($data0);

                        if ($attr_id) {
                            // Lưu vào #_thuoctinh
                            foreach (get_json('lang') as $lang) {
                                $data_attr = [
                                    'id_code' => $attr_id,
                                    'ten' => $new_attr_name,
                                    'alias' => $d->createAlias($new_attr_name),
                                    'lang' => $lang['code'],
                                    'id_sanpham' => $id_sp,
                                    'loai' => 'select',
                                    'sap_xep' => 'ten'
                                ];
                                $d->reset();
                                $d->setTable('#_thuoctinh');
                                $d->insert($data_attr);
                            }
                        }
                    } else {
                        $attr_id = $existing_attr['id_code'];
                    }
                }

                // Kiểm tra thuộc tính hợp lệ
                if ($attr_id !== 'new') {
                    $attr_check = $d->simple_fetch("SELECT id_code FROM #_thuoctinh WHERE id_code = ".(int)$attr_id." AND (id_sanpham = 0 OR id_sanpham = $id_sp)");
                    if (!$attr_check) {
                        $d->db->rollBack();
                        $d->alert("Thuộc tính không hợp lệ!");
                        $d->redirect("index.php?p=san-pham&a=man" . $link_option);
                        return;
                    }
                }

                // Xử lý giá trị thuộc tính
                $processed_values = [];
                $new_values = isset($attr['new_values']) && is_array($attr['new_values']) ? array_filter(array_map('trim', $attr['new_values'])) : [];
                foreach ($new_values as $new_value) {
                    if ($new_value) {
                        $existing_value = $d->simple_fetch("SELECT id_code FROM #_thuoctinh_giatri WHERE ten = '$new_value' AND id_thuoctinh = ".(int)$attr_id." AND id_sanpham = $id_sp AND lang = '".LANG."'");
                        if (!$existing_value) {
                            // Lưu giá trị mới vào cf_parent
                            $data0 = [
                                'ten' => $new_value,
                                'type' => 5
                            ];
                            $d->reset();
                            $d->setTable('cf_parent');
                            $value_id = $d->insert($data0);

                            if ($value_id) {
                                // Lưu vào #_thuoctinh_giatri
                                foreach (get_json('lang') as $lang) {
                                    $data_value = [
                                        'id_code' => $value_id,
                                        'id_thuoctinh' => (int)$attr_id,
                                        'ten' => $new_value,
                                        'alias' => $d->createAlias($new_value),
                                        'lang' => $lang['code'],
                                        'id_sanpham' => $id_sp,
                                        'gia_tri' => $new_value
                                    ];
                                    $d->reset();
                                    $d->setTable('#_thuoctinh_giatri');
                                    $d->insert($data_value);
                                }
                                $processed_values[] = $value_id;
                            }
                        } else {
                            $processed_values[] = $existing_value['id_code'];
                        }
                    }
                }

                // Kết hợp giá trị có sẵn và mới
                $processed_values = array_merge($processed_values, array_filter($attr['values'], function($val) {
                    // Dùng strpos thay cho str_starts_with để tương thích với PHP < 8.0
                    return strpos($val, 'new_') !== 0; 
                }));
                $processed_attributes[] = [
                    'attr' => $attr_id,
                    'values' => $processed_values
                ];
            }

            // --- 1. PREPARE COMMON DATA ---
            $product_attributes_json = json_encode($processed_attributes, JSON_UNESCAPED_UNICODE);

            $id_loai   = isset($_POST['id_loai']) ? (int)addslashes($_POST['id_loai']) : 0;
            $id_price  = isset($_POST['id_price']) ? (int)addslashes($_POST['id_price']) : 0;

            $data0['ten'] = addslashes($_POST['ten'][0]);
            $alias0       = addslashes($_POST['alias'][0]);

            // Đảm bảo alias duy nhất cho cf_parent
            $base_alias0 = $alias0;
            while ($d->checkLink($alias0, $id) == 0) {
                $alias0 = $base_alias0 . "-" . rand(10, 999);
            }

            // --- 2. HANDLE FILE UPLOAD ---
            $hinh_anh = addslashes($_POST['hinh_anh']);
            $file_dw = null;
            if ($file_download = Uploadfile("file_download", 'file', '../Uploads/files/', $alias0)) {
                if ($id != '') { // Nếu là cập nhật, xóa file cũ
                    $file = $d->o_fet("select file from #_sanpham where id_code = '" . $id . "'");
                    if (!empty($file) && !empty($file[0]['file'])) {
                        @unlink('../Uploads/files/' . $file[0]['file']);
                    }
                }
                $file_dw = $file_download;
            }

            // --- 3. SAVE CF_PARENT (Master ID) ---
            $d->reset();
            $d->setTable('cf_parent');

            if ($id != '') {
                $d->setWhere('id', $id);
                $action_success = $d->update($data0);
                $id_code = $id;
            } else {
                $action_success = $d->insert($data0);
                $id_code = $action_success;
            }

            // --- 4. DETAILS, IMAGES, VARIANTS, LOCALE ---
            if ($action_success && $id_code) {
                // Upload hình album
                $arr_img = isset($_POST['album']) ? $_POST['album'] : [];
                if (count($arr_img) > 0) {
                    foreach ($arr_img as $i => $img) {
                        $data_img = [
                            'id_sp' => $id_code,
                            'hinh_anh' => $img,
                            'stt' => $i,
                            'title' => ''
                        ];
                        $d->reset();
                        $d->setTable('#_sanpham_hinhanh');
                        $d->insert($data_img);
                    }
                }

                // Lưu biến thể
                if (isset($_POST['variants']) && is_array($_POST['variants'])) {
                    save_variants($id_code, $_POST['variants'], $d);
                } else {
                    delete_variants_by_product($id_code, $d);
                }

                // Xử lý đa ngôn ngữ (#_sanpham)
                foreach (get_json('lang') as $key => $value) {
                    $data = [
                        'id_loai'   => $id_loai,
                        'id_price'  => $id_price,
                        'video'     => $d->clear(addslashes($_POST['ma_video'])),
                        'link_khac' => $d->clear(addslashes($_POST['link_khac'])),
                        'ten'       => $d->clear(addslashes($_POST['ten'][$key])),
                        'slug'      => addslashes($_POST['slug'][$key]),
                        'mo_ta'     => $d->clear(addslashes($_POST['mo_ta'][$key])),
                        'noi_dung'  => $d->clear(addslashes($_POST['noi_dung'][$key])),
                        'title'     => $d->clear(addslashes($_POST['title'][$key])),
                        'dvt'       => $d->clear(addslashes($_POST['dvt'][$key])),
                        'keyword'   => $d->clear(addslashes($_POST['keyword'][$key])),
                        'des'       => addslashes($_POST['des'][$key]),
                        'seo_head'  => addslashes($_POST['seo_head'][$key]),
                        'seo_body'  => addslashes($_POST['seo_body'][$key]),
                        'hien_thi'  => isset($_POST['hien_thi']) ? 1 : 0,
                        'sp_moi'    => isset($_POST['sp_moi']) ? 1 : 0,
                        'sp_hot'    => isset($_POST['sp_hot']) ? 1 : 0,
                        'sp_sale'   => isset($_POST['sp_sale']) ? 1 : 0,
                        'sp_top'    => isset($_POST['sp_top']) ? 1 : 0,
                        'tieu_bieu' => isset($_POST['tieu_bieu']) ? 1 : 0,
                        'nofollow'  => isset($_POST['nofollow']) ? 1 : 0,
                        'noindex'   => isset($_POST['noindex']) ? 1 : 0,
                        'so_thu_tu' => $_POST['so_thu_tu'] != '' ? (int)$_POST['so_thu_tu'] : 0,
                        'ma_sp'     => $d->clear(addslashes($_POST['ma_sp'])),
                        'da_ban'    => $d->clear(addslashes($_POST['da_ban'])),
                        'gia'            => (int)$d->clear(addslashes($_POST['gia'])),
                        'khuyen_mai'     => (int)$d->clear(addslashes($_POST['khuyen_mai'])),
                        'weight'         => (float)$d->clear(addslashes($_POST['weight'])), // New weight field (grams)
                        'gia_flash_sale' => (int)$d->clear(addslashes($_POST['gia_flash_sale'])),
                        'flash_sale'     => (int)$d->clear(addslashes($_POST['flash_sale'])),
                        'product_attributes' => $product_attributes_json,
                    ];

                    if ($file_dw !== null) {
                        $data['file'] = $file_dw;
                    }
                    if ($_POST['link_khac'] != '') {
                        $data['loai_file'] = addslashes($_POST['loai_file']);
                    }
                    if ($hinh_anh != '') {
                        $data['hinh_anh'] = $hinh_anh;
                    }
                    if (isset($_POST['noi_dung_1'])) $data['noi_dung_1'] = $d->clear(addslashes($_POST['noi_dung_1'][$key]));
                    if (isset($_POST['noi_dung_2'])) $data['noi_dung_2'] = $d->clear(addslashes($_POST['noi_dung_2'][$key]));

                    // Xử lý thông số kỹ thuật (thong_so_kt)
                    if (isset($_POST['nameinfo_' . $value['code']])) {
                        $arr_info = array();
                        foreach ($_POST['nameinfo_' . $value['code']] as $i => $items) {
                            $detail_info = $_POST['detailinfo_' . $value['code']][$i];
                            if ($items != '' && $detail_info != '') {
                                array_push($arr_info, $items . '%%%' . $detail_info);
                            }
                        }
                        if ($_POST['nameinfo_' . $value['code']][0] == '' && count($arr_info) == 1) {
                            $data['thong_so_kt'] = "";
                        } else {
                            $data['thong_so_kt'] = addslashes(json_encode($arr_info));
                        }
                    }

                    // Xử lý alias riêng cho ngôn ngữ này (duy nhất)
                    $item_alias = $d->clear(addslashes($_POST['alias'][$key]));
                    $item_alias = $d->createAlias($item_alias);
                    $deta_id = isset($_POST['id_row'][$key]) ? $_POST['id_row'][$key] : "";
                    
                    $base_item_alias = $item_alias;
                    while ($d->checkLink($item_alias, $deta_id) == 0) {
                        $item_alias = $base_item_alias . "-" . rand(10, 999);
                    }
                    $data['alias'] = $item_alias;

                    // Lưu vào database
                    $d->reset();
                    $d->setTable('#_sanpham');

                    if ($id != '') { // Update
                        $data['cap_nhat'] = time();
                        $d->setWhere('id', $_POST['id_row'][$key]);
                        $d->update($data);
                    } else { // Insert
                        $data['id_code']   = $id_code;
                        $data['lang']      = $value['code'];
                        $data['ngay_dang'] = time();
                        $data['cap_nhat']  = time();
                        $d->insert($data);
                    }
                }

                // Redirect
                if (isset($_POST['capnhat']) && $_POST['capnhat'] == 'capnhat_sua') {
                    $d->db->commit();
                    $d->redirect("index.php?p=san-pham&a=edit&id=" . $id_code . $link_option);
                } else {
                    $d->db->commit();
                    $d->redirect("index.php?p=san-pham&a=man" . $link_option);
                }
            } else {
                $d->db->rollBack();
                $d->alert("Thao tác dữ liệu bị lỗi!");
                $d->redirect("index.php?p=san-pham&a=man" . $link_option);
            }

        } catch (Exception $e) {
            $d->db->rollBack();
            $d->alert("Lỗi cơ sở dữ liệu: " . addslashes($e->getMessage()));
            $d->redirect("index.php?p=san-pham&a=man" . $link_option);
        }

    } else {
        $d->redirect("index.php?p=san-pham&a=man" . $link_option);
    }
}

function xoadulieu($id_module)
{
    global $d, $link_option;

    if ($d->checkPermission_dele($id_module) == 1) {
        if (!empty($_GET['id'])) {
            $id = (int)$_GET['id'];

            delete_variants_by_product($id, $d);
            $d->o_que("DELETE FROM #_thuoctinh WHERE id_sanpham = $id");
            $d->o_que("DELETE FROM #_thuoctinh_giatri WHERE id_sanpham = $id");

            $d->reset();
            $d->setTable('#_sanpham');
            $d->setWhere('id_code', $id);
            if ($d->delete()) {
                $d->o_que("DELETE FROM cf_parent WHERE id = $id");
                $d->o_que("DELETE FROM #_sanpham_hinhanh WHERE id_sp = $id");
                $d->redirect("index.php?p=san-pham&a=man" . $link_option);
            } else {
                $d->alert("Xóa dữ liệu bị lỗi!");
                $d->redirect("index.php?p=san-pham&a=man" . $link_option);
            }
        } else {
            $d->alert("Không nhận được dữ liệu!");
            $d->redirect("index.php?p=san-pham&a=man" . $link_option);
        }
    } else {
        $d->redirect("index.php?p=san-pham&a=man" . $link_option);
    }
}

function xoadulieu_mang($id_module)
{
    global $d, $link_option;

    if ($d->checkPermission_dele($id_module) == 1) {
        if (isset($_POST['chk_child']) && !empty($_POST['chk_child'])) {
            $chuoi = implode(',', array_map('intval', $_POST['chk_child']));

            foreach ($_POST['chk_child'] as $val) {
                delete_variants_by_product((int)$val, $d);
                $d->o_que("DELETE FROM #_thuoctinh WHERE id_sanpham = ".(int)$val);
                $d->o_que("DELETE FROM #_thuoctinh_giatri WHERE id_sanpham = ".(int)$val);
            }

            if ($d->o_que("DELETE FROM #_sanpham WHERE id_code IN ($chuoi)")) {
                $d->o_que("DELETE FROM cf_parent WHERE id IN ($chuoi)");
                $d->o_que("DELETE FROM #_sanpham_hinhanh WHERE id_sp IN ($chuoi)");
                $d->redirect("index.php?p=san-pham&a=man" . $link_option);
            } else {
                $d->alert("Xóa dữ liệu bị lỗi!");
                $d->redirect("index.php?p=san-pham&a=man" . $link_option);
            }
        } else {
            $d->alert("Không nhận được dữ liệu!");
            $d->redirect("index.php?p=san-pham&a=man" . $link_option);
        }
    } else {
        $d->redirect("index.php?p=san-pham&a=man" . $link_option);
    }
}
?>