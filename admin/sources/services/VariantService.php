<?php
/**
 * VariantService
 *
 * Xử lý toàn bộ nghiệp vụ liên quan đến biến thể sản phẩm:
 * - Lưu/cập nhật danh sách biến thể
 * - Xóa biến thể theo sản phẩm
 * - Quản lý thuộc tính của biến thể
 */
class VariantService
{
    /** @var object Database instance ($d) */
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Lưu danh sách biến thể của một sản phẩm.
     * Các biến thể cũ không còn trong danh sách sẽ bị xóa.
     *
     * @param int   $id_code  id_code của sản phẩm
     * @param array $variants Mảng biến thể từ form
     * @return void
     */
    public function saveVariants($id_code, array $variants, array $temp_id_map = [])
    {
        if (empty($variants)) {
            return;
        }

        $id_code = (int) $id_code;

        // Lấy danh sách ID biến thể cũ từ DB
        $old_variants = $this->db->safeFetch(
            "SELECT id FROM #_sanpham_bienthe WHERE id_sanpham = ?",
            [$id_code]
        );
        $old_ids = array_column($old_variants, 'id');
        $submitted_ids = [];

        foreach ($variants as $variant) {
            $variant_id = isset($variant['id']) ? (int) $variant['id'] : 0;

            $data_bienthe = [
                'id_sanpham' => $id_code,
                'ma_sp'      => isset($variant['ma_sp']) ? trim($variant['ma_sp']) : '',
                'gia'        => isset($variant['gia']) ? (int) $variant['gia'] : 0,
                'khuyen_mai' => isset($variant['khuyen_mai']) ? (int) $variant['khuyen_mai'] : 0,
                'so_luong'   => isset($variant['so_luong']) ? (int) $variant['so_luong'] : 0,
                'hinh_anh'   => isset($variant['hinh_anh']) ? trim($variant['hinh_anh']) : '',
                'weight'     => isset($variant['weight']) ? (float) $variant['weight'] : 0,
                'hien_thi'   => 1,
                'cap_nhat'   => date("Y-m-d H:i:s"),
            ];

            if (isset($variant['pancake_variation_id'])) {
                $data_bienthe['pancake_variation_id'] = $variant['pancake_variation_id'];
            }

            $id_bienthe = 0;

            if ($variant_id > 0 && in_array($variant_id, $old_ids)) {
                // Cập nhật biến thể cũ
                $this->db->reset();
                $this->db->setTable('#_sanpham_bienthe');
                $this->db->setWhere('id', $variant_id);
                if ($this->db->update($data_bienthe)) {
                    $id_bienthe = $variant_id;
                    $submitted_ids[] = $variant_id;

                    // Xóa thuộc tính nối cũ của biến thể này để insert lại
                    $this->db->safeExec(
                        "DELETE FROM #_sanpham_bienthe_thuoctinh WHERE id_bienthe = ?",
                        [$id_bienthe]
                    );
                }
            } else {
                // Thêm biến thể mới
                $data_bienthe['ngay_dang'] = date("Y-m-d H:i:s");
                $this->db->reset();
                $this->db->setTable('#_sanpham_bienthe');
                $id_bienthe = $this->db->insert($data_bienthe);
                if ($id_bienthe) {
                    $submitted_ids[] = $id_bienthe;
                }
            }

            // Lưu thuộc tính của biến thể (chuyển temp ID sang ID thật)
            if ($id_bienthe && !empty($variant['attributes']) && is_array($variant['attributes'])) {
                $resolvedAttributes = $this->resolveTempVariantAttributes($variant['attributes'], $temp_id_map);
                $this->saveVariantAttributes($id_bienthe, $resolvedAttributes);
            }
        }

        // Xóa các biến thể cũ không còn trong danh sách gửi lên
        $ids_to_delete = array_diff($old_ids, $submitted_ids);
        if (!empty($ids_to_delete)) {
            $this->deleteVariantsByIds($ids_to_delete);
        }
    }

    /**
     * Chuyển đổi temp ID (new_xxx / new) trong thuộc tính biến thể sang ID thật.
     *
     * @param array $attributes  Mảng [thuoctinh_code => giatri_code]
     * @param array $temp_id_map Ánh xạ temp ID từ ProductService::processAttributes
     * @return array
     */
    protected function resolveTempVariantAttributes(array $attributes, array $temp_id_map)
    {
        $resolved = [];
        foreach ($attributes as $thuoctinh_code => $giatri_code) {
            $real_thuoctinh = $thuoctinh_code;
            $real_giatri = $giatri_code;

            // Nếu thuộc tính là "new", chuyển sang ID thật
            if ($thuoctinh_code === 'new' && isset($temp_id_map['attrs']['new'])) {
                $real_thuoctinh = $temp_id_map['attrs']['new'];
            }

            // Nếu giá trị là temp ID (new_xxx), chuyển sang ID thật
            if (is_string($giatri_code) && strpos($giatri_code, 'new_') === 0) {
                $lookup_attr = $real_thuoctinh;
                if (isset($temp_id_map['values'][$lookup_attr][$giatri_code])) {
                    $real_giatri = $temp_id_map['values'][$lookup_attr][$giatri_code];
                } elseif (isset($temp_id_map['values'][$thuoctinh_code][$giatri_code])) {
                    // Thử với cả thuộc tính temp gốc nếu real_thuoctinh chưa có mapping
                    $real_giatri = $temp_id_map['values'][$thuoctinh_code][$giatri_code];
                } else {
                    $real_giatri = 0;
                }
            }

            if (!empty($real_thuoctinh) && !empty($real_giatri)) {
                $resolved[$real_thuoctinh] = $real_giatri;
            }
        }
        return $resolved;
    }

    /**
     * Lưu thuộc tính cho một biến thể.
     *
     * @param int   $id_bienthe ID biến thể
     * @param array $attributes Mảng [thuoctinh_code => giatri_code]
     * @return void
     */
    protected function saveVariantAttributes($id_bienthe, array $attributes)
    {
        foreach ($attributes as $thuoctinh_code => $giatri_code) {
            if (empty($giatri_code)) {
                continue;
            }

            $data_thuoctinh = [
                'id_bienthe'          => (int) $id_bienthe,
                'id_thuoctinh'        => (int) $thuoctinh_code,
                'id_thuoctinh_giatri' => (int) $giatri_code,
            ];

            $this->db->reset();
            $this->db->setTable('#_sanpham_bienthe_thuoctinh');
            $this->db->insert($data_thuoctinh);
        }
    }

    /**
     * Xóa nhiều biến thể theo danh sách ID.
     *
     * @param array $ids Mảng ID biến thể cần xóa
     * @return void
     */
    public function deleteVariantsByIds(array $ids)
    {
        if (empty($ids)) {
            return;
        }

        $ids = array_map('intval', $ids);
        $ids_str = implode(',', $ids);

        $this->db->safeExec(
            "DELETE FROM #_sanpham_bienthe_thuoctinh WHERE id_bienthe IN ($ids_str)",
            []
        );
        $this->db->safeExec(
            "DELETE FROM #_sanpham_bienthe WHERE id IN ($ids_str)",
            []
        );
    }

    /**
     * Xóa tất cả biến thể của một sản phẩm.
     *
     * @param int $id_code id_code của sản phẩm
     * @return void
     */
    public function deleteByProduct($id_code)
    {
        $id_code = (int) $id_code;

        // Xóa tất cả thuộc tính của biến thể
        $this->db->safeExec(
            "DELETE FROM #_sanpham_bienthe_thuoctinh WHERE id_bienthe IN (SELECT id FROM #_sanpham_bienthe WHERE id_sanpham = ?)",
            [$id_code]
        );

        // Xóa tất cả biến thể
        $this->db->safeExec(
            "DELETE FROM #_sanpham_bienthe WHERE id_sanpham = ?",
            [$id_code]
        );
    }
}
