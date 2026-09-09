<?php
/**
 * Template: Login (Bebelume Customizado)
 * Version: 1.0
 * 
 * Template de login customizado com estética Bebelume
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

global $pmpro_msg, $pmpro_msgt;

// Processar login
if (isset($_POST['login_submit'])) {
    $username = sanitize_user($_POST['log']);
    $password = $_POST['pwd'];
    $remember = isset($_POST['rememberme']);
    
    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember
    );
    
    $user = wp_signon($creds, false);
    
    if (is_wp_error($user)) {
        $pmpro_msg = $user->get_error_message();
        $pmpro_msgt = 'pmpro_error';
    } else {
        // Redirecionar após login
        $redirect_to = home_url(); // Default: home
        
        // Se há redirect_to no POST (prioridade)
        if (isset($_POST['redirect_to']) && !empty($_POST['redirect_to'])) {
            $redirect_to = urldecode($_POST['redirect_to']);
            $redirect_to = wp_validate_redirect($redirect_to, home_url());
        }
        // Se há redirect_to na URL
        elseif (isset($_GET['redirect_to']) && !empty($_GET['redirect_to'])) {
            $redirect_to = urldecode($_GET['redirect_to']);
            $redirect_to = wp_validate_redirect($redirect_to, home_url());
        }
        
        wp_redirect($redirect_to);
        exit;
    }
}
?>

<div class="pmpro-login-page <?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
    
    <section id="pmpro_login" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section pmpro_login' ) ); ?>">
        
        <form id="pmpro_login_form" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form' ) ); ?>" action="" method="post">
            
            <?php if($pmpro_msg) { ?>
                <div role="alert" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
                    <?php echo wp_kses_post( $pmpro_msg ); ?>
                </div>
            <?php } ?>
            
            <!-- Header com badges -->
            <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
                
                <h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">
                    <i class="bi bi-box-arrow-in-right"></i> <?php esc_html_e( 'Entrar na Conta', 'paid-memberships-pro' ); ?>
                </h2>
                
                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
                    
                    <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
                        
                        <!-- Email -->
                        <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text' ) ); ?>">
                            <label for="log" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
                                <?php esc_html_e('Email', 'paid-memberships-pro' );?> <span class="pmpro_asterisk">*</span>
                            </label>
                            <input type="email" name="log" id="log" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text' ) ); ?>" value="" required autocomplete="email" placeholder="seu@email.com" />
                        </div>
                        
                        <!-- Senha -->
                        <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-password' ) ); ?>">
                            <label for="pwd" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
                                <?php esc_html_e('Senha', 'paid-memberships-pro' );?> <span class="pmpro_asterisk">*</span>
                            </label>
                            <input type="password" name="pwd" id="pwd" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password' ) ); ?>" value="" required autocomplete="current-password" />
                        </div>
                        
                        <!-- Lembrar-me -->
                        <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-checkbox' ) ); ?>">
                            <label class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_clickable' ) ); ?>">
                                <input type="checkbox" name="rememberme" id="rememberme" value="forever" />
                                <?php esc_html_e('Lembrar-me', 'paid-memberships-pro' );?>
                            </label>
                        </div>
                        
                    </div>
                    
                </div>

                            <!-- Botão de Login -->
                <div class="p-3 pt-0 m-0 <?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
                    <input type="hidden" name="login_submit" value="1" />
                    <input type="hidden" name="redirect_to" value="<?php echo isset($_GET['redirect_to']) ? esc_attr($_GET['redirect_to']) : ''; ?>" />
                    <input type="submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout' ) ); ?>" value="<?php esc_attr_e('Entrar', 'paid-memberships-pro' );?>" />
                </div>
                
                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
                    <a href="<?php echo home_url('/auth/esqueceu-senha/'); ?>">
                        <?php esc_html_e('Esqueceu a senha?', 'paid-memberships-pro' ); ?>
                    </a> | 
                    <a href="<?php echo home_url('/auth/registro/'); ?>">
                        Criar uma Conta
                    </a>
                </div>
                
            </div>
            
        </form>
        
    </section>
    
</div>

<?php get_footer(); ?>