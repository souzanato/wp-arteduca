(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, InnerBlocks } = wp.blockEditor;
    const { PanelBody, ColorPalette, Button } = wp.components;
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

    registerBlockType('bebelume-arteduca/marketing-capa', {
        title: __('Marketing Bebelume — Capa', 'bebelume-arteduca'),
        icon: 'cover-image',
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

            const removerBloco = function() {
                if (window.confirm('Tem certeza que deseja remover este bloco?')) {
                    wp.data.dispatch('core/block-editor').removeBlock(clientId);
                }
            };

            const duplicarBloco = function() {
                wp.data.dispatch('core/block-editor').duplicateBlocks([clientId]);
            };

            const adicionarAntes = function() {
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/marketing-capa');
                const index = wp.data.select('core/block-editor').getBlockIndex(clientId);
                wp.data.dispatch('core/block-editor').insertBlock(novoBloco, index);
            };

            const adicionarDepois = function() {
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/marketing-capa');
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
                            onChange: function(cor) {
                                setAttributes({ corFundo: cor || '#F4F2EA' });
                            }
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
                        className: 'bebelume-marketing-capa',
                        style: { backgroundColor: corFundo }
                    },
                    wp.element.createElement('div', { className: 'bebelume-capa-borda bebelume-capa-borda-topo' }),
                    wp.element.createElement('div', { className: 'bebelume-capa-borda bebelume-capa-borda-base' }),
                    wp.element.createElement('div', { className: 'bebelume-capa-borda bebelume-capa-borda-esquerda' }),
                    wp.element.createElement('div', { className: 'bebelume-capa-borda bebelume-capa-borda-direita' }),
                    wp.element.createElement(
                        'div',
                        { className: 'bebelume-capa-conteudo' },
                        wp.element.createElement(InnerBlocks)
                    )
                )
            );
        },

        save: function(props) {
            const { corFundo } = props.attributes;

            return wp.element.createElement(
                'div',
                {
                    className: 'bebelume-marketing-capa',
                    style: { backgroundColor: corFundo }
                },
                wp.element.createElement('div', { className: 'bebelume-capa-borda bebelume-capa-borda-topo' }),
                wp.element.createElement('div', { className: 'bebelume-capa-borda bebelume-capa-borda-base' }),
                wp.element.createElement('div', { className: 'bebelume-capa-borda bebelume-capa-borda-esquerda' }),
                wp.element.createElement('div', { className: 'bebelume-capa-borda bebelume-capa-borda-direita' }),
                wp.element.createElement(
                    'div',
                    { className: 'bebelume-capa-conteudo' },
                    wp.element.createElement(InnerBlocks.Content)
                )
            );
        }
    });
})(window.wp);
