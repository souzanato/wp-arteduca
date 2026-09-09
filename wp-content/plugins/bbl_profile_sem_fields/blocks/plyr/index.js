(function (blocks, element, blockEditor, components) {
    var el             = element.createElement;
    var useState       = element.useState;
    var useEffect      = element.useEffect;
    var useBlockProps  = blockEditor.useBlockProps;
    var InspectorControls = blockEditor.InspectorControls;
    var MediaUpload    = blockEditor.MediaUpload;
    var MediaUploadCheck = blockEditor.MediaUploadCheck;

    var PanelBody      = components.PanelBody;
    var PanelRow       = components.PanelRow;
    var TextControl    = components.TextControl;
    var ToggleControl  = components.ToggleControl;
    var RangeControl   = components.RangeControl;
    var SelectControl  = components.SelectControl;
    var Button         = components.Button;
    var ColorPicker    = components.ColorPicker;
    var Popover        = components.Popover;
    var __             = window.wp.i18n ? window.wp.i18n.__ : function(s){ return s; };

    // ── Ícone SVG do bloco ──────────────────────────────────────────────────
    var plyr_icon = el('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: '24', height: '24', fill: 'none', stroke: 'currentColor', strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round' },
        el('rect', { x: '2', y: '3', width: '20', height: '14', rx: '2' }),
        el('polygon', { points: '10,8 15,11 10,14' }),
        el('line', { x1: '8', y1: '21', x2: '16', y2: '21' }),
        el('line', { x1: '12', y1: '17', x2: '12', y2: '21' })
    );

    // ── ColorButton — botão que abre popover com ColorPicker ────────────────
    function ColorButton(props) {
        var label = props.label;
        var value = props.value;
        var onChange = props.onChange;
        var open = useState(false);
        var isOpen = open[0];
        var setIsOpen = open[1];

        return el('div', { style: { marginBottom: '8px' } },
            el('div', { style: { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '4px' } },
                el('span', { style: { fontSize: '11px', textTransform: 'uppercase', color: '#757575', fontWeight: 500 } }, label),
                el('div', { style: { display: 'flex', alignItems: 'center', gap: '6px' } },
                    value ? el('button', {
                        onClick: function() { onChange(''); },
                        style: { fontSize: '10px', color: '#cc0000', background: 'none', border: 'none', cursor: 'pointer', padding: '0' }
                    }, '✕ limpar') : null,
                    el('button', {
                        onClick: function() { setIsOpen(!isOpen); },
                        style: {
                            width: '28px', height: '28px', borderRadius: '50%', border: '2px solid #ccc',
                            background: value || '#fff', cursor: 'pointer', display: 'block'
                        }
                    })
                )
            ),
            isOpen ? el(Popover, { onClose: function() { setIsOpen(false); }, placement: 'left-start' },
                el('div', { style: { padding: '8px' } },
                    el(ColorPicker, {
                        color: value || '#ffffff',
                        onChange: function(c) { onChange(c); },
                        enableAlpha: false
                    })
                )
            ) : null
        );
    }

    // ── Preview no editor ───────────────────────────────────────────────────
    function PlyrPreview(props) {
        var a = props.attributes;

        if (!a.videoUrl) {
            return el('div', { style: { display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', padding: '40px', background: '#f0f0f0', border: '2px dashed #ccc', borderRadius: '4px', color: '#999' } },
                el('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '48', height: '48', viewBox: '0 0 24 24', fill: 'none', stroke: '#ccc', strokeWidth: '1.5' },
                    el('rect', { x: '2', y: '3', width: '20', height: '14', rx: '2' }),
                    el('polygon', { points: '10,8 15,11 10,14', fill: '#ccc', stroke: 'none' })
                ),
                el('p', { style: { margin: '12px 0 0', fontSize: '13px' } }, 'Selecione um vídeo no painel lateral →')
            );
        }

        // Monta CSS vars inline
        var cssVars = {};
        if (a.colorMain)           cssVars['--plyr-color-main'] = a.colorMain;
        if (a.colorControl)        cssVars['--plyr-video-control-color'] = a.colorControl;
        if (a.colorControlHover)   cssVars['--plyr-video-control-color-hover'] = a.colorControlHover;
        if (a.colorControlBgHover) cssVars['--plyr-video-control-background-hover'] = a.colorControlBgHover;
        if (a.colorProgress)       cssVars['--plyr-range-fill-background'] = a.colorProgress;

        // No editor mostra só um preview estático
        var paddingTop = '56.25%'; // 16:9
        if (a.ratio === '4:3')  paddingTop = '75%';
        if (a.ratio === '1:1')  paddingTop = '100%';
        if (a.ratio === '9:16') paddingTop = '177.78%';
        if (a.ratio === '21:9') paddingTop = '42.86%';

        return el('div', { style: Object.assign({ position: 'relative', width: '100%', paddingTop: paddingTop, background: '#000', borderRadius: '4px', overflow: 'hidden' }, cssVars) },
            el('div', { style: { position: 'absolute', inset: '0', display: 'flex', alignItems: 'center', justifyContent: 'center' } },
                a.posterUrl
                    ? el('img', { src: a.posterUrl, style: { width: '100%', height: '100%', objectFit: 'cover' } })
                    : null,
                el('div', { style: { position: 'absolute', inset: '0', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' } },
                    el('div', { style: { width: '64px', height: '64px', borderRadius: '50%', background: a.colorMain || '#00b3ff', display: 'flex', alignItems: 'center', justifyContent: 'center', opacity: '0.9' } },
                        el('svg', { width: '24', height: '24', viewBox: '0 0 24 24', fill: 'white' },
                            el('polygon', { points: '8,5 19,12 8,19' })
                        )
                    ),
                    el('p', { style: { color: '#fff', fontSize: '12px', marginTop: '12px', opacity: '0.8' } },
                        a.videoType === 'youtube' ? '▶ YouTube' :
                        a.videoType === 'vimeo'   ? '▶ Vimeo' : '▶ ' + (a.title || 'Vídeo')
                    )
                )
            )
        );
    }

    // ── Registro do bloco ───────────────────────────────────────────────────
    blocks.registerBlockType('bebelume/plyr', {
        icon: plyr_icon,

        edit: function (props) {
            var a = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps();

            return el(
                'div', blockProps,

                // ── Painel lateral ────────────────────────────────────────
                el(InspectorControls, {},

                    // FONTE DO VÍDEO
                    el(PanelBody, { title: '📁 Fonte do Vídeo', initialOpen: true },
                        el(SelectControl, {
                            label: 'Tipo de vídeo',
                            value: a.videoType,
                            options: [
                                { label: 'HTML5 (arquivo .mp4)', value: 'html5' },
                                { label: 'YouTube',              value: 'youtube' },
                                { label: 'Vimeo',                value: 'vimeo' },
                            ],
                            onChange: function(v) { setAttributes({ videoType: v }); }
                        }),

                        a.videoType === 'html5'
                            ? el(MediaUploadCheck, {},
                                el(MediaUpload, {
                                    onSelect: function(media) { setAttributes({ videoUrl: media.url, title: media.title || a.title }); },
                                    allowedTypes: ['video'],
                                    value: a.videoUrl,
                                    render: function(ref) {
                                        return el('div', {},
                                            el(Button, {
                                                onClick: ref.open,
                                                variant: a.videoUrl ? 'secondary' : 'primary',
                                                style: { width: '100%', justifyContent: 'center', marginBottom: '8px' }
                                            }, a.videoUrl ? '🎬 Trocar vídeo' : '🎬 Selecionar vídeo'),
                                            a.videoUrl ? el('p', { style: { fontSize: '11px', color: '#666', wordBreak: 'break-all', margin: '0 0 8px' } }, a.videoUrl) : null
                                        );
                                    }
                                })
                            )
                            : el(TextControl, {
                                label: a.videoType === 'youtube' ? 'URL ou ID do YouTube' : 'URL ou ID do Vimeo',
                                value: a.videoUrl,
                                placeholder: a.videoType === 'youtube' ? 'https://youtube.com/watch?v=...' : 'https://vimeo.com/...',
                                onChange: function(v) { setAttributes({ videoUrl: v }); }
                            }),

                        el(TextControl, {
                            label: 'Título do vídeo',
                            value: a.title,
                            placeholder: 'Nome do vídeo',
                            onChange: function(v) { setAttributes({ title: v }); }
                        }),

                        el(MediaUploadCheck, {},
                            el(MediaUpload, {
                                onSelect: function(media) { setAttributes({ posterUrl: media.url }); },
                                allowedTypes: ['image'],
                                value: a.posterUrl,
                                render: function(ref) {
                                    return el('div', {},
                                        el(Button, {
                                            onClick: ref.open,
                                            variant: 'secondary',
                                            style: { width: '100%', justifyContent: 'center', marginBottom: '4px' }
                                        }, a.posterUrl ? '🖼 Trocar poster' : '🖼 Selecionar poster'),
                                        a.posterUrl ? el('div', { style: { display: 'flex', alignItems: 'center', gap: '8px', marginTop: '6px' } },
                                            el('img', { src: a.posterUrl, style: { width: '60px', height: '40px', objectFit: 'cover', borderRadius: '3px' } }),
                                            el('button', {
                                                onClick: function() { setAttributes({ posterUrl: '' }); },
                                                style: { color: '#cc0000', background: 'none', border: 'none', cursor: 'pointer', fontSize: '11px' }
                                            }, '✕ remover')
                                        ) : null
                                    );
                                }
                            })
                        )
                    ),

                    // COMPORTAMENTO
                    el(PanelBody, { title: '▶️ Comportamento', initialOpen: false },
                        el(ToggleControl, { label: 'Autoplay',                    checked: a.autoplay,     onChange: function(v) { setAttributes({ autoplay: v }); } }),
                        el(ToggleControl, { label: 'Mudo (muted)',                checked: a.muted,        onChange: function(v) { setAttributes({ muted: v }); } }),
                        el(ToggleControl, { label: 'Loop',                        checked: a.loop,         onChange: function(v) { setAttributes({ loop: v }); } }),
                        el(ToggleControl, { label: 'Pausar outros players',       checked: a.autopause,    onChange: function(v) { setAttributes({ autopause: v }); } }),
                        el(ToggleControl, { label: 'Inline no iOS',               checked: a.playsinline,  onChange: function(v) { setAttributes({ playsinline: v }); } }),
                        el(ToggleControl, { label: 'Clicar para play/pause',      checked: a.clickToPlay,  onChange: function(v) { setAttributes({ clickToPlay: v }); } }),
                        el(ToggleControl, { label: 'Esconder controles ao pausar',checked: a.hideControls, onChange: function(v) { setAttributes({ hideControls: v }); } })
                    ),

                    // REPRODUÇÃO
                    el(PanelBody, { title: '⏱️ Reprodução', initialOpen: false },
                        el(RangeControl, {
                            label: 'Tempo de avanço/recuo (segundos)',
                            value: a.seekTime, min: 5, max: 30, step: 5,
                            onChange: function(v) { setAttributes({ seekTime: v }); }
                        }),
                        el(RangeControl, {
                            label: 'Volume inicial (0–1)',
                            value: a.volume, min: 0, max: 1, step: 0.1,
                            onChange: function(v) { setAttributes({ volume: v }); }
                        }),
                        el(SelectControl, {
                            label: 'Velocidade padrão',
                            value: String(a.speed),
                            options: [
                                { label: '0.5×', value: '0.5' },
                                { label: '0.75×', value: '0.75' },
                                { label: '1× (normal)', value: '1' },
                                { label: '1.25×', value: '1.25' },
                                { label: '1.5×', value: '1.5' },
                                { label: '1.75×', value: '1.75' },
                                { label: '2×', value: '2' },
                            ],
                            onChange: function(v) { setAttributes({ speed: parseFloat(v) }); }
                        }),
                        el(SelectControl, {
                            label: 'Proporção (ratio)',
                            value: a.ratio,
                            options: [
                                { label: '16:9 (padrão)', value: '16:9' },
                                { label: '4:3',           value: '4:3' },
                                { label: '1:1',           value: '1:1' },
                                { label: '9:16 (vertical)',value: '9:16' },
                                { label: '21:9 (cinema)', value: '21:9' },
                            ],
                            onChange: function(v) { setAttributes({ ratio: v }); }
                        })
                    ),

                    // CONTROLES
                    el(PanelBody, { title: '🎛️ Controles', initialOpen: false },
                        el('p', { style: { fontSize: '12px', color: '#666', margin: '0 0 8px' } }, 'Escolha quais botões aparecem no player:'),
                        el(ToggleControl, { label: '▶ Botão grande (centro)',   checked: a.ctrlPlayLarge,   onChange: function(v) { setAttributes({ ctrlPlayLarge: v }); } }),
                        el(ToggleControl, { label: '↺ Restart',                 checked: a.ctrlRestart,     onChange: function(v) { setAttributes({ ctrlRestart: v }); } }),
                        el(ToggleControl, { label: '⏪ Retroceder',             checked: a.ctrlRewind,      onChange: function(v) { setAttributes({ ctrlRewind: v }); } }),
                        el(ToggleControl, { label: '▶ Play / Pause',            checked: a.ctrlPlay,        onChange: function(v) { setAttributes({ ctrlPlay: v }); } }),
                        el(ToggleControl, { label: '⏩ Avançar',                checked: a.ctrlFastFwd,     onChange: function(v) { setAttributes({ ctrlFastFwd: v }); } }),
                        el(ToggleControl, { label: '━ Progresso',               checked: a.ctrlProgress,    onChange: function(v) { setAttributes({ ctrlProgress: v }); } }),
                        el(ToggleControl, { label: '🕐 Tempo atual',            checked: a.ctrlCurrentTime, onChange: function(v) { setAttributes({ ctrlCurrentTime: v }); } }),
                        el(ToggleControl, { label: '⏱ Duração',                checked: a.ctrlDuration,    onChange: function(v) { setAttributes({ ctrlDuration: v }); } }),
                        el(ToggleControl, { label: '🔇 Mute',                   checked: a.ctrlMute,        onChange: function(v) { setAttributes({ ctrlMute: v }); } }),
                        el(ToggleControl, { label: '🔊 Volume',                 checked: a.ctrlVolume,      onChange: function(v) { setAttributes({ ctrlVolume: v }); } }),
                        el(ToggleControl, { label: '💬 Legendas',               checked: a.ctrlCaptions,    onChange: function(v) { setAttributes({ ctrlCaptions: v }); } }),
                        el(ToggleControl, { label: '⚙️ Configurações',          checked: a.ctrlSettings,    onChange: function(v) { setAttributes({ ctrlSettings: v }); } }),
                        el(ToggleControl, { label: '📺 Picture-in-Picture',     checked: a.ctrlPip,         onChange: function(v) { setAttributes({ ctrlPip: v }); } }),
                        el(ToggleControl, { label: '📡 AirPlay',                checked: a.ctrlAirplay,     onChange: function(v) { setAttributes({ ctrlAirplay: v }); } }),
                        el(ToggleControl, { label: '⛶ Tela cheia',             checked: a.ctrlFullscreen,  onChange: function(v) { setAttributes({ ctrlFullscreen: v }); } })
                    ),

                    // CORES
                    el(PanelBody, { title: '🎨 Cores', initialOpen: false },
                        el('p', { style: { fontSize: '12px', color: '#666', margin: '0 0 12px' } }, 'Deixe em branco para usar a cor padrão do Plyr.'),
                        el(ColorButton, { label: 'Cor principal',              value: a.colorMain,           onChange: function(v) { setAttributes({ colorMain: v }); } }),
                        el(ColorButton, { label: 'Cor dos ícones',             value: a.colorControl,        onChange: function(v) { setAttributes({ colorControl: v }); } }),
                        el(ColorButton, { label: 'Cor dos ícones (hover)',     value: a.colorControlHover,   onChange: function(v) { setAttributes({ colorControlHover: v }); } }),
                        el(ColorButton, { label: 'Fundo dos controles (hover)',value: a.colorControlBgHover, onChange: function(v) { setAttributes({ colorControlBgHover: v }); } }),
                        el(ColorButton, { label: 'Cor da barra de progresso',  value: a.colorProgress,       onChange: function(v) { setAttributes({ colorProgress: v }); } })
                    )
                ),

                // ── Preview no canvas ──────────────────────────────────────
                el(PlyrPreview, { attributes: a })
            );
        },

        save: function () {
            // render.php cuida do output
            return null;
        }
    });

}(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components));
