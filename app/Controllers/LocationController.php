<?php

namespace App\Controllers;

use App\Core\Response;

/**
 * LocationController
 * Xử lý API địa chỉ: Tỉnh/Thành, Quận/Huyện, Phường/Xã.
 * Migrate từ: sources/ajax/ajax.php (action: get_huyen, get_xa)
 */
class LocationController extends Controller {

    /**
     * Lấy danh sách Quận/Huyện theo mã Tỉnh (AJAX)
     * POST /ajax/location/district
     */
    public function district($request) {
        $code_tinh = trim($request->input('code_tinh', ''));

        if (empty($code_tinh)) {
            return Response::json(['html' => '<option value="">Chọn Quận / Huyện</option>']);
        }

        $rows = \DistrictModel::where('code_tinh', $code_tinh)
                              ->orderBy('ten', 'ASC')
                              ->get();

        $html = '<option value="">Chọn Quận / Huyện</option>';
        foreach ((array) $rows as $row) {
            $html .= '<option value="' . e($row->code) . '">' . e($row->ten) . '</option>';
        }

        // Trả về HTML trực tiếp để tương thích ngược với JS cũ
        return new \App\Core\Response($html);
    }

    /**
     * Lấy danh sách Phường/Xã theo mã Quận/Huyện (AJAX)
     * POST /ajax/location/ward
     */
    public function ward($request) {
        $code_huyen = trim($request->input('code_huyen', ''));

        if (empty($code_huyen)) {
            return new \App\Core\Response('<option value="">Chọn Phường / Xã</option>');
        }

        $rows = \WardModel::where('code_huyen', $code_huyen)
                          ->orderBy('ten', 'ASC')
                          ->get();

        $html = '<option value="">Chọn Phường / Xã</option>';
        foreach ((array) $rows as $row) {
            $html .= '<option value="' . e($row->code) . '">' . e($row->ten) . '</option>';
        }

        return new \App\Core\Response($html);
    }
}
