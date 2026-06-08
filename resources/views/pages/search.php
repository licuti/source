<?php    

    $product_results = [];
    $post_results = [];

    if(isset($_GET['keyword'])) {
        $key_search = addslashes($_GET['keyword']);
        $category_id = isset($_GET['id_code']) ? (int)$_GET['id_code'] : 0;
        
        // 1. Tìm kiếm sản phẩm
        $p_query = ProductModel::query()
            ->where('ten', "%$key_search%", 'LIKE')
            ->where('status', 'publish');
            
        if ($category_id > 0) {
            $list_ids = getCategoryTreeIds($category_id);
            $p_query->where('id_loai', $list_ids, 'IN');
        }
        $product_results = $p_query->orderBy('so_thu_tu')->orderBy('id', 'DESC')->get();
        // Eager load variants cho kết quả tìm kiếm sản phẩm
        if (!empty($product_results)) {
            ProductModel::query()->withVariants($product_results);
        }

        // 2. Tìm kiếm tin tức/bài viết
        $post_results = \App\Models\PostModel::query()
            ->where('ten', "%$key_search%", 'LIKE')
            ->where('status', 'publish')
            ->orderBy('so_thu_tu')
            ->orderBy('id', 'DESC')
            ->get();
    }
?>

<div class="block page">
    <div class="container-fluid">
        <?php if (!empty($product_results)): ?>
            <div class="row mb-4">
                <div class="col-12 text-center mb-3">
                     <h3 class="fw-bold text-x"><?= $d->getTxt(98) ?: 'Sản phẩm tìm thấy' ?></h3>
                </div>
                <?php foreach ($product_results as $value): ?>
                    <div class="col-6 col-lg-3 mt-3">
                        <?php echo view('partials/components/card-product', ['value' => $value]); ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if (!empty($post_results)): ?>
            <div class="row">
                <div class="col-12 text-center mb-3">
                    <h3 class="fw-bold text-x"><?= $d->getTxt(137) ?: 'Tin tức tìm thấy' ?></h3>
                </div>
                <?php foreach ($post_results as $value): ?>
                    <div class="col-md-6 col-lg-4 mt-3">
                        <?php echo view('partials/components/card-post'); ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if (empty($product_results) && empty($post_results) && isset($_GET['keyword'])): ?>
            <div class="row py-5">
                <div class="col-12 text-center">
                    <i class="fa fa-search fa-3x mb-3 text-muted"></i>
                    <p class="fs-5"><?= $d->getTxt(119) ?: 'Không tìm thấy kết quả phù hợp với từ khóa của bạn.' ?></p>
                    <a href="index.html" class="btn btn-primary">Quay về trang chủ</a>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>

