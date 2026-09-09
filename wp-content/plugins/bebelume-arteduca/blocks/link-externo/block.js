(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { RichText, InspectorControls, BlockControls, AlignmentToolbar } = wp.blockEditor;
    const { PanelBody, ColorPalette, Button, TextControl, ToggleControl } = wp.components;
    const { __ } = wp.i18n;
    const { useEffect } = wp.element;

    // Paleta de cores Bebelume
    const coresPredefinidas = [
        { name: 'Azul Escuro', color: '#2A4582' },
        { name: 'Roxo', color: '#5C4F93' },
        { name: 'Vermelho', color: '#D75F4D' },
        { name: 'Amarelo', color: '#EAB35E' },
        { name: 'Cinza Claro', color: '#F3F0EB' },
        { name: 'Branco', color: '#FFFFFF' }
    ];

    registerBlockType('bebelume-arteduca/link-externo', {
        title: __('Bebelume Link Externo', 'bebelume-arteduca'),
        icon: 'admin-links',
        category: 'bebelume-arteduca',
        
        attributes: {
            titulo: {
                type: 'string',
                default: 'Link Interessante'
            },
            descricao: {
                type: 'string',
                default: 'Clique no botão abaixo para visitar o link.'
            },
            linkUrl: {
                type: 'string',
                default: ''
            },
            linkTexto: {
                type: 'string',
                default: 'Visitar Site'
            },
            corFundo: {
                type: 'string',
                default: '#F3F0EB'
            },
            corBotao: {
                type: 'string',
                default: '#2A4582'
            },
            alignment: {
                type: 'string'
            },
            abrirNovaAba: {
                type: 'boolean',
                default: true
            }
        },

        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { titulo, descricao, linkUrl, linkTexto, corFundo, corBotao, alignment, abrirNovaAba } = attributes;

            // Função para remover o bloco
            const removerBloco = function() {
                if (window.confirm('Tem certeza que deseja remover este bloco?')) {
                    wp.data.dispatch('core/block-editor').removeBlock(clientId);
                }
            };

            // Função para duplicar o bloco
            const duplicarBloco = function() {
                const blocoAtual = wp.data.select('core/block-editor').getBlock(clientId);
                const blocoDuplicado = wp.blocks.cloneBlock(blocoAtual);
                wp.data.dispatch('core/block-editor').insertBlocks(
                    blocoDuplicado,
                    wp.data.select('core/block-editor').getBlockIndex(clientId) + 1
                );
            };

            // Inicializa Lucide icons
            useEffect(function() {
                if (typeof lucide !== 'undefined') {
                    setTimeout(function() {
                        lucide.createIcons();
                    }, 100);
                }
            }, [linkUrl]);

            return wp.element.createElement(
                wp.element.Fragment,
                null,
                wp.element.createElement(
                    BlockControls,
                    null,
                    wp.element.createElement(AlignmentToolbar, {
                        value: alignment,
                        onChange: function(valor) {
                            setAttributes({ alignment: valor });
                        }
                    })
                ),
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Configurações de Cores', 'bebelume-arteduca') },
                        wp.element.createElement('p', null, 
                            wp.element.createElement('strong', null, __('Cor de Fundo:', 'bebelume-arteduca'))
                        ),
                        wp.element.createElement(ColorPalette, {
                            colors: coresPredefinidas,
                            value: corFundo,
                            onChange: function(cor) {
                                setAttributes({ corFundo: cor || '#F3F0EB' });
                            }
                        }),
                        wp.element.createElement('br'),
                        wp.element.createElement('p', null, 
                            wp.element.createElement('strong', null, __('Cor do Botão:', 'bebelume-arteduca'))
                        ),
                        wp.element.createElement(ColorPalette, {
                            colors: coresPredefinidas,
                            value: corBotao,
                            onChange: function(cor) {
                                setAttributes({ corBotao: cor || '#2A4582' });
                            }
                        })
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Configurações do Link', 'bebelume-arteduca') },
                        wp.element.createElement(TextControl, {
                            label: __('URL do Link', 'bebelume-arteduca'),
                            value: linkUrl,
                            onChange: function(valor) {
                                setAttributes({ linkUrl: valor });
                            },
                            placeholder: 'https://exemplo.com',
                            help: __('Digite a URL completa (com https://)', 'bebelume-arteduca')
                        }),
                        wp.element.createElement(TextControl, {
                            label: __('Texto do Botão', 'bebelume-arteduca'),
                            value: linkTexto,
                            onChange: function(valor) {
                                setAttributes({ linkTexto: valor });
                            },
                            placeholder: 'Visitar Site'
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: __('Abrir em nova aba', 'bebelume-arteduca'),
                            checked: abrirNovaAba,
                            onChange: function(valor) {
                                setAttributes({ abrirNovaAba: valor });
                            },
                            help: abrirNovaAba ? __('Link abrirá em nova aba', 'bebelume-arteduca') : __('Link abrirá na mesma aba', 'bebelume-arteduca')
                        })
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { 
                            title: __('Ações', 'bebelume-arteduca'),
                            initialOpen: false
                        },
                        wp.element.createElement(Button, {
                            variant: 'secondary',
                            onClick: duplicarBloco,
                            style: { width: '100%', marginBottom: '8px' }
                        }, __('Duplicar Bloco', 'bebelume-arteduca')),
                        wp.element.createElement(Button, {
                            isDestructive: true,
                            variant: 'secondary',
                            onClick: removerBloco,
                            style: { width: '100%' }
                        }, __('Remover Bloco', 'bebelume-arteduca'))
                    )
                ),
                wp.element.createElement(
                    'div',
                    { 
                        className: 'bebelume-link-externo-box',
                        style: (function() {
                            const style = { backgroundColor: corFundo };
                            if (alignment && alignment !== 'left') {
                                style.textAlign = alignment;
                            }
                            return style;
                        })()
                    },
                    wp.element.createElement(
                        'div',
                        { className: 'bebelume-link-externo-icone' },
                        wp.element.createElement('i', {
                            'data-lucide': 'external-link',
                            style: { width: '48px', height: '48px', color: corBotao }
                        })
                    ),
                    wp.element.createElement(RichText, {
                        tagName: 'h3',
                        value: titulo,
                        onChange: function(valor) {
                            setAttributes({ titulo: valor });
                        },
                        placeholder: __('Digite o título do link...', 'bebelume-arteduca'),
                        className: 'bebelume-link-externo-titulo',
                        allowedFormats: [
                            'core/bold',
                            'core/italic',
                            'core/underline',
                            'core/link',
                            'core/strikethrough',
                            'core/code',
                            'core/subscript',
                            'core/superscript',
                            'core/text-color'
                        ],
                        inlineToolbar: true,
                        keepPlaceholderOnFocus: true
                    }),
                    wp.element.createElement(RichText, {
                        tagName: 'p',
                        value: descricao,
                        onChange: function(valor) {
                            setAttributes({ descricao: valor });
                        },
                        placeholder: __('Digite a descrição do link...', 'bebelume-arteduca'),
                        className: 'bebelume-link-externo-descricao',
                        allowedFormats: [
                            'core/bold',
                            'core/italic',
                            'core/underline',
                            'core/link',
                            'core/strikethrough',
                            'core/code',
                            'core/subscript',
                            'core/superscript',
                            'core/text-color'
                        ],
                        inlineToolbar: true,
                        keepPlaceholderOnFocus: true
                    }),
                    linkUrl ? 
                        wp.element.createElement(
                            'div',
                            { className: 'bebelume-link-externo-preview' },
                            wp.element.createElement('p', { 
                                className: 'bebelume-link-externo-url',
                                style: { 
                                    fontSize: '13px', 
                                    color: '#666', 
                                    wordBreak: 'break-all',
                                    marginBottom: '10px'
                                }
                            }, 
                                wp.element.createElement('i', {
                                    'data-lucide': 'link',
                                    style: { width: '14px', height: '14px', marginRight: '5px', display: 'inline-block', verticalAlign: 'middle' }
                                }),
                                linkUrl
                            )
                        )
                    : wp.element.createElement(
                        'p',
                        { 
                            style: { 
                                textAlign: 'center', 
                                padding: '20px', 
                                color: '#999',
                                fontSize: '14px'
                            } 
                        },
                        __('Configure a URL do link nas configurações →', 'bebelume-arteduca')
                    ),
                    wp.element.createElement(
                        'button',
                        {
                            className: 'bebelume-link-externo-botao',
                            style: { backgroundColor: corBotao },
                            disabled: true
                        },
                        wp.element.createElement('i', {
                            'data-lucide': 'external-link',
                            style: { width: '20px', height: '20px', marginRight: '8px' }
                        }),
                        linkTexto || __('Visitar Site', 'bebelume-arteduca')
                    )
                )
            );
        },

        save: function(props) {
            const { titulo, descricao, linkUrl, linkTexto, corFundo, corBotao, alignment, abrirNovaAba } = props.attributes;

            if (!linkUrl) {
                return null;
            }

            // Monta style base
            const containerStyle = { backgroundColor: corFundo };
            if (alignment && alignment !== 'left') {
                containerStyle.textAlign = alignment;
            }

            return wp.element.createElement(
                'div',
                { 
                    className: 'bebelume-link-externo-box',
                    style: containerStyle
                },
                wp.element.createElement(
                    'div',
                    { className: 'bebelume-link-externo-icone' },
                    wp.element.createElement('i', {
                        'data-lucide': 'external-link',
                        style: { width: '48px', height: '48px', color: corBotao }
                    })
                ),
                wp.element.createElement(RichText.Content, {
                    tagName: 'h3',
                    value: titulo,
                    className: 'bebelume-link-externo-titulo'
                }),
                wp.element.createElement(RichText.Content, {
                    tagName: 'p',
                    value: descricao,
                    className: 'bebelume-link-externo-descricao'
                }),
                wp.element.createElement(
                    'a',
                    {
                        href: linkUrl,
                        className: 'bebelume-link-externo-botao',
                        style: { backgroundColor: corBotao },
                        target: abrirNovaAba ? '_blank' : '_self',
                        rel: abrirNovaAba ? 'noopener noreferrer' : undefined
                    },
                    wp.element.createElement('i', {
                        'data-lucide': 'external-link',
                        style: { width: '20px', height: '20px' }
                    }),
                    linkTexto || __('Visitar Site', 'bebelume-arteduca')
                )
            );
        }
    });
})(window.wp);
