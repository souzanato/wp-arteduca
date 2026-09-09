<?php
/**
 * BBL_N8N_Webhook — Disparo de webhook n8n no evento StartTrial
 *
 * Adiciona um campo "Webhook n8n" em cada nível do PMPro.
 * Ao confirmar a PRIMEIRA compra do usuário naquele nível,
 * envia um payload completo para cada URL cadastrada.
 *
 * Inclui página de teste no admin (Memberships → Teste Webhook n8n).
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BBL_N8N_Webhook {

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        if ( ! function_exists( 'pmpro_hasMembershipLevel' ) ) return;
        $this->hooks();
    }

    private function hooks(): void {
        // Campo extra na tela de edição de nível
        add_action( 'pmpro_membership_level_after_other_settings', [ $this, 'render_level_field' ] );
        add_action( 'pmpro_save_membership_level',                 [ $this, 'save_level_field' ] );

        // Disparo no checkout
        add_action( 'pmpro_after_checkout', [ $this, 'maybe_fire' ], 50, 2 );

        // Página de teste no admin
        add_action( 'admin_menu',            [ $this, 'register_test_page' ] );
        add_action( 'admin_post_bbl_n8n_test', [ $this, 'handle_test_dispatch' ] );
    }

    // ── Campo no nível ────────────────────────────────────────────────────────

    public function render_level_field(): void {
        $level_id = isset( $_REQUEST['edit'] ) ? (int) $_REQUEST['edit'] : 0;
        $raw      = $level_id ? get_option( "bbl_n8n_webhook_{$level_id}", '' ) : '';
        ?>
        <h3 class="topborder"><?php esc_html_e( 'Webhook n8n — StartTrial', 'bebelume-pmpro-profile' ); ?></h3>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="bbl_n8n_webhook_urls">
                            <?php esc_html_e( 'URLs do Webhook', 'bebelume-pmpro-profile' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea
                            id="bbl_n8n_webhook_urls"
                            name="bbl_n8n_webhook_urls"
                            rows="4"
                            class="large-text"
                            placeholder="https://seu-n8n.com/webhook/abc&#10;https://outro-endpoint.com/webhook/xyz"
                        ><?php echo esc_textarea( $raw ); ?></textarea>
                        <p class="description">
                            <?php esc_html_e( 'Uma URL por linha. O payload completo da compra será enviado para cada URL quando o usuário realizar o StartTrial (primeira compra) neste nível.', 'bebelume-pmpro-profile' ); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function save_level_field( int $level_id ): void {
        $raw = isset( $_POST['bbl_n8n_webhook_urls'] ) ? sanitize_textarea_field( $_POST['bbl_n8n_webhook_urls'] ) : '';
        update_option( "bbl_n8n_webhook_{$level_id}", $raw, false );
    }

    // ── Página de teste no admin ──────────────────────────────────────────────

    public function register_test_page(): void {
        add_submenu_page(
            'pmpro-dashboard',
            'Teste Webhook n8n',
            'Teste Webhook n8n',
            'manage_options',
            'bbl-n8n-test',
            [ $this, 'render_test_page' ]
        );
    }

    public function render_test_page(): void {
        global $wpdb;

        // Busca as 50 ordens mais recentes com status success
        $orders = $wpdb->get_results(
            "SELECT o.id, o.code, o.user_id, o.membership_id, o.total, o.timestamp,
                    u.user_email, ml.name as level_name
             FROM {$wpdb->prefix}pmpro_membership_orders o
             LEFT JOIN {$wpdb->users} u  ON u.ID = o.user_id
             LEFT JOIN {$wpdb->prefix}pmpro_membership_levels ml ON ml.id = o.membership_id
             WHERE o.status = 'success'
             ORDER BY o.timestamp DESC
             LIMIT 50"
        );

        $result_msg  = '';
        $result_type = '';
        if ( ! empty( $_GET['bbl_sent'] ) ) {
            $code        = ! empty( $_GET['bbl_code'] ) ? ' (HTTP ' . (int) $_GET['bbl_code'] . ')' : '';
            $result_msg  = 'Payload enviado com sucesso!' . $code;
            $result_type = 'success';
        }
        if ( ! empty( $_GET['bbl_error'] ) ) {
            $result_msg  = 'Erro ao enviar: ' . esc_html( urldecode( $_GET['bbl_error'] ) );
            $result_type = 'error';
        }
        ?>
        <div class="wrap">
            <h1>🧪 Teste Webhook n8n — StartTrial</h1>
            <p>Selecione uma ordem existente e clique em <strong>Enviar teste</strong>. O payload enviado é <strong>idêntico</strong> ao da compra real — gerado pelo mesmo código, com os dados reais do banco.</p>

            <?php if ( $result_msg ) : ?>
                <div class="notice notice-<?php echo $result_type; ?> is-dismissible"><p><?php echo $result_msg; ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'bbl_n8n_test', 'bbl_nonce' ); ?>
                <input type="hidden" name="action" value="bbl_n8n_test">

                <table class="form-table">
                    <tr>
                        <th><label for="bbl_test_order_id">Ordem</label></th>
                        <td>
                            <select name="bbl_test_order_id" id="bbl_test_order_id" style="min-width:500px;">
                                <?php foreach ( $orders as $o ) : ?>
                                    <option value="<?php echo (int) $o->id; ?>">
                                        #<?php echo (int) $o->id; ?> — <?php echo esc_html( $o->code ); ?> | <?php echo esc_html( $o->user_email ); ?> | <?php echo esc_html( $o->level_name ); ?> | R$ <?php echo number_format( (float) $o->total, 2, ',', '.' ); ?> | <?php echo esc_html( date( 'd/m/Y H:i', strtotime( $o->timestamp ) ) ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">50 ordens mais recentes com status <code>success</code>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bbl_test_url">URL de destino</label></th>
                        <td>
                            <input
                                type="url"
                                name="bbl_test_url"
                                id="bbl_test_url"
                                value="https://automacao.bematendido.com.br/webhook-test/bebelume"
                                class="large-text"
                                placeholder="https://seu-n8n.com/webhook-test/..."
                            >
                            <p class="description">URL do webhook n8n para o teste. Pode ser diferente das URLs cadastradas nos níveis.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( 'Enviar payload de teste', 'primary', 'submit', true ); ?>
            </form>
        </div>
        <?php
    }

    public function handle_test_dispatch(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sem permissão.' );
        check_admin_referer( 'bbl_n8n_test', 'bbl_nonce' );

        $order_id = isset( $_POST['bbl_test_order_id'] ) ? (int) $_POST['bbl_test_order_id'] : 0;
        $url      = isset( $_POST['bbl_test_url'] )      ? esc_url_raw( trim( $_POST['bbl_test_url'] ) ) : '';

        if ( ! $order_id || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            wp_redirect( add_query_arg( [ 'page' => 'bbl-n8n-test', 'bbl_error' => urlencode( 'Ordem ou URL inválida.' ) ], admin_url( 'admin.php' ) ) );
            exit;
        }

        $order = new MemberOrder( $order_id );
        if ( empty( $order ) || empty( $order->user_id ) ) {
            wp_redirect( add_query_arg( [ 'page' => 'bbl-n8n-test', 'bbl_error' => urlencode( 'Ordem não encontrada.' ) ], admin_url( 'admin.php' ) ) );
            exit;
        }

        // Monta payload idêntico ao da compra real + flag de teste
        $payload          = $this->build_payload( (int) $order->user_id, $order );
        $payload['teste'] = true; // única diferença: avisa o n8n que é teste

        // Disparo bloqueante no teste para capturar erros
        $response = wp_remote_post( $url, [
            'method'      => 'POST',
            'timeout'     => 15,
            'redirection' => 3,
            'blocking'    => true,
            'headers'     => [
                'Content-Type' => 'application/json',
                'X-BBL-Event'  => 'start_trial',
                'X-BBL-Source' => get_site_url(),
                'X-BBL-Test'   => '1',
            ],
            'body'        => wp_json_encode( $payload ),
        ] );

        if ( is_wp_error( $response ) ) {
            $msg = $response->get_error_message();
            wp_redirect( add_query_arg( [ 'page' => 'bbl-n8n-test', 'bbl_error' => urlencode( 'WP_Error: ' . $msg ) ], admin_url( 'admin.php' ) ) );
            exit;
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        $body      = wp_remote_retrieve_body( $response );

        if ( $http_code >= 200 && $http_code < 300 ) {
            wp_redirect( add_query_arg( [ 'page' => 'bbl-n8n-test', 'bbl_sent' => '1', 'bbl_code' => $http_code ], admin_url( 'admin.php' ) ) );
        } else {
            $detail = "HTTP {$http_code}: " . substr( $body, 0, 200 );
            wp_redirect( add_query_arg( [ 'page' => 'bbl-n8n-test', 'bbl_error' => urlencode( $detail ) ], admin_url( 'admin.php' ) ) );
        }
        exit;
    }

    // ── Disparo real (checkout) ───────────────────────────────────────────────

    public function maybe_fire( int $user_id, $order ): void {
        if ( empty( $order ) || empty( $order->membership_id ) ) return;

        $level_id = (int) $order->membership_id;

        if ( ! $this->is_start_trial( $user_id, $level_id, $order ) ) return;

        $raw  = get_option( "bbl_n8n_webhook_{$level_id}", '' );
        $urls = $this->parse_urls( $raw );
        if ( empty( $urls ) ) return;

        $payload = $this->build_payload( $user_id, $order );

        foreach ( $urls as $url ) {
            $this->send( $url, $payload );
        }
    }

    /**
     * Verifica se é StartTrial (primeira compra do usuário naquele nível).
     */
    private function is_start_trial( int $user_id, int $level_id, $current_order ): bool {
        global $wpdb;

        $count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_membership_orders
             WHERE user_id       = %d
               AND membership_id = %d
               AND status        IN ('success','pending')
               AND id            != %d",
            $user_id,
            $level_id,
            (int) $current_order->id
        ) );

        return $count === 0;
    }

    /**
     * Monta o payload completo com todos os dados disponíveis da compra.
     */
    private function build_payload( int $user_id, $order ): array {

        $user     = get_userdata( $user_id );
        $level    = pmpro_getLevel( $order->membership_id );
        $metadata = get_user_meta( $user_id );

        // ── Dados do comprador ─────────────────────────────────────────────
        $first_name = $metadata['first_name'][0]         ?? $metadata['pmpro_bfirstname'][0] ?? '';
        $last_name  = $metadata['last_name'][0]          ?? $metadata['pmpro_blastname'][0]  ?? '';
        $cpf        = $metadata['cpf_formatted'][0]      ?? $metadata['cpf'][0]              ?? '';
        $phone      = $metadata['pmpro_bphone'][0]       ?? '';

        // ── Endereço ───────────────────────────────────────────────────────
        $address = [
            'logradouro' => $metadata['pmpro_baddress1'][0] ?? '',
            'bairro'     => $metadata['pmpro_baddress2'][0] ?? '',
            'cidade'     => $metadata['pmpro_bcity'][0]     ?? '',
            'estado'     => $metadata['pmpro_bstate'][0]    ?? '',
            'cep'        => $metadata['pmpro_bzipcode'][0]  ?? '',
            'pais'       => $metadata['pmpro_bcountry'][0]  ?? 'BR',
        ];

        // ── Dados do nível / plano ─────────────────────────────────────────
        $plan = [];
        if ( $level ) {
            $plan = [
                'id'               => (int) $level->id,
                'nome'             => $level->name,
                'descricao'        => $level->description,
                'preco_inicial'    => (float) $level->initial_payment,
                'preco_recorrente' => (float) $level->billing_amount,
                'periodo_ciclo'    => (int) $level->cycle_number,
                'unidade_ciclo'    => $level->cycle_period,
                'periodo_trial'    => (int) $level->trial_limit,
                'preco_trial'      => (float) $level->trial_amount,
                'expiracao_numero' => (int) $level->expiration_number,
                'expiracao_periodo'=> $level->expiration_period,
            ];
        }

        // ── Dados da ordem / transação ────────────────────────────────────
        $transaction = [
            'order_id'               => (int) $order->id,
            'order_code'             => $order->code                       ?? '',
            'status'                 => $order->status                     ?? '',
            'gateway'                => $order->gateway                    ?? '',
            'gateway_env'            => $order->gateway_environment        ?? '',
            'subtotal'               => (float) ( $order->subtotal         ?? 0 ),
            'tax'                    => (float) ( $order->tax              ?? 0 ),
            'total'                  => (float) ( $order->total            ?? 0 ),
            'moeda'                  => 'BRL',
            'pagamento_tipo'         => $order->payment_type               ?? '',
            'cartao_tipo'            => $order->cardtype                   ?? '',
            'cartao_final'           => $order->accountnumber              ?? '',
            'cartao_expiracao'       => trim( $order->ExpirationMonthYear  ?? '' ),
            'stripe_payment_intent'  => $order->payment_transaction_id     ?? '',
            'stripe_subscription_id' => $order->subscription_transaction_id ?? '',
            'billing_nome'           => trim( ( $order->FirstName ?? '' ) . ' ' . ( $order->LastName ?? '' ) ),
            'billing_email'          => $order->Email                      ?? '',
            'billing_endereco'       => $order->Address1                   ?? '',
            'billing_cidade'         => $order->City                       ?? '',
            'billing_estado'         => $order->State                      ?? '',
            'billing_pais'           => $order->CountryCode                ?? '',
            'billing_cep'            => $order->Zip                        ?? '',
            'discount_code'          => $order->discount_code              ?? '',
            'data_compra'            => $order->timestamp                  ?? '',
        ];

        // ── Metadados extras da ordem (respostas do Stripe etc.) ──────────
        $stripe_meta = [];
        if ( ! empty( $order->id ) ) {
            global $wpdb;
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$wpdb->prefix}pmpro_membership_ordermeta WHERE pmpro_membership_order_id = %d",
                $order->id
            ), ARRAY_A );
            foreach ( $rows as $row ) {
                $stripe_meta[ $row['meta_key'] ] = $row['meta_value'];
            }
        }

        // ── Dados da assinatura ativa ─────────────────────────────────────
        $membership_row = [];
        if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
            $mem = pmpro_getMembershipLevelForUser( $user_id );
            if ( $mem ) {
                $membership_row = [
                    'startdate'    => $mem->startdate    ?? '',
                    'enddate'      => $mem->enddate      ?? '',
                    'cycle_number' => $mem->cycle_number ?? '',
                    'cycle_period' => $mem->cycle_period ?? '',
                    'trial_amount' => $mem->trial_amount ?? '',
                    'trial_limit'  => $mem->trial_limit  ?? '',
                    'status'       => $mem->status       ?? '',
                ];
            }
        }

        return [
            'evento'     => 'start_trial',
            'timestamp'  => gmdate( 'c' ),
            'site_url'   => get_site_url(),
            'comprador'  => [
                'user_id'       => $user_id,
                'username'      => $user->user_login    ?? '',
                'email'         => $user->user_email    ?? '',
                'nome'          => trim( "$first_name $last_name" ),
                'primeiro_nome' => $first_name,
                'sobrenome'     => $last_name,
                'cpf'           => $cpf,
                'telefone'      => $phone,
                'data_cadastro' => $user->user_registered ?? '',
                'endereco'      => $address,
            ],
            'plano'      => $plan,
            'transacao'  => $transaction,
            'stripe_meta'=> $stripe_meta,
            'assinatura' => $membership_row,
        ];
    }

    /**
     * Envia o payload para uma URL via POST JSON (não-bloqueante).
     */
    private function send( string $url, array $payload ): void {
        wp_remote_post( $url, [
            'method'      => 'POST',
            'timeout'     => 10,
            'redirection' => 3,
            'blocking'    => false,
            'headers'     => [
                'Content-Type' => 'application/json',
                'X-BBL-Event'  => 'start_trial',
                'X-BBL-Source' => get_site_url(),
            ],
            'body'        => wp_json_encode( $payload ),
        ] );
    }

    /**
     * Quebra o campo textarea em array de URLs válidas.
     */
    private function parse_urls( string $raw ): array {
        $lines = explode( "\n", str_replace( "\r", '', $raw ) );
        $urls  = [];
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( ! empty( $line ) && filter_var( $line, FILTER_VALIDATE_URL ) ) {
                $urls[] = $line;
            }
        }
        return $urls;
    }
}

add_action( 'plugins_loaded', function () {
    BBL_N8N_Webhook::get_instance();
} );
