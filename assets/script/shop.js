/**
 * Shop Related Functions
 * Includes: Variant Selection, Cart Management, Checkout Helpers
 */

$(document).ready(function() {
    // 1. Variant Selection Logic (Product Detail)
    if ($('.product-variants').length > 0) {
        // Save initial price HTML to restore later if needed
        window.initialPriceHTML = $('.single-price').html();
        
        // Auto-select variants from URL parameters if available
        if (typeof window.urlParams !== 'undefined' && Object.keys(window.urlParams).length > 0) {
            for (var key in window.urlParams) {
                var val = window.urlParams[key];
                var $option = $('.variant-item[data-val-id="' + val + '"]');
                if ($option.length > 0) {
                    $option.addClass('active');
                    $option.trigger('click'); 
                }
                var $select = $('.variant-group select.variant-select option[value="' + val + '"]').parent();
                if ($select.length > 0) {
                    $select.val(val).trigger('change');
                }
            }
        }

        // Event listener for variant items (div based)
        $(document).on('click', '.variant-item', function() {
            if ($(this).hasClass('un-available')) return;
            
            var $group = $(this).closest('.variant-group');
            $group.find('.variant-item').removeClass('active');
            $(this).addClass('active');
            
            // Update selected text label
            var selectedText = $(this).attr('title') || $(this).text().trim();
            $group.find('.selected-variant-text').text(selectedText);
            
            checkSelectedVariant();
        });

        // Event listener for variant selects
        $(document).on('change', '.variant-select', function() {
            var $group = $(this).closest('.variant-group');
            var selectedText = $(this).find('option:selected').text();
            if (!$(this).val()) selectedText = '';
            $group.find('.selected-variant-text').text(selectedText);
            
            checkSelectedVariant();
        });

        // Initial check if proactive marking is enabled
        if (window.enableProactiveVariants) {
            updateAvailableVariants();
        }
    }

    // 2. Price Range Slider (Sidebar)
    if ($('.price-range-slider').length > 0) {
        if (typeof window.minPriceRange !== 'undefined' && typeof window.maxPriceRange !== 'undefined') {
            initPriceSliders(window.minPriceRange, window.maxPriceRange, window.selectedPrice);
        }
        
        var offcanvasElement = document.getElementById('offcanvasFilter');
        if (offcanvasElement) {
            offcanvasElement.addEventListener('shown.bs.offcanvas', function () {
                if (typeof initPriceSliders === 'function') {
                    initPriceSliders(window.minPriceRange, window.maxPriceRange, window.selectedPrice);
                }
            });
        }
    }

    // 3. Checkout Province/District Selectors
    if ($('#code_tinh').length > 0) {
        // Any specific initialization for checkout
    }
    // 4. Quantity Increment/Decrement
    $(document).on('click', '.btn-add', function() {
        var $input = $(this).siblings('#soluong');
        var val = parseInt($input.val()) || 1;
        $input.val(val + 1);
    });

    $(document).on('click', '.btn-sub', function() {
        var $input = $(this).siblings('#soluong');
        var val = parseInt($input.val()) || 1;
        if (val > 1) $input.val(val - 1);
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.box-search-header').length) {
            $('#live-search-dropdown').hide();
        }
    });

    // 5. Sticky Cart Bar Logic
    if ($('.btn-add-cart').length > 0 && $('#sticky-cart-bar').length > 0) {
        var stickyThreshold = $('.btn-add-cart').offset().top + 50;
        $(window).scroll(function() {
            if ($(window).scrollTop() > stickyThreshold) {
                $('#sticky-cart-bar').addClass('active');
            } else {
                $('#sticky-cart-bar').removeClass('active');
            }
        });
    }
});

/**
 * PRODUCT DETAIL FUNCTIONS
 */

function checkSelectedVariant() {
    var selected = {};
    var allSelected = true;

    $('.variant-group').each(function() {
        var attrId = $(this).data('attr-id');
        var attrType = $(this).data('attr-type');
        var val = null;

        if (attrType === 'select') {
            val = $(this).find('.variant-select').val();
        } else {
            var $active = $(this).find('.variant-item.active');
            if ($active.length > 0) {
                val = $active.data('val-id');
            }
        }

        if (val) {
            selected[attrId] = val;
        } else {
            allSelected = false;
        }
    });

    if (allSelected) {
        var foundVariant = null;
        if (typeof window.productVariants !== 'undefined') {
            for (var i = 0; i < window.productVariants.length; i++) {
                var variant = window.productVariants[i];
                var matches = true;
                for (var attrId in selected) {
                    var attrMatch = false;
                    var thuoctinhs = variant.thuoctinh || [];
                    for (var j = 0; j < thuoctinhs.length; j++) {
                        if (thuoctinhs[j].id_thuoctinh == attrId && thuoctinhs[j].id_thuoctinh_giatri == selected[attrId]) {
                            attrMatch = true;
                            break;
                        }
                    }
                    if (!attrMatch) {
                        matches = false;
                        break;
                    }
                }
                if (matches) {
                    foundVariant = variant;
                    break;
                }
            }
        }

        if (foundVariant) {
            window.currentVariant = foundVariant;
            var gia = parseFloat(foundVariant.gia) || 0;
            var khuyen_mai = parseFloat(foundVariant.khuyen_mai) || 0;
            var priceDisplay = khuyen_mai > 0 ? formatVND(khuyen_mai) : formatVND(gia);
            
            $('#display_price_discount').text(priceDisplay);
            $('#form_gia').val(khuyen_mai > 0 ? khuyen_mai : gia);

            if (khuyen_mai > 0 && khuyen_mai < gia) {
                $('#display_price_regular').text(formatVND(gia)).show();
                var discount = 100 - ((khuyen_mai / gia) * 100);
                $('#display_percent_discount').text(discount.toFixed(0) + "%").show();
            } else {
                $('#display_price_regular').hide();
                $('#display_percent_discount').hide();
            }

            // --- Update Sticky Cart ---
            $('#sticky-cart-price').text(priceDisplay);
            if (foundVariant.hinh_anh) {
                var baseUrl = typeof URLPATH !== 'undefined' ? URLPATH : '';
                $('#sticky-cart-img').attr('src', baseUrl + 'img_data/images/' + foundVariant.hinh_anh);
            }
            $('.btn-add-cart-sticky').prop('disabled', false);

            $('#id_bienthe').val(foundVariant.id);
            $('#display_stock').text(foundVariant.so_luong);
            $('#display_sku').text(foundVariant.ma_sp || 'Đang cập nhật');
            $('#variant-out-of-stock-msg').hide();
            $('.single-group-button').removeClass('out-of-stock');

            // --- Update slide_show slide ---
            if (foundVariant.hinh_anh && typeof slide_show !== 'undefined') {
                var variantImage = foundVariant.hinh_anh;
                $('.slide_show .swiper-slide:not(.swiper-slide-duplicate)').each(function(index) {
                    var $img = $(this).find('img.image-cover');
                    if ($img.length && $img.attr('src') && $img.attr('src').indexOf(variantImage) !== -1) {
                        if (typeof slide_show.slideToLoop === 'function' && slide_show.params.loop) {
                            slide_show.slideToLoop(index);
                        } else {
                            slide_show.slideTo(index);
                        }
                        return false; // break loop
                    }
                });
            }
        } else {
            $('#variant-out-of-stock-msg').show();
            window.currentVariant = null;
            $('#id_bienthe').val('');
            $('#display_stock').text(0);
            $('.btn-add-cart-sticky').prop('disabled', true);
            if (window.hideButtonOnOutOfStock) {
                $('.single-group-button').addClass('out-of-stock');
            } else {
                $('.single-group-button').removeClass('out-of-stock');
            }
        }
    } else {
        // Reset to default display when no selection or incomplete selection
        window.currentVariant = null;
        $('#id_bienthe').val('');
        $('.single-group-button').removeClass('out-of-stock');
        $('#variant-out-of-stock-msg').hide();
        $('.btn-add-cart-sticky').prop('disabled', false);
        
        if (window.initialPriceHTML) {
            $('.single-price').html(window.initialPriceHTML);
            // Restore sticky price from initial state (assuming initialPriceHTML has text that can be used or we just fallback to the first element's text)
            $('#sticky-cart-price').text($('#display_price_discount').text() || $('.single-price').text().trim());
        }
        if (typeof window.currentProductImg !== 'undefined') {
            var baseUrl = typeof URLPATH !== 'undefined' ? URLPATH : '';
            $('#sticky-cart-img').attr('src', baseUrl + 'img_data/images/' + window.currentProductImg);
        }

        
        if (typeof window.productVariants !== 'undefined' && window.productVariants.length > 0) {
            $('#display_stock').text(window.totalVariantQty);
            $('#display_sku').text(window.mainProductCode || 'Đang cập nhật');
        } else {
            $('#display_stock').text(window.mainProductQty);
            $('#display_sku').text(window.mainProductCode || 'Đang cập nhật');
        }
    }

    if (window.enableProactiveVariants) {
        updateAvailableVariants();
    }
}

function updateAvailableVariants() {
    if (typeof window.productVariants === 'undefined' || !window.enableProactiveVariants) return;
    
    $('.variant-group').each(function() {
        var $currentGroup = $(this);
        var currentAttrId = $currentGroup.data('attr-id');
        var otherSelectedAttrs = {};
        
        $('.variant-group').not($currentGroup).each(function() {
            var $otherGroup = $(this);
            var otherAttrId = $otherGroup.data('attr-id');
            var otherAttrType = $otherGroup.data('attr-type');
            
            if (otherAttrType === 'select') {
                var val = $otherGroup.find('.variant-select').val();
                if (val) otherSelectedAttrs[otherAttrId] = val;
            } else {
                var $active = $otherGroup.find('.variant-item.active');
                if ($active.length > 0) otherSelectedAttrs[otherAttrId] = $active.data('val-id');
            }
        });

        $currentGroup.find('.variant-item, .variant-select option').each(function() {
            var $option = $(this);
            var isSelectOption = this.tagName === 'OPTION';
            if (isSelectOption && !$option.val()) return;

            var optionValId = isSelectOption ? $option.val() : $option.data('val-id');
            var isAvailable = false;
            
            for (var i = 0; i < window.productVariants.length; i++) {
                var variant = window.productVariants[i];
                var variantAttrMap = {};
                if(variant.thuoctinh) {
                    for(var j=0; j < variant.thuoctinh.length; j++) {
                        variantAttrMap[variant.thuoctinh[j].id_thuoctinh] = variant.thuoctinh[j].id_thuoctinh_giatri;
                    }
                }
                if (variantAttrMap[currentAttrId] != optionValId) continue;
                var matchesOther = true;
                for (var otherId in otherSelectedAttrs) {
                    if (variantAttrMap[otherId] != otherSelectedAttrs[otherId]) {
                        matchesOther = false;
                        break;
                    }
                }
                if (matchesOther) {
                    isAvailable = true;
                    break;
                }
            }

            if (isAvailable) {
                if (isSelectOption) $option.prop('disabled', false).css('color', '');
                else $option.removeClass('un-available');
            } else {
                if (isSelectOption) $option.prop('disabled', true).css('color', '#ccc');
                else $option.addClass('un-available');
            }
        });
    });
}

function quickAddToCart(id_sp, id_bienthe = 0) {
    var url_ajax = AJAX_ROUTES.cart + 'legacy';
    $.ajax({
        url: url_ajax,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'add-to-cart',
            id_sp: id_sp,
            id_bienthe: id_bienthe,
            so_luong: 1
        },
        success: function(res) {
            if (res.success) {
                $('.count_buy, .cart-count, .header-cart-count').html(res.count); // Cập nhật số lượng trên icon giỏ hàng
                swal({
                    title: 'Thành công!',
                    text: res.message || 'Đã thêm sản phẩm vào giỏ',
                    icon: 'success',
                    button: 'Tiếp tục mua sắm',
                    timer: 2000
                });
            } else {
                swal({
                    title: 'Lỗi',
                    text: res.message || 'Không thể thêm sản phẩm',
                    icon: 'error',
                    button: 'Đóng'
                });
            }
        },
        error: function() {
            swal({
                title: 'Lỗi kết nối',
                text: 'Vui lòng thử lại sau.',
                icon: 'error',
                button: 'Đóng'
            });
        }
    });
}

function add_to_cart(type){
    if ($('.variant-group').length > 0) {
        var valid = true;
        $('.variant-group').each(function() {
            var attrType = $(this).data('attr-type');
            if (attrType === 'select') {
                if (!$(this).find('.variant-select').val()) valid = false;
            } else {
                if ($(this).find('.variant-item.active').length === 0) valid = false;
            }
        });

        if (!valid) {
            swal({ title: 'Thông báo', text: 'Vui lòng chọn đầy đủ các tùy chọn sản phẩm', icon: 'warning', button: 'Đóng' });
            return;
        }
        if(!window.currentVariant) {
            swal({ title: 'Thông báo', text: 'Biến thể này hiện không khả dụng.', icon: 'error', button: 'Đóng' });
            return;
        }
    }

    var url_ajax = AJAX_ROUTES.cart + 'legacy';
    $.ajax({
        method: $('#form-cart').attr('method'),
        url: url_ajax,
        data: $('#form-cart').serialize(),
    }).done(function(response) {
        var res = typeof response === 'object' ? response : JSON.parse(response);
        if(type === 0){
            swal({ title: 'Success', text: res.message || 'Thêm vào giỏ hàng thành công', icon: 'success', button: false, timer: 2000 })
            .then(() => { if (res.success) $(".header-cart-count").html(res.count); });
        } else if(type === 1){
            swal({ title: 'Đã thêm vào giỏ hàng', text: 'Thêm thành công! Thanh toán ngay!', icon: 'success', button: false, timer: 2000 })
            .then(() => { window.location = (typeof URLPATH !== 'undefined' ? URLPATH : '') + "gio-hang.html"; });
        }
    });
    if(event) event.preventDefault();
}

/**
 * CART FUNCTIONS
 */

function formatVND(amount) {
    if (!amount || amount == 0) return '0 ₫'; 
    
    if (typeof currencyConfig === 'undefined') {
        return parseFloat(amount).toLocaleString('vi-VN').replace(/,/g, '.') + ' ₫';
    }
    
    var converted = amount * currencyConfig.rate;
    var formatted = "";
    
    if (currencyConfig.code === 'USD') {
        formatted = converted.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } else {
        formatted = converted.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).replace(/,/g, '.');
    }

    if (currencyConfig.position === 'prefix') {
        return currencyConfig.symbol + formatted;
    } else {
        return formatted + currencyConfig.symbol;
    }
}

function recalcCart() {
    var subtotal = 0;
    if (typeof cartPrices === 'undefined') return;
    
    $('.num_sl').each(function () {
        var key = $(this).data('key');
        var sl = parseInt($(this).val()) || 1;
        var price = cartPrices[key] || 0;
        var rowTotal = price * sl;
        subtotal += rowTotal;
        $('#total-' + key).text(formatVND(rowTotal));
    });
    $('#summary-subtotal').text(formatVND(subtotal));
    if (typeof so_giam !== 'undefined' && so_giam > 0) {
        $('#summary-discount').text('- ' + formatVND(so_giam));
        $('#summary-discount-row').show();
    } else {
        $('#summary-discount-row').hide();
    }
    var total = subtotal + (typeof phi_ship !== 'undefined' ? phi_ship : 0) - (typeof so_giam !== 'undefined' ? so_giam : 0);
    $('#summary-total').text(formatVND(total));
}

function updateCartAjax(key, sl) {
    var url_ajax = AJAX_ROUTES.cart + 'legacy';
    $.ajax({
        url: url_ajax,
        type: 'POST',
        data: { action: 'update_qty', key_cart: key, so_luong: sl },
        dataType: 'json'
    });
}

function check_sale(ma) {
    if (ma) $('#ma_sale').val(ma);
    var ma_sale = $('#ma_sale').val().trim();
    if (!ma_sale) return;
    var btn = $('[onclick="check_sale()"]');
    var oldText = btn.text();
    btn.prop('disabled', true).text('Đang kiểm tra...');
    
    var url_ajax = AJAX_ROUTES.cart + 'legacy';
    var subtotal = 0;
    $('.num_sl').each(function() {
        var key = $(this).data('key');
        subtotal += (cartPrices[key] || 0) * (parseInt($(this).val()) || 0);
    });

    $.ajax({
        url: url_ajax,
        type: 'POST',
        dataType: 'json',
        data: { action: 'check_sale', ma_sale: ma_sale, tong_dong: subtotal, phi_ship: (typeof phi_ship !== 'undefined' ? phi_ship : 0) },
        success: function(res) {
            btn.prop('disabled', false).text(oldText);
            if (res.success) {
                so_giam = res.price_sale;
                recalcCart();

                var estimateHint = '';
                if (res.is_estimated) {
                    estimateHint = '<div class="small text-muted mt-1" id="ship-estimate-hint" style="font-size:11px;">' +
                                   '<i class="fa-solid fa-circle-info me-1"></i>Mức giảm dựa trên phí ship mặc định. Phí chính xác sẽ cập nhật tại Thanh toán.' +
                                   '</div>';
                }

                if (!$('#applied-coupon').length) {
                    $('#coupon-section').prepend(
                        '<div id="applied-coupon-container">' +
                        '<div class="alert alert-success d-flex align-items-center justify-content-between py-2 mb-0" id="applied-coupon">' +
                        '<span><i class="fa-solid fa-tag me-1"></i> <strong>' + ma_sale + '</strong> (' + res.label.split('(')[1] + '</span>' +
                        '<button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" id="btn-remove-sale"><i class="fa-solid fa-xmark"></i></button>' +
                        '</div>' +
                        estimateHint +
                        '</div>'
                    );
                } else {
                    $('#applied-coupon strong').text(ma_sale);
                    $('#ship-estimate-hint').remove();
                    if (estimateHint) $('#applied-coupon').after(estimateHint);
                }
                swal({ title: '', text: 'Áp dụng mã thành công!', icon: 'success', button: false, timer: 1500 });
            } else {
                swal({ title: '', text: res.message || 'Mã giảm giá không hợp lệ', icon: 'error', button: false, timer: 2500 });
            }
        },
        error: function() {
            btn.prop('disabled', false).text(oldText);
            swal({ title: '', text: 'Có lỗi xảy ra, vui lòng thử lại.', icon: 'error', button: false, timer: 2000 });
        }
    });
}

// Cart Event Listeners
$(document).ready(function() {
    $(document).on('click', '.qty-up', function () {
        var key = $(this).data('key');
        var $input = $('#sl_' + key);
        var max = parseInt($input.data('max')) || 100;
        var val = parseInt($input.val()) || 1;
        if (val < max) $input.val(val + 1).trigger('change');
    });

    $(document).on('click', '.qty-down', function () {
        var key = $(this).data('key');
        var $input = $('#sl_' + key);
        var min = parseInt($input.data('min')) || 1;
        var val = parseInt($input.val()) || 1;
        if (val > min) $input.val(val - 1).trigger('change');
    });

    $(document).on('change', '.num_sl', function () {
        var key = $(this).data('key');
        var min = parseInt($(this).data('min')) || 1;
        var max = parseInt($(this).data('max')) || 100;
        var val = parseInt($(this).val()) || min;
        if (val < min) val = min;
        if (val > max) val = max;
        $(this).val(val);
        recalcCart();
        updateCartAjax(key, val);

        // Sync to offcanvas if it exists
        if ($('.offcanvas-qty-input[data-key="' + key + '"]').length) {
            $('.offcanvas-qty-input[data-key="' + key + '"]').val(val);
            if(typeof updateOffcanvasTotal === 'function') updateOffcanvasTotal();
        }
    });

    $(document).on('click', '.btn-delete-cart', function () {
        var key = $(this).data('key');
        swal({ title: "Xác nhận xóa?", text: "Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?", icon: "warning", buttons: true, dangerMode: true })
        .then((willDelete) => {
            if (willDelete) {
                var url_ajax = AJAX_ROUTES.cart + 'legacy';
                $.ajax({
                    url: url_ajax,
                    type: 'POST',
                    data: { action: 'delete_item', key_cart: key },
                    dataType: 'json',
                    success: function (res) {
                        if (res.success) {
                            $('#cart-row-' + key).remove();
                            if(typeof cartPrices !== 'undefined') delete cartPrices[key];
                            recalcCart();

                            // Sync delete to offcanvas if it exists
                            var $ocRow = $('.offcanvas-cart-item[data-key="' + key + '"]');
                            if ($ocRow.length) {
                                $ocRow.remove();
                                if(typeof updateOffcanvasTotal === 'function') updateOffcanvasTotal();
                                
                                var currentCount = parseInt($('.header-cart-count').first().text()) || 0;
                                $('.header-cart-count').text(Math.max(0, currentCount - 1));
                                
                                if($('.offcanvas-cart-item').length === 0) {
                                    $('.cart-offcanvas-list').html('<div class="text-center py-5 text-muted"><i class="fa-solid fa-cart-circle-xmark fa-3x mb-3"></i><p>Giỏ hàng đang trống.</p></div>');
                                    $('.offcanvas-footer').fadeOut();
                                }
                            }

                            if ($('.num_sl').length === 0) window.location.reload(); 
                            swal("Đã xóa sản phẩm khỏi giỏ hàng!", { icon: "success", timer: 1500, buttons: false });
                        }
                    }
                });
            }
        });
    });

    $(document).on('click', '#btn-remove-sale', function() {
        var url_ajax = AJAX_ROUTES.cart + 'legacy';
        $.post(url_ajax, { action: 'remove_sale' }, function(res) {
            if (res.success) {
                so_giam = 0;
                recalcCart();
                $('#applied-coupon').remove();
                $('#ma_sale').val('');
                swal({ title: '', text: 'Đã xóa mã giảm giá.', icon: 'success', button: false, timer: 1200 });
            }
        }, 'json');
    });
});

/**
 * CHECKOUT FUNCTIONS
 */

function get_huyen(code_tinh, code_huyen) {
    var id_quocgia = $('#' + code_tinh).val();
    var url_ajax = AJAX_ROUTES.product + 'legacy';
    $.ajax({
        url: url_ajax, type: "post", dataType: "text",
        data: { do: 'get_huyen', code_tinh: id_quocgia },
        success: function(result) {
            $('#' + code_huyen).html(result);
            if (typeof $.fn.niceSelect !== 'undefined') $('select').niceSelect('update');
            
            // Tự động tính lại ship khi đổi Tỉnh
            update_shipping_fee();
        }
    });
}
function get_xa(code_huyen, code_xa) {
    var id_huyen = $('#' + code_huyen).val();
    var url_ajax = AJAX_ROUTES.product + 'legacy';
    $.ajax({
        url: url_ajax, type: "post", dataType: "text",
        data: { do: 'get_xa', code_huyen: id_huyen },
        success: function(result) {
            $('#' + code_xa).html(result);
            if (typeof $.fn.niceSelect !== 'undefined') $('select').niceSelect('update');

            // Tự động tính lại ship khi đổi Huyện
            update_shipping_fee();
        }
    });
}

function update_shipping_fee() {
    var code_tinh = $('#code_tinh').val();
    var code_huyen = $('#code_huyen').val();
    var code_xa = $('#code_xa').val();
    var tong_don = (typeof window.orderSubtotal !== 'undefined') ? window.orderSubtotal : 0;

    if (!code_tinh) return;

    var url_ajax = AJAX_ROUTES.cart + 'legacy';
    $.ajax({
        url: url_ajax,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'get_shipping_fee',
            code_tinh: code_tinh,
            code_huyen: code_huyen,
            code_xa: code_xa,
            tong_don: tong_don
        },
        success: function(res) {
            if (res.success) {
                // Cập nhật biến toàn cục
                phi_ship = res.phi_ship;
                
                // Quan trọng: Cập nhật biến so_giam (giảm giá) thực tế mới từ Server 
                // (Server đã tính toán lại so_tien_giam dựa trên phí ship mới)
                if (typeof res.so_tien_giam !== 'undefined') {
                    so_giam = res.so_tien_giam;
                }

                // Gọi hàm tính toán lại toàn bộ UI
                recalcCart();

                // Cập nhật giao diện Checkout (Cụ thể các ID riêng của trang thanh toán)
                if ($('#checkout-shipping-fee').length > 0) {
                    $('#checkout-shipping-fee').html(res.phi_ship_format);
                }
                if ($('#checkout-total-amount').length > 0) {
                    $('#checkout-total-amount').html(res.tong_thanh_toan_format);
                }

                // Cập nhật dòng Giảm giá (nếu có mã giảm ship)
                if ($('#summary-discount').length > 0 && res.so_tien_giam_format) {
                    $('#summary-discount').html('- ' + res.so_tien_giam_format);
                    if (res.so_tien_giam > 0) {
                        $('#summary-discount-row').show();
                    } else {
                        $('#summary-discount-row').hide();
                    }
                }

                // Hiển thị mô tả (ghi chú) nếu có
                if (res.phi_desc) {
                    $('#ship-description').html(res.phi_desc).show();
                } else {
                    $('#ship-description').html('').hide();
                }
                
                // Cũng cập nhật phí ship cho phần Mã giảm giá nếu đang mở
                window.phi_ship = res.phi_ship;
            } else {
                $('#ship-description').html('').hide();
            }
        }
    });
}

$(document).on('click', '.btn-thanhtoan', function() {
    $('.btn-thanhtoan').removeClass('active');
    $(this).addClass('active');
    $('.thanhtoan_content').hide();
    $('.thanhtoan_content_' + $(this).attr('data')).show();
    $('#txt_phuongthucthanhtoan').val($(this).text().trim());
});

/* --- Offcanvas Cart Interactions --- */

$(document).on('click', '.btn-delete-offcanvas', function () {
    var key = $(this).data('key');
    var $row = $(this).closest('.offcanvas-cart-item');
    
    swal({ title: "Xác nhận", text: "Xóa sản phẩm này khỏi giỏ hàng?", icon: "warning", buttons: ["Huỷ", "Xoá"], dangerMode: true })
    .then((willDelete) => {
        if (willDelete) {
            var url_ajax = AJAX_ROUTES.cart + 'legacy';
            $.ajax({
                url: url_ajax,
                type: 'POST',
                data: { action: 'delete_item', key_cart: key },
                dataType: 'json',
                success: function (res) {
                    if(res.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            updateOffcanvasTotal();
                            
                            var currentCount = parseInt($('.header-cart-count').first().text()) || 0;
                            $('.header-cart-count').text(Math.max(0, currentCount - 1));
                            
                            if($('.offcanvas-cart-item').length === 0) {
                                $('.cart-offcanvas-list').html('<div class="text-center py-5 text-muted"><i class="fa-solid fa-cart-circle-xmark fa-3x mb-3"></i><p>Giỏ hàng đang trống.</p></div>');
                                $('.offcanvas-footer').fadeOut();
                            }

                            if ($('#cart-row-' + key).length) {
                                $('#cart-row-' + key).remove();
                                if(typeof cartPrices !== 'undefined') delete cartPrices[key];
                                if(typeof recalcCart === 'function') recalcCart();
                                if ($('.num_sl').length === 0) window.location.reload(); 
                            }
                        });
                    }
                }
            });
        }
    });
});

$(document).on('click', '.offcanvas-qty-btn', function () {
    var key = $(this).data('key');
    var dir = $(this).data('dir');
    var $input = $(this).siblings('.offcanvas-qty-input');
    var val = parseInt($input.val()) || 1;
    
    if (dir === 'up') val++;
    else if (dir === 'down' && val > 1) val--;
    
    if (val !== parseInt($input.val())) {
        $input.val(val);
        var url_ajax = AJAX_ROUTES.cart + 'legacy';
        $.ajax({
            url: url_ajax,
            type: 'POST',
            data: { action: 'update_qty', key_cart: key, so_luong: val },
            dataType: 'json',
            success: function() {
                updateOffcanvasTotal();
                if ($('#sl_' + key).length) {
                    $('#sl_' + key).val(val).trigger('change');
                }
            }
        });
    }
});

$(document).on('change', '.offcanvas-qty-input', function () {
    var key = $(this).data('key');
    var val = Math.max(1, parseInt($(this).val()) || 1);
    $(this).val(val);
    var url_ajax = AJAX_ROUTES.cart + 'legacy';
    $.ajax({
        url: url_ajax,
        type: 'POST',
        data: { action: 'update_qty', key_cart: key, so_luong: val },
        dataType: 'json',
        success: function() {
            updateOffcanvasTotal();
            if ($('#sl_' + key).length) {
                // To avoid infinite loops of triggering change back and forth, 
                // we set the value but trigger a custom event or check value match in their handler
                var mainInput = $('#sl_' + key);
                if (parseInt(mainInput.val()) !== val) {
                    mainInput.val(val).trigger('change');
                }
            }
        }
    });
});

function updateOffcanvasTotal() {
    var total = 0;
    $('.offcanvas-cart-item').each(function() {
        var qty = parseInt($(this).find('.offcanvas-qty-input').val()) || 0;
        var price = parseFloat($(this).data('price')) || 0;
        total += qty * price;
    });
    $('.offcanvas-total-price').text(typeof formatVND === 'function' ? formatVND(total) : total + ' đ');
}

/**
 * 4. PRICE RANGE SLIDER FUNCTIONS
 */

function initPriceSliders(minRange, maxRange, currentPrice) {
    if (typeof noUiSlider === 'undefined') return;

    var startMin = minRange;
    var startMax = maxRange;

    if (currentPrice) {
        var parts = currentPrice.split('-');
        if (parts[0]) startMin = parseInt(parts[0]);
        if (parts[1] && parseInt(parts[1]) > 0) startMax = parseInt(parts[1]);
    }

    $('.price-range-slider').each(function() {
        var slider = this;
        // Skip hidden unless it's in an offcanvas
        if ($(slider).is(':hidden') && $(slider).closest('.offcanvas').length === 0) return; 

        if (slider.noUiSlider) {
            try { slider.noUiSlider.destroy(); } catch(e) {}
        }

        try {
            noUiSlider.create(slider, {
                start: [startMin, startMax],
                connect: true,
                range: { 'min': minRange, 'max': maxRange },
                step: 10000
            });
            
            var $parent = $(slider).closest('.price-range-slider-wrapper');
            slider.noUiSlider.on('update', function (values, handle) {
                var value = values[handle];
                var displayFunc = typeof formatVND === 'function' ? formatVND : function(v) { return v.toLocaleString('vi-VN') + ' ₫'; };
                if (handle) {
                    $parent.find('.price-max-display').text(displayFunc(value));
                } else {
                    $parent.find('.price-min-display').text(displayFunc(value));
                }
            });
        } catch (e) {
            console.error('Price Slider error:', e);
        }
    });
}

$(document).on('click', '.btn-apply-price', function() {
    var $parent = $(this).closest('.price-range-slider-wrapper');
    var slider = $parent.find('.price-range-slider')[0];
    if (slider && slider.noUiSlider) {
        var values = slider.noUiSlider.get();
        var priceVal = Math.round(values[0]) + '-' + Math.round(values[1]);
        
        // Disable all other price inputs to avoid duplicates in URL
        $('.price-input-control').prop('disabled', true);
        
        var $form = $(this).closest('form');
        var $input = $form.find('.price-range-value');
        $input.prop('disabled', false).val(priceVal);
        $form.submit();
    }
});

function updateSliderFromRadio(el) {
    var val = $(el).val();
    if (val) {
        var parts = val.split('-');
        var min = parseInt(parts[0]);
        var max = parseInt(parts[1]);
        $('.price-range-slider').each(function() {
            if (this.noUiSlider) this.noUiSlider.set([min, max]);
        });
        
        $('.price-range-value').val(val);
    }
    
    // Disable all other price inputs to avoid duplicates
    $('.price-input-control').prop('disabled', true);
    $(el).prop('disabled', false).closest('form').submit();
}

/* ===========================================
   QUICK VIEW MODAL
   =========================================== */
function openQuickView(id) {
    var $modal = $('#quickViewModal');
    var $body  = $('#quickViewBody');

    $body.html('<div class="qv-loading text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Đang tải...</p></div>');
    $modal.modal('show');

    $.ajax({
        url: AJAX_URL, type: 'POST', dataType: 'json',
        data: { do: 'quick_view', id: id },
        success: function(p) {
            if (!p || !p.id) { $body.html('<p class="p-4 text-danger">Không tải được sản phẩm.</p>'); return; }

            var baseUrl = p.urlpath || '';
            var slug = p.slug ? p.slug + '/' : '';
            var productUrl = baseUrl + slug + p.alias + '.html';
            var imgUrl = p.hinh_anh ? baseUrl + 'img_data/images/' + p.hinh_anh : baseUrl + 'img_data/no-image.png';

            // Price display
            var priceHtml = '';
            var minP = parseFloat(p.min_price||0), maxP = parseFloat(p.max_price||0);
            if (minP > 0 && minP === maxP) {
                priceHtml = '<span class="qv-price-sale">' + formatVND(minP) + '</span>';
                if (parseFloat(p.gia||0) > minP) priceHtml += ' <span class="qv-price-old">' + formatVND(p.gia) + '</span>';
            } else if (minP > 0) {
                priceHtml = '<span class="qv-price-sale">' + formatVND(minP) + ' – ' + formatVND(maxP) + '</span>';
            } else if (parseFloat(p.khuyen_mai||0) > 0) {
                priceHtml = '<span class="qv-price-sale">' + formatVND(p.khuyen_mai) + '</span> <span class="qv-price-old">' + formatVND(p.gia) + '</span>';
            } else {
                priceHtml = '<span class="qv-price-sale">' + formatVND(p.gia||0) + '</span>';
            }

            // Attributes
            var attrsHtml = '';
            if (p.attrs && p.attrs.length > 0) {
                $.each(p.attrs, function(i, attr) {
                    attrsHtml += '<div class="qv-attr-group mb-2"><span class="qv-attr-label">' + attr.name + ':</span><div class="d-flex flex-wrap gap-2 mt-1">';
                    $.each(attr.values, function(j, v) {
                        if (attr.loai === 'color') {
                            attrsHtml += '<label class="qv-color-btn" style="background:' + v.val + ';" title="' + v.name + '" data-attr="' + attr.id + '" data-val="' + v.id + '"></label>';
                        } else {
                            attrsHtml += '<label class="qv-attr-btn" data-attr="' + attr.id + '" data-val="' + v.id + '">' + v.name + '</label>';
                        }
                    });
                    attrsHtml += '</div></div>';
                });
            }

            var html = '<div class="qv-layout">'
                + '<div class="qv-img-col"><img src="' + imgUrl + '" alt="' + p.ten + '" class="qv-main-img"></div>'
                + '<div class="qv-info-col">'
                + '<h4 class="qv-title">' + p.ten + '</h4>'
                + (p.ma_sp ? '<p class="qv-sku text-muted small">SKU: ' + p.ma_sp + '</p>' : '')
                + '<div class="qv-price mb-3">' + priceHtml + '</div>'
                + (p.mo_ta ? '<p class="qv-desc">' + p.mo_ta + '</p>' : '')
                + attrsHtml
                + '<div class="qv-actions mt-3 d-flex gap-2">'
                + '<button class="btn btn-primary btn-qv-cart" onclick="quickAddToCart(' + p.id + ')"><i class="fa fa-shopping-cart me-1"></i>Thêm giỏ hàng</button>'
                + '<a href="' + productUrl + '" class="btn btn-outline-secondary"><i class="fa fa-external-link me-1"></i>Xem chi tiết</a>'
                + '</div>'
                + '</div></div>';

            $body.html(html);

            // Attr click
            $body.find('.qv-attr-btn, .qv-color-btn').on('click', function() {
                var $this = $(this);
                $body.find('[data-attr="' + $this.data('attr') + '"]').removeClass('active');
                $this.addClass('active');

                // Match variant
                var selected = {};
                var allSelected = true;
                var totalAttrs = p.attrs ? p.attrs.length : 0;
                
                $body.find('.qv-attr-btn.active, .qv-color-btn.active').each(function() {
                    selected[$(this).data('attr')] = $(this).data('val');
                });

                if (Object.keys(selected).length < totalAttrs) allSelected = false;

                if (allSelected) {
                    var found = null;
                    if (p.variants && p.variants.length > 0) {
                        $.each(p.variants, function(i, v) {
                            var match = true;
                            if (!v.attributes || v.attributes.length !== totalAttrs) { match = false; }
                            else {
                                $.each(v.attributes, function(j, attr) {
                                    if (selected[attr.id_thuoctinh] != attr.id_thuoctinh_giatri) { match = false; return false; }
                                });
                            }
                            if (match) { found = v; return false; }
                        });
                    }

                    if (found) {
                        var price = parseFloat(found.khuyen_mai > 0 ? found.khuyen_mai : found.gia);
                        $body.find('.qv-price').html('<span class="qv-price-sale">' + formatVND(price) + '</span>');
                        if (found.ma_sp) $body.find('.qv-sku').text('SKU: ' + found.ma_sp);
                        $body.find('.btn-qv-cart').attr('onclick', 'quickAddToCart(' + p.id + ', ' + found.id + ')');
                    }
                }
            });
        },
        error: function() { $body.html('<p class="p-4 text-danger">Lỗi kết nối. Vui lòng thử lại.</p>'); }
    });
}

/* Utility: format price in VND */
function formatVND(amount) {
    if (typeof window.currencyConfig !== 'undefined' && window.currencyConfig) {
        var cfg = window.currencyConfig;
        var price = Math.round(parseFloat(amount) * (cfg.rate || 1));
        if (cfg.currency === 'USD') return '$' + price.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        return price.toLocaleString('vi-VN') + ' ' + (cfg.symbol || '₫');
    }
    return Math.round(amount).toLocaleString('vi-VN') + ' ₫';
}

/* ===========================================
   WISHLIST (localStorage)
   =========================================== */
function getWishlist() {
    try { return JSON.parse(localStorage.getItem('wishlist') || '[]'); } catch(e) { return []; }
}
function saveWishlist(list) {
    localStorage.setItem('wishlist', JSON.stringify(list));
}
function toggleWishlist(btn) {
    var id = parseInt($(btn).data('id'));
    var list = getWishlist();
    var idx = list.indexOf(id);
    if (idx === -1) {
        list.push(id);
        $(btn).addClass('active');
    } else {
        list.splice(idx, 1);
        $(btn).removeClass('active');
    }
    saveWishlist(list);
    syncWishlistButtons();
}
function syncWishlistButtons() {
    var list = getWishlist();
    $('.btn-wishlist').each(function() {
        var id = parseInt($(this).data('id'));
        if (list.indexOf(id) !== -1) $(this).addClass('active');
        else $(this).removeClass('active');
    });
    $('.header-wishlist-count').text(list.length);
}
$(document).ready(function() { syncWishlistButtons(); });

/* ===========================================
   RECENTLY VIEWED PRODUCTS (localStorage)
   =========================================== */
var RV_KEY = 'recentlyViewed';
var RV_MAX = 10;

function getRecentlyViewed() {
    try { return JSON.parse(localStorage.getItem(RV_KEY) || '[]'); } catch(e) { return []; }
}

function addRecentlyViewed(id) {
    var list = getRecentlyViewed();
    // Remove if already exists (move to front)
    list = list.filter(function(i) { return i !== id; });
    list.unshift(id);
    if (list.length > RV_MAX) list = list.slice(0, RV_MAX);
    localStorage.setItem(RV_KEY, JSON.stringify(list));
}

function loadRecentlyViewed() {
    if (typeof window.currentProductId === 'undefined') return;

    // Add current product to viewed list first
    addRecentlyViewed(window.currentProductId);

    // Load the other viewed products (exclude current)
    var list = getRecentlyViewed().filter(function(id) { return id !== window.currentProductId; });
    if (list.length === 0) return;

    $.ajax({
        url: AJAX_URL, type: 'POST', dataType: 'json',
        data: { do: 'recently_viewed', ids: list },
        success: function(res) {
            if (!res || !res.products || res.products.length === 0) return;
            var baseUrl = res.urlpath || URLPATH || '';
            var html = '';
            $.each(res.products, function(i, p) {
                var slug = p.slug ? p.slug + '/' : '';
                var url = baseUrl + slug + p.alias + '.html';
                var img = p.hinh_anh ? baseUrl + 'img_data/images/' + p.hinh_anh : baseUrl + 'img_data/no-image.png';
                var minP = parseFloat(p.min_price || 0);
                var maxP = parseFloat(p.max_price || 0);
                var priceStr = minP > 0 ? formatVND(minP) + (minP !== maxP ? ' – ' + formatVND(maxP) : '') : formatVND(p.gia || 0);

                html += '<div class="swiper-slide">'
                    + '<div class="box-product box-hover-zoom">'
                    + '<div class="box-thumbnail"><div class="inner-thumbnail ratio ratio-1x1">'
                    + '<a href="' + url + '"><img src="' + img + '" alt="' + p.ten + '" class="image-cover"></a>'
                    + '</div></div>'
                    + '<div class="box-content"><div class="box-title"><h3 class="title">' + p.ten + '</h3></div>'
                    + '<div class="box-price"><div class="label-sale-price">' + priceStr + '</div></div>'
                    + '</div></div></div>';
            });

            $('#recently-viewed-list').html(html);
            $('#recently-viewed-section').show();

            // Init Swiper using element reference (safe even if other swipers exist)
            if (typeof Swiper !== 'undefined' && !window._rvSwiper) {
                var el = document.querySelector('.recently-viewed-swiper');
                if (el) {
                    window._rvSwiper = new Swiper(el, {
                        slidesPerView: 2,
                        spaceBetween: 12,
                        breakpoints: { 768: { slidesPerView: 3 }, 1024: { slidesPerView: 4 } }
                    });
                }
            }
        }
    });
}

$(document).ready(function() {
    if (typeof window.currentProductId !== 'undefined') {
        loadRecentlyViewed();
    }
});
/* ===========================================
   LIVE SEARCH
   =========================================== */
let liveSearchTimer;
$(document).on('keyup', '#search-header-input', function() {
    clearTimeout(liveSearchTimer);
    const keyword = $(this).val().trim();
    const id_code = $('#search-cat-select').val();
    const $dropdown = $('#live-search-dropdown');

    if (keyword.length < 2) {
        $dropdown.hide().empty();
        return;
    }

    liveSearchTimer = setTimeout(function() {
        $.ajax({
            url: AJAX_ROUTES.product + 'legacy',
            type: 'POST',
            data: { 
                do: 'live_search', 
                keyword: keyword, 
                id_code: id_code 
            },
            success: function(data) {
                if (data && typeof data === 'string' && data.trim() !== "") {
                    $dropdown.html(data).fadeIn(200);
                } else {
                    $dropdown.hide().empty();
                }
            }
        });
    }, 400); 
});

$(document).on('click', function(e) {
    if (!$(e.target).closest('.box-search-header').length) {
        $('#live-search-dropdown').fadeOut(200);
    }
});
