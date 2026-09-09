<?php
/**
 * Template: Register
 * Version: 1.0
 *
 * Template de registro customizado com estética Bebelume
 * Baseado no design do checkout.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

global $current_user, $pmpro_msg, $pmpro_msgt;

// Se já estiver logado, redireciona para a conta
if (is_user_logged_in()) {
    wp_redirect(pmpro_url("account"));
    exit;
}

// Processar o formulário de registro
if (isset($_POST['bbl_register_submit'])) {
    $errors = array();
    
    // Validar campos
    $first_name = sanitize_text_field($_POST['bfirstname']);
    $last_name = sanitize_text_field($_POST['blastname']);
    $email = sanitize_email($_POST['bemail']);
    $confirm_email = sanitize_email($_POST['bconfirmemail']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    
    // Validações
    if (empty($first_name)) {
        $errors[] = 'Por favor, informe seu nome.';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Por favor, informe seu sobrenome.';
    }
    
    if (empty($email) || !is_email($email)) {
        $errors[] = 'Por favor, informe um email válido.';
    }
    
    if ($email !== $confirm_email) {
        $errors[] = 'Os emails não coincidem.';
    }
    
    if (email_exists($email)) {
        $errors[] = 'Este email já está cadastrado.';
    }
    
    if (empty($password)) {
        $errors[] = 'Por favor, informe uma senha.';
    }
    
    if (strlen($password) < 8) {
        $errors[] = 'A senha deve ter no mínimo 8 caracteres.';
    }
    
    if ($password !== $password2) {
        $errors[] = 'As senhas não coincidem.';
    }
    
    // Se não houver erros, criar o usuário
    if (empty($errors)) {
        // Gerar username baseado no email
        $username = sanitize_user(str_replace(['@', '.'], ['_', '_'], $email), true);
        
        // Garantir que é único
        $base_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . '_' . $counter;
            $counter++;
        }
        
        // Criar o usuário
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            $errors[] = 'Erro ao criar usuário: ' . $user_id->get_error_message();
        } else {
            // Atualizar informações do usuário
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name . ' ' . $last_name
            ));
            
            // Fazer login automaticamente
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            // Redirecionar para a página de níveis ou conta
            $redirect_url = apply_filters('bbl_register_redirect_url', home_url());
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    // Se houver erros, exibir
    if (!empty($errors)) {
        $pmpro_msg = implode('<br>', $errors);
        $pmpro_msgt = 'pmpro_error';
    }
}

// Obter valores dos campos para manter após erro
$bfirstname = isset($_POST['bfirstname']) ? sanitize_text_field($_POST['bfirstname']) : '';
$blastname = isset($_POST['blastname']) ? sanitize_text_field($_POST['blastname']) : '';
$bemail = isset($_POST['bemail']) ? sanitize_email($_POST['bemail']) : '';
$bconfirmemail = isset($_POST['bconfirmemail']) ? sanitize_email($_POST['bconfirmemail']) : '';

/**
 * Filter to set if PMPro uses email or text as the type for email field inputs.
 */
$pmpro_email_field_type = apply_filters('pmpro_email_field_type', true);
?>

<div class="pmpro-register-page <?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">

    <?php
    /**
     * Fires before the register form.
     */
    do_action( 'pmpro_register_before_form' );
    ?>

    <section id="pmpro_register" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section pmpro_register' ) ); ?>">

        <form id="pmpro_form" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form' ) ); ?>" action="" method="post">

            <input type="hidden" name="bbl_register_submit" value="1" />

            <?php if($pmpro_msg) { ?>
                <div role="alert" id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
                    <?php echo wp_kses_post( $pmpro_msg ); ?>
                </div>
            <?php } else { ?>
                <div id="pmpro_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message' ) ); ?>" style="display: none;"></div>
            <?php } ?>

            <?php
            /**
             * Fires after the register intro section.
             */
            do_action( 'pmpro_register_after_intro' );
            ?>

            <!-- Informações da Conta -->
            <fieldset id="pmpro_user_fields" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_user_fields' ) ); ?>">
                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
                    <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
                        <legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
                            <h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>">
                                <i class="bi bi-person-fill"></i> <?php esc_html_e( 'Criar Conta', 'paid-memberships-pro' ); ?>
                            </h2>
                        </legend>

                        <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">

                            <!-- Nome e Sobrenome -->
                            <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ); ?>">
                                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-bfirstname', 'pmpro_form_field-bfirstname' ) ); ?>">
                                    <label for="bfirstname" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
                                        <?php esc_html_e('Nome', 'paid-memberships-pro' );?> <span class="pmpro_asterisk">*</span>
                                    </label>
                                    <input id="bfirstname" name="bfirstname" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'bfirstname' ) ); ?>" value="<?php echo esc_attr($bfirstname); ?>" required autocomplete="given-name" />
                                </div>

                                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text pmpro_form_field-blastname', 'pmpro_form_field-blastname' ) ); ?>">
                                    <label for="blastname" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
                                        <?php esc_html_e('Sobrenome', 'paid-memberships-pro' );?> <span class="pmpro_asterisk">*</span>
                                    </label>
                                    <input id="blastname" name="blastname" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text', 'blastname' ) ); ?>" value="<?php echo esc_attr($blastname); ?>" required autocomplete="family-name" />
                                </div>
                            </div>

                            <?php do_action( 'pmpro_register_after_name' ); ?>

                            <!-- Email e Confirmar Email -->
                            <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ); ?>">
                                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-email pmpro_form_field-bemail', 'pmpro_form_field-bemail' ) ); ?>">
                                    <label for="bemail" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
                                        <?php esc_html_e('Email', 'paid-memberships-pro' );?> <span class="pmpro_asterisk">*</span>
                                    </label>
                                    <input id="bemail" name="bemail" type="<?php echo ( $pmpro_email_field_type ? 'email' : 'text' ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-email', 'bemail' ) ); ?>" value="<?php echo esc_attr( $bemail ); ?>" required autocomplete="email" />
                                </div>

                                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-email pmpro_form_field-bconfirmemail', 'pmpro_form_field-bconfirmemail' ) ); ?>">
                                    <label for="bconfirmemail" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
                                        <?php esc_html_e('Confirmar Email', 'paid-memberships-pro' );?> <span class="pmpro_asterisk">*</span>
                                    </label>
                                    <input id="bconfirmemail" name="bconfirmemail" type="<?php echo ( $pmpro_email_field_type ? 'email' : 'text' ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-email', 'bconfirmemail' ) ); ?>" value="<?php echo esc_attr( $bconfirmemail ); ?>" required autocomplete="email" />
                                </div>
                            </div>

                            <?php do_action( 'pmpro_register_after_email' ); ?>

                            <!-- Senha e Confirmar Senha -->
                            <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_cols-2' ) ); ?>">
                                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-password' ) ); ?>">
                                    <label for="password" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
                                        <?php esc_html_e( 'Senha', 'paid-memberships-pro' );?> <span class="pmpro_asterisk">*</span>
                                    </label>
                                    <input type="password" name="password" id="password" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password', 'password' ) ); ?>" autocomplete="new-password" spellcheck="false" value="" required />
                                    <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-password-toggle' ) ); ?>">
                                        <button type="button" class="pmpro_btn pmpro_btn-plain pmpro_btn-password-toggle hide-if-no-js" data-toggle="0">
                                            <span class="pmpro_icon pmpro_icon-eye" aria-hidden="true">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--pmpro--color--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </span>
                                            <span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field-password-toggle-state' ) ); ?>"><?php esc_html_e( 'Mostrar Senha', 'paid-memberships-pro' ); ?></span>
                                        </button>
                                    </div>
                                    <small class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint' ) ); ?>">
                                        <?php esc_html_e('Mínimo de 8 caracteres', 'paid-memberships-pro' );?>
                                    </small>
                                </div>

                                <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-password', 'pmpro_form_field-password2' ) ); ?>">
                                    <label for="password2" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>">
                                        <?php esc_html_e('Confirmar Senha', 'paid-memberships-pro' );?> <span class="pmpro_asterisk">*</span>
                                    </label>
                                    <input type="password" name="password2" id="password2" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-password', 'password2' ) ); ?>" autocomplete="new-password" spellcheck="false" value="" required />
                                </div>
                            </div>

                                        <!-- Botão de Submit -->
                            <div class="d-block <?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_submit' ) ); ?>">
                                <span id="pmpro_submit_span w-100">
                                    <input type="submit" id="pmpro_btn-submit" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout', 'pmpro_btn-submit-register' ) ); ?>" value="<?php esc_attr_e('Criar Minha Conta', 'paid-memberships-pro' );?>" />
                                </span>

                                <div id="pmpro_processing_message" style="visibility: hidden;">
                                    <?php esc_html_e("Processando...", 'paid-memberships-pro' ); ?>
                                </div>
                            </div>


                            <?php do_action( 'pmpro_register_after_password' ); ?>

                            <!-- Campo honeypot (anti-spam) -->
                            <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_hidden' ) ); ?>">
                                <label for="fullname"><?php esc_html_e('Full Name', 'paid-memberships-pro' );?></label>
                                <input id="fullname" name="fullname" type="text" value="" autocomplete="off" aria-hidden="true" aria-label="<?php esc_html_e( 'Do not fill this field out. Leave this blank.', 'paid-memberships-pro'); ?>"/>
                            </div>

                        </div> <!-- end pmpro_form_fields -->
                    </div> <!-- end pmpro_card_content -->

                    <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
                        <?php esc_html_e('Já tem uma conta?', 'paid-memberships-pro' );?> 
                        <a href="<?php echo esc_url( wp_login_url( home_url() ) ); ?>">
                            <?php esc_html_e('Fazer login', 'paid-memberships-pro' ); ?>
                        </a>
                    </div>

                </div> <!-- end pmpro_card -->
            </fieldset>

            <?php
            /**
             * Add additional checkout boxes to the register page.
             */
            do_action( 'pmpro_register_boxes' );
            ?>

            <?php do_action( 'pmpro_register_before_submit_button' ); ?>

            <?php if ( $pmpro_msg ) { ?>
                <div id="pmpro_message_bottom" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
                    <?php echo wp_kses_post( $pmpro_msg ); ?>
                </div>
            <?php } else { ?>
                <div id="pmpro_message_bottom" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message' ) ); ?>" style="display: none;"></div>
            <?php } ?>

        </form>

        <?php
        /**
         * Fires after the register form.
         */
        do_action( 'pmpro_register_after_form' );
        ?>

    </section>

</div>

<?php get_footer(); ?>