<?php
/**
 * Bebelume_NFSe_Email
 * Envia o e-mail da NFS-e aprovada pelo WordPress (shell visual Bebelume),
 * anexando o PDF/XML oficiais baixados da TransmiteNota. Substitui o envio pelo
 * endpoint EnviarEmailNfse da TransmiteNota (que está fora do nosso controle).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Bebelume_NFSe_Email {

    /**
     * Resolve o destinatário da nota: e-mail do usuário WP vinculado ao pedido;
     * se o usuário não existir (ex.: conta de teste removida), usa o e-mail do
     * tomador gravado no payload da emissão.
     */
    public static function destinatario( object $nota ): string|WP_Error {
        if ( ! empty( $nota->user_id ) ) {
            $user = get_userdata( (int) $nota->user_id );
            if ( $user && ! empty( $user->user_email ) ) {
                return $user->user_email;
            }
        }
        $payload = is_string( $nota->payload ) ? json_decode( $nota->payload, true ) : null;
        if ( is_array( $payload ) && ! empty( $payload['email_tomador'] ) ) {
            return sanitize_email( $payload['email_tomador'] );
        }
        return new WP_Error( 'bbl_nfse_email_sem_destinatario', 'Sem destinatário para a nota #' . $nota->id );
    }

    /**
     * Envia o e-mail da nota aprovada. $para = e-mail de destino.
     */
    public static function enviar( $nota, string $para ): bool|WP_Error {
        if ( is_numeric( $nota ) ) {
            $nota = Bebelume_NFSe_DB::get( (int) $nota );
        }
        if ( ! $nota || empty( $nota->searchkey ) ) {
            return new WP_Error( 'bbl_nfse_email_nota', 'Nota não encontrada.' );
        }
        if ( 'aprovada' !== $nota->status ) {
            return new WP_Error( 'bbl_nfse_email_status', 'Nota #' . $nota->id . ' não está aprovada (' . $nota->status . ').' );
        }

        if ( ! class_exists( 'BBL_Emails' ) ) {
            return new WP_Error( 'bbl_nfse_email_shell', 'BBL_Emails (shell visual) não está disponível.' );
        }

        $dados = self::dados_da_nota( $nota );

        $html = self::html( $dados );
        if ( is_wp_error( $html ) ) {
            return $html;
        }

        $subject = sprintf( 'Sua Nota Fiscal de Serviço — %s (Nº %s)', get_bloginfo( 'name' ), $dados['numero'] );

        $from_email = function_exists( 'pmpro_getOption' ) ? pmpro_getOption( 'from_email' ) : '';
        $from_name  = function_exists( 'pmpro_getOption' ) ? pmpro_getOption( 'from_name' ) : '';
        $from_email = $from_email ?: get_option( 'admin_email' );
        $from_name  = $from_name ?: get_bloginfo( 'name' );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        ];

        $anexos = self::anexos( $nota );

        $ok = wp_mail( $para, $subject, $html, $headers, $anexos );

        // Limpa os temporários criados para os anexos.
        foreach ( (array) $anexos as $a ) {
            if ( is_string( $a ) && is_file( $a ) ) {
                @unlink( $a );
            }
        }

        if ( $ok ) {
            if ( get_option( 'bbl_nfse_debug', '0' ) === '1' ) {
                error_log( '[Bebelume NFS-e] Email WP da nota #' . $nota->id . ' enviado para ' . $para );
            }
            return true;
        }
        return new WP_Error( 'bbl_nfse_email_wp', 'wp_mail falhou para a nota #' . $nota->id );
    }

    // -------------------------------------------------------------------------

    private static function dados_da_nota( object $nota ): array {
        $resposta = is_string( $nota->resposta ) ? json_decode( $nota->resposta, true ) : null;
        $payload  = is_string( $nota->payload ) ? json_decode( $nota->payload, true ) : null;
        $res      = is_array( $resposta ) ? ( $resposta['resultado'] ?? [] ) : [];
        if ( ! is_array( $res ) ) {
            $res = [];
        }

        $numero = $res['numero'] ?? $nota->numero_nfse ?? '';
        $valor  = $res['valor_total_nfse'] ?? $payload['valor_total_nfse'] ?? $nota->valor ?? 0;

        // data_emissao chega como d/m/Y. O strtotime leria "04/09/2026" como
        // 9/abr (formato americano m/d/Y) — normaliza p/ Y-m-d antes de formatar.
        $emissao  = $payload['data_emissao'] ?? null;
        $data_raw = is_string( $emissao ) ? self::normalizar_data( $emissao ) : null;
        if ( ! $data_raw && ! empty( $nota->created_at ) && '0000-00-00' !== substr( (string) $nota->created_at, 0, 10 ) ) {
            $data_raw = $nota->created_at;
        }
        $data = $data_raw
            ? date_i18n( 'd/m/Y', strtotime( (string) $data_raw ) )
            : date_i18n( 'd/m/Y' );

        return [
            'numero'    => (string) $numero,
            'plano'     => self::plano_da_nota( $nota ),
            'valor'     => 'R$ ' . number_format( (float) $valor, 2, ',', '.' ),
            'data'      => $data,
            'cod_verif' => (string) ( $res['codigo_verificador'] ?? '' ),
            'tomador'   => (string) ( $payload['razao_social_tomador'] ?? $res['tomador'] ?? '' ),
            'link_pdf'  => (string) ( $nota->link_pdf ?: ( $res['link_pdf'] ?? '' ) ),
            'link_xml'  => (string) ( $nota->link_xml ?: ( $res['link_xml'] ?? '' ) ),
        ];
    }

    /**
     * Nome do plano (nível PMPro) do pedido da nota. Lido direto do pedido via SQL
     * (membership_id -> nome do nível), para NÃO depender do usuário WP — a conta
     * do assinante pode já ter sido removida. Retorna '' quando não é possível
     * descobrir (nota avulsa, pedido apagado, tabelas ausentes).
     */
    private static function plano_da_nota( object $nota ): string {
        $order_id = (int) $nota->order_id;
        if ( $order_id < 1 ) {
            return '';
        }
        global $wpdb;
        $prefix   = $wpdb->prefix;
        $level_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT membership_id FROM {$prefix}pmpro_membership_orders WHERE id = %d LIMIT 1", $order_id
        ) );
        if ( $level_id < 1 ) {
            return '';
        }
        return (string) $wpdb->get_var( $wpdb->prepare(
            "SELECT name FROM {$prefix}pmpro_membership_levels WHERE id = %d LIMIT 1", $level_id
        ) );
    }

    /**
     * Converte data no formato brasileiro d/m/Y (como a TransmiteNota devolve)
     * para Y-m-d, evitando que o strtotime a leia como m/d/Y. Retorna intacto
     * qualquer valor já em formato ISO (Y-m-d ...).
     */
    private static function normalizar_data( string $data ): ?string {
        if ( preg_match( '#^(\d{2})/(\d{2})/(\d{4})$#', $data, $m ) ) {
            return sprintf( '%s-%s-%s', $m[3], $m[2], $m[1] );
        }
        return $data !== '' ? $data : null;
    }

    private static function html( array $d ): string|WP_Error {
        $rows = [
            'Nota Nº'            => esc_html( $d['numero'] ),
            'Plano assinado'     => esc_html( $d['plano'] ),
            'Data de emissão'    => esc_html( $d['data'] ),
            'Valor'              => esc_html( $d['valor'] ),
        ];
        if ( '' !== $d['cod_verif'] ) {
            $rows['Código de verificação'] = esc_html( $d['cod_verif'] );
        }
        if ( '' !== $d['tomador'] ) {
            $rows['Emitida para'] = esc_html( $d['tomador'] );
        }

        $content =
            self::para( 'Olá! Emitimos a sua <strong>Nota Fiscal de Serviço</strong> referente à assinatura no Bebelume. O documento segue em anexo neste e-mail (PDF e XML).' ) .
            self::details( $rows ) .
            ( '' !== $d['link_pdf'] ? self::button( $d['link_pdf'], 'Baixar o PDF da nota' ) : '' ) .
            ( '' !== $d['link_xml' ] ? self::muted( '<a href="' . esc_url( $d['link_xml'] ) . '" style="color:#b0bcc8;text-decoration:underline;">Baixar o XML da nota</a>' ) : '' ) .
            self::muted( 'Guarde este documento para a sua declaração. Qualquer dúvida, fale com a gente: <a href="mailto:' . esc_attr( get_option( 'admin_email' ) ) . '" style="color:#b0bcc8;text-decoration:underline;">' . esc_html( get_option( 'admin_email' ) ) . '</a>' );

        return BBL_Emails::get_instance()->build_email( [
            'title' => 'Sua Nota Fiscal está pronta',
            'body'  => $content,
            'align' => 'center',
        ] );
    }

    private static function anexos( object $nota ): array {
        $arquivos = [];
        $pdf = self::baixar( $nota->link_pdf, 'NFSe-' . $nota->numero_nfse . '.pdf' );
        if ( $pdf ) {
            $arquivos[] = $pdf;
        }
        $xml = self::baixar( $nota->link_xml, 'NFSe-' . $nota->numero_nfse . '.xml' );
        if ( $xml ) {
            $arquivos[] = $xml;
        }
        if ( $arquivos && get_option( 'bbl_nfse_debug', '0' ) === '1' ) {
            error_log( '[Bebelume NFS-e] Anexos da nota #' . $nota->id . ': ' . count( $arquivos ) . ' baixados.' );
        }
        return $arquivos;
    }

    /**
     * Baixa o arquivo e grava em um temporário nomeado (uploads/nfse-email/),
     * retornando o caminho. Tenta https se a URL original (http) falhar.
     */
    private static function baixar( string $url, string $nome ): ?string {
        if ( '' === $url ) {
            return null;
        }
        $base = wp_upload_dir();
        $dir  = $base['basedir'] . '/nfse-email';
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        $arquivo = $dir . '/' . sanitize_file_name( $nome );

        $candidatas = [ $url ];
        $https = preg_replace( '#^http://#', 'https://', $url );
        if ( $https !== $url ) {
            $candidatas[] = $https;
        }
        foreach ( $candidatas as $u ) {
            $resp = wp_remote_get( $u, [ 'timeout' => 30 ] );
            if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
                continue;
            }
            $body = wp_remote_retrieve_body( $resp );
            if ( '' === $body ) {
                continue;
            }
            if ( false === file_put_contents( $arquivo, $body ) ) {
                continue;
            }
            return $arquivo;
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // Helpers de montagem (mesmo padrão visual dos e-mails de conta)
    // -------------------------------------------------------------------------

    private static function para( string $html ): string {
        return '<p style="margin:0 0 18px;line-height:1.6;">' . $html . '</p>';
    }

    private static function button( string $url, string $label ): string {
        return '<div style="text-align:center;margin:6px 0 10px;">'
            . '<a href="' . esc_url( $url ) . '" style="display:inline-block;padding:14px 32px;background:#E73665;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">'
            . $label . '</a></div>';
    }

    private static function muted( string $html ): string {
        return '<p style="margin:14px 0 0;font-size:13px;color:#b0bcc8;text-align:center;">' . $html . '</p>';
    }

    private static function details( array $rows ): string {
        $html = '<div style="margin:8px 0 22px;padding:16px 20px;background:#f7f3f9;border-radius:12px;text-align:left;">';
        foreach ( $rows as $label => $value ) {
            if ( '' === $value ) {
                continue;
            }
            $html .= '<p style="margin:0 0 6px;font-size:14px;line-height:1.5;"><strong>' . $label . ':</strong> ' . $value . '</p>';
        }
        $html .= '</div>';
        return $html;
    }
}
