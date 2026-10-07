<?php
/**
 * ProductRepository
 *
 * Tập trung các thao tác truy vấn database liên quan đến sản phẩm.
 * Mục tiêu: giảm lặp code và dễ dàng chuyển sang prepared statement sau này.
 */
class ProductRepository
{
    /** @var object Database instance ($d) */
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Tìm sản phẩm theo id_code.
     *
     * @param int $id_code
     * @return array
     */
    public function findById($id_code)
    {
        return $this->db->safeFetchOne(
            "SELECT * FROM #_sanpham WHERE id_code = ? LIMIT 1",
            [(int) $id_code]
        );
    }

    /**
     * Tìm sản phẩm theo id_code và ngôn ngữ.
     *
     * @param int    $id_code
     * @param string $lang
     * @return array
     */
    public function findByIdAndLang($id_code, $lang)
    {
        return $this->db->safeFetchOne(
            "SELECT * FROM #_sanpham WHERE id_code = ? AND lang = ? LIMIT 1",
            [(int) $id_code, $lang]
        );
    }

    /**
     * Đếm số sản phẩm theo điều kiện tìm kiếm.
     *
     * @param string $where
     * @param string $lang
     * @return int
     */
    public function count($where = '', $lang = '')
    {
        $lang = $lang ?: (defined('LANG') ? LANG : '');
        $sql = "SELECT count(*) AS total FROM #_sanpham WHERE 1=1 AND lang = '" . addslashes($lang) . "' " . $where;
        $row = $this->db->safeFetchOne($sql, []);
        return isset($row['total']) ? (int) $row['total'] : 0;
    }

    /**
     * Lấy danh sách sản phẩm có phân trang.
     *
     * @param string $where
     * @param int    $start
     * @param int    $limit
     * @param string $lang
     * @return array
     */
    public function getList($where = '', $start = 0, $limit = 12, $lang = '')
    {
        $lang = $lang ?: (defined('LANG') ? LANG : '');
        $sql = "SELECT * FROM #_sanpham WHERE 1=1 AND lang = '" . addslashes($lang) . "' {$where} "
             . "ORDER BY so_thu_tu ASC, id DESC "
             . "LIMIT " . (int) $start . ", " . (int) $limit;

        return $this->db->safeFetch($sql, []);
    }

    /**
     * Lấy tên file download của sản phẩm theo id_code.
     *
     * @param int $id_code
     * @return string|null
     */
    public function findFileById($id_code)
    {
        $row = $this->db->safeFetchOne(
            "SELECT file FROM #_sanpham WHERE id_code = ? LIMIT 1",
            [(int) $id_code]
        );
        return isset($row['file']) ? $row['file'] : null;
    }

    /**
     * Xóa sản phẩm theo id_code.
     *
     * @param int $id_code
     * @return bool
     */
    public function delete($id_code)
    {
        $this->db->reset();
        $this->db->setTable('#_sanpham');
        $this->db->setWhere('id_code', (int) $id_code);
        return $this->db->delete();
    }
}
