/**
 * Bebelume Theme - Main JavaScript
 * 
 * @package Bebelume_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Gerenciamento do Spinner de Loading
     */
    function initSpinner() {
        // Função para esconder o spinner
        function hideSpinner() {
            var spinner = document.getElementById('page-spinner');
            if (spinner) {
                spinner.classList.add('hidden');
                
                // Remove o elemento do DOM após a transição
                setTimeout(function() {
                    spinner.remove();
                }, 500);
            }
        }
        
        // Aguarda o carregamento completo do DOM e todos os recursos
        if (document.readyState === 'complete') {
            // Se já estiver carregado, esconde imediatamente
            hideSpinner();
        } else {
            // Aguarda o evento de load (DOM + imagens + CSS + scripts)
            window.addEventListener('load', hideSpinner);
        }
    }

    /**
     * Quando o documento estiver pronto
     */
    $(document).ready(function() {
        
        // Inicializa o spinner
        initSpinner();
        
        // Inicializa outras funcionalidades
        bebelumeInit();
        
    });

    /**
     * Inicialização do tema
     */
    function bebelumeInit() {
        handleAndarHover();
        handleKeyboardNavigation();
        addLoadingAnimation();
    }

    /**
     * Efeitos de hover nos andares
     */
    function handleAndarHover() {
        $('.andar-link').on('mouseenter', function() {
            $(this).closest('.bebelume-section').addClass('andar-hover');
        });

        $('.andar-link').on('mouseleave', function() {
            $(this).closest('.bebelume-section').removeClass('andar-hover');
        });
    }

    /**
     * Navegação por teclado (acessibilidade)
     */
    function handleKeyboardNavigation() {
        $('.andar-link').on('keydown', function(e) {
            // Enter ou Espaço
            if (e.which === 13 || e.which === 32) {
                e.preventDefault();
                window.location.href = $(this).attr('href');
            }
        });
    }

    /**
     * Animação de loading ao clicar nos andares
     */
    function addLoadingAnimation() {
        $('.andar-link').on('click', function() {
            $(this).addClass('andar-loading');
        });
    }

    /**
     * Smooth scroll (caso precise no futuro)
     */
    function smoothScroll() {
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            var target = $(this.getAttribute('href'));
            
            if (target.length) {
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 800);
            }
        });
    }

    /**
     * Detecta se é dispositivo touch
     */
    function isTouchDevice() {
        return ('ontouchstart' in window) || 
               (navigator.maxTouchPoints > 0) || 
               (navigator.msMaxTouchPoints > 0);
    }

    /**
     * Ajusta comportamento para dispositivos touch
     */
    if (isTouchDevice()) {
        $('body').addClass('touch-device');
    }


    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('userMenuToggle');
        const menu = document.getElementById('userMenu');
        
        if (!toggle || !menu) return;
        
        // Toggle dropdown
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            
            if (isExpanded) {
                closeMenu();
            } else {
                openMenu();
            }
        });
        
        // Fechar ao clicar fora
        document.addEventListener('click', function(e) {
            if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                closeMenu();
            }
        });
        
        // Fechar com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMenu();
            }
        });
        
        // Recalcular posição ao scroll/resize
        window.addEventListener('scroll', updateMenuPosition);
        window.addEventListener('resize', updateMenuPosition);
        
        function openMenu() {
            updateMenuPosition();
            toggle.setAttribute('aria-expanded', 'true');
            menu.setAttribute('aria-hidden', 'false');
        }
        
        function closeMenu() {
            toggle.setAttribute('aria-expanded', 'false');
            menu.setAttribute('aria-hidden', 'true');
        }
        
        function updateMenuPosition() {
            // Pegar posição do botão
            const rect = toggle.getBoundingClientRect();
            
            // Calcular posição do menu
            const top = rect.bottom + 12; // 12px de espaço
            const right = window.innerWidth - rect.right;
            
            // Aplicar posição
            menu.style.top = top + 'px';
            menu.style.right = right + 'px';
        }
    });

})(jQuery);