<?php
/**
 * Bebelume_NFSe_PMPro
 * Integração com o Paid Memberships Pro.
 * Emite NFS-e SOMENTE quando o pagamento for real (total > 0, status = success).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Bebelume_NFSe_PMPro {

    public static function init(): void {
        // Hook principal para pagamentos recorrentes via webhook do Stripe.
        // Disparado em gateway-request-handlers.php após saveOrder() no invoice.payment_succeeded.
        add_action( 'pmpro_subscription_payment_completed', [ __CLASS__, 'maybe_emit_nfse' ] );

        // Hook para pagamentos diretos no checkout (primeiro pagamento sem trial).
        add_action( 'pmpro_updated_order', [ __CLASS__, 'maybe_emit_nfse' ] );

        // Cobrança imediata no checkout (ex.: retornante, sem trial) é finalizada por
        // pmpro_complete_checkout() via saveOrder() direto + pmpro_after_checkout — sem
        // passar por updateStatus(), então os hooks acima não disparam nesse fluxo.
        add_action( 'pmpro_after_checkout', [ __CLASS__, 'after_checkout_emit_nfse' ], 20, 2 );
    }

    public static function after_checkout_emit_nfse( $user_id, $order ): void {
        self::maybe_emit_nfse( $order );
    }

    // -------------------------------------------------------------------------
    // Consulta/reemissão para a tela bloqueante de dados fiscais
    // -------------------------------------------------------------------------

    /**
     * Pedidos pagos (success, total > 0) do usuário que NÃO têm nota aprovada.
     * Usado pelo backstop do bloqueio (cobrança feita sem nota) e pela reemissão.
     *
     * @return int[]
     */
    public static function get_pedidos_sem_nota_aprovada( int $user_id ): array {
        global $wpdb;

        $orders = $wpdb->prefix . 'pmpro_membership_orders';
        $notas  = $wpdb->prefix . BBL_NFSE_TABLE;

        $sql = "SELECT o.id
                FROM {$orders} o
                WHERE o.user_id = %d
                  AND o.status = 'success'
                  AND o.total > 0
                  AND NOT EXISTS (
                    SELECT 1 FROM {$notas} n
                    WHERE n.order_id = o.id AND n.status = 'aprovada'
                  )";

        return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( $sql, $user_id ) ) ?: [] );
    }

    /**
     * Assinaturas ativas/trialing do usuário com cobrança nos próximos N dias.
     * O fim do trial é a `next_payment_date` da sub `trialing` (não há coluna trial_end).
     *
     * @return object[] stdClass com id, status, next_payment_date
     */
    public static function get_assinaturas_para_cobrar( int $user_id, int $dias = 7 ): array {
        global $wpdb;

        $table = $wpdb->prefix . 'pmpro_subscriptions';

        $sql = "SELECT id, status, next_payment_date
                FROM {$table}
                WHERE user_id = %d
                  AND status IN ('active','trialing')
                  AND next_payment_date IS NOT NULL
                  AND next_payment_date != '0000-00-00 00:00:00'
                  AND next_payment_date <= DATE_ADD( NOW(), INTERVAL %d DAY )";

        return $wpdb->get_results( $wpdb->prepare( $sql, $user_id, (int) $dias ) ) ?: [];
    }

    /**
     * Reemite a NFS-e de todos os pedidos pagos sem nota aprovada do usuário.
     * Chamado logo após o salvamento dos dados fiscais na tela bloqueante.
     * Atualiza a última nota do pedido (ou insere nova se não existir).
     *
     * @return array{reemitidos:int, erros:list<string>}
     */
    public static function reemitir_apos_dados( int $user_id ): array {
        if ( ! class_exists( 'MemberOrder' ) ) {
            return [ 'reemitidos' => 0, 'erros' => [ 'PMPro (MemberOrder) não está disponível.' ] ];
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return [ 'reemitidos' => 0, 'erros' => [ 'Usuário não encontrado.' ] ];
        }

        $order_ids  = self::get_pedidos_sem_nota_aprovada( $user_id );
        $reemitidos = 0;
        $erros      = [];

        foreach ( $order_ids as $order_id ) {
            $order = MemberOrder::get_order( $order_id );
            if ( ! $order || strtolower( (string) $order->status ) !== 'success' || (float) $order->total <= 0 ) {
                continue;
            }

            $dados = self::build_payload( $order, $user );
            if ( is_wp_error( $dados ) ) {
                $erros[] = sprintf( 'Pedido %d: %s', $order_id, $dados->get_error_message() );
                continue;
            }

            $api      = new Bebelume_NFSe_API_Client();
            $resposta = $api->enviar_nfse( $dados );

            $registro = [
                'order_id'  => (int) $order_id,
                'user_id'   => $user_id,
                'valor'     => (float) $order->total,
                'payload'   => wp_json_encode( $dados ),
                'link_pdf'  => '',
                'link_xml'  => '',
            ];

            if ( is_wp_error( $resposta ) ) {
                $registro['searchkey'] = '';
                $registro['status']    = 'erro';
                $registro['resposta']  = $resposta->get_error_message();
                $erros[] = sprintf( 'Pedido %d: %s', $order_id, $resposta->get_error_message() );
            } else {
                $status_api = strtolower( $resposta['status'] ?? 'erro' );

                if ( $status_api === 'ok' ) {
                    $registro['searchkey'] = $resposta['searchkey'] ?? '';
                    $registro['status']    = 'pendente';
                    $registro['resposta']  = wp_json_encode( $resposta );
                    $reemitidos++;
                } else {
                    $registro['searchkey'] = '';
                    $registro['status']    = 'erro';
                    $registro['resposta']  = wp_json_encode( $resposta );
                    $erros[] = sprintf( 'Pedido %d: %s', $order_id, $resposta['descricao'] ?? 'API recusou a emissão.' );
                }
            }

            $existente = Bebelume_NFSe_DB::get_by_order( $order_id );
            if ( $existente ) {
                Bebelume_NFSe_DB::update( (int) $existente->id, $registro );
            } else {
                Bebelume_NFSe_DB::insert( $registro );
            }
        }

        return [ 'reemitidos' => $reemitidos, 'erros' => $erros ];
    }

    // -------------------------------------------------------------------------
    // Gatilho principal
    // -------------------------------------------------------------------------

    /**
     * Verifica se deve emitir NFS-e para o pedido.
     * Só emite quando: status = success E total > 0 E ainda não emitiu antes.
     *
     * @param MemberOrder $order
     */
    public static function maybe_emit_nfse( $order ): void {
        self::log( 'maybe_emit_nfse chamado — order_id: ' . ( $order->id ?? 'null' ) . ' | total: ' . ( $order->total ?? 'null' ) . ' | status: ' . ( $order->status ?? 'null' ) );

        // 1. Apenas pedidos pagos com valor real
        if ( strtolower( (string) $order->status ) !== 'success' ) {
            self::log( 'Bloqueado: status não é success (' . $order->status . ')' );
            return;
        }
        if ( (float) $order->total <= 0 ) {
            self::log( 'Bloqueado: total <= 0 (' . $order->total . ')' );
            return;
        }

        $order_id = (int) $order->id;

        // 2. Evita emissão duplicada para o mesmo pedido
        $existente = Bebelume_NFSe_DB::get_by_order( $order_id );
        if ( $existente ) {
            self::log( 'Bloqueado: nota já emitida para order_id ' . $order_id );
            return;
        }

        // 2b. Trava curta contra corrida entre hooks concorrentes
        //     (checkout + webhook do Stripe podem cair quase juntos, e o SELECT
        //     acima sozinho não protege essa janela).
        $lock = 'bbl_nfse_lock_' . $order_id;
        if ( get_transient( $lock ) ) {
            self::log( 'Bloqueado: emissão já em andamento para order_id ' . $order_id );
            return;
        }
        set_transient( $lock, 1, 2 * MINUTE_IN_SECONDS );

        // 3. Busca dados do usuário
        $user = get_userdata( $order->user_id );
        if ( ! $user ) {
            self::log( 'Bloqueado: usuário não encontrado (user_id: ' . $order->user_id . ')' );
            delete_transient( $lock );
            return;
        }

        // 4. Monta o payload e envia
        $dados = self::build_payload( $order, $user );
        if ( is_wp_error( $dados ) ) {
            self::log_error( $order, $dados->get_error_message() );
            delete_transient( $lock );
            return;
        }

        $api      = new Bebelume_NFSe_API_Client();
        $resposta = $api->enviar_nfse( $dados );

        self::handle_response( $order, $dados, $resposta );

        // A partir daqui já existe registro no banco, então o get_by_order()
        // volta a ser suficiente — libera a trava.
        delete_transient( $lock );
    }

    /**
     * Log condicionado à opção de debug do plugin.
     */
    private static function log( string $msg ): void {
        if ( get_option( 'bbl_nfse_debug', '0' ) === '1' ) {
            error_log( '[Bebelume NFS-e] ' . $msg );
        }
    }

    // -------------------------------------------------------------------------
    // Montagem do payload
    // -------------------------------------------------------------------------

    /**
     * Constrói o array "Dados" para EnviarNfse a partir do pedido PMPro.
     *
     * Fontes dos dados do tomador:
     *  - CPF       → user_meta 'cpf' ou 'cpf_formatted'  (salvo pelo bebelume-profile/class-pmpro-fields.php)
     *  - Endereço  → user_meta 'pmpro_baddress1'          (salvo nativamente pelo PMPro no checkout)
     *  - Bairro    → user_meta 'pmpro_baddress2'          (usamos address2 como bairro, convenção Bebelume)
     *  - Município → user_meta 'pmpro_bcity'
     *  - UF        → user_meta 'pmpro_bstate'
     *  - CEP       → user_meta 'pmpro_bzipcode'
     *  - Telefone  → user_meta 'pmpro_bphone'
     */
    private static function build_payload( $order, WP_User $user ): array|WP_Error {
        $tomador = self::tomador_from_user( $user );

        $faltando = self::validar_tomador( $tomador );
        if ( $faltando ) {
            return new WP_Error(
                'bbl_nfse_dados',
                sprintf( 'Dados faltando no perfil do usuário %d: %s', $user->ID, implode( ', ', $faltando ) )
            );
        }

        if ( empty( $tomador['bairro'] ) ) {
            self::log( 'Atenção: bairro vazio para o usuário ' . $user->ID . ' — emitindo com fallback "Centro".' );
        }

        return self::montar_payload( $tomador, (float) $order->total );
    }

    /**
     * Extrai os dados do tomador a partir do perfil do usuário WP.
     * Retorna array cru (sem validação) — use validar_tomador() em seguida.
     */
    public static function tomador_from_user( WP_User $user ): array {
        // --- CPF: salvo pelo bebelume-profile em 'cpf' (só dígitos) ou 'cpf_formatted' ---
        $cpf_raw = get_user_meta( $user->ID, 'cpf', true );
        if ( empty( $cpf_raw ) ) {
            // fallback: tentar cpf_formatted e extrair dígitos
            $cpf_fmt = get_user_meta( $user->ID, 'cpf_formatted', true );
            $cpf_raw = preg_replace( '/\D/', '', (string) $cpf_fmt );
        }

        $nome_completo = trim( $user->first_name . ' ' . $user->last_name );
        if ( empty( $nome_completo ) ) {
            $nome_completo = $user->display_name;
        }

        // --- Endereço: campos nativos do PMPro (salvos pelo gateway/checkout) ---
        return [
            'nome'      => $nome_completo,
            'email'     => $user->user_email,
            'cpf_cnpj'  => preg_replace( '/\D/', '', (string) $cpf_raw ),
            'telefone'  => (string) get_user_meta( $user->ID, 'pmpro_bphone',    true ),
            'endereco'  => (string) get_user_meta( $user->ID, 'pmpro_baddress1', true ),
            'bairro'    => (string) get_user_meta( $user->ID, 'pmpro_baddress2', true ), // address2 = bairro (convenção Bebelume)
            'municipio' => (string) get_user_meta( $user->ID, 'pmpro_bcity',     true ),
            'uf'        => (string) get_user_meta( $user->ID, 'pmpro_bstate',    true ),
            'cep'       => preg_replace( '/\D/', '', (string) get_user_meta( $user->ID, 'pmpro_bzipcode', true ) ),
        ];
    }

    /**
     * Valida os campos obrigatórios do tomador.
     * Retorna a lista de campos faltando (array vazio = tudo certo).
     *
     * @param bool $com_meta Inclui o nome do user_meta no rótulo. Útil quando os
     *                       dados vêm do perfil (ajuda a localizar o campo);
     *                       ruído quando foram digitados à mão na tela de teste.
     */
    public static function validar_tomador( array $t, bool $com_meta = true ): array {
        $campos = [
            'cpf_cnpj'  => [ 'CPF',       'cpf' ],
            'telefone'  => [ 'Telefone',  'pmpro_bphone' ],
            'endereco'  => [ 'Endereço',  'pmpro_baddress1' ],
            'municipio' => [ 'Município', 'pmpro_bcity' ],
            'uf'        => [ 'UF',        'pmpro_bstate' ],
            'cep'       => [ 'CEP',       'pmpro_bzipcode' ],
        ];

        $vazios = [];
        if ( strlen( (string) ( $t['cpf_cnpj'] ?? '' ) ) < 11 ) $vazios[] = 'cpf_cnpj';
        if ( empty( $t['telefone'] ) )  $vazios[] = 'telefone';
        if ( empty( $t['endereco'] ) )  $vazios[] = 'endereco';
        if ( empty( $t['municipio'] ) ) $vazios[] = 'municipio';
        if ( empty( $t['uf'] ) )        $vazios[] = 'uf';
        if ( strlen( (string) ( $t['cep'] ?? '' ) ) < 8 ) $vazios[] = 'cep';

        $faltando = [];
        foreach ( $vazios as $campo ) {
            [ $rotulo, $meta ] = $campos[ $campo ];
            $faltando[] = $com_meta ? sprintf( '%s (meta: %s)', $rotulo, $meta ) : $rotulo;
        }

        return $faltando;
    }

    /**
     * Monta o array "Dados" do EnviarNfse.
     * Usado tanto pela emissão automática quanto pela tela de teste.
     *
     * @param array  $t         Tomador já validado (ver tomador_from_user/validar_tomador)
     * @param float  $valor     Valor do serviço
     * @param string $descricao Descrição do item; vazio = usa a das configurações
     */
    public static function montar_payload( array $t, float $valor, string $descricao = '' ): array {
        $opts = get_option( 'bbl_nfse_settings', [] );

        // --- Valores ---
        $valor_total = number_format( $valor, 2, '.', '' );

        // --- Dados fiscais do prestador (configurações do plugin) ---
        $natureza        = $opts['natureza_operacao']  ?? '1';
        $tipo_servico    = $opts['tipo_servico']       ?? '';
        $codigo_servico  = $opts['codigo_servico']     ?? '';
        $desc_servico    = $descricao !== ''
            ? $descricao
            : ( $opts['descricao_servico'] ?? 'Assinatura de serviço digital' );
        $aliquota        = $opts['valor_aliquota']     ?? '2';
        $iss_retido      = $opts['iss_retido']         ?? '2';

        // Bairro: se address2 estiver vazio, usa 'Centro' como fallback (campo não obrigatório na API)
        $bairro_final = ! empty( $t['bairro'] ) ? $t['bairro'] : 'Centro';

        return [
            'natureza_operacao'     => $natureza,
            'tipo_servico'          => $tipo_servico,
            'tipo_rps'              => 1,
            // current_time() respeita o fuso do site. Com date() a nota emitida
            // após as 21h (BRT) saía com a data do dia seguinte.
            'data_emissao'          => current_time( 'd/m/Y' ),
            'data_competencia'      => current_time( 'd/m/Y' ),
            'razao_social_tomador'  => self::upper( (string) $t['nome'] ),
            'email_tomador'         => $t['email'],
            'cnpj_tomador'          => $t['cpf_cnpj'],
            'telefone_tomador'      => preg_replace( '/\D/', '', (string) $t['telefone'] ),
            'endereco_tomador'      => $t['endereco'],
            'bairro_tomador'        => $bairro_final,
            'municipio_tomador'     => $t['municipio'],
            'uf_tomador'            => self::upper( (string) $t['uf'] ),
            'pais_tomador'          => 'BRASIL',
            'cep_tomador'           => $t['cep'],
            'valor_aliquota'        => $aliquota,
            'iss_retido'            => (int) $iss_retido,
            'valor_base_calculo'    => $valor_total,
            'valor_liquido'         => $valor_total,
            'valor_total_nfse'      => $valor_total,
            'valor_total_servicos'  => $valor_total,
            'Itens'                 => [ [
                [
                    'codigo_servico'    => $codigo_servico,
                    'descricao_servico' => $desc_servico,
                    'valor_servico'     => $valor_total,
                ],
            ] ],
        ];
    }

    // -------------------------------------------------------------------------
    // Emissão avulsa (tela de teste)
    // -------------------------------------------------------------------------

    /**
     * Emite uma nota avulsa, sem pedido do PMPro.
     * O registro é gravado com order_id = 0, o que a marca como nota de teste
     * na listagem e a mantém elegível para os botões Atualizar/Cancelar.
     *
     * @return array{id:int,payload:array,resposta:mixed}|WP_Error
     */
    public static function emitir_avulsa( array $tomador, float $valor, string $descricao = '', int $user_id = 0, bool $do_perfil = true ) {
        if ( $valor <= 0 ) {
            return new WP_Error( 'bbl_nfse_valor', 'O valor precisa ser maior que zero.' );
        }

        $faltando = self::validar_tomador( $tomador, $do_perfil );
        if ( $faltando ) {
            return new WP_Error(
                'bbl_nfse_dados',
                'Dados do tomador incompletos: ' . implode( ', ', $faltando )
            );
        }

        $payload  = self::montar_payload( $tomador, $valor, $descricao );
        $api      = new Bebelume_NFSe_API_Client();
        $resposta = $api->enviar_nfse( $payload );

        $registro = [
            'order_id' => 0,
            'user_id'  => $user_id,
            'valor'    => $valor,
            'payload'  => wp_json_encode( $payload ),
            'link_pdf' => '',
            'link_xml' => '',
        ];

        if ( is_wp_error( $resposta ) ) {
            $registro['searchkey'] = '';
            $registro['status']    = 'erro';
            $registro['resposta']  = $resposta->get_error_message();
            Bebelume_NFSe_DB::insert( $registro );
            return $resposta;
        }

        $ok = strtolower( $resposta['status'] ?? '' ) === 'ok';

        $registro['searchkey'] = $ok ? ( $resposta['searchkey'] ?? '' ) : '';
        $registro['status']    = $ok ? 'pendente' : 'erro';
        $registro['resposta']  = wp_json_encode( $resposta );

        $id = Bebelume_NFSe_DB::insert( $registro );

        return [
            'id'       => (int) $id,
            'payload'  => $payload,
            'resposta' => $resposta,
        ];
    }

    /**
     * Maiúsculas com suporte a acentos.
     * strtoupper() não é multibyte: "José" virava "JOSé" impresso na nota.
     */
    private static function upper( string $texto ): string {
        return function_exists( 'mb_strtoupper' )
            ? mb_strtoupper( $texto, 'UTF-8' )
            : strtoupper( $texto );
    }

    // -------------------------------------------------------------------------
    // Tratamento da resposta
    // -------------------------------------------------------------------------

    private static function handle_response( $order, array $payload, $resposta ): void {
        $resposta_json = wp_json_encode( $resposta );
        $payload_json  = wp_json_encode( $payload );

        if ( is_wp_error( $resposta ) ) {
            Bebelume_NFSe_DB::insert( [
                'order_id'  => (int) $order->id,
                'user_id'   => (int) $order->user_id,
                'searchkey' => '',
                'status'    => 'erro',
                'valor'     => (float) $order->total,
                'payload'   => $payload_json,
                'resposta'  => $resposta->get_error_message(),
                'link_pdf'  => '',
                'link_xml'  => '',
            ] );
            return;
        }

        $status_api = strtolower( $resposta['status'] ?? 'erro' );

        if ( $status_api === 'ok' ) {
            Bebelume_NFSe_DB::insert( [
                'order_id'  => (int) $order->id,
                'user_id'   => (int) $order->user_id,
                'searchkey' => $resposta['searchkey'] ?? '',
                'status'    => 'pendente',
                'valor'     => (float) $order->total,
                'payload'   => $payload_json,
                'resposta'  => $resposta_json,
                'link_pdf'  => '',
                'link_xml'  => '',
            ] );
        } else {
            Bebelume_NFSe_DB::insert( [
                'order_id'  => (int) $order->id,
                'user_id'   => (int) $order->user_id,
                'searchkey' => '',
                'status'    => 'erro',
                'valor'     => (float) $order->total,
                'payload'   => $payload_json,
                'resposta'  => $resposta_json,
                'link_pdf'  => '',
                'link_xml'  => '',
            ] );
        }
    }

    private static function log_error( $order, string $msg ): void {
        Bebelume_NFSe_DB::insert( [
            'order_id'  => (int) $order->id,
            'user_id'   => (int) $order->user_id,
            'searchkey' => '',
            'status'    => 'erro',
            'valor'     => (float) $order->total,
            'payload'   => '',
            'resposta'  => $msg,
            'link_pdf'  => '',
            'link_xml'  => '',
        ] );
    }

    // -------------------------------------------------------------------------
    // Cron: atualiza status das notas pendentes
    // -------------------------------------------------------------------------

    public static function check_pending_notes(): void {
        $pendentes = Bebelume_NFSe_DB::get_pending();
        if ( empty( $pendentes ) ) return;

        $api = new Bebelume_NFSe_API_Client();

        foreach ( $pendentes as $nota ) {
            $resposta = $api->consultar_emissao( $nota->searchkey );
            if ( is_wp_error( $resposta ) ) continue;
            if ( ( strtolower( $resposta['status'] ?? '' ) ) !== 'ok' ) continue;

            $resultado = $resposta['resultado'] ?? [];
            $novo_status = strtolower( $resultado['status'] ?? $nota->status );

            // Normaliza status da TransmiteNota para o nosso padrão
            $map = [
                'aguardando processamento' => 'pendente',
                'em andamento'             => 'processando',
                'aprovada'                 => 'aprovada',
                'aprovada com correcao'    => 'aprovada',
                'cancelada'                => 'cancelada',
                'reprovada'                => 'reprovada',
            ];
            $status = $map[ $novo_status ] ?? $nota->status;

            $update = [
                'status'      => $status,
                'numero_nfse' => $resultado['numero']           ?? $nota->numero_nfse,
                'link_pdf'    => $resultado['link_pdf']         ?? $nota->link_pdf,
                'link_xml'    => $resultado['link_xml']         ?? $nota->link_xml,
                'resposta'    => wp_json_encode( $resposta ),
            ];

            Bebelume_NFSe_DB::update( (int) $nota->id, $update );

            // Envio automático de email quando nota aprovada pela primeira vez.
            // Via WordPress (Bebelume_NFSe_Email, PDF/XML em anexo) quando a opção
            // bbl_nfse_email_wp_aprovacao estiver ativa. O endpoint da TransmiteNota
            // (EnviarEmailNfse) está fora do ar e não deve mais ser usado.
            if ( $status === 'aprovada' && $nota->status !== 'aprovada' ) {
                self::enviar_email_aprovacao_wp( (int) $nota->id );
            }

            // Dispara ação para outros plugins/código custom reagirem
            do_action( 'bbl_nfse_status_updated', $nota->id, $status, $resultado );
        }
    }

    /**
     * Envia o email da NFS-e aprovada via WordPress (Bebelume_NFSe_Email),
     * anexando PDF/XML baixados do link oficial. Controlado pela opção
     * bbl_nfse_email_wp_aprovacao ('1' = envia ao aprovar). Chamado
     * automaticamente quando a nota é aprovada pelo cron.
     */
    private static function enviar_email_aprovacao_wp( int $nota_id ): void {
        if ( get_option( 'bbl_nfse_email_wp_aprovacao', '0' ) !== '1' ) {
            return;
        }
        if ( ! class_exists( 'Bebelume_NFSe_Email' ) ) {
            error_log( '[Bebelume NFS-e] Bebelume_NFSe_Email indisponível para a nota #' . $nota_id );
            return;
        }

        // O $nota de check_pending_notes() está desatualizado (número/links em
        // branco) porque a atualização acima já foi gravada — recarrega a linha.
        $nota = Bebelume_NFSe_DB::get( $nota_id );
        if ( ! $nota || empty( $nota->searchkey ) ) {
            error_log( '[Bebelume NFS-e] Nota #' . $nota_id . ' não encontrada para envio de email.' );
            return;
        }

        $para = Bebelume_NFSe_Email::destinatario( $nota );
        if ( is_wp_error( $para ) ) {
            error_log( '[Bebelume NFS-e] ' . $para->get_error_message() );
            return;
        }

        $enviado = Bebelume_NFSe_Email::enviar( $nota, $para );
        if ( is_wp_error( $enviado ) ) {
            error_log( '[Bebelume NFS-e] Erro ao enviar email da nota #' . $nota_id . ': ' . $enviado->get_error_message() );
            return;
        }

        // Registra o envio no histórico da resposta.
        Bebelume_NFSe_DB::update( $nota_id, [
            'resposta' => wp_json_encode( array_merge(
                json_decode( $nota->resposta, true ) ?: [],
                [ 'email_enviado' => $para, 'email_enviado_em' => current_time( 'mysql' ) ]
            ) ),
        ] );

        if ( get_option( 'bbl_nfse_debug', '0' ) === '1' ) {
            error_log( '[Bebelume NFS-e] Email da nota #' . $nota_id . ' enviado para ' . $para );
        }
    }
}
