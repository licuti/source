<?php
/**
 * ============================================================
 *  STRING HELPERS
 *  Các hàm xử lý văn bản và SEO.
 * ============================================================
 */

if (!function_exists('str_slug')) {
    /**
     * Chuyển đổi chuỗi sang Slug SEO chuẩn
     */
    function str_slug($str, $separator = '-') {
        if (empty($str)) return '';
        
        $unicode = array(
            'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd'=>'đ',
            'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i'=>'í|ì|ỉ|ĩ|ị',
            'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y'=>'ý|ỳ|ỷ|ỹ|ỵ',
            'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'D'=>'Đ',
            'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
            'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        );

        foreach($unicode as $nonUnicode=>$uni){
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        }

        $str = strtolower(trim($str));
        $str = preg_replace('/[^a-z0-9-]/', $separator, $str);
        $str = preg_replace('/-+/', $separator, $str);
        $str = trim($str, $separator);

        return $str;
    }
}

if (!function_exists('str_limit')) {
    /**
     * Cắt chuỗi thông minh (không làm đứt từ)
     */
    function str_limit($str, $limit = 100, $end = '...') {
        $str = strip_tags($str);
        if (mb_strlen($str) <= $limit) {
            return $str;
        }
        
        $str = mb_substr($str, 0, $limit);
        $lastSpace = mb_strrpos($str, ' ');
        
        if ($lastSpace !== false) {
            $str = mb_substr($str, 0, $lastSpace);
        }
        
        return $str . $end;
    }
}

if (!function_exists('str_random')) {
    /**
     * Tạo chuỗi ngẫu nhiên
     */
    function str_random($length = 10) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, $max)];
        }
        return $str;
    }
}

if (!function_exists('str_contains')) {
    /**
     * Kiểm tra xem chuỗi có chứa từ khóa không
     */
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
