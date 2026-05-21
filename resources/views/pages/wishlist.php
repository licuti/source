<?php
    // $row được tạo giả để hiển thị SEO và Title
    // Cần dùng object (stdClass) để đồng bộ với kiến trúc mới trong index.php và seo.php
    $row = (object) [
        'ten' => 'Sản phẩm yêu thích',
        'title' => 'Sản phẩm yêu thích',
        'keyword' => 'Sản phẩm yêu thích',
        'des' => 'Danh sách các sản phẩm bạn đã lưu lại.',
        'seo_head' => '',
        'seo_body' => ''
    ];
?>

<div class="block page" style="min-height: 50vh;">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="main-title text-center fw-bold py-4"><?= $row->ten ?></h2>
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
                <div class="mb-4 text-muted">
                    <i class="fa-light fa-heart-crack fa-4x mb-3 opacity-25"></i>
                    <h4>Danh sách yêu thích đang trống</h4>
                    <p>Hãy lưu lại những sản phẩm bạn yêu thích để dễ dàng tìm lại sau này.</p>
                </div>
                <a href="<?= URLPATH ?>san-pham.html" class="btn btn-primary px-4 py-2 shadow-sm rounded-pill">Tiếp tục mua sắm</a>
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

                $.each(res.products, function(i, p) {
                    var slug = p.slug ? p.slug + '/' : '';
                    var url = baseUrl + slug + p.alias + '.html';
                    var img = p.hinh_anh ? baseUrl + 'img_data/images/' + p.hinh_anh : baseUrl + 'img_data/no-image.png';
                    
                    var priceStr = '', oldPriceStr = '';
                    var minP = parseFloat(p.min_price || 0);
                    var maxP = parseFloat(p.max_price || 0);
                    
                    if (minP > 0) {
                        priceStr = (minP !== maxP) ? formatVND(minP) + ' – ' + formatVND(maxP) : formatVND(minP);
                        if (parseFloat(p.gia || 0) > minP) oldPriceStr = formatVND(p.gia);
                    } else if (parseFloat(p.khuyen_mai || 0) > 0) {
                        priceStr = formatVND(p.khuyen_mai);
                        oldPriceStr = formatVND(p.gia || 0);
                    } else {
                        priceStr = formatVND(p.gia || 0);
                    }

                    var badgeHtml = '';
                    if (parseFloat(p.khuyen_mai||0) > 0 && parseFloat(p.khuyen_mai) < parseFloat(p.gia)) {
                        var percent = Math.round(100 - (p.khuyen_mai / p.gia * 100));
                        badgeHtml += '<span class="badge-product bg-danger">-' + percent + '%</span>';
                    }
                    if (p.noi_bat == 1) badgeHtml += '<span class="badge-product bg-warning text-dark">Hot</span>';

                    html += '<div class="col" id="wl-item-' + p.id_code + '">'
                        + '<div class="card h-100 border-0 shadow-sm transition-up">'
                        + '<div class="position-relative overflow-hidden">'
                        + '<a href="' + url + '"><img src="' + img + '" alt="' + p.ten + '" class="card-img-top object-fit-cover" style="aspect-ratio:1/1;"></a>'
                        + '<div class="position-absolute top-0 start-0 p-2">' + badgeHtml + '</div>'
                        + '<div class="box-product-actions position-absolute bottom-0 start-0 w-100 p-2 d-flex justify-content-center gap-2 opacity-0 transition-300">'
                        + '<button type="button" class="btn btn-light btn-sm rounded-circle shadow-sm" onclick="removeWishlistItem(this, ' + p.id_code + ')" title="Bỏ yêu thích"><i class="fa-solid fa-heart text-danger"></i></button>'
                        + '<button type="button" class="btn btn-light btn-sm rounded-circle shadow-sm" onclick="openQuickView(' + p.id_code + ')" title="Xem nhanh"><i class="fa-solid fa-eye"></i></button>'
                        + '</div></div>'
                        + '<div class="card-body p-3">'
                        + '<h3 class="h6 card-title mb-2 text-truncate-2">' + p.ten + '</h3>'
                        + '<div class="d-flex flex-wrap gap-2 align-items-center h6 mb-3">'
                        + '<span class="text-danger fw-bold">' + priceStr + '</span>'
                        + (oldPriceStr ? '<small class="text-decoration-line-through opacity-50">' + oldPriceStr + '</small>' : '')
                        + '</div>'
                        + '<div class="mt-auto">'
                        + ((minP > 0 && (minP !== maxP || p.gia == 0)) 
                            ? '<button type="button" onclick="openQuickView(' + p.id_code + ')" class="btn btn-outline-primary btn-sm w-100 rounded-pill">Tùy chọn</button>' 
                            : '<button type="button" onclick="quickAddToCart(' + p.id_code + ');" class="btn btn-primary btn-sm w-100 rounded-pill shadow-sm"><i class="fa-solid fa-cart-shopping me-1"></i> Mua ngay</button>')
                        + '</div></div></div></div>';
                });

                $('#wishlist-container').html(html);
            },
            error: function() {
                $('#wishlist-loading').html('<p class="text-danger">Lỗi kết nối. Vui lòng thử lại.</p>');
            }
        });
    }

    if (window.location.href.indexOf('yeu-thich') !== -1) {
        loadWishlistPage();
    }
    
    window.removeWishlistItem = function(btn, id) {
        if (typeof toggleWishlist === 'function') toggleWishlist(btn);
        
        $('#wl-item-' + id).fadeOut(300, function() {
            $(this).remove();
            if ($('#wishlist-container .col').length === 0) {
                $('#wishlist-empty').fadeIn();
            }
        });
    };
});
</script>
