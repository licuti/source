<?php
  // $tin_lienquan

$link=explode("?",$_SERVER['REQUEST_URI']);
if($link[1]!=''){
    $vari=explode("&",$link[1]);
    $search=array();
    foreach($vari as $item) {
        $str=explode("=",$item);
        $search["$str[0]"]=$str[1];
    }
}
$id_tacgia = $search['code'];
$row =     $d->simple_fetch("select hinh_anh, ho_ten, noi_dung from #_user where id= ".$id_tacgia."");
$tinlienquan = $d->o_fet("select * from #_tintuc where hien_thi =1 "._where_lang." and id_user = ".$id_tacgia." order by so_thu_tu ASC, id DESC limit 0,20 ");
?>
<main>
<div class="sodotrang">
    <div class="container">
        <ol vocab="https://schema.org/" typeof="BreadcrumbList" class="breadcrumb"> 
            <li property="itemListElement" typeof="ListItem" class="breadcrumb-item">
                <a property="item" typeof="WebPage" href="<?=URLPATH?>">
                <span property="name">Trang chủ</span></a>
                 <meta property="position" content="1">
            </li>
            
                <li property="itemListElement" typeof="ListItem" class="breadcrumb-item active">
                    <a property="item" typeof="WebPage" href="<?=$url_page?>">
                    <span property="name"><?=$row['ho_ten']?></span></a>
                    <meta property="position" content="2">
                </li>
        </ol>
    </div>
</div>
<?php
include 'ct-sp-hot.php';
?>
    <!-- news-detalis-area-start -->
    <div class="blog-area py-5">
        <div class="container py-5" style="background-color: #fff">
            <div class="news-detalis-content mb-50">
                <?=cf_tag_html($row['ho_ten'],_heading_ct_new,'title-news_ct mb-4')?>
                <div class="news-detalis">
                    <?php if($row['noi_dung']!=''){ ?>
                    <?=content_mucluc($row['noi_dung'],$url_page)?>
                    <?php } ?>
                </div>
            </div>
            <?php $lienquan = $d->getContent(101) ?>
            <?=cf_tag_html($lienquan['ten'],$lienquan['heading'],'video_home_title mb-4 mt-5')?>
            <div class="row" >
                <?php foreach ($tinlienquan as $key => $value) {?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="single-smblog mb-4">
                        <div class="smblog-thum">
                            <div class="blog-image w-getImageUrl">
                                <a href="<?=URLPATH.$value['alias']?>.html"><getImageUrl src="getImageUrl_data/images/<?=$value['hinh_anh']?>" alt="<?=$value['ten']?>"></a>
                            </div>
                        </div>
                        <div class="smblog-content">
                            <<?=_heading_cate_new?> class="tintuc_home_item_title"><a <?=  cf_tag_a_url($value['slug'], $value['alias'], $value['nofollow'], $value['target'])?>><?=$value['ten']?></a></<?=_heading_cate_new?>>
                            <span class="author mb-10"><?=$d->getTxt(118)?>:  <a href="#"><?=date('d M, Y', $value['ngay_dang']) ?></a></span>
                            <p><?=truncate_text($value['mo_ta'],150) ?></p>
                            <div class="smblog-foot pt-15">
                                <div class="post-readmore">
                                    <a href="<?=URLPATH.$value['alias']?>.html"> <?=$d->getTxt(114)?> <span class="icon"></span></a>
                                </div>
                                <div class="post-date">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>   
                <?php } ?>
            </div>
        </div>
        
        
    </div>
    <!-- news-detalis-area-end  -->
</main>
<?php  $why = $d->getContent(70);?>
<div class="why py-5">
    <div class="container">
        <?=cf_tag_html($why['ten'],$why['heading'],'why_title text-center mb-4')?>
        <div class="row">
            <?php foreach ($d->getContents(70) as $key => $value) {?>
            <div class="col-sm-4">
                <div class="why_item col-text">
                    <getImageUrl class="why_item_getImageUrl" src="<?=  getImageUrl($value['hinh_anh'])?>" alt="<?=$value['ten']?>" />
                    <div class="why_item_content">
                        <?=cf_tag_html($value['ten'],$value['heading'],'why_item_content_title')?>
                        <?=strip_tags($value['noi_dung'])?>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php 
$video_c = $d->getContent(51);
$vdieo_home = $d->getContents(51);
$truyenthong_c = $d->getContent(56);
$truyenthong_home = $d->getContents(56);
?>

<div class="br-xam py-5">
    <div class="container">
        <?php
        if(count($vdieo_home)>0){
        ?>
        <div class="video">
            <?=cf_tag_html($video_c['ten'],$video_c['heading'],'video_home_title mb-4')?>
            <div class="row">
                <div class="col-md-8 col-sm-12">
                    <div class="video_item video_item_big position-relative">
                        <a data-fancybox href="https://www.youtube.com/watch?v=<?=$vdieo_home[0]['ma_video']?>">
                            <getImageUrl src="<?=  getImageUrl($vdieo_home[0]['hinh_anh'])?>" alt="<?=$vdieo_home[0]['ten']?>" class="video_item_getImageUrl">
                            <span class="video_item_play  position-absolute top-50 start-50 translate-middle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-play-fill" viewBox="0 0 16 16">
                                    <path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>
                                </svg>
                            </span>
                            <span class="video_item_title"><?=$vdieo_home[0]['ten']?></span>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="row">
                        <?php foreach ($vdieo_home as $key => $value) {
                        if($key>0){    
                        ?>
                        <div class="col-md-12 col-sm-4">
                            <div class="video_item position-relative">
                                <a data-fancybox href="https://www.youtube.com/watch?v=<?=$value['ma_video']?>">
                                    <getImageUrl src="<?=  getImageUrl($value['hinh_anh'])?>" alt="<?=$value['ten']?>" class="video_item_getImageUrl">
                                    <span class="video_item_play position-absolute top-50 start-50 translate-middle">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-play-fill" viewBox="0 0 16 16">
                                            <path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>
                                        </svg>
                                    </span>
                                    <span class="video_item_title"><?=$value['ten']?></span>
                                </a>
                            </div>
                        </div>            
                        <?php }} ?>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
        <?php
        if(count($truyenthong_home)>0){
        ?>
        <div class="truyenthong">
            <?=cf_tag_html($truyenthong_c['ten'],$truyenthong_c['heading'],'video_home_title mb-4')?>
            <div class="row">
                <div class="col-md-4">
                    <div class="row">
                        <?php foreach ($truyenthong_home as $key => $value) {
                        if($key>0){    
                        ?>
                        <div class="col-md-12 col-sm-4">
                            <div class="video_item item-truyenthong position-relative">
                                <a <?=cf_tag_a($truyenthong_home[0]['link'], $truyenthong_home[00]['nofollow'], $truyenthong_home[0]['target'])?>  >
                                    <getImageUrl src="<?=  getImageUrl($value['hinh_anh'])?>" alt="<?=$value['ten']?>" class="video_item_getImageUrl">
                                    <span class="video_item_title"><?=$value['ten']?></span>
                                </a>
                            </div>
                        </div>            
                        <?php }} ?>
                    </div>
                </div>
                <div class="col-md-8 col-sm-12">
                    <div class="video_item video_item_big position-relative">
                        <a <?=cf_tag_a($truyenthong_home[0]['link'], $truyenthong_home[00]['nofollow'], $truyenthong_home[0]['target'])?> >
                            <getImageUrl src="<?=  getImageUrl($truyenthong_home[0]['hinh_anh'])?>" alt="<?=$truyenthong_home[0]['ten']?>" class="video_item_getImageUrl">
                            <span class="video_item_title"><?=$truyenthong_home[0]['ten']?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

<?php $danhgia_c = $d->getContent(61);
?>
<div class="danhgia_home py-5" style="background-image: url('<?=  getImageUrl($danhgia_c['hinh_anh'])?>')">
    <div class="container">
        <?=cf_tag_html($danhgia_c['ten'],$danhgia_c['heading'],'danhgia_home_title text-center mb-4')?>
        
        <div class=" position-relative danhmuc_home-body">
            <div class="swiper danhmuc_Swiper">
                <div class="swiper-wrapper">
                    <?php foreach ($d->getContents(61) as $key => $value) {?>
                    <div class="swiper-slide col-lg-2 col-md-3">
                        <div class="danhgia_home_item">
                           <getImageUrl  class="danhmuc_home_item_getImageUrl" src="<?=  getImageUrl($value['hinh_anh'])?>" alt="<?=$value['ten']?>" />
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <div class="swiper-button-next danhmuc_Swiper-next"></div>
            <div class="swiper-button-prev danhmuc_Swiper-prev"></div>
        </div>
    </div>
</div>
<script>
    jQuery(document).ready(function($) {
        $('.share').click(function() {
            var NWin = window.open($(this).prop('href'), '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=300,width=600');
            if (window.focus){NWin.focus();}
            return false;
        });
    });
</script>