<?php

    $categoryIds = getCategoryTreeIds($row->id_code);
    $limit = (int)get_json('posts', 'paging') ?: 10;
    
    $allPost = NewsModel::query()
        ->where('id_loai', $categoryIds, 'IN')
        ->where('hien_thi', 1)
        ->orderBy('so_thu_tu')
        ->orderBy('id', 'DESC')
        ->get();
?>

<div class="block page page-pagination" data-limit="<?= $limit ?>">
    <div class="container-fluid">
        <?php if ($row->id_code == 242): ?>
            <div class="row g-3 g-lg-4 content-pagination">
                <?php foreach ($allPost as $value): ?>
                    <div class="col-lg-6">
                        <?php echo view('partials/components/card-service'); ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php else: ?>
            <div class="row g-3 g-lg-4 content-pagination">
                <?php foreach ($allPost as $value): ?>
                    <div class="col-md-6 col-lg-4">
                        <?php echo view('partials/components/card-post'); ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <div class="row mt-4">
            <div class="col-12 d-flex justify-content-center">
                <?php if (count($allPost) == 0): ?>
                    <p class="text-center"><?= $d->getTxt(143) ?: 'Nội dung đang được cập nhật' ?></p>
                <?php elseif(count($allPost) > $limit): ?>
                    <div class="arrow-pagination"></div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

