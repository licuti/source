/* ==========================================================================
   LMS MasterStudy — JS chung 3 portal.
   - CSRF tự gửi kèm mọi AJAX (header X-CSRF-Token)
   - Toggle sidebar (mobile)
   - Đóng banner cảnh báo
   - Dropdown chuông thông báo + user menu
   - Render flash message (toast)
   PHP 7.3 không ảnh hưởng (JS thuần + jQuery).
   ========================================================================== */

(function ($) {
    'use strict';

    // ===================== Cấu hình toàn cục =====================

    window.LMS = window.LMS || {};

    /**
     * Sinh/lấy CSRF token từ meta tag trong <head> (layout render).
     */
    LMS.csrfToken = function () {
        var $meta = $('meta[name="lms-csrf-token"]');
        return $meta.length ? $meta.attr('content') : '';
    };

    /**
     * Gửi AJAX kèm CSRF header + credentials.
     */
    LMS.ajax = function (url, options) {
        options = options || {};
        var headers = options.headers || {};
        var token = LMS.csrfToken();
        if (token) {
            headers['X-CSRF-Token'] = token;
        }
        options.headers = headers;
        options.url = url;
        if (!options.hasOwnProperty('dataType')) {
            options.dataType = 'json';
        }
        return $.ajax(options);
    };

    /**
     * POST form-data kèm CSRF.
     */
    LMS.post = function (url, data, options) {
        var body = $.extend({}, data, { csrf_token: LMS.csrfToken() });
        var opts = $.extend({ method: 'POST', data: body }, options || {});
        return LMS.ajax(url, opts);
    };

    // ===================== Flash message (toast) =====================

    /**
     * Hiện thông báo nhanh góc trên phải.
     *
     * @param string type 'success' | 'error' | 'warning' | 'info'
     * @param string message
     */
    LMS.toast = function (type, message) {
        if (typeof Toastify === 'undefined') {
            alert(message);
            return;
        }
        var bg = {
            success: 'linear-gradient(90deg,#28a745,#20c997)',
            error: 'linear-gradient(90deg,#dc3545,#e74c5e)',
            warning: 'linear-gradient(90deg,#f0ad4e,#F26B2A)',
            info: 'linear-gradient(90deg,#0d6efd,#3b82f6)'
        };
        Toastify({
            text: message,
            duration: 4000,
            close: true,
            gravity: 'top',
            position: 'right',
            style: { background: bg[type] || bg.info }
        }).showToast();
    };

    /**
     * Render flash message do server xuất qua data attribute (layout).
     */
    LMS.renderFlash = function () {
        var $holder = $('#lms-flash-data');
        if (!$holder.length) {
            return;
        }
        var type = $holder.data('type');
        var msg = $holder.data('message');
        if (msg) {
            LMS.toast(type, msg);
        }
    };

    // ===================== Sidebar (mobile) =====================

    LMS.initSidebar = function () {
        var $sidebar = $('.lms-sidebar');
        var $backdrop = $('.lms-sidebar-backdrop');
        if (!$sidebar.length) {
            return;
        }
        $(document).on('click', '.lms-sidebar-toggle', function (e) {
            e.preventDefault();
            $sidebar.toggleClass('show');
            $backdrop.toggleClass('show');
        });
        $backdrop.on('click', function () {
            $sidebar.removeClass('show');
            $backdrop.removeClass('show');
        });
    };

    // ===================== Đóng banner cảnh báo =====================

    LMS.initAlertBanner = function () {
        $(document).on('click', '.lms-alert-banner .btn-close-lms', function () {
            $(this).closest('.lms-alert-banner').fadeOut(180, function () {
                $(this).remove();
            });
        });
    };

    // ===================== Dropdown (chuông + user menu) =====================

    LMS.initDropdowns = function () {
        // Click bên ngoài → đóng tất cả dropdown LMS
        $(document).on('click', function (e) {
            var $open = $('.lms-dropdown.open');
            if ($open.length && !$(e.target).closest('.lms-dropdown').length) {
                $open.removeClass('open');
            }
        });
        // Toggle
        $(document).on('click', '.lms-dropdown-toggle', function (e) {
            e.preventDefault();
            var $dd = $(this).closest('.lms-dropdown');
            var wasOpen = $dd.hasClass('open');
            $('.lms-dropdown.open').removeClass('open');
            if (!wasOpen) {
                $dd.addClass('open');
            }
        });
    };

    // ===================== Khởi tạo =====================

    $(function () {
        LMS.renderFlash();
        LMS.initSidebar();
        LMS.initAlertBanner();
        LMS.initDropdowns();
    });
})(jQuery);
