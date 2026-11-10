<?php
/**
 * Router của website: từ alias trên URL tìm ra nội dung và template tương ứng.
 *
 * Chạy ở MỌI request công khai nên $com (alias) phải được bind tham số.
 *
 * LƯU Ý: biến $where_lang dùng trong bản cũ không được gán ở bất kỳ đâu
 * (đúng ra là hằng _where_lang trong sources/lib/lang.php) nên luôn rỗng —
 * tức là chưa bao giờ lọc theo ngôn ngữ. Giữ nguyên hành vi này để không
 * đổi kết quả truy vấn; nếu muốn bật lọc đa ngữ thì xử lý ở một đợt riêng.
 */

$com    = isset($_REQUEST['com']) ? (string) $_REQUEST['com'] : '';
$source = '404';
$type   = '';

/** Lấy bản dịch của một bản ghi theo id_code. */
$fetchMultiLang = function ($table, $id_code) use ($d) {
    return array_key_by_column(
        $d->safeFetch("select * from #_$table where id_code = ?", array($id_code)),
        'lang'
    );
};

/** Lấy tên template của module. */
$fetchTemplate = function ($id_module) use ($d) {
    $row = $d->safeFetchOne("select templates from #_module where id = ? limit 1", array((int) $id_module));
    return isset($row['templates']) ? $row['templates'] : '';
};

/** Tìm bản ghi theo alias trong bảng chỉ định. */
$findByAlias = function ($table, $alias) use ($d) {
    return $d->safeFetchOne("select * from #_$table where alias = ? limit 1", array($alias));
};

$row = $findByAlias('category', $com);

if ($com === '') {
    $row = $d->safeFetchOne(
        "select * from #_category where module = 1 and lang = ? limit 1",
        array(isset($_SESSION['lang']) ? $_SESSION['lang'] : '')
    );

    if ($row) {
        $row_multi_lang = $fetchMultiLang('category', $row['id_code']);
        $com            = $row['alias'];
        $source         = 'index';
        $type           = 'home';
    }
}
elseif (!empty($row)) {
    $row_multi_lang = $fetchMultiLang('category', $row['id_code']);
    $source         = $fetchTemplate($row['module']);
    $type           = 'page';
}
elseif (($row = $findByAlias('tintuc', $com)) && $com !== '') {
    $row_multi_lang = $fetchMultiLang('tintuc', $row['id_code']);
    $category       = $d->safeFetchOne(
        "select * from #_category where id_code = ? limit 1",
        array($row['id_loai'])
    );

    if (!isset($_SESSION['viewed_posts'][$row['id']])) {
        $d->safeExec("update #_tintuc set view = view + 1 where id = ?", array((int) $row['id']));
        $_SESSION['viewed_posts'][$row['id']] = true;
    }

    if (!empty($category)) {
        $source = $fetchTemplate($category['module']) . '-detail';
        $type   = 'single-post';
    }
}
elseif (($row = $findByAlias('sanpham', $com)) && $com !== '') {
    $row_multi_lang = $fetchMultiLang('sanpham', $row['id_code']);
    $category       = $d->safeFetchOne(
        "select * from #_category where id_code = ? limit 1",
        array($row['id_loai'])
    );

    if (!empty($category)) {
        $source = $fetchTemplate($category['module']) . '-detail';
        $type   = 'single-product';
    }
}
elseif (($row = $findByAlias('album', $com)) && $com !== '') {
    $row_multi_lang = $fetchMultiLang('album', $row['id_code']);
    $category       = $d->safeFetchOne(
        "select * from #_category where id_code = ? limit 1",
        array($row['id_loai'])
    );

    if (!empty($category)) {
        $source = $fetchTemplate($category['module']) . '-detail';
        $type   = 'article';
    }
}
elseif (($row = $findByAlias('video', $com)) && $com !== '') {
    $row_multi_lang = $fetchMultiLang('video', $row['id_code']);
    $category       = $d->safeFetchOne(
        "select * from #_category where id_code = ? limit 1",
        array($row['id_loai'])
    );

    if (!empty($category)) {
        $source = $fetchTemplate($category['module']) . '-detail';
        $type   = 'article';
    }
}
elseif ($com === 'giao-vien') {
    $source = 'giao-vien';
    $type   = 'page';
    $row    = array(
        'title'   => 'Đội ngũ giáo viên - Master Chuyên Anh',
        'ten'     => 'Đội ngũ giáo viên',
        'des'     => 'Đội ngũ giáo viên chuyên nghiệp, tận tâm và giàu kinh nghiệm tại Master Chuyên Anh.',
        'keyword' => 'giáo viên, master chuyên anh, ielts, tesol'
    );
}
elseif (!empty($com) && (
    (isset($_REQUEST['alias']) && $_REQUEST['alias'] === 'giao-vien') ||
    ($teacher_profile = $d->safeFetchOne(
        "SELECT p.*, t.full_name, t.teacher_code, t.gender 
         FROM #_lms_teacher_profiles p 
         JOIN #_lms_teachers t ON t.user_id = p.teacher_id 
         WHERE p.slug = ? AND p.show_on_website = 1 LIMIT 1",
        array($com)
    ))
)) {
    if (empty($teacher_profile)) {
        $teacher_profile = $d->safeFetchOne(
            "SELECT p.*, t.full_name, t.teacher_code, t.gender 
             FROM #_lms_teacher_profiles p 
             JOIN #_lms_teachers t ON t.user_id = p.teacher_id 
             WHERE p.slug = ? AND p.show_on_website = 1 LIMIT 1",
            array($com)
        );
    }
    if (!empty($teacher_profile)) {
        $source = 'giao-vien-detail';
        $type   = 'single-post';
        $row    = array(
            'title'   => (!empty($teacher_profile['seo_title']) ? $teacher_profile['seo_title'] : $teacher_profile['full_name']) . ' - Master Chuyên Anh',
            'ten'     => $teacher_profile['full_name'],
            'des'     => !empty($teacher_profile['seo_des']) ? $teacher_profile['seo_des'] : $teacher_profile['quote'],
            'keyword' => $teacher_profile['seo_keyword']
        );
    } else {
        $row = array(
            'title'   => '404 - Không tìm thấy giáo viên',
            'des'     => 'Thông tin giáo viên bạn yêu cầu không tồn tại.',
            'noindex' => 1,
        );
    }
}
else {
    // Định tuyến tĩnh
    $staticRoutes = array(
        'sitemap'     => array('sitemap',     ''),
        'tags'        => array('tin-tuc',     ''),
        'search'      => array('search',      ''),
        'author'      => array('author',      ''),
        'thanh-cong'  => array('thanh-cong',  'page'),
        'tra-cuu'     => array('tra-cuu',     'page'),
        'yeu-thich'   => array('yeu-thich',   'page'),
    );

    if (isset($staticRoutes[$com])) {
        list($source, $type) = $staticRoutes[$com];

        if ($com === 'search') {
            $keyword = isset($_GET['keyword']) ? ' ' . htmlspecialchars($_GET['keyword'], ENT_QUOTES, 'UTF-8') : '';
            $row['ten'] = $d->getTxt(136) . $keyword;
        }
    } else {
        // Không khớp bất kỳ route nào -> trang 404
        $row = array(
            'title'   => '404 - Không tìm thấy trang',
            'des'     => 'Trang bạn yêu cầu không tồn tại hoặc đã bị di chuyển.',
            'noindex' => 1,
        );
    }
}
