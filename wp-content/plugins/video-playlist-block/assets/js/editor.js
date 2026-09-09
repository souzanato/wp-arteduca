// assets/js/editor.js

(function(blocks, element, blockEditor, components, i18n, apiFetch) {
    const el = element.createElement;
    const { registerBlockType } = blocks;
    const { useState, useEffect } = element;
    
    const { InspectorControls, MediaUpload } = blockEditor;
    const { PanelBody, TextControl, TextareaControl, Button, TabPanel, CheckboxControl, Spinner, Modal } = components;
    const { __ } = i18n;
    
    registerBlockType('video-playlist/playlist-block', {
        title: __('Lista de Vídeos', 'video-playlist-block'),
        icon: 'playlist-video',
        category: 'media',
        attributes: {
            playlistName: {
                type: 'string',
                default: 'Nova Lista de Reprodução'
            },
            playlistDescription: {
                type: 'string',
                default: ''
            },
            videos: {
                type: 'array',
                default: []
            },
            videoPostIds: {
                type: 'array',
                default: []
            },
            _lastModified: {
                type: 'number',
                default: 0
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { playlistName, playlistDescription, videos, videoPostIds } = attributes;
            
            // Estados para posts de vídeo
            const [videoPosts, setVideoPosts] = useState([]);
            const [loading, setLoading] = useState(false);
            const [error, setError] = useState(null);
            const [isModalOpen, setIsModalOpen] = useState(false);
            const [searchTerm, setSearchTerm] = useState('');
            
            // Busca posts de vídeo da API
            useEffect(() => {
                setLoading(true);
                apiFetch({ path: '/vpb/v1/video-posts' })
                    .then((posts) => {
                        setVideoPosts(posts);
                        setLoading(false);
                    })
                    .catch((err) => {
                        setError('Erro ao carregar vídeos');
                        setLoading(false);
                    });
            }, []);
            
            // Garante que arrays são sempre arrays
            const safeVideos = Array.isArray(videos) ? videos : [];
            const safeVideoPostIds = Array.isArray(videoPostIds) ? videoPostIds : [];
            
            // Funções do modo manual (mantidas do código original)
            function addVideo() {
                const newVideos = [...safeVideos, {
                    title: 'Novo Vídeo',
                    thumbnail: '',
                    videoUrl: '',
                    description: ''
                }];
                setAttributes({ videos: newVideos });
            }
            
            function updateVideo(index, field, value) {
                if (index < 0 || index >= safeVideos.length) return;
                
                const newVideos = [...safeVideos];
                if (!newVideos[index]) {
                    newVideos[index] = {
                        title: '',
                        thumbnail: '',
                        videoUrl: '',
                        description: ''
                    };
                }
                newVideos[index][field] = value || '';
                setAttributes({ videos: newVideos });
            }
            
            function removeVideo(index) {
                const newVideos = safeVideos.filter((_, i) => i !== index);
                setAttributes({ videos: newVideos });
            }
            
            function moveVideo(index, direction) {
                const newVideos = [...safeVideos];
                const newIndex = direction === 'up' ? index - 1 : index + 1;
                
                if (newIndex >= 0 && newIndex < safeVideos.length) {
                    [newVideos[index], newVideos[newIndex]] = [newVideos[newIndex], newVideos[index]];
                    setAttributes({ videos: newVideos });
                }
            }
            
            // Funções do modo posts
            function toggleVideoPost(postId) {
                const newIds = safeVideoPostIds.includes(postId)
                    ? safeVideoPostIds.filter(id => id !== postId)
                    : [...safeVideoPostIds, postId];
                setAttributes({ 
                    videoPostIds: newIds,
                    _lastModified: Date.now()
                });
            }
            
            function movePostUp(index) {
                if (index === 0) return;
                const newIds = [...safeVideoPostIds];
                [newIds[index - 1], newIds[index]] = [newIds[index], newIds[index - 1]];
                setAttributes({ 
                    videoPostIds: newIds,
                    _lastModified: Date.now()
                });
            }
            
            function movePostDown(index) {
                if (index === safeVideoPostIds.length - 1) return;
                const newIds = [...safeVideoPostIds];
                [newIds[index], newIds[index + 1]] = [newIds[index + 1], newIds[index]];
                setAttributes({ 
                    videoPostIds: newIds,
                    _lastModified: Date.now()
                });
            }
            
            // Renderiza painel de posts selecionados
            function renderSelectedPosts() {
                if (safeVideoPostIds.length === 0) {
                    return el('p', { style: { fontStyle: 'italic', color: '#757575' } },
                        __('Nenhum vídeo selecionado', 'video-playlist-block')
                    );
                }
                
                return safeVideoPostIds.map((postId, index) => {
                    const post = videoPosts.find(p => p.id === postId);
                    if (!post) return null;
                    
                    return el('div', {
                        key: postId,
                        style: {
                            padding: '10px',
                            marginBottom: '8px',
                            border: '1px solid #ddd',
                            borderRadius: '4px',
                            background: '#f9f9f9'
                        }
                    },
                        el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
                            el('strong', {}, post.title),
                            el('div', { style: { display: 'flex', gap: '5px' } },
                                index > 0 && el(Button, {
                                    onClick: () => movePostUp(index),
                                    isSmall: true
                                }, '↑'),
                                index < safeVideoPostIds.length - 1 && el(Button, {
                                    onClick: () => movePostDown(index),
                                    isSmall: true
                                }, '↓'),
                                el(Button, {
                                    onClick: () => toggleVideoPost(postId),
                                    isDestructive: true,
                                    isSmall: true
                                }, '✕')
                            )
                        )
                    );
                });
            }
            
            // Renderiza modal de seleção
            function renderVideoModal() {
                if (!isModalOpen) return null;
                
                // Filtra vídeos baseado na busca
                const filteredVideoPosts = videoPosts.filter(post => 
                    post.title.toLowerCase().includes(searchTerm.toLowerCase())
                );
                
                return el(Modal, {
                    title: __('Selecionar Vídeos', 'video-playlist-block'),
                    onRequestClose: () => {
                        // IMPORTANTE: Força nova referência e timestamp para Gutenberg detectar mudança
                        setAttributes({ 
                            videoPostIds: [...safeVideoPostIds],
                            _lastModified: Date.now()
                        });
                        setIsModalOpen(false);
                        setSearchTerm(''); // Limpa busca ao fechar
                    },
                    className: 'vpb-video-modal',
                    style: { maxWidth: '95vw', width: '1400px' }
                },
                    loading && el('div', { style: { textAlign: 'center', padding: '40px' } },
                        el(Spinner)
                    ),
                    error && el('p', { style: { color: 'red', padding: '20px' } }, error),
                    !loading && !error && videoPosts.length === 0 && el('div', { style: { padding: '40px', textAlign: 'center' } },
                        el('p', { style: { fontSize: '16px', color: '#666' } },
                            __('Nenhum post de vídeo encontrado.', 'video-playlist-block')
                        ),
                        el('p', { style: { fontSize: '14px', color: '#999' } },
                            __('Crie posts do tipo "Vídeo" primeiro.', 'video-playlist-block')
                        )
                    ),
                    !loading && videoPosts.length > 0 && el('div', {},
                        // Header: Busca + Contador + Botão
                        el('div', {
                            style: {
                                padding: '20px 30px',
                                borderBottom: '1px solid #ddd',
                                background: '#fff',
                                display: 'flex',
                                alignItems: 'center',
                                gap: '20px'
                            }
                        },
                            // Campo de busca (cresce)
                            el('div', {
                                style: {
                                    flex: '1'
                                }
                            },
                                el(TextControl, {
                                    placeholder: __('🔍 Buscar vídeos...', 'video-playlist-block'),
                                    value: searchTerm,
                                    onChange: (value) => setSearchTerm(value),
                                    style: {
                                        fontSize: '15px',
                                        padding: '10px 15px',
                                        margin: '0'
                                    }
                                })
                            ),
                            
                            // Contador de vídeos
                            el('div', { 
                                style: { 
                                    color: '#2271b1', 
                                    fontSize: '15px',
                                    fontWeight: '600',
                                    whiteSpace: 'nowrap'
                                } 
                            },
                                `✓ ${safeVideoPostIds.length} vídeo(s)`
                            ),
                            
                            // Botão Concluído
                            el(Button, {
                                isPrimary: true,
                                onClick: () => {
                                    // IMPORTANTE: Força nova referência e timestamp para Gutenberg detectar mudança
                                    setAttributes({ 
                                        videoPostIds: [...safeVideoPostIds],
                                        _lastModified: Date.now()
                                    });
                                    setIsModalOpen(false);
                                    setSearchTerm(''); // Limpa busca ao concluir
                                },
                                style: {
                                    padding: '10px 24px',
                                    height: 'auto',
                                    whiteSpace: 'nowrap'
                                }
                            }, __('✓ Concluído', 'video-playlist-block'))
                        ),
                        
                        // Grid de vídeos
                        el('div', { 
                            style: { 
                                display: 'grid', 
                                gridTemplateColumns: 'repeat(5, 1fr)',
                                gap: '20px',
                                padding: '30px',
                                maxHeight: '65vh',
                                overflowY: 'auto',
                                background: '#f5f5f5'
                            } 
                        },
                            filteredVideoPosts.length === 0 ? 
                                el('div', {
                                    style: {
                                        gridColumn: '1 / -1',
                                        textAlign: 'center',
                                        padding: '40px',
                                        color: '#666'
                                    }
                                },
                                    el('p', { style: { fontSize: '16px', marginBottom: '10px' } }, '🔍'),
                                    el('p', { style: { fontSize: '14px' } }, 
                                        __('Nenhum vídeo encontrado com esse termo.', 'video-playlist-block')
                                    )
                                ) :
                            filteredVideoPosts.map((post) => {
                                const isSelected = safeVideoPostIds.includes(post.id);
                                
                                return el('div', {
                                    key: post.id,
                                    onClick: () => toggleVideoPost(post.id),
                                    style: {
                                        border: isSelected ? '4px solid #2271b1' : '2px solid #ddd',
                                        borderRadius: '8px',
                                        cursor: 'pointer',
                                        overflow: 'hidden',
                                        background: '#fff',
                                        transition: 'all 0.2s',
                                        position: 'relative',
                                        boxShadow: isSelected ? '0 4px 12px rgba(34, 113, 177, 0.3)' : '0 2px 4px rgba(0,0,0,0.1)',
                                        transform: isSelected ? 'scale(0.98)' : 'scale(1)'
                                    },
                                    onMouseEnter: (e) => {
                                        if (!isSelected) {
                                            e.currentTarget.style.boxShadow = '0 4px 8px rgba(0,0,0,0.15)';
                                            e.currentTarget.style.transform = 'translateY(-2px)';
                                        }
                                    },
                                    onMouseLeave: (e) => {
                                        if (!isSelected) {
                                            e.currentTarget.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                                            e.currentTarget.style.transform = 'translateY(0)';
                                        }
                                    }
                                },
                                    // Thumbnail
                                    post.thumbnail ? el('img', {
                                        src: post.thumbnail,
                                        alt: post.title,
                                        style: {
                                            width: '100%',
                                            height: '160px',
                                            objectFit: 'cover',
                                            display: 'block'
                                        }
                                    }) : el('div', {
                                        style: {
                                            width: '100%',
                                            height: '160px',
                                            background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontSize: '48px'
                                        }
                                    }, '🎬'),
                                    
                                    // Checkbox overlay
                                    el('div', {
                                        style: {
                                            position: 'absolute',
                                            top: '12px',
                                            right: '12px',
                                            width: '32px',
                                            height: '32px',
                                            borderRadius: '50%',
                                            background: isSelected ? '#2271b1' : 'rgba(255,255,255,0.95)',
                                            border: isSelected ? 'none' : '2px solid #ccc',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            color: isSelected ? '#fff' : '#999',
                                            fontWeight: 'bold',
                                            fontSize: '18px',
                                            boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
                                            transition: 'all 0.2s'
                                        }
                                    }, isSelected ? '✓' : ''),
                                    
                                    // Título e data
                                    el('div', {
                                        style: {
                                            padding: '12px',
                                            background: '#fff'
                                        }
                                    },
                                        el('div', {
                                            style: {
                                                fontWeight: '600',
                                                fontSize: '14px',
                                                marginBottom: '6px',
                                                overflow: 'hidden',
                                                textOverflow: 'ellipsis',
                                                whiteSpace: 'nowrap',
                                                color: '#1e1e1e'
                                            },
                                            title: post.title
                                        }, post.title),
                                        el('div', {
                                            style: {
                                                fontSize: '12px',
                                                color: '#757575',
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: '4px'
                                            }
                                        }, 
                                            el('span', {}, '📅'),
                                            post.date
                                        )
                                    )
                                );
                            })
                        )
                    )
                );
            }
            
            return el('div', { className: 'video-playlist-editor' },
                el(InspectorControls, {},
                    el(PanelBody, { 
                        title: __('Configurações da Lista', 'video-playlist-block'), 
                        initialOpen: true 
                    },
                        el(TextControl, {
                            label: __('Nome da Lista', 'video-playlist-block'),
                            value: playlistName || '',
                            onChange: (value) => setAttributes({ playlistName: value || '' })
                        }),
                        el(TextareaControl, {
                            label: __('Descrição da Lista', 'video-playlist-block'),
                            value: playlistDescription || '',
                            onChange: (value) => setAttributes({ playlistDescription: value || '' })
                        })
                    ),
                    
                    el(PanelBody, {
                        title: __('Vídeos', 'video-playlist-block'),
                        initialOpen: true
                    },
                        el(TabPanel, {
                            className: 'video-mode-tabs',
                            activeClass: 'is-active',
                            tabs: [
                                {
                                    name: 'posts',
                                    title: __('📂 Selecionar Posts', 'video-playlist-block'),
                                    className: 'tab-posts'
                                },
                                {
                                    name: 'manual',
                                    title: __('✏️ Manual', 'video-playlist-block'),
                                    className: 'tab-manual'
                                }
                            ]
                        }, (tab) => {
                            if (tab.name === 'posts') {
                                return el('div', { className: 'posts-selector' },
                                    el('h4', {}, __('Posts Selecionados:', 'video-playlist-block')),
                                    renderSelectedPosts(),
                                    
                                    el(Button, {
                                        onClick: () => setIsModalOpen(true),
                                        isPrimary: true,
                                        style: { 
                                            marginTop: '15px',
                                            width: '100%',
                                            justifyContent: 'center'
                                        }
                                    }, __('➕ Adicionar ou Remover Vídeos', 'video-playlist-block'))
                                );
                            } else {
                                // Modo manual (código original)
                                return el('div', { className: 'manual-videos' },
                                    safeVideos.map((video, index) => {
                                        const safeVideo = video || {};
                                        const videoTitle = safeVideo.title || `Vídeo ${index + 1}`;
                                        
                                        return el('div', {
                                            key: `manual-${index}`,
                                            style: {
                                                padding: '15px',
                                                marginBottom: '15px',
                                                border: '1px solid #ddd',
                                                borderRadius: '4px'
                                            }
                                        },
                                            el(TextControl, {
                                                label: __('Título', 'video-playlist-block'),
                                                value: videoTitle,
                                                onChange: (value) => updateVideo(index, 'title', value)
                                            }),
                                            el(TextControl, {
                                                label: __('URL do Vídeo', 'video-playlist-block'),
                                                value: safeVideo.videoUrl || '',
                                                onChange: (value) => updateVideo(index, 'videoUrl', value)
                                            }),
                                            el('div', { style: { display: 'flex', gap: '5px', marginTop: '10px' } },
                                                index > 0 && el(Button, {
                                                    onClick: () => moveVideo(index, 'up'),
                                                    isSmall: true
                                                }, '↑'),
                                                index < safeVideos.length - 1 && el(Button, {
                                                    onClick: () => moveVideo(index, 'down'),
                                                    isSmall: true
                                                }, '↓'),
                                                el(Button, {
                                                    onClick: () => removeVideo(index),
                                                    isDestructive: true,
                                                    isSmall: true
                                                }, __('Remover', 'video-playlist-block'))
                                            )
                                        );
                                    }),
                                    el(Button, {
                                        onClick: addVideo,
                                        isPrimary: true,
                                        style: { marginTop: '10px' }
                                    }, __('➕ Adicionar Vídeo', 'video-playlist-block'))
                                );
                            }
                        })
                    )
                ),
                
                // Modal
                renderVideoModal(),
                
                // Preview
                el('div', { className: 'video-playlist-preview' },
                    el('h3', {}, playlistName || 'Nova Lista de Reprodução'),
                    playlistDescription && el('p', {}, playlistDescription),
                    el('p', { style: { color: '#666', fontSize: '14px' } },
                        safeVideoPostIds.length > 0 
                            ? `${safeVideoPostIds.length} posts selecionados`
                            : safeVideos.length > 0 
                                ? `${safeVideos.length} vídeos manuais`
                                : 'Nenhum vídeo adicionado'
                    )
                )
            );
        },
        
        save: function() {
            return null;
        }
    });
    
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor || window.wp.editor,
    window.wp.components,
    window.wp.i18n,
    window.wp.apiFetch
);