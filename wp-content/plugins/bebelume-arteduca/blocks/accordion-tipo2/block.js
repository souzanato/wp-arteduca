(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { RichText, InspectorControls, InnerBlocks, BlockControls, AlignmentToolbar } = wp.blockEditor;
    const { PanelBody, ColorPalette, TextareaControl, Button } = wp.components;
    const { __ } = wp.i18n;
    const { useState, useEffect } = wp.element;

    // Paleta de cores
    const coresPredefinidas = [
        { name: 'Cinza Claro', color: '#D1D1D1' },
        { name: 'Azul Escuro', color: '#2A4582' },
        { name: 'Roxo', color: '#5C4F93' },
        { name: 'Vermelho', color: '#D75F4D' },
        { name: 'Amarelo', color: '#EAB35E' }
    ];

    registerBlockType('bebelume-arteduca/accordion-tipo2', {
        title: __('Accordion ArtEduca - Tipo 2', 'bebelume-arteduca'),
        icon: 'minus',
        category: 'bebelume-arteduca',
        
        attributes: {
            titulo: {
                type: 'string',
                default: 'Clique para expandir'
            },
            corBotao: {
                type: 'string',
                default: '#D1D1D1'
            },
            iconeHtml: {
                type: 'string',
                default: ''
            },
            uniqueId: {
                type: 'string',
                default: ''
            },
            alignment: {
                type: 'string'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { titulo, corBotao, iconeHtml, uniqueId, alignment } = attributes;
            const [expanded, setExpanded] = useState(true); // Começa aberto no editor

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
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/accordion-tipo2');
                const index = wp.data.select('core/block-editor').getBlockIndex(clientId);
                wp.data.dispatch('core/block-editor').insertBlock(novoBloco, index);
            };

            // Função para adicionar bloco depois
            const adicionarDepois = function() {
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/accordion-tipo2');
                const index = wp.data.select('core/block-editor').getBlockIndex(clientId);
                wp.data.dispatch('core/block-editor').insertBlock(novoBloco, index + 1);
            };

            // Gera ID único
            useEffect(function() {
                if (!uniqueId) {
                    setAttributes({ uniqueId: 'accordion-tipo2-' + clientId });
                }
            }, []);

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
                        { title: __('Configurações', 'bebelume-arteduca') },
                        wp.element.createElement('p', null, 
                            wp.element.createElement('strong', null, __('Cor do Botão:', 'bebelume-arteduca'))
                        ),
                        wp.element.createElement(ColorPalette, {
                            colors: coresPredefinidas,
                            value: corBotao,
                            onChange: function(cor) {
                                setAttributes({ corBotao: cor || '#D1D1D1' });
                            }
                        }),
                        wp.element.createElement('br'),
                        wp.element.createElement(TextareaControl, {
                            label: __('Ícone HTML (opcional)', 'bebelume-arteduca'),
                            help: __('Cole o código HTML completo do ícone. Exemplo Font Awesome: <i class="fas fa-star"></i> | Procure ícones em: fontawesome.com/icons', 'bebelume-arteduca'),
                            value: iconeHtml,
                            onChange: function(valor) {
                                setAttributes({ iconeHtml: valor });
                            },
                            placeholder: '<i class="fas fa-baby"></i>'
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
                        className: 'bebelume-accordion-tipo2',
                        style: (function() {
                            const style = {};
                            if (alignment && alignment !== 'left') {
                                style.textAlign = alignment;
                            }
                            return Object.keys(style).length > 0 ? style : undefined;
                        })()
                    },
                    wp.element.createElement(
                        'button',
                        {
                            className: 'bebelume-accordion-botao-tipo2',
                            style: { backgroundColor: corBotao },
                            onClick: function() { setExpanded(!expanded); },
                            type: 'button'
                        },
                        iconeHtml ? wp.element.createElement('span', {
                            dangerouslySetInnerHTML: { __html: iconeHtml },
                            className: 'bebelume-accordion-icone-custom'
                        }) : null,
                        wp.element.createElement(RichText, {
                            tagName: 'span',
                            value: titulo,
                            onChange: function(valor) {
                                setAttributes({ titulo: valor });
                            },
                            placeholder: __('Digite o título...', 'bebelume-arteduca'),
                            className: 'bebelume-accordion-titulo-tipo2',
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
                        })
                    ),
                    wp.element.createElement(
                        'div',
                        { className: 'bebelume-accordion-conteudo-tipo2' + (expanded ? ' expandido' : '') },
                        wp.element.createElement(
                            'div',
                            { className: 'bebelume-accordion-inner-tipo2' },
                            wp.element.createElement(InnerBlocks, {
                                templateLock: false,
                                template: [
                                    ['core/paragraph', { placeholder: 'Digite o conteúdo aqui ou cole seu texto...' }]
                                ]
                            })
                        )
                    )
                )
            );
        },

        save: function(props) {
            const { titulo, corBotao, iconeHtml, uniqueId, alignment } = props.attributes;
            const accordionId = uniqueId || 'accordion-tipo2-' + Date.now();
            
            // Só adiciona text-align se for diferente de 'left'
            const containerStyle = {};
            if (alignment && alignment !== 'left') {
                containerStyle.textAlign = alignment;
            }

            return wp.element.createElement(
                'div',
                { 
                    className: 'bebelume-accordion-tipo2',
                    style: Object.keys(containerStyle).length > 0 ? containerStyle : undefined
                },
                wp.element.createElement(
                    'button',
                    {
                        className: 'bebelume-accordion-botao-tipo2',
                        style: { backgroundColor: corBotao },
                        type: 'button',
                        'data-target': accordionId
                    },
                    iconeHtml ? wp.element.createElement('span', {
                        dangerouslySetInnerHTML: { __html: iconeHtml },
                        className: 'bebelume-accordion-icone-custom'
                    }) : null,
                    wp.element.createElement(RichText.Content, {
                        tagName: 'span',
                        value: titulo,
                        className: 'bebelume-accordion-titulo-tipo2'
                    })
                ),
                wp.element.createElement(
                    'div',
                    { 
                        id: accordionId,
                        className: 'bebelume-accordion-conteudo-tipo2'
                    },
                    wp.element.createElement(
                        'div',
                        { className: 'bebelume-accordion-inner-tipo2' },
                        wp.element.createElement(InnerBlocks.Content)
                    )
                )
            );
        }
    });
})(window.wp);
