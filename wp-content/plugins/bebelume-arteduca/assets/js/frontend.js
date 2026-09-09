/**
 * Frontend JavaScript - Bebelume ArtEduca
 * Gerencia abertura/fechamento dos accordions SEM Bootstrap
 */

(function() {
    'use strict';

    // Aguarda o DOM carregar
    document.addEventListener('DOMContentLoaded', function() {
        initAccordions();
    });

    function initAccordions() {
        // Seleciona todos os botões de accordion
        const buttons = document.querySelectorAll(
            '.bebelume-accordion-cabecalho-tipo1, .bebelume-accordion-botao-tipo2'
        );

        buttons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                toggleAccordion(this);
            });
        });
    }

    function toggleAccordion(button) {
        const targetId = button.getAttribute('data-target');
        const content = document.getElementById(targetId);
        const icon = button.querySelector('.bebelume-accordion-icone');
        
        if (!content) {
            console.error('Accordion content not found:', targetId);
            return;
        }

        const isExpanded = content.classList.contains('expandido');
        
        if (isExpanded) {
            // Fechar
            content.classList.remove('expandido');
            if (icon) icon.classList.remove('aberto');
            button.setAttribute('aria-expanded', 'false');
        } else {
            // Abrir
            content.classList.add('expandido');
            if (icon) icon.classList.add('aberto');
            button.setAttribute('aria-expanded', 'true');
        }
    }
})();