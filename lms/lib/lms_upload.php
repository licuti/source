<?php
/**
 * Upload helper LMS (TCH-01/02).
 *
 * - lms_upload_dir(): thư mục lms/public/uploads/ (tự tạo + .htaccess cấm
 *   execute PHP — bắt buộc theo checklist bảo mật).
 * - lms_save_files(): lưu nhiều file từ $_FILES, validate MIME bằng finfo
 *   (không tin extension), chống path traversal, tên file ngẫu nhiên.
 *
 * PHP 7.3 compatible.
 */

if (!function_exists('lms_upload_dir')) {
    /**
     * Đường dẫn tuyệt đối tới lms/public/uploads/. Tự tạo + viết .htaccess
     * cấm chạy PHP trong thư mục uploads (bảo mật bắt buộc).
     *
     * @return string|null null khi không tạo được
     */
    function lms_upload_dir()
    {
        $dir = _LMS_DIR . '/public/uploads';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!is_dir($dir)) {
            return null;
        }

        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) {
            // Cấm execute PHP + cấm truy cập file .php trực tiếp
            $content = "# Tu dong sinh boi lms_upload_dir() — cấm execute PHP trong uploads\n"
                . "php_flag engine off\n"
                . "RemoveHandler .php .phtml .php3 .php4 .php5 .php7\n"
                . "<FilesMatch \"\\.(?i:php|php3|php4|php5|php7|phtml|phar)$\">\n"
                . "    Require all denied\n"
                . "</FilesMatch>\n"
                . "Options -ExecCGI\n";
            @file_put_contents($htaccess, $content);
        }
        return $dir;
    }
}

if (!function_exists('lms_save_files')) {
    /**
     * Lưu nhiều file từ cấu trúc $_FILES (multi-file).
     *
     * @param array  $files  $_FILES['ten_field'] (cấu trúc name/tmp_name mảng)
     * @param string $subdir thư mục con trong uploads (VD 'hw/202610')
     * @param array  $opts   maxFiles (4), maxSize bytes (20MB),
     *                        mimeWhitelist (mảng đuôi file cho phép),
     *                        cli (bool — bỏ qua is_uploaded_file khi smoke test CLI)
     * @return array array('ok' => bool, 'paths' => array, 'error' => string)
     *               paths là đường dẫn tương đối trong uploads/
     */
    function lms_save_files($files, $subdir, array $opts = array())
    {
        $maxFiles = isset($opts['maxFiles']) ? (int) $opts['maxFiles'] : 4;
        $maxSize  = isset($opts['maxSize']) ? (int) $opts['maxSize'] : 20 * 1024 * 1024;
        $allowed  = isset($opts['mimeWhitelist']) && is_array($opts['mimeWhitelist'])
            ? $opts['mimeWhitelist']
            : array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'docx', 'xlsx', 'pptx', 'mp3', 'mp4', 'zip');
        $cli      = !empty($opts['cli']);

        $allowedMap = array();
        foreach ($allowed as $ext) {
            $ext = strtolower((string) $ext);
            $allowedMap[$ext] = lms_mime_types_for($ext);
        }

        // Chuẩn hoá $files về danh sách (chấp nhận cả file đơn lẻ)
        $items = array();
        if (!is_array($files)) {
            return array('ok' => false, 'paths' => array(), 'error' => 'Không có file nào được gửi.');
        }
        $isMulti = isset($files['name']) && is_array($files['name']);
        if ($isMulti) {
            $n = count($files['name']);
            for ($i = 0; $i < $n; $i++) {
                $items[] = array(
                    'name'     => isset($files['name'][$i]) ? $files['name'][$i] : '',
                    'tmp_name' => isset($files['tmp_name'][$i]) ? $files['tmp_name'][$i] : '',
                    'error'    => isset($files['error'][$i]) ? $files['error'][$i] : UPLOAD_ERR_NO_FILE,
                    'size'     => isset($files['size'][$i]) ? $files['size'][$i] : 0,
                );
            }
        } else {
            $items[] = array(
                'name'     => isset($files['name']) ? $files['name'] : '',
                'tmp_name' => isset($files['tmp_name']) ? $files['tmp_name'] : '',
                'error'    => isset($files['error']) ? $files['error'] : UPLOAD_ERR_NO_FILE,
                'size'     => isset($files['size']) ? $files['size'] : 0,
            );
        }

        // Lọc file rỗng (không chọn gì)
        $real = array();
        foreach ($items as $it) {
            $name = (string) $it['name'];
            if ($name === '' || (int) $it['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $real[] = $it;
        }

        if (count($real) === 0) {
            return array('ok' => true, 'paths' => array(), 'error' => '');
        }
        if (count($real) > $maxFiles) {
            return array('ok' => false, 'paths' => array(), 'error' => 'Chỉ được đính kèm tối đa ' . $maxFiles . ' file.');
        }

        $base = lms_upload_dir();
        if ($base === null) {
            return array('ok' => false, 'paths' => array(), 'error' => 'Không chuẩn bị được thư mục lưu file.');
        }

        // subdir an toàn: chỉ cho phép [A-Za-z0-9_/-]
        $subdir = trim((string) $subdir, "/\r\n");
        if ($subdir !== '' && !preg_match('#^[A-Za-z0-9_/-]+$#', $subdir)) {
            return array('ok' => false, 'paths' => array(), 'error' => 'Thư mục lưu file không hợp lệ.');
        }
        $dest = $subdir !== '' ? $base . '/' . $subdir : $base;
        if (!is_dir($dest)) {
            @mkdir($dest, 0755, true);
        }
        if (!is_dir($dest)) {
            return array('ok' => false, 'paths' => array(), 'error' => 'Không tạo được thư mục lưu file.');
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;

        $paths = array();
        foreach ($real as $it) {
            $name = basename((string) $it['name']); // chống path traversal
            if ($name === '' || strpos($name, "\0") !== false) {
                return array('ok' => false, 'paths' => array(), 'error' => 'Tên file không hợp lệ.');
            }

            $err = (int) $it['error'];
            if ($err !== UPLOAD_ERR_OK) {
                return array('ok' => false, 'paths' => array(), 'error' => 'Tải file "' . $name . '" thất bại (mã lỗi ' . $err . ').');
            }

            $tmp = (string) $it['tmp_name'];
            if (!$cli && !is_uploaded_file($tmp)) {
                return array('ok' => false, 'paths' => array(), 'error' => 'File "' . $name . '" không phải file upload hợp lệ.');
            }
            if (!is_file($tmp)) {
                return array('ok' => false, 'paths' => array(), 'error' => 'File "' . $name . '" không tồn tại.');
            }

            $size = (int) $it['size'];
            if ($size <= 0 || $size > $maxSize) {
                return array('ok' => false, 'paths' => array(), 'error' => 'File "' . $name . '" vượt quá dung lượng cho phép (' . round($maxSize / 1024 / 1024, 1) . 'MB).');
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!isset($allowedMap[$ext])) {
                return array('ok' => false, 'paths' => array(), 'error' => 'Đuôi file "' . $name . '" không được phép.');
            }

            // MIME thực tế (không tin extension)
            $mime = 'application/octet-stream';
            if ($finfo) {
                $detected = finfo_file($finfo, $tmp);
                if (is_string($detected) && $detected !== '') {
                    $mime = strtolower(trim($detected));
                }
            }
            $mimes = $allowedMap[$ext];
            if (!in_array($mime, $mimes, true)) {
                return array('ok' => false, 'paths' => array(), 'error' => 'Nội dung file "' . $name . '" không đúng định dạng ' . $ext . '.');
            }

            try {
                $random = bin2hex(random_bytes(8));
            } catch (Exception $e) {
                $random = md5(uniqid((string) mt_rand(), true) . microtime(true));
            }
            $relative = ($subdir !== '' ? $subdir . '/' : '') . $random . '.' . $ext;
            if (!@move_uploaded_file($tmp, $base . '/' . $relative)) {
                // CLI không có file upload thật → dùng rename
                if (!@rename($tmp, $base . '/' . $relative)) {
                    return array('ok' => false, 'paths' => array(), 'error' => 'Không lưu được file "' . $name . '".');
                }
            }
            $paths[] = $relative;
        }

        if ($finfo) {
            finfo_close($finfo);
        }

        return array('ok' => true, 'paths' => $paths, 'error' => '');
    }
}

if (!function_exists('lms_mime_types_for')) {
    /**
     * Danh sách MIME chấp nhận cho một đuôi file.
     *
     * @param string $ext
     * @return array
     */
    function lms_mime_types_for($ext)
    {
        $ext = strtolower((string) $ext);
        $map = array(
            'pdf'  => array('application/pdf'),
            'jpg'  => array('image/jpeg'),
            'jpeg' => array('image/jpeg'),
            'png'  => array('image/png'),
            'gif'  => array('image/gif'),
            'webp' => array('image/webp'),
            // Office Open XML thường bị finfo nhận là application/zip
            'docx' => array(
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
            ),
            'xlsx' => array(
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
            ),
            'pptx' => array(
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
            ),
            'mp3'  => array('audio/mpeg'),
            'mp4'  => array('video/mp4'),
            'zip'  => array('application/zip', 'application/x-zip-compressed'),
        );
        return isset($map[$ext]) ? $map[$ext] : array();
    }
}
