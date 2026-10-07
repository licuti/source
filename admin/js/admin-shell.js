/* admin-shell.js — Select2, CKEditor dialog (tu index.php) */
(function (window, document) {
    'use strict';

    if (typeof jQuery !== 'undefined') {
        jQuery(function ($) {
            // File manager: su dung CKFinder modal (khong con RFM / Fancybox)
            function bindFilemanagerButtons(ctx) {
                if (window.AdminFM && window.AdminFM.bind) {
                    window.AdminFM.bind(ctx || document);
                }
            }

            bindFilemanagerButtons(document);

            // Cho module goi lai sau khi inject HTML
            window.adminBindFilemanager = bindFilemanagerButtons;

            if ($.fn.select2MultiCheckboxes) {
                $('.select2-multiple').select2MultiCheckboxes({
                    placeholder: 'Choose your field',
                    width: 'auto'
                });
                $('.select2-multiple2').select2MultiCheckboxes({
                    templateSelection: function (selected, total) {
                        return 'Selected ' + selected.length + ' of ' + total + ' your field';
                    }
                });
            }
            if ($.fn.select2) {
                $('.select2').select2({ width: '100%' });
            }
        });
    }

    if (typeof CKEDITOR !== 'undefined') {
        CKEDITOR.on('dialogDefinition', function (ev) {
            var dialogName = ev.data.name;
            var dialogDefinition = ev.data.definition;
            if (dialogName === 'image' || dialogName === 'link') {
                dialogDefinition.removeContents('Upload');
                dialogDefinition.removeContents('upload');
            }
        });
    }
})(window, document);