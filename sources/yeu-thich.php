<?php
    // $row được tạo giả để hiển thị SEO và Title
    $row = array(
        'ten' => 'Sản phẩm yêu thích',
        'title' => 'Sản phẩm yêu thích',
        'keyword' => 'Sản phẩm yêu thích',
        'description' => 'Danh sách các sản phẩm bạn đã lưu lại.'
    );
?>

<div class="block page" style="min-height: 50vh;">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="main-title"><?= $row['ten'] ?></h2>
            </div>
        </div>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4" id="wishlist-container">
            <div class="col-12 text-center py-5 w-100" id="wishlist-loading">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
            </div>
        </div>
        
        <div class="row" id="wishlist-empty" style="display:none;">
            <div class="col-12 text-center py-5">
                <div class="mb-3 text-muted">
                    <i class="fa fa-heart-crack fa-4x mb-3"></i>
                    <h4>Bạn chưa lưu sản phẩm nào</h4>
                    <p>Hãy thêm các sản phẩm yêu thích để dễ dàng mua sắm sau này.</p>
                </div>
                <a href="<?= URLPATH ?>san-pham.html" class="btn-custom btn-solid btn-x">Tiếp tục mua sắm</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function loadWishlistPage() {
        var list = [];
        try { 
            list = JSON.parse(localStorage.getItem('wishlist') || '[]'); 
        } catch(e) { 
            list = []; 
        }

        if (list.length === 0) {
            $('#wishlist-loading').hide();
            $('#wishlist-empty').show();
            return;
        }

        $.ajax({
            url: AJAX_URL, 
            type: 'POST', 
            dataType: 'json',
            data: { do: 'get_wishlist_products', ids: list },
            success: function(res) {
                $('#wishlist-loading').hide();
                if (!res || !res.products || res.products.length === 0) {
                    $('#wishlist-empty').show();
                    return;
                }

                var baseUrl = res.urlpath || URLPATH || '';
                var html = '';

                // Bọc HTML card-product chuẩn
                $.each(res.products, function(i, p) {
                    var slug = p.slug ? p.slug + '/' : '';
                    var url = baseUrl + slug + p.alias + '.html';
                    var img = p.hinh_anh ? baseUrl + 'media/images/' + p.hinh_anh : baseUrl + 'media/no-image.png';
                    
                    var minP = parseFloat(p.min_price || 0);
                    var maxP = parseFloat(p.max_price || 0);
                    var priceStr = '', oldPriceStr = '';
                    
                    if (minP > 0) {
                        if (minP !== maxP) {
                            priceStr = formatVND(minP) + ' – ' + formatVND(maxP);
                        } else {
                            priceStr = formatVND(minP);
                            if (parseFloat(p.gia || 0) > minP) oldPriceStr = formatVND(p.gia);
                        }
                    } else if (parseFloat(p.khuyen_mai || 0) > 0) {
                        priceStr = formatVND(p.khuyen_mai);
                        oldPriceStr = formatVND(p.gia || 0);
                    } else {
                        priceStr = formatVND(p.gia || 0);
                    }

                    // Tái tạo UI box-product cho giống card-product.php
                    var badgeHtml = '';
                    if (parseFloat(p.khuyen_mai||0) > 0 && parseFloat(p.khuyen_mai) < parseFloat(p.gia)) {
                        var percent = Math.round(100 - (p.khuyen_mai / p.gia * 100));
                        badgeHtml += '<span class="badge-product badge-sale">-' + percent + '%</span>';
                    }
                    if (p.noi_bat == 1) badgeHtml += '<span class="badge-product badge-hot">Hot</span>';

                    html += '<div class="col" id="wl-item-' + p.id_code + '"><div class="box-product box-hover-zoom">'
                        + '<div class="box-thumbnail"><div class="inner-thumbnail ratio ratio-1x1">'
                        + '<a href="' + url + '"><img src="' + img + '" alt="' + p.ten + '" class="image-cover"></a></div>'
                        + '<div class="box-badges">' + badgeHtml + '</div>'
                        + '<div class="box-product-actions">'
                        + '<button type="button" class="btn-product-action btn-wishlist active" data-id="' + p.id_code + '" title="Bỏ yêu thích" onclick="removeWishlistItem(this, ' + p.id_code + ')"><i class="fa fa-heart"></i></button>'
                        + '<button type="button" class="btn-product-action btn-quickview" data-id="' + p.id_code + '" title="Xem nhanh" onclick="openQuickView(' + p.id_code + ')"><i class="fa fa-eye"></i></button>'
                        + '</div></div>'
                        + '<div class="box-content">'
                        // + (p.category_name ? '<div class="box-category-name text-muted mb-1 fs-12 text-uppercase fw-500 ls-1">' + p.category_name + '</div>' : '')
                        + '<div class="box-title"><h3 class="title">' + p.ten + '</h3></div>'
                        + '<div class="box-price">'
                        + '<div class="label-sale-price">' + priceStr + '</div>'
                        + (oldPriceStr ? '<div class="label-regular-price">' + oldPriceStr + '</div>' : '')
                        + '</div>' // end box-price
                        // + '<div class="box-add-cart mt-2">'
                        // + ((minP > 0 && (minP !== maxP || p.gia == 0)) ? '<button type="button" onclick="openQuickView(' + p.id_code + ')" class="btn btn-outline-primary btn-sm w-100" style="border-radius:20px;">Tùy chọn</button>' : '<button type="button" onclick="quickAddToCart(' + p.id_code + ');" class="btn btn-primary btn-sm w-100" style="border-radius:20px; background-color: var(--cl-x, #f59e0b); border-color: var(--cl-x, #f59e0b);"><i class="fa fa-shopping-cart me-1"></i> Thêm giỏ hàng</button>')
                        // + '</div>'
                        + '</div></div></div>'; // end col
                });

                $('#wishlist-container').html(html);
            },
            error: function() {
                $('#wishlist-loading').html('<p class="text-danger">Lỗi kết nối. Vui lòng thử lại.</p>');
            }
        });
    }

    // Call load when page is ready (and when routing matches this page)
    if (window.location.href.indexOf('yeu-thich') !== -1) {
        loadWishlistPage();
    }
    
    // Custom remove wrapper to hide element in this specific page
    window.removeWishlistItem = function(btn, id) {
        // Run default toggle to remove from localStorage and sync other buttons
        toggleWishlist(btn);
        
        // Remove visually from list
        $('#wl-item-' + id).fadeOut(300, function() {
            $(this).remove();
            if ($('#wishlist-container .col').length === 0) {
                $('#wishlist-empty').fadeIn();
            }
        });
    };
});
</script>
