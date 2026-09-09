<?php
/**
 * Template: Lost Password (Bebelume Customizado)
 * Version: 1.0
 *
 * Template de recuperação de senha customizado com estética Bebelume
 * Baseado no design do login.php e register.php
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

// Verificar se foi enviado
$email_sent = false;

// Processar o formulário de recuperação
if (isset($_POST['bbl_lostpassword_submit'])) {
    $user_login = sanitize_text_field($_POST['user_login']);
    
    if (empty($user_login)) {
        $pmpro_msg = 'Por favor, informe seu email.';
        $pmpro_msgt = 'pmpro_error';
    } else {
        // Usar a função do WordPress para recuperação de senha
        $result = retrieve_password($user_login);
        
        if (is_wp_error($result)) {
            $pmpro_msg = $result->get_error_message();
            $pmpro_msgt = 'pmpro_error';
        } else {
            $email_sent = true;
            $pmpro_msg = 'Enviamos um email com instruções para redefinir sua senha. Verifique sua caixa de entrada!';
            $pmpro_msgt = 'pmpro_success';
        }
    }
}
?>

<div class="pmpro-lostpassword-page <?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
    
    <?php
    /**
     * Fires before the lost password form.
     */
    do_action( 'pmpro_lostpassword_before_form' );
    ?>
    
    <section id="pmpro_lostpassword" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section pmpro_lostpassword' ) ); ?>">
        
        <?php if ($email_sent): ?>
            
            <!-- Mensagem de sucesso -->
            <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card', 'pmpro_lostpassword_success' ) ); ?>">
                <div class="bbl-checkout-header">
                    <div class="bbl-trust-badges">
                        <div class="trust-badge">
                            <i class="bi bi-envelope-check-fill"></i>
                            <span>Email Enviado</span>
                        </div>
                    </div>
                </div>
                
                <h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">
                    <i class="bi bi-check-circle-fill" style="color: #66BB6A;"></i> <?php esc_html_e( 'Email Enviado com Sucesso!', 'paid-memberships-pro' ); ?>
                </h2>
                
                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
                    <div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message pmpro_success' ) ); ?>">
                        <?php echo wp_kses_post( $pmpro_msg ); ?>
                    </div>
                    
                    <p style="text-align: center; color: #5A6C7D; margin: 20px 0;">
                        Não recebeu o email? Verifique sua pasta de spam ou lixo eletrônico.
                    </p>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="<?php echo home_url('/auth/login/'); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout' ) ); ?>" style="display: inline-block; width: auto; padding: 16px 32px;">
                            <i class="bi bi-arrow-left"></i> Voltar para Login
                        </a>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
        
            <form id="pmpro_lostpassword_form" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form' ) ); ?>" action="" method="post" autocomplete="on">
                
                <input type="hidden" name="bbl_lostpassword_submit" value="1" />
                
                <?php if($pmpro_msg) { ?>
                    <div role="alert" id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
                        <?php echo wp_kses_post( $pmpro_msg ); ?>
                    </div>
                <?php } ?>
                
                
                <!-- Card único com tudo -->
                <div id="pmpro_lostpassword_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
                    <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
                        
                        <h2 class="p-1 pl-0 pr-0 <?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">
                            <i class="bi bi-key-fill"></i> <?php esc_html_e( 'Esqueceu a Senha?', 'paid-memberships-pro' ); ?>
                        </h2>
                        
                        <p style="margin-bottom: 24px; color: #5A6C7D; font-size: 16px;">
                            <?php esc_html_e( 'Digite seu email para receber instruções de redefinição.', 'paid-memberships-pro' ); ?>
                        </p>
                        
                        <!-- Email -->
                        <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text' ) ); ?>" style="margin-bottom: 24px;">
                            <label for="user_login" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
                                <?php esc_html_e('Email', 'paid-memberships-pro' );?> <span class="pmpro_asterisk">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="user_login" 
                                id="user_login" 
                                class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text' ) ); ?>" 
                                value="<?php echo isset($_POST['user_login']) ? esc_attr($_POST['user_login']) : ''; ?>" 
                                required 
                                autocomplete="email" 
                                placeholder="Digite seu email" 
                                style="display: block !important; width: 100% !important;"
                            />
                        </div>
                        
                        <?php do_action( 'pmpro_lostpassword_after_fields' ); ?>
                        
                        <!-- Botão de Enviar -->
                        <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>" style="margin-top: 24px;">
                            <input 
                                type="submit" 
                                id="pmpro_btn-submit" 
                                name="wp-submit" 
                                class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout' ) ); ?>" 
                                value="<?php esc_attr_e('Enviar Email de Recuperação', 'paid-memberships-pro' );?>" 
                            />
                            
                            <div id="pmpro_processing_message" style="visibility: hidden; text-align: center; margin-top: 12px; color: #00BCD4; font-weight: 600;">
                                <?php esc_html_e("Enviando...", 'paid-memberships-pro' ); ?>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Footer do Card -->
                    <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
                        <a href="<?php echo home_url('/auth/login/'); ?>">
                            <i class="bi bi-arrow-left"></i> <?php esc_html_e('Voltar para o Login', 'paid-memberships-pro' ); ?>
                        </a>
                    </div>
                </div>
                
            </form>
        
        <?php endif; ?>
        
        <?php
        /**
         * Fires after the lost password form.
         */
        do_action( 'pmpro_lostpassword_after_form' );
        ?>
        
    </section>
    
</div>

<?php get_footer(); ?>