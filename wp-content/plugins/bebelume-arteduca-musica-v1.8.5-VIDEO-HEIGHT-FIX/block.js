(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var Button = wp.components.Button;
    var ToggleControl = wp.components.ToggleControl;
    var MediaUpload = wp.blockEditor.MediaUpload;
    var __ = wp.i18n.__;
    var useState = wp.element.useState;

    registerBlockType('bebelume/arteduca-musica', {
        title: __('Bebelume ArtEduca Música', 'bebelume-arteduca-musica'),
        icon: 'playlist-audio',
        category: 'media',
        supports: {
            html: false,
            align: false
        },
        attributes: {
            playlistTitle: {
                type: 'string',
                default: 'Minha Playlist'
            },
            tracks: {
                type: 'array',
                default: []
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            // Estados
            var modalState = useState(false);
            var showModal = modalState[0];
            var setShowModal = modalState[1];
            
            var videosState = useState([]);
            var videos = videosState[0];
            var setVideos = videosState[1];
            
            var loadingState = useState(false);
            var loading = loadingState[0];
            var setLoading = loadingState[1];
            
            var searchState = useState('');
            var searchTerm = searchState[0];
            var setSearchTerm = searchState[1];

            // Função para buscar vídeos
            function fetchVideos() {
                setLoading(true);
                setShowModal(true);
                setSearchTerm('');
                
                fetch(bebelumeMusicaVideoApiUrl)
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
                var newTrack = {
                    id: Date.now(),
                    url: video.videoUrl || '',
                    title: video.title || '',
                    author: '', // Deixa vazio
                    thumbnail: video.thumbnail || '',
                    showVideo: false // Padrão: não mostrar vídeo
                };
                
                var newTracks = attributes.tracks.concat([newTrack]);
                setAttributes({ tracks: newTracks });
                setShowModal(false);
                setSearchTerm('');
            }

            // Função para adicionar track manual
            function addManualTrack() {
                var newTrack = {
                    id: Date.now(),
                    url: '',
                    title: 'Nova Música',
                    author: '',
                    thumbnail: '',
                    showVideo: false
                };
                
                var newTracks = attributes.tracks.concat([newTrack]);
                setAttributes({ tracks: newTracks });
            }

            // Função para atualizar track
            function updateTrack(trackId, field, value) {
                var newTracks = attributes.tracks.map(function(track) {
                    if (track.id === trackId) {
                        var updatedTrack = Object.assign({}, track);
                        updatedTrack[field] = value;
                        return updatedTrack;
                    }
                    return track;
                });
                setAttributes({ tracks: newTracks });
            }

            // Função para remover track
            function removeTrack(trackId) {
                var newTracks = attributes.tracks.filter(function(track) {
                    return track.id !== trackId;
                });
                setAttributes({ tracks: newTracks });
            }

            // Função para mover track
            function moveTrack(index, direction) {
                var newTracks = [].concat(attributes.tracks);
                var newIndex = index + direction;
                
                if (newIndex >= 0 && newIndex < newTracks.length) {
                    var temp = newTracks[index];
                    newTracks[index] = newTracks[newIndex];
                    newTracks[newIndex] = temp;
                    setAttributes({ tracks: newTracks });
                }
            }

            return [
                // Inspector Controls
                el(InspectorControls, {},
                    el(PanelBody, { 
                        title: __('🎵 Configurações da Playlist', 'bebelume-arteduca-musica'), 
                        initialOpen: true 
                    },
                        el(TextControl, {
                            label: __('Título da Playlist', 'bebelume-arteduca-musica'),
                            value: attributes.playlistTitle,
                            onChange: function(val) { setAttributes({ playlistTitle: val }); },
                            help: __('Nome que aparece no topo da playlist', 'bebelume-arteduca-musica')
                        })
                    )
                ),

                // Editor View
                el('div', { 
                    className: 'bebelume-music-editor',
                    style: {
                        padding: '20px',
                        background: '#f5f5f5',
                        borderRadius: '8px',
                        minHeight: '300px'
                    }
                },
                    // Título da Playlist
                    el('h2', { 
                        style: { 
                            margin: '0 0 20px',
                            fontSize: '24px',
                            color: '#1e1e1e'
                        } 
                    }, '🎵 ' + attributes.playlistTitle),
                    
                    // Botões de Ação
                    el('div', { 
                        style: { 
                            display: 'flex', 
                            gap: '12px',
                            marginBottom: '20px'
                        } 
                    },
                        el(Button, {
                            onClick: fetchVideos,
                            variant: 'primary',
                            style: {
                                background: '#1db954',
                                border: 'none'
                            }
                        }, '🎬 Importar dos Posts'),
                        
                        el(Button, {
                            onClick: addManualTrack,
                            variant: 'secondary'
                        }, '➕ Adicionar Manualmente')
                    ),
                    
                    // Lista de Tracks
                    attributes.tracks.length === 0 ?
                        el('div', {
                            style: {
                                textAlign: 'center',
                                padding: '40px',
                                color: '#666',
                                background: 'white',
                                borderRadius: '8px'
                            }
                        },
                            el('div', { style: { fontSize: '48px', marginBottom: '16px' } }, '🎵'),
                            el('p', {}, 'Nenhuma música na playlist'),
                            el('p', { style: { fontSize: '14px', marginTop: '8px' } }, 
                                'Importe dos posts ou adicione manualmente'
                            )
                        ) :
                        el('div', { 
                            style: {
                                background: 'white',
                                borderRadius: '8px',
                                padding: '16px'
                            }
                        },
                            el('h3', { 
                                style: { 
                                    margin: '0 0 16px',
                                    fontSize: '16px',
                                    color: '#666'
                                } 
                            }, '📋 ' + attributes.tracks.length + ' música' + (attributes.tracks.length !== 1 ? 's' : '')),
                            
                            attributes.tracks.map(function(track, index) {
                                return el('div', {
                                    key: track.id,
                                    style: {
                                        background: '#f9f9f9',
                                        padding: '16px',
                                        marginBottom: '12px',
                                        borderRadius: '8px',
                                        border: '1px solid #ddd'
                                    }
                                },
                                    // Header do Track
                                    el('div', {
                                        style: {
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            alignItems: 'center',
                                            marginBottom: '12px'
                                        }
                                    },
                                        el('strong', { 
                                            style: { color: '#1db954' } 
                                        }, (index + 1) + '. ' + (track.title || 'Sem título')),
                                        
                                        el('div', { style: { display: 'flex', gap: '8px' } },
                                            index > 0 ? 
                                                el(Button, {
                                                    onClick: function() { moveTrack(index, -1); },
                                                    isSmall: true,
                                                    variant: 'secondary'
                                                }, '↑') : null,
                                            
                                            index < attributes.tracks.length - 1 ?
                                                el(Button, {
                                                    onClick: function() { moveTrack(index, 1); },
                                                    isSmall: true,
                                                    variant: 'secondary'
                                                }, '↓') : null,
                                            
                                            el(Button, {
                                                onClick: function() { removeTrack(track.id); },
                                                isSmall: true,
                                                isDestructive: true
                                            }, '🗑️')
                                        )
                                    ),
                                    
                                    // Campos do Track
                                    el('div', { style: { display: 'grid', gap: '12px' } },
                                        el(TextControl, {
                                            label: 'URL do Vídeo/Áudio',
                                            value: track.url,
                                            onChange: function(val) { updateTrack(track.id, 'url', val); },
                                            placeholder: 'https://...'
                                        }),
                                        
                                        el(TextControl, {
                                            label: 'Título da Música',
                                            value: track.title,
                                            onChange: function(val) { updateTrack(track.id, 'title', val); },
                                            placeholder: 'Nome da música'
                                        }),
                                        
                                        el(TextControl, {
                                            label: 'Autor/Artista',
                                            value: track.author,
                                            onChange: function(val) { updateTrack(track.id, 'author', val); },
                                            placeholder: 'Nome do artista'
                                        }),
                                        
                                        // Thumbnail
                                        el('div', {},
                                            el('label', { 
                                                style: { 
                                                    display: 'block', 
                                                    marginBottom: '8px',
                                                    fontWeight: '600',
                                                    fontSize: '11px'
                                                }
                                            }, 'THUMBNAIL/CAPA'),
                                            
                                            track.thumbnail ?
                                                el('div', {},
                                                    el('img', {
                                                        src: track.thumbnail,
                                                        alt: track.title,
                                                        style: {
                                                            width: '120px',
                                                            height: '120px',
                                                            objectFit: 'cover',
                                                            borderRadius: '4px',
                                                            marginBottom: '8px'
                                                        }
                                                    }),
                                                    el('div', { style: { display: 'flex', gap: '8px' } },
                                                        el(MediaUpload, {
                                                            onSelect: function(media) {
                                                                updateTrack(track.id, 'thumbnail', media.url);
                                                            },
                                                            allowedTypes: ['image'],
                                                            value: track.thumbnail,
                                                            render: function(obj) {
                                                                return el(Button, {
                                                                    onClick: obj.open,
                                                                    variant: 'secondary',
                                                                    isSmall: true
                                                                }, 'Trocar');
                                                            }
                                                        }),
                                                        el(Button, {
                                                            onClick: function() {
                                                                updateTrack(track.id, 'thumbnail', '');
                                                            },
                                                            variant: 'secondary',
                                                            isDestructive: true,
                                                            isSmall: true
                                                        }, 'Remover')
                                                    )
                                                ) :
                                                el(MediaUpload, {
                                                    onSelect: function(media) {
                                                        updateTrack(track.id, 'thumbnail', media.url);
                                                    },
                                                    allowedTypes: ['image'],
                                                    value: track.thumbnail,
                                                    render: function(obj) {
                                                        return el(Button, {
                                                            onClick: obj.open,
                                                            variant: 'secondary',
                                                            isSmall: true
                                                        }, '📷 Selecionar Imagem');
                                                    }
                                                })
                                        ),
                                        
                                        // Toggle Mostrar Vídeo
                                        el(ToggleControl, {
                                            label: '🎥 Mostrar Vídeo',
                                            checked: track.showVideo,
                                            onChange: function(val) { updateTrack(track.id, 'showVideo', val); },
                                            help: track.showVideo ? 
                                                'Vídeo será exibido no player' : 
                                                'Apenas thumbnail/capa será exibida'
                                        })
                                    )
                                );
                            })
                        )
                ),
                
                // MODAL DE IMPORTAÇÃO
                showModal ? el('div', {
                    className: 'bebelume-music-import-modal-overlay',
                    onClick: function(e) {
                        if (e.target.className === 'bebelume-music-import-modal-overlay') {
                            setShowModal(false);
                            setSearchTerm('');
                        }
                    }
                },
                    el('div', { className: 'bebelume-music-import-modal' },
                        // Header
                        el('div', { className: 'bebelume-music-import-modal-header' },
                            el('div', { style: { flex: 1 } },
                                el('h2', {}, '🎵 Importar Música dos Posts'),
                                el('input', {
                                    type: 'text',
                                    placeholder: '🔍 Buscar músicas/vídeos...',
                                    value: searchTerm,
                                    onChange: function(e) { setSearchTerm(e.target.value); },
                                    style: {
                                        width: '100%',
                                        padding: '10px 14px',
                                        fontSize: '14px',
                                        border: '2px solid #ddd',
                                        borderRadius: '6px',
                                        outline: 'none'
                                    }
                                })
                            ),
                            el('button', {
                                className: 'bebelume-music-import-modal-close',
                                onClick: function() { 
                                    setShowModal(false);
                                    setSearchTerm('');
                                }
                            }, '×')
                        ),
                        
                        // Body
                        el('div', { className: 'bebelume-music-import-modal-body' },
                            loading ? 
                                el('div', { className: 'bebelume-music-import-modal-loading' },
                                    el('p', {}, '⏳ Carregando vídeos...')
                                ) :
                            videos.length === 0 ?
                                el('div', { className: 'bebelume-music-import-modal-empty' },
                                    el('div', { className: 'bebelume-music-import-modal-empty-icon' }, '🎵'),
                                    el('p', {}, 'Nenhum vídeo encontrado')
                                ) :
                                (function() {
                                    var filteredVideos = filterVideos();
                                    
                                    return el('div', {},
                                        // Contador
                                        el('div', { 
                                            style: { 
                                                padding: '0 0 16px 0',
                                                fontSize: '14px',
                                                color: '#666',
                                                display: 'flex',
                                                justifyContent: 'space-between'
                                            } 
                                        },
                                            el('span', {},
                                                filteredVideos.length === videos.length ?
                                                    '📊 ' + videos.length + ' vídeo' + (videos.length !== 1 ? 's' : '') :
                                                    '🔍 ' + filteredVideos.length + ' de ' + videos.length
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
                                                        cursor: 'pointer'
                                                    }
                                                }, '✕ Limpar') : null
                                        ),
                                        
                                        // Grid
                                        filteredVideos.length === 0 ?
                                            el('div', { className: 'bebelume-music-import-modal-empty' },
                                                el('div', { className: 'bebelume-music-import-modal-empty-icon' }, '🔍'),
                                                el('p', {}, 'Nenhum resultado para "' + searchTerm + '"')
                                            ) :
                                            el('div', { className: 'bebelume-music-import-modal-grid' },
                                                filteredVideos.map(function(video) {
                                                    return el('div', {
                                                        key: video.id,
                                                        className: 'bebelume-music-import-video-card',
                                                        onClick: function() { importVideo(video); }
                                                    },
                                                        video.thumbnail ?
                                                            el('img', {
                                                                className: 'bebelume-music-import-video-thumbnail',
                                                                src: video.thumbnail,
                                                                alt: video.title
                                                            }) :
                                                            el('div', {
                                                                className: 'bebelume-music-import-video-thumbnail',
                                                                style: {
                                                                    display: 'flex',
                                                                    alignItems: 'center',
                                                                    justifyContent: 'center',
                                                                    fontSize: '48px',
                                                                    color: '#ddd'
                                                                }
                                                            }, '🎵'),
                                                        
                                                        el('div', { className: 'bebelume-music-import-video-info' },
                                                            el('h3', { className: 'bebelume-music-import-video-title' }, video.title),
                                                            video.description ? 
                                                                el('p', { className: 'bebelume-music-import-video-desc' }, video.description) : 
                                                                null,
                                                            el('div', { className: 'bebelume-music-import-video-date' }, video.date)
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

        save: function(props) {
            var attributes = props.attributes;
            
            return el('div', { 
                className: 'bebelume-music-player',
                'data-playlist-title': attributes.playlistTitle,
                'data-tracks': JSON.stringify(attributes.tracks)
            });
        }
    });
})(window.wp);
