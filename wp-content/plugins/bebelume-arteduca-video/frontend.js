/**
 * Frontend JavaScript - Thumbnail com Preview e Lightbox
 */
(function() {
    'use strict';

    // Aguarda o DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        // Encontra todos os wrappers de vídeo
        var videoWrappers = document.querySelectorAll('.bebelume-video-wrapper');
        
        videoWrappers.forEach(function(wrapper) {
            setupVideoThumbnail(wrapper);
        });

        // Botão Voltar do browser fecha a lightbox (igual ao ESC)
        window.addEventListener('popstate', function(e) {
            var lightbox = document.getElementById('bebelume-lightbox');
            if (lightbox && lightbox.classList.contains('active')) {
                closeLightbox(true);
            }
        });
    }

    function setupVideoThumbnail(wrapper) {
        var video = wrapper.querySelector('.bebelume-plyr-video');
        if (!video) return;

        var videoUrl = video.src;
        var posterUrl = wrapper.getAttribute('data-poster-url') || video.poster;

        // Configura o logo Bebelume ou Customizado
        var logo = wrapper.querySelector('.bebelume-logo');
        if (logo && (!logo.src || logo.src === '' || logo.src === window.location.href)) {
            // Verifica se tem imagem customizada
            var customThumbUrl = wrapper.getAttribute('data-description-thumb-url');
            
            if (customThumbUrl && customThumbUrl !== '') {
                // Usa imagem customizada
                logo.src = customThumbUrl;
                logo.alt = 'Thumb';
            } else {
                // Usa logo padrão Bebelume
                if (typeof bebelumePluginUrl !== 'undefined') {
                    logo.src = bebelumePluginUrl + '/assets/images/bebelume.png';
                } else {
                    var scripts = document.querySelectorAll('script[src*="bebelume-arteduca-video"]');
                    if (scripts.length > 0) {
                        var scriptSrc = scripts[0].src;
                        var pluginPath = scriptSrc.substring(0, scriptSrc.lastIndexOf('/'));
                        logo.src = pluginPath + '/assets/images/bebelume.png';
                    }
                }
            }
        }

        // Detecta se é mobile
        var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 768;

        // Cria estrutura do thumbnail
        var thumbnail = document.createElement('div');
        thumbnail.className = 'bebelume-video-thumbnail';

        // Imagem do poster
        var posterImg = document.createElement('img');
        posterImg.src = posterUrl || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="1920" height="1080"%3E%3Crect width="1920" height="1080" fill="%23333"/%3E%3C/svg%3E';
        posterImg.alt = 'Video thumbnail';

        // Vídeo de preview (apenas desktop)
        var previewVideo = null;
        if (!isMobile) {
            previewVideo = document.createElement('video');
            previewVideo.className = 'bebelume-video-preview';
            previewVideo.src = videoUrl;
            previewVideo.muted = true;
            previewVideo.loop = true;
            previewVideo.preload = 'metadata';

            // Começa o preview em um ponto interessante (10 segundos)
            previewVideo.addEventListener('loadedmetadata', function() {
                var startTime = Math.min(10, previewVideo.duration * 0.2); // 10s ou 20% do vídeo
                previewVideo.currentTime = startTime;
            });
        }

        // Botão de play
        var playButton = document.createElement('div');
        playButton.className = 'bebelume-play-button';

        // Duração do vídeo (opcional, será preenchida depois)
        var durationLabel = document.createElement('div');
        durationLabel.className = 'bebelume-video-duration';
        durationLabel.style.display = 'none';

        // Monta o thumbnail
        thumbnail.appendChild(posterImg);
        if (previewVideo) {
            thumbnail.appendChild(previewVideo);
        }
        thumbnail.appendChild(playButton);
        thumbnail.appendChild(durationLabel);

        // Insere o thumbnail antes do vídeo
        video.parentNode.insertBefore(thumbnail, video);

        // Pega a duração do vídeo
        var tempVideo = document.createElement('video');
        tempVideo.src = videoUrl;
        tempVideo.preload = 'metadata';
        tempVideo.addEventListener('loadedmetadata', function() {
            var duration = formatDuration(tempVideo.duration);
            durationLabel.textContent = duration;
            durationLabel.style.display = 'block';
            tempVideo = null; // Limpa da memória
        });

        // Preview no hover (apenas desktop)
        if (previewVideo) {
            thumbnail.addEventListener('mouseenter', function() {
                previewVideo.play().catch(function(e) {
                    console.log('Não foi possível reproduzir preview:', e);
                });
            });

            thumbnail.addEventListener('mouseleave', function() {
                previewVideo.pause();
                // Volta para o ponto inicial do preview
                var startTime = Math.min(10, previewVideo.duration * 0.2);
                previewVideo.currentTime = startTime;
            });
        }

        // Click abre lightbox
        thumbnail.addEventListener('click', function() {
            openLightbox(wrapper, videoUrl, posterUrl);
        });

        // Aplica CSS Custom Properties
        applyCustomStyles(wrapper);
    }

    function openLightbox(wrapper, videoUrl, posterUrl) {
        // Cria lightbox se não existir
        var lightbox = document.getElementById('bebelume-lightbox');
        if (!lightbox) {
            lightbox = document.createElement('div');
            lightbox.id = 'bebelume-lightbox';
            lightbox.className = 'bebelume-lightbox';
            lightbox.innerHTML = `
                <div class="bebelume-lightbox-content">
                    <button class="bebelume-lightbox-close" aria-label="Fechar">&times;</button>
                    <video class="bebelume-plyr-video" controls playsinline></video>
                </div>
            `;
            document.body.appendChild(lightbox);

            // Cria botão de fechar para fullscreen
            var fullscreenCloseBtn = document.createElement('button');
            fullscreenCloseBtn.id = 'bebelume-fullscreen-close';
            fullscreenCloseBtn.className = 'bebelume-fullscreen-close';
            fullscreenCloseBtn.innerHTML = '&times;';
            fullscreenCloseBtn.setAttribute('aria-label', 'Sair da tela cheia');
            document.body.appendChild(fullscreenCloseBtn);

            // Fecha ao clicar no X normal
            lightbox.querySelector('.bebelume-lightbox-close').addEventListener('click', function() {
                closeLightbox();
            });

            // Fecha ao clicar no X do fullscreen
            fullscreenCloseBtn.addEventListener('click', function() {
                // Sai do fullscreen primeiro se estiver em fullscreen
                if (window.bebelumePlyrInstance && window.bebelumePlyrInstance.fullscreen.active) {
                    window.bebelumePlyrInstance.fullscreen.exit();
                }
                closeLightbox();
            });

            // Fecha ao clicar fora do vídeo
            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) {
                    closeLightbox();
                }
            });

            // Fecha com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                    closeLightbox();
                }
            });
        }

        // Destroi instância anterior corretamente antes de qualquer coisa
        if (window.bebelumePlyrInstance) {
            window.bebelumePlyrInstance.pause();
            window.bebelumePlyrInstance.destroy();
            window.bebelumePlyrInstance = null;
        }

        // Recria o elemento <video> do zero para evitar resíduos da instância anterior
        var lightboxContent = lightbox.querySelector('.bebelume-lightbox-content');
        var oldVideo = lightboxContent.querySelector('.bebelume-plyr-video');
        if (oldVideo) oldVideo.remove();
        var lightboxVideo = document.createElement('video');
        lightboxVideo.className = 'bebelume-plyr-video';
        lightboxVideo.setAttribute('playsinline', '');
        lightboxVideo.src = videoUrl;
        if (posterUrl) lightboxVideo.poster = posterUrl;
        lightboxContent.appendChild(lightboxVideo);

        // Aplica estilos customizados na lightbox
        applyCustomStylesToElement(wrapper, lightboxContent);

        // Abre a lightbox
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Entrada falsa no histórico para capturar o botão Voltar
        history.pushState({ bebelumeLightbox: true }, '');

        // Inicializa o Plyr na lightbox
        if (typeof Plyr !== 'undefined') {

            window.bebelumePlyrInstance = new Plyr(lightboxVideo, {
                controls: [
                    'play-large',
                    'play',
                    'progress',
                    'current-time',
                    'duration',
                    'mute',
                    'volume',
                    'settings',
                    'pip',
                    'airplay',
                    'fullscreen'
                ],
                settings: ['quality', 'speed', 'loop'],
                i18n: {
                    restart: 'Reiniciar',
                    rewind: 'Retroceder {seektime}s',
                    play: 'Reproduzir',
                    pause: 'Pausar',
                    fastForward: 'Avançar {seektime}s',
                    seek: 'Buscar',
                    seekLabel: '{currentTime} de {duration}',
                    played: 'Reproduzido',
                    buffered: 'Buffered',
                    currentTime: 'Tempo atual',
                    duration: 'Duração',
                    volume: 'Volume',
                    mute: 'Mudo',
                    unmute: 'Ativar som',
                    enableCaptions: 'Ativar legendas',
                    disableCaptions: 'Desativar legendas',
                    download: 'Download',
                    enterFullscreen: 'Entrar em tela cheia',
                    exitFullscreen: 'Sair de tela cheia',
                    frameTitle: 'Player para {title}',
                    captions: 'Legendas',
                    settings: 'Configurações',
                    pip: 'Picture-in-Picture',
                    menuBack: 'Voltar',
                    speed: 'Velocidade',
                    normal: 'Normal',
                    quality: 'Qualidade',
                    loop: 'Loop'
                }
            });

            // Inicia reprodução automaticamente
            window.bebelumePlyrInstance.play();

            // Controla visibilidade do botão de fechar em fullscreen com auto-hide
            var fullscreenCloseBtn = document.getElementById('bebelume-fullscreen-close');
            var hideTimeout;
            
            // Função para esconder botão após 3 segundos
            function scheduleHide() {
                clearTimeout(hideTimeout);
                if (fullscreenCloseBtn) {
                    fullscreenCloseBtn.classList.remove('auto-hide');
                    fullscreenCloseBtn.classList.add('show-on-move');
                    
                    hideTimeout = setTimeout(function() {
                        fullscreenCloseBtn.classList.remove('show-on-move');
                        fullscreenCloseBtn.classList.add('auto-hide');
                    }, 3000);
                }
            }
            
            // Mostra botão ao mover mouse
            function onMouseMove() {
                if (fullscreenCloseBtn && fullscreenCloseBtn.classList.contains('active')) {
                    scheduleHide();
                }
            }
            
            window.bebelumePlyrInstance.on('enterfullscreen', function() {
                if (fullscreenCloseBtn) {
                    fullscreenCloseBtn.classList.add('active');
                    fullscreenCloseBtn.classList.remove('auto-hide');
                    
                    // Inicia auto-hide após 3 segundos
                    scheduleHide();
                    
                    // Adiciona listener de mouse
                    document.addEventListener('mousemove', onMouseMove);
                }
            });

            window.bebelumePlyrInstance.on('exitfullscreen', function() {
                if (fullscreenCloseBtn) {
                    fullscreenCloseBtn.classList.remove('active');
                    fullscreenCloseBtn.classList.remove('auto-hide');
                    fullscreenCloseBtn.classList.remove('show-on-move');
                    clearTimeout(hideTimeout);
                    
                    // Remove listener de mouse
                    document.removeEventListener('mousemove', onMouseMove);
                }
            });
        }
    }

    function closeLightbox(viaHistory) {
        var lightbox = document.getElementById('bebelume-lightbox');
        if (!lightbox) return;

        // Para o vídeo
        if (window.bebelumePlyrInstance) {
            window.bebelumePlyrInstance.pause();
        }

        // Esconde o botão de fechar do fullscreen
        var fullscreenCloseBtn = document.getElementById('bebelume-fullscreen-close');
        if (fullscreenCloseBtn) {
            fullscreenCloseBtn.classList.remove('active');
        }

        // Fecha a lightbox
        lightbox.classList.remove('active');
        document.body.style.overflow = '';

        // Se não foi o botão Voltar que fechou, limpa a entrada falsa do histórico
        if (!viaHistory && history.state && history.state.bebelumeLightbox) {
            history.back();
        }
    }

    function applyCustomStyles(wrapper) {
        // Função auxiliar para aplicar CSS Custom Property se o valor existir
        function applyProperty(dataAttr, cssVar) {
            var value = wrapper.getAttribute(dataAttr);
            if (value && value !== 'null' && value !== '') {
                wrapper.style.setProperty(cssVar, value);
            }
        }

        // Aplica todas as CSS Custom Properties do Plyr
        applyProperty('data-color-main', '--plyr-color-main');
        applyProperty('data-video-background', '--plyr-video-background');
        applyProperty('data-video-controls-bg', '--plyr-video-controls-background');
        applyProperty('data-video-control-color', '--plyr-video-control-color');
        applyProperty('data-video-control-color-hover', '--plyr-video-control-color-hover');
        applyProperty('data-video-control-bg-hover', '--plyr-video-control-background-hover');
        applyProperty('data-range-fill-bg', '--plyr-range-fill-background');
        applyProperty('data-range-thumb-bg', '--plyr-range-thumb-background');
        applyProperty('data-video-range-track-bg', '--plyr-video-range-track-background');
        applyProperty('data-range-track-height', '--plyr-range-track-height');
        applyProperty('data-menu-bg', '--plyr-menu-background');
        applyProperty('data-menu-color', '--plyr-menu-color');
        applyProperty('data-menu-radius', '--plyr-menu-radius');
        applyProperty('data-captions-bg', '--plyr-captions-background');
        applyProperty('data-captions-text-color', '--plyr-captions-text-color');
        applyProperty('data-control-icon-size', '--plyr-control-icon-size');
        applyProperty('data-control-spacing', '--plyr-control-spacing');
        applyProperty('data-control-radius', '--plyr-control-radius');
    }

    function applyCustomStylesToElement(wrapper, element) {
        // Aplica os mesmos estilos customizados no elemento da lightbox
        var properties = [
            ['data-color-main', '--plyr-color-main'],
            ['data-video-background', '--plyr-video-background'],
            ['data-video-controls-bg', '--plyr-video-controls-background'],
            ['data-video-control-color', '--plyr-video-control-color'],
            ['data-video-control-color-hover', '--plyr-video-control-color-hover'],
            ['data-video-control-bg-hover', '--plyr-video-control-background-hover'],
            ['data-range-fill-bg', '--plyr-range-fill-background'],
            ['data-range-thumb-bg', '--plyr-range-thumb-background'],
            ['data-video-range-track-bg', '--plyr-video-range-track-background'],
            ['data-range-track-height', '--plyr-range-track-height'],
            ['data-menu-bg', '--plyr-menu-background'],
            ['data-menu-color', '--plyr-menu-color'],
            ['data-menu-radius', '--plyr-menu-radius'],
            ['data-captions-bg', '--plyr-captions-background'],
            ['data-captions-text-color', '--plyr-captions-text-color'],
            ['data-control-icon-size', '--plyr-control-icon-size'],
            ['data-control-spacing', '--plyr-control-spacing'],
            ['data-control-radius', '--plyr-control-radius']
        ];

        properties.forEach(function(prop) {
            var value = wrapper.getAttribute(prop[0]);
            if (value && value !== 'null' && value !== '') {
                element.style.setProperty(prop[1], value);
            }
        });
    }

    function formatDuration(seconds) {
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = Math.floor(seconds % 60);

        if (hours > 0) {
            return hours + ':' + pad(minutes) + ':' + pad(secs);
        }
        return minutes + ':' + pad(secs);
    }

    function pad(num) {
        return num < 10 ? '0' + num : num;
    }
})();