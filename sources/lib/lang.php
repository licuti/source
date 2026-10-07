<?php
function setLang($langs, $default = 'vi') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    // Lấy ngôn ngữ từ request, session, hoặc mặc định
    $lang = $_REQUEST['lang'] ?? $_SESSION['lang'] ?? $default;

    // Nếu ngôn ngữ không hợp lệ, dùng mặc định
    $valid_codes = array_column($langs, 'code');
    if (!in_array($lang, $valid_codes)) {
        $lang = $default;
    }

    // Lưu vào session
    $_SESSION['lang'] = $lang;

    // Xây dựng URL và điều kiện SQL
    define('_lang', $lang);
    define('_where_lang', $lang ? "and lang='$lang'" : '');
    define('_URLLANG', count($langs) > 1 ? URLPATH.$lang.'/' : URLPATH);

    return $lang;
}

// Sử dụng
$lang = setLang($config['lang'], LANG);