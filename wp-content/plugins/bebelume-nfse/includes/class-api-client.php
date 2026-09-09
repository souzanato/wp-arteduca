<?php
/**
 * Bebelume_NFSe_API_Client
 * Wrapper para todos os endpoints NFS-e da TransmiteNota.
 * Suporta ambiente de homologação e produção.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Bebelume_NFSe_API_Client {

    const HOST            = 'api1.transmitenota.com.br';
    const PATH_PRODUCAO   = '/api/producao/';
    const PATH_HOMOLOGACAO = '/api/homologacao/';

    private string $api_key;
    private string $cnpj;
    private string $base_url;
    private string $ambiente;

    public function __construct() {
        $this->cnpj     = get_option( 'bbl_nfse_cnpj', '' );
        $this->ambiente = get_option( 'bbl_nfse_ambiente', 'homologacao' );
        $this->api_key  = self::resolve_api_key( $this->ambiente );

        // HTTPS é opcional e desligado por padrão para não quebrar instalações
        // que já emitem. Ligue em NFS-e → Configurações depois de testar em
        // homologação. Ver: get_option( 'bbl_nfse_https' ).
        $scheme = get_option( 'bbl_nfse_https', '0' ) === '1' ? 'https' : 'http';
        $path   = $this->ambiente === 'producao' ? self::PATH_PRODUCAO : self::PATH_HOMOLOGACAO;

        $this->base_url = $scheme . '://' . self::HOST . $path;
    }

    /**
     * Resolve a ApiKey do ambiente ativo.
     * Prefere a key específica do ambiente (bbl_nfse_settings) e cai para a
     * opção espelho legada (bbl_nfse_api_key) se ainda não tiver sido preenchida.
     */
    public static function resolve_api_key( string $ambiente ): string {
        $settings = get_option( 'bbl_nfse_settings', [] );
        $chave    = $ambiente === 'producao' ? 'api_key_producao' : 'api_key_homologacao';

        if ( ! empty( $settings[ $chave ] ) ) {
            return (string) $settings[ $chave ];
        }

        return (string) get_option( 'bbl_nfse_api_key', '' );
    }

    public function get_base_url(): string {
        return $this->base_url;
    }

    public function get_ambiente(): string {
        return $this->ambiente;
    }

    public function is_homologacao(): bool {
        return $this->ambiente === 'homologacao';
    }

    // -------------------------------------------------------------------------
    // NFS-e — Emissão
    // -------------------------------------------------------------------------

    public function enviar_nfse( array $dados ) {
        return $this->post( 'EnviarNfse/', $dados );
    }

    public function consultar_emissao( string $searchkey ) {
        return $this->post( 'ConsultarEmissaoNotaNfse/', [ 'searchkey' => $searchkey ] );
    }

    public function cancelar_nfse( string $searchkey, int $motivo = 1 ) {
        return $this->post( 'CancelarNfse/', [
            'searchkey'           => $searchkey,
            'motivo_cancelamento' => (string) $motivo,
        ] );
    }

    public function consultar_cancelamento( string $searchkey ) {
        return $this->post( 'ConsultarCancelamentoNfse/', [ 'searchkey' => $searchkey ] );
    }

    public function enviar_email( string $searchkey, string $para ) {
        $de      = get_option( 'admin_email' );
        $assunto = get_bloginfo( 'name' ) . ' — Sua Nota Fiscal de Serviço';
        $mensagem = 'Prezado(a), segue em anexo o PDF e XML da sua Nota Fiscal de Serviço referente à sua assinatura. Obrigado!';

        return $this->post( 'EnviarEmailNfse/', [
            'de'        => $de,
            'para'      => $para,
            'searchkey' => $searchkey,
            'assunto'   => $assunto,
            'mensagem'  => $mensagem,
        ] );
    }

    public function consultar_pdf( string $searchkey ) {
        return $this->post( 'ConsultarPDFNfse/', [ 'searchkey' => $searchkey ] );
    }

    public function consultar_xml( string $searchkey ) {
        return $this->post( 'ConsultarXMLNfse/', [ 'searchkey' => $searchkey ] );
    }

    // -------------------------------------------------------------------------
    // Internos
    // -------------------------------------------------------------------------

    private function post( string $endpoint, array $dados ) {
        if ( empty( $this->api_key ) || empty( $this->cnpj ) ) {
            return new WP_Error( 'bbl_nfse_config', __( 'ApiKey ou CNPJ não configurados.', 'bebelume-nfse' ) );
        }

        $payload = [
            'ApiKey' => $this->api_key,
            'Cnpj'   => $this->cnpj,
            'Dados'  => $dados,
        ];

        $url = $this->base_url . $endpoint;

        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Accept'       => 'application/json',
            ],
            'body'    => wp_json_encode( $payload ),
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        // Log completo para debug — feito ANTES do parse para que respostas
        // inválidas (HTML de erro, etc.) também apareçam no debug.log.
        if ( get_option( 'bbl_nfse_debug', '0' ) === '1' ) {
            error_log( '[Bebelume NFS-e][' . strtoupper( $this->ambiente ) . '] ' . $endpoint . ' | HTTP ' . $code . ' | ' . $body );
        }

        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'bbl_nfse_json', __( 'Resposta inválida da API.', 'bebelume-nfse' ), $body );
        }

        return $data;
    }
}
