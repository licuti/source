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

    // Tương thích jQuery 4.0+ (các hàm bị deprecated/removed)
    if (!$.isArray) $.isArray = Array.isArray;
    if (!$.isFunction) $.isFunction = function (obj) { return typeof obj === 'function'; };
    if (!$.isNumeric) $.isNumeric = function (obj) { return !isNaN(parseFloat(obj)) && isFinite(obj); };
    if (!$.trim) $.trim = function (str) { return str == null ? '' : String(str).trim(); };
    if (!$.type) $.type = function (obj) { return Object.prototype.toString.call(obj).replace(/^\[object (.+)\]$/, '$1').toLowerCase(); };

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

    // ===================== Select2 (Bootstrap 5 theme) =====================

    LMS.initSelect2 = function (context) {
        if (!$.fn.select2) {
            return;
        }
        var $ctx = context ? $(context) : $(document);
        $ctx.find('select.select2, select.select2-multiple, select[multiple].lms-select2, select[data-select2]').each(function () {
            var $this = $(this);
            if ($this.data('select2')) {
                return;
            }
            if ($this.is(':disabled') && $this.closest('[style*="display:none"], [style*="display: none"]').length) {
                return;
            }
            var placeholder = $this.data('placeholder') || $this.attr('placeholder') || '— Chọn —';
            var isMultiple = $this.prop('multiple');
            $this.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: placeholder,
                allowClear: !isMultiple && !$this.prop('required'),
                closeOnSelect: !isMultiple
            });
        });
    };

    // ===================== Accordion arrows =====================

    /**
     * Xoay chevron của các header collapse dùng class LMS
     * (.lms-section-header, .lms-category-header).
     */
    LMS.initCollapseArrows = function () {
        var toggles = document.querySelectorAll('[data-bs-toggle="collapse"][data-bs-target]');

        Array.prototype.forEach.call(toggles, function (toggle) {
            if (toggle.getAttribute('data-collapse-arrow-inited') === '1') {
                return;
            }
            toggle.setAttribute('data-collapse-arrow-inited', '1');

            var target = document.querySelector(toggle.getAttribute('data-bs-target'));
            var arrow = toggle.querySelector('.lms-toggle-arrow, .lms-cat-arrow');
            if (!target || !arrow) {
                return;
            }

            target.addEventListener('show.bs.collapse', function () {
                arrow.classList.remove('fa-chevron-down');
                arrow.classList.add('fa-chevron-up');
            });
            target.addEventListener('hide.bs.collapse', function () {
                arrow.classList.remove('fa-chevron-up');
                arrow.classList.add('fa-chevron-down');
            });
        });
    };

    // ===================== Upload nhiều tệp =====================

    /**
     * Khởi tạo các vùng [data-lms-upload]:
     * chọn/kéo thả nhiều tệp, giới hạn số lượng + dung lượng, xoá tệp trước khi nộp.
     */
    LMS.initFileUploads = function () {
        var fields = document.querySelectorAll('[data-lms-upload]');

        Array.prototype.forEach.call(fields, function (field) {
            if (field.getAttribute('data-upload-inited') === '1') {
                return;
            }
            field.setAttribute('data-upload-inited', '1');

            var input = field.querySelector('[data-upload-input]');
            if (!input) {
                return;
            }

            var zone = field.querySelector('[data-upload-zone]');
            var trigger = field.querySelector('[data-upload-trigger]');
            var list = field.querySelector('[data-upload-list]');
            var addBtn = field.querySelector('[data-upload-add]');
            var errorBox = field.querySelector('[data-upload-error]');
            var maxFiles = parseInt(field.getAttribute('data-max-files'), 10) || 5;
            var maxSize = parseInt(field.getAttribute('data-max-size'), 10) || 0;
            var required = field.getAttribute('data-upload-required') === '1';
            var accept = (input.getAttribute('accept') || '').toLowerCase();
            var acceptList = accept.split(',').map(function (item) {
                return item.trim();
            }).filter(function (item) {
                return item !== '';
            });
            var selected = [];
            var transfer = typeof DataTransfer !== 'undefined' ? new DataTransfer() : null;

            var showError = function (message) {
                if (!errorBox) {
                    return;
                }
                errorBox.textContent = message;
                errorBox.hidden = false;
            };

            var clearError = function () {
                if (errorBox) {
                    errorBox.hidden = true;
                    errorBox.textContent = '';
                }
            };

            var fileExt = function (name) {
                var parts = String(name || '').toLowerCase().split('.');
                return parts.length > 1 ? parts.pop() : '';
            };

            var formatSize = function (bytes) {
                if (bytes >= 1048576) {
                    return (bytes / 1048576).toFixed(1) + ' MB';
                }
                if (bytes >= 1024) {
                    return Math.round(bytes / 1024) + ' KB';
                }
                return bytes + ' B';
            };

            var fileIcon = function (name) {
                var ext = fileExt(name);
                if (ext === 'pdf') {
                    return 'fa-solid fa-file-pdf text-danger';
                }
                if (ext === 'doc' || ext === 'docx') {
                    return 'fa-solid fa-file-word text-primary';
                }
                if (ext === 'xls' || ext === 'xlsx') {
                    return 'fa-solid fa-file-excel text-success';
                }
                if (ext === 'ppt' || ext === 'pptx') {
                    return 'fa-solid fa-file-powerpoint text-warning';
                }
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].indexOf(ext) !== -1) {
                    return 'fa-solid fa-file-image text-info';
                }
                if (ext === 'zip') {
                    return 'fa-solid fa-file-zipper text-secondary';
                }
                return 'fa-solid fa-file text-secondary';
            };

            var syncInput = function () {
                if (!transfer) {
                    return;
                }
                transfer.items.clear();
                selected.forEach(function (file) {
                    transfer.items.add(file);
                });
                input.files = transfer.files;
            };

            var render = function () {
                if (!list) {
                    return;
                }
                list.innerHTML = '';

                selected.forEach(function (file, index) {
                    var item = document.createElement('li');
                    item.className = 'lms-upload-selected-item';

                    var icon = document.createElement('i');
                    icon.className = fileIcon(file.name) + ' lms-upload-selected-icon';

                    var name = document.createElement('span');
                    name.className = 'lms-upload-selected-name';
                    name.textContent = file.name;
                    name.title = file.name;

                    var size = document.createElement('span');
                    size.className = 'lms-upload-selected-size';
                    size.textContent = formatSize(file.size);

                    var remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'lms-upload-selected-remove';
                    remove.setAttribute('aria-label', 'Xoá tệp ' + file.name);
                    remove.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                    remove.addEventListener('click', function () {
                        selected.splice(index, 1);
                        syncInput();
                        render();
                        clearError();
                    });

                    item.appendChild(icon);
                    item.appendChild(name);
                    item.appendChild(size);
                    item.appendChild(remove);
                    list.appendChild(item);
                });

                list.hidden = selected.length === 0;
                if (trigger) {
                    trigger.hidden = selected.length > 0;
                }
                if (addBtn) {
                    addBtn.hidden = selected.length === 0 || selected.length >= maxFiles;
                }
            };

            var addFiles = function (fileList) {
                var incoming = Array.prototype.slice.call(fileList || []);
                var hasError = false;

                incoming.forEach(function (file) {
                    if (selected.length >= maxFiles) {
                        showError('Chỉ được chọn tối đa ' + maxFiles + ' tệp.');
                        hasError = true;
                        return;
                    }

                    var ext = fileExt(file.name);
                    if (acceptList.length > 0 && acceptList.indexOf('.' + ext) === -1) {
                        showError('Tệp "' + file.name + '" không đúng định dạng cho phép.');
                        hasError = true;
                        return;
                    }

                    if (maxSize > 0 && file.size > maxSize) {
                        showError('Tệp "' + file.name + '" vượt quá ' + Math.round(maxSize / 1048576) + 'MB.');
                        hasError = true;
                        return;
                    }

                    var duplicated = selected.some(function (current) {
                        return current.name === file.name
                            && current.size === file.size
                            && current.lastModified === file.lastModified;
                    });
                    if (!duplicated) {
                        selected.push(file);
                    }
                });

                if (!hasError) {
                    clearError();
                }
                syncInput();
                render();
            };

            var openPicker = function () {
                input.click();
            };

            if (trigger) {
                trigger.addEventListener('click', openPicker);
            }
            if (addBtn) {
                addBtn.addEventListener('click', openPicker);
            }
            input.addEventListener('change', function () {
                addFiles(input.files);
            });

            if (zone) {
                zone.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    zone.classList.add('is-dragover');
                });
                zone.addEventListener('dragleave', function () {
                    zone.classList.remove('is-dragover');
                });
                zone.addEventListener('drop', function (event) {
                    event.preventDefault();
                    zone.classList.remove('is-dragover');
                    addFiles(event.dataTransfer ? event.dataTransfer.files : null);
                });
            }

            if (required) {
                var form = field.closest('form');
                if (form) {
                    form.addEventListener('submit', function (event) {
                        if (selected.length === 0) {
                            event.preventDefault();
                            showError('Vui lòng chọn ít nhất 1 tệp.');
                            if (trigger && !trigger.hidden) {
                                trigger.focus();
                            }
                        }
                    });
                }
            }
        });
    };

    // ===================== Ghim cột bảng (data-pin-left / data-pin-right) =====================

    /**
     * Bảng mang class .lms-table-pin: mọi ô có data-pin-left / data-pin-right được sticky.
     * JS chỉ lo TOẠ ĐỘ: đọc bề rộng của ô tiêu đề để tính offset cộng dồn, ghi vào
     * style.left / style.right của từng ô cùng cột. CSS (lms.css) lo phần position + viền.
     *
     * Nhờ vậy không cần hard-code bề rộng cột, thêm/bớt cột ghim chỉ sửa attribute trong
     * template, và cột ghim phải không bắt buộc là cột cuối bảng.
     *
     * Muốn cột ghim có bề rộng cố định: khai báo min-width cho cột đó (inline trong <th>
     * hoặc rule CSS). JS đọc cả min-width lẫn bề rộng thật và lấy giá trị lớn hơn, nên số
     * khai báo luôn được tôn trọng. JS KHÔNG ghi width/min-width — ghi thuộc tính layout
     * sau first paint sẽ làm layout giật một nhịp.
     * (Đo trong table-layout:auto: min-width ép được cột, còn width/max-width bị bỏ qua.)
     */
    LMS.initPinnedColumns = function () {
        var tables = document.querySelectorAll('.lms-table-pin');
        if (!tables.length) {
            return;
        }

        /**
         * Bề rộng dùng để cộng dồn offset = lớn hơn giữa bề rộng thật và min-width khai báo.
         * min-width được đưa vào để ô chưa kịp layout (offsetWidth = 0 / nhỏ hơn sàn)
         * vẫn cho ra offset đúng như thiết kế.
         */
        function columnWidth(cell) {
            var real = cell.offsetWidth;
            var declared = parseFloat(window.getComputedStyle(cell).minWidth);
            if (isNaN(declared)) {
                declared = 0;
            }
            return Math.max(real, declared);
        }

        function applyToTable(table) {
            var headRows = table.tHead ? table.tHead.rows : [];
            if (!headRows.length) {
                return;
            }
            var headCells = headRows[0].cells;
            var lefts = [];
            var rights = [];
            var i;
            var acc = 0;

            // Offset trái -> phải
            for (i = 0; i < headCells.length; i++) {
                if (headCells[i].hasAttribute('data-pin-left')) {
                    var wLeft = columnWidth(headCells[i]);
                    if (!wLeft) {
                        return; // bảng đang ẩn (tab chưa mở) -> tính lại ở lượt sau
                    }
                    lefts[i] = acc;
                    acc += wLeft;
                }
            }

            // Offset phải -> trái
            acc = 0;
            for (i = headCells.length - 1; i >= 0; i--) {
                if (headCells[i].hasAttribute('data-pin-right')) {
                    rights[i] = acc;
                    acc += columnWidth(headCells[i]);
                }
            }

            // Cụm ghim ăn hết bề rộng khả dụng (màn hẹp) -> bỏ ghim để bảng cuộn bình thường,
            // thay vì để 2 cột ghim chồng lên nhau và không còn phần giữa để đọc.
            var pinnedTotal = 0;
            for (i = 0; i < headCells.length; i++) {
                if (typeof lefts[i] !== 'undefined' || typeof rights[i] !== 'undefined') {
                    pinnedTotal += columnWidth(headCells[i]);
                }
            }
            var available = table.parentElement ? table.parentElement.clientWidth : 0;
            var keepPinned = pinnedTotal === 0 || available - pinnedTotal >= 80;

            Array.prototype.forEach.call(table.rows, function (row) {
                Array.prototype.forEach.call(row.cells, function (cell, index) {
                    if (!keepPinned) {
                        if (cell.style.left) {
                            cell.style.left = '';
                        }
                        if (cell.style.right) {
                            cell.style.right = '';
                        }
                        cell.classList.remove('lms-pin-prev');
                        return;
                    }
                    if (typeof lefts[index] !== 'undefined' && cell.hasAttribute('data-pin-left')) {
                        cell.style.left = lefts[index] + 'px';
                    }
                    if (typeof rights[index] !== 'undefined' && cell.hasAttribute('data-pin-right')) {
                        cell.style.right = rights[index] + 'px';
                        var prev = index > 0 ? row.cells[index - 1] : null;
                        if (prev && !prev.hasAttribute('data-pin-right')) {
                            prev.classList.add('lms-pin-prev');
                        }
                    }
                });
            });
        }

        function refresh() {
            Array.prototype.forEach.call(tables, applyToTable);
        }

        refresh();

        // Bề rộng cột đổi theo font/zoom/nội dung -> tính lại khi đổi kích thước cửa sổ
        var timer = null;
        window.addEventListener('resize', function () {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(refresh, 150);
        });

        LMS.refreshPinnedColumns = refresh;
    };

    // ===================== Khởi tạo =====================

    $(function () {
        LMS.renderFlash();
        LMS.initSidebar();
        LMS.initAlertBanner();
        LMS.initDropdowns();
        LMS.initSelect2();
        LMS.initFileUploads();
        LMS.initCollapseArrows();
        LMS.initPinnedColumns();
    });
})(jQuery);
