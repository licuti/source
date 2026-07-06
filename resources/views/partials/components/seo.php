<?php	

$current_lang = $_SESSION['lang'] ?? 'vi';

$seo_title = $row->title ?? '';
if (empty($seo_title)) {
    $seo_title = \App\Models\OptionModel::getValue('seo_title_' . $current_lang, site('company'));
}

$seo_keyword = $row->keyword ?? '';	
if (empty($seo_keyword)) {
    $seo_keyword = \App\Models\OptionModel::getValue('seo_keyword_' . $current_lang, '');
}

$seo_description = $row->des ?? '';
if (empty($seo_description)) {
    $seo_description = \App\Models\OptionModel::getValue('seo_description_' . $current_lang, '');
}

// Lấy ảnh chia sẻ (ưu tiên ảnh của trang hiện tại, sau đó ảnh cấu hình SEO, cuối cùng là logo)
$getImageUrl_cn = (!empty($row->hinh_anh)) ? getImageUrl($row->hinh_anh) : \App\Models\OptionModel::getValue('seo_image', '');
if (empty($getImageUrl_cn)) {
    $getImageUrl_cn = site('logo');
}

$fb_app_id = \App\Models\OptionModel::getValue('seo_facebook_app_id', '');
$twitter_site = \App\Models\OptionModel::getValue('seo_twitter_site', '');

// Xây dựng URL hiện tại chuẩn xác
$current_url = url($_SERVER['REQUEST_URI'] ?? '');

?>
<?php 
$is_noindex = ($row->noindex ?? 0) == 1 || ($category->noindex ?? 0) == 1 || \App\Models\OptionModel::getValue('seo_noindex', '0') == '1';
if($is_noindex): 
?>
<meta name="robots" content="noindex, nofollow">
<?php else: ?>
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<?php endif; ?>

<title><?= e($seo_title) ?></title>
<meta name="keywords" content="<?= e($seo_keyword) ?>" />
<meta name="description" content="<?= e($seo_description) ?>" />
<link href="<?= $current_url ?>" rel="canonical" />
<link href="<?= site('favicon') ?>" rel="shortcut icon" type="image/x-icon" />

<?php include 'sitemap/seo_head.inc';?>

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seo_title) ?>">
<?php if(!empty($twitter_site)): ?>
<meta name="twitter:site" content="<?= e($twitter_site) ?>">
<?php else: ?>
<meta name="twitter:site" content="@<?= $current_url ?>">
<?php endif; ?>
<meta name="twitter:description" content="<?= e($seo_description) ?>">
<meta name="twitter:image" content="<?= $getImageUrl_cn ?>">
<meta name="twitter:image:alt" content="<?= e($seo_title) ?>">

<!-- Open Graph -->
<?php if(($type ?? '') == 'article'): ?>
<meta property="og:type" content="article">
<meta property="article:published_time" content="<?= date("c", $row->ngay_dang ?? time()) ?>">
<meta property="article:modified_time" content="<?= date("c", $row->cap_nhat ?? time()) ?>">
<meta property="article:section" content="<?= e($category->ten ?? '') ?>">
<?php if(!empty($row->tags_hienthi)): 
    $arr_tag = explode(',', $row->tags_hienthi);
    foreach($arr_tag as $tag): 
        if(trim($tag) != ''): ?>
<meta property="article:tag" content="<?= e(trim($tag)) ?>">
<?php   endif; 
    endforeach; 
endif; ?>
<?php elseif(($type ?? '') == 'product'): ?>
<meta property="og:type" content="product">
<meta property="product:plural_title" content="">
<meta property="product:price.amount" content="<?= $row->gia ?? 0 ?>">
<meta property="product:price.currency" content="<?= config('lang.0.price', 'VND') ?>">
<?php else: ?>
<meta property="og:type" content="website">
<?php endif; ?>

<meta property="og:url" content="<?= $current_url ?>" />
<meta property="og:title" content="<?= e($seo_title) ?>" />
<meta property="og:image" content="<?= $getImageUrl_cn ?>" />
<meta property="og:description" content="<?= e($seo_description) ?>" />     
<?php if(!empty($fb_app_id)): ?>
<meta property="fb:app_id" content="<?= e($fb_app_id) ?>" />
<?php endif; ?>
<meta property="fb:page_id" content="<?= site('messenger') ?>" />

<!-- Khai báo ngôn ngữ -->
<?php 
$langs = config('lang', []);
if (count($langs) > 1): 
    $link_lang = (($com ?? '') != '') ? ($row->alias ?? '') . '.html' : '';
    foreach ($langs as $value): ?>
<link rel="alternate" hreflang="<?= $value['code'] ?>" href="<?= url($value['code'] . '/' . $link_lang) ?>" />
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?= url(config('locale', 'vi') . '/' . $link_lang) ?>" />
<?php endif; ?>

<script type="application/ld+json">
{
    "@context" : "http://schema.org",
    "@type" : "Organization",
    "legalname": "<?= e(site('company')) ?>",
    "url" : "<?= url() ?>",
    "contactPoint" : [
      {
        "@type" : "ContactPoint",
        "telephone" : "+84<?= site('hotline') ?>",
        "contactType" : "customer service",
        "contactOption" : "TollFree",
        "areaServed" : "VN"
      }
    ],
    "logo": "<?= site('logo') ?>"
}
</script> 
