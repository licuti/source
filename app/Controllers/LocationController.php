<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\LocationModel;

/**
 * LocationController
 * Xử lý API địa chỉ: Tỉnh/Thành, Quận/Huyện, Phường/Xã cho Frontend.
 * Tương thích ngược: Nhận input code_tinh, code_huyen và trả về chuỗi HTML.
 */
class LocationController extends Controller {

    /**
     * Lấy danh sách Quận/Huyện theo mã Tỉnh (AJAX)
     * POST /ajax/location/district
     */
    public function district(Request $request) {
        $code_tinh = trim($request->input('code_tinh', ''));

        if (empty($code_tinh)) {
            return new Response('<option value="">Chọn Quận / Huyện</option>');
        }

        $province = LocationModel::where('type', 'province')->where('code', $code_tinh)->first();
        
        $html = '<option value="">Chọn Quận / Huyện</option>';
        if ($province) {
            $rows = LocationModel::where('type', 'district')
                                  ->where('parent_id', $province->id)
                                  ->orderBy('name', 'ASC')
                                  ->get();
            foreach ((array) $rows as $row) {
                $html .= '<option value="' . e($row->code) . '">' . e($row->name) . '</option>';
            }
        }

        // Trả về HTML trực tiếp để tương thích ngược với JS Frontend cũ
        return new Response($html);
    }

    /**
     * Lấy danh sách Phường/Xã theo mã Quận/Huyện (AJAX)
     * POST /ajax/location/ward
     */
    public function ward(Request $request) {
        $code_huyen = trim($request->input('code_huyen', ''));

        if (empty($code_huyen)) {
            return new Response('<option value="">Chọn Phường / Xã</option>');
        }

        $district = LocationModel::where('type', 'district')->where('code', $code_huyen)->first();

        $html = '<option value="">Chọn Phường / Xã</option>';
        if ($district) {
            $rows = LocationModel::where('type', 'ward')
                                  ->where('parent_id', $district->id)
                                  ->orderBy('name', 'ASC')
                                  ->get();
            foreach ((array) $rows as $row) {
                $html .= '<option value="' . e($row->code) . '">' . e($row->name) . '</option>';
            }
        }

        return new Response($html);
    }
}
