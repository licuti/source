/* ==========================================================================
 * admin-ui.js
 * Hành vi cho bộ component mới (admin/templates/components/).
 *
 * Viết bằng JS thuần (không phụ thuộc jQuery) để dần giảm phụ thuộc.
 * Các hàm cũ trong admin.js (on_check, xoa_img, a_stt...) vẫn giữ nguyên.
 * ========================================================================== */
(function () {
    'use strict';

    /* ----------------------------------------------------------------------
     * 1. Chọn tất cả dòng
     *
     * Chỉ tick các checkbox CHỌN DÒNG (.js-row-check), KHÔNG đụng tới các
     * công tắc trạng thái (hien_thi, menu, tieu_bieu...) cũng nằm trong bảng.
     *
     * Ghi chú: handler tương ứng bên admin.js trước đây dùng
     *      $('.table input:checkbox').prop('checked', this.checked)
     * nên bật luôn mọi công tắc trạng thái. Lỗi đó ĐÃ được sửa trong admin.js
     * (nay chỉ nhắm chk_child[] và .js-row-check), vì vậy 2 handler cùng chạy
     * trên 1 trang vẫn cho cùng kết quả và không xung đột.
     * -------------------------------------------------------------------- */
    function initSelectAll() {
        var master = document.getElementById('check_all');
        if (!master) { return; }

        var table = master.closest('table') || document;

        function rowChecks() {
            return Array.prototype.slice.call(table.querySelectorAll('.js-row-check'));
        }

        master.addEventListener('change', function () {
            rowChecks().forEach(function (cb) {
                if (!cb.disabled) { cb.checked = master.checked; }
            });
            updateBulkBar();
        });

        // Bỏ tick 1 dòng -> master về trạng thái "không xác định"
        table.addEventListener('change', function (ev) {
            if (!ev.target.classList || !ev.target.classList.contains('js-row-check')) { return; }

            var all = rowChecks();
            var checked = all.filter(function (cb) { return cb.checked; });

            master.checked = checked.length > 0 && checked.length === all.length;
            master.indeterminate = checked.length > 0 && checked.length < all.length;

            updateBulkBar();
        });
    }

    /* ----------------------------------------------------------------------
     * 2. Thanh thông báo "đã chọn N dòng"
     * -------------------------------------------------------------------- */
    function updateBulkBar() {
        var bar = document.querySelector('.bulk-bar');
        if (!bar) { return; }

        var count = document.querySelectorAll('.js-row-check:checked').length;
        var label = bar.querySelector('.bulk-count');

        if (label) { label.textContent = count; }
        bar.classList.toggle('is-visible', count > 0);
    }

    /* ----------------------------------------------------------------------
     * 3. Thu gọn / mở rộng nhánh cây danh mục
     *
     * Cấu trúc: mỗi <tr> có data-id và data-parent. Khi thu gọn 1 node, ta ẩn
     * toàn bộ con-cháu của nó. Trạng thái được lưu vào localStorage theo module.
     * -------------------------------------------------------------------- */
    function initTree() {
        var table = document.querySelector('[data-tree-table]');
        if (!table) { return; }

        var storeKey = 'admin-tree-collapsed:' + (table.getAttribute('data-tree-table') || 'default');
        var collapsed = loadCollapsed(storeKey);

        function loadCollapsed(key) {
            try {
                return new Set(JSON.parse(localStorage.getItem(key) || '[]'));
            } catch (err) {
                return new Set();
            }
        }

        function saveCollapsed() {
            try {
                localStorage.setItem(storeKey, JSON.stringify(Array.prototype.slice.call(collapsed)));
            } catch (err) { /* localStorage bị tắt -> bỏ qua */ }
        }

        var rows = Array.prototype.slice.call(table.querySelectorAll('tr[data-id]'));

        /** Lấy tất cả dòng con-cháu của 1 id. */
        function descendants(id) {
            var result = [];
            var queue = [String(id)];

            while (queue.length) {
                var parent = queue.shift();
                rows.forEach(function (tr) {
                    if (tr.getAttribute('data-parent') === parent) {
                        result.push(tr);
                        queue.push(tr.getAttribute('data-id'));
                    }
                });
            }
            return result;
        }

        /** Áp dụng trạng thái ẩn/hiện cho toàn bảng. */
        function render() {
            // Mặc định hiện tất cả
            rows.forEach(function (tr) { tr.hidden = false; });

            // Với mỗi node bị thu gọn -> ẩn con cháu
            collapsed.forEach(function (id) {
                descendants(id).forEach(function (tr) { tr.hidden = true; });
            });

            // Cập nhật icon nút toggle
            table.querySelectorAll('.tree-toggle').forEach(function (btn) {
                btn.classList.toggle('collapsed', collapsed.has(btn.getAttribute('data-target-id')));
            });
        }

        table.addEventListener('click', function (ev) {
            var btn = ev.target.closest('.tree-toggle');
            if (!btn) { return; }

            ev.preventDefault();
            var id = btn.getAttribute('data-target-id');

            if (collapsed.has(id)) {
                collapsed.delete(id);
            } else {
                collapsed.add(id);
            }

            saveCollapsed();
            render();
        });

        render();
    }

    /* ----------------------------------------------------------------------
     * 4. Lọc nhanh trên cây (không reload trang)
     *
     * Khi nhập từ khoá, ẩn các dòng không khớp NHƯNG vẫn giữ lại dòng cha của
     * dòng khớp để người dùng thấy được ngữ cảnh.
     * -------------------------------------------------------------------- */
    function initQuickFilter() {
        var input = document.querySelector('[data-quick-filter]');
        var table = document.querySelector('[data-tree-table]');
        if (!input || !table) { return; }

        var rows = Array.prototype.slice.call(table.querySelectorAll('tr[data-id]'));
        var byId = {};
        rows.forEach(function (tr) { byId[tr.getAttribute('data-id')] = tr; });

        input.addEventListener('input', debounce(function () {
            var keyword = input.value.trim().toLowerCase();

            if (keyword === '') {
                rows.forEach(function (tr) { tr.hidden = false; });
                return;
            }

            var visible = new Set();

            rows.forEach(function (tr) {
                var name = (tr.getAttribute('data-name') || '').toLowerCase();
                if (name.indexOf(keyword) === -1) { return; }

                visible.add(tr.getAttribute('data-id'));

                // Hiện luôn chuỗi cha để không bị "mồ côi"
                var parentId = tr.getAttribute('data-parent');
                while (parentId && byId[parentId] && !visible.has(parentId)) {
                    visible.add(parentId);
                    parentId = byId[parentId].getAttribute('data-parent');
                }
            });

            rows.forEach(function (tr) {
                tr.hidden = !visible.has(tr.getAttribute('data-id'));
            });
        }, 250));
    }

    /* ----------------------------------------------------------------------
     * 5. Bộ đếm ký tự cho ô SEO
     * -------------------------------------------------------------------- */
    function initCharCounters() {
        document.querySelectorAll('.js-char-counter').forEach(function (counter) {
            var target = document.getElementById(counter.getAttribute('data-counter-for'));
            var max = parseInt(counter.getAttribute('data-counter-max'), 10) || 0;
            if (!target || !max) { return; }

            function update() {
                var len = target.value.length;
                counter.textContent = len + ' / ' + max + ' ký tự';
                counter.classList.toggle('is-over', len > max);
                counter.classList.toggle('is-near', len <= max && len > max * 0.9);
            }

            target.addEventListener('input', update);
            update();
        });
    }

    /* ----------------------------------------------------------------------
     * 6. Cảnh báo khi rời trang mà chưa lưu form
     * -------------------------------------------------------------------- */
    function initUnsavedGuard() {
        var form = document.querySelector('form[data-warn-unsaved]');
        if (!form) { return; }

        var dirty = false;

        form.addEventListener('input', function () { dirty = true; });
        form.addEventListener('change', function () { dirty = true; });
        form.addEventListener('submit', function () { dirty = false; });

        window.addEventListener('beforeunload', function (ev) {
            if (!dirty) { return; }
            ev.preventDefault();
            ev.returnValue = '';
        });
    }

    /* ----------------------------------------------------------------------
     * 7. Xác nhận xoá bằng data-attribute (dùng AdminUI.confirm)
     *
     * Cách dùng trong HTML — không cần viết JS:
     *      <a href="...&a=delete&id=5"
     *         data-confirm-delete
     *         data-name="Danh mục A"
     *         data-child-count="3">Xóa</a>
     *
     * Vì hộp thoại là bất đồng bộ, ta chặn click rồi tự điều hướng sau khi
     * người dùng đồng ý.
     * -------------------------------------------------------------------- */
    function initConfirmDelete() {
        document.addEventListener('click', function (ev) {
            var el = ev.target.closest('[data-confirm-delete]');
            if (!el) { return; }

            ev.preventDefault();
            ev.stopPropagation();

            var isForm = el.tagName === 'BUTTON' && el.form;

            AdminUI.confirm.delete({
                name: el.getAttribute('data-name') || '',
                childCount: parseInt(el.getAttribute('data-child-count'), 10) || 0
            }).then(function (ok) {
                if (!ok) { return; }

                if (isForm) {
                    el.form.submit();
                } else if (el.href) {
                    window.location.href = el.href;
                }
            });
        });
    }

    /* ----------------------------------------------------------------------
     * 8. Tác vụ hàng loạt (thay cho <select onclick="form_submit(this)">)
     *
     *      <select data-bulk-action data-form="#form"> ... </select>
     * -------------------------------------------------------------------- */
    function initBulkAction() {
        var select = document.querySelector('[data-bulk-action]');
        if (!select) { return; }

        select.addEventListener('change', function () {
            var action = select.value;
            if (!action) { return; }

            var form = document.querySelector(select.getAttribute('data-form') || '#form');
            if (!form) { return; }

            var count = document.querySelectorAll('.js-row-check:checked').length;

            if (count === 0) {
                AdminUI.notify.warning('Vui lòng chọn ít nhất 1 dòng');
                select.value = '';
                return;
            }

            AdminUI.confirm.delete({
                extra: 'Đang chọn <strong>' + count + '</strong> mục.'
            }).then(function (ok) {
                if (ok) { form.submit(); }
                else { select.value = ''; }
            });
        });
    }

    /* ----------------------------------------------------------------------
     * 9. Công tắc trạng thái (thay onclick="on_check(...)")
     *
     *      <input type="checkbox" class="js-toggle"
     *             data-table="#_category" data-col="hien_thi" data-id="12">
     *
     * Ưu điểm so với on_check() cũ:
     *  - Dùng AdminUI.http (fetch) thay vì phụ thuộc jQuery
     *  - Có trạng thái "đang lưu" và TỰ HOÀN TÁC nếu lưu thất bại
     * -------------------------------------------------------------------- */
    function initToggles() {
        document.addEventListener('change', function (ev) {
            var cb = ev.target;
            if (!cb.classList || !cb.classList.contains('js-toggle')) { return; }
            if (!cb.dataset.table || !cb.dataset.col) { return; }   // dùng onclick cũ

            var desired = cb.checked;
            cb.disabled = true;

            AdminUI.http.post('./sources/aj_checkbox.php', {
                do: 'checkbox',
                trangthai: desired ? 1 : 0,
                bang: cb.dataset.table,
                cot: cb.dataset.col,
                id: cb.dataset.id
            }).then(function (res) {
                cb.disabled = false;

                if (res.ok) {
                    AdminUI.notify.success('Đã cập nhật');
                } else {
                    cb.checked = !desired;          // hoàn tác
                    AdminUI.notify.error('Lưu thất bại, đã hoàn tác');
                }
            }).catch(function () {
                cb.disabled = false;
                cb.checked = !desired;
                AdminUI.notify.error('Lỗi kết nối, đã hoàn tác');
            });
        });
    }

    /* ----------------------------------------------------------------------
     * 9b. Khối SEO: xem trước kết quả Google + đo độ dài Title/Description
     *
     * Markup do component form/seo_panel.php sinh ra. Mỗi tab ngôn ngữ là
     * một khối [data-seo-panel] độc lập nên trang đa ngôn ngữ vẫn chạy đúng.
     *
     * Lưu ý: luôn gán bằng textContent (KHÔNG dùng innerHTML) vì nội dung
     * người dùng gõ vào phải được coi là văn bản thuần.
     * -------------------------------------------------------------------- */
    function initSeoPanel() {
        var panels = document.querySelectorAll('[data-seo-panel]');
        if (!panels.length) { return; }

        Array.prototype.forEach.call(panels, function (panel) {
            var d = panel.dataset;

            var titleInput = document.getElementById(d.titleId);
            var desInput = document.getElementById(d.desId);
            var nameInput = d.nameId ? document.getElementById(d.nameId) : null;
            var slugInput = d.slugId ? document.getElementById(d.slugId) : null;
            var aliasInput = d.aliasId ? document.getElementById(d.aliasId) : null;

            var serpTitle = panel.querySelector('[data-seo-serp-title]');
            var serpDesc = panel.querySelector('[data-seo-serp-desc]');
            var serpUrl = panel.querySelector('[data-seo-url]');
            var preview = panel.querySelector('[data-seo-preview]');
            var tagBox = panel.querySelector('[data-seo-tags]');

            var limits = {
                title: { min: +d.titleMin || 50, max: +d.titleMax || 60 },
                des: { min: +d.desMin || 120, max: +d.desMax || 160 }
            };

            /** Trả về phần tử con của đúng ô đo (title | des). */
            function meterParts(kind) {
                var meter = panel.querySelector('[data-seo-meter="' + kind + '"]');
                if (!meter) { return null; }

                // Thanh đo nằm cùng .seo-field với ô đo tương ứng
                var field = meter.closest('.seo-field');

                return {
                    root: meter,
                    count: meter.querySelector('[data-seo-count]'),
                    badge: meter.querySelector('[data-seo-badge]'),
                    bar: field ? field.querySelector('[data-seo-bar]') : null
                };
            }

            /**
             * Cập nhật bộ đếm + thanh đo cho 1 field.
             * Ngắn hơn min -> vàng, trong khoảng -> xanh, vượt max -> đỏ.
             */
            function updateMeter(kind, text) {
                var p = meterParts(kind);
                if (!p) { return; }

                var len = text.length;
                var lim = limits[kind];
                var state = 'warn';
                var label = 'Hơi ngắn';

                if (len === 0) {
                    state = '';
                    label = 'Trống';
                } else if (len > lim.max) {
                    state = 'over';
                    label = 'Quá dài';
                } else if (len >= lim.min) {
                    state = 'good';
                    label = 'Tốt';
                }

                if (p.count) { p.count.textContent = len; }
                if (p.badge) { p.badge.textContent = label; }

                p.root.classList.remove('is-good', 'is-warn', 'is-over');
                if (state) { p.root.classList.add('is-' + state); }

                if (p.bar) {
                    var pct = Math.min(100, Math.round((len / lim.max) * 100));
                    p.bar.style.width = pct + '%';
                    p.bar.classList.remove('is-good', 'is-warn', 'is-over');
                    if (state) { p.bar.classList.add('is-' + state); }
                }
            }

            /** Ghép đường dẫn hiển thị: tên-miền/tiền-tố/đường-dẫn */
            function buildUrl() {
                var parts = [d.baseUrl || ''];

                [slugInput, aliasInput].forEach(function (input) {
                    if (!input) { return; }
                    var v = (input.value || '').trim().replace(/^\/+|\/+$/g, '');
                    if (v !== '') { parts.push(v); }
                });

                return parts.join(' › ');
            }

            /** Vẽ lại toàn bộ phần xem trước. */
            function render() {
                var titleVal = titleInput ? titleInput.value.trim() : '';
                var desVal = desInput ? desInput.value.trim() : '';

                // Chưa nhập Title -> tạm mượn Tên danh mục cho dễ hình dung
                var fallbackTitle = nameInput ? nameInput.value.trim() : '';

                if (serpTitle) {
                    var shownTitle = titleVal || fallbackTitle;
                    serpTitle.textContent = shownTitle || 'Tiêu đề trang sẽ hiển thị ở đây';
                    serpTitle.classList.toggle('is-placeholder', titleVal === '');
                }

                if (serpDesc) {
                    serpDesc.textContent = desVal
                        || 'Google sẽ tự trích một đoạn nội dung trong trang khi bạn để trống mô tả.';
                    serpDesc.classList.toggle('is-placeholder', desVal === '');
                }

                if (serpUrl) { serpUrl.textContent = buildUrl(); }

                updateMeter('title', titleVal);
                updateMeter('des', desVal);
            }

            /** Từ khóa -> chip, bỏ khoảng trắng thừa và mục rỗng. */
            function renderTags() {
                if (!tagBox) { return; }

                var kwInput = tagBox.closest('.seo-field').querySelector('textarea');
                var raw = kwInput ? kwInput.value : '';

                tagBox.textContent = '';

                raw.split(',').forEach(function (word) {
                    var w = word.trim();
                    if (w === '') { return; }

                    var chip = document.createElement('span');
                    chip.className = 'seo-tag';
                    chip.textContent = w;      // textContent -> an toàn với XSS
                    tagBox.appendChild(chip);
                });
            }

            // Gõ tới đâu, xem trước cập nhật tới đó
            [titleInput, desInput, nameInput, slugInput, aliasInput].forEach(function (input) {
                if (input) { input.addEventListener('input', render); }
            });

            var kwArea = tagBox ? tagBox.closest('.seo-field').querySelector('textarea') : null;
            if (kwArea) { kwArea.addEventListener('input', renderTags); }

            // Đổi khung xem trước máy tính / điện thoại
            panel.querySelectorAll('[data-seo-device]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var mobile = btn.dataset.seoDevice === 'mobile';

                    if (preview) { preview.classList.toggle('is-mobile', mobile); }

                    panel.querySelectorAll('[data-seo-device]').forEach(function (b) {
                        var on = b === btn;
                        b.classList.toggle('active', on);
                        b.setAttribute('aria-pressed', on ? 'true' : 'false');
                    });
                });
            });

            render();
            renderTags();
        });
    }

    /* ----------------------------------------------------------------------
     * 10. Kéo thả sắp xếp thứ tự (SortableJS)
     *
     *      <tbody data-sortable data-table="#_category">
     *
     * Chỉ cho kéo trong cùng 1 cấp & cùng cha, vì đổi cha sẽ làm sai cây.
     * Sau khi thả -> gửi thứ tự mới cho từng dòng.
     * -------------------------------------------------------------------- */
    function initSortable() {
        if (typeof window.Sortable === 'undefined') { return; }

        document.querySelectorAll('[data-sortable]').forEach(function (tbody) {
            var table = tbody.dataset.table || '';

            window.Sortable.create(tbody, {
                handle: '.drag-handle',
                draggable: 'tr[data-id]',
                animation: 150,
                ghostClass: 'row-dragging',

                // Chỉ xếp trong cùng data-parent (cùng nhóm)
                onMove: function (evt) {
                    var dragged = evt.dragged;
                    var related = evt.related;
                    if (!related || !related.getAttribute) { return true; }
                    if (!related.getAttribute('data-id')) { return false; }
                    return dragged.getAttribute('data-parent') === related.getAttribute('data-parent');
                },

                onEnd: function () {
                    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-id]'));
                    var byParent = {};
                    var updates = [];

                    rows.forEach(function (tr) {
                        var p = tr.getAttribute('data-parent') || '';
                        if (!byParent[p]) { byParent[p] = []; }
                        byParent[p].push(tr);
                    });

                    Object.keys(byParent).forEach(function (p) {
                        byParent[p].forEach(function (tr, index) {
                            var input = tr.querySelector('.a_stt');
                            var order = index + 1;
                            if (input && String(input.value) !== String(order)) {
                                input.value = order;
                                updates.push({ id: tr.getAttribute('data-id'), stt: order });
                            }
                        });
                    });

                    if (!updates.length) { return; }

                    Promise.all(updates.map(function (u) {
                        return AdminUI.http.post('./sources/ajax.php', {
                            do: 'update_stt',
                            table: table,
                            col: 'so_thu_tu',
                            id: u.id,
                            val: u.stt
                        });
                    })).then(function () {
                        AdminUI.notify.success('Đã lưu thứ tự mới');
                    }).catch(function () {
                        AdminUI.notify.error('Lưu thứ tự thất bại, vui lòng tải lại trang');
                    });
                }
            });
        });
    }

    /* ----------------------------------------------------------------------
     * 11. Xử lý image picker (hiển thị nút xóa khi có ảnh)
     * -------------------------------------------------------------------- */
    function initImagePickerWatch() {
        // Cung cấp API công khai để các thành phần bên ngoài (filemanager) gọi khi cập nhật ảnh
        if (typeof window.AdminUI === 'undefined') {
            window.AdminUI = {};
        }

        window.AdminUI.updateImagePicker = function(inputId, imageUrl) {
            var input = document.getElementById(inputId);
            if (!input) return;

            var url = String(imageUrl || '').trim();
            var img = document.getElementById('review_' + inputId) || document.getElementById('img_' + inputId);
            if (img) {
                // Cập nhật src với đường dẫn ảnh đầy đủ
                if (url.indexOf('../') === 0 || url.indexOf('./') === 0) {
                    img.src = url.replace(/^(\.\.\/|\.\/)+/, '');
                } else if (!/^https?:\/\//i.test(url) && url.indexOf('/') !== 0 && url.indexOf('img/') !== 0) {
                    img.src = '../media/images/' + url; // Đường dẫn tương đối
                } else {
                    img.src = url;
                }
            }

            // Hiển thị nút xóa
            var picker = input.closest ? input.closest('.form-file-picker') : null;
            var deleteBtn = picker ? picker.querySelector('.js-file-picker-delete') : null;
            if (deleteBtn) {
                deleteBtn.style.display = '';
                deleteBtn.classList.remove('d-none');
            }
        };
    }

    /* ----------------------------------------------------------------------
     * 12. Sắp xếp gallery ảnh (SortableJS)
     * -------------------------------------------------------------------- */
    function initGallerySortable() {
        if (typeof window.Sortable === 'undefined') { return; }
        document.querySelectorAll('[data-gallery-sortable]').forEach(function (container) {
            if (container.dataset.gallerySortableBound) { return; }
            container.dataset.gallerySortableBound = '1';
            window.Sortable.create(container, {
                draggable: '.col-6',
                animation: 150,
                ghostClass: 'gallery-item-dragging'
            });
        });
    }

    /* ----------------------------------------------------------------------
     * Tiện ích
     * -------------------------------------------------------------------- */

    /**
     * Khởi tạo DataTables cho bảng có [data-datatable].
     *
     * Tuỳ chọn đọc từ data-attribute:
     *   data-page-length="25"
     *   data-order-col="3" data-order-dir="desc"
     *   data-no-sort="0,5"   (chỉ số cột không cho sắp xếp)
     *
     * Module phải tự nạp DataTables; nếu chưa có thì bỏ qua, bảng vẫn hiển thị.
     */
    function initDataTables() {
        var tables = document.querySelectorAll('[data-datatable]');
        if (!tables.length || !window.jQuery || !window.jQuery.fn.DataTable) { return; }

        var $ = window.jQuery;

        tables.forEach(function (table) {
            if ($.fn.DataTable.isDataTable(table)) { return; }

            var noSort = (table.dataset.noSort || '')
                .split(',')
                .filter(function (v) { return v !== ''; })
                .map(Number);

            var options = {
                autoWidth: false,
                pageLength: parseInt(table.dataset.pageLength, 10) || 25,
                columnDefs: noSort.length ? [{ orderable: false, targets: noSort }] : [],
                language: {
                    search: 'Tìm:',
                    lengthMenu: 'Hiện _MENU_ dòng',
                    info: 'Hiển thị _START_-_END_ / _TOTAL_',
                    infoEmpty: 'Không có dữ liệu',
                    infoFiltered: '(lọc từ _MAX_ dòng)',
                    zeroRecords: 'Không tìm thấy kết quả',
                    paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
                }
            };

            if (table.dataset.orderCol !== undefined) {
                options.order = [[parseInt(table.dataset.orderCol, 10), table.dataset.orderDir || 'asc']];
            }

            $(table).DataTable(options);
        });
    }

    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(null, args); }, wait);
        };
    }

    /**
     * File picker actions (xoa-img, clear-image, remove-gallery-item).
     * Thay thế onclick inline trong component form/image_picker, file_picker, image_gallery.
     */
    function initFilePickerActions() {
        document.addEventListener('click', function (ev) {
            var btn = ev.target.closest && ev.target.closest('[data-action]');
            if (!btn) { return; }
            var action = btn.getAttribute('data-action');

            if (action === 'xoa-img') {
                ev.preventDefault();
                var bang = btn.getAttribute('data-bang') || '';
                var cot = btn.getAttribute('data-cot') || '';
                var idCode = btn.getAttribute('data-id-code') || '';
                var folder = btn.getAttribute('data-folder') || '';
                if (typeof xoa_img === 'function') {
                    xoa_img(bang, cot, idCode, folder);
                }
                return;
            }

            if (action === 'clear-image') {
                ev.preventDefault();
                var targetId = btn.getAttribute('data-target') || '';
                if (!targetId) { return; }
                var input = document.getElementById(targetId);
                var img = document.getElementById('review_' + targetId);
                if (input) { input.value = ''; }
                if (img) { img.src = img.getAttribute('data-fallback') || 'img/no-image.png'; }
                btn.style.display = '';
                btn.classList.add('d-none');
                return;
            }

            if (action === 'remove-gallery-item') {
                ev.preventDefault();
                var targetId = btn.getAttribute('data-target') || '';
                if (!targetId) { return; }
                var item = btn.closest('[id^="gallery_item_"]') || btn.closest('.col-6');
                if (item) {
                    item.remove();
                } else {
                    var el = document.getElementById(targetId);
                    if (el) { el.remove(); }
                }
                return;
            }
        });
    }

    /**
     * YouTube input: parse mã từ URL, cập nhật iframe preview.
     */
    function initYoutubeInputs() {
        function extractCode(raw) {
            raw = String(raw || '').trim();
            if (!raw) { return ''; }
            var m = raw.match(/(?:v=|youtu\.be\/|embed\/)([A-Za-z0-9_-]{6,})/);
            if (m) { return m[1]; }
            if (/^[A-Za-z0-9_-]{6,}$/.test(raw)) { return raw; }
            return raw;
        }

        function updatePreview(input) {
            var code = extractCode(input.value);
            if (code !== input.value) {
                input.value = code;
            }
            var pid = input.getAttribute('data-yt-preview');
            if (!pid) { return; }
            var frame = document.getElementById(pid);
            if (!frame) { return; }
            if (code) {
                frame.src = 'https://www.youtube.com/embed/' + encodeURIComponent(code);
                frame.style.display = '';
            } else {
                frame.removeAttribute('src');
                frame.style.display = 'none';
            }
        }

        document.addEventListener('change', function (ev) {
            var t = ev.target;
            if (t && t.matches && t.matches('[data-yt-preview]')) {
                updatePreview(t);
            }
        });
        document.addEventListener('input', debounce(function (ev) {
            var t = ev.target;
            if (t && t.matches && t.matches('[data-yt-preview]')) {
                updatePreview(t);
            }
        }, 400));
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSelectAll();
        initTree();
        initQuickFilter();
        initCharCounters();
        initUnsavedGuard();
        initConfirmDelete();
        initBulkAction();
        initToggles();
        initSeoPanel();
        initImagePickerWatch();
        initSortable();
        initGallerySortable();
        initDataTables();
        updateBulkBar();
        initYoutubeInputs();
        initFilePickerActions();
    });
})();