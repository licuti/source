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
    case 'generate':
        taositemap($id_module);
        break;
    case 'save':
        luudulieu($id_module);
        break;
    default:
        $template = 'index';
}

function sitemap_root_path()
{
    return dirname(__DIR__, 2);
}

function sitemap_flash($type, $message)
{
    if (!isset($_SESSION) || session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['admin_flash'] = array(
        'type'    => $type,
        'message' => $message,
    );
}

function sitemap_read()
{
    $path = sitemap_root_path() . '/sitemap.xml';
    if (!is_file($path) || !is_readable($path)) {
        return '';
    }
    $content = @file_get_contents($path);
    return $content === false ? '' : $content;
}

function sitemap_write($content)
{
    $path = sitemap_root_path() . '/sitemap.xml';
    $dir  = dirname($path);
    if (function_exists('check_shell')) {
        $content = check_shell($content);
    }
    if (!is_dir($dir)) {
        return false;
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

function sitemap_backup()
{
    $path = sitemap_root_path() . '/sitemap.xml';
    if (!is_file($path)) {
        return false;
    }
    $bak = sitemap_root_path() . '/sitemap.xml.bak';
    return @copy($path, $bak);
}

function sitemap_count_urls($xml)
{
    if ($xml === '' || $xml === null) {
        return 0;
    }
    return (int) preg_match_all('/<url\b/i', (string) $xml);
}

/**
 * Validate sitemap XML structure (basic).
 *
 * @return array{ok:bool,error:string,count:int}
 */
function sitemap_validate_xml($xml)
{
    $xml = trim((string) $xml);
    if ($xml === '') {
        return array('ok' => false, 'error' => 'Nội dung trống.', 'count' => 0);
    }
    if (stripos($xml, '<urlset') === false && stripos($xml, '<sitemapindex') === false) {
        return array('ok' => false, 'error' => 'Thiếu thẻ <urlset> hoặc <sitemapindex>.', 'count' => 0);
    }
    if (stripos($xml, '<urlset') !== false && stripos($xml, '<loc') === false) {
        return array('ok' => false, 'error' => 'Không có thẻ <loc> nào trong sitemap.', 'count' => 0);
    }

    $prev = libxml_use_internal_errors(true);
    $doc  = simplexml_load_string($xml);
    $errs = libxml_get_errors();
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    if ($doc === false) {
        $msg = 'XML không hợp lệ.';
        if (!empty($errs[0]->message)) {
            $msg .= ' ' . trim($errs[0]->message);
        }
        return array('ok' => false, 'error' => $msg, 'count' => 0);
    }

    return array(
        'ok'    => true,
        'error' => '',
        'count' => sitemap_count_urls($xml),
    );
}

function sitemap_table_has_column($table, $column)
{
    global $d;
    static $cache = array();
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $rows = $d->o_fet("SHOW COLUMNS FROM " . $table . " LIKE '" . addslashes($column) . "'");
    $cache[$key] = !empty($rows);
    return $cache[$key];
}

function sitemap_public_url()
{
    return (defined('URLPATH') ? rtrim(URLPATH, '/') : '') . '/sitemap.xml';
}

/**
 * Ensure robots.txt contains Sitemap: line. Returns status note.
 *
 * @return array{status:string,message:string,has_line:bool}
 */
function sitemap_ensure_robots_line($autoWrite = true)
{
    $root = sitemap_root_path();
    $path = $root . '/robots.txt';
    $sitemapLine = 'Sitemap: ' . sitemap_public_url();

    if (!is_file($path)) {
        if (!$autoWrite) {
            return array(
                'status'   => 'missing_file',
                'message'  => 'Chưa có robots.txt.',
                'has_line' => false,
            );
        }
        if (!is_writable($root)) {
            return array(
                'status'   => 'unwritable',
                'message'  => 'Không tạo được robots.txt (thư mục chỉ đọc).',
                'has_line' => false,
            );
        }
        $body = "User-agent: *\nAllow: /\n\n" . $sitemapLine . "\n";
        if (@file_put_contents($path, $body) === false) {
            return array(
                'status'   => 'error',
                'message'  => 'Không ghi được robots.txt.',
                'has_line' => false,
            );
        }
        return array(
            'status'   => 'created',
            'message'  => 'Đã tạo robots.txt kèm dòng Sitemap.',
            'has_line' => true,
        );
    }

    $content = (string) @file_get_contents($path);
    if (preg_match('/(?im)^\s*Sitemap:\s*\S+/', $content)) {
        return array(
            'status'   => 'ok',
            'message'  => 'robots.txt đã có dòng Sitemap.',
            'has_line' => true,
        );
    }

    if (!$autoWrite) {
        return array(
            'status'   => 'missing_line',
            'message'  => 'robots.txt thiếu dòng Sitemap.',
            'has_line' => false,
        );
    }

    if (!is_writable($path) && !is_writable($root)) {
        return array(
            'status'   => 'unwritable',
            'message'  => 'Không ghi được robots.txt để thêm Sitemap.',
            'has_line' => false,
        );
    }

    $append = (substr($content, -1) === "\n" ? '' : "\n") . "\n" . $sitemapLine . "\n";
    if (@file_put_contents($path, $content . $append) === false) {
        return array(
            'status'   => 'error',
            'message'  => 'Không ghi được robots.txt.',
            'has_line' => false,
        );
    }

    return array(
        'status'   => 'appended',
        'message'  => 'Đã thêm dòng Sitemap vào robots.txt.',
        'has_line' => true,
    );
}

/**
 * Build sitemap.xml from published content.
 *
 * @return array{xml:string,count:int,stats:array,skipped_noindex:int}
 */
function sitemap_build_xml()
{
    global $d, $config;

    $base = defined('URLPATH') ? rtrim(URLPATH, '/') . '/' : '/';
    $multiLang = !empty($config['lang']) && is_array($config['lang']) && count($config['lang']) > 1;

    $sources = array(
        'home'     => array('label' => 'Trang chủ', 'table' => null),
        'category' => array('label' => 'Danh mục', 'table' => '#_category'),
        'tintuc'   => array('label' => 'Tin tức', 'table' => '#_tintuc'),
        'album'    => array('label' => 'Album', 'table' => '#_album'),
        'video'    => array('label' => 'Video', 'table' => '#_video'),
        'sanpham'  => array('label' => 'Sản phẩm', 'table' => '#_sanpham'),
    );

    $stats = array();
    foreach ($sources as $key => $src) {
        $stats[$key] = array('label' => $src['label'], 'count' => 0);
    }

    $seen = array();
    $urls = array();
    $skippedNoindex = 0;

    $home = $base;
    $seen[$home] = true;
    $urls[] = array('loc' => $home, 'lastmod' => date('c'), 'source' => 'home');
    $stats['home']['count'] = 1;

    foreach ($sources as $key => $src) {
        if (empty($src['table'])) {
            continue;
        }
        $table = $src['table'];
        $cols = 'alias, lang, cap_nhat';
        $hasNoindex = sitemap_table_has_column($table, 'noindex');
        if ($hasNoindex) {
            $cols .= ', noindex';
        }

        $rows = $d->o_fet(
            'select ' . $cols . ' from ' . $table
            . ' where hien_thi = 1 order by cap_nhat DESC'
        );
        if (!is_array($rows)) {
            continue;
        }

        foreach ($rows as $row) {
            if ($hasNoindex && !empty($row['noindex']) && (int) $row['noindex'] === 1) {
                $skippedNoindex++;
                continue;
            }
            $alias = trim((string) ($row['alias'] ?? ''));
            if ($alias === '' || preg_match('/[\s<>"\']/', $alias)) {
                continue;
            }

            $langPrefix = '';
            if ($multiLang && !empty($row['lang'])) {
                $langPrefix = $row['lang'] . '/';
            }
            $loc = $base . $langPrefix . $alias . '.html';
            if (isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            $ts = !empty($row['cap_nhat']) ? (int) $row['cap_nhat'] : time();
            if ($ts <= 0) {
                $ts = time();
            }
            $urls[] = array(
                'loc'     => $loc,
                'lastmod' => date('c', $ts),
                'source'  => $key,
            );
            $stats[$key]['count']++;
        }
    }

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<?xml-stylesheet type="text/xsl" href="main-sitemap.xsl"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $loc = htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $lm  = htmlspecialchars($u['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml .= "  <url>\n";
        $xml .= '    <loc>' . $loc . "</loc>\n";
        $xml .= '    <lastmod>' . $lm . "</lastmod>\n";
        $xml .= "  </url>\n";
    }
    $xml .= '</urlset>' . "\n";

    return array(
        'xml'             => $xml,
        'count'           => count($urls),
        'stats'           => $stats,
        'skipped_noindex' => $skippedNoindex,
    );
}

function showdulieu()
{
    global $d, $sitemap_content, $sitemap_meta, $sitemap_robots;

    $root = sitemap_root_path();
    $path = $root . '/sitemap.xml';
    $sitemap_content = sitemap_read();

    $exists   = is_file($path);
    $writable = is_writable($root) || ($exists && is_writable($path));
    $mtime    = $exists ? @filemtime($path) : 0;
    $size     = $exists ? @filesize($path) : 0;
    $urlCount = sitemap_count_urls($sitemap_content);

    $robotsInfo = sitemap_ensure_robots_line(false);

    $sitemap_meta = array(
        'exists'     => $exists,
        'writable'   => $writable,
        'mtime'      => $mtime ? (int) $mtime : 0,
        'size'       => $size ? (int) $size : 0,
        'url_count'  => $urlCount,
        'public_url' => sitemap_public_url(),
        'has_xsl'    => is_file($root . '/main-sitemap.xsl'),
        'has_bak'    => is_file($root . '/sitemap.xml.bak'),
        'too_many'   => $urlCount > 50000,
        'last_stats' => isset($_SESSION['sitemap_last_stats']) ? $_SESSION['sitemap_last_stats'] : null,
    );

    $sitemap_robots = $robotsInfo;
}

function taositemap($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        sitemap_flash('error', 'Bạn không có quyền tạo sitemap.');
        $d->redirect('index.php?p=sitemap&a=man');
        return;
    }

    sitemap_backup();

    $built = sitemap_build_xml();
    if ($built['count'] <= 0) {
        sitemap_flash('error', 'Không có URL nào để đưa vào sitemap.');
        $d->redirect('index.php?p=sitemap&a=man');
        return;
    }

    if (!sitemap_write($built['xml'])) {
        sitemap_flash('error', 'Không ghi được sitemap.xml (kiểm tra quyền thư mục gốc website).');
        $d->redirect('index.php?p=sitemap&a=man');
        return;
    }

    if (!isset($_SESSION) || session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['sitemap_last_stats'] = array(
        'stats'           => $built['stats'],
        'count'           => $built['count'],
        'skipped_noindex' => $built['skipped_noindex'],
        'at'              => time(),
    );

    $notes = array();
    $notes[] = 'Đã tạo sitemap.xml — ' . (int) $built['count'] . ' URL.';
    if ($built['skipped_noindex'] > 0) {
        $notes[] = 'Bỏ qua ' . (int) $built['skipped_noindex'] . ' mục noindex.';
    }

    $parts = array();
    foreach ($built['stats'] as $st) {
        if ((int) $st['count'] > 0) {
            $parts[] = $st['label'] . ': ' . (int) $st['count'];
        }
    }
    if ($parts) {
        $notes[] = '(' . implode(', ', $parts) . ')';
    }

    $robots = sitemap_ensure_robots_line(true);
    if (in_array($robots['status'], array('created', 'appended'), true)) {
        $notes[] = $robots['message'];
    } elseif ($robots['status'] === 'unwritable' || $robots['status'] === 'error') {
        $notes[] = $robots['message'];
    }

    if ($built['count'] > 50000) {
        $notes[] = 'Cảnh báo: > 50.000 URL (giới hạn Google mỗi file).';
    }

    sitemap_flash('success', implode(' ', $notes));
    $d->redirect('index.php?p=sitemap&a=man');
}

function luudulieu($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        sitemap_flash('error', 'Bạn không có quyền chỉnh sửa sitemap.');
        $d->redirect('index.php?p=sitemap&a=man');
        return;
    }

    $content = isset($_POST['content_sitemap']) ? (string) $_POST['content_sitemap'] : '';
    $valid = sitemap_validate_xml($content);
    if (!$valid['ok']) {
        sitemap_flash('error', 'Không lưu: ' . $valid['error']);
        $d->redirect('index.php?p=sitemap&a=man');
        return;
    }

    sitemap_backup();

    if (!sitemap_write($content)) {
        sitemap_flash('error', 'Không ghi được sitemap.xml (kiểm tra quyền thư mục).');
        $d->redirect('index.php?p=sitemap&a=man');
        return;
    }

    $n = (int) $valid['count'];
    $msg = 'Đã cập nhật sitemap.xml' . ($n > 0 ? ' — ' . $n . ' URL.' : '.');
    if ($n > 50000) {
        $msg .= ' Cảnh báo: > 50.000 URL.';
    }
    sitemap_flash('success', $msg);
    $d->redirect('index.php?p=sitemap&a=man');
}
