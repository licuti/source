<?php
if (!defined('_source')) {
    die('Error');
}

$a = isset($_REQUEST['a']) ? addslashes($_REQUEST['a']) : '';

switch ($a) {
    case 'man':
        showdulieu();
        $template = @$_REQUEST['p'] . '/them';
        break;
    case 'save':
        luudulieu($id_module);
        break;
    case 'delete_verify':
        xoa_file_xacminh($id_module);
        break;
    default:
        $template = 'index';
}

function seo_root_path()
{
    return dirname(__DIR__, 2);
}

function seo_read_file($relativePath)
{
    $path = seo_root_path() . '/' . ltrim(str_replace(chr(92), '/', $relativePath), '/');
    if (!is_file($path) || !is_readable($path)) {
        return '';
    }
    $content = @file_get_contents($path);
    return $content === false ? '' : $content;
}

function seo_write_file($relativePath, $content)
{
    $path = seo_root_path() . '/' . ltrim(str_replace(chr(92), '/', $relativePath), '/');
    $dir  = dirname($path);
    if (!is_dir($dir)) {
        return false;
    }
    if (function_exists('check_shell')) {
        $content = check_shell($content);
    }
    if (!is_writable($dir) && !(is_file($path) && is_writable($path))) {
        return false;
    }
    $fp = @fopen($path, 'w+');
    if (!$fp) {
        return false;
    }
    fwrite($fp, (string) $content);
    fclose($fp);
    return true;
}

function seo_flash($type, $message)
{
    if (!isset($_SESSION) || session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['admin_flash'] = array(
        'type'    => $type,
        'message' => $message,
    );
}

function seo_ensure_og_columns()
{
    global $d;
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $defs = array(
        'og_title' => 'TEXT NULL',
        'og_des'   => 'TEXT NULL',
        'og_image' => "VARCHAR(255) NULL DEFAULT ''",
    );
    foreach ($defs as $name => $def) {
        $check = $d->o_fet("SHOW COLUMNS FROM #_seo LIKE '" . $name . "'");
        if (!empty($check)) {
            continue;
        }
        $sql = 'ALTER TABLE #_seo ADD COLUMN `' . $name . '` ' . $def;
        if (method_exists($d, 'query')) {
            @$d->query($sql);
        } elseif (isset($d->db) && is_object($d->db) && method_exists($d->db, 'query')) {
            $table = (isset($d->refix) ? $d->refix : 'db_') . 'seo';
            @$d->db->query(str_replace('#_seo', $table, $sql));
        }
    }
}

function seo_default_robots()
{
    $site = defined('URLPATH') ? rtrim(URLPATH, '/') : '';
    return "User-agent: *\n"
        . "Allow: /\n"
        . "Disallow: /admin/\n"
        . "Disallow: /sources/\n"
        . "Disallow: /library/\n"
        . "\n"
        . "# Sitemap\n"
        . ($site !== '' ? 'Sitemap: ' . $site . "/sitemap.xml\n" : "# Sitemap: https://example.com/sitemap.xml\n");
}

function seo_list_verify_files()
{
    $root = seo_root_path();
    $out  = array();
    $seen = array();
    foreach (array('html', 'htm', 'txt', 'json') as $ext) {
        foreach (glob($root . '/*.' . $ext) ?: array() as $file) {
            $base = basename($file);
            if (isset($seen[$base])) {
                continue;
            }
            if (in_array(strtolower($base), array('index.html', 'index.htm', 'robots.txt'), true)) {
                continue;
            }
            $looksVerify = (bool) preg_match('/^(google|yandex|bing|html_verify|verify)/i', $base)
                || preg_match('/verification|site.?verification/i', $base);
            $size = @filesize($file);
            if (!$looksVerify && $size !== false && $size > 51200) {
                continue;
            }
            $seen[$base] = true;
            $out[] = array(
                'name'  => $base,
                'size'  => $size !== false ? (int) $size : 0,
                'mtime' => @filemtime($file) ?: 0,
            );
        }
    }
    usort($out, function ($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    return $out;
}


function showdulieu()
{
    global $d, $item, $robots, $seo_head, $seo_body, $rowByLang;
    global $seo_writable, $seo_verify_files, $seo_has_sitemap, $seo_robots_blocks_all;

    seo_ensure_og_columns();

    $langs = get_json('lang');
    $rowByLang = array();
    foreach ($langs as $lang) {
        $code = $lang['code'];
        $row  = $d->simple_fetch("select * from #_seo where lang = '" . $code . "'");
        $rowByLang[$code] = is_array($row) ? $row : array();
    }

    $item = $d->o_fet('select * from #_seo where id = 1');

    $robots   = seo_read_file('robots.txt');
    $seo_head = seo_read_file('sitemap/seo_head.inc');
    $seo_body = seo_read_file('sitemap/seo_footer.inc');

    $root = seo_root_path();
    $seo_writable = array(
        'robots' => is_writable($root) || (is_file($root . '/robots.txt') && is_writable($root . '/robots.txt')),
        'head'   => is_writable($root . '/sitemap') || (is_file($root . '/sitemap/seo_head.inc') && is_writable($root . '/sitemap/seo_head.inc')),
        'body'   => is_writable($root . '/sitemap') || (is_file($root . '/sitemap/seo_footer.inc') && is_writable($root . '/sitemap/seo_footer.inc')),
    );

    $seo_verify_files = seo_list_verify_files();
    $seo_has_sitemap  = is_file($root . '/sitemap.xml');
    $seo_robots_blocks_all = (bool) preg_match('/(?im)^\s*Disallow:\s*\/\s*$/m', $robots)
        && !preg_match('/(?im)^\s*Allow:\s*\/\s*$/m', $robots);
}


function luudulieu($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        seo_flash('error', 'Bạn không có quyền chỉnh sửa.');
        $d->redirect('index.php?p=seo-co-ban&a=man');
        return;
    }

    seo_ensure_og_columns();

    $errors = array();
    $notes  = array();

    if (!empty($_POST['robots_reset'])) {
        $_POST['robots'] = seo_default_robots();
        $notes[] = 'Đã nạp mẫu robots.txt khuyến nghị.';
    }

    foreach (get_json('lang') as $key => $value) {
        $code = $value['code'];
        $row  = $d->simple_fetch("select * from #_seo where lang = '" . $code . "'");

        $data = array(
            'title'    => addslashes(trim((string) ($_POST['title'][$key] ?? ''))),
            'keyword'  => addslashes(trim((string) ($_POST['keyword'][$key] ?? ''))),
            'des'      => addslashes(trim((string) ($_POST['des'][$key] ?? ''))),
            'og_title' => addslashes(trim((string) ($_POST['og_title'][$key] ?? ''))),
            'og_des'   => addslashes(trim((string) ($_POST['og_des'][$key] ?? ''))),
            'og_image' => addslashes(trim((string) ($_POST['og_image'][$key] ?? ''))),
            'lang'     => $code,
        );

        $d->reset();
        $d->setTable('#_seo');
        if (is_array($row) && !empty($row['id'])) {
            $d->setWhere('id', $row['id']);
            if (!$d->update($data)) {
                $errors[] = 'Lỗi cập nhật meta (' . $code . ').';
            }
        } else {
            if (!$d->insert($data)) {
                $errors[] = 'Lỗi thêm meta (' . $code . ').';
            }
        }
    }

    if (isset($_POST['robots'])) {
        if (!seo_write_file('robots.txt', (string) $_POST['robots'])) {
            $errors[] = 'Không ghi được robots.txt (quyền thư mục).';
        }
    }
    if (isset($_POST['head'])) {
        if (!seo_write_file('sitemap/seo_head.inc', (string) $_POST['head'])) {
            $errors[] = 'Không ghi được seo_head.inc.';
        }
    }
    if (isset($_POST['body'])) {
        if (!seo_write_file('sitemap/seo_footer.inc', (string) $_POST['body'])) {
            $errors[] = 'Không ghi được seo_footer.inc.';
        }
    }

    if (!empty($_FILES['file']['name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        $origName = (string) $_FILES['file']['name'];
        $base     = basename(str_replace(chr(92), '/', $origName));
        $ext      = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        $allowed  = array('html', 'htm', 'txt', 'json');
        $mime     = (string) ($_FILES['file']['type'] ?? '');
        $mimeOk   = in_array($mime, array('text/html', 'text/plain', 'application/json', 'application/octet-stream', ''), true);

        if (!in_array($ext, $allowed, true) || !$mimeOk || !preg_match('/^[A-Za-z0-9._-]+$/', $base)) {
            $errors[] = 'File xác minh không hợp lệ.';
        } else {
            $raw = @file_get_contents($_FILES['file']['tmp_name']);
            if ($raw === false) {
                $errors[] = 'Không đọc được file xác minh.';
            } else {
                if (function_exists('check_shell')) {
                    $raw = check_shell($raw);
                }
                $dest = seo_root_path() . '/' . $base;
                $ok = false;
                if ($raw !== '' && $raw !== null) {
                    if (@move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                        $ok = true;
                    } elseif (@file_put_contents($dest, $raw) !== false) {
                        $ok = true;
                    }
                }
                if ($ok) {
                    $notes[] = 'Đã tải file xác minh: ' . $base;
                } else {
                    $errors[] = 'Không lưu được file xác minh.';
                }
            }
        }
    }

    if ($errors) {
        seo_flash('error', implode(' ', $errors) . ($notes ? ' ' . implode(' ', $notes) : ''));
    } else {
        $msg = 'Đã cập nhật cấu hình SEO.';
        if ($notes) {
            $msg .= ' ' . implode(' ', $notes);
        }
        seo_flash('success', $msg);
    }

    $d->redirect('index.php?p=seo-co-ban&a=man');
}

function xoa_file_xacminh($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        seo_flash('error', 'Bạn không có quyền xóa file.');
        $d->redirect('index.php?p=seo-co-ban&a=man');
        return;
    }

    $name = basename(str_replace(chr(92), '/', (string) ($_GET['file'] ?? '')));
    if ($name === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
        seo_flash('error', 'Tên file không hợp lệ.');
        $d->redirect('index.php?p=seo-co-ban&a=man');
        return;
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, array('html', 'htm', 'txt', 'json'), true)) {
        seo_flash('error', 'Chỉ xóa file xác minh (.html/.htm/.txt/.json).');
        $d->redirect('index.php?p=seo-co-ban&a=man');
        return;
    }

    $path = seo_root_path() . '/' . $name;
    if (!is_file($path)) {
        seo_flash('error', 'File không tồn tại.');
        $d->redirect('index.php?p=seo-co-ban&a=man');
        return;
    }

    if (@unlink($path)) {
        seo_flash('success', 'Đã xóa file: ' . $name);
    } else {
        seo_flash('error', 'Không xóa được file.');
    }
    $d->redirect('index.php?p=seo-co-ban&a=man');
}
