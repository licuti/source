<?php
/**
 * env.php — Helper đọc file `.env` (dùng chung cho admin/ và lms/).
 *
 * Đặt ở thư mục gốc (trung lập) để e-commerce và LMS cùng dùng được mà không
 * phụ thuộc lẫn nhau (ràng buộc D2: LMS không phụ thuộc code admin/).
 *
 * Không dùng parse_ini_file vì password có thể chứa ký tự đặc biệt;
 * tự tách dòng đơn giản, bỏ qua comment `#`.
 *
 * Giới hạn: không hỗ trợ cú pháp `export KEY=val`, giá trị nhiều dòng,
 * hay inline comment sau giá trị (`K=v # c` sẽ giữ nguyên `# c`).
 *
 * @param  string $path
 * @return array<string,string>
 */
if (!function_exists('env_load')) {
    function env_load($path)
    {
        $out = array();
        if (!is_file($path) || !is_readable($path)) {
            return $out;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return $out;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if (substr($line, 0, 3) === "\xEF\xBB\xBF") {
                $line = substr($line, 3);
            }
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            // Bỏ dấu nháy bao ngoài nếu có
            if (strlen($val) >= 2) {
                $first = $val[0];
                $last  = substr($val, -1);
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $val = substr($val, 1, -1);
                }
            }
            if ($key !== '') {
                $out[$key] = $val;
            }
        }
        return $out;
    }
}
