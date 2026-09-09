<?php
/**
 * BBL_Checkout — Customizações do checkout via hooks e filtros
 * Sem override de template — compatível com PMPro 3.7.3
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BBL_Checkout {

    // Datas da última revisão dos textos exibidos no checkout (fixas — não o dia corrente).
    const TERMOS_ATUALIZACAO     = '06/09/2026';
    const PRIVACIDADE_ATUALIZACAO = '04/09/2026';

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        if ( ! function_exists( 'pmpro_url' ) ) return;
        $this->hooks();
    }

    private function hooks(): void {
        add_action( 'wp_enqueue_scripts',              [ $this, 'enqueue_assets' ] );
        add_action( 'pmpro_checkout_before_form',      [ $this, 'render_plan_summary' ] );
        add_action( 'pmpro_checkout_after_payment_information_fields', [ $this, 'render_trust_signals' ] );

        // Ícones SVG nos títulos dos cards PMPro nativos
        add_action( 'pmpro_checkout_boxes', [ $this, 'inject_card_icons' ] );
        add_filter( 'pmpro_checkout_default_submit_button', '__return_false' );
        add_action( 'pmpro_checkout_before_submit_button', [ $this, 'render_terms_checkbox' ] );
        add_action( 'pmpro_checkout_before_submit_button', [ $this, 'render_submit_button' ] );
        add_filter( 'pmpro_processing_message',        fn() => 'Processando seu pagamento...' );
        add_filter( 'pmpro_level_cost_text',           [ $this, 'translate_cost_text' ], 10, 3 );
        add_filter( 'gettext',                         [ $this, 'translate_strings' ], 10, 3 );
        add_action( 'wp_enqueue_scripts',              [ $this, 'enqueue_cep_script' ] );
        add_action( 'wp_footer',                       [ $this, 'move_trust_signals_into_card' ], 5 );
        add_filter( 'pmpro_registration_checks',       [ $this, 'validate_terms_checkbox' ] );
        add_action( 'wp_footer',                       [ $this, 'render_logged_user_box' ], 5 );
    }

    public function enqueue_assets(): void {
        if ( ! function_exists( 'pmpro_is_checkout' ) || ! pmpro_is_checkout() ) return;
        wp_enqueue_style( 'bbl-pmpro', BBL_PMPro_URL . 'assets/css/pmpro.css', [], BBL_PMPro_VERSION );
        wp_enqueue_style( 'bbl-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap', [], null );
    }

    public function render_plan_summary( $pmpro_level ): void {
        if ( empty( $pmpro_level ) || empty( $pmpro_level->id ) ) return;

        $preco   = 'R$ ' . number_format( (float) $pmpro_level->billing_amount, 2, ',', '.' );
        $periodo = match( strtolower( $pmpro_level->cycle_period ) ) {
            'month' => '/mês',
            'year'  => '/ano',
            'week'  => '/semana',
            default => '',
        };

        $has_trial = ( floatval( $pmpro_level->initial_payment ) === 0.0 && floatval( $pmpro_level->billing_amount ) > 0 );

        // Próxima cobrança
        $proxima = '';
        if ( ! empty( $pmpro_level->cycle_number ) && ! empty( $pmpro_level->cycle_period ) ) {
            $interval = (int) $pmpro_level->cycle_number;
            $map      = [ 'day' => 'day', 'week' => 'week', 'month' => 'month', 'year' => 'year' ];
            $p        = strtolower( $pmpro_level->cycle_period );
            if ( isset( $map[ $p ] ) ) {
                $proxima = date_i18n( 'd/m/Y', strtotime( '+' . $interval . ' ' . $map[ $p ] ) );
            }
        }
        ?>
        <div class="bbl-checkout-hero">
            <div class="bbl-checkout-hero-top">
                <span class="bbl-checkout-hero-label">Você está assinando</span>
                <h2 class="bbl-checkout-hero-name"><?php echo esc_html( $pmpro_level->name ); ?></h2>
            </div>
            <div class="bbl-checkout-hero-price-row">
                <span class="bbl-checkout-hero-price"><?php echo esc_html( $preco ); ?></span>
                <span class="bbl-checkout-hero-period"><?php echo esc_html( $periodo ); ?></span>
                <?php if ( $has_trial ) : ?>
                <span class="bbl-checkout-hero-trial">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                    Período gratuito incluído
                </span>
                <?php endif; ?>
            </div>
            <p class="bbl-checkout-hero-micro">
                <?php echo $has_trial
                    ? 'Comece grátis hoje. Cancele quando quiser, sem burocracia.'
                    : 'Comece agora e cancele quando quiser, sem burocracia.'; ?>
            </p>
            <?php if ( $proxima || $has_trial ) : ?>
            <div class="bbl-checkout-hero-meta">
                <span class="bbl-checkout-hero-meta-item">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Cobrança hoje: <strong><?php echo $has_trial ? 'R$&nbsp;0,00' : esc_html( $preco ); ?></strong>
                </span>
                <?php if ( $proxima ) : ?>
                <span class="bbl-checkout-hero-meta-sep">·</span>
                <span class="bbl-checkout-hero-meta-item">
                    Próxima: <strong><?php echo esc_html( $proxima . ' — ' . $preco ); ?></strong>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function inject_card_icons(): void {
        $icons = [
            '#pmpro_pricing_fields'            => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
            '#pmpro_user_fields'               => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            '#pmpro_payment_information_fields' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
            '#pmpro_billing_address_fields'    => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        ];

        $js_icons = json_encode( $icons );
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var icons = <?php echo $js_icons; ?>;
            Object.keys(icons).forEach(function(selector) {
                var fieldset = document.querySelector(selector);
                if (!fieldset) return;
                var el = fieldset.querySelector('.pmpro_form_heading, .pmpro_card_title');
                if (!el) return;
                // Não injeta se já tem SVG (cards bbl já têm)
                if (el.querySelector('svg')) return;
                el.style.display = 'flex';
                el.style.alignItems = 'center';
                el.style.gap = '8px';
                var span = document.createElement('span');
                span.innerHTML = icons[selector];
                span.style.flexShrink = '0';
                span.style.color = '#eb2a61';
                el.insertBefore(span, el.firstChild);
            });
        });
        </script>
        <?php
    }

    public function render_trust_signals(): void {
        ?>
        <div class="bbl-checkout-trust">
            <div class="bbl-checkout-trust-badges">
                <span class="bbl-checkout-trust-item">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Pagamento seguro
                </span>
                <span class="bbl-checkout-trust-item">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Dados protegidos
                </span>
                <span class="bbl-checkout-trust-item">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-.08-8.92"/></svg>
                    Cancele quando quiser
                </span>
            </div>
            <div class="bbl-checkout-trust-cards">
                <svg viewBox="0 0 38 24" width="36" height="22" role="img" aria-label="Visa"><rect width="38" height="24" rx="4" fill="#1A1F71"/><text x="7" y="17" font-size="11" font-weight="bold" fill="#fff" font-family="Arial">VISA</text></svg>
                <svg viewBox="0 0 38 24" width="36" height="22" role="img" aria-label="Mastercard"><rect width="38" height="24" rx="4" fill="#252525"/><circle cx="15" cy="12" r="7" fill="#EB001B"/><circle cx="23" cy="12" r="7" fill="#F79E1B"/><path d="M19 6.8a7 7 0 0 1 0 10.4A7 7 0 0 1 19 6.8z" fill="#FF5F00"/></svg>
                <svg viewBox="0 0 38 24" width="36" height="22" role="img" aria-label="Elo"><rect width="38" height="24" rx="4" fill="#fff" stroke="#e0e0e0"/><text x="6" y="17" font-size="11" font-weight="bold" fill="#333" font-family="Arial">Elo</text></svg>
                <svg viewBox="0 0 38 24" width="36" height="22" role="img" aria-label="Stripe"><rect width="38" height="24" rx="4" fill="#635BFF"/><text x="5" y="17" font-size="10" font-weight="bold" fill="#fff" font-family="Arial">stripe</text></svg>
            </div>
        </div>
        <?php
    }

    public function render_submit_button(): void {
        global $pmpro_requirebilling;
        $label = $pmpro_requirebilling ? 'Começar minha assinatura' : 'Confirmar assinatura';
        ?>
        <div class="bbl-checkout-pre-cta">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Pagamento seguro · Dados protegidos · Cancele quando quiser
        </div>
        <span id="pmpro_submit_span">
            <input type="hidden" name="submit-checkout" value="1" />
            <button type="submit" id="pmpro_btn-submit"
                class="pmpro_btn pmpro_btn-submit-checkout bbl-checkout-btn"
                name="submit-checkout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <?php echo esc_html( $label ); ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" style="margin-left:2px"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
        </span>
        <?php
    }

    public function translate_cost_text( string $cost_text, $level, $tags ): string {
        $find    = [ 'The price for membership is', 'now and, then', 'now and,', 'now and then', ' now and ', 'per month', 'per Month', 'per year', 'per Year', 'per week', 'per day', 'an initial payment of', ' then ', ' and then ', 'free trial', ' trial', 'for free', 'and a ' ];
        $replace = [ 'O valor é', 'agora e depois', 'agora e', 'agora e depois', ' agora e depois ', 'por mês', 'por mês', 'por ano', 'por ano', 'por semana', 'por dia', 'entrada de', ', depois ', ', depois ', 'período gratuito', ' experimental', 'gratuitamente', 'com ' ];
        return str_ireplace( $find, $replace, $cost_text );
    }

    public function translate_strings( string $translated, string $text, string $domain ): string {
        if ( $domain !== 'paid-memberships-pro' ) return $translated;

        static $map = null;
        if ( $map === null ) {
            $map = [
                'Membership Information'   => 'Resumo do plano',
                'Account Information'      => 'Dados da conta',
                'Billing Address'          => 'Endereço de cobrança',
                'Payment Information'      => 'Pagamento',
                'Username'                 => 'Nome de usuário',
                'Password'                 => 'Senha',
                'Confirm Password'         => 'Confirmar senha',
                'Show Password'            => 'Mostrar senha',
                'Hide Password'            => 'Ocultar senha',
                'Email Address'            => 'E-mail',
                'Confirm Email Address'    => 'Confirmar e-mail',
                'First Name'               => 'Nome',
                'Last Name'                => 'Sobrenome',
                'Address 1'                => 'Endereço',
                'Address 2'                => 'Complemento',
                'City'                     => 'Cidade',
                'State'                    => 'Estado',
                'Postal Code'              => 'CEP',
                'Country'                  => 'País',
                'Phone'                    => 'Telefone',
                'Card Number'              => 'Número do cartão',
                'Expiration Date'          => 'Validade',
                'Security Code (CVC)'      => 'Código de segurança (CVV)',
                'Discount Code'            => 'Cupom de desconto',
                'Apply'                    => 'Aplicar',
                'Do you have a discount code?' => 'Tem um cupom?',
                'Click here to enter your discount code' => 'Clique para inserir',
                'Click here to change your discount code' => 'Alterar cupom',
                'Already have an account?' => '',
                'Log in here'              => '',
                'Submit and Check Out'     => 'Finalizar pagamento',
                'Submit and Confirm'       => 'Confirmar assinatura',
                'Complete Payment'         => 'Concluir pagamento',
                'Processing...'            => 'Processando...',
                'You have selected the %s membership level.' => 'Você selecionou o plano %s.',
                'Use Link'                 => 'Link salvo',
                // Strings do bloco "Dados da conta" quando logado
                'You are logged in as %s.'                         => 'Você está logado como %s.',
                'If you would like to use a different account for this membership,' => 'Se quiser usar outra conta,',
                'log out now'                                      => 'sair agora',
                // Texto de custo com trial
                'The price for membership is %s now and then %s.'  => 'O valor é %s agora e depois %s.',
                'The price for membership is %s now and then %s per %s.' => 'O valor é %s agora e depois %s por %s.',
                'now and'                                          => 'agora e depois',
                'then'                                             => 'depois',
                'per'                                              => 'por',
                'month'                                            => 'mês',
                'year'                                             => 'ano',
                'day'                                              => 'dia',
                'week'                                             => 'semana',
                'months'                                           => 'meses',
                'years'                                            => 'anos',
                'days'                                             => 'dias',
                'weeks'                                            => 'semanas',
                // Erro de email já cadastrado
                'That email address is already in use. Please <a href="%s">log in</a>, or use a different email address.'
                    => 'Este e-mail já está cadastrado. Por favor, <a href="%s">faça login</a> ou use outro e-mail.',
                'That email address is already in use. Please log in, or use a different email address.'
                    => 'Este e-mail já está cadastrado. Por favor, faça login ou use outro e-mail.',
                // Erros de pagamento e formulário
                'Invalid discount code.'                           => 'Cupom inválido.',
                'Discount code applied.'                           => 'Cupom aplicado.',
                'That discount code is not valid for this level.'  => 'Este cupom não é válido para este plano.',
                'That discount code has expired.'                  => 'Este cupom expirou.',
                'That discount code has been used the maximum number of times.' => 'Este cupom atingiu o limite de usos.',
                'Your payment was declined. Please check your credit card information and try again.' => 'Seu pagamento foi recusado. Confira os dados do cartão e tente novamente.',
                'Please enter a valid credit card number.'         => 'Por favor, informe um número de cartão válido.',
                'Please enter a valid expiration date.'            => 'Por favor, informe uma data de validade válida.',
                'Please enter a valid security code.'              => 'Por favor, informe um código de segurança válido.',
                'Please enter your credit card number.'            => 'Por favor, informe o número do cartão.',
                'Please enter your credit card expiration date.'   => 'Por favor, informe a validade do cartão.',
                'Please enter your credit card security code.'     => 'Por favor, informe o CVV do cartão.',
                'Please enter your first name.'                    => 'Por favor, informe seu nome.',
                'Please enter your last name.'                     => 'Por favor, informe seu sobrenome.',
                'Please enter a valid email address.'              => 'Por favor, informe um e-mail válido.',
                'Please enter your email address.'                 => 'Por favor, informe seu e-mail.',
                'Please enter a password.'                         => 'Por favor, crie uma senha.',
                'Please enter a valid zip/postal code.'            => 'Por favor, informe um CEP válido.',
                'Your email addresses do not match.'               => 'Os e-mails informados não coincidem.',
                'Your passwords do not match.'                     => 'As senhas não coincidem.',
                'There was an error processing your payment. Please try again.' => 'Ocorreu um erro ao processar seu pagamento. Tente novamente.',
            ];
        }

        return $map[ $text ] ?? $translated;
    }

    public function move_trust_signals_into_card(): void {
        if ( ! function_exists( 'pmpro_is_checkout' ) || ! pmpro_is_checkout() ) return;
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var trust = document.querySelector('.bbl-checkout-trust');
            var payCard = document.querySelector('#pmpro_payment_information_fields .pmpro_card_content');
            if (trust && payCard) {
                payCard.appendChild(trust);
            }
            // Mensagem "cancele a qualquer momento" dentro do card de pagamento
            if (payCard) {
                var msg = document.createElement('p');
                msg.className = 'bbl-cancel-anytime';
                msg.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-.08-8.92"/></svg> Assine agora e cancele a qualquer momento.';
                payCard.appendChild(msg);
            }
        });
        </script>
        <?php
    }

    /**
     * Bloco customizado de usuário logado no checkout.
     * Esconde #pmpro_account_loggedin e injeta nosso box via JS dentro do card.
     */
    public function render_logged_user_box(): void {
        if ( ! is_user_logged_in() ) return;
        if ( ! function_exists( 'pmpro_is_checkout' ) || ! pmpro_is_checkout() ) return;

        $user      = wp_get_current_user();
        $first     = esc_js( $user->first_name ?: $user->display_name );
        $full_name = esc_js( trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name );
        $email     = esc_js( $user->user_email );
        $logout    = esc_js( wp_logout_url( get_permalink() ) );
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Esconde o div padrão do PMPro
            var loggedInDiv = document.getElementById('pmpro_account_loggedin');
            if (loggedInDiv) loggedInDiv.style.display = 'none';

            // 2. Injeta nosso box dentro do card de dados da conta
            var card = document.querySelector('#pmpro_user_fields .pmpro_card_content');
            if (!card) return;

            var box = document.createElement('div');
            box.className = 'bbl-logged-user-box';
            box.innerHTML =
                '<div class="bbl-logged-user-greeting">Olá, <strong><?php echo $first; ?></strong>! Você está comprando como:</div>' +
                '<div class="bbl-logged-user-data">' +
                    '<div class="bbl-data-item"><label>Nome</label><span><?php echo $full_name; ?></span></div>' +
                    '<div class="bbl-data-item"><label>E-mail</label><span><?php echo $email; ?></span></div>' +
                '</div>' +
                '<a href="<?php echo $logout; ?>" class="bbl-logged-user-switch">Não é você? Entrar com outra conta</a>';

            card.appendChild(box);
        });
        </script>
        <?php
    }

    /**
     * Checkbox de aceite dos termos + modais Bootstrap.
     */
    public function render_terms_checkbox(): void {
        if ( ! function_exists( 'pmpro_is_checkout' ) || ! pmpro_is_checkout() ) return;
        ?>
        <div class="pmpro_card bbl-terms-card">
            <div class="pmpro_card_content">
                <label class="bbl-terms-label">
                    <input type="checkbox" id="bbl_accept_terms" name="bbl_accept_terms" value="1" class="bbl-terms-check" />
                    <span>
                        Li e aceito os
                        <a href="#" data-bs-toggle="modal" data-bs-target="#bbl-modal-termos" class="bbl-terms-link">Termos de Uso</a>
                        e a
                        <a href="#" data-bs-toggle="modal" data-bs-target="#bbl-modal-privacidade" class="bbl-terms-link">Política de Privacidade</a>
                        do Bebelume.
                    </span>
                </label>
            </div>
        </div>

        <!-- Modal Termos de Uso -->
        <div class="modal fade" id="bbl-modal-termos" tabindex="-1" aria-labelledby="bbl-termos-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content" style="border-radius:14px;">
                    <div class="modal-header" style="border-bottom:1px solid #e4e8ed;padding:1.25rem 1.5rem;">
                        <h5 class="modal-title" id="bbl-termos-title" style="font-family:'Nunito',sans-serif;font-weight:700;font-size:16px;">Termos de Uso</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body" style="padding:1.5rem;font-family:'Nunito',sans-serif;font-size:14px;line-height:1.7;color:#333;">
                        <p><strong>Última atualização:</strong> <?php echo esc_html( self::TERMOS_ATUALIZACAO ); ?></p>

                        <p>Bem-vindo ao <strong>Bebelume</strong>! Ao assinar um de nossos planos, você concorda com os Termos de Uso abaixo. Leia com atenção antes de prosseguir.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">1. Sobre o Bebelume</h6>
                        <p>O Bebelume é uma plataforma digital de conteúdo educativo e cultural voltada à primeira infância, operada por pessoa jurídica com sede em Brasília/DF. Oferecemos conteúdo por meio de assinatura mensal ou anual nos canais <strong>Canal Bebelume</strong> e <strong>Bebelume ArtEduca</strong>.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">2. Assinatura e Pagamento</h6>
                        <p>A assinatura é cobrada de forma recorrente (mensal ou anual) em cartão de crédito, processado pela <strong>Stripe</strong>. O valor e a periodicidade são exibidos no momento da contratação. A renovação é automática até o cancelamento.</p>
                        <p>Novos assinantes podem receber um <strong>período de teste gratuito</strong>. O teste é válido <strong>uma única vez por conta (e-mail)</strong>. Ao término do período gratuito, a cobrança regular é iniciada automaticamente.</p>
                        <p>Quem já contratou o Bebelume anteriormente — ainda que tenha cancelado — <strong>não faz jus a novo período de teste</strong>: na reativação da assinatura, a primeira cobrança ocorre na própria data de contratação.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">3. Cancelamento</h6>
                        <p>Você pode cancelar sua assinatura a qualquer momento pela área de conta no site onde assinou, no menu <strong>Conta de associação</strong> (em <strong><?php echo esc_url( home_url( '/conta-de-associacao/' ) ); ?></strong>).</p>
                        <p>O cancelamento vale a partir do <strong>fim do período em andamento</strong>: durante o teste gratuito, o acesso permanece ativo até o fim do teste; após o início da cobrança, até o fim do período já pago. Nenhuma cobrança é feita após o cancelamento e não há reembolso proporcional de períodos não utilizados.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">4. Dados Fiscais e Nota Fiscal</h6>
                        <p>Para cada cobrança, o Bebelume emite a <strong>Nota Fiscal de Serviço Eletrônica (NFS-e)</strong>, o que exige os dados fiscais do assinante: <strong>CPF, telefone, endereço, município, UF e CEP</strong>. Ao contratar, você se compromete a fornecê-los e a mantê-los atualizados.</p>
                        <p>Se houver cobrança a realizar ou já realizada e os dados fiscais estiverem incompletos, o Bebelume poderá exibir uma tela para que você os complete. Até o preenchimento, o acesso ao conteúdo poderá ficar temporariamente indisponível, sem prejuízo da cobrança devida. Sua área de conta — incluindo cancelamento, faturas e perfil — permanece acessível normalmente.</p>
                        <p>Durante o período de teste gratuito, essa tela poderá ser exibida quando faltarem 7 dias ou menos para a data da primeira cobrança, caso os dados fiscais ainda estejam incompletos. Ao completá-los: nenhuma cobrança é realizada naquele momento, o teste gratuito segue valendo sem alteração da data de término, e o acesso é liberado imediatamente. Você também pode cancelar quando quiser pela área de conta.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">5. Direito de Arrependimento</h6>
                        <p>Conforme o Código de Defesa do Consumidor (Art. 49), você tem direito ao cancelamento em até <strong>7 dias corridos</strong> da contratação, com reembolso integral, desde que solicitado antes do uso do conteúdo.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">6. Uso do Conteúdo</h6>
                        <p>Todo o conteúdo disponível na plataforma é de uso exclusivamente pessoal e não comercial. É proibido reproduzir, distribuir, transmitir ou compartilhar o conteúdo sem autorização expressa do Bebelume.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">7. Conta de Usuário</h6>
                        <p>Você é responsável pela segurança de sua conta e senha. O compartilhamento de credenciais é proibido. Identificado o compartilhamento, a conta poderá ser suspensa sem aviso prévio.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">8. Alterações nos Termos</h6>
                        <p>O Bebelume pode atualizar estes Termos periodicamente. Alterações significativas serão comunicadas por e-mail com antecedência mínima de 10 dias.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">9. Foro</h6>
                        <p>Fica eleito o foro da Comarca de Brasília/DF para dirimir quaisquer controvérsias decorrentes destes Termos.</p>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #e4e8ed;padding:1rem 1.5rem;background:#fafafa;border-radius:0 0 14px 14px;">
                        <button type="button" class="btn" data-bs-dismiss="modal"
                            style="font-family:'Nunito',sans-serif;font-size:13px;font-weight:600;background:#eb2a61;color:#fff;border:none;border-radius:99px;padding:8px 20px;">
                            Entendi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Política de Privacidade -->
        <div class="modal fade" id="bbl-modal-privacidade" tabindex="-1" aria-labelledby="bbl-privacidade-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content" style="border-radius:14px;">
                    <div class="modal-header" style="border-bottom:1px solid #e4e8ed;padding:1.25rem 1.5rem;">
                        <h5 class="modal-title" id="bbl-privacidade-title" style="font-family:'Nunito',sans-serif;font-weight:700;font-size:16px;">Política de Privacidade</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body" style="padding:1.5rem;font-family:'Nunito',sans-serif;font-size:14px;line-height:1.7;color:#333;">
                        <p><strong>Última atualização:</strong> <?php echo esc_html( self::PRIVACIDADE_ATUALIZACAO ); ?></p>

                        <p>Esta Política descreve como o <strong>Bebelume</strong> coleta, usa e protege seus dados pessoais, em conformidade com a <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong>.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">1. Dados que Coletamos</h6>
                        <p><strong>Dados de cadastro:</strong> nome completo, e-mail e senha (armazenada de forma criptografada).</p>
                        <p><strong>Dados fiscais:</strong> CPF e endereço completo, coletados <em>exclusivamente</em> para emissão de documentos fiscais (NF-e/NFS-e), conforme obrigação legal.</p>
                        <p><strong>Dados de contato:</strong> telefone/WhatsApp, coletado para suporte e comunicações sobre sua assinatura.</p>
                        <p><strong>Dados de pagamento:</strong> processados integralmente pela <strong>Stripe</strong>. O Bebelume <strong>não armazena</strong> dados de cartão de crédito.</p>
                        <p><strong>Dados de navegação:</strong> endereço IP, tipo de navegador e páginas visitadas, coletados automaticamente para análise e melhoria da plataforma.</p>
                        <p><strong>Dados de atribuição de marketing:</strong> identificadores do Meta Pixel (fbclid, fbp) para mensuração de campanhas publicitárias.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">2. Finalidade do Tratamento</h6>
                        <ul style="padding-left:1.25rem;">
                            <li>Prestação do serviço de assinatura e acesso ao conteúdo</li>
                            <li>Emissão de notas fiscais eletrônicas (obrigação legal)</li>
                            <li>Comunicações sobre sua conta e assinatura</li>
                            <li>Suporte ao cliente</li>
                            <li>Mensuração e otimização de campanhas de marketing</li>
                            <li>Prevenção a fraudes</li>
                        </ul>

                        <h6 style="font-weight:700;margin-top:1.25rem;">3. Base Legal (LGPD)</h6>
                        <p>Tratamos seus dados com base em: <strong>consentimento</strong> (Art. 7º, I), <strong>execução de contrato</strong> (Art. 7º, V) e <strong>obrigação legal</strong> (Art. 7º, II — para fins fiscais).</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">4. Compartilhamento de Dados</h6>
                        <p>Seus dados podem ser compartilhados com:</p>
                        <ul style="padding-left:1.25rem;">
                            <li><strong>Stripe</strong> — processamento de pagamentos</li>
                            <li><strong>Meta (Facebook)</strong> — mensuração de campanhas via Conversions API</li>
                            <li><strong>TransmiteNota</strong> — emissão das Notas Fiscais de Serviço Eletrônicas (NFS-e)</li>
                            <li><strong>ViaCEP</strong> — consulta de CEP para preenchimento automático do endereço no momento da contratação</li>
                        </ul>
                        <p>Não vendemos, alugamos ou compartilhamos seus dados com terceiros para fins comerciais. Na consulta de CEP, é enviado à API do ViaCEP apenas o número do CEP informado, sem qualquer outro dado pessoal.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">5. Segurança</h6>
                        <p>Adotamos medidas técnicas e organizacionais adequadas para proteger seus dados: conexões criptografadas (HTTPS/TLS), senhas hasheadas e acesso restrito aos sistemas.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">6. Seus Direitos (LGPD)</h6>
                        <p>Você tem direito a: confirmar a existência do tratamento, acessar, corrigir, anonimizar, bloquear ou eliminar seus dados, revogar consentimento e obter portabilidade. Exerça seus direitos pelo e-mail: <strong>contato@bebelume.com.br</strong>.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">7. Retenção de Dados</h6>
                        <p>Mantemos seus dados pelo período necessário à prestação do serviço e por até <strong>5 anos</strong> após o encerramento da conta para cumprimento de obrigações legais e fiscais.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">8. Cookies e Pixel</h6>
                        <p>Utilizamos cookies essenciais para autenticação e o Meta Pixel para mensuração de campanhas. Você pode gerenciar cookies pelo seu navegador.</p>

                        <h6 style="font-weight:700;margin-top:1.25rem;">9. Contato e DPO</h6>
                        <p>Dúvidas sobre esta Política: <strong>contato@bebelume.com.br</strong><br>
                        Sede: Brasília/DF, Brasil.</p>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #e4e8ed;padding:1rem 1.5rem;background:#fafafa;border-radius:0 0 14px 14px;">
                        <button type="button" class="btn" data-bs-dismiss="modal"
                            style="font-family:'Nunito',sans-serif;font-size:13px;font-weight:600;background:#eb2a61;color:#fff;border:none;border-radius:99px;padding:8px 20px;">
                            Entendi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Impede que o clique nos links de termos marque o checkbox
        document.querySelectorAll('.bbl-terms-link').forEach(function(a) {
            a.addEventListener('click', function(e) { e.preventDefault(); });
        });
        </script>
        <?php
    }

    public function validate_terms_checkbox(): bool {
        if ( ! function_exists( 'pmpro_is_checkout' ) || ! pmpro_is_checkout() ) return true;
        if ( empty( $_POST['bbl_accept_terms'] ) ) {
            pmpro_setMessage( 'Você precisa aceitar os Termos de Uso e a Política de Privacidade para continuar.', 'pmpro_error' );
            return false;
        }
        return true;
    }

    public function enqueue_cep_script(): void {
        if ( ! function_exists( 'pmpro_is_checkout' ) || ! pmpro_is_checkout() ) return;
        wp_add_inline_script( 'jquery-core', "
        jQuery(document).ready(function($) {
            var cep = $('#bzipcode');
            if (!cep.length) return;
            cep.attr('placeholder','00000-000').on('input', function() {
                var v = $(this).val().replace(/\D/g,'').slice(0,8);
                $(this).val(v.length>5 ? v.slice(0,5)+'-'+v.slice(5) : v);
            }).on('blur', function() {
                var v = $(this).val().replace(/\D/g,'');
                if (v.length !== 8) return;
                $.getJSON('https://viacep.com.br/ws/'+v+'/json/', function(d) {
                    if (d.erro) return;
                    $('#baddress1').val(d.logradouro);
                    $('#bcity').val(d.localidade);
                    $('#bstate').val(d.uf);
                    $('#bcountry').val('BR').trigger('change');
                });
            });
        });
        " );
    }
}

add_action( 'plugins_loaded', function() {
    BBL_Checkout::get_instance();
} );
