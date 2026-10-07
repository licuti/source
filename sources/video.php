<?php 
$list_video = $d->o_fet("select * from #_video where hien_thi = 1 and id_loai = '".$row['id_code']."'");
?>
<!-- ==================== End Navbar ==================== -->
<div class="circle-bg">
    <div class="circle-color fixed">
        <div class="gradient-circle"></div>
        <div class="gradient-circle two"></div>
    </div>
</div>
<!-- ==================== Start header ==================== -->
<header class="works-header fixed-slider hfixd valign sub-bg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9 static">
                <div class="capt mt-50">
                    <div class="parlx text-center">
                        <h1 class="color-font"><?=$row['ten']?></h1>
                    </div>
                    <div class="bactxt custom-font valign">
                        <span>  </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="line bottom right"></div>
</header>
<!-- ==================== End header ==================== -->
<div class="main-content">
    <!-- ==================== Start works ==================== -->
    <section class="portfolio three-column section-padding pb-70">
        <div class="container">
            <div class="row">
                <!-- filter links -->
                
                <!-- gallery -->
                <div class="gallery full-width">
                    <?php foreach ($list_video as $key => $value) {?>
                    <!-- gallery item -->
                    <div class="col-lg-6 col-md-6 items album_<?=$value['id_album']?>">
						<?php if($value['video']!=''){ ?>
						<div class="item-img wow fadeInUp" data-wow-delay=".2s">
                            <a data-fancybox href="#video<?=$value['id']?>">
                                <img src="media/images/<?=$value['hinh_anh']?>" alt="image">
								<span class="fas fa-play" style="position: absolute;position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);z-index: 100;color: #24c2ea;width: 70px;height: 70px;line-height: 70px;background-color: #060606a6;border-radius: 50%;text-align: center;font-size: 29px;color: #fff;padding-left: 9px;box-shadow: 1px 1px 10px 1px #fff;"></span>
							</a>
						</div>
						<div class="cont">
							<h6><?=$value['ten']?></h6>
						</div>
						<video width="90%" height="90%" controls id="video<?=$value['id']?>" style="display:none;">
							<source src="media/images/<?=$value['video']?>" type="video/mp4">
						</video>
						<?php }elseif($value['ma_video']!=''){?>
						<div class="item-img wow fadeInUp" data-wow-delay=".2s">
                            <a data-fancybox href="https://www.youtube.com/watch?v=<?=$value['ma_video']?>">
                                <img src="media/images/<?=$value['hinh_anh']?>" alt="image">
								<span class="fas fa-play" style="position: absolute;position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);z-index: 100;color: #24c2ea;width: 70px;height: 70px;line-height: 70px;background-color: #060606a6;border-radius: 50%;text-align: center;font-size: 29px;color: #fff;padding-left: 9px;box-shadow: 1px 1px 10px 1px #fff;"></span>
							</a>
						</div>
						<div class="cont">
							<h6><?=$value['ten']?></h6>
						</div>
						<?php }else{?>
                        <div class="item-img wow fadeInUp" data-wow-delay=".2s">
                            <a href="media/images/<?=$value['hinh_anh']?>" data-fancybox="images">
                                <img src="media/images/<?=$value['hinh_anh']?>" alt="image">
                            </a>
                        </div>
						<div class="cont">
							<h6><?=$value['ten']?></h6>
						</div>
						<?php } ?>
                    </div> 
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
</div>
    <!-- ==================== End works ==================== -->