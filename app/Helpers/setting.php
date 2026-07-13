<?php
use App\Models\SettingModel;

use App\Models\OptionModel;

/**
 * ============================================================
 *  SETTING HELPERS
 *  Helper functions for OptionModel & SettingModel
 * ============================================================
 */

if (!function_exists('get_option')) {
    /**
     * Lấy giá trị option từ DB
     *
     * @param string $key Tên option
     * @param mixed $default Giá trị mặc định nếu không tìm thấy
     * @return mixed
     */
    function get_option($key, $default = null) {
        return \App\Models\OptionModel::getValue($key, $default);
    }
}
