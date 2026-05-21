<?php	

$seo_title = $row->title ?? '';
$seo_keyword = $row->keyword ?? '';	
$seo_description = $row->des ?? '';

// Lấy ảnh chia sẻ (ưu tiên ảnh của trang hiện tại, nếu không có thì lấy logo từ SiteInfoService)
$getImageUrl_cn = (!empty($row->hinh_anh)) ? getImageUrl($row->hinh_anh) : site('logo');
if (empty($getImageUrl_cn)) {
    $getImageUrl_cn = site('logo');
}

// Xây dựng URL hiện tại chuẩn xác
$current_url = url($_SERVER['REQUEST_URI'] ?? '');

?>
<?php if(($row->noindex ?? 0) == 1 || ($category->noindex ?? 0) == 1): ?>
<meta name="robots" content="noindex">
<?php endif; ?>

<title><?= e($seo_title) ?></title>
<meta name="keywords" content="<?= e($seo_keyword) ?>" />
<meta name="description" content="<?= e($seo_description) ?>" />
<link href="<?= $current_url ?>" rel="canonical" />
<link href="<?= site('favicon') ?>" rel="shortcut icon" type="image/x-icon" />

<?php include 'sitemap/seo_head.inc';?>

<!-- Twitter Card -->
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= e($seo_title) ?>">
<meta name="twitter:site" content="@<?= $current_url ?>">
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
