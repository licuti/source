<?php
/**
 * SEO head tags — trang chủ lấy từ #_seo, trang con ưu tiên meta $row.
 */

$seo_site = null;
$isHome   = empty($com) || (isset($type) && $type === 'home');

if ($isHome) {
    $langCode = defined('LANG') ? LANG : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'vi');
    $seo_site = $d->simple_fetch("select * from #_seo where lang = '" . addslashes($langCode) . "' limit 1");
    if (!is_array($seo_site) || empty($seo_site)) {
        $seo_site = $d->simple_fetch('select * from #_seo limit 1');
    }
}

$seo_title       = '';
$seo_keyword     = '';
$seo_description = '';

if (is_array($seo_site) && !empty($seo_site)) {
    $seo_title       = $seo_site['title'] ?? '';
    $seo_keyword     = $seo_site['keyword'] ?? '';
    $seo_description = $seo_site['des'] ?? '';
}

if (!empty($row) && is_array($row)) {
    if (!empty($row['title'])) {
        $seo_title = $row['title'];
    } elseif (!empty($row['ten']) && (!$isHome || $seo_title === '')) {
        $seo_title = $row['ten'];
    }
    if (isset($row['keyword']) && $row['keyword'] !== '') {
        $seo_keyword = $row['keyword'];
    }
    if (isset($row['des']) && $row['des'] !== '') {
        $seo_description = $row['des'];
    }
}

$og_title      = $seo_title;
$og_des        = $seo_description;
$og_image_file = '';

if (is_array($seo_site)) {
    if (!empty($seo_site['og_title'])) {
        $og_title = $seo_site['og_title'];
    }
    if (!empty($seo_site['og_des'])) {
        $og_des = $seo_site['og_des'];
    }
    if (!empty($seo_site['og_image'])) {
        $og_image_file = $seo_site['og_image'];
    }
}

$favicon = '';
$getImageUrl_cn = '';

if ($og_image_file !== '') {
    $getImageUrl_cn = function_exists('getImageUrl')
        ? getImageUrl($og_image_file)
        : ((defined('URLPATH') ? URLPATH : '') . 'media/images/' . $og_image_file);
} elseif (!empty($row['hinh_anh'])) {
    $getImageUrl_cn = function_exists('getImageUrl') ? getImageUrl($row['hinh_anh']) : '';
}

if ($getImageUrl_cn == '') {
    $getImageUrl_cn = _logo;
}
?>
<?php if ((!empty($row['noindex']) && $row['noindex'] == 1) || (!empty($category['noindex']) && $category['noindex'] == 1)) { ?>
<meta name="robots" content="noindex">
<?php } ?>
<title><?= $seo_title ?></title>
<meta name="keywords" content="<?= $seo_keyword ?>" />
<meta name="description" content="<?= $seo_description ?>" />
<link href="<?= $d->fullAddress() ?>" rel="canonical" />
<link href="<?= _favicon ?>" rel="shortcut icon" type="image/x-icon" />
<?php include 'sitemap/seo_head.inc'; ?>
<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $og_title ?>">
<meta name="twitter:site" content="@<?= _url_page ?>">
<meta name="twitter:description" content="<?= $og_des ?>">
<meta name="twitter:image" content="<?= $getImageUrl_cn ?>">
<meta name="twitter:image:alt" content="<?= $og_title ?>">
<!-- Open Graph -->
<?php if (isset($type) && $type == 'article') { ?>
<meta property="og:type" content="article">
<meta property="article:published_time" content="<?= date('c', $row['ngay_dang'] ?? time()) ?>">
<meta property="article:modified_time" content="<?= date('c', $row['cap_nhat'] ?? time()) ?>">
<meta property="article:section" content="<?= $category['ten'] ?? '' ?>">
<?php
if (!empty($row['tags_hienthi'])) {
    $arr_tag = explode(',', $row['tags_hienthi']);
    for ($i = 0; $i < count($arr_tag); $i++) {
        if (trim($arr_tag[$i]) != '') {
            ?>
<meta property="article:tag" content="<?= trim($arr_tag[$i]) ?>">
<?php
        }
    }
}
?>
<?php } elseif (isset($type) && $type == 'product') { ?>
<meta property="og:type" content="product">
<meta property="product:plural_title" content="">
<meta property="product:price.amount" content="<?= $row['gia'] ?? '' ?>">
<meta property="product:price.currency" content="<?= get_json('lang', '0', 'price') ?>">
<?php } else { ?>
<meta property="og:type" content="website">
<?php } ?>
<meta property="og:url" content="<?= _url_page ?>" />
<meta property="og:title" content="<?= $og_title ?>" />
<meta property="og:image" content="<?= $getImageUrl_cn ?>" />
<meta property="og:description" content="<?= $og_des ?>" />
<meta property="fb:page_id" content="<?= _messenger ?>" />
<?php if (count(get_json('lang')) > 1) {
    if (!empty($com)) {
        $link_lang = !empty($row['alias']) ? $row['alias'] . '.html' : '';
    } else {
        $link_lang = '';
    }
    ?>
<?php foreach (get_json('lang') as $key => $value) { ?>
<link rel="alternate" hreflang="<?= $value['code'] ?>" href="<?= URLPATH . $value['code'] ?>/<?= $link_lang ?>" />
<?php } ?>
<link rel="alternate" hreflang="x-default" href="<?= URLPATH . LANG ?>/<?= $link_lang ?>" />
<?php } ?>
<script type="application/ld+json">
    { "@context" : "http://schema.org",
    "@type" : "Organization",
    "legalname":"<?= _ten_cong_ty ?>",
    "url" : "<?= _web_page ?>",
    "contactPoint" : [
      { "@type" : "ContactPoint",
        "telephone" : "+84<?= _hotline ?>",
        "contactType" : "customer service",
        "contactOption" : "TollFree",
        "areaServed" : "VN"
      } ],
      "logo": "<?= _logo ?>"}
</script>
