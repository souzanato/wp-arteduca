<?php
/**
 * Bebelume_NFSe_Admin
 * Página de configuração e listagem/gestão de NFS-e no admin WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Bebelume_NFSe_Admin {

    public static function init(): void {
        add_action( 'admin_menu',           [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_init',           [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_ajax_bbl_nfse_cancelar',   [ __CLASS__, 'ajax_cancelar' ] );
        add_action( 'wp_ajax_bbl_nfse_reemitir',   [ __CLASS__, 'ajax_reemitir' ] );
        add_action( 'wp_ajax_bbl_nfse_check_now',  [ __CLASS__, 'ajax_check_now' ] );
        add_action( 'wp_ajax_bbl_nfse_teste_emitir',  [ __CLASS__, 'ajax_teste_emitir' ] );
        add_action( 'wp_ajax_bbl_nfse_teste_preview', [ __CLASS__, 'ajax_teste_preview' ] );
    }

    // -------------------------------------------------------------------------
    // Menu
    // -------------------------------------------------------------------------

    public static function register_menu(): void {
        add_menu_page(
            'NFS-e Bebelume',
            'NFS-e',
            'manage_options',
            'bbl-nfse',
            [ __CLASS__, 'page_notas' ],
            'dashicons-media-document',
            56
        );

        add_submenu_page(
            'bbl-nfse',
            'Notas Emitidas',
            'Notas Emitidas',
            'manage_options',
            'bbl-nfse',
            [ __CLASS__, 'page_notas' ]
        );

        add_submenu_page(
            'bbl-nfse',
            'Teste de Emissão',
            'Teste de Emissão',
            'manage_options',
            'bbl-nfse-teste',
            [ __CLASS__, 'page_teste' ]
        );

        add_submenu_page(
            'bbl-nfse',
            'Configurações',
            'Configurações',
            'manage_options',
            'bbl-nfse-settings',
            [ __CLASS__, 'page_settings' ]
        );
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    public static function register_settings(): void {
        register_setting( 'bbl_nfse_group', 'bbl_nfse_ambiente',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'bbl_nfse_group', 'bbl_nfse_api_key',   [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'bbl_nfse_group', 'bbl_nfse_cnpj',      [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'bbl_nfse_group', 'bbl_nfse_debug',     [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'bbl_nfse_group', 'bbl_nfse_https',     [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'bbl_nfse_group', 'bbl_nfse_settings',  [ 'sanitize_callback' => [ __CLASS__, 'sanitize_fiscal_settings' ] ] );
    }

    public static function sanitize_fiscal_settings( $input ): array {
        $clean = [];
        $fields = [
            'natureza_operacao', 'tipo_servico', 'codigo_servico',
            'descricao_servico', 'valor_aliquota', 'iss_retido',
            'api_key_homologacao', 'api_key_producao',
        ];
        foreach ( $fields as $f ) {
            $clean[ $f ] = isset( $input[ $f ] ) ? sanitize_text_field( $input[ $f ] ) : '';
        }

        // Mantém bbl_nfse_api_key sincronizada apenas por compatibilidade com
        // instalações antigas. A leitura oficial agora é feita em
        // Bebelume_NFSe_API_Client::resolve_api_key(), que lê a key do ambiente
        // direto daqui — então a ordem de execução dos sanitize callbacks não
        // afeta mais qual key é usada na emissão.
        $ambiente = get_option( 'bbl_nfse_ambiente', 'homologacao' );
        $key = $ambiente === 'producao'
            ? ( $clean['api_key_producao'] ?: get_option( 'bbl_nfse_api_key', '' ) )
            : ( $clean['api_key_homologacao'] ?: get_option( 'bbl_nfse_api_key', '' ) );
        update_option( 'bbl_nfse_api_key', $key );

        return $clean;
    }

    // -------------------------------------------------------------------------
    // Assets
    // -------------------------------------------------------------------------

    public static function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'bbl-nfse' ) === false ) return;
        wp_enqueue_style( 'bbl-nfse-admin', BBL_NFSE_URL . 'assets/css/admin.css', [], BBL_NFSE_VERSION );
    }

    // -------------------------------------------------------------------------
    // Página: Notas Emitidas
    // -------------------------------------------------------------------------

    public static function page_notas(): void {
        $status_filter = sanitize_text_field( $_GET['status_filter'] ?? '' );
        $page_num      = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $per_page      = 25;
        $offset        = ( $page_num - 1 ) * $per_page;

        $args  = [ 'limit' => $per_page, 'offset' => $offset ];
        if ( $status_filter ) $args['status'] = $status_filter;

        $notas = Bebelume_NFSe_DB::list( $args );
        $total = Bebelume_NFSe_DB::count( $status_filter ? [ 'status' => $status_filter ] : [] );
        $pages = ceil( $total / $per_page );

        $status_labels = [
            'pendente'    => [ 'label' => 'Pendente',    'color' => '#f59e0b' ],
            'processando' => [ 'label' => 'Processando', 'color' => '#3b82f6' ],
            'aprovada'    => [ 'label' => 'Aprovada',    'color' => '#22c55e' ],
            'reprovada'   => [ 'label' => 'Reprovada',   'color' => '#ef4444' ],
            'cancelada'   => [ 'label' => 'Cancelada',   'color' => '#6b7280' ],
            'erro'        => [ 'label' => 'Erro',        'color' => '#dc2626' ],
        ];

        $nonce = wp_create_nonce( 'bbl_nfse_action' );

        include BBL_NFSE_DIR . 'templates/admin-notas.php';
    }

    // -------------------------------------------------------------------------
    // Página: Configurações
    // -------------------------------------------------------------------------

    public static function page_settings(): void {
        $ambiente = get_option( 'bbl_nfse_ambiente', 'homologacao' );
        $api_key  = get_option( 'bbl_nfse_api_key', '' );
        $cnpj     = get_option( 'bbl_nfse_cnpj', '' );
        $debug    = get_option( 'bbl_nfse_debug', '0' );
        $https    = get_option( 'bbl_nfse_https', '0' );
        $settings = get_option( 'bbl_nfse_settings', [] );

        include BBL_NFSE_DIR . 'templates/admin-settings.php';
    }

    // -------------------------------------------------------------------------
    // Página: Teste de Emissão
    // -------------------------------------------------------------------------

    public static function page_teste(): void {
        $ambiente       = get_option( 'bbl_nfse_ambiente', 'homologacao' );
        $is_homologacao = $ambiente === 'homologacao';
        $settings       = get_option( 'bbl_nfse_settings', [] );
        $desc_padrao    = $settings['descricao_servico'] ?? 'Assinatura de serviço digital';
        $nonce          = wp_create_nonce( 'bbl_nfse_action' );

        include BBL_NFSE_DIR . 'templates/admin-teste.php';
    }

    /**
     * Monta o array do tomador a partir do POST da tela de teste.
     * Origem 'usuario' lê o perfil; 'manual' usa os campos digitados.
     */
    private static function tomador_from_post(): array|WP_Error {
        $origem = sanitize_text_field( $_POST['origem'] ?? 'usuario' );

        if ( $origem === 'usuario' ) {
            $user_id = (int) ( $_POST['user_id'] ?? 0 );
            $user    = get_userdata( $user_id );
            if ( ! $user ) {
                return new WP_Error( 'bbl_nfse_user', 'Usuário não encontrado.' );
            }
            return Bebelume_NFSe_PMPro::tomador_from_user( $user );
        }

        return [
            'nome'      => sanitize_text_field( $_POST['nome']      ?? '' ),
            'email'     => sanitize_email(      $_POST['email']     ?? '' ),
            'cpf_cnpj'  => preg_replace( '/\D/', '', (string) ( $_POST['cpf_cnpj'] ?? '' ) ),
            'telefone'  => sanitize_text_field( $_POST['telefone']  ?? '' ),
            'endereco'  => sanitize_text_field( $_POST['endereco']  ?? '' ),
            'bairro'    => sanitize_text_field( $_POST['bairro']    ?? '' ),
            'municipio' => sanitize_text_field( $_POST['municipio'] ?? '' ),
            'uf'        => sanitize_text_field( $_POST['uf']        ?? '' ),
            'cep'       => preg_replace( '/\D/', '', (string) ( $_POST['cep'] ?? '' ) ),
        ];
    }

    // -------------------------------------------------------------------------
    // AJAX: Pré-visualizar dados do usuário
    // -------------------------------------------------------------------------

    public static function ajax_teste_preview(): void {
        check_ajax_referer( 'bbl_nfse_action', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sem permissão.' );

        $user = get_userdata( (int) ( $_POST['user_id'] ?? 0 ) );
        if ( ! $user ) wp_send_json_error( 'Usuário não encontrado.' );

        $t        = Bebelume_NFSe_PMPro::tomador_from_user( $user );
        $faltando = Bebelume_NFSe_PMPro::validar_tomador( $t );

        $rotulos = [
            'nome' => 'Nome', 'email' => 'E-mail', 'cpf_cnpj' => 'CPF/CNPJ',
            'telefone' => 'Telefone', 'endereco' => 'Endereço', 'bairro' => 'Bairro',
            'municipio' => 'Município', 'uf' => 'UF', 'cep' => 'CEP',
        ];

        $html = '<table class="bbl-preview-table">';
        foreach ( $rotulos as $campo => $rotulo ) {
            $valor = $t[ $campo ] ?? '';
            $vazio = $valor === '' || $valor === null;
            $html .= sprintf(
                '<tr><th>%s</th><td>%s</td></tr>',
                esc_html( $rotulo ),
                $vazio ? '<em class="bbl-vazio">— vazio —</em>' : esc_html( $valor )
            );
        }
        $html .= '</table>';

        if ( $faltando ) {
            $html .= '<p class="bbl-preview-faltando"><strong>⚠️ Faltando:</strong> '
                   . esc_html( implode( ', ', $faltando ) )
                   . '<br>A emissão automática também falharia para este usuário.</p>';
        } else {
            $html .= '<p class="bbl-preview-ok">✅ Todos os campos obrigatórios preenchidos.</p>';
        }

        wp_send_json_success( [ 'html' => $html, 'faltando' => $faltando ] );
    }

    // -------------------------------------------------------------------------
    // AJAX: Emitir nota de teste
    // -------------------------------------------------------------------------

    public static function ajax_teste_emitir(): void {
        check_ajax_referer( 'bbl_nfse_action', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'mensagem' => 'Sem permissão.' ] );

        $valor = (float) str_replace( ',', '.', (string) ( $_POST['valor'] ?? 0 ) );
        if ( $valor <= 0 ) {
            wp_send_json_error( [ 'mensagem' => 'Informe um valor maior que zero.' ] );
        }

        $tomador = self::tomador_from_post();
        if ( is_wp_error( $tomador ) ) {
            wp_send_json_error( [ 'mensagem' => $tomador->get_error_message() ] );
        }

        $descricao  = sanitize_text_field( $_POST['descricao'] ?? '' );
        $do_perfil  = sanitize_text_field( $_POST['origem'] ?? 'usuario' ) === 'usuario';
        $user_id    = $do_perfil ? (int) ( $_POST['user_id'] ?? 0 ) : 0;

        $resultado = Bebelume_NFSe_PMPro::emitir_avulsa( $tomador, $valor, $descricao, $user_id, $do_perfil );

        if ( is_wp_error( $resultado ) ) {
            wp_send_json_error( [ 'mensagem' => $resultado->get_error_message() ] );
        }

        $resposta = $resultado['resposta'];
        $ok       = strtolower( $resposta['status'] ?? '' ) === 'ok';

        $carga = [
            'id'       => $resultado['id'],
            'payload'  => wp_json_encode( $resultado['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
            'resposta' => wp_json_encode( $resposta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
        ];

        if ( $ok ) {
            $carga['mensagem'] = sprintf(
                'Nota #%d enviada. Aguardando processamento da prefeitura.',
                $resultado['id']
            );
            wp_send_json_success( $carga );
        }

        $carga['mensagem'] = 'A API recusou a emissão: '
            . ( $resposta['descricao'] ?? $resposta['mensagem'] ?? 'veja a resposta abaixo.' );
        wp_send_json_error( $carga );
    }

    // -------------------------------------------------------------------------
    // AJAX: Cancelar nota
    // -------------------------------------------------------------------------

    public static function ajax_cancelar(): void {
        check_ajax_referer( 'bbl_nfse_action', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sem permissão.' );

        $id     = (int) ( $_POST['id'] ?? 0 );
        $motivo = (int) ( $_POST['motivo'] ?? 1 );

        $nota = Bebelume_NFSe_DB::get( $id );
        if ( ! $nota || empty( $nota->searchkey ) ) {
            wp_send_json_error( 'Nota não encontrada ou sem searchkey.' );
        }

        $api      = new Bebelume_NFSe_API_Client();
        $resposta = $api->cancelar_nfse( $nota->searchkey, $motivo );

        if ( is_wp_error( $resposta ) ) {
            wp_send_json_error( $resposta->get_error_message() );
        }

        if ( strtolower( $resposta['status'] ?? '' ) === 'ok' ) {
            Bebelume_NFSe_DB::update( $id, [
                'status'   => 'cancelada',
                'resposta' => wp_json_encode( $resposta ),
            ] );
            wp_send_json_success( 'Cancelamento solicitado com sucesso.' );
        }

        wp_send_json_error( $resposta['descricao'] ?? 'Erro ao cancelar.' );
    }

    // -------------------------------------------------------------------------
    // AJAX: Reemitir nota com erro
    // -------------------------------------------------------------------------

    public static function ajax_reemitir(): void {
        check_ajax_referer( 'bbl_nfse_action', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sem permissão.' );

        $id   = (int) ( $_POST['id'] ?? 0 );
        $nota = Bebelume_NFSe_DB::get( $id );

        if ( ! $nota || empty( $nota->payload ) ) {
            wp_send_json_error( 'Nota não encontrada ou sem payload para reemissão.' );
        }

        $payload = json_decode( $nota->payload, true );

        if ( ! is_array( $payload ) ) {
            wp_send_json_error( 'Payload salvo é inválido — não é possível reemitir automaticamente.' );
        }

        // Atualiza as datas para hoje. O payload salvo carrega a data da
        // tentativa original; reenviar uma nota de dias atrás faz a prefeitura
        // recusar o RPS por data retroativa.
        if ( isset( $payload['data_emissao'] ) ) {
            $payload['data_emissao'] = current_time( 'd/m/Y' );
        }
        if ( isset( $payload['data_competencia'] ) ) {
            $payload['data_competencia'] = current_time( 'd/m/Y' );
        }

        $api      = new Bebelume_NFSe_API_Client();
        $resposta = $api->enviar_nfse( $payload );

        if ( is_wp_error( $resposta ) ) {
            wp_send_json_error( $resposta->get_error_message() );
        }

        if ( strtolower( $resposta['status'] ?? '' ) === 'ok' ) {
            Bebelume_NFSe_DB::update( $id, [
                'searchkey' => $resposta['searchkey'] ?? '',
                'status'    => 'pendente',
                'payload'   => wp_json_encode( $payload ),
                'resposta'  => wp_json_encode( $resposta ),
            ] );
            wp_send_json_success( 'Nota reenviada. Aguardando processamento.' );
        }

        wp_send_json_error( $resposta['descricao'] ?? 'Erro ao reemitir.' );
    }

    // -------------------------------------------------------------------------
    // AJAX: Checar status agora (força consulta manual)
    // -------------------------------------------------------------------------

    public static function ajax_check_now(): void {
        check_ajax_referer( 'bbl_nfse_action', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sem permissão.' );

        $id   = (int) ( $_POST['id'] ?? 0 );
        $nota = Bebelume_NFSe_DB::get( $id );

        if ( ! $nota || empty( $nota->searchkey ) ) {
            wp_send_json_error( 'Nota não encontrada ou sem searchkey.' );
        }

        $api      = new Bebelume_NFSe_API_Client();
        $resposta = $api->consultar_emissao( $nota->searchkey );

        if ( is_wp_error( $resposta ) ) wp_send_json_error( $resposta->get_error_message() );

        $resultado = $resposta['resultado'] ?? [];
        $map = [
            'aguardando processamento' => 'pendente',
            'em andamento'             => 'processando',
            'aprovada'                 => 'aprovada',
            'aprovada com correcao'    => 'aprovada',
            'cancelada'                => 'cancelada',
            'reprovada'                => 'reprovada',
        ];
        $novo = strtolower( $resultado['status'] ?? '' );
        $status = $map[ $novo ] ?? $nota->status;

        Bebelume_NFSe_DB::update( $id, [
            'status'      => $status,
            'numero_nfse' => $resultado['numero']   ?? $nota->numero_nfse,
            'link_pdf'    => $resultado['link_pdf'] ?? $nota->link_pdf,
            'link_xml'    => $resultado['link_xml'] ?? $nota->link_xml,
            'resposta'    => wp_json_encode( $resposta ),
        ] );

        wp_send_json_success( [
            'status'      => $status,
            'numero_nfse' => $resultado['numero']   ?? '',
            'link_pdf'    => $resultado['link_pdf'] ?? '',
        ] );
    }
}
