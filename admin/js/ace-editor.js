/**
 * Ace Editor helper (CDN).
 * Usage:
 *   <textarea id="code" data-ace-editor data-ace-mode="html" data-ace-height="200"></textarea>
 *   // ace-editor.js auto-inits on DOMContentLoaded
 *
 * Or: AdminAce.bind('#code', { mode: 'xml', height: 400 })
 */
(function (window, document) {
    'use strict';

    var ACE_VERSION = '1.36.5';
    var ACE_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/ace/' + ACE_VERSION + '/';
    var loadPromise = null;
    var instances = [];

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onload = function () { resolve(); };
            s.onerror = function () { reject(new Error('Failed to load ' + src)); };
            document.head.appendChild(s);
        });
    }

    function ensureAce() {
        if (window.ace) {
            return Promise.resolve(window.ace);
        }
        if (loadPromise) {
            return loadPromise;
        }
        loadPromise = loadScript(ACE_CDN + 'ace.min.js').then(function () {
            if (!window.ace) {
                throw new Error('Ace failed to initialize');
            }
            window.ace.config.set('basePath', ACE_CDN);
            window.ace.config.set('modePath', ACE_CDN);
            window.ace.config.set('themePath', ACE_CDN);
            window.ace.config.set('workerPath', ACE_CDN);
            return window.ace;
        });
        return loadPromise;
    }

    function detectTheme() {
        var root = document.documentElement;
        var dark =
            root.getAttribute('data-bs-theme') === 'dark' ||
            root.classList.contains('dark-mode') ||
            (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches &&
                root.getAttribute('data-bs-theme') !== 'light');
        return dark ? 'ace/theme/tomorrow_night' : 'ace/theme/chrome';
    }

    function wrapTextarea(ta, options) {
        options = options || {};
        var mode = options.mode || ta.getAttribute('data-ace-mode') || 'text';
        var height = options.height || ta.getAttribute('data-ace-height') || '220';
        var readOnly = options.readOnly === true || ta.hasAttribute('readonly') || ta.disabled;
        var theme = options.theme || detectTheme();

        if (String(height).match(/^\d+$/)) {
            height = height + 'px';
        }

        // Hide original textarea (still submitted with form)
        ta.classList.add('d-none');
        ta.setAttribute('aria-hidden', 'true');

        var host = document.createElement('div');
        host.className = 'ace-editor-host border rounded overflow-hidden';
        host.style.height = height;
        host.style.width = '100%';
        ta.parentNode.insertBefore(host, ta.nextSibling);

        var editor = window.ace.edit(host);
        editor.setTheme(theme);
        editor.session.setMode('ace/mode/' + mode.replace(/^ace\/mode\//, ''));
        editor.setShowPrintMargin(false);
        editor.setHighlightActiveLine(true);
        editor.session.setUseWrapMode(true);
        editor.session.setTabSize(2);
        editor.setFontSize(13);
        editor.renderer.setScrollMargin(6, 6);
        editor.setValue(ta.value || '', -1);
        editor.setReadOnly(readOnly);
        editor.session.setUseWorker(false); // avoid worker noise on small snippets

        // Keep textarea in sync (input events + before submit)
        function sync() {
            ta.value = editor.getValue();
            try {
                ta.dispatchEvent(new Event('input', { bubbles: true }));
            } catch (e) { /* IE noop */ }
        }
        editor.session.on('change', sync);

        var form = ta.form || ta.closest('form');
        if (form && !form._aceSubmitBound) {
            form.addEventListener('submit', function () {
                instances.forEach(function (item) {
                    if (item.form === form) {
                        item.ta.value = item.editor.getValue();
                    }
                });
            });
            form._aceSubmitBound = true;
        }

        var item = { ta: ta, editor: editor, host: host, form: form };
        instances.push(item);
        ta._aceEditor = editor;
        return editor;
    }

    function bind(selectorOrEl, options) {
        return ensureAce().then(function () {
            var els;
            if (typeof selectorOrEl === 'string') {
                els = Array.prototype.slice.call(document.querySelectorAll(selectorOrEl));
            } else if (selectorOrEl && selectorOrEl.nodeType) {
                els = [selectorOrEl];
            } else {
                els = [];
            }
            return els.map(function (el) {
                if (el._aceEditor) {
                    return el._aceEditor;
                }
                return wrapTextarea(el, options || {});
            });
        });
    }

    function autoInit() {
        var nodes = document.querySelectorAll('textarea[data-ace-editor]');
        if (!nodes.length) {
            return;
        }
        ensureAce().then(function () {
            Array.prototype.forEach.call(nodes, function (ta) {
                if (!ta._aceEditor) {
                    wrapTextarea(ta, {});
                }
            });
        }).catch(function (err) {
            if (window.console && console.warn) {
                console.warn('[AdminAce]', err);
            }
        });
    }

    window.AdminAce = {
        bind: bind,
        ensureAce: ensureAce,
        autoInit: autoInit,
        instances: instances
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }
})(window, document);
