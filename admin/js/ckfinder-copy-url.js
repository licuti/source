/**
 * CKFinder Copy URL Plugin / Integration
 * Hỗ trợ sao chép đường dẫn file (ảnh, video, tệp tin...) trực tiếp trong CKFinder.
 * Dùng được cho cả modal CKFinder và trang standalone ckfinder.html.
 * Toàn bộ icon sử dụng SVG vector sắc nét, chuẩn UI hiện đại.
 */
(function (global) {
    'use strict';

    var SVGS = {
        file: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>',
        image: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>',
        video: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>',
        pdf: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="13" x2="15" y2="13"></line><line x1="9" y1="17" x2="13" y2="17"></line></svg>',
        word: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
        excel: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="3" x2="9" y2="21"></line><line x1="15" y1="3" x2="15" y2="21"></line></svg>',
        zip: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#eab308" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>',
        copy: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>',
        folder: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>',
        open: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>',
        close: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
        check: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>'
    };

    var CKFinderCopyUrl = {
        init: function (finder, customDoc) {
            if (!finder) return;

            var modalIframe = (typeof document !== 'undefined') ? document.querySelector('#ckf-modal iframe') : null;
            var doc = customDoc
                || (modalIframe && modalIframe.contentDocument)
                || (finder.Backbone && finder.Backbone.$ && finder.Backbone.$('body')[0] && finder.Backbone.$('body')[0].ownerDocument)
                || document;
            var win = doc.defaultView || global;

            if (doc.getElementById('ckf-copy-actionbar')) {
                return;
            }

            // Tạo thanh floating Quick Action Bar
            var bar = doc.createElement('div');
            bar.id = 'ckf-copy-actionbar';
            bar.style.cssText = [
                'position: fixed',
                'bottom: 12px',
                'left: 50%',
                'transform: translateX(-50%) translateY(100px)',
                'background: rgba(15, 23, 42, 0.94)',
                'backdrop-filter: blur(8px)',
                '-webkit-backdrop-filter: blur(8px)',
                'color: #f8fafc',
                'padding: 8px 14px',
                'border-radius: 10px',
                'box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.12)',
                'display: flex',
                'align-items: center',
                'gap: 12px',
                'z-index: 9999999',
                'transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease',
                'opacity: 0',
                'pointer-events: none',
                'font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
                'font-size: 13px',
                'max-width: 92vw',
                'box-sizing: border-box'
            ].join(';');

            bar.innerHTML = [
                '<div style="display:flex; align-items:center; gap:8px; min-width:0; max-width:280px;">',
                '  <span id="ckf-copy-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">' + SVGS.file + '</span>',
                '  <div style="min-width:0;">',
                '    <div id="ckf-copy-name" style="font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#f1f5f9; font-size:12px;"></div>',
                '    <div id="ckf-copy-sub" style="font-size:11px; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></div>',
                '  </div>',
                '</div>',
                '<div style="height:24px; width:1px; background:rgba(255,255,255,0.15); flex-shrink:0;"></div>',
                '<div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">',
                '  <button type="button" id="ckf-btn-full" style="background:#2563eb; color:#fff; border:none; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:500; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s ease;">',
                '    ' + SVGS.copy + '<span>Sao chép URL đầy đủ</span>',
                '  </button>',
                '  <button type="button" id="ckf-btn-rel" style="background:#334155; color:#f1f5f9; border:none; padding:6px 11px; border-radius:6px; font-size:12px; font-weight:500; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s ease;">',
                '    ' + SVGS.folder + '<span>Sao chép Path</span>',
                '  </button>',
                '  <a id="ckf-btn-open" href="#" target="_blank" rel="noopener" style="background:#0f766e; color:#fff; border:none; padding:6px 10px; border-radius:6px; font-size:12px; font-weight:500; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">',
                '    ' + SVGS.open + '<span>Mở</span>',
                '  </a>',
                '</div>',
                '<button type="button" id="ckf-btn-close" style="background:transparent; border:none; color:#94a3b8; cursor:pointer; padding:4px 6px; line-height:1; margin-left:4px; display:flex; align-items:center; justify-content:center; border-radius:4px; transition:color .15s ease;" title="Đóng thanh sao chép">' + SVGS.close + '</button>'
            ].join('');

            doc.body.appendChild(bar);

            var currentFile = null;

            function fallbackCopy(text) {
                var ta = doc.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.top = '0';
                ta.style.left = '0';
                ta.style.width = '2em';
                ta.style.height = '2em';
                ta.style.padding = '0';
                ta.style.border = 'none';
                ta.style.outline = 'none';
                ta.style.boxShadow = 'none';
                ta.style.background = 'transparent';
                ta.style.opacity = '0';
                doc.body.appendChild(ta);
                ta.focus();
                ta.select();
                try {
                    doc.execCommand('copy');
                } catch (e) {
                    console.error('[CKFinderCopyUrl] fallback copy failed', e);
                }
                doc.body.removeChild(ta);
            }

            function copyText(text, btn, successLabel) {
                function flash() {
                    if (!btn) return;
                    var origHtml = btn.innerHTML;
                    btn.innerHTML = SVGS.check + '<span style="margin-left:5px;">' + successLabel + '</span>';
                    btn.style.background = '#059669';
                    btn.style.color = '#ffffff';
                    setTimeout(function () {
                        btn.innerHTML = origHtml;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 1800);
                }

                if (win.navigator && win.navigator.clipboard && win.navigator.clipboard.writeText) {
                    win.navigator.clipboard.writeText(text).then(flash).catch(function () {
                        fallbackCopy(text);
                        flash();
                    });
                } else {
                    fallbackCopy(text);
                    flash();
                }

                // Thông báo qua parent window nếu có AdminUI
                try {
                    var topWin = win.top || win.parent || win;
                    if (topWin && topWin.AdminUI && topWin.AdminUI.notify) {
                        topWin.AdminUI.notify.show('Đã sao chép: ' + text, 'success');
                    }
                } catch (e) {}
            }

            var btnFull = doc.getElementById('ckf-btn-full');
            var btnRel = doc.getElementById('ckf-btn-rel');
            var btnClose = doc.getElementById('ckf-btn-close');

            if (btnFull) {
                btnFull.addEventListener('click', function () {
                    if (!currentFile || !currentFile.url) return;
                    var fullUrl = win.location.origin + currentFile.url;
                    copyText(fullUrl, this, 'Đã chép URL!');
                });
            }

            if (btnRel) {
                btnRel.addEventListener('click', function () {
                    if (!currentFile || !currentFile.url) return;
                    var relPath = currentFile.url.replace(/^\//, '');
                    copyText(relPath, this, 'Đã chép Path!');
                });
            }

            if (btnClose) {
                btnClose.addEventListener('click', function () {
                    hideBar();
                });
            }

            function showBar(file) {
                currentFile = file;
                var iconSvg = SVGS.file;
                var ext = (file.name.split('.').pop() || '').toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].indexOf(ext) !== -1) iconSvg = SVGS.image;
                else if (['mp4', 'webm', 'mov', 'avi', 'mkv'].indexOf(ext) !== -1) iconSvg = SVGS.video;
                else if (['pdf'].indexOf(ext) !== -1) iconSvg = SVGS.pdf;
                else if (['doc', 'docx'].indexOf(ext) !== -1) iconSvg = SVGS.word;
                else if (['xls', 'xlsx', 'csv'].indexOf(ext) !== -1) iconSvg = SVGS.excel;
                else if (['zip', 'rar', '7z'].indexOf(ext) !== -1) iconSvg = SVGS.zip;

                var iconEl = doc.getElementById('ckf-copy-icon');
                var nameEl = doc.getElementById('ckf-copy-name');
                var subEl = doc.getElementById('ckf-copy-sub');
                var openEl = doc.getElementById('ckf-btn-open');

                if (iconEl) iconEl.innerHTML = iconSvg;
                if (nameEl) {
                    nameEl.textContent = file.name;
                    nameEl.title = file.name;
                }
                if (subEl) {
                    subEl.textContent = file.size ? ('Kích thước: ' + file.size) : 'Đường dẫn tệp tin';
                }
                if (openEl) {
                    openEl.href = win.location.origin + file.url;
                }

                bar.style.opacity = '1';
                bar.style.pointerEvents = 'auto';
                bar.style.transform = 'translateX(-50%) translateY(0)';

                injectToolbarButton(file);
            }

            function hideBar() {
                bar.style.opacity = '0';
                bar.style.pointerEvents = 'none';
                bar.style.transform = 'translateX(-50%) translateY(100px)';
                currentFile = null;
                removeToolbarButton();
            }

            function injectToolbarButton(file) {
                var toolbarItems = doc.querySelector('.ckf-toolbar-items');
                if (!toolbarItems) return;

                var existingBtn = doc.getElementById('ckf-custom-copy-url-btn');
                if (existingBtn) {
                    existingBtn.style.display = '';
                    return;
                }

                var btn = doc.createElement('button');
                btn.id = 'ckf-custom-copy-url-btn';
                btn.type = 'button';
                btn.className = 'ckf-toolbar-item ckf-toolbar-button ui-btn ui-btn-inline ui-mini ui-corner-all';
                btn.title = 'Sao chép đường dẫn file vào bộ nhớ tạm';
                btn.style.cssText = 'background:#2563eb !important; color:#fff !important; font-weight:600 !important; border:none; margin:0 4px; display:inline-flex; align-items:center; gap:4px;';
                btn.innerHTML = SVGS.copy + '<span>Copy Link</span>';

                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!currentFile || !currentFile.url) return;
                    var fullUrl = win.location.origin + currentFile.url;
                    copyText(fullUrl, btn, 'Đã copy!');
                });

                // Chèn sau nút Download hoặc nút View
                var refBtn = toolbarItems.querySelector('[data-ckf-name="Download"]') || toolbarItems.querySelector('[data-ckf-name="View"]');
                if (refBtn && refBtn.parentNode) {
                    refBtn.parentNode.insertBefore(btn, refBtn.nextSibling);
                } else {
                    toolbarItems.appendChild(btn);
                }
            }

            function removeToolbarButton() {
                var btn = doc.getElementById('ckf-custom-copy-url-btn');
                if (btn) {
                    btn.style.display = 'none';
                }
            }

            finder.on('files:selected', function (evt) {
                var files = evt.data && evt.data.files;
                if (files && files.length) {
                    var f = files.first ? files.first() : (files.at ? files.at(0) : files.models[0]);
                    if (f && f.getUrl) {
                        showBar({
                            name: f.get('name') || '',
                            url: f.getUrl(),
                            size: f.get('sizeFormatted') || ''
                        });
                        return;
                    }
                }
                hideBar();
            });

            finder.on('toolbar:reset:Main:empty', function () {
                hideBar();
            });

            finder.on('folder:getFiles:after', function () {
                hideBar();
            });

            // Nếu lúc init đã có file đang được chọn sẵn
            try {
                var sel = finder.request('files:getSelected');
                if (sel && sel.length) {
                    var f = sel.first ? sel.first() : (sel.at ? sel.at(0) : sel.models[0]);
                    if (f && f.getUrl) {
                        showBar({
                            name: f.get('name') || '',
                            url: f.getUrl(),
                            size: f.get('sizeFormatted') || ''
                        });
                    }
                }
            } catch (e) {}
        }
    };

    global.CKFinderCopyUrl = CKFinderCopyUrl;

    // Tự động kích hoạt nếu window.finder đã có sẵn
    if (typeof global.finder !== 'undefined') {
        CKFinderCopyUrl.init(global.finder);
    }
})(typeof window !== 'undefined' ? window : this);
