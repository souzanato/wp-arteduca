/**
 * Bebelume ArtEduca Música - Player Estilo Spotify
 */
(function() {
    'use strict';

    // Aguarda DOM carregar
    document.addEventListener('DOMContentLoaded', function() {
        initializePlayers();
    });

    function initializePlayers() {
        var playerContainers = document.querySelectorAll('.bebelume-music-player');
        
        playerContainers.forEach(function(container, index) {
            var playlistTitle = container.getAttribute('data-playlist-title');
            var tracksJson = container.getAttribute('data-tracks');
            var tracks = [];
            
            try {
                tracks = JSON.parse(tracksJson);
            } catch(e) {
                console.error('Erro ao parsear tracks:', e);
                return;
            }
            
            if (tracks.length === 0) {
                container.innerHTML = '<div class="bebelume-music-empty">🎵 Playlist vazia</div>';
                return;
            }
            
            // Cria o player
            var playerInstance = new BebelumeMusicPlayer(container, playlistTitle, tracks, index);
            playerInstance.init();
        });
    }

    /**
     * Classe do Player
     */
    function BebelumeMusicPlayer(container, title, tracks, index) {
        this.container = container;
        this.playlistTitle = title;
        this.tracks = tracks;
        this.playerIndex = index;
        this.currentTrackIndex = 0;
        this.isPlaying = false;
        this.isShuffle = false;
        this.repeatMode = 'off'; // off, all, one
        this.player = null;
        this.fixedPlayer = null;
        this.originalOrder = [];
        this.shuffledOrder = [];
    }

    BebelumeMusicPlayer.prototype.init = function() {
        this.originalOrder = this.tracks.map(function(_, i) { return i; });
        this.createPlayerHTML();
        this.initPlyr();
        this.attachEventListeners();
        this.createFixedPlayer();
    };

    BebelumeMusicPlayer.prototype.createPlayerHTML = function() {
        var self = this;
        var html = '<div class="bebelume-music-container">';
        
        // Header
        html += '<div class="bebelume-music-header">';
        html += '<h2 class="bebelume-music-title">';
        html += '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>';
        html += this.escapeHtml(this.playlistTitle);
        html += '</h2>';
        html += '</div>';
        
        // Main Player
        html += '<div class="bebelume-music-main">';
        html += this.createMainPlayerHTML();
        html += '</div>';
        
        // Playlist
        html += '<div class="bebelume-music-playlist">';
        html += '<div class="bebelume-music-playlist-header">';
        html += '<span>';
        html += '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 6px;"><path d="M21 15V6"></path><path d="M18.5 18a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"></path><path d="M12 12H3"></path><path d="M16 6H3"></path><path d="M12 18H3"></path></svg>';
        html += 'Playlist';
        html += '</span>';
        html += '<span class="bebelume-music-track-count">' + this.tracks.length + ' música' + (this.tracks.length !== 1 ? 's' : '') + '</span>';
        html += '</div>';
        html += '<div class="bebelume-music-tracks">';
        this.tracks.forEach(function(track, i) {
            html += self.createTrackHTML(track, i);
        });
        html += '</div>';
        html += '</div>';
        
        html += '</div>';
        this.container.innerHTML = html;
    };

    BebelumeMusicPlayer.prototype.createMainPlayerHTML = function() {
        var track = this.tracks[this.currentTrackIndex];
        var html = '';
        
        if (track.showVideo && track.url) {
            // Mostrar vídeo
            html += '<div class="bebelume-music-video-container">';
            html += '<video id="bebelume-music-video-' + this.playerIndex + '" playsinline controls>';
            html += '<source src="' + this.escapeHtml(track.url) + '" type="video/mp4">';
            html += '</video>';
            html += '</div>';
        } else if (track.thumbnail) {
            // Mostrar thumbnail
            html += '<div class="bebelume-music-thumbnail-container">';
            html += '<img src="' + this.escapeHtml(track.thumbnail) + '" alt="' + this.escapeHtml(track.title) + '" class="bebelume-music-thumbnail">';
            html += '<div class="bebelume-music-thumbnail-overlay">';
            html += '<button class="bebelume-music-play-overlay" data-play-overlay="true">';
            html += '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="white" stroke="none"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';
            html += '</button>';
            html += '</div>';
            html += '<audio id="bebelume-music-audio-' + this.playerIndex + '" src="' + this.escapeHtml(track.url) + '"></audio>';
            html += '</div>';
        } else {
            // Placeholder
            html += '<div class="bebelume-music-placeholder">';
            html += '<svg xmlns="http://www.w3.org/2000/svg" class="bebelume-music-placeholder-icon" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>';
            html += '<audio id="bebelume-music-audio-' + this.playerIndex + '" src="' + this.escapeHtml(track.url) + '"></audio>';
            html += '</div>';
        }
        
        // Info atual
        html += '<div class="bebelume-music-current-info">';
        html += '<div class="bebelume-music-current-title">' + this.escapeHtml(track.title) + '</div>';
        if (track.author) {
            html += '<div class="bebelume-music-current-author">' + this.escapeHtml(track.author) + '</div>';
        }
        html += '</div>';
        
        return html;
    };

    BebelumeMusicPlayer.prototype.createTrackHTML = function(track, index) {
        var html = '<div class="bebelume-music-track-wrapper" data-track-index="' + index + '">';
        
        // Track clicável
        html += '<div class="bebelume-music-track">';
        html += '<div class="bebelume-music-track-number">';
        html += '<span class="track-num">' + (index + 1) + '</span>';
        html += '<i data-lucide="play" class="track-playing-icon" style="width: 16px; height: 16px; display: none;"></i>';
        html += '</div>';
        html += '<div class="bebelume-music-track-info">';
        html += '<div class="bebelume-music-track-title">' + this.escapeHtml(track.title) + '</div>';
        if (track.author) {
            html += '<div class="bebelume-music-track-author">' + this.escapeHtml(track.author) + '</div>';
        }
        html += '</div>';
        if (track.thumbnail) {
            html += '<img src="' + this.escapeHtml(track.thumbnail) + '" alt="" class="bebelume-music-track-thumb">';
        }
        html += '</div>'; // .bebelume-music-track
        
        // Container de vídeo expansível (se tiver vídeo)
        if (track.showVideo && track.url) {
            html += '<div class="bebelume-music-track-video-container" id="video-container-' + this.playerIndex + '-' + index + '">';
            html += '<div class="bebelume-music-track-video-content">';
            html += '<video id="bebelume-track-video-' + this.playerIndex + '-' + index + '" class="bebelume-music-track-video" playsinline>';
            html += '<source src="' + this.escapeHtml(track.url) + '" type="video/mp4">';
            html += '</video>';
            html += '</div>';
            html += '</div>';
        }
        
        html += '</div>'; // .bebelume-music-track-wrapper
        return html;
    };

    BebelumeMusicPlayer.prototype.initPlyr = function() {
        var self = this;
        var track = this.tracks[this.currentTrackIndex];
        var element = null;
        var isAccordionVideo = false;
        
        // Destrói player antigo se existir
        if (this.player) {
            try {
                this.player.destroy();
            } catch(e) {
                console.log('Erro ao destruir player:', e);
            }
            this.player = null;
        }
        
        if (track.showVideo && track.url) {
            // Usa vídeo do accordion (visível na playlist)
            element = document.getElementById('bebelume-track-video-' + this.playerIndex + '-' + this.currentTrackIndex);
            
            if (element) {
                isAccordionVideo = true;
            } else {
                // Fallback para vídeo do main player oculto
                element = document.getElementById('bebelume-music-video-' + this.playerIndex);
            }
        } else {
            // Usa áudio do main player oculto
            element = document.getElementById('bebelume-music-audio-' + this.playerIndex);
        }
        
        if (!element) return;
        
        // Controles completos para vídeo no accordion
        var plyrConfig = {
            controls: isAccordionVideo 
                ? ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings', 'fullscreen']
                : ['play', 'progress', 'current-time', 'mute', 'volume'],
            hideControls: false,
            resetOnEnd: false
        };
        
        this.player = new Plyr(element, plyrConfig);
        
        // Eventos do player
        this.player.on('play', function() {
            self.isPlaying = true;
            self.showFixedPlayer();
            self.updatePlayButtonStates();
            self.updateFixedPlayer();
            self.updateActiveTrack();
        });
        
        this.player.on('pause', function() {
            self.isPlaying = false;
            self.updatePlayButtonStates();
            self.updateFixedPlayer();
            self.updateActiveTrack();
        });
        
        this.player.on('ended', function() {
            self.handleTrackEnd();
        });
        
        this.player.on('timeupdate', function() {
            self.updateFixedPlayer();
        });
        
        // Atualização inicial dos botões após 300ms
        setTimeout(function() {
            self.updatePlayButtonStates();
        }, 300);
    };
    
    BebelumeMusicPlayer.prototype.showFixedPlayer = function() {
        if (this.fixedPlayer && !this.fixedPlayer.classList.contains('visible')) {
            this.fixedPlayer.classList.add('visible');
        }
    };

    BebelumeMusicPlayer.prototype.attachEventListeners = function() {
        var self = this;
        
        // Cliques nas tracks
        var trackElements = this.container.querySelectorAll('.bebelume-music-track');
        trackElements.forEach(function(el) {
            el.addEventListener('click', function() {
                var wrapper = this.parentElement;
                var index = parseInt(wrapper.getAttribute('data-track-index'));
                
                // Se clicar na música atual
                if (index === self.currentTrackIndex) {
                    // Toggle play/pause
                    self.togglePlay();
                } else {
                    // Trocar e dar play
                    self.playTrack(index);
                }
            });
        });
        
        // Botão play overlay
        var playOverlay = this.container.querySelector('[data-play-overlay]');
        if (playOverlay) {
            playOverlay.addEventListener('click', function(e) {
                e.stopPropagation();
                self.togglePlay();
            });
        }
    };

    BebelumeMusicPlayer.prototype.createFixedPlayer = function() {
        var self = this;
        
        // Remove fixo anterior se existir
        var existing = document.getElementById('bebelume-music-fixed-' + this.playerIndex);
        if (existing) existing.remove();
        
        var html = '<div id="bebelume-music-fixed-' + this.playerIndex + '" class="bebelume-music-fixed">';
        html += '<div class="bebelume-music-fixed-content">';
        
        // Info
        html += '<div class="bebelume-music-fixed-info">';
        html += '<div class="bebelume-music-fixed-thumb">';
        html += '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>';
        html += '</div>';
        html += '<div>';
        html += '<div class="bebelume-music-fixed-title"></div>';
        html += '<div class="bebelume-music-fixed-author"></div>';
        html += '</div>';
        html += '</div>';
        
        // Controles (com SVG direto, SEM Lucide)
        html += '<div class="bebelume-music-fixed-controls">';
        
        // Shuffle
        html += '<button class="bebelume-music-btn bebelume-music-btn-shuffle" data-action="shuffle" title="Aleatório">';
        html += '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line><line x1="4" y1="4" x2="9" y2="9"></line></svg>';
        html += '</button>';
        
        // Previous
        html += '<button class="bebelume-music-btn" data-action="previous" title="Anterior">';
        html += '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="19 20 9 12 19 4 19 20"></polygon><line x1="5" y1="19" x2="5" y2="5"></line></svg>';
        html += '</button>';
        
        // Play/Pause (começa com PLAY)
        html += '<button class="bebelume-music-btn bebelume-music-btn-play" data-action="play" title="Play/Pause">';
        html += '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';
        html += '</button>';
        
        // Next
        html += '<button class="bebelume-music-btn" data-action="next" title="Próxima">';
        html += '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 4 15 12 5 20 5 4"></polygon><line x1="19" y1="5" x2="19" y2="19"></line></svg>';
        html += '</button>';
        
        // Repeat
        html += '<button class="bebelume-music-btn bebelume-music-btn-repeat" data-action="repeat" title="Repetir">';
        html += '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>';
        html += '</button>';
        
        html += '</div>';
        
        // Progress
        html += '<div class="bebelume-music-fixed-progress">';
        html += '<span class="bebelume-music-time-current">0:00</span>';
        html += '<div class="bebelume-music-progress-bar">';
        html += '<div class="bebelume-music-progress-fill"></div>';
        html += '</div>';
        html += '<span class="bebelume-music-time-total">0:00</span>';
        html += '</div>';
        
        html += '</div>';
        html += '</div>';
        
        document.body.insertAdjacentHTML('beforeend', html);
        this.fixedPlayer = document.getElementById('bebelume-music-fixed-' + this.playerIndex);
        
        // Event listeners do fixed player
        this.fixedPlayer.querySelector('[data-action="play"]').addEventListener('click', function() {
            self.togglePlay();
        });
        
        this.fixedPlayer.querySelector('[data-action="previous"]').addEventListener('click', function() {
            self.previousTrack();
        });
        
        this.fixedPlayer.querySelector('[data-action="next"]').addEventListener('click', function() {
            self.nextTrack();
        });
        
        this.fixedPlayer.querySelector('[data-action="shuffle"]').addEventListener('click', function() {
            self.toggleShuffle();
        });
        
        this.fixedPlayer.querySelector('[data-action="repeat"]').addEventListener('click', function() {
            self.toggleRepeat();
        });
        
        // Progress bar clicável
        var progressBar = this.fixedPlayer.querySelector('.bebelume-music-progress-bar');
        progressBar.addEventListener('click', function(e) {
            var rect = this.getBoundingClientRect();
            var percent = (e.clientX - rect.left) / rect.width;
            if (self.player) {
                self.player.currentTime = self.player.duration * percent;
            }
        });
        
        this.updateFixedPlayer();
    };

    BebelumeMusicPlayer.prototype.playTrack = function(index) {
        if (index < 0 || index >= this.tracks.length) return;
        
        // PAUSA E DESTRÓI player anterior COMPLETAMENTE
        if (this.player) {
            try {
                this.player.pause();
                this.player.destroy();
                this.player = null;
            } catch(e) {
                console.log('Erro ao destruir player anterior:', e);
            }
        }
        
        // Define como não tocando e limpa índice anterior
        var previousIndex = this.currentTrackIndex;
        this.isPlaying = false;
        this.currentTrackIndex = index;
        
        // LIMPA TODOS os ícones ANTES (força limpeza da track anterior)
        var allTracks = this.container.querySelectorAll('.bebelume-music-track');
        allTracks.forEach(function(track) {
            track.classList.remove('active');
            var trackNum = track.querySelector('.track-num');
            var playingIcon = track.querySelector('.track-playing-icon');
            if (trackNum && playingIcon) {
                trackNum.style.display = 'inline';
                playingIcon.style.display = 'none';
            }
        });
        
        var track = this.tracks[this.currentTrackIndex];
        
        // Fecha todos os vídeos accordion
        var allVideoContainers = this.container.querySelectorAll('.bebelume-music-track-video-container');
        allVideoContainers.forEach(function(container) {
            container.style.maxHeight = '0';
            // Para todos os vídeos dentro
            var videos = container.querySelectorAll('video');
            videos.forEach(function(video) {
                video.pause();
                video.currentTime = 0;
            });
        });
        
        // Se track tem vídeo, expande accordion
        if (track.showVideo && track.url) {
            var videoContainerId = 'video-container-' + this.playerIndex + '-' + index;
            var videoContainer = document.getElementById(videoContainerId);
            if (videoContainer) {
                videoContainer.style.maxHeight = '500px'; // Reduzido para não cortar controles
            }
        }
        
        // Mantém o código original que FUNCIONA
        this.updateMainPlayer();
        
        var self = this;
        setTimeout(function() {
            if (self.player) {
                self.player.play().then(function() {
                    // Garante que estado está correto após play
                    self.isPlaying = true;
                    self.showFixedPlayer();
                    self.updatePlayButtonStates();
                    self.updateActiveTrack();
                }).catch(function(error) {
                    console.error('Erro ao dar play:', error);
                });
            }
        }, 100);
        
        this.updateFixedPlayer();
    };

    BebelumeMusicPlayer.prototype.updateMainPlayer = function() {
        var mainContainer = this.container.querySelector('.bebelume-music-main');
        mainContainer.innerHTML = this.createMainPlayerHTML();
        
        // Inicializa ícones Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        this.initPlyr();
        
        // Re-attach play overlay listener
        var playOverlay = this.container.querySelector('[data-play-overlay]');
        var self = this;
        if (playOverlay) {
            playOverlay.addEventListener('click', function(e) {
                e.stopPropagation();
                self.togglePlay();
            });
        }
    };

    BebelumeMusicPlayer.prototype.togglePlay = function() {
        if (!this.player) return;
        
        if (this.isPlaying) {
            this.player.pause();
            // Estado será atualizado no evento 'pause'
        } else {
            var self = this;
            this.player.play().then(function() {
                // Garante que estado está correto após play
                self.isPlaying = true;
                self.showFixedPlayer();
                self.updatePlayButtonStates();
            }).catch(function(error) {
                console.error('Erro ao dar play:', error);
            });
        }
    };

    BebelumeMusicPlayer.prototype.nextTrack = function() {
        var nextIndex;
        
        if (this.isShuffle) {
            var currentPos = this.shuffledOrder.indexOf(this.currentTrackIndex);
            var nextPos = (currentPos + 1) % this.shuffledOrder.length;
            nextIndex = this.shuffledOrder[nextPos];
        } else {
            nextIndex = (this.currentTrackIndex + 1) % this.tracks.length;
        }
        
        this.playTrack(nextIndex);
    };

    BebelumeMusicPlayer.prototype.previousTrack = function() {
        var prevIndex;
        
        if (this.isShuffle) {
            var currentPos = this.shuffledOrder.indexOf(this.currentTrackIndex);
            var prevPos = (currentPos - 1 + this.shuffledOrder.length) % this.shuffledOrder.length;
            prevIndex = this.shuffledOrder[prevPos];
        } else {
            prevIndex = (this.currentTrackIndex - 1 + this.tracks.length) % this.tracks.length;
        }
        
        this.playTrack(prevIndex);
    };

    BebelumeMusicPlayer.prototype.handleTrackEnd = function() {
        if (this.repeatMode === 'one') {
            this.player.restart();
            this.player.play();
        } else {
            this.nextTrack();
        }
    };

    BebelumeMusicPlayer.prototype.toggleShuffle = function() {
        this.isShuffle = !this.isShuffle;
        
        if (this.isShuffle) {
            // Cria ordem aleatória
            this.shuffledOrder = this.originalOrder.slice();
            for (var i = this.shuffledOrder.length - 1; i > 0; i--) {
                var j = Math.floor(Math.random() * (i + 1));
                var temp = this.shuffledOrder[i];
                this.shuffledOrder[i] = this.shuffledOrder[j];
                this.shuffledOrder[j] = temp;
            }
        }
        
        var btn = this.fixedPlayer.querySelector('[data-action="shuffle"]');
        if (this.isShuffle) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    };

    BebelumeMusicPlayer.prototype.toggleRepeat = function() {
        var modes = ['off', 'all', 'one'];
        var currentIndex = modes.indexOf(this.repeatMode);
        this.repeatMode = modes[(currentIndex + 1) % modes.length];
        
        var btn = this.fixedPlayer.querySelector('[data-action="repeat"]');
        btn.classList.remove('active', 'repeat-one');
        
        if (this.repeatMode === 'all') {
            btn.classList.add('active');
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>';
        } else if (this.repeatMode === 'one') {
            btn.classList.add('active', 'repeat-one');
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path><path d="M11 10h2v4"></path></svg>';
        } else {
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>';
        }
    };

    BebelumeMusicPlayer.prototype.updatePlayButtonStates = function() {
        if (!this.fixedPlayer) return;
        
        var playBtn = this.fixedPlayer.querySelector('[data-action="play"]');
        if (!playBtn) return;
        
        // ABORDAGEM DIRETA: Substitui o HTML do botão inteiro
        if (this.isPlaying) {
            // Música TOCANDO → mostra PAUSE
            playBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>';
        } else {
            // Música PAUSADA → mostra PLAY
            playBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';
        }
        
        // Atualizar overlay também
        var playOverlay = this.container.querySelector('[data-play-overlay]');
        if (playOverlay) {
            if (this.isPlaying) {
                playOverlay.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="white" stroke="none"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>';
            } else {
                playOverlay.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="white" stroke="none"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';
            }
        }
        
        // NÃO atualiza ícone da track aqui - deixa updateActiveTrack() fazer isso
    };

    BebelumeMusicPlayer.prototype.updateActiveTrack = function() {
        var self = this;
        var tracks = this.container.querySelectorAll('.bebelume-music-track');
        tracks.forEach(function(track) {
            var wrapper = track.parentElement;
            var trackIndex = parseInt(wrapper.getAttribute('data-track-index'));
            var trackNum = track.querySelector('.track-num');
            var playingIcon = track.querySelector('.track-playing-icon');
            
            if (trackIndex === self.currentTrackIndex) {
                track.classList.add('active');
                if (trackNum && playingIcon) {
                    trackNum.style.display = 'none';
                    playingIcon.style.display = 'inline-block';
                    
                    // Atualizar ícone baseado no estado
                    if (self.isPlaying) {
                        playingIcon.setAttribute('data-lucide', 'volume-2');
                    } else {
                        playingIcon.setAttribute('data-lucide', 'pause');
                    }
                    
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
            } else {
                track.classList.remove('active');
                if (trackNum && playingIcon) {
                    trackNum.style.display = 'inline';
                    playingIcon.style.display = 'none';
                }
            }
        });
    };

    BebelumeMusicPlayer.prototype.updateFixedPlayer = function() {
        if (!this.fixedPlayer || !this.player) return;
        
        var track = this.tracks[this.currentTrackIndex];
        
        // Thumbnail
        var thumb = this.fixedPlayer.querySelector('.bebelume-music-fixed-thumb');
        if (track.thumbnail) {
            thumb.style.backgroundImage = 'url(' + track.thumbnail + ')';
            thumb.innerHTML = '';
        } else {
            thumb.style.backgroundImage = 'none';
            thumb.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>';
        }
        
        // Título e autor
        this.fixedPlayer.querySelector('.bebelume-music-fixed-title').textContent = track.title;
        this.fixedPlayer.querySelector('.bebelume-music-fixed-author').textContent = track.author || '';
        
        // Tempo
        var current = this.formatTime(this.player.currentTime);
        var total = this.formatTime(this.player.duration);
        this.fixedPlayer.querySelector('.bebelume-music-time-current').textContent = current;
        this.fixedPlayer.querySelector('.bebelume-music-time-total').textContent = total;
        
        // Progress
        var percent = (this.player.currentTime / this.player.duration) * 100 || 0;
        this.fixedPlayer.querySelector('.bebelume-music-progress-fill').style.width = percent + '%';
    };

    BebelumeMusicPlayer.prototype.formatTime = function(seconds) {
        if (isNaN(seconds)) return '0:00';
        var min = Math.floor(seconds / 60);
        var sec = Math.floor(seconds % 60);
        return min + ':' + (sec < 10 ? '0' : '') + sec;
    };

    BebelumeMusicPlayer.prototype.escapeHtml = function(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

})();
