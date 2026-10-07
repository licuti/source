<?php    

    if(isset($_GET['keyword'])) {
        $key_search = validate_content($_GET['keyword']);
        $id_code = isset($_GET['id_code']) ? (int)$_GET['id_code'] : 0;
        
        $where_p = " ten like '%".$key_search."%' and hien_thi = 1 " . _where_lang;
        $where_n = " ten like '%".$key_search."%' and hien_thi = 1 " . _where_lang;
        
        if ($id_code > 0) {
            $list_id = $id_code . $d->getIdsub($id_code);
            $where_p .= " and id_loai in ($list_id)";
        }

        $product_results = $d->o_fet("select *, (select ten from #_category where id_code = id_loai and lang = '".$_SESSION['lang']."') as category_name from #_sanpham where $where_p order by so_thu_tu ASC, id DESC");
        $post_results = $d->o_fet("select * from #_tintuc where $where_n order by so_thu_tu ASC, id DESC");
    }
?>

<div class="block page">
    <div class="container-fluid">
        <?php if ($product_results): ?>
            <div class="row mb-4">
                <div class="col-12">
                     <h3 class="fw-bold text-x text-center"><?= $d->getTxt(98) ?></h3>
                </div>
                <?php if (count($product_results) == 0): ?>
                    <div class="col-12 text-center">
                        <?= $d->getTxt(142) ?>
                    </div>
                <?php endif ?>
                <?php foreach ($product_results as $key => $value): ?>
                    <div class="col-6 col-lg-3 mt-3 mt-md-4">
                        <?php include _source."module/card-product.php"; ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if ($post_results): ?>
            <div class="row">
                <div class="col-12">
                    <h3 class="fw-bold text-x text-center"><?= $d->getTxt(137) ?></h3>
                </div>
                <?php if (count($post_results) == 0): ?>
                    <div class="col-12 text-center">
                        <?= $d->getTxt(143) ?>
                    </div>
                <?php endif ?>

                <?php foreach ($post_results as $key => $value): ?>
                    <div class="col-md-6 col-lg-4 mt-3 mt-md-4">
                        <?php include _source."module/card-post.php"; ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if (count($product_results) == 0 && count($post_results) == 0): ?>
            <div class="row">
                <div class="col-12 text-center">
                    <p><?= $d->getTxt(119) ?></p>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>

