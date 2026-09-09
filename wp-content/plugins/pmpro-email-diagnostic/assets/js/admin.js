/**
 * PMPro Email Diagnostic - Admin JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        /**
         * Formulário de teste de email
         */
        const $testForm = $('.pmpro-test-form');
        
        if ($testForm.length) {
            $testForm.on('submit', function() {
                const $form = $(this);
                const $button = $form.find('.button-primary');
                
                // Adicionar estado de loading
                $form.addClass('loading');
                $button.prop('disabled', true);
                $button.text('⏳ Enviando email...');
                
                // O formulário vai submeter normalmente
                // O loading será removido no reload da página
            });
        }
        
        /**
         * Tooltip para erros nos logs
         */
        $('.status-badge.error').each(function() {
            const $badge = $(this);
            const errorMessage = $badge.attr('title');
            
            if (errorMessage) {
                $badge.on('click', function(e) {
                    e.preventDefault();
                    alert('Erro: ' + errorMessage);
                });
                
                $badge.css('cursor', 'help');
            }
        });
        
        /**
         * Auto-dismiss notices
         */
        setTimeout(function() {
            $('.notice.is-dismissible').fadeOut();
        }, 5000);
        
        /**
         * Confirmar antes de limpar logs (se existir)
         */
        $('.pmpro-clear-logs').on('click', function(e) {
            if (!confirm('Tem certeza que deseja limpar todos os logs?')) {
                e.preventDefault();
                return false;
            }
        });
        
        /**
         * Copiar comando para clipboard
         */
        $('.copy-to-clipboard').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const text = $button.data('copy');
            
            if (text) {
                navigator.clipboard.writeText(text).then(function() {
                    const originalText = $button.text();
                    $button.text('✅ Copiado!');
                    
                    setTimeout(function() {
                        $button.text(originalText);
                    }, 2000);
                }).catch(function() {
                    alert('Erro ao copiar para área de transferência');
                });
            }
        });
        
        /**
         * Atualizar preview do email ao mudar tipo
         */
        $('#test_type').on('change', function() {
            const type = $(this).val();
            const descriptions = {
                'wp_mail': 'Testa a função básica wp_mail() do WordPress',
                'pmpro_checkout': 'Testa o email que é enviado após um checkout',
                'pmpro_admin': 'Testa o email enviado para administradores',
                'pmpro_welcome': 'Testa o email de boas-vindas enviado aos novos membros'
            };
            
            // Atualizar descrição (se existir elemento)
            if ($('#test-description').length) {
                $('#test-description').text(descriptions[type] || '');
            }
        });
        
        /**
         * Highlight na linha da tabela ao passar o mouse
         */
        $('.wp-list-table tbody tr').hover(
            function() {
                $(this).css('background-color', '#f6f7f7');
            },
            function() {
                $(this).css('background-color', '');
            }
        );
        
        /**
         * Expandir/Colapsar seções (se implementado)
         */
        $('.postbox .hndle').on('click', function() {
            $(this).closest('.postbox').toggleClass('closed');
        });
        
    });
    
})(jQuery);
