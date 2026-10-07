/* ==========================================================================
 * ui-adapter.js  —  LỚP CẦU NỐI (Adapter) CHO CÁC THƯ VIỆN UI
 *
 * MỤC ĐÍCH
 * Trước đây code gọi trực tiếp thư viện, ví dụ `$.notify("Xong","success")`.
 * Muốn đổi sang thư viện khác thì phải sửa hàng trăm chỗ.
 *
 * Nay mọi nơi chỉ gọi qua 1 "cổng" duy nhất:
 *
 *      AdminUI.notify.success('Cập nhật thành công');
 *      AdminUI.confirm.delete({ name: 'Danh mục A' }).then(ok => { ... });
 *      AdminUI.http.post(url, data).then(...);
 *      AdminUI.storage.get('key', default);
 *
 * Khi muốn thay thư viện (vd Toastify -> Notyf), CHỈ sửa 1 driver trong file
 * này, toàn bộ module còn lại không phải đổi 1 dòng nào.
 *
 * CƠ CHẾ
 * - Mỗi tính năng có nhiều "driver" (cách hiện thực).
 * - Adapter tự chọn driver đầu tiên khả dụng (thư viện đã được nạp).
 * - Nếu không có thư viện nào -> tự lùi về driver native của browser
 *   (alert/confirm) nên KHÔNG BAO GIỜ vỡ chức năng.
 *
 * Muốn ép dùng 1 driver cụ thể:
 *      AdminUI.notify.use('native');
 *      AdminUI.confirm.use('sweetalert');
 * ========================================================================== */
(function (window) {
    'use strict';

    /* ======================================================================
     * 1. NOTIFY — thông báo dạng toast
     * ==================================================================== */

    /**
     * Mỗi driver phải có: isAvailable() và show(message, type)
     * type: 'success' | 'error' | 'warning' | 'info'
     */
    var notifyDrivers = {

        /* Toastify — thư viện mặc định hiện tại */
        toastify: {
            isAvailable: function () { return typeof window.Toastify !== 'undefined'; },
            show: function (message, type) {
                var colors = {
                    success: 'linear-gradient(135deg,#198754,#20c997)',
                    error: 'linear-gradient(135deg,#dc3545,#e35d6a)',
                    warning: 'linear-gradient(135deg,#fd7e14,#ffc107)',
                    info: 'linear-gradient(135deg,#0d6efd,#0dcaf0)'
                };
                var icons = {
                    success: '\u2713 ', error: '\u2717 ', warning: '\u26A0 ', info: '\u2139 '
                };

                window.Toastify({
                    text: (icons[type] || '') + message,
                    duration: type === 'error' ? 5000 : 3000,
                    gravity: 'top',
                    position: 'right',
                    close: true,
                    stopOnFocus: true,
                    style: { background: colors[type] || colors.info, borderRadius: '6px' }
                }).showToast();
            }
        },

        /* jQuery Notify — thư viện CŨ, giữ lại để tương thích ngược */
        jqueryNotify: {
            isAvailable: function () {
                return typeof window.jQuery !== 'undefined' && typeof window.jQuery.notify === 'function';
            },
            show: function (message, type) {
                window.jQuery.notify(message, type);
            }
        },

        /* Dự phòng cuối: luôn dùng được, không cần thư viện */
        native: {
            isAvailable: function () { return true; },
            show: function (message, type) {
                if (type === 'error') {
                    console.error('[Admin]', message);
                } else {
                    console.log('[Admin]', message);
                }
            }
        }
    };

    /* ======================================================================
     * 2. CONFIRM — hộp thoại xác nhận
     * ==================================================================== */

    /**
     * Mỗi driver phải có: isAvailable() và ask(options) -> Promise<boolean>
     */
    var confirmDrivers = {

        /* SweetAlert2 — mặc định, đẹp và không chặn luồng */
        sweetalert: {
            isAvailable: function () { return typeof window.Swal !== 'undefined'; },
            ask: function (opts) {
                return window.Swal.fire({
                    title: opts.title,
                    html: opts.message,
                    icon: opts.icon || 'warning',
                    showCancelButton: true,
                    confirmButtonText: opts.confirmText || 'Đồng ý',
                    cancelButtonText: opts.cancelText || 'Hủy',
                    confirmButtonColor: opts.danger ? '#dc3545' : '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    focusCancel: true
                }).then(function (result) {
                    return result.isConfirmed === true;
                });
            }
        },

        /* Dự phòng: confirm() của browser */
        native: {
            isAvailable: function () { return true; },
            ask: function (opts) {
                // Bỏ thẻ HTML vì confirm() chỉ hiển thị text thuần
                var text = String(opts.message || opts.title || 'Xác nhận?')
                    .replace(/<[^>]*>/g, '');
                return Promise.resolve(window.confirm(text));
            }
        }
    };

    /* ======================================================================
     * 3. Bộ chọn driver dùng chung
     * ==================================================================== */

    /**
     * Tạo 1 "bộ chọn": tự tìm driver khả dụng theo thứ tự ưu tiên.
     *
     * @param {Object} drivers  bảng driver
     * @param {Array}  priority thứ tự ưu tiên tên driver
     */
    function createResolver(drivers, priority) {
        var forced = null;

        return {
            /** Ép dùng 1 driver cụ thể theo tên. */
            use: function (name) {
                if (!drivers[name]) {
                    console.warn('[AdminUI] Không có driver:', name);
                    return;
                }
                forced = name;
            },

            /** Lấy driver đang dùng. */
            resolve: function () {
                if (forced && drivers[forced].isAvailable()) {
                    return drivers[forced];
                }
                for (var i = 0; i < priority.length; i++) {
                    var d = drivers[priority[i]];
                    if (d && d.isAvailable()) { return d; }
                }
                return drivers.native;
            },

            /** Tên driver đang dùng (hữu ích khi debug). */
            current: function () {
                if (forced) { return forced; }
                for (var i = 0; i < priority.length; i++) {
                    if (drivers[priority[i]] && drivers[priority[i]].isAvailable()) {
                        return priority[i];
                    }
                }
                return 'native';
            },

            drivers: drivers
        };
    }

    var notifyResolver = createResolver(notifyDrivers, ['toastify', 'jqueryNotify', 'native']);
    var confirmResolver = createResolver(confirmDrivers, ['sweetalert', 'native']);

    /* ======================================================================
     * 4. API công khai
     * ==================================================================== */

    var AdminUI = {

        /* ---------------- Thông báo ---------------- */
        notify: {
            use: notifyResolver.use,
            current: notifyResolver.current,

            show: function (message, type) {
                notifyResolver.resolve().show(message, type || 'info');
            },
            success: function (message) { this.show(message || 'Thành công', 'success'); },
            error: function (message) { this.show(message || 'Đã xảy ra lỗi', 'error'); },
            warning: function (message) { this.show(message, 'warning'); },
            info: function (message) { this.show(message, 'info'); },

            /** Dùng ngay sau khi gọi AJAX: tự chọn thông báo theo kết quả. */
            result: function (ok, okMsg, failMsg) {
                if (ok) { this.success(okMsg || 'Cập nhật thành công'); }
                else { this.error(failMsg || 'Cập nhật thất bại'); }
            }
        },

        /* ---------------- Xác nhận ---------------- */
        confirm: {
            use: confirmResolver.use,
            current: confirmResolver.current,

            /**
             * @param  {Object} opts {title, message, confirmText, cancelText, icon, danger}
             * @return {Promise<boolean>}
             */
            ask: function (opts) {
                return confirmResolver.resolve().ask(opts || {});
            },

            /**
             * Hộp thoại xác nhận xoá, có cảnh báo số mục con nếu có.
             *
             * @param  {Object} opts {name, childCount, extra}
             * @return {Promise<boolean>}
             */
            delete: function (opts) {
                opts = opts || {};

                var message = opts.name
                    ? 'Bạn có chắc muốn xóa <strong>' + escapeHtml(opts.name) + '</strong>?'
                    : 'Bạn có chắc muốn xóa mục này?';

                if (opts.childCount > 0) {
                    message += '<br><span class="text-danger">Sẽ xóa kèm '
                        + opts.childCount + ' mục con trực thuộc!</span>';
                }
                if (opts.extra) {
                    message += '<br>' + opts.extra;
                }
                message += '<br><small class="text-secondary">Hành động này không thể hoàn tác.</small>';

                return this.ask({
                    title: 'Xác nhận xóa',
                    message: message,
                    icon: 'warning',
                    confirmText: 'Xóa',
                    cancelText: 'Hủy',
                    danger: true
                });
            }
        },

        /* ---------------- HTTP ----------------
         * Bọc fetch/jQuery.ajax để module không phụ thuộc jQuery.
         */
        http: {
            /**
             * POST dạng form-urlencoded (khớp với các endpoint PHP hiện có).
             *
             * @param  {string} url
             * @param  {Object} data
             * @return {Promise<{ok:boolean, text:string, json:*}>}
             */
            post: function (url, data) {
                var body = new URLSearchParams();
                Object.keys(data || {}).forEach(function (k) {
                    body.append(k, data[k]);
                });

                return fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString(),
                    credentials: 'same-origin'
                }).then(function (res) {
                    return res.text().then(function (text) {
                        var json = null;
                        try { json = JSON.parse(text); } catch (e) { /* không phải JSON */ }
                        return { ok: res.ok, status: res.status, text: text.trim(), json: json };
                    });
                });
            }
        },

        /* ---------------- Lưu trữ cục bộ ----------------
         * Bọc localStorage: tự bắt lỗi khi bị tắt / chế độ ẩn danh.
         */
        storage: {
            get: function (key, fallback) {
                try {
                    var raw = window.localStorage.getItem(key);
                    return raw === null ? fallback : JSON.parse(raw);
                } catch (e) {
                    return fallback;
                }
            },
            set: function (key, value) {
                try {
                    window.localStorage.setItem(key, JSON.stringify(value));
                    return true;
                } catch (e) {
                    return false;
                }
            },
            remove: function (key) {
                try { window.localStorage.removeItem(key); } catch (e) { /* bỏ qua */ }
            }
        }
    };

    /** Escape HTML để chèn tên do người dùng nhập vào hộp thoại. */
    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    window.AdminUI = AdminUI;
})(window);