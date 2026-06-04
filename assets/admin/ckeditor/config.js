/**
 * @license Copyright (c) 2003-2022, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function(config) {
    // Define changes to default configuration here. For example:
    // config.language = 'fr';
    // config.uiColor = '#AADC6E';
    //config.extraPlugins = 'codesnippet';
    config.height = '20em';

    config.extraPlugins = 'btgrid, youtube, ckeditorfa,';

    

    config.toolbar_Basic = [{
            name: 'basicstyles',
            items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']
        },
        {
            name: 'paragraph',
            items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv',
                '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl'
            ]
        },
        { name: 'colors', items: ['TextColor', 'BGColor'] },
        { name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize', 'lineheight'] },

    ];

    config.allowedContent = true;

    // config.contentsCss = 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css';
    // config.mj_variables_bootstrap_css_path = 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css';
    config.bootstrapGrid_container_extra_large = 1140;
    config.bootstrapGrid_container_large = 960;
    config.bootstrapGrid_container_medium = 720;
    config.bootstrapGrid_container_small = 540;
    config.bootstrapGrid_grid_columns = 12;


    config.contentsCss = [
        'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css',
        'https://use.fontawesome.com/releases/v5.13.0/css/all.css',
        '/admin/ckeditor/contents.css'
    ];
};