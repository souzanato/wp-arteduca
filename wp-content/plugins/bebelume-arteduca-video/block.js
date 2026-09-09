(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var BlockControls = wp.blockEditor.BlockControls;
    var AlignmentToolbar = wp.blockEditor.AlignmentToolbar;
    var RichText = wp.blockEditor.RichText;
    var MediaUpload = wp.blockEditor.MediaUpload;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
    var ColorPalette = wp.components.ColorPalette;
    var Button = wp.components.Button;
    var __ = wp.i18n.__;

    registerBlockType('bebelume/arteduca-video', {
        title: __('Bebelume ArtEduca Video', 'bebelume-arteduca-video'),
        icon: 'video-alt3',
        category: 'media',
        supports: {
            html: true,
            align: false
        },
        attributes: {
            videoUrl: {
                type: 'string',
                default: ''
            },
            posterUrl: {
                type: 'string',
                default: ''
            },
            title: {
                type: 'string',
                default: ''
            },
            description: {
                type: 'string',
                default: ''
            },
            descriptionThumbUrl: {
                type: 'string',
                default: ''
            },
            // Cores Principais
            colorMain: {
                type: 'string',
                default: '#00b3ff'
            },
            videoBackground: {
                type: 'string',
                default: ''
            },
            // Controles de Vídeo
            videoControlsBackground: {
                type: 'string',
                default: ''
            },
            videoControlColor: {
                type: 'string',
                default: '#ffffff'
            },
            videoControlColorHover: {
                type: 'string',
                default: '#ffffff'
            },
            videoControlBackgroundHover: {
                type: 'string',
                default: ''
            },
            // Barra de Progresso
            rangeFillBackground: {
                type: 'string',
                default: ''
            },
            rangeThumbBackground: {
                type: 'string',
                default: '#ffffff'
            },
            videoRangeTrackBackground: {
                type: 'string',
                default: ''
            },
            rangeTrackHeight: {
                type: 'string',
                default: '5px'
            },
            // Menus
            menuBackground: {
                type: 'string',
                default: ''
            },
            menuColor: {
                type: 'string',
                default: ''
            },
            menuRadius: {
                type: 'string',
                default: '4px'
            },
            // Legendas
            captionsBackground: {
                type: 'string',
                default: ''
            },
            captionsTextColor: {
                type: 'string',
                default: ''
            },
            // Dimensões
            controlIconSize: {
                type: 'string',
                default: '18px'
            },
            controlSpacing: {
                type: 'string',
                default: '10px'
            },
            controlRadius: {
                type: 'string',
                default: '3px'
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var useState = wp.element.useState;

            // Estado da modal
            var modalState = useState(false);
            var showModal = modalState[0];
            var setShowModal = modalState[1];
            
            var videosState = useState([]);
            var videos = videosState[0];
            var setVideos = videosState[1];
            
            var loadingState = useState(false);
            var loading = loadingState[0];
            var setLoading = loadingState[1];
            
            // Estado de busca
            var searchState = useState('');
            var searchTerm = searchState[0];
            var setSearchTerm = searchState[1];

            // Função para buscar vídeos
            function fetchVideos() {
                setLoading(true);
                setShowModal(true);
                setSearchTerm(''); // Limpa busca ao abrir
                
                fetch(bebelumeVideoApiUrl)
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        setVideos(data);
                        setLoading(false);
                    })
                    .catch(function(error) {
                        console.error('Erro ao buscar vídeos:', error);
                        setLoading(false);
                        setVideos([]);
                    });
            }

            // Função para filtrar vídeos
            function filterVideos() {
                if (!searchTerm || searchTerm.trim() === '') {
                    return videos;
                }
                
                var term = searchTerm.toLowerCase();
                return videos.filter(function(video) {
                    var title = (video.title || '').toLowerCase();
                    var description = (video.description || '').toLowerCase();
                    var videoUrl = (video.videoUrl || '').toLowerCase();
                    
                    return title.indexOf(term) !== -1 || 
                           description.indexOf(term) !== -1 ||
                           videoUrl.indexOf(term) !== -1;
                });
            }

            // Função para importar vídeo
            function importVideo(video) {
                setAttributes({
                    videoUrl: video.videoUrl || '',
                    title: video.title || '',
                    description: video.description || '',
                    posterUrl: video.thumbnail || ''
                });
                setShowModal(false);
                setSearchTerm(''); // Limpa busca ao fechar
            }

            var colors = [
                { name: 'Azul Plyr', color: '#00b3ff' },
                { name: 'Verde', color: '#4CAF50' },
                { name: 'Vermelho', color: '#f44336' },
                { name: 'Rosa', color: '#E91E63' },
                { name: 'Roxo', color: '#9C27B0' },
                { name: 'Laranja', color: '#FF9800' },
                { name: 'Amarelo', color: '#FFC107' },
                { name: 'Azul Escuro', color: '#2196F3' },
                { name: 'Preto', color: '#000000' },
                { name: 'Branco', color: '#ffffff' },
                { name: 'Cinza Escuro', color: '#333333' },
                { name: 'Cinza', color: '#999999' },
                { name: 'Cinza Claro', color: '#f5f5f5' },
                { name: 'Transparente Escuro', color: 'rgba(0, 0, 0, 0.75)' }
            ];

            var blockId = 'plyr-' + props.clientId;

            return [
                // Painel de configurações
                el(InspectorControls, {},
                    // CONFIGURAÇÕES DO VÍDEO
                    el(PanelBody, { 
                        title: __('📹 Configurações do Vídeo', 'bebelume-arteduca-video'), 
                        initialOpen: true 
                    },
                        // BOTÃO IMPORTAR VÍDEOS
                        el('div', { 
                            style: { 
                                marginBottom: '20px',
                                padding: '16px',
                                background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                borderRadius: '8px'
                            } 
                        },
                            el(Button, {
                                onClick: fetchVideos,
                                variant: 'primary',
                                style: {
                                    width: '100%',
                                    height: '44px',
                                    fontSize: '14px',
                                    fontWeight: '600',
                                    background: 'white',
                                    color: '#667eea',
                                    border: 'none',
                                    borderRadius: '6px',
                                    boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                                }
                            }, '🎬 Importar Vídeo dos Posts')
                        ),
                        
                        el(TextControl, {
                            label: __('URL do Vídeo', 'bebelume-arteduca-video'),
                            value: attributes.videoUrl,
                            onChange: function(val) { setAttributes({ videoUrl: val }); },
                            placeholder: 'https://exemplo.com/video.mp4',
                            help: __('Formatos: MP4, WebM, OGG', 'bebelume-arteduca-video')
                        }),
                        
                        el(TextControl, {
                            label: __('Título do Vídeo', 'bebelume-arteduca-video'),
                            value: attributes.title,
                            onChange: function(val) { setAttributes({ title: val }); },
                            placeholder: 'Digite o título do vídeo...',
                            help: __('Título que aparece abaixo do vídeo', 'bebelume-arteduca-video')
                        }),
                        
                        el(TextareaControl, {
                            label: __('Descrição do Vídeo', 'bebelume-arteduca-video'),
                            value: attributes.description,
                            onChange: function(val) { setAttributes({ description: val }); },
                            placeholder: 'Digite a descrição do vídeo...',
                            help: __('Descrição que aparece abaixo do título', 'bebelume-arteduca-video'),
                            rows: 3
                        }),
                        
                        // Campo de Thumbnail com MediaUpload
                        el('div', { style: { marginTop: '16px' } },
                            el('label', { 
                                className: 'components-base-control__label',
                                style: { display: 'block', marginBottom: '8px' }
                            }, __('Thumbnail/Poster do Vídeo', 'bebelume-arteduca-video')),
                            
                            attributes.posterUrl ? 
                                el('div', {},
                                    el('img', {
                                        src: attributes.posterUrl,
                                        alt: 'Thumbnail',
                                        style: {
                                            width: '100%',
                                            maxWidth: '200px',
                                            height: 'auto',
                                            borderRadius: '4px',
                                            marginBottom: '8px',
                                            display: 'block'
                                        }
                                    }),
                                    el('div', { style: { display: 'flex', gap: '8px' } },
                                        el(MediaUpload, {
                                            onSelect: function(media) {
                                                setAttributes({ posterUrl: media.url });
                                            },
                                            allowedTypes: ['image'],
                                            value: attributes.posterUrl,
                                            render: function(obj) {
                                                return el(Button, {
                                                    onClick: obj.open,
                                                    variant: 'secondary',
                                                    isSmall: true
                                                }, __('Trocar Imagem', 'bebelume-arteduca-video'));
                                            }
                                        }),
                                        el(Button, {
                                            onClick: function() {
                                                setAttributes({ posterUrl: '' });
                                            },
                                            variant: 'secondary',
                                            isDestructive: true,
                                            isSmall: true
                                        }, __('Remover', 'bebelume-arteduca-video'))
                                    )
                                ) :
                                el(MediaUpload, {
                                    onSelect: function(media) {
                                        setAttributes({ posterUrl: media.url });
                                    },
                                    allowedTypes: ['image'],
                                    value: attributes.posterUrl,
                                    render: function(obj) {
                                        return el(Button, {
                                            onClick: obj.open,
                                            variant: 'secondary'
                                        }, __('📷 Selecionar da Galeria', 'bebelume-arteduca-video'));
                                    }
                                }),
                            
                            el('p', { 
                                className: 'components-base-control__help',
                                style: { marginTop: '8px', fontSize: '12px', color: '#757575' }
                            }, __('Imagem exibida antes de reproduzir o vídeo', 'bebelume-arteduca-video'))
                        ),
                        
                        // Campo de Thumb da Descrição com MediaUpload
                        el('div', { style: { marginTop: '16px' } },
                            el('label', { 
                                className: 'components-base-control__label',
                                style: { display: 'block', marginBottom: '8px' }
                            }, __('Thumb da Descrição', 'bebelume-arteduca-video')),
                            
                            attributes.descriptionThumbUrl ? 
                                el('div', {},
                                    el('img', {
                                        src: attributes.descriptionThumbUrl,
                                        alt: 'Thumb da Descrição',
                                        style: {
                                            width: '100%',
                                            maxWidth: '200px',
                                            height: 'auto',
                                            borderRadius: '4px',
                                            marginBottom: '8px',
                                            display: 'block'
                                        }
                                    }),
                                    el('div', { style: { display: 'flex', gap: '8px', marginBottom: '8px' } },
                                        el(MediaUpload, {
                                            onSelect: function(media) {
                                                setAttributes({ descriptionThumbUrl: media.url });
                                            },
                                            allowedTypes: ['image'],
                                            value: attributes.descriptionThumbUrl,
                                            render: function(obj) {
                                                return el(Button, {
                                                    onClick: obj.open,
                                                    variant: 'secondary',
                                                    isSmall: true
                                                }, __('Trocar Imagem', 'bebelume-arteduca-video'));
                                            }
                                        }),
                                        el(Button, {
                                            onClick: function() {
                                                setAttributes({ descriptionThumbUrl: '' });
                                            },
                                            variant: 'secondary',
                                            isDestructive: true,
                                            isSmall: true
                                        }, __('Remover', 'bebelume-arteduca-video'))
                                    ),
                                    el(TextControl, {
                                        label: __('URL da Imagem', 'bebelume-arteduca-video'),
                                        value: attributes.descriptionThumbUrl,
                                        onChange: function(val) { setAttributes({ descriptionThumbUrl: val }); },
                                        placeholder: 'https://exemplo.com/imagem.jpg'
                                    })
                                ) :
                                el('div', {},
                                    el(MediaUpload, {
                                        onSelect: function(media) {
                                            setAttributes({ descriptionThumbUrl: media.url });
                                        },
                                        allowedTypes: ['image'],
                                        value: attributes.descriptionThumbUrl,
                                        render: function(obj) {
                                            return el(Button, {
                                                onClick: obj.open,
                                                variant: 'secondary',
                                                style: { marginBottom: '8px', display: 'block', width: '100%' }
                                            }, __('📷 Selecionar da Galeria', 'bebelume-arteduca-video'));
                                        }
                                    }),
                                    el(TextControl, {
                                        label: __('ou digite a URL da Imagem', 'bebelume-arteduca-video'),
                                        value: attributes.descriptionThumbUrl,
                                        onChange: function(val) { setAttributes({ descriptionThumbUrl: val }); },
                                        placeholder: 'https://exemplo.com/imagem.jpg'
                                    })
                                ),
                            
                            el('p', { 
                                className: 'components-base-control__help',
                                style: { marginTop: '8px', fontSize: '12px', color: '#757575' }
                            }, __('Imagem que aparece ao lado da descrição do vídeo (padrão: logo Bebelume)', 'bebelume-arteduca-video'))
                        ),
                        
                        el('p', {
                            className: 'components-base-control__help',
                            style: { 
                                marginTop: '16px',
                                padding: '12px',
                                background: '#f0f0f0',
                                borderRadius: '4px',
                                fontSize: '13px',
                                color: '#1e1e1e'
                            }
                        }, __('💡 Edite o título e descrição diretamente no bloco abaixo com formatação rica (negrito, itálico, etc)', 'bebelume-arteduca-video'))
                    ),

                    // CORES PRINCIPAIS
                    el(PanelBody, { 
                        title: __('🎨 Cores Principais', 'bebelume-arteduca-video'), 
                        initialOpen: false 
                    },
                        el('p', { className: 'components-base-control__label' }, 
                            __('Cor Principal (botões, barra)', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.colorMain,
                            onChange: function(val) { setAttributes({ colorMain: val }); }
                        }),
                        
                        el('p', { className: 'components-base-control__label', style: { marginTop: '16px' } }, 
                            __('Fundo do Vídeo', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.videoBackground,
                            onChange: function(val) { setAttributes({ videoBackground: val }); },
                            clearable: true
                        })
                    ),

                    // CONTROLES DE VÍDEO
                    el(PanelBody, { 
                        title: __('🎮 Controles de Vídeo', 'bebelume-arteduca-video'), 
                        initialOpen: false 
                    },
                        el('p', { className: 'components-base-control__label' }, 
                            __('Fundo dos Controles', 'bebelume-arteduca-video')
                        ),
                        el('p', { style: { fontSize: '12px', color: '#757575', marginTop: '4px', marginBottom: '8px' } }, 
                            __('Padrão: gradiente transparente', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.videoControlsBackground,
                            onChange: function(val) { setAttributes({ videoControlsBackground: val }); },
                            clearable: true
                        }),
                        
                        el('p', { className: 'components-base-control__label', style: { marginTop: '16px' } }, 
                            __('Cor dos Ícones/Texto', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.videoControlColor,
                            onChange: function(val) { setAttributes({ videoControlColor: val }); }
                        }),
                        
                        el('p', { className: 'components-base-control__label', style: { marginTop: '16px' } }, 
                            __('Cor ao Passar o Mouse', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.videoControlColorHover,
                            onChange: function(val) { setAttributes({ videoControlColorHover: val }); }
                        }),
                        
                        el('p', { className: 'components-base-control__label', style: { marginTop: '16px' } }, 
                            __('Fundo ao Passar o Mouse', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.videoControlBackgroundHover,
                            onChange: function(val) { setAttributes({ videoControlBackgroundHover: val }); },
                            clearable: true
                        })
                    ),

                    // BARRA DE PROGRESSO
                    el(PanelBody, { 
                        title: __('📊 Barra de Progresso', 'bebelume-arteduca-video'), 
                        initialOpen: false 
                    },
                        el('p', { className: 'components-base-control__label' }, 
                            __('Cor de Preenchimento', 'bebelume-arteduca-video')
                        ),
                        el('p', { style: { fontSize: '12px', color: '#757575', marginTop: '4px', marginBottom: '8px' } }, 
                            __('Padrão: usa cor principal', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.rangeFillBackground,
                            onChange: function(val) { setAttributes({ rangeFillBackground: val }); },
                            clearable: true
                        }),
                        
                        el('p', { className: 'components-base-control__label', style: { marginTop: '16px' } }, 
                            __('Cor da Bolinha (Thumb)', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.rangeThumbBackground,
                            onChange: function(val) { setAttributes({ rangeThumbBackground: val }); }
                        }),
                        
                        el('p', { className: 'components-base-control__label', style: { marginTop: '16px' } }, 
                            __('Cor do Trilho', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.videoRangeTrackBackground,
                            onChange: function(val) { setAttributes({ videoRangeTrackBackground: val }); },
                            clearable: true
                        }),
                        
                        el(TextControl, {
                            label: __('Altura do Trilho', 'bebelume-arteduca-video'),
                            value: attributes.rangeTrackHeight,
                            onChange: function(val) { setAttributes({ rangeTrackHeight: val }); },
                            help: __('Ex: 5px, 8px, 10px', 'bebelume-arteduca-video'),
                            style: { marginTop: '16px' }
                        })
                    ),

                    // MENUS
                    el(PanelBody, { 
                        title: __('📋 Menus', 'bebelume-arteduca-video'), 
                        initialOpen: false 
                    },
                        el('p', { className: 'components-base-control__label' }, 
                            __('Fundo do Menu', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.menuBackground,
                            onChange: function(val) { setAttributes({ menuBackground: val }); },
                            clearable: true
                        }),
                        
                        el('p', { className: 'components-base-control__label', style: { marginTop: '16px' } }, 
                            __('Cor do Texto do Menu', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.menuColor,
                            onChange: function(val) { setAttributes({ menuColor: val }); },
                            clearable: true
                        }),
                        
                        el(TextControl, {
                            label: __('Arredondamento do Menu', 'bebelume-arteduca-video'),
                            value: attributes.menuRadius,
                            onChange: function(val) { setAttributes({ menuRadius: val }); },
                            help: __('Ex: 4px, 8px, 12px', 'bebelume-arteduca-video'),
                            style: { marginTop: '16px' }
                        })
                    ),

                    // LEGENDAS
                    el(PanelBody, { 
                        title: __('💬 Legendas', 'bebelume-arteduca-video'), 
                        initialOpen: false 
                    },
                        el('p', { className: 'components-base-control__label' }, 
                            __('Fundo das Legendas', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.captionsBackground,
                            onChange: function(val) { setAttributes({ captionsBackground: val }); },
                            clearable: true
                        }),
                        
                        el('p', { className: 'components-base-control__label', style: { marginTop: '16px' } }, 
                            __('Cor do Texto das Legendas', 'bebelume-arteduca-video')
                        ),
                        el(ColorPalette, {
                            colors: colors,
                            value: attributes.captionsTextColor,
                            onChange: function(val) { setAttributes({ captionsTextColor: val }); },
                            clearable: true
                        })
                    ),

                    // DIMENSÕES E ESPAÇAMENTOS
                    el(PanelBody, { 
                        title: __('📐 Dimensões e Espaçamentos', 'bebelume-arteduca-video'), 
                        initialOpen: false 
                    },
                        el(TextControl, {
                            label: __('Tamanho dos Ícones', 'bebelume-arteduca-video'),
                            value: attributes.controlIconSize,
                            onChange: function(val) { setAttributes({ controlIconSize: val }); },
                            help: __('Ex: 16px, 18px, 20px', 'bebelume-arteduca-video')
                        }),
                        
                        el(TextControl, {
                            label: __('Espaçamento entre Controles', 'bebelume-arteduca-video'),
                            value: attributes.controlSpacing,
                            onChange: function(val) { setAttributes({ controlSpacing: val }); },
                            help: __('Ex: 8px, 10px, 12px', 'bebelume-arteduca-video'),
                            style: { marginTop: '12px' }
                        }),
                        
                        el(TextControl, {
                            label: __('Arredondamento dos Controles', 'bebelume-arteduca-video'),
                            value: attributes.controlRadius,
                            onChange: function(val) { setAttributes({ controlRadius: val }); },
                            help: __('Ex: 3px, 5px, 8px', 'bebelume-arteduca-video'),
                            style: { marginTop: '12px' }
                        })
                    )
                ),

                // Preview no editor
                el('div', {
                    className: 'bebelume-video-wrapper',
                    style: Object.assign({}, 
                        attributes.colorMain ? { '--plyr-color-main': attributes.colorMain } : {},
                        attributes.videoBackground ? { '--plyr-video-background': attributes.videoBackground } : {},
                        attributes.videoControlsBackground ? { '--plyr-video-controls-background': attributes.videoControlsBackground } : {},
                        attributes.videoControlColor ? { '--plyr-video-control-color': attributes.videoControlColor } : {},
                        attributes.videoControlColorHover ? { '--plyr-video-control-color-hover': attributes.videoControlColorHover } : {},
                        attributes.videoControlBackgroundHover ? { '--plyr-video-control-background-hover': attributes.videoControlBackgroundHover } : {},
                        attributes.rangeFillBackground ? { '--plyr-range-fill-background': attributes.rangeFillBackground } : {},
                        attributes.rangeThumbBackground ? { '--plyr-range-thumb-background': attributes.rangeThumbBackground } : {},
                        attributes.videoRangeTrackBackground ? { '--plyr-video-range-track-background': attributes.videoRangeTrackBackground } : {},
                        attributes.rangeTrackHeight ? { '--plyr-range-track-height': attributes.rangeTrackHeight } : {},
                        attributes.menuBackground ? { '--plyr-menu-background': attributes.menuBackground } : {},
                        attributes.menuColor ? { '--plyr-menu-color': attributes.menuColor } : {},
                        attributes.menuRadius ? { '--plyr-menu-radius': attributes.menuRadius } : {},
                        attributes.captionsBackground ? { '--plyr-captions-background': attributes.captionsBackground } : {},
                        attributes.captionsTextColor ? { '--plyr-captions-text-color': attributes.captionsTextColor } : {},
                        attributes.controlIconSize ? { '--plyr-control-icon-size': attributes.controlIconSize } : {},
                        attributes.controlSpacing ? { '--plyr-control-spacing': attributes.controlSpacing } : {},
                        attributes.controlRadius ? { '--plyr-control-radius': attributes.controlRadius } : {}
                    )
                },
                    // Vídeo
                    attributes.videoUrl ? 
                        el('video', {
                            id: blockId,
                            className: 'bebelume-plyr-video',
                            controls: true,
                            src: attributes.videoUrl,
                            poster: attributes.posterUrl || undefined
                        }) :
                        el('div', {
                            className: 'bebelume-video-placeholder',
                            style: {
                                padding: '60px 20px',
                                textAlign: 'center',
                                backgroundColor: '#f0f0f0',
                                borderRadius: '8px',
                                border: '2px dashed #ccc'
                            }
                        }, 
                            el('span', { style: { fontSize: '48px', display: 'block', marginBottom: '12px' } }, '🎬'),
                            el('p', { style: { margin: '0', fontSize: '16px', color: '#666' } }, 
                                __('Adicione uma URL de vídeo no painel lateral →', 'bebelume-arteduca-video')
                            )
                        ),
                    
                    // Preview do layout Frontend (atualiza em tempo real)
                    (attributes.title || attributes.description) ?
                        el('div', {
                            className: 'bebelume-video-info',
                            style: {
                                display: 'flex',
                                marginTop: '12px',
                                gap: '12px',
                                padding: '12px',
                                background: '#f9f9f9',
                                borderRadius: '8px',
                                border: '1px solid #ddd'
                            }
                        },
                            // Logo Bebelume ou Customizada
                            el('img', {
                                src: attributes.descriptionThumbUrl || '/wp-content/plugins/bebelume-arteduca-video/assets/images/bebelume.png',
                                alt: attributes.descriptionThumbUrl ? 'Thumb' : 'Bebelume',
                                className: 'bebelume-logo',
                                style: {
                                    width: '50px',
                                    height: '50px',
                                    borderRadius: '50%',
                                    flexShrink: '0',
                                    objectFit: 'cover'
                                },
                                onError: function(e) {
                                    e.target.style.display = 'none';
                                }
                            }),
                            // Título e Descrição (Preview - não editável)
                            el('div', {
                                className: 'bebelume-video-text',
                                style: {
                                    flex: '1',
                                    minWidth: '0'
                                }
                            },
                                attributes.title ?
                                    el('h3', {
                                        className: 'bebelume-video-title',
                                        style: {
                                            margin: '0 0 8px 0',
                                            fontSize: '16px',
                                            fontWeight: '700',
                                            color: '#0f0f0f',
                                            lineHeight: '1.4'
                                        }
                                    }, attributes.title) : null,
                                
                                attributes.description ?
                                    el('div', {
                                        className: 'bebelume-video-description',
                                        style: {
                                            margin: '0',
                                            fontSize: '14px',
                                            color: '#606060',
                                            lineHeight: '1.6',
                                            whiteSpace: 'pre-wrap'
                                        }
                                    }, attributes.description) : null
                            )
                        ) : null
                ),
                
                // MODAL DE IMPORTAÇÃO
                showModal ? el('div', {
                    className: 'bebelume-import-modal-overlay',
                    onClick: function(e) {
                        if (e.target.className === 'bebelume-import-modal-overlay') {
                            setShowModal(false);
                        }
                    }
                },
                    el('div', { className: 'bebelume-import-modal' },
                        // Header
                        el('div', { className: 'bebelume-import-modal-header' },
                            el('div', { style: { flex: 1 } },
                                el('h2', { style: { marginBottom: '12px' } }, '🎬 Importar Vídeo dos Posts'),
                                el('input', {
                                    type: 'text',
                                    placeholder: '🔍 Buscar vídeos por título, descrição ou URL...',
                                    value: searchTerm,
                                    onChange: function(e) { setSearchTerm(e.target.value); },
                                    style: {
                                        width: '100%',
                                        padding: '10px 14px',
                                        fontSize: '14px',
                                        border: '2px solid #ddd',
                                        borderRadius: '6px',
                                        outline: 'none',
                                        transition: 'border-color 0.2s'
                                    },
                                    onFocus: function(e) { 
                                        e.target.style.borderColor = '#00b3ff'; 
                                    },
                                    onBlur: function(e) { 
                                        e.target.style.borderColor = '#ddd'; 
                                    }
                                })
                            ),
                            el('button', {
                                className: 'bebelume-import-modal-close',
                                onClick: function() { 
                                    setShowModal(false);
                                    setSearchTerm('');
                                }
                            }, '×')
                        ),
                        
                        // Body
                        el('div', { className: 'bebelume-import-modal-body' },
                            loading ? 
                                el('div', { className: 'bebelume-import-modal-loading' },
                                    el('p', {}, '⏳ Carregando vídeos...')
                                ) :
                            videos.length === 0 ?
                                el('div', { className: 'bebelume-import-modal-empty' },
                                    el('div', { className: 'bebelume-import-modal-empty-icon' }, '🎥'),
                                    el('p', {}, 'Nenhum vídeo encontrado'),
                                    el('p', { style: { fontSize: '14px', marginTop: '8px' } }, 
                                        'Crie posts de vídeo usando o plugin Video Playlist Block'
                                    )
                                ) :
                                (function() {
                                    var filteredVideos = filterVideos();
                                    
                                    return el('div', {},
                                        // Contador de resultados
                                        el('div', { 
                                            style: { 
                                                padding: '0 0 16px 0',
                                                fontSize: '14px',
                                                color: '#666',
                                                display: 'flex',
                                                justifyContent: 'space-between',
                                                alignItems: 'center'
                                            } 
                                        },
                                            el('span', {},
                                                filteredVideos.length === videos.length ?
                                                    '📊 ' + videos.length + ' vídeo' + (videos.length !== 1 ? 's' : '') + ' disponível' + (videos.length !== 1 ? 'eis' : '') :
                                                    '🔍 ' + filteredVideos.length + ' de ' + videos.length + ' vídeo' + (videos.length !== 1 ? 's' : '')
                                            ),
                                            searchTerm ? 
                                                el('button', {
                                                    onClick: function() { setSearchTerm(''); },
                                                    style: {
                                                        padding: '4px 12px',
                                                        fontSize: '12px',
                                                        background: '#f0f0f0',
                                                        border: 'none',
                                                        borderRadius: '4px',
                                                        cursor: 'pointer',
                                                        color: '#666'
                                                    }
                                                }, '✕ Limpar busca') : 
                                                null
                                        ),
                                        
                                        // Grid de vídeos ou mensagem de busca vazia
                                        filteredVideos.length === 0 ?
                                            el('div', { className: 'bebelume-import-modal-empty' },
                                                el('div', { className: 'bebelume-import-modal-empty-icon' }, '🔍'),
                                                el('p', {}, 'Nenhum vídeo encontrado para "' + searchTerm + '"'),
                                                el('p', { style: { fontSize: '14px', marginTop: '8px' } }, 
                                                    'Tente buscar por outro termo'
                                                )
                                            ) :
                                            el('div', { className: 'bebelume-import-modal-grid' },
                                                filteredVideos.map(function(video) {
                                        return el('div', {
                                            key: video.id,
                                            className: 'bebelume-import-video-card',
                                            onClick: function() { importVideo(video); }
                                        },
                                            video.thumbnail ?
                                                el('img', {
                                                    className: 'bebelume-import-video-thumbnail',
                                                    src: video.thumbnail,
                                                    alt: video.title
                                                }) :
                                                el('div', {
                                                    className: 'bebelume-import-video-thumbnail',
                                                    style: {
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent: 'center',
                                                        fontSize: '48px',
                                                        color: '#ddd'
                                                    }
                                                }, '🎥'),
                                            
                                            el('div', { className: 'bebelume-import-video-info' },
                                                el('h3', { className: 'bebelume-import-video-title' }, video.title),
                                                video.description ? 
                                                    el('p', { className: 'bebelume-import-video-desc' }, video.description) : 
                                                    null,
                                                el('div', { className: 'bebelume-import-video-date' }, video.date)
                                            )
                                        );
                                    })
                                )
                                    );
                                })()
                        )
                    )
                ) : null
            ];
        },

        deprecated: [
            {
                // Versão antiga com id no vídeo e data-logo-path na imagem
                attributes: {
                    videoUrl: { type: 'string', default: '' },
                    posterUrl: { type: 'string', default: '' },
                    title: { type: 'string', default: '' },
                    description: { type: 'string', default: '' },
                    colorMain: { type: 'string', default: '#00b3ff' },
                    videoBackground: { type: 'string', default: '' },
                    videoControlsBackground: { type: 'string', default: '' },
                    videoControlColor: { type: 'string', default: '#ffffff' },
                    videoControlColorHover: { type: 'string', default: '#ffffff' },
                    videoControlBackgroundHover: { type: 'string', default: '' },
                    rangeFillBackground: { type: 'string', default: '' },
                    rangeThumbBackground: { type: 'string', default: '#ffffff' },
                    videoRangeTrackBackground: { type: 'string', default: '' },
                    rangeTrackHeight: { type: 'string', default: '5px' },
                    menuBackground: { type: 'string', default: '' },
                    menuColor: { type: 'string', default: '' },
                    menuRadius: { type: 'string', default: '4px' },
                    captionsBackground: { type: 'string', default: '' },
                    captionsTextColor: { type: 'string', default: '' },
                    controlIconSize: { type: 'string', default: '18px' },
                    controlSpacing: { type: 'string', default: '10px' },
                    controlRadius: { type: 'string', default: '3px' }
                },
                // Detecta se o bloco salvo é da versão antiga
                isEligible: function(attributes, innerBlocks, outerHTML) {
                    // Converte para string se necessário
                    if (!outerHTML) return false;
                    
                    var htmlString = '';
                    if (typeof outerHTML === 'string') {
                        htmlString = outerHTML;
                    } else if (outerHTML && typeof outerHTML === 'object' && outerHTML.outerHTML) {
                        htmlString = outerHTML.outerHTML;
                    } else if (outerHTML && typeof outerHTML === 'object' && outerHTML.innerHTML) {
                        htmlString = outerHTML.innerHTML;
                    } else {
                        // Tenta converter para string
                        try {
                            htmlString = String(outerHTML);
                        } catch (e) {
                            return false;
                        }
                    }
                    
                    // Se o HTML contém id="plyr-" ou data-logo-path, é versão antiga
                    return htmlString && (
                        htmlString.indexOf('id="plyr-') !== -1 || 
                        htmlString.indexOf('data-logo-path') !== -1
                    );
                },
                // Migra da versão antiga para nova automaticamente
                migrate: function(attributes) {
                    // Simplesmente retorna os mesmos atributos
                    // O WordPress vai re-salvar com a nova função save
                    return attributes;
                },
                save: function(props) {
                    // Não precisa ser exato, o isEligible já detecta
                    return null;
                }
            }
        ],

        save: function(props) {
            var attributes = props.attributes;

            return el('div', {
                className: 'bebelume-video-wrapper',
                'data-poster-url': attributes.posterUrl,
                'data-description-thumb-url': attributes.descriptionThumbUrl,
                'data-color-main': attributes.colorMain,
                'data-video-background': attributes.videoBackground,
                'data-video-controls-bg': attributes.videoControlsBackground,
                'data-video-control-color': attributes.videoControlColor,
                'data-video-control-color-hover': attributes.videoControlColorHover,
                'data-video-control-bg-hover': attributes.videoControlBackgroundHover,
                'data-range-fill-bg': attributes.rangeFillBackground,
                'data-range-thumb-bg': attributes.rangeThumbBackground,
                'data-video-range-track-bg': attributes.videoRangeTrackBackground,
                'data-range-track-height': attributes.rangeTrackHeight,
                'data-menu-bg': attributes.menuBackground,
                'data-menu-color': attributes.menuColor,
                'data-menu-radius': attributes.menuRadius,
                'data-captions-bg': attributes.captionsBackground,
                'data-captions-text-color': attributes.captionsTextColor,
                'data-control-icon-size': attributes.controlIconSize,
                'data-control-spacing': attributes.controlSpacing,
                'data-control-radius': attributes.controlRadius
            },
                attributes.videoUrl ?
                    el('video', {
                        className: 'bebelume-plyr-video',
                        controls: true,
                        src: attributes.videoUrl,
                        poster: attributes.posterUrl || undefined,
                        playsInline: true
                    }) : null,
                
                // Layout estilo YouTube: Logo + Título + Descrição
                (attributes.title || attributes.description) ?
                    el('div', {
                        className: 'bebelume-video-info'
                    },
                        // Logo Bebelume
                        el('img', {
                            src: '',
                            alt: 'Bebelume',
                            className: 'bebelume-logo'
                        }),
                        // Título e Descrição
                        el('div', {
                            className: 'bebelume-video-text'
                        },
                            attributes.title ?
                                el(RichText.Content, {
                                    tagName: 'h3',
                                    className: 'bebelume-video-title',
                                    value: attributes.title
                                }) : null,
                            
                            attributes.description ?
                                el(RichText.Content, {
                                    tagName: 'div',
                                    className: 'bebelume-video-description',
                                    value: attributes.description
                                }) : null
                        )
                    ) : null
            );
        }
    });
})(window.wp);
