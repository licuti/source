<?php
    $pageProduct = $d->simple_fetch("select ten,id_code from #_category where id_code=100 and hien_thi=1 "._where_lang."");
    $list_id_product = $pageProduct['id_code'].$d->getIdsub($pageProduct['id_code']);
    $home_product = $d->o_fet("select sp.*, 
        (select ten from #_category where id_code = sp.id_loai and lang = '".$_SESSION['lang']."') as category_name,
        COALESCE((select min(IF(v.khuyen_mai > 0, v.khuyen_mai, v.gia)) from #_sanpham_bienthe v where v.id_sanpham = sp.id_code), IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia)) as min_price,
        COALESCE((select max(IF(v.khuyen_mai > 0, v.khuyen_mai, v.gia)) from #_sanpham_bienthe v where v.id_sanpham = sp.id_code), IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia)) as max_price
        from #_sanpham sp 
        where sp.id_loai in ($list_id_product) and sp.sp_sale=1 and sp.hien_thi=1 "._where_lang." 
        order by sp.so_thu_tu, sp.id desc 
        limit 24");
?>

<div class="block py-4">
    <div class="container-fluid">
        <div class="row mb-3 mb-md-4">
            <div class="col-12">
                <div class="box-title-flash-sale d-flex justify-content-between align-items-center">
                    <h2 class="main-title mb-0">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.5 2H18.5L13 9H20.5L8.5 22L11 12.5H4L9.5 2Z" fill="#FDE41B" stroke="#FDE41B" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        <?= $d->getTxt(1) ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.5 2H18.5L13 9H20.5L8.5 22L11 12.5H4L9.5 2Z" fill="#FDE41B" stroke="#FDE41B" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                    </h2>
                    <div class="box-time-counter">
                        <div class="time hour"></div>
                        <div class="">:</div>
                        <div class="time minute"></div>
                        <div class="">:</div>
                        <div class="time second"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="wrapper-slide">
                    <div class="swiper product-slide p-1">
                        <div class="swiper-wrapper">
                            <?php foreach ($home_product as $key => $value): ?>
                                <div class="swiper-slide" style="height: auto;">
                                    <?php include _source."module/card-product.php"; ?>
                                </div>
                            <?php endforeach ?>
                        </div>
                        <div class="swiper-button-next product-next"></div>
                        <div class="swiper-button-prev product-prev"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const hourEl = document.querySelector('.box-time-counter .time.hour');
        const minuteEl = document.querySelector('.box-time-counter .time.minute');
        const secondEl = document.querySelector('.box-time-counter .time.second');

        // Ham lay timestamp cuoi ngay theo gio Viet Nam (UTC+7)
        function getVietnamEndOfDay() {
            const now = new Date();
            // Chuyen doi sang mui gio Asia/Ho_Chi_Minh
            const vnNow = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Ho_Chi_Minh' }));
            vnNow.setHours(23, 59, 59, 999); // Dat moc la cuoi ngay hom nay theo gio VN
            return vnNow.getTime();
        }

        // CAU HINH MOC THOI GIAN (chon 1 trong 3 cach)
        
        // Cach 1: Dem nguoc den cuoi ngay hom nay theo gio Viet Nam
        let TARGET_TIME = getVietnamEndOfDay();

        // Cach 2: Ngay/gio cu the theo gio Viet Nam (bo comment de su dung)
        // let TARGET_TIME = new Date('2025-12-31T23:59:59+07:00').getTime();

        // Cach 3: Dem nguoc X gio ke tu luc load trang
        // let TARGET_TIME = Date.now() + (2 * 60 * 60 * 1000); // 2 gio nua

        function updateTimer() {
            const now = Date.now();
            let remaining = Math.max(0, Math.floor((TARGET_TIME - now) / 1000));

            if (remaining <= 0) {
                clearInterval(timer);
                hourEl.textContent = '00';
                minuteEl.textContent = '00';
                secondEl.textContent = '00';
                console.log('Het gio!');
                // TODO: Them logic khi het gio (an dong ho, hien thong bao, chuyen huong...)
                return;
            }

            const h = Math.floor(remaining / 3600);
            const m = Math.floor((remaining % 3600) / 60);
            const s = remaining % 60;

            hourEl.textContent = String(h).padStart(2, '0');
            minuteEl.textContent = String(m).padStart(2, '0');
            secondEl.textContent = String(s).padStart(2, '0');
        }

        // Cap nhat ngay khi load trang
        updateTimer();
        // Cap nhat moi giay (tinh lai tu Date.now() de dam bao do chinh xac)
        const timer = setInterval(updateTimer, 1000);
    });
</script>