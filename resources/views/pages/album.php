<?php
    $id_loai = $row->id_code . CategoryModel::query()->getChildrenIds($row->id_code);
    
    // Sử dụng AlbumModel đã tạo ở lượt trước
    $album = AlbumModel::query()
        ->where('id_loai', $id_loai, 'IN')
        ->where('hien_thi', 1)
        ->orderBy('so_thu_tu')
        ->orderBy('id', 'DESC')
        ->get();
?>

<div class="block page">
    <div class="container-fluid">
        <div class="row g-3">
            <?php foreach ($album as $value): ?>
                <div class="col-md-4 col-lg-3">
                    <a href="<?= url($value->alias) ?>">
                        <div class="box-gallery box-hover-zoom">
                            <div class="box-thumbnail">
                                <div class="inner-thumbnail ratio ratio-1x1">
                                    <img src="<?= getImageUrl($value->hinh_anh) ?>" alt="<?= $value->ten ?>" class="image-cover"/>
                                </div>
                            </div>
                            <div class="box-content text-center mt-2">
                                <h3 class="title fs-6"><?= $value->ten ?></h3>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach ?>
            <?php if (empty($album)): ?>
                <div class="col-12 text-center py-5">
                    <p><?= $d->getTxt(141) ?: 'Nội dung đang được cập nhật' ?></p>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>