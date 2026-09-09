(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { RichText, InspectorControls, BlockControls, AlignmentToolbar } = wp.blockEditor;
    const { PanelBody, ColorPalette, Button } = wp.components;
    const { __ } = wp.i18n;

    // Paleta de cores predefinidas
    const coresPredefinidas = [
        { name: 'Azul Escuro', color: '#2A4582' },
        { name: 'Roxo', color: '#5C4F93' },
        { name: 'Vermelho', color: '#D75F4D' },
        { name: 'Amarelo', color: '#EAB35E' },
        { name: 'Preto', color: '#000000' }
    ];

    registerBlockType('bebelume-arteduca/titulo-arteduca', {
        title: __('Título ArtEduca', 'bebelume-arteduca'),
        icon: 'heading',
        category: 'bebelume-arteduca',
        
        attributes: {
            titulo: {
                type: 'string',
                default: 'TÍTULO'
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

            // Função para adicionar bloco antes
            const adicionarAntes = function() {
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/titulo-arteduca');
                const index = wp.data.select('core/block-editor').getBlockIndex(clientId);
                wp.data.dispatch('core/block-editor').insertBlock(novoBloco, index);
            };

            // Função para adicionar bloco depois
            const adicionarDepois = function() {
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/titulo-arteduca');
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
                    { className: 'bebelume-titulo-arteduca' },
                    wp.element.createElement('div', { className: 'bebelume-titulo-barra-colorida' }),
                    wp.element.createElement(
                        'div',
                        { 
                            className: 'bebelume-titulo-conteudo',
                            style: (function() {
                                const style = { backgroundColor: corFundo };
                                if (alignment && alignment !== 'left') {
                                    style.textAlign = alignment;
                                }
                                return style;
                            })()
                        },
                        wp.element.createElement(RichText, {
                            tagName: 'h2',
                            value: titulo,
                            onChange: function(valor) {
                                setAttributes({ titulo: valor });
                            },
                            placeholder: __('Digite o título...', 'bebelume-arteduca'),
                            className: 'bebelume-titulo-texto',
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
                            inlineToolbar: true
                        })
                    )
                )
            );
        },

        save: function(props) {
            const { titulo, corFundo, alignment } = props.attributes;
            
            // Só adiciona text-align se for diferente de 'left' (para compatibilidade com blocos antigos)
            const style = { backgroundColor: corFundo };
            if (alignment && alignment !== 'left') {
                style.textAlign = alignment;
            }

            return wp.element.createElement(
                'div',
                { className: 'bebelume-titulo-arteduca' },
                wp.element.createElement('div', { className: 'bebelume-titulo-barra-colorida' }),
                wp.element.createElement(
                    'div',
                    { 
                        className: 'bebelume-titulo-conteudo',
                        style: style
                    },
                    wp.element.createElement(RichText.Content, {
                        tagName: 'h2',
                        value: titulo,
                        className: 'bebelume-titulo-texto'
                    })
                )
            );
        }
    });
})(window.wp);
