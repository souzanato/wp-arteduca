<?php
/**
 * Template: Reset Password (Bebelume Customizado)
 * Version: 1.0
 *
 * Template de redefinição de senha customizado com estética Bebelume
 * Esta é a página que o usuário acessa ao clicar no link do email
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

global $pmpro_msg, $pmpro_msgt;

// Se já estiver logado, redireciona
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

// Obter parâmetros da URL
$key = isset($_GET['key']) ? $_GET['key'] : '';
$login = isset($_GET['login']) ? $_GET['login'] : '';

// Validar link
$user = check_password_reset_key($key, $login);
$has_error = false;
$password_reset = false;

if (is_wp_error($user)) {
    $has_error = true;
    if ($user->get_error_code() === 'expired_key') {
        $pmpro_msg = 'Este link expirou. Por favor, solicite um novo link de recuperação de senha.';
    } else {
        $pmpro_msg = 'Link inválido. Por favor, solicite um novo link de recuperação de senha.';
    }
    $pmpro_msgt = 'pmpro_error';
}

// Processar o formulário de nova senha
if (isset($_POST['bbl_resetpass_submit']) && !$has_error) {
    $password = $_POST['pass1'];
    $password_confirm = $_POST['pass2'];
    
    // Validações
    if (empty($password)) {
        $pmpro_msg = 'Por favor, digite uma nova senha.';
        $pmpro_msgt = 'pmpro_error';
    } elseif (strlen($password) < 8) {
        $pmpro_msg = 'A senha deve ter no mínimo 8 caracteres.';
        $pmpro_msgt = 'pmpro_error';
    } elseif ($password !== $password_confirm) {
        $pmpro_msg = 'As senhas não coincidem. Por favor, tente novamente.';
        $pmpro_msgt = 'pmpro_error';
    } else {
        // Redefinir a senha
        reset_password($user, $password);
        $password_reset = true;
        $pmpro_msg = 'Sua senha foi redefinida com sucesso! Você já pode fazer login.';
        $pmpro_msgt = 'pmpro_success';
    }
}
?>

<div class="pmpro-resetpass-page <?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
    
    <section id="pmpro_resetpass" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section pmpro_resetpass' ) ); ?>">
        
        <?php if ($has_error): ?>
            
            <!-- Link inválido ou expirado -->
            <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
                <div class="bbl-checkout-header">
                    <div class="bbl-trust-badges">
                        <div class="trust-badge">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>Link Inválido</span>
                        </div>
                    </div>
                </div>
                
                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
                    <h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>" style="color: #E73665;">
                        <i class="bi bi-x-circle-fill"></i> <?php esc_html_e( 'Link Inválido ou Expirado', 'paid-memberships-pro' ); ?>
                    </h2>
                    
                    <div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_error' ) ); ?>">
                        <?php echo wp_kses_post( $pmpro_msg ); ?>
                    </div>
                    
                    <p style="text-align: center; margin: 20px 0;">
                        Clique no botão abaixo para solicitar um novo link de recuperação.
                    </p>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="<?php echo home_url('/auth/esqueceu-senha/'); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout' ) ); ?>" style="display: inline-block; width: auto; padding: 16px 32px;">
                            <i class="bi bi-arrow-repeat"></i> Solicitar Novo Link
                        </a>
                    </div>
                </div>
                
                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
                    <a href="<?php echo home_url('/auth/login/'); ?>">
                        <i class="bi bi-arrow-left"></i> Voltar para o Login
                    </a>
                </div>
            </div>
            
        <?php elseif ($password_reset): ?>
            
            <!-- Senha redefinida com sucesso -->
            <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
                <div class="bbl-checkout-header">
                    <div class="bbl-trust-badges">
                        <div class="trust-badge">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Senha Atualizada</span>
                        </div>
                    </div>
                </div>
                
                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
                    <h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>" style="color: #66BB6A;">
                        <i class="bi bi-check-circle-fill"></i> <?php esc_html_e( 'Senha Redefinida com Sucesso!', 'paid-memberships-pro' ); ?>
                    </h2>
                    
                    <div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_success' ) ); ?>">
                        <?php echo wp_kses_post( $pmpro_msg ); ?>
                    </div>
                    
                    <p style="text-align: center; margin: 20px 0;">
                        Agora você pode fazer login com sua nova senha.
                    </p>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="<?php echo home_url('/auth/login/'); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout' ) ); ?>" style="display: inline-block; width: auto; padding: 16px 32px; background: linear-gradient(135deg, #66BB6A 0%, #4CAF50 100%);">
                            <i class="bi bi-box-arrow-in-right"></i> Fazer Login Agora
                        </a>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
        
            <!-- Formulário de nova senha -->
            <form id="pmpro_resetpass_form" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form' ) ); ?>" action="" method="post" autocomplete="off">
                
                <input type="hidden" name="bbl_resetpass_submit" value="1" />
                <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>" />
                <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>" />
                
                <?php if($pmpro_msg) { ?>
                    <div role="alert" id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
                        <?php echo wp_kses_post( $pmpro_msg ); ?>
                    </div>
                <?php } ?>
                
                <!-- Card único -->
                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
                    <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
                        
                        <h2 class="p-0 pt-1 pb-1 <?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">
                            <i class="bi bi-key-fill"></i> <?php esc_html_e( 'Redefinir Senha', 'paid-memberships-pro' ); ?>
                        </h2>
                        
                        <p style="margin-bottom: 24px; color: #5A6C7D; font-size: 16px;">
                            <?php esc_html_e( 'Digite sua nova senha abaixo. Escolha uma senha forte com no mínimo 8 caracteres.', 'paid-memberships-pro' ); ?>
                        </p>
                        
                        <p style="margin-bottom: 24px; padding: 12px; background: #F0F8FF; border-left: 4px solid #00BCD4; border-radius: 8px; color: #2C3E50; font-size: 15px;">
                            <i class="bi bi-envelope-fill"></i> <strong>Email:</strong> <?php echo esc_html($login); ?>
                        </p>
                        
                        <!-- Nova Senha -->
                        <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-password' ) ); ?>" style="margin-bottom: 24px; position: relative;">
                            <label for="pass1" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
                                <?php esc_html_e('Nova Senha', 'paid-memberships-pro' );?> <span class="pmpro_asterisk">*</span>
                            </label>
                            <input 
                                type="password" 
                                name="pass1" 
                                id="pass1" 
                                class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password' ) ); ?>" 
                                value="" 
                                required 
                                autocomplete="new-password"
                                minlength="8"
                                placeholder="Digite sua nova senha (mín. 8 caracteres)"
                                style="display: block !important; width: 100% !important;"
                            />
                            <button type="button" class="pmpro_btn-password-toggle" onclick="togglePassword('pass1', this)" style="position: absolute; right: 16px; top: 52px; background: none; border: none; color: #00BCD4; cursor: pointer; font-size: 20px;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        
                        <!-- Confirmar Senha -->
                        <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-password' ) ); ?>" style="margin-bottom: 24px; position: relative;">
                            <label for="pass2" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
                                <?php esc_html_e('Confirmar Nova Senha', 'paid-memberships-pro' );?> <span class="pmpro_asterisk">*</span>
                            </label>
                            <input 
                                type="password" 
                                name="pass2" 
                                id="pass2" 
                                class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password' ) ); ?>" 
                                value="" 
                                required 
                                autocomplete="new-password"
                                minlength="8"
                                placeholder="Digite a senha novamente"
                                style="display: block !important; width: 100% !important;"
                            />
                            <button type="button" class="pmpro_btn-password-toggle" onclick="togglePassword('pass2', this)" style="position: absolute; right: 16px; top: 52px; background: none; border: none; color: #00BCD4; cursor: pointer; font-size: 20px;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        
                        <!-- Requisitos da senha -->
                        <div style="background: #FFF8E1; border-left: 4px solid #FFC107; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px;">
                            <p style="margin: 0; font-size: 14px; color: #5A6C7D; font-weight: 600;">
                                <i class="bi bi-info-circle"></i> Requisitos da senha:
                            </p>
                            <ul style="margin: 8px 0 0 0; padding-left: 24px; font-size: 14px; color: #5A6C7D;">
                                <li>Mínimo de 8 caracteres</li>
                                <li>Use letras, números e símbolos</li>
                                <li>Evite informações pessoais</li>
                            </ul>
                        </div>
                        
                        <!-- Botão de Enviar -->
                        <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>" style="margin-top: 24px;">
                            <input 
                                type="submit" 
                                id="pmpro_btn-submit" 
                                name="wp-submit" 
                                class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout' ) ); ?>" 
                                value="<?php esc_attr_e('Redefinir Minha Senha', 'paid-memberships-pro' );?>" 
                            />
                        </div>
                        
                    </div>
                    
                    <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
                        <a href="<?php echo home_url('/auth/login/'); ?>">
                            <i class="bi bi-arrow-left"></i> <?php esc_html_e('Voltar para o Login', 'paid-memberships-pro' ); ?>
                        </a>
                    </div>
                </div>
                
            </form>
            
            <!-- Script para mostrar/ocultar senha -->
            <script>
            function togglePassword(fieldId, button) {
                const field = document.getElementById(fieldId);
                const icon = button.querySelector('i');
                
                if (field.type === 'password') {
                    field.type = 'text';
                    icon.className = 'bi bi-eye-slash';
                } else {
                    field.type = 'password';
                    icon.className = 'bi bi-eye';
                }
            }
            
            // Validação de senhas iguais
            document.getElementById('pmpro_resetpass_form').addEventListener('submit', function(e) {
                const pass1 = document.getElementById('pass1').value;
                const pass2 = document.getElementById('pass2').value;
                
                if (pass1 !== pass2) {
                    e.preventDefault();
                    alert('As senhas não coincidem. Por favor, verifique e tente novamente.');
                    document.getElementById('pass2').focus();
                }
            });
            </script>
        
        <?php endif; ?>
        
    </section>
    
</div>

<?php get_footer(); ?>