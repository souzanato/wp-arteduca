(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Salvar perfil
        $('#bebelumeProfileForm').on('submit', function(e) {
            e.preventDefault();
            
            const newPass = $('#newPassword').val();
            const confirmPass = $('#confirmPassword').val();
            
            // Validar senha se estiver preenchida
            if (newPass || confirmPass) {
                if (newPass.length < 8) {
                    showToast('error', 'A senha deve ter no mínimo 8 caracteres');
                    return;
                }
                
                if (newPass !== confirmPass) {
                    showToast('error', 'As senhas não coincidem');
                    return;
                }
            }
            
            const $button = $('#saveProfile');
            $button.prop('disabled', true).text('Salvando...');
            
            const formData = {
                action: 'bbl_save_profile',
                nonce: bblProfileData.nonce,
                first_name: $('#firstName').val(),
                last_name: $('#lastName').val(),
            };
            
            // Adicionar senha se estiver preenchida
            if (newPass) {
                formData.new_password = newPass;
            }
            
            $.ajax({
                url: bblProfileData.ajaxUrl,
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showToast('success', response.data.message);
                        // Limpar campos de senha
                        $('#newPassword, #confirmPassword').val('');
                    } else {
                        showToast('error', response.data.message || 'Erro ao salvar');
                    }
                },
                error: function() {
                    showToast('error', 'Erro de conexão');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Salvar Alterações');
                }
            });
        });
        
        // Resetar formulário
        $('#resetForm').on('click', function() {
            if (confirm('Deseja descartar as alterações?')) {
                $('#bebelumeProfileForm')[0].reset();
                showToast('info', 'Alterações descartadas');
            }
        });
        
        // Validar senha em tempo real
        $('#newPassword').on('input', function() {
            const newPass = $(this).val();
            if (newPass.length > 0 && newPass.length < 8) {
                $(this).css('border-color', '#FF6B6B');
            } else {
                $(this).css('border-color', '');
            }
        });
        
        $('#confirmPassword').on('input', function() {
            const newPass = $('#newPassword').val();
            const confirmPass = $(this).val();
            
            if (confirmPass.length > 0 && newPass !== confirmPass) {
                $(this).css('border-color', '#FF6B6B');
            } else {
                $(this).css('border-color', '');
            }
        });
        
        // Função para mostrar toast
        function showToast(type, message) {
            const $toast = $('#toast');
            const $icon = $('#toastIcon');
            const $message = $('#toastMessage');
            
            $toast.removeClass('bbl-toast-show bbl-toast-success bbl-toast-error bbl-toast-info');
            
            if (type === 'success') {
                $toast.addClass('bbl-toast-success');
                $icon.text('✓');
            } else if (type === 'error') {
                $toast.addClass('bbl-toast-error');
                $icon.text('✕');
            } else {
                $toast.addClass('bbl-toast-info');
                $icon.text('ℹ');
            }
            
            $message.text(message);
            $toast.addClass('bbl-toast-show');
            
            setTimeout(function() {
                $toast.removeClass('bbl-toast-show');
            }, 4000);
        }
        
    });
    
})(jQuery);