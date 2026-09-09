(function (blocks, element, blockEditor, components) {
    var el = element.createElement;
    var Fragment = element.Fragment;
    var useState = element.useState;
    var RichText = blockEditor.RichText;
    var InspectorControls = blockEditor.InspectorControls;
    var BlockControls = blockEditor.BlockControls;
    var AlignmentToolbar = blockEditor.AlignmentToolbar;
    var useBlockProps = blockEditor.useBlockProps;
    var ToolbarGroup = components.ToolbarGroup;
    var ToolbarDropdownMenu = components.ToolbarDropdownMenu;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;
    var ColorPicker = components.ColorPicker;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;

    function getStoredFonts() {
        try { return JSON.parse(localStorage.getItem('bbl_custom_fonts') || '[]'); }
        catch (e) { return []; }
    }
    function storeFonts(fonts) { localStorage.setItem('bbl_custom_fonts', JSON.stringify(fonts)); }
    function injectFontFace(fontName, fontUrl) {
        var styleId = 'bbl-font-' + fontName.replace(/\s+/g, '-').toLowerCase();
        if (document.getElementById(styleId)) return;
        var style = document.createElement('style');
        style.id = styleId;
        style.textContent = '@font-face { font-family: "' + fontName + '"; src: url("' + fontUrl + '"); }';
        document.head.appendChild(style);
    }
    getStoredFonts().forEach(function (f) { injectFontFace(f.name, f.url); });

    function buildStyle(a) {
        return {
            fontFamily:     a.fontFamily ? '"' + a.fontFamily + '"' : undefined,
            fontSize:       a.fontSize + 'px',
            color:          a.textColor || undefined,
            backgroundColor: a.backgroundColor || undefined,
            textAlign:      a.textAlign || undefined,
            fontWeight:     a.fontWeight || 'normal',
            fontStyle:      a.italic ? 'italic' : 'normal',
            lineHeight:     a.lineHeight || undefined,
            letterSpacing:  a.letterSpacing ? a.letterSpacing + 'px' : undefined,
            textDecoration: a.textDecoration || undefined,
            textTransform:  a.textTransform || undefined,
        };
    }

    var weightOptions = [
        { label: '100 — Thin',        value: '100' },
        { label: '200 — Extra Light', value: '200' },
        { label: '300 — Light',       value: '300' },
        { label: '400 — Normal',      value: '400' },
        { label: '500 — Medium',      value: '500' },
        { label: '600 — Semi Bold',   value: '600' },
        { label: '700 — Bold',        value: '700' },
        { label: '800 — Extra Bold',  value: '800' },
        { label: '900 — Black',       value: '900' },
        { label: 'bold',              value: 'bold' },
        { label: 'bolder',            value: 'bolder' },
        { label: 'lighter',           value: 'lighter' },
        { label: 'normal',            value: 'normal' },
    ];

    blocks.registerBlockType('bebelume/custom-fonts', {

        transforms: {
            from: [
                { type: 'block', blocks: ['core/paragraph'],    transform: function(a){ return blocks.createBlock('bebelume/custom-fonts', { content: a.content || '' }); } },
                { type: 'block', blocks: ['core/heading'],      transform: function(a){ return blocks.createBlock('bebelume/custom-fonts', { content: a.content || '' }); } },
                { type: 'block', blocks: ['core/quote'],        transform: function(a){ return blocks.createBlock('bebelume/custom-fonts', { content: a.value   || '' }); } },
                { type: 'block', blocks: ['core/verse'],        transform: function(a){ return blocks.createBlock('bebelume/custom-fonts', { content: a.content || '' }); } },
                { type: 'block', blocks: ['core/pullquote'],    transform: function(a){ return blocks.createBlock('bebelume/custom-fonts', { content: a.value   || '' }); } },
                { type: 'block', blocks: ['core/preformatted'], transform: function(a){ return blocks.createBlock('bebelume/custom-fonts', { content: a.content || '' }); } }
            ],
            to: [
                { type: 'block', blocks: ['core/paragraph'],  transform: function(a){ return blocks.createBlock('core/paragraph',  { content: a.content || '' }); } },
                { type: 'block', blocks: ['core/heading'],    transform: function(a){ return blocks.createBlock('core/heading',    { content: a.content || '' }); } },
                { type: 'block', blocks: ['core/quote'],      transform: function(a){ return blocks.createBlock('core/quote',      { value:   a.content || '' }); } },
                { type: 'block', blocks: ['core/verse'],      transform: function(a){ return blocks.createBlock('core/verse',      { content: a.content || '' }); } },
                { type: 'block', blocks: ['core/pullquote'],  transform: function(a){ return blocks.createBlock('core/pullquote',  { value:   a.content || '' }); } },
                { type: 'block', blocks: ['core/list'],       transform: function(a){ return blocks.createBlock('core/list',       { values:  '<li>' + (a.content || '') + '</li>' }); } }
            ]
        },

        edit: function (props) {
            var a = props.attributes;
            var set = props.setAttributes;
            var uploadError = useState('')[0];
            var setUploadError = useState('')[1];

            var fontOptions = [{ label: '— Selecionar fonte —', value: '' }].concat(
                getStoredFonts().map(function (f) { return { label: f.name, value: f.name }; })
            );

            var blockProps = useBlockProps({ style: buildStyle(a) });

            // Label exibido no botão da toolbar
            var currentWeightLabel = (a.fontWeight || 'normal');

            function handleFontUpload(event) {
                var file = event.target.files[0];
                if (!file) return;
                var ext = file.name.split('.').pop().toLowerCase();
                if (['woff','woff2','ttf','otf'].indexOf(ext) === -1) {
                    setUploadError('Formato inválido. Use .woff, .woff2, .ttf ou .otf');
                    return;
                }
                setUploadError('');
                var fontName = file.name.replace(/\.[^.]+$/, '').replace(/[-_]/g, ' ');
                var reader = new FileReader();
                reader.onload = function (e) {
                    var dataUrl = e.target.result;
                    var current = getStoredFonts();
                    if (!current.find(function (f) { return f.name === fontName; })) {
                        current.push({ name: fontName, url: dataUrl });
                        storeFonts(current);
                    }
                    injectFontFace(fontName, dataUrl);
                    set({ fontFamily: fontName });
                    var bbl = window.bebelumeBlocks || {};
                    if (bbl.ajaxUrl && bbl.nonce) {
                        var fd = new FormData();
                        fd.append('action', 'bbl_save_font');
                        fd.append('nonce', bbl.nonce);
                        fd.append('fontName', fontName);
                        fd.append('fontUrl', dataUrl);
                        fetch(bbl.ajaxUrl, { method: 'POST', body: fd });
                    }
                    event.target.value = '';
                };
                reader.readAsDataURL(file);
            }

            return el(Fragment, null,

                el(BlockControls, null,
                    // Alinhamento
                    el(AlignmentToolbar, {
                        value: a.textAlign,
                        onChange: function (val) { set({ textAlign: val || 'left' }); }
                    }),
                    // Dropdown de font-weight na toolbar
                    el(ToolbarGroup, null,
                        el(ToolbarDropdownMenu, {
                            icon: el('span', {
                                style: { fontWeight: 'bold', fontSize: '13px', lineHeight: 1, padding: '0 2px' }
                            }, currentWeightLabel),
                            label: 'Peso da fonte',
                            controls: weightOptions.map(function (opt) {
                                return {
                                    title: opt.label,
                                    isActive: (a.fontWeight || 'normal') === opt.value,
                                    onClick: function () { set({ fontWeight: opt.value }); }
                                };
                            })
                        })
                    )
                ),

                el(InspectorControls, null,
                    el(PanelBody, { title: 'Fonte', initialOpen: true },
                        el('p', { style: { fontWeight: '600', marginBottom: '6px' } }, 'Fazer upload de webfont'),
                        el('input', { type: 'file', accept: '.woff,.woff2,.ttf,.otf', onChange: handleFontUpload, style: { width: '100%', marginBottom: '12px' } }),
                        uploadError && el('p', { style: { color: 'red', fontSize: '12px' } }, uploadError),
                        el(SelectControl, {
                            label: 'Fonte ativa',
                            value: a.fontFamily,
                            options: fontOptions,
                            onChange: function (val) {
                                set({ fontFamily: val });
                                if (val) { var f = getStoredFonts().find(function(f){ return f.name===val; }); if(f) injectFontFace(val,f.url); }
                            }
                        })
                    ),
                    el(PanelBody, { title: 'Tipografia', initialOpen: true },
                        el(RangeControl, {
                            label: 'Tamanho (px)',
                            value: a.fontSize,
                            min: 10, max: 120,
                            onChange: function(val){ set({ fontSize: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Peso da fonte',
                            value: a.fontWeight || 'normal',
                            options: weightOptions,
                            onChange: function(val){ set({ fontWeight: val }); }
                        }),
                        el(ToggleControl, {
                            label: 'Itálico',
                            checked: a.italic,
                            onChange: function(val){ set({ italic: val }); }
                        }),
                        el(RangeControl, {
                            label: 'Line Height',
                            value: parseFloat(a.lineHeight) || 1,
                            min: 0.5, max: 5, step: 0.1,
                            onChange: function(val){ set({ lineHeight: String(val) }); },
                            allowReset: true,
                            resetFallbackValue: '',
                            onReset: function(){ set({ lineHeight: '' }); }
                        }),
                        el(RangeControl, {
                            label: 'Letter Spacing (px)',
                            value: parseFloat(a.letterSpacing) || 0,
                            min: -10, max: 30, step: 0.5,
                            onChange: function(val){ set({ letterSpacing: String(val) }); },
                            allowReset: true,
                            resetFallbackValue: '',
                            onReset: function(){ set({ letterSpacing: '' }); }
                        }),
                        el(SelectControl, {
                            label: 'Text Decoration',
                            value: a.textDecoration || '',
                            options: [
                                { label: '— Nenhum —',     value: '' },
                                { label: 'Underline',      value: 'underline' },
                                { label: 'Overline',       value: 'overline' },
                                { label: 'Line-through',   value: 'line-through' },
                            ],
                            onChange: function(val){ set({ textDecoration: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Text Transform',
                            value: a.textTransform || '',
                            options: [
                                { label: '— Nenhum —',    value: '' },
                                { label: 'Uppercase',     value: 'uppercase' },
                                { label: 'Lowercase',     value: 'lowercase' },
                                { label: 'Capitalize',    value: 'capitalize' },
                            ],
                            onChange: function(val){ set({ textTransform: val }); }
                        })
                    ),
                    el(PanelBody, { title: 'Cor do texto', initialOpen: false },
                        el(ColorPicker, {
                            color: a.textColor,
                            onChangeComplete: function(val){ set({ textColor: val.hex }); }
                        })
                    ),
                    el(PanelBody, { title: 'Cor de fundo', initialOpen: false },
                        el(ColorPicker, {
                            color: a.backgroundColor,
                            onChangeComplete: function(val){ set({ backgroundColor: val.hex }); }
                        }),
                        a.backgroundColor && el('button', {
                            style: { marginTop: '8px', fontSize: '11px', cursor: 'pointer' },
                            onClick: function(){ set({ backgroundColor: '' }); }
                        }, '✕ Remover cor de fundo')
                    )
                ),

                el(RichText, Object.assign({}, blockProps, {
                    tagName: 'p',
                    value: a.content,
                    onChange: function (val) { set({ content: val }); },
                    placeholder: 'Digite seu texto aqui…',
                    allowedFormats: [
                        'core/bold', 'core/italic', 'core/link',
                        'core/strikethrough', 'core/underline',
                        'core/text-color', 'core/subscript', 'core/superscript',
                        'core/keyboard', 'core/code', 'core/image'
                    ]
                }))
            );
        },

        save: function (props) {
            var a = props.attributes;
            return el('p', {
                className: 'wp-block-bebelume-custom-fonts',
                style: buildStyle(a),
                'data-bbl-font': a.fontFamily || undefined,
                dangerouslySetInnerHTML: { __html: a.content || '' }
            });
        }
    });

}(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components));
