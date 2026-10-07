/* ==========================================================================
 * csrf.js — Tự động đính kèm CSRF token vào mọi POST AJAX nội bộ admin.
 *
 * Token lấy từ <meta name="csrf-token"> do admin/index.php sinh.
 *
 * Cơ chế:
 *  - Hook `$.ajaxPrefilter` của jQuery: thêm `csrf_token` vào mọi POST tới
 *    các endpoint admin (sources/ajax.php, sources/aj_checkbox.php,
 *    ajax_pancake_*.php).
 *  - Bọc `AdminUI.http.post` để luôn gửi kèm `csrf_token`.
 *
 * Form POST thường (submit trang) KHÔNG đi qua đây — các form đã có
 * `admin_csrf_field()` là hidden input.
 *
 * PHẢI nạp SAU jquery + ui-adapter.js.
 * ========================================================================== */
(function (window, document) {
    'use strict';

    function readToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? (meta.getAttribute('content') || '') : '';
    }

    var CSRF_TOKEN = readToken();

    function isSameOrigin(url) {
        if (!url) { return true; }
        if (/^https?:\/\//i.test(url)) {
            try {
                var u = new URL(url, window.location.href);
                return u.origin === window.location.origin;
            } catch (e) { return false; }
        }
        return true;
    }

    /* Khớp các endpoint admin cần CSRF. */
    function isAdminAjaxUrl(url) {
        if (!url) { return false; }
        return /(?:^|\/)(?:sources\/ajax\.php|sources\/aj_checkbox\.php|ajax_pancake_[a-z_]+\.php)(?:[?#]|$)/i.test(url);
    }

    /* 1) jQuery: áp cho mọi $.ajax POST tới endpoint admin. */
    if (window.jQuery && window.jQuery.ajaxPrefilter) {
        window.jQuery.ajaxPrefilter(function (options) {
            if (!CSRF_TOKEN) { return; }
            var type = (options.type || options.method || 'GET').toUpperCase();
            if (type !== 'POST') { return; }
            if (!isSameOrigin(options.url)) { return; }
            if (!isAdminAjaxUrl(options.url)) { return; }

            if (typeof options.data === 'string') {
                options.data += (options.data.length ? '&' : '')
                    + 'csrf_token=' + encodeURIComponent(CSRF_TOKEN);
            } else if (window.jQuery.isPlainObject(options.data)) {
                options.data.csrf_token = CSRF_TOKEN;
            } else if (options.data === undefined || options.data === null) {
                options.data = { csrf_token: CSRF_TOKEN };
            }
        });
    }

    /* 2) AdminUI.http.post: tự thêm csrf_token nếu chưa có. */
    function wrapAdminUI() {
        if (!window.AdminUI || !window.AdminUI.http || window.AdminUI.http.__csrfWrapped) {
            return false;
        }
        var originalPost = window.AdminUI.http.post;
        window.AdminUI.http.post = function (url, data) {
            data = data || {};
            if (CSRF_TOKEN && isSameOrigin(url) && isAdminAjaxUrl(url)
                && data.csrf_token === undefined) {
                data.csrf_token = CSRF_TOKEN;
            }
            return originalPost.call(this, url, data);
        };
        window.AdminUI.http.__csrfWrapped = true;
        return true;
    }

    if (!wrapAdminUI()) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', wrapAdminUI);
        } else {
            setTimeout(wrapAdminUI, 0);
        }
    }

    window.AdminCSRF = {
        token: CSRF_TOKEN,
        isAdminAjaxUrl: isAdminAjaxUrl
    };
})(window, document);