(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { RichText, InspectorControls, MediaUpload, BlockControls, AlignmentToolbar } = wp.blockEditor;
    const { PanelBody, ColorPalette, Button } = wp.components;
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

    // Função para formatar tamanho do arquivo
    function formatarTamanho(bytes) {
        if (!bytes) return '';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    // Função para obter extensão do arquivo
    function obterExtensao(url) {
        if (!url) return '';
        const partes = url.split('.');
        return partes[partes.length - 1].toUpperCase();
    }

    registerBlockType('bebelume-arteduca/documento', {
        title: __('Bebelume Doc Download', 'bebelume-arteduca'),
        icon: 'media-document',
        category: 'bebelume-arteduca',
        
        attributes: {
            titulo: {
                type: 'string',
                default: 'Documento para Download'
            },
            descricao: {
                type: 'string',
                default: 'Clique no botão abaixo para fazer o download do documento.'
            },
            documentoId: {
                type: 'number',
                default: 0
            },
            documentoUrl: {
                type: 'string',
                default: ''
            },
            documentoNome: {
                type: 'string',
                default: ''
            },
            documentoTamanho: {
                type: 'string',
                default: ''
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
            }
        },

        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { titulo, descricao, documentoId, documentoUrl, documentoNome, documentoTamanho, corFundo, corBotao, alignment } = attributes;

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
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/documento');
                const index = wp.data.select('core/block-editor').getBlockIndex(clientId);
                wp.data.dispatch('core/block-editor').insertBlock(novoBloco, index);
            };

            // Função para adicionar bloco depois
            const adicionarDepois = function() {
                const novoBloco = wp.blocks.createBlock('bebelume-arteduca/documento');
                const index = wp.data.select('core/block-editor').getBlockIndex(clientId);
                wp.data.dispatch('core/block-editor').insertBlock(novoBloco, index + 1);
            };

            // Função para remover o documento
            const removerDocumento = function() {
                if (window.confirm('Tem certeza que deseja remover o documento anexado?')) {
                    setAttributes({
                        documentoId: 0,
                        documentoUrl: '',
                        documentoNome: '',
                        documentoTamanho: ''
                    });
                }
            };

            // Função quando seleciona um documento
            const onSelectDocument = function(media) {
                setAttributes({
                    documentoId: media.id,
                    documentoUrl: media.url,
                    documentoNome: media.filename || media.title,
                    documentoTamanho: formatarTamanho(media.filesizeInBytes)
                });
            };

            // Inicializa Lucide icons
            useEffect(function() {
                if (typeof lucide !== 'undefined') {
                    setTimeout(function() {
                        lucide.createIcons();
                    }, 100);
                }
            }, [documentoId]);

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
                        className: 'bebelume-documento-box',
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
                        { className: 'bebelume-documento-icone' },
                        wp.element.createElement('i', {
                            'data-lucide': 'file-text',
                            style: { width: '48px', height: '48px', color: corBotao }
                        })
                    ),
                    wp.element.createElement(RichText, {
                        tagName: 'h3',
                        value: titulo,
                        onChange: function(valor) {
                            setAttributes({ titulo: valor });
                        },
                        placeholder: __('Digite o título do documento...', 'bebelume-arteduca'),
                        className: 'bebelume-documento-titulo',
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
                        placeholder: __('Digite a descrição do documento...', 'bebelume-arteduca'),
                        className: 'bebelume-documento-descricao',
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
                    documentoUrl ? 
                        wp.element.createElement(
                            'div',
                            { className: 'bebelume-documento-preview' },
                            wp.element.createElement(
                                'div',
                                { className: 'bebelume-documento-info' },
                                wp.element.createElement('span', { className: 'bebelume-documento-extensao' }, obterExtensao(documentoUrl)),
                                wp.element.createElement(
                                    'div',
                                    { className: 'bebelume-documento-detalhes' },
                                    wp.element.createElement('strong', null, documentoNome),
                                    documentoTamanho ? wp.element.createElement('span', { className: 'bebelume-documento-tamanho' }, documentoTamanho) : null
                                )
                            ),
                            wp.element.createElement(
                                'div',
                                { className: 'bebelume-documento-acoes' },
                                wp.element.createElement(MediaUpload, {
                                    onSelect: onSelectDocument,
                                    allowedTypes: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'text/plain'],
                                    value: documentoId,
                                    render: function(obj) {
                                        return wp.element.createElement(Button, {
                                            onClick: obj.open,
                                            variant: 'secondary',
                                            isSmall: true
                                        }, __('Trocar', 'bebelume-arteduca'));
                                    }
                                }),
                                wp.element.createElement(Button, {
                                    onClick: removerDocumento,
                                    variant: 'secondary',
                                    isDestructive: true,
                                    isSmall: true
                                }, __('Remover', 'bebelume-arteduca'))
                            )
                        ) : 
                        wp.element.createElement(MediaUpload, {
                            onSelect: onSelectDocument,
                            allowedTypes: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'text/plain'],
                            value: documentoId,
                            render: function(obj) {
                                return wp.element.createElement(
                                    'div',
                                    { className: 'bebelume-documento-upload' },
                                    wp.element.createElement(Button, {
                                        onClick: obj.open,
                                        variant: 'primary',
                                        style: { backgroundColor: corBotao, borderColor: corBotao }
                                    }, 
                                    wp.element.createElement('i', { 
                                        'data-lucide': 'upload',
                                        style: { width: '16px', height: '16px', marginRight: '8px' }
                                    }),
                                    __('Fazer Upload do Documento', 'bebelume-arteduca')
                                    )
                                );
                            }
                        })
                )
            );
        },

        deprecated: [
            {
                attributes: {
                    titulo: {
                        type: 'string',
                        default: 'Documento para Download'
                    },
                    descricao: {
                        type: 'string',
                        default: 'Clique no botão abaixo para fazer o download do documento.'
                    },
                    documentoId: {
                        type: 'number',
                        default: 0
                    },
                    documentoUrl: {
                        type: 'string',
                        default: ''
                    },
                    documentoNome: {
                        type: 'string',
                        default: ''
                    },
                    documentoTamanho: {
                        type: 'string',
                        default: ''
                    },
                    corFundo: {
                        type: 'string',
                        default: '#F3F0EB'
                    },
                    corBotao: {
                        type: 'string',
                        default: '#2A4582'
                    }
                },
                save: function(props) {
                    const { titulo, descricao, documentoUrl, documentoNome, documentoTamanho, corFundo, corBotao } = props.attributes;

                    if (!documentoUrl) {
                        return null;
                    }

                    return wp.element.createElement(
                        'div',
                        { 
                            className: 'bebelume-documento-box',
                            style: { backgroundColor: corFundo }
                        },
                        wp.element.createElement(
                            'div',
                            { className: 'bebelume-documento-icone' },
                            wp.element.createElement('i', {
                                'data-lucide': 'file-text',
                                style: { width: '48px', height: '48px', color: corBotao }
                            })
                        ),
                        wp.element.createElement(RichText.Content, {
                            tagName: 'h3',
                            value: titulo,
                            className: 'bebelume-documento-titulo'
                        }),
                        wp.element.createElement(RichText.Content, {
                            tagName: 'p',
                            value: descricao,
                            className: 'bebelume-documento-descricao'
                        }),
                        wp.element.createElement(
                            'div',
                            { className: 'bebelume-documento-info-frontend' },
                            wp.element.createElement('span', { className: 'bebelume-documento-extensao' }, obterExtensao(documentoUrl)),
                            wp.element.createElement('span', { className: 'bebelume-documento-nome' }, documentoNome),
                            documentoTamanho ? wp.element.createElement('span', { className: 'bebelume-documento-tamanho' }, documentoTamanho) : null
                        ),
                        wp.element.createElement(
                            'a',
                            {
                                href: documentoUrl,
                                download: documentoNome,
                                className: 'bebelume-documento-botao',
                                style: { backgroundColor: corBotao }
                            },
                            wp.element.createElement('i', {
                                'data-lucide': 'download',
                                style: { width: '20px', height: '20px' }
                            }),
                            __('Baixar Documento', 'bebelume-arteduca')
                        )
                    );
                }
            }
        ],

        save: function(props) {
            const { titulo, descricao, documentoUrl, documentoNome, documentoTamanho, corFundo, corBotao, alignment } = props.attributes;

            if (!documentoUrl) {
                return null;
            }

            return wp.element.createElement(
                'div',
                { 
                    className: 'bebelume-documento-box',
                    style: { 
                        backgroundColor: corFundo,
                        textAlign: alignment || 'left'
                    }
                },
                wp.element.createElement(
                    'div',
                    { className: 'bebelume-documento-icone' },
                    wp.element.createElement('i', {
                        'data-lucide': 'file-text',
                        style: { width: '48px', height: '48px', color: corBotao }
                    })
                ),
                wp.element.createElement(RichText.Content, {
                    tagName: 'h3',
                    value: titulo,
                    className: 'bebelume-documento-titulo'
                }),
                wp.element.createElement(RichText.Content, {
                    tagName: 'p',
                    value: descricao,
                    className: 'bebelume-documento-descricao'
                }),
                wp.element.createElement(
                    'div',
                    { className: 'bebelume-documento-info-frontend' },
                    wp.element.createElement('span', { className: 'bebelume-documento-extensao' }, obterExtensao(documentoUrl)),
                    wp.element.createElement('span', { className: 'bebelume-documento-nome' }, documentoNome),
                    documentoTamanho ? wp.element.createElement('span', { className: 'bebelume-documento-tamanho' }, documentoTamanho) : null
                ),
                wp.element.createElement(
                    'a',
                    {
                        href: documentoUrl,
                        download: documentoNome,
                        className: 'bebelume-documento-botao',
                        style: { backgroundColor: corBotao }
                    },
                    wp.element.createElement('i', {
                        'data-lucide': 'download',
                        style: { width: '20px', height: '20px' }
                    }),
                    __('Baixar Documento', 'bebelume-arteduca')
                )
            );
        }
    });
})(window.wp);
