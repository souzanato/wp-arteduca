(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { RichText, InspectorControls, BlockControls, AlignmentToolbar } = wp.blockEditor;
    const { PanelBody, ColorPalette, ColorPicker, Button, RangeControl, __experimentalToggleGroupControl: ToggleGroupControl, __experimentalToggleGroupControlOption: ToggleGroupControlOption } = wp.components;
    const { useState } = wp.element;
    const { __ } = wp.i18n;

    const coresPredefinidas = [
        { name: 'Azul Escuro', color: '#2A4582' },
        { name: 'Roxo', color: '#5C4F93' },
        { name: 'Vermelho', color: '#D75F4D' },
        { name: 'Amarelo', color: '#EAB35E' },
        { name: 'Preto', color: '#000000' },
        { name: 'Creme', color: '#F3F0EB' },
        { name: 'Branco', color: '#FFFFFF' },
        { name: 'Creme Capa', color: '#F4F2EA' }
    ];

    registerBlockType('bebelume-arteduca/marketing-titulo', {
        title: __('Marketing Bebelume — Título', 'bebelume-arteduca'),
        icon: 'editor-textcolor',
        category: 'bebelume-arteduca',

        attributes: {
            titulo: {
                type: 'string',
                default: 'Título'
            },
            corFundo: {
                type: 'string',
                default: '#F4F2EA'
            },
            corTexto: {
                type: 'string',
                default: '#000000'
            },
            tamanhoFonte: {
                type: 'number',
                default: 48
            },
            alignment: {
                type: 'string',
                default: 'left'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { titulo, corFundo, corTexto, tamanhoFonte, alignment } = attributes;
            const mostrarPickerFundoState = useState(false);
            const mostrarPickerFundo = mostrarPickerFundoState[0];
            const setMostrarPickerFundo = mostrarPickerFundoState[1];

            const removerBloco = function() {
                if (window.confirm('Tem certeza que deseja remover este bloco?')) {
                    wp.data.dispatch('core/block-editor').removeBlock(clientId);
                }
            };

            const duplicarBloco = function() {
                wp.data.dispatch('core/block-editor').duplicateBlocks([clientId]);
            };

            const adicionarAntes = function() {
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/marketing-titulo');
                const index = wp.data.select('core/block-editor').getBlockIndex(clientId);
                wp.data.dispatch('core/block-editor').insertBlock(novoBloco, index);
            };

            const adicionarDepois = function() {
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/marketing-titulo');
                const index = wp.data.select('core/block-editor').getBlockIndex(clientId);
                wp.data.dispatch('core/block-editor').insertBlock(novoBloco, index + 1);
            };

            return wp.element.createElement(
                wp.element.Fragment,
                null,
                wp.element.createElement(
                    BlockControls,
                    null,
                    wp.element.createElement(AlignmentToolbar, {
                        value: alignment,
                        onChange: function(valor) {
                            setAttributes({ alignment: valor || 'left' });
                        }
                    })
                ),
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Cores', 'bebelume-arteduca') },
                        wp.element.createElement('p', null,
                            wp.element.createElement('strong', null, __('Cor de Fundo:', 'bebelume-arteduca'))
                        ),
                        wp.element.createElement(ColorPalette, {
                            colors: coresPredefinidas,
                            value: corFundo,
                            disableCustomColors: false,
                            onChange: function(cor) {
                                setAttributes({ corFundo: cor || '#F4F2EA' });
                            }
                        }),
                        wp.element.createElement(Button, {
                            variant: 'link',
                            onClick: function() { setMostrarPickerFundo(!mostrarPickerFundo); },
                            style: { marginTop: '8px', marginBottom: '8px' }
                        }, mostrarPickerFundo
                            ? __('Ocultar cor personalizada', 'bebelume-arteduca')
                            : __('Escolher cor de fundo personalizada…', 'bebelume-arteduca')
                        ),
                        mostrarPickerFundo ? wp.element.createElement(ColorPicker, {
                            color: corFundo,
                            onChange: function(cor) {
                                setAttributes({ corFundo: cor || '#F4F2EA' });
                            },
                            enableAlpha: false,
                            defaultValue: corFundo
                        }) : null,
                        wp.element.createElement('p', null,
                            wp.element.createElement('strong', null, __('Cor do Texto:', 'bebelume-arteduca'))
                        ),
                        wp.element.createElement(ColorPalette, {
                            colors: coresPredefinidas,
                            value: corTexto,
                            onChange: function(cor) {
                                setAttributes({ corTexto: cor || '#000000' });
                            }
                        })
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Tipografia', 'bebelume-arteduca') },
                        wp.element.createElement(RangeControl, {
                            label: __('Tamanho da fonte (px)', 'bebelume-arteduca'),
                            value: tamanhoFonte,
                            onChange: function(valor) {
                                setAttributes({ tamanhoFonte: valor || 48 });
                            },
                            min: 16,
                            max: 120
                        }),
                        wp.element.createElement('p', { style: { marginBottom: '8px' } },
                            wp.element.createElement('strong', null, __('Alinhamento:', 'bebelume-arteduca'))
                        ),
                        ToggleGroupControl ? wp.element.createElement(
                            ToggleGroupControl,
                            {
                                value: alignment || 'left',
                                isBlock: true,
                                onChange: function(valor) {
                                    setAttributes({ alignment: valor || 'left' });
                                }
                            },
                            wp.element.createElement(ToggleGroupControlOption, { value: 'left', label: __('Esquerda', 'bebelume-arteduca') }),
                            wp.element.createElement(ToggleGroupControlOption, { value: 'center', label: __('Centro', 'bebelume-arteduca') }),
                            wp.element.createElement(ToggleGroupControlOption, { value: 'right', label: __('Direita', 'bebelume-arteduca') })
                        ) : wp.element.createElement(
                            'div',
                            { style: { display: 'flex', gap: '8px' } },
                            ['left', 'center', 'right'].map(function(op) {
                                return wp.element.createElement(Button, {
                                    key: op,
                                    variant: alignment === op ? 'primary' : 'secondary',
                                    onClick: function() { setAttributes({ alignment: op }); },
                                    style: { flex: 1 }
                                }, op === 'left' ? __('Esquerda', 'bebelume-arteduca') : (op === 'center' ? __('Centro', 'bebelume-arteduca') : __('Direita', 'bebelume-arteduca')));
                            })
                        )
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
                            variant: 'secondary',
                            onClick: adicionarAntes,
                            style: { width: '100%', marginBottom: '8px' }
                        }, __('Adicionar Antes', 'bebelume-arteduca')),
                        wp.element.createElement(Button, {
                            variant: 'secondary',
                            onClick: adicionarDepois,
                            style: { width: '100%', marginBottom: '8px' }
                        }, __('Adicionar Depois', 'bebelume-arteduca')),
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
                        className: 'bebelume-marketing-titulo',
                        style: { backgroundColor: corFundo, textAlign: alignment }
                    },
                    wp.element.createElement(RichText, {
                        tagName: 'h2',
                        value: titulo,
                        onChange: function(valor) {
                            setAttributes({ titulo: valor });
                        },
                        placeholder: __('Digite o título...', 'bebelume-arteduca'),
                        className: 'bebelume-titulo-handypot',
                        style: { color: corTexto, fontSize: tamanhoFonte + 'px' },
                        allowedFormats: [
                            'core/bold',
                            'core/italic',
                            'core/underline',
                            'core/link',
                            'core/strikethrough',
                            'core/text-color'
                        ],
                        inlineToolbar: true
                    })
                )
            );
        },

        save: function(props) {
            const { titulo, corFundo, corTexto, tamanhoFonte, alignment } = props.attributes;

            return wp.element.createElement(
                'div',
                {
                    className: 'bebelume-marketing-titulo',
                    style: { backgroundColor: corFundo, textAlign: alignment }
                },
                wp.element.createElement(RichText.Content, {
                    tagName: 'h2',
                    value: titulo,
                    className: 'bebelume-titulo-handypot',
                    style: { color: corTexto, fontSize: tamanhoFonte + 'px' }
                })
            );
        }
    });
})(window.wp);
