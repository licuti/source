(function () {
    function addCombo(editor, lang, entries, styleDefinition) {
        var config = editor.config;
        var style = new CKEDITOR.style(styleDefinition);
        var styles = {};
        var names = entries.split(';');
        for (var i = 0; i < names.length; i++) {
            var name = names[i];
            styles[name] = new CKEDITOR.style(styleDefinition, {'weight': name});
            styles[name]._.definition.name = name;
        }
        editor.ui.addRichCombo('fontweight', {
            label: lang.title,
            title: lang.title,
            toolbar: 'styles',
            allowedContent: style,
            requiredContent: style,
            panel: {
                css: [
                    CKEDITOR.skin.getPath('editor')
                ].concat(config.contentsCss),
                multiSelect: false,
                attributes: {
                    'aria-label': 'fontweight'
                }
            },
            init: function () {
                this.startGroup('fontweight')
                for (var i = 0; i < names.length; i++) {
                    var name = names[i];
                    this.add(name, styles[name].buildPreview(), name)
                }
            },
            onClick: function (value) {
                editor.focus();
                editor.fire('saveSnapshot');
                var style = styles[ value ];
                editor[ this.getValue() == value ? 'removeStyle' : 'applyStyle' ]( style );
                editor.fire('saveSnapshot');
            },
            onRender: function () {
                editor.on('selectionChange', function (event) {
                    var value = this.getValue();
                    var elementPath = event.data.path;
                    var elements = elementPath.elements;
                    for (var i = 0; i < elements.length; i++) {
                        var element = elements[i];
                        for (var v in styles) {
                            if (styles[v].checkElementMatch(element, true, editor)) {
                                if (v != value) {
                                    this.setValue(v);
                                }
                                return;
                            }
                        }
                    }
                    this.setValue('', lang.title);
                }, this);
            },
            refresh: function () {
                if (!editor.activeFilter.check(style)) {
                    this.setState(CKEDITOR.TRISTATE_DISABLED);
                }
            }
        })
    }

    CKEDITOR.plugins.add('fontweight', {
        requires: 'richcombo',
        lang: 'en',
        init: function (editor) {
            var config = editor.config;
            var lang = editor.lang.fontweight;
            addCombo(editor, lang, config.fontWeight, config.fontWeight_style);
        }
    })
})();

CKEDITOR.config.fontWeight = '100;200;300;400;500;600;700;800;900'
CKEDITOR.config.fontWeight_style = {
    element: 'span',
    styles: {
        'font-weight': '#(weight)'
    },
    overrides: [{
        element: 'font',
        attributes: {
            'weight': null
        }
    }]
}

