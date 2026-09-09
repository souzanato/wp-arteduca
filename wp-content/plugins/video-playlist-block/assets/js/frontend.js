// assets/js/frontend.js

document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // SLIDER
    // ==========================================

    const playlists = document.querySelectorAll('.video-playlist-container');
    playlists.forEach(function(playlist) {
        initPlaylist(playlist);
    });

    function initPlaylist(playlist) {
        const sliderWrapper = playlist.querySelector('.video-slider-wrapper');
        const sliderTrack  = playlist.querySelector('.video-slider-track');
        const prevBtn      = playlist.querySelector('.video-slider-prev');
        const nextBtn      = playlist.querySelector('.video-slider-next');

        if (!sliderTrack) return;

        let currentScroll = 0;
        const scrollAmount = 250;

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                currentScroll = Math.max(0, currentScroll - scrollAmount);
                sliderTrack.style.transform = 'translateX(-' + currentScroll + 'px)';
                updateNavButtons();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                const maxScroll = sliderTrack.scrollWidth - sliderWrapper.clientWidth;
                currentScroll = Math.min(maxScroll, currentScroll + scrollAmount);
                sliderTrack.style.transform = 'translateX(-' + currentScroll + 'px)';
                updateNavButtons();
            });
        }

        function updateNavButtons() {
            if (prevBtn) {
                prevBtn.disabled = currentScroll <= 0;
                prevBtn.style.opacity = currentScroll <= 0 ? '0.3' : '1';
            }
            if (nextBtn) {
                const maxScroll = sliderTrack.scrollWidth - sliderWrapper.clientWidth;
                nextBtn.disabled = currentScroll >= maxScroll;
                nextBtn.style.opacity = currentScroll >= maxScroll ? '0.3' : '1';
            }
        }

        updateNavButtons();
        window.addEventListener('resize', updateNavButtons);
    }

    // ==========================================
    // PLAYER BEBELUME (Plyr + Lightbox)
    // ==========================================

    var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 768;
    var vpbPlyrInstance = null;

    // Botão Voltar do browser fecha a lightbox (igual ao ESC)
    window.addEventListener('popstate', function() {
        var lightbox = document.getElementById('vpb-plyr-lightbox');
        if (lightbox && lightbox.classList.contains('active')) {
            closeLightbox(true);
        }
    });

    // Inicializa cada card de vídeo desbloqueado
    document.querySelectorAll('.video-thumb:not(.video-locked)').forEach(function(thumbLink) {
        var videoUrl  = thumbLink.getAttribute('href') || thumbLink.getAttribute('data-video-url');
        var posterUrl = thumbLink.querySelector('img') ? thumbLink.querySelector('img').src : '';
        if (!videoUrl) return;

        // Wrapper do thumb para posicionamento do preview e duração
        var thumbImg = thumbLink.querySelector('img');

        // — Duração —
        var durationLabel = document.createElement('div');
        durationLabel.className = 'vpb-video-duration';
        durationLabel.style.display = 'none';
        thumbLink.appendChild(durationLabel);

        var tempVideo = document.createElement('video');
        tempVideo.src = videoUrl;
        tempVideo.preload = 'metadata';
        tempVideo.addEventListener('loadedmetadata', function() {
            durationLabel.textContent = formatDuration(tempVideo.duration);
            durationLabel.style.display = 'block';
            tempVideo = null;
        });

        // — Botão play overlay —
        var playBtn = document.createElement('div');
        playBtn.className = 'vpb-play-button';
        thumbLink.appendChild(playBtn);

        // — Preview animado no hover (desktop) —
        var previewVideo = null;
        if (!isMobile) {
            previewVideo = document.createElement('video');
            previewVideo.className = 'vpb-video-preview';
            previewVideo.src = videoUrl;
            previewVideo.muted = true;
            previewVideo.loop  = true;
            previewVideo.preload = 'metadata';
            previewVideo.addEventListener('loadedmetadata', function() {
                previewVideo.currentTime = Math.min(10, previewVideo.duration * 0.2);
            });
            thumbLink.appendChild(previewVideo);

            thumbLink.addEventListener('mouseenter', function() {
                previewVideo.play().catch(function() {});
            });
            thumbLink.addEventListener('mouseleave', function() {
                previewVideo.pause();
                previewVideo.currentTime = Math.min(10, previewVideo.duration * 0.2);
            });
        }

        // — Clique abre lightbox —
        thumbLink.addEventListener('click', function(e) {
            e.preventDefault();
            openLightbox(videoUrl, posterUrl);
        });
    });

    // ==========================================
    // LIGHTBOX
    // ==========================================

    function openLightbox(videoUrl, posterUrl) {
        // Cria lightbox uma única vez
        var lightbox = document.getElementById('vpb-plyr-lightbox');
        if (!lightbox) {
            lightbox = document.createElement('div');
            lightbox.id = 'vpb-plyr-lightbox';
            lightbox.className = 'vpb-plyr-lightbox';
            lightbox.innerHTML =
                '<div class="vpb-plyr-lightbox-content">' +
                    '<button class="vpb-plyr-lightbox-close" aria-label="Fechar">&times;</button>' +
                '</div>';
            document.body.appendChild(lightbox);

            lightbox.querySelector('.vpb-plyr-lightbox-close').addEventListener('click', function() {
                closeLightbox();
            });
            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) closeLightbox();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && lightbox.classList.contains('active')) closeLightbox();
            });
        }

        // Destroi instância anterior corretamente
        if (vpbPlyrInstance) {
            vpbPlyrInstance.pause();
            vpbPlyrInstance.destroy();
            vpbPlyrInstance = null;
        }

        // Recria o elemento <video> do zero
        var lightboxContent = lightbox.querySelector('.vpb-plyr-lightbox-content');
        var oldVideo = lightboxContent.querySelector('video');
        if (oldVideo) oldVideo.remove();

        var videoEl = document.createElement('video');
        videoEl.className = 'vpb-plyr-video';
        videoEl.setAttribute('playsinline', '');
        videoEl.src = videoUrl;
        if (posterUrl) videoEl.poster = posterUrl;
        lightboxContent.appendChild(videoEl);

        // Abre
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
        history.pushState({ vpbLightbox: true }, '');

        // Inicializa Plyr
        if (typeof Plyr !== 'undefined') {
            vpbPlyrInstance = new Plyr(videoEl, {
                controls: [
                    'play-large', 'play', 'progress', 'current-time',
                    'duration', 'mute', 'volume', 'settings',
                    'pip', 'airplay', 'fullscreen'
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

            vpbPlyrInstance.play();
        }
    }

    function closeLightbox(viaHistory) {
        var lightbox = document.getElementById('vpb-plyr-lightbox');
        if (!lightbox) return;

        if (vpbPlyrInstance) {
            vpbPlyrInstance.pause();
        }

        lightbox.classList.remove('active');
        document.body.style.overflow = '';

        if (!viaHistory && history.state && history.state.vpbLightbox) {
            history.back();
        }
    }

    // ==========================================
    // PROTEÇÃO DE VÍDEOS - PMPRO (Bootstrap Modal)
    // ==========================================

    var accessDeniedModal = null;

    function initAccessDeniedModal() {
        var modalElement = document.getElementById('vpb-access-denied-modal');
        if (!modalElement) return;
        if (typeof bootstrap === 'undefined') return;

        accessDeniedModal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: true
        });

        modalElement.addEventListener('hidden.bs.modal', function() {
            document.querySelectorAll('.modal-backdrop').forEach(function(b) { b.remove(); });
            document.body.classList.remove('modal-open');
            document.body.style.overflow   = '';
            document.body.style.paddingRight = '';
        });
    }

    initAccessDeniedModal();

    document.querySelectorAll('.video-thumb.video-locked').forEach(function(thumb) {
        thumb.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var videoSlide = thumb.closest('.video-slide');
            var message   = (videoSlide && videoSlide.dataset.accessMessage) || 'Este vídeo é exclusivo para membros.';
            var plansUrl  = (videoSlide && videoSlide.dataset.plansUrl) || '';
            showAccessDeniedModal(message, plansUrl);
        });
    });

    function showAccessDeniedModal(message, plansUrl) {
        if (!accessDeniedModal) return;
        var messageEl = document.getElementById('vpb-access-message');
        if (messageEl) messageEl.textContent = message;

        // Atualiza o href do botão "Assinar Agora" se tiver URL do nível
        if (plansUrl) {
            var modalEl = document.getElementById('vpb-access-denied-modal');
            if (modalEl) {
                modalEl.querySelectorAll('a.btn-primary').forEach(function(btn) {
                    btn.setAttribute('href', plansUrl);
                });
            }
        }

        accessDeniedModal.show();
    }

    // ==========================================
    // HELPERS
    // ==========================================

    function formatDuration(seconds) {
        var hours   = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs    = Math.floor(seconds % 60);
        if (hours > 0) return hours + ':' + pad(minutes) + ':' + pad(secs);
        return minutes + ':' + pad(secs);
    }

    function pad(num) {
        return num < 10 ? '0' + num : String(num);
    }

});
