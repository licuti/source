<?php
    $id_loai = $row['id_code'].$d->getIdsub($row['id_code']);
    $album = $d->o_fet("select * from #_album where id_loai in ($id_loai) and hien_thi=1 "._where_lang." order by so_thu_tu ASC, id DESC");
?>

<style>
    .wrapper-galley {
    display: grid;
    /* Chia grid thành 4 cột bằng nhau */
    grid-template-columns: repeat(4, 1fr);
    /* Chiều cao cố định cho mỗi hàng để tạo sự đồng nhất */
    grid-auto-rows: 200px; 
    gap: 10px; /* Khoảng cách giữa các ảnh */
    /* Tự động điền các ô trống bằng các item nhỏ hơn */
    grid-auto-flow: dense; 
    width: 100%;
    margin: 0 auto;
}

.wrapper-galley-item {
    position: relative;
    overflow: hidden;
    width: 100%;
    height: 100%;
}

/* --- LOGIC TẠO KHỐI TO NHỎ ĐAN XEN --- */

/* Item thứ 1: To nhất (chiếm 2 cột, 2 hàng) - Giống ảnh mẫu */
.wrapper-galley-item:nth-child(4n+1) {
    grid-column: span 2;
    grid-row: span 2;
}

/* Item thứ 5: Chiếm 2 cột, 1 hàng */
.wrapper-galley-item:nth-child(4n+5) {
    grid-column: span 2;
    grid-row: span 1;
}

/* Item thứ 7: Chiếm 1 cột, 2 hàng */
.wrapper-galley-item:nth-child(4n+7) {
    grid-column: span 1;
    grid-row: span 2;
}


.box-gallery, .box-thumbnail {
    width: 100%;
    height: 100%;
    position: relative;
}

.box-gallery .box-content{
    position: absolute;
    inset: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.3);
}

.box-gallery .box-content .title{
    padding: 0.5rem 0;
    border-top: 1px solid #fff;
    border-bottom: 1px solid #fff;
    color: #fff;
    font-size: var(--fs-20);
    text-align: center;
}



/* Tablet: 2 cột */
@media (max-width: 992px) {
    .wrapper-galley {
        grid-template-columns: repeat(2, 1fr);
    }
    .wrapper-galley-item:nth-child(n) {
        grid-column: span 1; 
        grid-row: span 1;
    }
    /* Vẫn giữ 1 cái to ở đầu cho đẹp */
    .wrapper-galley-item:nth-child(1) {
        grid-column: span 2;
        grid-row: span 2;
    }
}

/* Mobile: 1 cột */
@media (max-width: 480px) {
    .wrapper-galley {
        grid-template-columns: repeat(1, 1fr);
        grid-auto-rows: 250px;
    }
    .wrapper-galley-item:nth-child(n) {
        grid-column: span 1;
        grid-row: span 1;
    }
}

</style>

<div class="block page">
    <div class="container-fluid">
        <div class="row mb-3 mb-md-4">
            <div class="col-12">
                <h2 class="main-title text-center"><?= $row['ten'] ?></h2>
            </div>
        </div>
        <div class="row">
                <div class="col-12">
                    <div class="wrapper-galley">
                        <?php foreach ($album as $key => $value): ?>
                            <div class="wrapper-galley-item">
                                <a <?= createAlias($value) ?>>
                                    <div class="box-gallery">
                                        <div class="box-thumbnail">
                                            <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="" class="image-cover"/>
                                        </div>
                                        <div class="box-content">
                                            <h3 class="title"><?= $value['ten'] ?></h3>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
        </div>
    </div>
</div>