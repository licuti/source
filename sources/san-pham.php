<?php
    $d->query("update #_sanpham set view = view + 1 where alias='".$com."'");

    // 1. Cấu hình phân trang
    $default_limit = (int)get_json('product', 'paging') ?: 16;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $default_limit;
    if (!in_array($limit, [9, 12, 18, 24])) $limit = $default_limit;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    // 2. Xử lý danh mục (Category)
    if (isset($_GET['cats']) && is_array($_GET['cats'])) {
        $cat_ids = array_map('intval', $_GET['cats']);
        $cat_ids = array_filter($cat_ids);
        if (!empty($cat_ids)) {
            $selected_cat_list = [];
            foreach($cat_ids as $cid) {
                $selected_cat_list[] = $cid . $d->getIdsub($cid);
            }
            $selected_cat_str = implode(',', $selected_cat_list);
            $id_loai_list = $selected_cat_str; // For the price stats subquery below
            $where = " sp.id_loai in ($selected_cat_str) and sp.hien_thi = 1 " . _where_lang;
        } else {
            $id_loai_list = $row['id_code'].$d->getIdsub($row['id_code']);
            $where = " sp.id_loai in ($id_loai_list) and sp.hien_thi = 1 " . _where_lang;
        }
    } else {
        $id_loai_list = $row['id_code'].$d->getIdsub($row['id_code']);
        $where = " sp.id_loai in ($id_loai_list) and sp.hien_thi = 1 " . _where_lang;
    }

    // Calculate global min/max price for this category (without price filter)
    $price_stats = $d->simple_fetch("SELECT 
        MIN(LEAST(IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia), COALESCE(v.min_v, 999999999999))) as abs_min,
        MAX(GREATEST(IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia), COALESCE(v.max_v, 0))) as abs_max
        FROM #_sanpham sp
        LEFT JOIN (
            SELECT id_sanpham, 
                   MIN(IF(khuyen_mai > 0, khuyen_mai, gia)) as min_v,
                   MAX(IF(khuyen_mai > 0, khuyen_mai, gia)) as max_v
            FROM #_sanpham_bienthe
            GROUP BY id_sanpham
        ) v ON v.id_sanpham = sp.id_code
        WHERE sp.id_loai in ($id_loai_list) and sp.hien_thi = 1 " . _where_lang);
    $min_price_range = (float)($price_stats['abs_min'] ?? 0);
    $max_price_range = (float)($price_stats['abs_max'] ?? 50000000); 
    if ($max_price_range <= $min_price_range) $max_price_range = $min_price_range + 1000000;

    // 3. Xử lý lọc theo Giá (Price Range)
    $price_range = isset($_GET['price']) ? $_GET['price'] : '';
    if ($price_range != '') {
        $ranges = explode('-', $price_range);
        $min = (float)($ranges[0] ?? 0);
        $max = (float)($ranges[1] ?? 0);
        
        if ($max > 0) {
            $where .= " AND (
                EXISTS (SELECT 1 FROM #_sanpham_bienthe v WHERE v.id_sanpham = sp.id_code AND IF(v.khuyen_mai > 0, v.khuyen_mai, v.gia) BETWEEN $min AND $max)
                OR (NOT EXISTS (SELECT 1 FROM #_sanpham_bienthe v WHERE v.id_sanpham = sp.id_code) AND IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia) BETWEEN $min AND $max)
            )";
        } else {
            $where .= " AND (
                EXISTS (SELECT 1 FROM #_sanpham_bienthe v WHERE v.id_sanpham = sp.id_code AND IF(v.khuyen_mai > 0, v.khuyen_mai, v.gia) >= $min)
                OR (NOT EXISTS (SELECT 1 FROM #_sanpham_bienthe v WHERE v.id_sanpham = sp.id_code) AND IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia) >= $min)
            )";
        }
    }

    // 4. Xử lý lọc theo Thuộc tính (Attributes: Màu sắc, Kích thước...)
    if (isset($_GET['attrs']) && is_array($_GET['attrs'])) {
        $attr_ids = array_map('intval', $_GET['attrs']);
        $attr_ids_str = implode(',', $attr_ids);
        if ($attr_ids_str != '') {
            $where .= " AND EXISTS (SELECT 1 FROM #_sanpham_bienthe_thuoctinh btt JOIN #_sanpham_bienthe bt ON bt.id = btt.id_bienthe WHERE bt.id_sanpham = sp.id_code AND btt.id_thuoctinh_giatri IN ($attr_ids_str))";
        }
    }

    // 5. Lọc chỉ còn hàng (In-stock)
    if (isset($_GET['in_stock']) && (int)$_GET['in_stock'] === 1) {
        $where .= " AND EXISTS (SELECT 1 FROM #_sanpham_bienthe v WHERE v.id_sanpham = sp.id_code AND v.so_luong > 0)";
    }

    // 6. Lọc theo điểm đánh giá tối thiểu (Min Rating)
    if (isset($_GET['min_rating']) && (int)$_GET['min_rating'] > 0) {
        $min_r = (int)$_GET['min_rating'];
        $where .= " AND (SELECT COALESCE(AVG(bl.danh_gia),0) FROM #_binhluan bl WHERE bl.id_sanpham = sp.id_code AND bl.trang_thai=1 AND bl.parent=0 AND bl.danh_gia > 0) >= $min_r";
    }

    // 5. Xử lý Sắp xếp (Sort)
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
    $order_by = "sp.so_thu_tu ASC, sp.id DESC";
    switch ($sort) {
        case 'price_asc':
            // Sắp xếp theo giá nhỏ nhất của sản phẩm (min_price)
            $order_by = "min_price ASC, sp.id DESC";
            break;
        case 'price_desc':
            $order_by = "min_price DESC, sp.id DESC";
            break;
        case 'newest':
            $order_by = "sp.id DESC";
            break;
    }

    // 6. Tính tổng số bản ghi và phân trang
    $total_sql = "select count(sp.id) as total from #_sanpham sp where $where";
    $total_res = $d->simple_fetch($total_sql);
    $total_records = (int)($total_res['total'] ?? 0);
    
    $offset = ($page - 1) * $limit;

    // 7. Lấy danh sách sản phẩm trang hiện tại
    $sanpham = $d->o_fet("select sp.*, 
        (select ten from #_category where id_code = sp.id_loai and lang = '".$_SESSION['lang']."') as category_name,
        COALESCE((select min(IF(v.khuyen_mai > 0, v.khuyen_mai, v.gia)) from #_sanpham_bienthe v where v.id_sanpham = sp.id_code), IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia)) as min_price,
        COALESCE((select max(IF(v.khuyen_mai > 0, v.khuyen_mai, v.gia)) from #_sanpham_bienthe v where v.id_sanpham = sp.id_code), IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia)) as max_price
        from #_sanpham sp 
        where $where 
        ORDER BY $order_by 
        LIMIT $offset, $limit");

    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        ob_clean(); // Xóa bộ đệm output (header đã được in ra) trước đó
        if (count($sanpham) > 0) {
            foreach ($sanpham as $key => $value) {
                echo '<div class="col-6 col-md-4 col-lg-3">';
                include _source."module/card-product.php";
                echo '</div>';
            }
            if ($page * $limit < $total_records) {
                echo '<div class="col-12 text-center load-more-container mt-4">';
                echo '<button class="btn-custom btn-x btn-load-more" data-page="'.($page + 1).'">Xem thêm</button>';
                echo '</div>';
            }
        } else {
            echo '<div class="col-12 py-5 text-center">';
            echo '<i class="fa-light fa-magnifying-glass fa-3x"></i>';
            echo '<p class="fs-6">'.($d->getTxt(142) ?: 'Không tìm thấy sản phẩm nào khớp với tiêu chí của bạn.').'</p>';
            echo '<a href="'.$com.'.html" class="btn-custom btn-x mt-4">Xem tất cả sản phẩm</a>';
            echo '</div>';
        }
        exit;
    }

    $allCategory = $d->o_fet("select * from #_category where id_loai=100 and hien_thi =1 "._where_lang." order by so_thu_tu ASC, id DESC");
    
    // Tạo link phân trang
    $paging_url = getCurrentUrlWithoutPage();
?>

<div class="block">
    <div class="container-fluid">
        <div class="row justify-content-between mb-3">
            <div class="col-auto">
                <button class="btn-custom btn-solid bg-transparent border-0 text-dark p-0 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasFilter" aria-controls="offcanvasFilter">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu-icon lucide-menu"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg>
                    Bộ lọc
                </button>
            </div>
            <div class="col-auto d-flex align-items-center gap-5">
                <div class="d-flex align-items-center gap-2">
                    <span>Show</span>
                    <div class="grid-action">
                        <div class="grid-action-item">9</div>
                        <div class="grid-action-item">12</div>
                        <div class="grid-action-item">18</div>
                        <div class="grid-action-item">24</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <form action="" method="get" id="sort-form">
                        <select name="sort" class="form-select">
                            <option value="default" <?= ($sort == 'default') ? 'selected' : '' ?>>Thứ tự mặc định</option>
                            <option value="newest" <?= ($sort == 'newest') ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="price_asc" <?= ($sort == 'price_asc') ? 'selected' : '' ?>>Giá tăng dần</option>
                            <option value="price_desc" <?= ($sort == 'price_desc') ? 'selected' : '' ?>>Giá giảm dần</option>
                        </select>
                        <!-- Giữ lại các tham số lọc hiện tại khi sắp xếp -->
                        <?php if($price_range): ?>
                            <input type="hidden" name="price" value="<?= htmlspecialchars($price_range) ?>">
                        <?php endif; ?>
                        <?php if(isset($_GET['attrs'])): foreach($_GET['attrs'] as $at): ?>
                            <input type="hidden" name="attrs[]" value="<?= htmlspecialchars($at) ?>">
                        <?php endforeach; endif; ?>
                        <?php if(isset($_GET['cats'])): foreach($_GET['cats'] as $cat_id): ?>
                            <input type="hidden" name="cats[]" value="<?= htmlspecialchars($cat_id) ?>">
                        <?php endforeach; endif; ?>
                        <?php if(isset($_GET['in_stock']) && (int)$_GET['in_stock'] === 1): ?>
                            <input type="hidden" name="in_stock" value="1">
                        <?php endif; ?>
                        <?php if(isset($_GET['min_rating']) && (int)$_GET['min_rating'] > 0): ?>
                            <input type="hidden" name="min_rating" value="<?= (int)$_GET['min_rating'] ?>">
                        <?php endif; ?>
                        <?php if(isset($_GET['key']) && $_GET['key'] != ''): ?>
                            <input type="hidden" name="key" value="<?= htmlspecialchars($_GET['key']) ?>">
                        <?php endif; ?>
                        <?php if(isset($_GET['keyword']) && $_GET['keyword'] != ''): ?>
                            <input type="hidden" name="keyword" value="<?= htmlspecialchars($_GET['keyword']) ?>">
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="row g-3 g-lg-4" id="product-grid">
            <?php if (count($sanpham) > 0): ?>
                <?php foreach ($sanpham as $key => $value): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <?php include _source."module/card-product.php"; ?>
                    </div>
                <?php endforeach ?>
                <?php if ($page * $limit < $total_records): ?>
                    <div class="col-12 text-center load-more-container mt-4">
                        <button class="btn-custom btn-x btn-load-more" data-page="<?= $page + 1 ?>">Xem thêm</button>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="col-12 py-5 text-center">
                    <i class="fa-light fa-magnifying-glass fa-3x"></i>
                    <p class="fs-6"><?= $d->getTxt(142) ?: 'Không tìm thấy sản phẩm nào khớp với tiêu chí của bạn.' ?></p>
                    <a href="<?= $com ?>.html" class="btn-custom btn-x mt-4">Xem tất cả sản phẩm</a>
                </div>
            <?php endif ?>
        </div>
    </div>

</div>

<!-- Offcanvas Bộ lọc -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasFilter" aria-labelledby="offcanvasFilterLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold fs-6 d-flex align-items-center gap-2" id="offcanvasFilterLabel">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-funnel-icon lucide-funnel"><path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z"/></svg>
            Bộ lọc sản phẩm
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4">
        <?php include _source."module/shop-sidebar.php"; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        var currentLimit = <?= $limit ?>;
        var isFetching = false;

        // Mark active limit item on load
        $('.grid-action-item').each(function(){
            if(parseInt($(this).text().trim()) === currentLimit) {
                $('.grid-action-item').removeClass('active');
                $(this).addClass('active');
            }
        });

        function fetchProducts(page, isLoadMore) {
            if (isFetching) return;
            isFetching = true;

            var sortData = $('#sort-form').serializeArray();
            var filterData = $('.filter-form').serializeArray();
            
            var data = {};
            $.each(sortData, function(i, field){
                data[field.name] = field.value;
            });
            $.each(filterData, function(i, field){
                if (data[field.name]) {
                    if ($.isArray(data[field.name])) {
                        data[field.name].push(field.value);
                    } else {
                        data[field.name] = [data[field.name], field.value];
                    }
                } else {
                    data[field.name] = field.value;
                }
            });

            data.limit = currentLimit;
            data.page = page;
            data.ajax = 1;

            var url = window.location.pathname;

            if (!isLoadMore) {
                $('#product-grid').html('<div class="col-12 text-center py-5"><i class="fa fa-spinner fa-spin fa-3x"></i><div class="mt-2">Đang tải...</div></div>');
            } else {
                $('.btn-load-more').html('<i class="fa fa-spinner fa-spin"></i> Đang tải...').prop('disabled', true);
            }

            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                success: function(res) {
                    if (isLoadMore) {
                        $('.load-more-container').remove();
                        $('#product-grid').append(res);
                    } else {
                        $('#product-grid').html(res);
                    }
                    
                    var dataForUrl = $.extend({}, data);
                    delete dataForUrl.ajax;
                    var queryString = $.param(dataForUrl);
                    window.history.pushState(null, '', url + '?' + queryString);
                    
                    isFetching = false;
                },
                error: function() {
                    isFetching = false;
                    if (!isLoadMore) {
                        $('#product-grid').html('<div class="col-12 text-center py-5">Lỗi kết nối. Vui lòng thử lại.</div>');
                    } else {
                        $('.btn-load-more').html('Xem thêm').prop('disabled', false);
                    }
                }
            });
        }

        $(document).on('change', '.filter-form input, .filter-form select, #sort-form select', function(e) {
            // Cập nhật class active cho các thuộc tính dạng màu sắc (color swatch)
            if ($(this).closest('.color-swatch-item').length > 0) {
                var $label = $(this).siblings('.color-swatch');
                if($(this).is(':checked')) {
                    $label.addClass('active');
                } else {
                    $label.removeClass('active');
                }
            }
            
            fetchProducts(1, false);
        });

        $(document).on('click', '.btn-apply-price', function() {
            fetchProducts(1, false);
        });

        $(document).on('click', '.grid-action-item', function() {
            $('.grid-action-item').removeClass('active');
            $(this).addClass('active');
            currentLimit = parseInt($(this).text().trim());
            fetchProducts(1, false);
        });

        $(document).on('click', '.btn-load-more', function() {
            var nextPage = $(this).data('page');
            fetchProducts(nextPage, true);
        });
    });
</script>

<?php ?>