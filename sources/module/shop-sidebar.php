<?php
    $getCategorySidebar = $d->o_fet("select ten,alias,id_code,lang from #_category where id_loai=100 and hien_thi=1 order by so_thu_tu ASC, id DESC");
    
    // Lấy các thuộc tính lọc toàn cầu (id_sanpham = 0)
    $globalAttributes = $d->o_fet("select * from #_thuoctinh where id_sanpham = 0 "._where_lang." order by sap_xep ASC, id DESC");
    // Lấy các giá trị đã chọn từ bộ lọc
    $selectedPrice = $_GET['price'] ?? '';
    $selectedAttrs = $_GET['attrs'] ?? [];
    $selectedCats  = $_GET['cats'] ?? [];
    if (!is_array($selectedCats)) $selectedCats = [];
    $selectedSort      = $_GET['sort'] ?? '';
    $selectedInStock   = isset($_GET['in_stock']) ? (int)$_GET['in_stock'] : 0;
    $selectedMinRating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;


    

    $grouped = [];

    foreach ($getCategorySidebar as $item) {
        $id = $item['id_code'];
        $lang = $item['lang'];

        if (!isset($grouped[$id])) {
            $grouped[$id] = [];
        }

        $grouped[$id][$lang] = $item;
    }

    // echo "<pre>";
    // print_r($grouped);
    // echo "</pre>";
?>

<div class="shop-sidebar">
    <?php /* ?>
    <div class="sidebar-widget">
        <h4 class="widdget-title">DANH MỤC SẢN PHẨM</h4>
        <div class="widdget-content widdget-category">
            <ul class="list-unstyled mb-0">
                <?php 
                $lang = $_SESSION['lang'] ?? 'vi';

                foreach ($grouped as $id => $langs): 
                    $vi = $langs['vi'] ?? null;
                    $en = $langs['en'] ?? null;

                    // ngôn ngữ chính
                    $main = $langs[$lang] ?? $vi ?? $en;

                    // ngôn ngữ phụ (ngược lại)
                    $sub = ($lang == 'vi') 
                        ? ($en ?? $vi) 
                        : ($vi ?? $en);
                ?>
                    <li class="<?= $row['id_code'] == $id ? 'active' : '' ?>">
                        <a <?= createAlias($main) ?>>
                            <div class="label">
                                <div class="label-main-lg">
                                    <?= $main['ten'] ?>
                                </div>
                                <div class="label-sub-lg">
                                    <?= $sub['ten'] ?>
                                </div>
                            </div>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
    <?php */ ?>


    <form action="" method="get" class="filter-form">
        <?php if($selectedSort != '' && $selectedSort != 'default'): ?>
            <input type="hidden" name="sort" value="<?=htmlspecialchars($selectedSort)?>">
        <?php endif; ?>
        <?php if(isset($_GET['key']) && $_GET['key'] != ''): ?>
            <input type="hidden" name="key" value="<?=htmlspecialchars($_GET['key'])?>">
        <?php endif; ?>
        <!-- Danh mục -->
        <div class="sidebar-widget mb-4">
            <div class="widdget widdget-category">
                <h4 class="widdget-title">Danh mục</h4>
                <div class="filter-list d-flex flex-column gap-2">
                    <?php foreach ($getCategorySidebar as $key => $value): ?>
                        <?php 
                            $childCategory = $d->o_fet("select * from #_category where id_loai=".$value['id_code']." and hien_thi=1 "._where_lang." order by so_thu_tu ASC, id DESC"); 
                        ?>
                        <div class="form-check w-100">
                            <input class="form-check-input" type="checkbox" name="cats[]" value="<?=$value['id_code']?>" id="cat-<?=$value['id_code']?>" <?=in_array($value['id_code'], $selectedCats)?'checked':''?>>
                            <label class="form-check-label" for="cat-<?=$value['id_code']?>"><?=$value['ten']?></label>
                        </div>
                        <?php if (count($childCategory) > 0): ?>
                            <div class="d-flex flex-column gap-1 ms-4 mb-2">
                                <?php foreach ($childCategory as $key => $child): ?>
                                    <div class="form-check w-100">
                                        <input class="form-check-input" type="checkbox" name="cats[]" value="<?=$child['id_code']?>" id="cat-<?=$child['id_code']?>" <?=in_array($child['id_code'], $selectedCats)?'checked':''?>>
                                        <label class="form-check-label" for="cat-<?=$child['id_code']?>"><?=$child['ten']?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Lọc theo Giá -->
        <div class="sidebar-widget mb-4">
            <h4 class="widdget-title">Khoảng giá</h4>
            
            <!-- Range Slider -->
            <div class="price-range-slider-wrapper mb-4">
                <div class="price-range-slider mb-3"></div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="price-input">
                        <span class="price-min-display fw-bold text-primary"></span>
                    </div>
                    <div class="price-divider">-</div>
                    <div class="price-input text-end">
                        <span class="price-max-display fw-bold text-primary"></span>
                    </div>
                </div>
                <input type="hidden" name="price" class="price-range-value price-input-control" value="<?=htmlspecialchars($selectedPrice)?>">
                <button type="button" class="btn-custom btn-solid btn-x w-100 btn-apply-price">Lọc giá</button>
            </div>

            <!-- Radio Filters (Hidden as requested) -->
            <div class="filter-list d-none">

                <?php 
                    $priceRanges = [
                        '' => 'Tất cả',
                        '0-1000000' => 'Dưới 1.000.000 ₫',
                        '1000000-5000000' => '1.000.000 ₫ - 5.000.000 ₫',
                        '5000000-10000000' => '5.000.000 ₫ - 10.000.000 ₫',
                        '10000000-0' => 'Trên 10.000.000 ₫'
                    ];
                    foreach ($priceRanges as $val => $label):
                ?>
                <div class="form-check mb-1">
                    <input class="form-check-input price-input-control" type="radio" name="price" value="<?=$val?>" id="price-<?=$val?>" <?=$selectedPrice==$val?'checked':''?> onchange="updateSliderFromRadio(this)">
                    <label class="form-check-label" for="price-<?=$val?>"><?=$label?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Lọc theo Kho hàng -->
        <div class="sidebar-widget mb-4">
            <h4 class="widdget-title">Tình trạng</h4>
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="in_stock" value="1" id="filter-instock" <?= ($selectedInStock == 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="filter-instock">Chỉ hiện sản phẩm còn hàng</label>
            </div>
        </div>

        <!-- Lọc theo Đánh giá -->
        <div class="sidebar-widget mb-4">
            <h4 class="widdget-title">Đánh giá</h4>
            <div class="d-flex flex-column gap-2 mt-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="min_rating" value="" id="rating-all" <?= ($selectedMinRating == 0) ? 'checked' : '' ?>>
                    <label class="form-check-label text-muted small" for="rating-all">Tất cả</label>
                </div>
                <?php foreach ([5, 4, 3] as $star): ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="min_rating" value="<?= $star ?>" id="rating-<?= $star ?>" <?= ($selectedMinRating == $star) ? 'checked' : '' ?>>
                    <label class="form-check-label d-flex align-items-center gap-1" for="rating-<?= $star ?>">
                        <?php for($s=1;$s<=5;$s++): ?><i class="fa fa-star<?= $s <= $star ? ' text-warning' : '-o text-muted' ?> fa-sm"></i><?php endfor; ?>
                        <span class="ms-1 text-muted small"><?= $star == 5 ? '5 sao' : 'Từ '.$star.' sao trở lên' ?></span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php foreach ($globalAttributes as $attr): ?>
            <?php 
                $attrValues = $d->o_fet("select * from #_thuoctinh_giatri where id_thuoctinh = ".$attr['id_code']." "._where_lang." order by id ASC");
                if (count($attrValues) == 0) continue;
            ?>
            <div class="sidebar-widget mb-4">
                <h4 class="widdget-title"><?=$attr['ten']?></h4>
                <div class="filter-list d-flex flex-wrap gap-2">
                    <?php if ($attr['loai'] == 'color'): ?>
                        <?php foreach ($attrValues as $val): ?>
                            <div class="color-swatch-item">
                                <input type="checkbox" name="attrs[]" value="<?=$val['id_code']?>" id="attr-<?=$val['id_code']?>" <?=in_array($val['id_code'], $selectedAttrs)?'checked':''?> class="d-none">
                                <label for="attr-<?=$val['id_code']?>" class="color-swatch <?=in_array($val['id_code'], $selectedAttrs)?'active':''?>" style="background-color: <?=$val['gia_tri']?>;" title="<?=$val['ten']?>"></label>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($attrValues as $val): ?>
                            <div class="form-check w-100">
                                <input class="form-check-input" type="checkbox" name="attrs[]" value="<?=$val['id_code']?>" id="attr-<?=$val['id_code']?>" <?=in_array($val['id_code'], $selectedAttrs)?'checked':''?>>
                                <label class="form-check-label" for="attr-<?=$val['id_code']?>"><?=$val['ten']?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($selectedPrice != '' || count($selectedAttrs) > 0 || count($selectedCats) > 0 || $selectedInStock || $selectedMinRating > 0 || ($selectedSort != '' && $selectedSort != 'default')): ?>
            <div class="sidebar-widget mt-2">
                <a href="<?=$com?>.html" class="btn btn-sm btn-secondary w-100"><i class="fa fa-times me-1"></i> Xóa tất cả bộ lọc</a>
            </div>
        <?php endif; ?>
    </form>

</div>

<script>
    window.minPriceRange = <?= (int)($min_price_range ?? 0) ?>;
    window.maxPriceRange = <?= (int)($max_price_range ?? 0) ?>;
    window.selectedPrice = '<?= $selectedPrice ?>';
</script>
<!-- Sidebar JS & CSS di chuyển sang shop.js & shop.css -->

