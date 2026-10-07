/* ==========================================================================
 * notify-bridge.js  —  CẦU NỐI CHO CODE CŨ
 *
 * VẤN ĐỀ: `admin.js` và ~30 module cũ đang gọi trực tiếp:
 *      $.notify("Cập nhật thành công", "success");
 *
 * Muốn tất cả cùng dùng thư viện toast mới (Toastify) mà KHÔNG phải sửa từng
 * file, ta ghi đè `$.notify` để nó chuyển tiếp sang AdminUI.notify.
 *
 * Nhờ vậy:
 *  - Code cũ không cần đổi 1 dòng nào.
 *  - Khi muốn thay thư viện toast, chỉ sửa driver trong ui-adapter.js.
 *
 * PHẢI nạp SAU jquery + ui-adapter.js và TRƯỚC admin.js.
 * ========================================================================== */
(function (window) {
    'use strict';

    var $ = window.jQuery;

    if (!$ || !window.AdminUI) {
        return;
    }

    /*
     * Ghi đè $.notify. Giữ đúng chữ ký cũ: $.notify(message, type)
     * type cũ có thể là 'success' | 'error' | 'warn' | 'info'.
     */
    $.notify = function (message, type) {
        // notify.js cũ cho phép truyền object làm tham số thứ 2
        if (type && typeof type === 'object') {
            type = type.className || 'info';
        }

        // Chuẩn hoá tên loại thông báo
        var map = { warn: 'warning', danger: 'error', fail: 'error' };
        type = map[type] || type || 'info';

        window.AdminUI.notify.show(String(message), type);
    };

    /*
     * Ghi đè window.confirm để các link cũ dùng
     *      onclick="if(!confirm('...')) return false;"
     * KHÔNG được đổi sang SweetAlert2, vì SweetAlert2 hoạt động bất đồng bộ
     * (trả về Promise) nên không thể chặn điều hướng theo kiểu đồng bộ.
     * -> Giữ nguyên confirm() gốc của browser cho code cũ để đảm bảo an toàn.
     *
     * Các module MỚI nên dùng AdminUI.confirm.delete() với data-attribute
     * (xem admin-ui.js) để có hộp thoại đẹp.
     */
})(window);