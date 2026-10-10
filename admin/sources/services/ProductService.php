<?php
/**
 * ProductService
 *
 * Xử lý nghiệp vụ chính của sản phẩm: lưu, xóa, quản lý dữ liệu liên quan.
 * Mục tiêu: dần dần thay thế các hàm procedural trong san-pham.php.
 */
class ProductService
{
    /** @var object Database instance ($d) */
    protected $db;

    /** @var VariantService */
    protected $variantService;

    /** @var ProductRepository */
    protected $repo;

    public function __construct($db)
    {
        $this->db = $db;
        $this->variantService = new VariantService($db);
        $this->repo = new ProductRepository($db);
    }

    /**
     * Xóa một sản phẩm theo id_code, bao gồm cả dữ liệu liên quan.
     *
     * @param int $id_code
     * @return bool
     */
    public function delete($id_code)
    {
        $id_code = (int) $id_code;

        // Xóa biến thể
        $this->variantService->deleteByProduct($id_code);

        // Xóa thuộc tính và giá trị thuộc tính
        $this->db->safeExec("DELETE FROM #_thuoctinh WHERE id_sanpham = ?", [$id_code]);
        $this->db->safeExec("DELETE FROM #_thuoctinh_giatri WHERE id_sanpham = ?", [$id_code]);

        // Xóa sản phẩm
        if ($this->repo->delete($id_code)) {
            // Xóa các dữ liệu phụ
            $this->db->safeExec("DELETE FROM cf_parent WHERE id = ?", [$id_code]);
            $this->db->safeExec("DELETE FROM #_sanpham_hinhanh WHERE id_sp = ?", [$id_code]);
            return true;
        }

        return false;
    }

    /**
     * Xóa nhiều sản phẩm theo danh sách id_code.
     *
     * @param array $ids
     * @return bool
     */
    public function deleteMany(array $ids)
    {
        if (empty($ids)) {
            return false;
        }

        $ids = array_map('intval', $ids);

        foreach ($ids as $id) {
            $this->variantService->deleteByProduct($id);
            $this->db->safeExec("DELETE FROM #_thuoctinh WHERE id_sanpham = ?", [$id]);
            $this->db->safeExec("DELETE FROM #_thuoctinh_giatri WHERE id_sanpham = ?", [$id]);
        }

        $ids_str = implode(',', $ids);
        $this->db->safeExec("DELETE FROM #_sanpham WHERE id_code IN ($ids_str)", []);
        $this->db->safeExec("DELETE FROM cf_parent WHERE id IN ($ids_str)", []);
        $this->db->safeExec("DELETE FROM #_sanpham_hinhanh WHERE id_sp IN ($ids_str)", []);

        return true;
    }

    /**
     * Lưu sản phẩm (thêm mới hoặc cập nhật).
     *
     * @param int    $id_module
     * @param array  $post
     * @param array  $files
     * @param array  $request
     * @param string $link_option
     * @param array  $arrr_setting
     * @return void
     */
    /**
     * Xu ly va luu cac thuoc tinh san pham.
     *
     * @param array  $post
     * @param int    $id_sp
     * @param string $link_option
     * @return array|false
     */
    protected function processAttributes($post, $id_sp, $link_option)
    {
        // Validate và xử lý product_attributes
        $product_attributes = isset($post['product_attributes']) && is_array($post['product_attributes']) ? $post['product_attributes'] : [];
        $processed_attributes = [];
        // Ánh xạ temp ID (new_xxx / new) sang ID thật của thuộc tính và giá trị
        $temp_id_map = ['attrs' => [], 'values' => []];

        foreach ($product_attributes as $attr) {
            if (empty($attr['attr'])) {
                continue;
            }
            if (!isset($attr['values']) || !is_array($attr['values'])) {
                $attr['values'] = [];
            }

            $attr_id = $attr['attr'];
            $is_new_attr = isset($attr['is_new']) && $attr['is_new'] == 1;
            $new_attr_name = isset($attr['new_attr_name']) ? $this->db->clear($attr['new_attr_name']) : '';

            if ($is_new_attr && $new_attr_name) {
                // Kiểm tra xem thuộc tính mới đã tồn tại chưa
                $existing_attr = $this->db->simple_fetch("SELECT id_code FROM #_thuoctinh WHERE ten = '$new_attr_name' AND id_sanpham = $id_sp AND lang = '".LANG."'");
                if (!$existing_attr) {
                    // Lưu thuộc tính mới vào cf_parent
                    $data0 = [
                        'ten' => $new_attr_name,
                        'type' => 4
                    ];
                    $this->db->reset();
                    $this->db->setTable('cf_parent');
                    $attr_id = $this->db->insert($data0);

                    if ($attr_id) {
                        // Lưu vào #_thuoctinh
                        foreach (get_json('lang') as $lang) {
                            $data_attr = [
                                'id_code' => $attr_id,
                                'ten' => $new_attr_name,
                                'alias' => $this->db->createAlias($new_attr_name),
                                'lang' => $lang['code'],
                                'id_sanpham' => $id_sp,
                                'loai' => 'select',
                                'sap_xep' => 'ten'
                            ];
                            $this->db->reset();
                            $this->db->setTable('#_thuoctinh');
                            $this->db->insert($data_attr);
                        }
                    }
                } else {
                    $attr_id = $existing_attr['id_code'];
                }
                // Ánh xạ temp attr "new" sang ID thật để biến thể sử dụng
                $temp_id_map['attrs']['new'] = $attr_id;
            }

            // Kiểm tra thuộc tính hợp lệ
            if ($attr_id !== 'new') {
                $attr_check = $this->db->simple_fetch("SELECT id_code FROM #_thuoctinh WHERE id_code = ".(int)$attr_id." AND (id_sanpham = 0 OR id_sanpham = $id_sp)");
                if (!$attr_check) {
                    $this->db->db->rollBack();
                    $this->db->alert("Thuộc tính không hợp lệ!");
                    $this->db->redirect("index.php?p=san-pham&a=man" . $link_option);
                    return false;
                }
            }

            // Xử lý giá trị thuộc tính
            $processed_values = [];
            $new_values = isset($attr['new_values']) && is_array($attr['new_values']) ? array_filter($attr['new_values'], 'is_string') : [];
            foreach ($new_values as $temp_id => $new_value) {
                $new_value = trim($new_value);
                if ($new_value) {
                    $existing_value = $this->db->simple_fetch("SELECT id_code FROM #_thuoctinh_giatri WHERE ten = '$new_value' AND id_thuoctinh = ".(int)$attr_id." AND id_sanpham = $id_sp AND lang = '".LANG."'");
                    if (!$existing_value) {
                        // Lưu giá trị mới vào cf_parent
                        $data0 = [
                            'ten' => $new_value,
                            'type' => 5
                        ];
                        $this->db->reset();
                        $this->db->setTable('cf_parent');
                        $value_id = $this->db->insert($data0);

                        if ($value_id) {
                            // Lưu vào #_thuoctinh_giatri
                            foreach (get_json('lang') as $lang) {
                                $data_value = [
                                    'id_code' => $value_id,
                                    'id_thuoctinh' => (int)$attr_id,
                                    'ten' => $new_value,
                                    'alias' => $this->db->createAlias($new_value),
                                    'lang' => $lang['code'],
                                    'id_sanpham' => $id_sp,
                                    'gia_tri' => $new_value
                                ];
                                $this->db->reset();
                                $this->db->setTable('#_thuoctinh_giatri');
                                $this->db->insert($data_value);
                            }
                            $processed_values[] = $value_id;
                        }
                    } else {
                        $value_id = $existing_value['id_code'];
                        $processed_values[] = $value_id;
                    }
                    // Ánh xạ temp value ID (new_xxx) sang ID thật để biến thể sử dụng
                    if (!empty($temp_id) && is_string($temp_id)) {
                        $temp_id_map['values'][$attr_id][$temp_id] = $value_id;
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

        return [$processed_attributes, $temp_id_map];
    }

    /**
     * Luu album hinh anh cua san pham.
     *
     * @param int   $id_code
     * @param array $post
     * @return void
     */
    protected function saveAlbum($id_code, $post)
    {
        $arr_img = isset($post['album']) ? $post['album'] : [];
        if (count($arr_img) > 0) {
            foreach ($arr_img as $i => $img) {
                $data_img = [
                    'id_sp' => $id_code,
                    'hinh_anh' => $img,
                    'stt' => $i,
                    'title' => ''
                ];
                $this->db->reset();
                $this->db->setTable('#_sanpham_hinhanh');
                $this->db->insert($data_img);
            }
        }
    }

    /**
     * Luu thong tin da ngon ngu cua san pham.
     *
     * @param int    $id_code
     * @param string $id
     * @param array  $post
     * @param int    $id_loai
     * @param int    $ngayDang
     * @param string $hinh_anh
     * @param string $file_dw
     * @param string $product_attributes_json
     * @return void
     */
    protected function saveTranslations($id_code, $id, $post, $id_loai, $ngayDang, $hinh_anh, $file_dw, $product_attributes_json)
    {
            // Xử lý đa ngôn ngữ (#_sanpham)
            foreach (get_json('lang') as $key => $value) {
                $data = [
                    'id_loai'   => $id_loai,
                    'video'     => $this->db->clear(addslashes($post['ma_video'] ?? '')),
                    'link_khac'   => $this->db->clear(addslashes($post['link_khac'] ?? '')),
                    'link_shopee' => $this->db->clear(addslashes($post['link_shopee'] ?? '')),
                    'link_tiktok' => $this->db->clear(addslashes($post['link_tiktok'] ?? '')),
                    'ten'       => $this->db->clear(addslashes($post['ten'][$key] ?? '')),
                    'slug'      => addslashes($post['slug'][$key] ?? ''),
                    'mo_ta'     => $this->db->clear(addslashes($post['mo_ta'][$key] ?? '')),
                    'noi_dung'  => $this->db->clear(addslashes($post['noi_dung'][$key] ?? '')),
                    'title'     => $this->db->clear(addslashes($post['title'][$key] ?? '')),
                    'dvt'       => $this->db->clear(addslashes($post['dvt'][$key] ?? '')),
                    'keyword'   => $this->db->clear(addslashes($post['keyword'][$key] ?? '')),
                    'des'       => addslashes($post['des'][$key] ?? ''),
                    'seo_head'  => addslashes($post['seo_head'][$key] ?? ''),
                    'seo_body'  => addslashes($post['seo_body'][$key] ?? ''),
                    'hien_thi'  => isset($post['hien_thi']) ? 1 : 0,
                    'sp_moi'    => isset($post['sp_moi']) ? 1 : 0,
                    'sp_hot'    => isset($post['sp_hot']) ? 1 : 0,
                    'sp_sale'   => isset($post['sp_sale']) ? 1 : 0,
                    'sp_top'    => isset($post['sp_top']) ? 1 : 0,
                    'tieu_bieu' => isset($post['tieu_bieu']) ? 1 : 0,
                    'nofollow'  => isset($post['nofollow']) ? 1 : 0,
                    'noindex'   => isset($post['noindex']) ? 1 : 0,
                    'so_thu_tu' => isset($post['so_thu_tu']) && $post['so_thu_tu'] != '' ? (int)$post['so_thu_tu'] : 0,
                    'ma_sp'     => $this->db->clear(addslashes($post['ma_sp'] ?? '')),
                    'da_ban'    => $this->db->clear(addslashes($post['da_ban'] ?? '')),
                    'gia'            => (int)$this->db->clear(addslashes($post['gia'] ?? '')),
                    'khuyen_mai'     => (int)$this->db->clear(addslashes($post['khuyen_mai'] ?? '')),
                    'so_luong'       => isset($post['so_luong']) ? (int)$post['so_luong'] : 0,
                    'id_price'       => 0, // Mặc định để tránh lỗi MySQL Strict Mode
                    'weight'         => (float)$this->db->clear(addslashes($post['weight'] ?? '')), // New weight field (grams)
                    'product_attributes' => $product_attributes_json,
                ];

                if ($file_dw !== null) {
                    $data['file'] = $file_dw;
                }
                if ($hinh_anh != '') {
                    $data['hinh_anh'] = $hinh_anh;
                }

                // Xử lý alias riêng cho ngôn ngữ này (duy nhất)
                $item_alias = $this->db->clear(addslashes($post['alias'][$key] ?? ''));
                $item_alias = $this->db->createAlias($item_alias);
                $deta_id = isset($post['id_row'][$key]) ? $post['id_row'][$key] : "";
                
                $base_item_alias = $item_alias;
                while ($this->db->checkLink($item_alias, $deta_id) == 0) {
                    $item_alias = $base_item_alias . "-" . rand(10, 999);
                }
                $data['alias'] = $item_alias;

                // Lưu vào database
                $this->db->reset();
                $this->db->setTable('#_sanpham');

                if ($id != '') { // Update
                    if ($ngayDang) {
                        $data['ngay_dang'] = $ngayDang; // Bỏ trống: giữ nguyên ngày đăng hiện tại
                    }
                    $data['cap_nhat'] = time();
                    $rowIdSp = isset($post['id_row'][$key]) ? $post['id_row'][$key] : 0;
                    $this->db->setWhere('id', $rowIdSp);
                    $this->db->update($data);
                    // Đồng bộ URL trên menu (chỉ đường dẫn)
                    if (!empty($rowIdSp) && !empty($item_alias) && function_exists('sync_menu_item_url')) {
                        sync_menu_item_url('sanpham', (int) $rowIdSp, $item_alias);
                    }
                } else { // Insert
                    $data['id_code']   = $id_code;
                    $data['lang']      = $value['code'];
                    $data['ngay_dang'] = $ngayDang ?: time(); // Bỏ trống: lấy giờ hiện tại
                    $data['cap_nhat']  = time();
                    $this->db->insert($data);
                }
            }
    }

    /**
     * Xử lý upload file tải về (file_download).
     * Trả về tên file lưu trong media/files/ hoặc null nếu không có upload.
     */
    protected function handleFileDownload($files, $id, $alias)
    {
        if (empty($files['file_download']['tmp_name'])) {
            return null;
        }

        $file = $files['file_download'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'txt'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        $uploadDir = dirname(dirname(dirname(__DIR__))) . '/media/files/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $alias) . '-' . time() . '.' . $ext;
        $destPath = $uploadDir . $safeName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            return $safeName;
        }

        return null;
    }

    public function save($id_module, $post, $files, $request, $link_option, $arrr_setting)
    {
        if ($this->db->checkPermission_edit($id_module) != 1) {
            $this->db->redirect("index.php?p=san-pham&a=man" . $link_option);
            return;
        }

        try {
            $this->db->db->beginTransaction();
            $this->executeSave($post, $files, $request, $link_option);
        } catch (Exception $e) {
            $this->db->db->rollBack();
            $this->db->alert("Lỗi cơ sở dữ liệu: " . addslashes($e->getMessage()));
            $this->db->redirect("index.php?p=san-pham&a=man" . $link_option);
        }
    }

    /**
     * Lưu bản ghi master trong bảng cf_parent (trả về array(success, id_code)).
     */
    protected function saveMasterRecord($data0, $id)
    {
        $action_success = false;
        $id_code = 0;

        $this->db->reset();
        $this->db->setTable('cf_parent');

        if ($id != '') {
            $this->db->setWhere('id', $id);
            if ($this->db->update($data0)) {
                $action_success = true;
                $id_code = (int) $id;
            }
        } else {
            $data0['type'] = 4;
            if ($id_code = $this->db->insert($data0)) {
                $action_success = true;
                $id_code = (int) $id_code;
            }
        }

        return array($action_success, $id_code);
    }

    protected function executeSave($post, $files, $request, $link_option)
    {
        $id = (isset($request['id'])) ? addslashes($request['id']) : "";
        $id_sp = $id ? intval($id) : 0;

        $processResult = $this->processAttributes($post, $id_sp, $link_option);
        if ($processResult === false) {
            return;
        }
        list($processed_attributes, $temp_id_map) = $processResult;
        $product_attributes_json = json_encode($processed_attributes, JSON_UNESCAPED_UNICODE);

        $id_loai   = isset($post['id_loai']) ? (int)addslashes($post['id_loai']) : 0;
        $ngayDang  = !empty($post['ngay_dang']) ? strtotime($post['ngay_dang']) : 0;

        $data0['ten'] = addslashes($post['ten'][0] ?? '');
        $alias0       = addslashes($post['alias'][0] ?? '');

        $base_alias0 = $alias0;
        while ($this->db->checkLink($alias0, $id) == 0) {
            $alias0 = $base_alias0 . "-" . rand(10, 999);
        }

        $hinh_anh = addslashes($post['hinh_anh'] ?? '');
        $file_dw = $this->handleFileDownload($files, $id, $alias0);

        list($action_success, $id_code) = $this->saveMasterRecord($data0, $id);

        if (!$action_success || !$id_code) {
            $this->db->db->rollBack();
            $this->db->alert("Thao tÃ¡c dá»¯ liá»‡u bá»‹ lá»—i!");
            $this->db->redirect("index.php?p=san-pham&a=man" . $link_option);
            return;
        }

        $this->saveProductDetails(
            $id_code,
            $id,
            $post,
            $id_loai,
            $ngayDang,
            $hinh_anh,
            $file_dw,
            $product_attributes_json,
            $temp_id_map
        );

        $this->redirectAfterSave($post, $id_code, $link_option);
    }

    protected function saveProductDetails($id_code, $id, $post, $id_loai, $ngayDang, $hinh_anh, $file_dw, $product_attributes_json, $temp_id_map = [])
    {
        $this->saveAlbum($id_code, $post);

        if (isset($post['variants']) && is_array($post['variants'])) {
            $this->variantService->saveVariants($id_code, $post['variants'], $temp_id_map);
        } else {
            $this->variantService->deleteByProduct($id_code);
        }

        $this->saveTranslations(
            $id_code,
            $id,
            $post,
            $id_loai,
            $ngayDang,
            $hinh_anh,
            $file_dw,
            $product_attributes_json
        );
    }

    protected function redirectAfterSave($post, $id_code, $link_option)
    {
        if (isset($post['capnhat']) && $post['capnhat'] == 'capnhat_sua') {
            $this->db->db->commit();
            $this->db->redirect("index.php?p=san-pham&a=edit&id=" . $id_code . $link_option);
        } else {
            $this->db->db->commit();
            $this->db->redirect("index.php?p=san-pham&a=man" . $link_option);
        }
    }


}
