(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, InnerBlocks } = wp.blockEditor;
    const { PanelBody, ColorPalette, ColorPicker, Button } = wp.components;
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

    registerBlockType('bebelume-arteduca/marketing-conteudo', {
        title: __('Marketing Bebelume — Conteúdo', 'bebelume-arteduca'),
        icon: 'layout',
        category: 'bebelume-arteduca',

        attributes: {
            corFundo: {
                type: 'string',
                default: '#F4F2EA'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { corFundo } = attributes;
            const mostrarPickerState = useState(false);
            const mostrarPicker = mostrarPickerState[0];
            const setMostrarPicker = mostrarPickerState[1];

            const removerBloco = function() {
                if (window.confirm('Tem certeza que deseja remover este bloco?')) {
                    wp.data.dispatch('core/block-editor').removeBlock(clientId);
                }
            };

            const duplicarBloco = function() {
                wp.data.dispatch('core/block-editor').duplicateBlocks([clientId]);
            };

            const adicionarAntes = function() {
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/marketing-conteudo');
                const index = wp.data.select('core/block-editor').getBlockIndex(clientId);
                wp.data.dispatch('core/block-editor').insertBlock(novoBloco, index);
            };

            const adicionarDepois = function() {
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/marketing-conteudo');
                const index = wp.data.select('core/block-editor').getBlockIndex(clientId);
                wp.data.dispatch('core/block-editor').insertBlock(novoBloco, index + 1);
            };

            return wp.element.createElement(
                wp.element.Fragment,
                null,
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
                            onClick: function() { setMostrarPicker(!mostrarPicker); },
                            style: { marginTop: '8px', marginBottom: '8px' }
                        }, mostrarPicker
                            ? __('Ocultar cor personalizada', 'bebelume-arteduca')
                            : __('Escolher cor personalizada…', 'bebelume-arteduca')
                        ),
                        mostrarPicker ? wp.element.createElement(ColorPicker, {
                            color: corFundo,
                            onChange: function(cor) {
                                setAttributes({ corFundo: cor || '#F4F2EA' });
                            },
                            enableAlpha: false,
                            defaultValue: corFundo
                        }) : null
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
                        className: 'bebelume-marketing-conteudo',
                        style: { backgroundColor: corFundo }
                    },
                    wp.element.createElement(InnerBlocks)
                )
            );
        },

        save: function(props) {
            const { corFundo } = props.attributes;

            return wp.element.createElement(
                'div',
                {
                    className: 'bebelume-marketing-conteudo',
                    style: { backgroundColor: corFundo }
                },
                wp.element.createElement(InnerBlocks.Content)
            );
        }
    });
})(window.wp);
