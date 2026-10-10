/* ==========================================================================
   LMS — Chuông thông báo (T10).
   - Mở dropdown → load danh sách chưa đọc qua AJAX (GET .../lms-thong-bao/unread)
   - Click "Đánh dấu tất cả đã đọc" → POST (CSRF kèm theo LMS.post)
   - Click 1 thông báo → đánh dấu đã đọc rồi mở link (nếu có)
   Endpoint: module ẩn lms-thong-bao (router đảm nhiệm auth gate + CSRF + quyền).
   ========================================================================== */

(function ($) {
    'use strict';

    window.LMS = window.LMS || {};

    LMS.notif = {
        urls: {
            list: null,
            markRead: null,
            markAllRead: null
        },
        loaded: false
    };

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function updateBadge(count) {
        var $badge = $('#lms-notif-count');
        count = parseInt(count, 10) || 0;
        if (count <= 0) {
            $badge.attr('hidden', 'hidden').text('0');
        } else {
            $badge.removeAttr('hidden').text(count > 99 ? '99+' : count);
        }
    }

    function renderEmpty() {
        $('#lms-notif-list').html(
            '<div class="lms-dd-empty">'
            + '<i class="fa-solid fa-bell-slash d-block mb-2" style="font-size:1.4rem;"></i>'
            + 'Không có thông báo mới.'
            + '</div>'
        );
    }

    function renderItems(items) {
        if (!items || !items.length) {
            renderEmpty();
            return;
        }
        var html = '';
        $.each(items, function (i, it) {
            var unreadCls = it.is_read ? '' : ' lms-unread';
            html += '<a href="#" class="lms-dd-item' + unreadCls + '" data-notif-id="' + it.id + '"'
                + ' data-link="' + escapeHtml(it.link_url || '') + '">'
                + '<div class="lms-dd-title">' + escapeHtml(it.title) + '</div>'
                + '<div class="lms-dd-msg">' + escapeHtml(it.message) + '</div>'
                + '<div class="lms-dd-time">' + escapeHtml(it.created_at) + '</div>'
                + '</a>';
        });
        $('#lms-notif-list').html(html);
    }

    function loadList() {
        if (!LMS.notif.urls.list) {
            return;
        }
        LMS.ajax(LMS.notif.urls.list, { method: 'GET', cache: false })
            .done(function (res) {
                if (res && res.ok) {
                    renderItems(res.items);
                    updateBadge(res.unread);
                    LMS.notif.loaded = true;
                }
            })
            .fail(function () {
                $('#lms-notif-list').html('<div class="lms-dd-empty">Không tải được thông báo.</div>');
            });
    }

    function markRead(notifId, link) {
        return LMS.post(LMS.notif.urls.markRead, { id: notifId })
            .done(function (res) {
                if (res && res.ok) {
                    updateBadge(res.unread);
                }
            })
            .always(function () {
                if (link) {
                    window.location.href = link;
                }
            });
    }

    $(function () {
        // URL endpoint từ layout
        LMS.notif.urls.list = $('#lms-notif-list').data('list-url');
        LMS.notif.urls.markRead = $('#lms-notif-list').data('mark-read-url');
        LMS.notif.urls.markAllRead = $('.lms-mark-all-read').data('url');

        // Mở dropdown → load danh sách
        $(document).on('click', '#lms-notif-btn', function () {
            if (!LMS.notif.loaded) {
                loadList();
            }
        });

        // Đánh dấu tất cả đã đọc
        $(document).on('click', '.lms-mark-all-read', function (e) {
            e.preventDefault();
            var url = $(this).data('url');
            if (!url) {
                return;
            }
            LMS.post(url, {})
                .done(function (res) {
                    if (res && res.ok) {
                        updateBadge(0);
                        LMS.notif.loaded = false;
                        loadList();
                        LMS.toast('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
                    }
                })
                .fail(function () {
                    LMS.toast('error', 'Không thể đánh dấu thông báo. Vui lòng thử lại.');
                });
        });

        // Click 1 thông báo → đánh dấu đã đọc + mở link
        $(document).on('click', '.lms-dd-item[data-notif-id]', function (e) {
            e.preventDefault();
            var $item = $(this);
            var link = $item.data('link');
            var notifId = $item.data('notif-id');
            $item.removeClass('lms-unread');
            markRead(notifId, link);
        });
    });
})(jQuery);
