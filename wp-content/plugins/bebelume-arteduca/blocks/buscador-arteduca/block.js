(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { RichText, InspectorControls, BlockControls, AlignmentToolbar, InnerBlocks } = wp.blockEditor;
    const { PanelBody, ColorPalette, Button } = wp.components;
    const { __ } = wp.i18n;

    // Paleta de cores predefinidas (mesma do Título ArtEduca)
    const coresPredefinidas = [
        { name: 'Azul Escuro', color: '#2A4582' },
        { name: 'Roxo', color: '#5C4F93' },
        { name: 'Vermelho', color: '#D75F4D' },
        { name: 'Amarelo', color: '#EAB35E' },
        { name: 'Preto', color: '#000000' }
    ];

    registerBlockType('bebelume/buscador-arteduca', {
        title: __('Buscador Bebelume ArtEduca', 'bebelume-arteduca'),
        icon: 'search',
        category: 'bebelume-arteduca',
        
        attributes: {
            titulo: {
                type: 'string',
                default: 'BEBELUME ARTEDUCA'
            },
            corFundo: {
                type: 'string',
                default: '#000000'
            },
            alignment: {
                type: 'string'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { titulo, corFundo, alignment } = attributes;

            // Função para remover o bloco
            const removerBloco = function() {
                if (window.confirm('Tem certeza que deseja remover este bloco?')) {
                    wp.data.dispatch('core/block-editor').removeBlock(clientId);
                }
            };

            // Função para duplicar o bloco
            const duplicarBloco = function() {
                wp.data.dispatch('core/block-editor').duplicateBlocks([clientId]);
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
                            setAttributes({ alignment: valor });
                        }
                    })
                ),
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Configurações de Cor', 'bebelume-arteduca') },
                        wp.element.createElement('p', null, 
                            wp.element.createElement('strong', null, __('Cor de Fundo:', 'bebelume-arteduca'))
                        ),
                        wp.element.createElement(ColorPalette, {
                            colors: coresPredefinidas,
                            value: corFundo,
                            onChange: function(cor) {
                                setAttributes({ corFundo: cor || '#000000' });
                            }
                        })
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { 
                            title: __('Ações', 'bebelume-arteduca'),
                            initialOpen: false
                        },
                        wp.element.createElement('div', { className: 'arteduca-button-group' },
                            wp.element.createElement(Button, {
                                variant: 'secondary',
                                onClick: duplicarBloco,
                                style: { marginBottom: '8px', width: '100%' }
                            }, '📋 Duplicar Bloco'),
                            wp.element.createElement(Button, {
                                variant: 'secondary',
                                isDestructive: true,
                                onClick: removerBloco,
                                style: { width: '100%' }
                            }, '🗑️ Remover Bloco')
                        )
                    )
                ),
                wp.element.createElement(
                    'div',
                    { className: 'bebelume-buscador-arteduca' },
                    // Barra colorida
                    wp.element.createElement('div', { className: 'bebelume-titulo-barra-colorida' }),
                    
                    // Título (igual ao Título ArtEduca)
                    wp.element.createElement(
                        'div',
                        { 
                            className: 'bebelume-titulo-conteudo',
                            style: { 
                                backgroundColor: corFundo,
                                textAlign: alignment || 'center'
                            }
                        },
                        wp.element.createElement(RichText, {
                            tagName: 'h2',
                            className: 'bebelume-titulo-texto',
                            value: titulo,
                            onChange: function(novoTitulo) {
                                setAttributes({ titulo: novoTitulo });
                            },
                            placeholder: __('Digite o título...', 'bebelume-arteduca')
                        })
                    ),
                    
                    // Container para blocos internos
                    wp.element.createElement(
                        'div',
                        { className: 'buscador-inner-blocks' },
                        wp.element.createElement(InnerBlocks, {
                            allowedBlocks: true, // Permite qualquer bloco
                            template: [
                                ['bebelume/filtro-arteduca']
                            ],
                            templateLock: false
                        })
                    )
                )
            );
        },

        save: function(props) {
            const { attributes } = props;
            const { titulo, corFundo, alignment } = attributes;

            return wp.element.createElement(
                'div',
                { className: 'bebelume-buscador-arteduca' },
                // Barra colorida
                wp.element.createElement('div', { className: 'bebelume-titulo-barra-colorida' }),
                
                // Título
                wp.element.createElement(
                    'div',
                    { 
                        className: 'bebelume-titulo-conteudo',
                        style: { 
                            backgroundColor: corFundo,
                            textAlign: alignment || 'center'
                        }
                    },
                    wp.element.createElement(RichText.Content, {
                        tagName: 'h2',
                        className: 'bebelume-titulo-texto',
                        value: titulo
                    })
                ),
                
                // Container para blocos internos
                wp.element.createElement(
                    'div',
                    { className: 'buscador-inner-blocks' },
                    wp.element.createElement(InnerBlocks.Content)
                )
            );
        }
    });
})(window.wp);
