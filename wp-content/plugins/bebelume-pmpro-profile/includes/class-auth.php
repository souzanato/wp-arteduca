<?php
/**
 * BBL_Auth — Customização da autenticação nativa do WordPress (wp-login.php)
 *
 * Estratégia:
 *  - Todo o redesign é feito via JS no login_footer, após a DOM estar pronta
 *  - JS cria .bbl-card, injeta painel esquerdo, move #login para dentro do card
 *  - JS reordena os elementos do formulário (tabs/título/google ficam no topo)
 *  - CSS só estiliza, não tenta reorganizar o HTML
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_Auth {

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->hooks();
    }

    private function hooks(): void {
        add_action( 'login_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'login_head',            [ $this, 'inject_head' ] );
        add_action( 'login_footer',          [ $this, 'inject_redesign_js' ] );
        add_action( 'login_footer',          [ $this, 'inject_checkemail_ui' ], 20 );
        add_action( 'login_footer',          [ $this, 'inject_lostpassword_sent_ui' ], 20 );
        add_action( 'login_footer',          [ $this, 'inject_password_reset_success_ui' ], 20 );

        // Injeta os nossos elementos no form (serão reordenados via JS)
        add_action( 'login_form',        [ $this, 'inject_login_ui' ] );

        add_action( 'register_form',     [ $this, 'inject_register_ui' ], 1 );
        add_action( 'lostpassword_form', [ $this, 'inject_lostpassword_ui' ], 1 );
        add_action( 'resetpass_form',     [ $this, 'inject_resetpass_ui' ], 1 );

        add_action( 'login_header',          [ $this, 'inject_site_header' ] );
        add_filter( 'login_headerurl',      [ $this, 'logo_url' ] );
        add_filter( 'login_headertext',     [ $this, 'logo_title' ] );
        add_filter( 'login_site_html_link', '__return_empty_string' );
        add_filter( 'login_errors',         [ $this, 'translate_errors' ] );
        add_filter( 'login_messages',       [ $this, 'translate_messages' ] );
        add_filter( 'login_redirect',       [ $this, 'after_login_redirect' ],  10, 3 );
        add_filter( 'logout_redirect',      [ $this, 'after_logout_redirect' ], 10, 3 );
        add_filter( 'registration_redirect',[ $this, 'after_register_redirect' ] );

        // Nos satélites (canal/arteduca): garante que login_url aponte para wp-login.php
        if ( defined( 'BBL_SATELLITE_CANAL' ) || defined( 'BBL_SATELLITE_ARTEDUCA' ) ) {
            add_filter( 'login_url',         [ $this, 'force_wp_login_url' ], 99, 3 );
            add_action( 'template_redirect', [ $this, 'redirect_login_slug' ] );
        }
    }

    // ── Assets ───────────────────────────────────────────────────────────────

    public function enqueue_assets(): void {
        // Carrega o main.css do tema ativo — garante que o header fique idêntico ao site
        $theme_css = get_template_directory_uri() . '/assets/css/main.css';
        wp_enqueue_style(
            'bebelume-theme-main',
            $theme_css,
            [],
            defined( 'BEBELUME_VERSION' ) ? BEBELUME_VERSION : null
        );

        wp_enqueue_style(
            'bbl-login-fonts',
            'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap',
            [], null
        );
        wp_enqueue_style(
            'bbl-login',
            BBL_PMPro_URL . 'assets/css/login.css',
            [ 'bbl-login-fonts', 'bebelume-theme-main' ],
            BBL_PMPro_VERSION
        );

        wp_enqueue_script(
            'bbl-login-validation',
            BBL_PMPro_URL . 'assets/js/login-validation.js',
            [],
            BBL_PMPro_VERSION,
            true
        );
    }

    // ── Head ─────────────────────────────────────────────────────────────────

    public function inject_head(): void {
        ?>
        <style>
            :root {
                --bbl-pink:    #E73665;
                --bbl-cyan:    #00BCD4;
                --bbl-yellow:  #FFD54F;
                --bbl-green:   #66BB6A;
                --bbl-border:  #e0e7ed;
                --bbl-text-sec:#7a8999;
                --bbl-font:    'Nunito', sans-serif;
            }
        </style>
        <?php
    }

    // ── Elementos injetados no form (reordenados pelo JS) ─────────────────────

    public function inject_login_ui(): void {
        $this->render_tabs( 'login' );
        echo '<div class="bbl-form-header"><h2 class="bbl-form-title">Olá!</h2><p class="bbl-form-sub">Entre na sua conta Bebelume</p></div>';
        $this->render_google_button();
        $this->render_divider( 'OU COM E-MAIL' );
    }

    public function inject_login_footer_ui(): void {
        $lost_url = wp_lostpassword_url();
        echo '<p class="bbl-lost-password"><a href="' . esc_url( $lost_url ) . '">Esqueci minha senha</a></p>';
    }

    public function inject_register_ui(): void {
        $this->render_tabs( 'register' );
        echo '<div class="bbl-form-header"><h2 class="bbl-form-title">Bem-vindo!</h2><p class="bbl-form-sub">Crie sua conta Bebelume gratuitamente</p></div>';
        $this->render_google_button();
        $this->render_divider( 'OU PREENCHA OS DADOS' );
    }

    public function inject_lostpassword_ui(): void {
        $this->render_tabs( 'login' );
        echo '<div class="bbl-form-header"><h2 class="bbl-form-title">Esqueceu a senha?</h2><p class="bbl-form-sub">Informe seu e-mail para receber o link de redefinição</p></div>';
    }

    public function inject_resetpass_ui(): void {
        $this->render_tabs( 'login' );
        echo '<div class="bbl-form-header"><h2 class="bbl-form-title">Nova senha</h2><p class="bbl-form-sub">Escolha uma senha forte para sua conta</p></div>';
    }

    private function render_tabs( string $active ): void {
        $is_login = $active === 'login';
        ?>
        <div class="bbl-tabs">
            <a href="<?php echo esc_url( wp_login_url() ); ?>"
               class="bbl-tab<?php echo $is_login ? ' bbl-tab--active' : ''; ?>">Entrar</a>
            <a href="<?php echo esc_url( wp_registration_url() ); ?>"
               class="bbl-tab<?php echo ! $is_login ? ' bbl-tab--active' : ''; ?>">Cadastrar</a>
        </div>
        <?php
    }

    private function render_google_button(): void {
        ?>
        <a href="#" class="bbl-btn-google" id="bbl-google-login">
            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continuar com Google
        </a>
        <?php
    }

    private function render_divider( string $label ): void {
        echo '<div class="bbl-divider"><span>' . esc_html( $label ) . '</span></div>';
    }

    // ── Tela de confirmação de e-mail ────────────────────────────────────────────

    public function inject_checkemail_ui(): void {
        $checkemail = $_GET['checkemail'] ?? '';
        if ( $checkemail !== 'registered' ) {
            return;
        }

        // Tenta recuperar o user_id recém-registrado via cookie ou query var
        $user_id    = (int) ( $_COOKIE['bbl_last_registered'] ?? 0 );
        $resend_url = '';
        if ( $user_id && class_exists( 'BBL_Email_Confirmation' ) ) {
            $resend_url = add_query_arg( [
                'action'   => 'bbl_resend_confirmation',
                'user_id'  => $user_id,
                '_wpnonce' => wp_create_nonce( 'bbl_resend_' . $user_id ),
            ], wp_login_url() );
        }
        ?>
        <script>
        (function () {
            // Roda após o redesign ter montado .bbl-right
            var right = document.querySelector('.bbl-right');
            var login = document.getElementById('login');
            if ( ! right || ! login ) return;

            var content = '<div class="bbl-checkemail">' +
                '<div class="bbl-checkemail-icon">' +
                    '<svg width="64" height="64" viewBox="0 0 64 64" fill="none">' +
                        '<circle cx="32" cy="32" r="32" fill="#FFF0F3"/>' +
                        '<path d="M16 24a4 4 0 0 1 4-4h24a4 4 0 0 1 4 4v16a4 4 0 0 1-4 4H20a4 4 0 0 1-4-4V24z" fill="#E73665" opacity=".15"/>' +
                        '<path d="M16 24l16 11 16-11" stroke="#E73665" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>' +
                        '<rect x="16" y="24" width="32" height="20" rx="4" stroke="#E73665" stroke-width="2.5"/>' +
                    '</svg>' +
                '</div>' +
                '<h2 class="bbl-checkemail-title">Verifique seu e-mail</h2>' +
                '<p class="bbl-checkemail-msg">Enviamos um link de confirmação para o seu e-mail.<br>Acesse sua caixa de entrada e clique no link para ativar sua conta.</p>' +
                '<p class="bbl-checkemail-hint">Não recebeu? Verifique a pasta de spam.</p>' +
                <?php if ( $resend_url ) : ?>
                '<a href="<?php echo esc_js( $resend_url ); ?>" class="bbl-checkemail-resend">Enviar novamente</a>' +
                <?php endif; ?>
                '<a href="<?php echo esc_js( wp_login_url() ); ?>" class="bbl-checkemail-back">← Voltar</a>' +
            '</div>';

            login.innerHTML = content;
        })();
        </script>
        <?php
    }

    // ── Tela pós-esqueci senha ───────────────────────────────────────────────────

    public function inject_lostpassword_sent_ui(): void {
        $checkemail = $_GET['checkemail'] ?? '';
        if ( $checkemail !== 'confirm' ) {
            return;
        }
        ?>
        <script>
        (function () {
            var right = document.querySelector('.bbl-right');
            var login = document.getElementById('login');
            if ( ! right || ! login ) return;

            login.innerHTML =
                '<div class="bbl-checkemail">' +
                    '<div class="bbl-checkemail-icon">' +
                        '<svg width="64" height="64" viewBox="0 0 64 64" fill="none">' +
                            '<circle cx="32" cy="32" r="32" fill="#FFF0F3"/>' +
                            '<path d="M16 24a4 4 0 0 1 4-4h24a4 4 0 0 1 4 4v16a4 4 0 0 1-4 4H20a4 4 0 0 1-4-4V24z" fill="#E73665" opacity=".15"/>' +
                            '<path d="M16 24l16 11 16-11" stroke="#E73665" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>' +
                            '<rect x="16" y="24" width="32" height="20" rx="4" stroke="#E73665" stroke-width="2.5"/>' +
                        '</svg>' +
                    '</div>' +
                    '<h2 class="bbl-checkemail-title">Verifique seu e-mail</h2>' +
                    '<p class="bbl-checkemail-msg">Enviamos um link de redefinição de senha para o seu e-mail.<br>Acesse sua caixa de entrada e clique no link para redefinir sua senha.</p>' +
                    '<p class="bbl-checkemail-hint">Não recebeu? Verifique a pasta de spam.</p>' +
                    '<a href="<?php echo esc_js( wp_login_url() ); ?>" class="bbl-checkemail-back">← Voltar</a>' +
                '</div>';
        })();
        </script>
        <?php
    }

    // ── Tela pós-redefinição de senha ────────────────────────────────────────────

    public function inject_password_reset_success_ui(): void {
        // Detecta a mensagem de sucesso do WP após resetpass
        $action = $_GET['action'] ?? '';
        if ( $action !== 'resetpass' ) return;

        // Só injeta após o POST (senha redefinida)
        // O WP exibe ?action=resetpass com a mensagem na página após reset
        ?>
        <script>
        (function () {
            // Verifica se há mensagem de sucesso na página
            var msg = document.querySelector('.message');
            if ( ! msg ) return;
            if ( msg.textContent.indexOf('redefinida') === -1 && msg.textContent.indexOf('Senha') === -1 ) return;

            var login = document.getElementById('login');
            if ( ! login ) return;

            login.innerHTML =
                '<div class="bbl-checkemail">' +
                    '<div class="bbl-checkemail-icon">' +
                        '<svg width="64" height="64" viewBox="0 0 64 64" fill="none">' +
                            '<circle cx="32" cy="32" r="32" fill="#F0FFF4"/>' +
                            '<path d="M20 32l8 8 16-16" stroke="#66BB6A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>' +
                        '</svg>' +
                    '</div>' +
                    '<h2 class="bbl-checkemail-title">Senha redefinida!</h2>' +
                    '<p class="bbl-checkemail-msg">Sua senha foi alterada com sucesso.<br>Agora você já pode entrar na sua conta.</p>' +
                    '<a href="<?php echo esc_js( wp_login_url() ); ?>" class="bbl-checkemail-btn">Entrar</a>' +
                '</div>';
        })();
        </script>
        <?php
    }

    // ── JS principal — reconstrói toda a DOM ──────────────────────────────────

    public function inject_redesign_js(): void {
        $logo_url       = BBL_PMPro_URL . 'assets/images/bebelume-logo-fundo-transparente.png';
        $bg_url         = BBL_PMPro_URL . 'assets/images/bebelume-cama.png';
        $google_url     = wp_login_url() . '?action=google_login';
        ?>
        <script>
        (function () {

            /* ── 1. Cria o card wrapper ── */
            var body  = document.body;
            var login = document.getElementById('login');
            if ( ! login ) return;

            var card = document.createElement('div');
            card.className = 'bbl-card';
            body.appendChild(card);

            /* ── 2. Cria painel esquerdo ── */
            var left = document.createElement('div');
            left.className = 'bbl-left';
            left.innerHTML =
                '<div class="bbl-left-img" style="background-image:url(\'<?php echo esc_js( $bg_url ); ?>\')"></div>' +
                '<div class="bbl-left-overlay"></div>' +
                '<div class="bbl-left-content">' +
                '<img src="<?php echo esc_js( $logo_url ); ?>" alt="Bebelume" class="bbl-logo-img">' +
                '<p class="bbl-tagline">Arte e imaginação para a primeira infância</p>' +
                '</div>';
            card.appendChild(left);

            /* ── 3. Move #login para dentro do card ── */
            var right = document.createElement('div');
            right.className = 'bbl-right';
            right.appendChild(login);
            card.appendChild(right);

            body.classList.add('bbl-split');

            /* ── 4. Esconde elementos nativos desnecessários ── */
            var hide = ['h1', '#nav', '#backtoblog', '.language-switcher'];
            hide.forEach(function(sel) {
                var el = document.querySelector(sel);
                if (el) el.style.display = 'none';
            });

            /* ── 5. Reordena elementos do form ──
               Usa DocumentFragment para preservar ordem: tabs → título → google → divisor → campos
            */
            var form = login.querySelector('form');
            if ( form ) {
                var frag = document.createDocumentFragment();
                [
                    form.querySelector('.bbl-tabs'),
                    form.querySelector('.bbl-form-header'),
                    form.querySelector('.bbl-btn-google'),
                    form.querySelector('.bbl-divider'),
                ].forEach(function(el) {
                    if (el) frag.appendChild(el);
                });
                form.insertBefore(frag, form.firstChild);

                /* Oculta campo "Nome de usuário" nativo APENAS no cadastro */
                if (form.id === 'registerform') {
                    var userLoginField = form.querySelector('#user_login');
                    if (userLoginField) {
                        var wrap = userLoginField.closest('p') || userLoginField.parentNode;
                        if (wrap) wrap.style.display = 'none';
                    }
                }

                /* Oculta mensagem nativa "Cadastre-se nesse site" */
                login.querySelectorAll('.message').forEach(function(el) {
                    if (el.textContent.indexOf('Cadastre') !== -1 || el.textContent.indexOf('cadastre') !== -1) {
                        el.style.display = 'none';
                    }
                });
            }

            /* ── 6. Atualiza URL do botão Google ── */
            var googleBtn = document.getElementById('bbl-google-login');
            if (googleBtn) googleBtn.href = '<?php echo esc_js( $google_url ); ?>';

            /* ── 7. Injeta link "Esqueci minha senha" após o botão submit (só no loginform) ── */
            if (form && form.id === 'loginform') {
                var submitWrap = form.querySelector('.submit') || form.querySelector('[type="submit"]');
                var lostLink = document.createElement('p');
                lostLink.className = 'bbl-lost-password';
                lostLink.innerHTML = '<a href="<?php echo esc_js( wp_lostpassword_url() ); ?>">Esqueci minha senha</a>';
                if (submitWrap) {
                    var submitParent = submitWrap.closest('p') || submitWrap.parentNode;
                    submitParent.parentNode.insertBefore(lostLink, submitParent.nextSibling);
                } else {
                    form.appendChild(lostLink);
                }
            }

            /* ── 8. Troca label do campo de login para "E-mail" ── */
            var userLoginLabel = login.querySelector('label[for="user_login"]');
            if (userLoginLabel) {
                userLoginLabel.textContent = 'E-mail';
            }
            var userLoginInput = login.querySelector('#user_login');
            if (userLoginInput) {
                userLoginInput.setAttribute('placeholder', 'seu@email.com');
                userLoginInput.setAttribute('autocomplete', 'email');
            }

            /* ── 8. Move mensagens de erro/info para antes do form ── */
            var msgEls = login.querySelectorAll('#login_error, .message');
            msgEls.forEach(function(el) {
                if (form) form.insertBefore(el, form.firstChild);
            });

        })();
        </script>
        <?php
    }

    // ── Redireciona erro de conta não confirmada ─────────────────────────────────

    public function handle_unconfirmed_error( string $errors ): string {
        if ( strpos( $errors, 'bbl_unconfirmed::' ) === false ) {
            return $errors;
        }

        if ( preg_match( '/bbl_unconfirmed::(\d+)/', $errors, $m ) ) {
            $user_id = (int) $m[1];
            wp_redirect( add_query_arg( 'bbl_unconfirmed', $user_id, wp_login_url() ) );
            exit;
        }

        return $errors;
    }

    // ── Logo ─────────────────────────────────────────────────────────────────

    public function inject_site_header(): void {
        $logo_img = has_custom_logo()
            ? get_custom_logo()
            : '<a href="' . esc_url( home_url('/') ) . '" class="text-decoration-none fw-bold" style="color:#fff;font-size:1.4rem;">' . esc_html( get_bloginfo('name') ) . '</a>';
        ?>
        <style>
            /* Reseta o wp-login.php para não ter padding/logo nativos */
            body.login { padding-top: 0 !important; background: #f0f4f8; }
            #login h1 { display: none; }
            .custom-logo-link img { height: 3em; width: auto; }

            /* Replica exatamente o .site-header do tema */
            .site-header {
                background-color: #eb2a61;
                padding: 1rem 0;
                z-index: 9998;
                width: 100%;
                box-sizing: border-box;
                overflow: hidden;
            }
            .site-header .container-fluid { padding: 0 1.5rem; }
            .site-header .d-flex {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .btn-entrar {
                display: inline-block;
                font-size: 0.8rem;
                padding: 6px 14px;
                background: #fff;
                color: #eb2a61;
                border-radius: 999px;
                font-weight: 700;
                text-decoration: none;
                border: none;
                cursor: pointer;
            }
            .btn-entrar:hover { opacity: .85; color: #eb2a61; }
        </style>
        <header class="site-header">
            <div class="container-fluid">
                <div class="d-flex">
                    <div class="navbar-brand">
                        <?php echo $logo_img; ?>
                    </div>
                    <div class="header-user-area">
                        <a href="<?php echo esc_url( home_url('/') ); ?>"
                           class="btn btn-light px-4 py-2 rounded-pill fw-bold position-relative btn-entrar">
                            &#8592; VOLTAR
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <?php
    }

        public function logo_url(): string { return home_url(); }
    public function logo_title(): string { return get_bloginfo( 'name' ); }

    // ── Mensagens ─────────────────────────────────────────────────────────────

    public function translate_errors( string $errors ): string {
        $map = [
            'The username field is empty.'                                     => 'Por favor, informe seu e-mail.',
            'The password field is empty.'                                     => 'Por favor, informe sua senha.',
            'Invalid username or password.'                                    => 'E-mail ou senha incorretos.',
            '<strong>Error:</strong> Invalid username.'                        => '<strong>Erro:</strong> E-mail não encontrado.',
            '<strong>Error:</strong> The email address isn&#8217;t correct.'   => '<strong>Erro:</strong> E-mail inválido.',
            'Too many failed login attempts.'                                  => 'Muitas tentativas. Tente novamente em alguns minutos.',
            'Your password reset link appears to be invalid.'                  => 'O link de redefinição é inválido ou expirou.',
            'The link you followed has expired.'                               => 'O link expirou. Solicite um novo.',
            '<strong>Error:</strong>'                                          => '<strong>Erro:</strong>',
            'is not registered'                                                => 'não está cadastrado neste site.',
        ];
        return str_replace( array_keys( $map ), array_values( $map ), $errors );
    }

    public function translate_messages( string $messages ): string {
        $map = [
            'Check your email for the confirmation link.'                                      => 'Verifique seu e-mail para confirmar o registro.',
            'Please enter your username or email address.'                                     => 'Informe seu e-mail para receber o link de redefinição.',
            'A password reset email has been sent to the email address'                        => 'Um e-mail com o link foi enviado para',
            'Enter your new password below or generate one.'                                   => 'Digite sua nova senha abaixo.',
            'Your password has been reset.'                                                    => 'Senha redefinida com sucesso!',
            '<strong>Error:</strong> There is no account with that username or email address.' => '<strong>Erro:</strong> Nenhuma conta encontrada com esse e-mail.',
        ];
        return str_replace( array_keys( $map ), array_values( $map ), $messages );
    }

    // ── Login URL nos satélites ───────────────────────────────────────────────

    /**
     * Força que login_url() retorne wp-login.php (não /login ou /entrar do tema).
     * Ativo apenas nos satélites (canal/arteduca), identificados por BBL_SATELLITE_CANAL ou BBL_SATELLITE_ARTEDUCA.
     */
    public function force_wp_login_url( string $login_url, string $redirect, bool $force_reauth ): string {
        $base = site_url( 'wp-login.php', 'login' );
        if ( $redirect ) {
            $login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $base );
        } else {
            $login_url = $base;
        }
        if ( $force_reauth ) {
            $login_url = add_query_arg( 'reauth', '1', $login_url );
        }
        return $login_url;
    }

    /**
     * Redireciona páginas com slug de login para wp-login.php.
     * Evita que /login, /entrar ou /minha-conta abram a home em vez da tela de login.
     */
    public function redirect_login_slug(): void {
        if ( ! is_page() ) return;
        global $post;
        if ( $post && in_array( $post->post_name, [ 'login', 'entrar', 'minha-conta' ], true ) ) {
            $redirect = isset( $_GET['redirect_to'] ) ? sanitize_url( $_GET['redirect_to'] ) : '';
            wp_redirect( wp_login_url( $redirect ), 301 );
            exit;
        }
    }

    // ── Redirecionamentos ─────────────────────────────────────────────────────

    public function after_login_redirect( string $redirect_to, string $requested_redirect_to, $user ): string {
        if ( is_wp_error( $user ) ) return $redirect_to;
        if ( $user instanceof WP_User && $user->has_cap( 'manage_options' ) ) return admin_url();
        if ( ! empty( $requested_redirect_to ) ) {
            // wp_validate_redirect só aceita o mesmo domínio por padrão.
            // Abrimos para qualquer subdomínio *.bebelume.com.br.
            add_filter( 'allowed_redirect_hosts', function( $hosts ) {
                $hosts[] = 'bebelume.com.br';
                $hosts[] = 'canal.bebelume.com.br';
                $hosts[] = 'arteduca.bebelume.com.br';
                $hosts[] = 'hub.bebelume.com.br';
                return $hosts;
            } );
            $validated = wp_validate_redirect( $requested_redirect_to, '' );
            if ( $validated ) return $validated;
        }
        return home_url();
    }

    public function after_logout_redirect( string $redirect_to, string $requested_redirect_to, $user ): string {
        return wp_login_url();
    }

    public function after_register_redirect( string $redirect_to ): string {
        return wp_login_url() . '?checkemail=registered';
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Auth::get_instance();
} );
