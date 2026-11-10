/**
 * File Manager adapter - CKFinder only.
 */
(function (window, document) {
    'use strict';

    var cfg = window.ADMIN_FM_CONFIG || {
        driver: 'ckfinder',
        ckfinderInstalled: true,
        ckfinderScript: 'ckfinder/ckfinder.js',
        mediaBase: { image: '../media/images/', file: '../media/files/', video: '../media/videos/' }
    };

    function normalizePath(p) {
        p = String(p || '').trim();
        if (!p) return '';
        if (/^https?:\/\//i.test(p)) return p;
        p = p.replace(/^[\.\\/]*media\/(images|files|videos)\//i, '');
        p = p.replace(/^\//, '');
        return p;
    }

    function mediaUrl(path, preview) {
        path = normalizePath(path);
        if (!path) return '';
        if (/^https?:\/\//i.test(path)) return path;
        var base = (cfg.mediaBase && cfg.mediaBase[preview]) || cfg.mediaBase.image || '../media/images/';
        return base + path;
    }

    function getField(fieldId) {
        if (!fieldId) return null;
        if (window.jQuery) {
            var $f = window.jQuery('#' + fieldId);
            if ($f.length) return $f[0];
        }
        return document.getElementById(fieldId);
    }

    function applyToField(fieldId, paths) {
        var field = getField(fieldId);
        if (!field) return;

        var list = Array.isArray(paths) ? paths : [paths];
        list = list.map(normalizePath).filter(Boolean);
        if (!list.length) return;

        var preview = (field.getAttribute('data-preview') || 'image').toLowerCase();
        var multiple = field.getAttribute('data-fm-multiple') === '1'
            || field.getAttribute('data_view')
            || (fieldId.indexOf('album') === 0);

        var dataView = field.getAttribute('data_view');
        var idThuoc = field.getAttribute('data_thuoctinh');
        if (dataView && window.jQuery) {
            var $ = window.jQuery;
            list.forEach(function (url) {
                $('#list_album_' + dataView).append(
                    '<div class="col-sm-3 p10">' +
                    '<div class="item-album">' +
                    '<input type="hidden" name="album_' + idThuoc + '_' + dataView + '[]" value="' + url + '" class="form-control" />' +
                    '<img src="' + mediaUrl(url, 'image') + '" />' +
                    '</div>' +
                    '<button onclick="jQuery(this).parent().remove()" class="btn btn-delete-album" type="button" style="top:0;right:10px;">' +
                    '<i class="fa fa-trash"></i></button></div>'
                );
            });
            field.value = '';
            return;
        }

        var listEl = document.getElementById('list_' + fieldId);
        if (listEl && (multiple || list.length > 1)) {
            var uniqueSuffix = Date.now();
            list.forEach(function (url, idx) {
                var inputName = field.getAttribute('data-name') || field.name || 'album';
                var itemId = 'gallery-item-' + fieldId + '-' + uniqueSuffix + '-' + idx;
                var card = document.createElement('div');
                card.className = 'col-4 col-md-3 mb-2 gallery-item';
                card.id = itemId;
                card.innerHTML =
                    '<div class="card h-100 shadow-sm" data-path="' + escapeHtml(url) + '">' +
                    '<img class="card-img-top bg-light" src="' + mediaUrl(url, preview) + '" style="height:120px;object-fit:contain;">' +
                    '<div class="card-body p-2 text-center">' +
                    '<input type="hidden" name="' + inputName + '[]" value="' + escapeHtml(url) + '">' +
                    '<div class="d-flex gap-1">' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary js-copy-file-url flex-grow-1" data-copy-val="' + mediaUrl(url, preview) + '" title="Sao chép đường dẫn"><i class="fa-solid fa-copy me-1"></i>Copy</button>' +
                    '<button type="button" class="btn btn-sm btn-danger btn-remove-gallery flex-grow-1" data-action="remove-gallery-item" data-target="' + itemId + '">' +
                    '<i class="fa-solid fa-trash me-1"></i>Xoá</button>' +
                    '</div></div></div>';
                listEl.appendChild(card);
            });
            var emptyEl = document.getElementById('empty_' + fieldId);
            if (emptyEl) {
                emptyEl.style.display = 'none';
            }
            field.value = '';
            return;
        }

        var singleImage = document.getElementById('review_' + fieldId) || document.getElementById('img_' + fieldId);
        if (singleImage) {
            singleImage.src = mediaUrl(list[0], preview);
            if (singleImage.tagName === 'VIDEO') {
                singleImage.classList.remove('d-none');
                singleImage.style.display = 'block';
                try { if (singleImage.load) singleImage.load(); } catch (e) {}
                var emptyV = document.getElementById('review_' + fieldId + '_empty');
                if (emptyV) {
                    emptyV.classList.add('d-none');
                    emptyV.style.display = 'none';
                }
            } else {
                singleImage.classList.remove('d-none');
                singleImage.style.display = 'inline-block';
            }
        }

        var fileWrap = document.getElementById('review_' + fieldId + '_wrap');
        if (fileWrap) {
            fileWrap.classList.remove('d-none');
            fileWrap.style.display = '';
            var fileLink = document.getElementById('review_' + fieldId + '_link');
            if (fileLink) {
                fileLink.href = mediaUrl(list[0], preview);
                fileLink.textContent = list[0].split('/').pop();
            }
            var emptyF = document.getElementById('review_' + fieldId + '_empty');
            if (emptyF) {
                emptyF.classList.add('d-none');
                emptyF.style.display = 'none';
            }
        }

        field.value = list[0];

        // Trigger change event
        if (window.jQuery) {
            window.jQuery(field).trigger('change');
        } else {
            try {
                var evt = document.createEvent('HTMLEvents');
                evt.initEvent('change', true, false);
                field.dispatchEvent(evt);
            } catch (e) {}
        }

        var deleteBtn = document.getElementById('del_' + fieldId);
        var copyBtn = null;
        if (field && field.closest) {
            var picker = field.closest('.form-file-picker') || field.closest('[data-file-picker]') || field.closest('.repeater-item');
            if (picker) {
                if (!deleteBtn) deleteBtn = picker.querySelector('.js-file-picker-delete');
                copyBtn = picker.querySelector('.js-copy-file-url');
            }
        }
        if (deleteBtn) {
            deleteBtn.style.display = '';
            deleteBtn.classList.remove('d-none');
        }
        if (copyBtn) {
            copyBtn.style.display = '';
            copyBtn.classList.remove('d-none');
        }

        if (window.AdminUI && typeof window.AdminUI.updateImagePicker === 'function') {
            window.AdminUI.updateImagePicker(fieldId, list[0], preview);
        }
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function loadScript(src, cb) {
        var s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.onload = function () { cb(true); };
        s.onerror = function () { cb(false); };
        document.head.appendChild(s);
    }
    function getOptimalModalDimensions(customW, customH) {
        var winW = window.innerWidth || document.documentElement.clientWidth || 1200;
        var winH = window.innerHeight || document.documentElement.clientHeight || 800;

        // Chiều rộng: 94% viewport, tối thiểu 960px (hoặc winW - 30 nếu màn hình nhỏ), tối đa 1920px
        var targetW = customW || (cfg && cfg.modalWidth) || Math.floor(winW * 0.94);
        targetW = Math.max(Math.min(960, winW - 30), Math.min(1920, targetW));
        targetW = Math.min(targetW, winW - 20);

        // Chiều cao: 90% viewport, tối thiểu 620px (hoặc winH - 60 nếu màn hình nhỏ), tối đa 1200px
        var targetH = customH || (cfg && cfg.modalHeight) || Math.floor(winH * 0.90);
        targetH = Math.max(Math.min(620, winH - 60), Math.min(1200, targetH));
        targetH = Math.min(targetH, winH - 50);

        return {
            width: Math.max(320, Math.floor(targetW)),
            height: Math.max(300, Math.floor(targetH))
        };
    }

    function showModalBackdrop() {
        var existing = document.getElementById('ckf-modal-backdrop');
        if (existing) return;
        var backdrop = document.createElement('div');
        backdrop.id = 'ckf-modal-backdrop';
        backdrop.className = 'ckf-backdrop';
        backdrop.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.55);z-index:8998;backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);transition:opacity .2s ease;opacity:0;';
        document.body.appendChild(backdrop);
        requestAnimationFrame(function () {
            backdrop.style.opacity = '1';
        });

        backdrop.addEventListener('click', function () {
            if (typeof CKFinder !== 'undefined' && CKFinder.modal) {
                CKFinder.modal('close');
            }
            hideModalBackdrop();
        });
    }

    function hideModalBackdrop() {
        var backdrop = document.getElementById('ckf-modal-backdrop');
        if (!backdrop) return;
        backdrop.style.opacity = '0';
        setTimeout(function () {
            if (backdrop && backdrop.parentNode) {
                backdrop.parentNode.removeChild(backdrop);
            }
        }, 200);
    }

    // Tự động ẩn backdrop khi CKFinder modal đóng
    if (typeof MutationObserver !== 'undefined' && document.body) {
        var observer = new MutationObserver(function () {
            var modalExists = !!document.getElementById('ckf-modal');
            var backdrop = document.getElementById('ckf-modal-backdrop');
            if (!modalExists && backdrop) {
                hideModalBackdrop();
            }
        });
        observer.observe(document.body, { childList: true });
    }

    // Đóng modal khi nhấn phím Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            var modal = document.getElementById('ckf-modal');
            if (modal && typeof CKFinder !== 'undefined' && CKFinder.modal) {
                CKFinder.modal('close');
                hideModalBackdrop();
            }
        }
    });

    // Tự động canh chỉnh kích thước khi resize màn hình
    var resizeTimer = null;
    window.addEventListener('resize', function () {
        if (resizeTimer) clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            var modal = document.getElementById('ckf-modal');
            var modalBody = document.getElementById('ckf-modal-body');
            if (!modal || !modalBody) return;
            if (typeof CKFinder !== 'undefined' && CKFinder.modal && CKFinder.modal('isMaximized')) return;

            var dims = getOptimalModalDimensions();
            var winW = window.innerWidth || document.documentElement.clientWidth || 1200;
            var winH = window.innerHeight || document.documentElement.clientHeight || 800;

            modalBody.style.width = dims.width + 'px';
            modalBody.style.height = dims.height + 'px';
            modal.style.left = Math.max(0, Math.floor((winW - dims.width - 2) / 2)) + 'px';
            modal.style.top = Math.max(0, Math.floor((winH - dims.height - 48) / 2)) + 'px';
        }, 120);
    });

    function sanitizeGlobalScopeForCkfinder() {
        if (typeof window === 'undefined') return;
        try {
            if (typeof window.module !== 'undefined' && (window.module.nodeType || window.module instanceof Element || !window.module.exports)) {
                Object.defineProperty(window, 'module', {
                    value: undefined,
                    configurable: true,
                    writable: true
                });
            }
        } catch (e) {
            try { window.module = undefined; } catch (err) {}
        }
        try {
            if (typeof window.exports !== 'undefined' && (window.exports.nodeType || window.exports instanceof Element)) {
                Object.defineProperty(window, 'exports', {
                    value: undefined,
                    configurable: true,
                    writable: true
                });
            }
        } catch (e) {
            try { window.exports = undefined; } catch (err) {}
        }
    }

    function openCkfinder(opts) {
        opts = opts || {};
        var fieldId = opts.fieldId || '';
        var resource = (opts.resource !== undefined && opts.resource !== null) ? opts.resource : '';
        if (resource === 'all' || resource === '*' || opts.type === 'all' || opts.type === '*') {
            resource = null;
        } else if (!resource && fieldId) {
            resource = 'Images';
        }
        var multiple = !!opts.multiple;
        var customW = opts.width || null;
        var customH = opts.height || null;

        sanitizeGlobalScopeForCkfinder();

        function run() {
            sanitizeGlobalScopeForCkfinder();
            var dims = getOptimalModalDimensions(customW, customH);
            showModalBackdrop();

            var modalConfig = {
                chooseFiles: !!fieldId,
                chooseMultiple: multiple,
                width: dims.width,
                height: dims.height,
                onInit: function (finder) {
                    if (window.CKFinderCopyUrl && typeof window.CKFinderCopyUrl.init === 'function') {
                        window.CKFinderCopyUrl.init(finder);
                    }
                    var onChooseFile = function (evt) {
                        if (evt.data && evt.data.files) {
                            var paths = [];
                            evt.data.files.forEach(function (file) {
                                paths.push(normalizePath(file.getUrl()));
                            });
                            if (!multiple && paths.length) paths = [paths[0]];
                            applyToField(fieldId, paths);
                        } else if (evt.data && evt.data.file) {
                            applyToField(fieldId, [normalizePath(evt.data.file.getUrl())]);
                        }
                    };
                    
                    finder.on('file:choose:file', onChooseFile);
                    finder.on('files:choose:file', onChooseFile);
                    finder.on('files:choose', onChooseFile);
                    finder.on('file:choose', onChooseFile);

                    finder.on('file:choose:resizedImage', function (evt) {
                        applyToField(fieldId, [normalizePath(evt.data.resizedUrl)]);
                    });
                }
            };
            if (resource) {
                modalConfig.resourceType = resource;
            }

            CKFinder.modal(modalConfig);
        }

        if (typeof window.CKFinder !== 'undefined') {
            run();
            return;
        }

        loadScript(cfg.ckfinderScript || 'ckfinder/ckfinder.js', function (ok) {
            if (!ok) {
                console.error('Khong the tai CKFinder.');
                alert('Khong the tai trinh chon file. Vui long kiem tra cai dat CKFinder.');
                return;
            }
            sanitizeGlobalScopeForCkfinder();
            run();
        });
    }

    function open(opts) {
        opts = opts || {};
        opts.type = opts.type || 'image';
        var resource = (opts.resource !== undefined) ? opts.resource : '';
        if (!resource && opts.type !== 'all' && opts.type !== '*') {
            resource = (opts.type === 'file' ? 'Files' : (opts.type === 'video' ? 'Videos' : (opts.type === 'asset' ? 'Assets' : 'Images')));
        }
        if (resource === 'all' || resource === '*' || opts.type === 'all' || opts.type === '*') {
            resource = null;
        }
        openCkfinder({
            fieldId: opts.fieldId,
            resource: resource,
            type: opts.type,
            multiple: !!opts.multiple,
            width: opts.width,
            height: opts.height
        });
    }

    function bind(ctx) {
        var root = ctx || document;
        var nodes = root.querySelectorAll
            ? root.querySelectorAll('a.js-fm-open, a.iframe-btn')
            : [];
        Array.prototype.forEach.call(nodes, function (a) {
            if (a.getAttribute('data-fm-bound') === '1') return;
            a.setAttribute('data-fm-bound', '1');
        });
    }

    document.addEventListener('click', function (e) {
        var a = e.target;
        if (!a) return;
        while (a && a !== document && !(a.tagName && a.tagName.toLowerCase() === 'a')) {
            a = a.parentNode;
        }
        if (!a || !a.tagName) return;
        if (!a.classList) return;
        if (!a.classList.contains('js-fm-open') && !a.classList.contains('iframe-btn')) return;

        e.preventDefault();
        e.stopPropagation();

        var fieldId = a.getAttribute('data-fm-field') || '';
        var type = a.getAttribute('data-fm-type') || 'image';
        var resource = a.getAttribute('data-fm-resource') || '';
        var multiple = a.getAttribute('data-fm-multiple') === '1';
        var customW = a.getAttribute('data-fm-width') ? parseInt(a.getAttribute('data-fm-width'), 10) : null;
        var customH = a.getAttribute('data-fm-height') ? parseInt(a.getAttribute('data-fm-height'), 10) : null;

        open({
            fieldId: fieldId,
            type: type,
            resource: resource,
            multiple: multiple,
            width: customW,
            height: customH
        });
    }, true);

    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest && e.target.closest('.btn-remove-gallery');
        if (!btn) return;
        e.preventDefault();
        var targetId = btn.getAttribute('data-target');
        var el = (targetId && document.getElementById(targetId)) || btn.closest('.gallery-item') || btn.closest('[id^="gallery_item_"]');
        if (el) el.remove();
    });

    window.AdminFM = {
        open: open,
        applyToField: applyToField,
        bind: bind,
        config: cfg
    };

    window.adminBindFilemanager = bind;

    sanitizeGlobalScopeForCkfinder();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            sanitizeGlobalScopeForCkfinder();
            bind(document);
        });
    } else {
        bind(document);
    }
})(window, document);
