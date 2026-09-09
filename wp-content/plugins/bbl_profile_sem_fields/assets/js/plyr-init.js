/**
 * Bebelume Plyr.io Integration
 * Inicializa e configura o Plyr.io para vídeos do site
 * 
 * @package Bebelume_Profile
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Configuração do Plyr.io
     */
    const BebelumePlyr = {
        
        /**
         * Configurações padrão do Plyr
         */
        defaultConfig: {
            controls: [
                'play-large',      // Botão grande no centro
                'play',            // Play/Pause
                'progress',        // Barra de progresso
                'current-time',    // Tempo atual
                'duration',        // Duração
                'mute',            // Mute
                'volume',          // Volume
                'settings',        // Configurações
                'pip',             // Picture-in-Picture
                'airplay',         // AirPlay
                'fullscreen'       // Tela cheia
            ],
            
            settings: ['quality', 'speed', 'loop'],
            
            speed: { 
                selected: 1, 
                options: [0.5, 0.75, 1, 1.25, 1.5, 2] 
            },
            
            autoplay: false,
            clickToPlay: true,
            hideControls: true,
            resetOnEnd: false,
            
            // Tradução PT-BR
            i18n: {
                restart: 'Reiniciar',
                rewind: 'Retroceder {seektime}s',
                play: 'Reproduzir',
                pause: 'Pausar',
                fastForward: 'Avançar {seektime}s',
                seek: 'Buscar',
                seekLabel: '{currentTime} de {duration}',
                played: 'Reproduzido',
                buffered: 'Carregado',
                currentTime: 'Tempo atual',
                duration: 'Duração',
                volume: 'Volume',
                mute: 'Mudo',
                unmute: 'Ativar som',
                enableCaptions: 'Ativar legendas',
                disableCaptions: 'Desativar legendas',
                download: 'Baixar',
                enterFullscreen: 'Tela cheia',
                exitFullscreen: 'Sair da tela cheia',
                frameTitle: 'Player para {title}',
                captions: 'Legendas',
                settings: 'Configurações',
                pip: 'Picture-in-Picture',
                menuBack: 'Voltar',
                speed: 'Velocidade',
                normal: 'Normal',
                quality: 'Qualidade',
                loop: 'Loop',
                start: 'Início',
                end: 'Fim',
                all: 'Tudo',
                reset: 'Redefinir',
                disabled: 'Desativado',
                enabled: 'Ativado',
                advertisement: 'Anúncio',
                qualityBadge: {
                    2160: '4K',
                    1440: 'HD',
                    1080: 'HD',
                    720: 'HD',
                    576: 'SD',
                    480: 'SD'
                }
            }
        },
        
        /**
         * Array de instâncias do Plyr
         */
        players: [],
        
        /**
         * Inicializar todos os players
         */
        init: function() {
            console.log('[Bebelume Plyr] Inicializando players...');
            
            if (typeof Plyr === 'undefined') {
                console.error('[Bebelume Plyr] Biblioteca Plyr.io não carregada!');
                return;
            }
            
            // ── Players do bloco bebelume/plyr (config individual) ──────────
            const blockPlayers = document.querySelectorAll('.bbl-plyr-video, .bbl-plyr-embed');
            blockPlayers.forEach((element, index) => {
                if (element.dataset.plyrInitialized) return;
                try {
                    // Lê config do data-plyr-config do elemento
                    let config = {};
                    if (element.dataset.plyrConfig) {
                        try { config = JSON.parse(element.dataset.plyrConfig); } catch(e) {}
                    }

                    // Aplica poster se existir (Plyr HTML5 precisa via config)
                    if (element.dataset.poster) {
                        config.poster = element.dataset.poster;
                    }

                    const player = new Plyr(element, config);
                    this.players.push(player);
                    element.dataset.plyrInitialized = '1';
                    this.attachEvents(player, this.players.length - 1);

                    // Força aspect-ratio no container do Plyr após inicialização
                    if (config.ratio) {
                        player.on('ready', () => {
                            const parts = config.ratio.split(':');
                            if (parts.length === 2) {
                                const container = player.elements.container;
                                if (container) {
                                    container.style.aspectRatio = parts[0] + ' / ' + parts[1];
                                    // Vídeo 9:16: limita largura para não esticar
                                    if (parts[0] < parts[1]) {
                                        container.style.maxWidth = 'min(100%, ' + (parseInt(parts[0]) / parseInt(parts[1]) * 100) + 'vh)';
                                        container.style.margin = '0 auto';
                                    }
                                }
                            }
                        });
                    }

                    console.log('[Bebelume Plyr] Bloco player ' + (index + 1) + ' inicializado');
                } catch (error) {
                    console.error('[Bebelume Plyr] Erro no bloco player ' + (index + 1) + ':', error);
                }
            });

            // ── Players genéricos com classe .bebelume-video ─────────────────
            const videoElements = document.querySelectorAll('.bebelume-video:not(.bbl-plyr-video)');
            videoElements.forEach((element, index) => {
                if (element.dataset.plyrInitialized) return;
                try {
                    const player = new Plyr(element, this.defaultConfig);
                    this.players.push(player);
                    element.dataset.plyrInitialized = '1';
                    this.attachEvents(player, this.players.length - 1);
                    console.log('[Bebelume Plyr] Player ' + (index + 1) + ' inicializado');
                } catch (error) {
                    console.error('[Bebelume Plyr] Erro ao inicializar player ' + (index + 1) + ':', error);
                }
            });

            console.log('[Bebelume Plyr] Total de ' + this.players.length + ' player(s) inicializado(s)');
        },
        
        /**
         * Adicionar event listeners
         */
        attachEvents: function(player, index) {
            player.on('ready', event => {
                console.log(`[Bebelume Plyr] Player ${index + 1} pronto`);
            });
            
            player.on('play', event => {
                console.log(`[Bebelume Plyr] Player ${index + 1} iniciado`);
                
                // Pausar outros players (opcional)
                this.pauseOtherPlayers(index);
            });
            
            player.on('pause', event => {
                console.log(`[Bebelume Plyr] Player ${index + 1} pausado`);
            });
            
            player.on('ended', event => {
                console.log(`[Bebelume Plyr] Player ${index + 1} finalizado`);
            });
            
            player.on('error', event => {
                console.error(`[Bebelume Plyr] Erro no player ${index + 1}:`, event);
            });
        },
        
        /**
         * Pausar outros players quando um começar
         */
        pauseOtherPlayers: function(currentIndex) {
            this.players.forEach((player, index) => {
                if (index !== currentIndex && !player.paused) {
                    player.pause();
                }
            });
        },
        
        /**
         * Destruir todos os players
         */
        destroy: function() {
            this.players.forEach(player => {
                player.destroy();
            });
            this.players = [];
            console.log('[Bebelume Plyr] Players destruídos');
        }
    };
    
    /**
     * Inicializar quando documento estiver pronto
     */
    $(document).ready(function() {
        BebelumePlyr.init();
    });
    
    /**
     * Reinicializar após AJAX (para conteúdo dinâmico)
     */
    $(document).ajaxComplete(function() {
        // Delay para garantir que DOM foi atualizado
        setTimeout(function() {
            const newVideos = document.querySelectorAll('.bebelume-video:not(.plyr--setup)');
            if (newVideos.length > 0) {
                console.log('[Bebelume Plyr] Reinicializando após AJAX...');
                BebelumePlyr.init();
            }
        }, 100);
    });
    
    // Expor no escopo global para uso externo se necessário
    window.BebelumePlyr = BebelumePlyr;
    
})(jQuery);