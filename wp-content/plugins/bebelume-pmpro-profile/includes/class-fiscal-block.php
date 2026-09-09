<?php
/**
 * BBL_Fiscal_Block — Tela bloqueante de dados fiscais
 *
 * Bloqueia TODO o acesso do usuário quando falta cobrança próxima (ou já
 * cobrado sem nota) e os dados fiscais estão incompletos. Ao salvar os 6
 * campos, grava o user_meta, reemite a NFS-e pendente e libera o acesso.
 *
 * Controlado pela feature flag BBL_FISCAL_BLOCK (wp-config.php).
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_Fiscal_Block {

    const DIAS_PRECOBRANCA = 7;

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Prioridade 1: intercepta antes de qualquer outro redirect de template.
        add_action( 'template_redirect', [ $this, 'maybe_block' ], 1 );
    }

    // =========================================================================
    // INTERCEPTAÇÃO
    // =========================================================================

    public function maybe_block(): void {
        if ( ! defined( 'BBL_FISCAL_BLOCK' ) || ! BBL_FISCAL_BLOCK ) {
            return;
        }
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            return;
        }
        if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
            return;
        }
        if ( defined( 'REST_REQUEST' ) || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
            return;
        }
        if ( ! is_user_logged_in() ) {
            return;
        }
        if ( function_exists( 'is_login' ) && is_login() ) {
            return;
        }

        // Com o bloqueio ativo, a área de conta (e telas associadas) permanece
        // acessível para o usuário gerenciar a assinatura e completar o cadastro.
        if ( $this->is_account_route() ) {
            return;
        }

        $user_id = get_current_user_id();

        if ( ! $this->block_required( $user_id ) ) {
            return;
        }

        if ( ! empty( $_POST ) ) {
            $this->process_form( $user_id );
            return; // process_form re-renderiza (falha) ou redireciona (sucesso)
        }

        $this->render_form( $user_id, [], [] );
        exit;
    }

    // =========================================================================
    // REGRA DE BLOQUEIO
    // =========================================================================

    private function block_required( int $user_id ): bool {
        $transient = 'bbl_fiscal_block_' . $user_id;
        $cached    = get_transient( $transient );
        if ( $cached !== false ) {
            return (bool) $cached;
        }

        $required = $this->compute_block( $user_id );
        set_transient( $transient, $required ? 1 : 0, 5 * MINUTE_IN_SECONDS );
        return $required;
    }

    private function compute_block( int $user_id ): bool {
        if ( ! class_exists( 'Bebelume_NFSe_PMPro' ) ) {
            return false;
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }

        // Dados completos → nunca bloqueia (mesmo que a API da NFS-e falhe).
        $tomador  = Bebelume_NFSe_PMPro::tomador_from_user( $user );
        $faltando = Bebelume_NFSe_PMPro::validar_tomador( $tomador );
        if ( empty( $faltando ) ) {
            return false;
        }

        // Gatilho pré-cobrança: assinatura ativa/trialing com cobrança em ≤7 dias.
        if ( ! empty( Bebelume_NFSe_PMPro::get_assinaturas_para_cobrar( $user_id, self::DIAS_PRECOBRANCA ) ) ) {
            return true;
        }

        // Backstop pós-cobrança: pedido pago sem nota aprovada.
        if ( ! empty( Bebelume_NFSe_PMPro::get_pedidos_sem_nota_aprovada( $user_id ) ) ) {
            return true;
        }

        return false;
    }

    // =========================================================================
    // PROCESSAMENTO DO FORMULÁRIO
    // =========================================================================

    private function process_form( int $user_id ): void {
        if ( ! isset( $_POST['bbl_fiscal_nonce'] )
             || ! wp_verify_nonce( $_POST['bbl_fiscal_nonce'], 'bbl_fiscal_block_' . $user_id ) ) {
            $this->render_form( $user_id, [ 'Erro de segurança. Recarregue a página e tente novamente.' ], [] );
            exit;
        }

        $old = [
            'cpf'       => preg_replace( '/\D/', '', (string) ( $_POST['bbl_cpf'] ?? '' ) ),
            'telefone'  => sanitize_text_field( $_POST['bbl_telefone'] ?? '' ),
            'endereco'  => sanitize_text_field( $_POST['bbl_endereco'] ?? '' ),
            'bairro'    => sanitize_text_field( $_POST['bbl_bairro'] ?? '' ),
            'municipio' => sanitize_text_field( $_POST['bbl_municipio'] ?? '' ),
            'uf'        => strtoupper( sanitize_text_field( $_POST['bbl_uf'] ?? '' ) ),
            'cep'       => preg_replace( '/\D/', '', (string) ( $_POST['bbl_cep'] ?? '' ) ),
        ];

        $errors = [];

        if ( empty( $old['cpf'] ) ) {
            $errors[] = 'Informe seu CPF.';
        } elseif ( ! $this->validate_cpf( $old['cpf'] ) ) {
            $errors[] = 'CPF inválido.';
        }

        if ( empty( $old['telefone'] ) ) $errors[] = 'Informe seu telefone.';
        if ( empty( $old['endereco'] ) ) $errors[] = 'Informe seu endereço.';
        if ( empty( $old['municipio'] ) ) $errors[] = 'Informe seu município.';
        if ( strlen( $old['uf'] ) !== 2 )        $errors[] = 'Informe a UF (2 letras).';
        if ( strlen( $old['cep'] ) < 8 )         $errors[] = 'Informe um CEP válido (8 dígitos).';

        if ( ! empty( $errors ) ) {
            $this->render_form( $user_id, $errors, $old );
            exit;
        }

        $cpf_formatted = preg_replace( '/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $old['cpf'] );

        update_user_meta( $user_id, 'cpf',           $old['cpf'] );
        update_user_meta( $user_id, 'cpf_formatted', $cpf_formatted );
        update_user_meta( $user_id, 'pmpro_bphone',    $old['telefone'] );
        update_user_meta( $user_id, 'pmpro_baddress1', $old['endereco'] );
        update_user_meta( $user_id, 'pmpro_baddress2', $old['bairro'] );
        update_user_meta( $user_id, 'pmpro_bcity',     $old['municipio'] );
        update_user_meta( $user_id, 'pmpro_bstate',    $old['uf'] );
        update_user_meta( $user_id, 'pmpro_bzipcode',  $old['cep'] );

        delete_transient( 'bbl_fiscal_block_' . $user_id );

        // Reemite a NFS-e dos pedidos que já foram cobrados sem nota (backstop).
        // No caso só pré-cobrança não há o que reemitir — a nota sai no próximo
        // pmpro_subscription_payment_completed, pois os dados já estão completos.
        if ( class_exists( 'Bebelume_NFSe_PMPro' ) ) {
            $resultado = Bebelume_NFSe_PMPro::reemitir_apos_dados( $user_id );
            if ( ! empty( $resultado['erros'] ) ) {
                error_log( '[Bebelume Fiscal Block] Reemissão com erros: ' . implode( ' | ', $resultado['erros'] ) );
            }
        }

        wp_safe_redirect( home_url() );
        exit;
    }

    // =========================================================================
    // RENDERIZAÇÃO DA TELA
    // =========================================================================

    private function render_form( int $user_id, array $errors = [], array $old = [] ): void {
        $nonce = wp_create_nonce( 'bbl_fiscal_block_' . $user_id );

        // Link "Ir para minha conta": área de conta fica liberada com o bloqueio ativo.
        $account_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'account' ) : '';
        if ( empty( $account_url ) ) {
            $account_url = home_url( '/conta-de-associacao/' );
        }

        // Logo no topo do card — asset oficial da marca (upload do tema).
        $logo_base = wp_upload_dir()['baseurl'] ?? '';
        $logo_url  = $logo_base ? trailingslashit( $logo_base ) . '2026/07/logo@3x-2048x351-3.png' : '';
        $logo_img  = $logo_url
            ? sprintf( '<img src="%s" alt="%s">', esc_url( $logo_url ), esc_attr( get_bloginfo( 'name' ) ) )
            : '';

        // Fim do período gratuito = próxima cobrança da assinatura (quando houver).
        $trial_fim = $this->trial_end_label( $user_id );

        $value = function ( string $key ) use ( $user_id, $old ): string {
            if ( array_key_exists( $key, $old ) ) {
                return $old[ $key ];
            }
            $map = [
                'cpf'       => 'cpf',
                'telefone'  => 'pmpro_bphone',
                'endereco'  => 'pmpro_baddress1',
                'bairro'    => 'pmpro_baddress2',
                'municipio' => 'pmpro_bcity',
                'uf'        => 'pmpro_bstate',
                'cep'       => 'pmpro_bzipcode',
            ];
            $raw = get_user_meta( $user_id, $map[ $key ], true );
            if ( $key === 'cpf' && $raw ) {
                return preg_replace( '/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $raw );
            }
            return (string) $raw;
        };

        get_header();
        ?>
        <style>
        body, #page, .site, .site-content, #content, main, #main {
            background-color: #f5f4f1 !important;
        }
        .bbl-fiscal-wrap {
            display: flex;
            justify-content: center;
            padding: 48px 16px 80px;
        }
        .bbl-fiscal-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0,0,0,.07);
            max-width: 560px;
            width: 100%;
            padding: 40px 36px;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
            margin: 24px auto;
        }
        .bbl-fiscal-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 16px;
        }
        .bbl-fiscal-logo img {
            height: 60px;
            width: auto;
            object-fit: contain;
        }
        .bbl-fiscal-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 12px;
            line-height: 1.3;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
        }
        .bbl-fiscal-sub {
            font-size: 15px;
            color: #555555;
            line-height: 1.6;
            margin: 0 0 24px;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
        }
        .bbl-fiscal-errors {
            background: #fdecea;
            border: 1px solid #f5c6cb;
            color: #8a1f2d;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            line-height: 1.5;
            margin: 0 0 20px;
        }
        .bbl-fiscal-errors span { display: block; }
        .bbl-fiscal-field { margin-bottom: 16px; }
        .bbl-fiscal-field label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333333;
            margin-bottom: 6px;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
        }
        .bbl-fiscal-field .bbl-optional { font-weight: 400; color: #999999; font-size: 13px; }
        .bbl-fiscal-field input,
        .bbl-fiscal-field select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            font-size: 15px;
            color: #1a1a1a;
            background: #ffffff;
            border: 1px solid #d9d6d0;
            border-radius: 10px;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
        }
        .bbl-fiscal-field input:focus,
        .bbl-fiscal-field select:focus {
            outline: none;
            border-color: #eb2a61;
            box-shadow: 0 0 0 3px rgba(235,42,97,.12);
        }
        .bbl-fiscal-hint {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #888888;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
        }
        .bbl-fiscal-note {
            margin: 14px 0 4px;
            font-size: 13px;
            color: #666666;
            line-height: 1.5;
            text-align: center;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
        }
        .bbl-fiscal-foot {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid #efece7;
            text-align: center;
        }
        .bbl-fiscal-foot span {
            display: block;
            font-size: 12px;
            color: #999999;
            line-height: 1.5;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
        }
        .bbl-fiscal-row { display: flex; gap: 16px; }
        .bbl-fiscal-row .bbl-fiscal-field { flex: 1; }
        @media (max-width: 560px) {
            .bbl-fiscal-card { padding: 28px 20px; }
            .bbl-fiscal-row { flex-direction: column; gap: 0; }
        }
        .bbl-fiscal-btn {
            display: block;
            width: 100%;
            box-sizing: border-box;
            background: #eb2a61;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            text-decoration: none;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
            font-size: 15px;
            font-weight: 600;
            border-radius: 50px;
            height: 48px;
            line-height: 48px;
            text-align: center;
            margin-top: 8px;
            border: none;
            cursor: pointer;
            transition: background .15s;
        }
        .bbl-fiscal-btn:hover { background: #c41e50; }
        .bbl-fiscal-logout {
            display: block;
            text-align: center;
            margin-top: 14px;
            font-size: 13px;
            color: #888888;
            text-decoration: none;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
        }
        .bbl-fiscal-logout:hover { color: #eb2a61; }
        </style>

        <div class="bbl-fiscal-wrap">
            <div class="bbl-fiscal-card">

                <div class="bbl-fiscal-logo"><?php echo $logo_img; ?></div>

                <h2 class="bbl-fiscal-title">Falta só um passo para concluir seu cadastro</h2>
                <p class="bbl-fiscal-sub">
                    Para emitir a nota fiscal da sua assinatura, precisamos completar alguns dados.
                    Leva menos de um minuto e seu acesso gratuito continua normalmente.
                </p>

                <?php if ( ! empty( $errors ) ) : ?>
                    <div class="bbl-fiscal-errors">
                        <?php foreach ( $errors as $error ) : ?>
                            <span><?php echo esc_html( $error ); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url( home_url( '/' ) ); ?>" novalidate>

                    <div class="bbl-fiscal-field">
                        <label for="bbl_cep">CEP <span aria-hidden="true">*</span></label>
                        <input type="text" name="bbl_cep" id="bbl_cep"
                               value="<?php echo esc_attr( $value( 'cep' ) ); ?>"
                               placeholder="00000-000" maxlength="9"
                               inputmode="numeric" autocomplete="postal-code" required />
                        <span class="bbl-fiscal-hint" id="bbl_cep_hint">Digite o CEP e completamos o endereço para você.</span>
                    </div>

                    <div class="bbl-fiscal-field">
                        <label for="bbl_endereco">Endereço <span aria-hidden="true">*</span></label>
                        <input type="text" name="bbl_endereco" id="bbl_endereco"
                               value="<?php echo esc_attr( $value( 'endereco' ) ); ?>"
                               placeholder="Rua, número" autocomplete="street-address" required />
                        <span class="bbl-fiscal-hint">Endereço de cobrança, obrigatório na nota fiscal.</span>
                    </div>

                    <div class="bbl-fiscal-field">
                        <label for="bbl_bairro">Bairro <span class="bbl-optional">(opcional)</span></label>
                        <input type="text" name="bbl_bairro" id="bbl_bairro"
                               value="<?php echo esc_attr( $value( 'bairro' ) ); ?>" autocomplete="address-level3" />
                    </div>

                    <div class="bbl-fiscal-row">
                        <div class="bbl-fiscal-field">
                            <label for="bbl_municipio">Município <span aria-hidden="true">*</span></label>
                            <input type="text" name="bbl_municipio" id="bbl_municipio"
                                   value="<?php echo esc_attr( $value( 'municipio' ) ); ?>"
                                   autocomplete="address-level2" required />
                        </div>
                        <div class="bbl-fiscal-field">
                            <label for="bbl_uf">UF <span aria-hidden="true">*</span></label>
                            <select name="bbl_uf" id="bbl_uf" required>
                                <option value="">—</option>
                                <?php foreach ( $this->uf_list() as $sigla ) : ?>
                                    <option value="<?php echo esc_attr( $sigla ); ?>"
                                        <?php selected( $value( 'uf' ), $sigla ); ?>>
                                        <?php echo esc_html( $sigla ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="bbl-fiscal-field">
                        <label for="bbl_cpf">CPF <span aria-hidden="true">*</span></label>
                        <input type="text" name="bbl_cpf" id="bbl_cpf"
                               value="<?php echo esc_attr( $value( 'cpf' ) ); ?>"
                               placeholder="000.000.000-00" maxlength="14"
                               inputmode="numeric" autocomplete="off" required />
                        <span class="bbl-fiscal-hint">Usado apenas na emissão da nota fiscal.</span>
                    </div>

                    <div class="bbl-fiscal-field">
                        <label for="bbl_telefone">Telefone <span aria-hidden="true">*</span></label>
                        <input type="tel" name="bbl_telefone" id="bbl_telefone"
                               value="<?php echo esc_attr( $value( 'telefone' ) ); ?>"
                               placeholder="(00) 00000-0000" autocomplete="tel" required />
                        <span class="bbl-fiscal-hint">Só usamos para avisos importantes sobre sua conta.</span>
                    </div>

                    <input type="hidden" name="bbl_fiscal_nonce" value="<?php echo esc_attr( $nonce ); ?>" />

                    <button type="submit" class="bbl-fiscal-btn">Salvar e continuar</button>

                    <p class="bbl-fiscal-note">
                        Nenhuma cobrança é feita agora. Você pode cancelar quando quiser pela sua conta.
                    </p>

                    <a class="bbl-fiscal-logout" href="<?php echo esc_url( $account_url ); ?>">Fazer isso depois</a>

                    <div class="bbl-fiscal-foot">
                        <?php if ( $trial_fim ) : ?>
                            <span>Seu período gratuito vai até <?php echo esc_html( $trial_fim ); ?></span>
                        <?php endif; ?>
                        <span>Seus dados são usados só para emissão fiscal e não são compartilhados.</span>
                    </div>

                </form>
            </div>
        </div>

        <script>
        (function () {
            function maskCpf(input) {
                var v = input.value.replace(/\D/g, '').substring(0, 11);
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                input.value = v;
            }
            function maskCep(input) {
                var v = input.value.replace(/\D/g, '').substring(0, 8);
                v = v.replace(/(\d{5})(\d)/, '$1-$2');
                input.value = v;
            }
            function maskTel(input) {
                var d = input.value.replace(/\D/g, '').substring(0, 11);
                if (d.length <= 2) { input.value = d ? '(' + d : ''; return; }
                var out = '(' + d.slice(0, 2) + ') ';
                var rest = d.slice(2);
                if (d.length > 10) {
                    out += rest.slice(0, 5) + '-' + rest.slice(5);
                } else if (d.length > 6) {
                    out += rest.slice(0, 4) + '-' + rest.slice(4);
                } else {
                    out += rest;
                }
                input.value = out;
            }
            var cpf = document.getElementById('bbl_cpf');
            var cep = document.getElementById('bbl_cep');
            var tel = document.getElementById('bbl_telefone');
            if (cpf) cpf.addEventListener('input', function () { maskCpf(this); });
            if (cep) cep.addEventListener('input', function () { maskCep(this); });
            if (tel) {
                maskTel(tel); // formata valor inicial (re-render pós-falha)
                tel.addEventListener('input', function () { maskTel(this); });
            }

            // Autofill de endereço pelo CEP (viacep) — mesma regra do checkout:
            // dispara quando os 8 dígitos estão presentes; feedback no hint.
            var cepHint = document.getElementById('bbl_cep_hint');
            if (cep) cep.addEventListener('input', function () {
                var d = this.value.replace(/\D/g, '');
                if (d.length !== 8) {
                    if (cepHint) cepHint.textContent = 'Digite o CEP e completamos o endereço para você.';
                    return;
                }
                if (cepHint) cepHint.textContent = 'Buscando endereço…';
                fetch('https://viacep.com.br/ws/' + d + '/json/')
                    .then(function (r) { return r.json(); })
                    .then(function (r) {
                        var end = document.getElementById('bbl_endereco');
                        var bai = document.getElementById('bbl_bairro');
                        var mun = document.getElementById('bbl_municipio');
                        var uf  = document.getElementById('bbl_uf');
                        if (!r || r.erro || !r.localidade) {
                            if (cepHint) cepHint.textContent = 'CEP não encontrado. Confira o número digitado.';
                            return;
                        }
                        if (end) end.value = r.logradouro || '';
                        if (bai) bai.value = r.bairro || '';
                        if (mun) mun.value = r.localidade || '';
                        if (uf) uf.value = (r.uf || '').toUpperCase();
                        if (cepHint) cepHint.textContent = 'Endereço preenchido automaticamente.';
                    })
                    .catch(function () {
                        if (cepHint) cepHint.textContent = 'Não foi possível buscar o endereço. Preencha manualmente.';
                    });
            });
        })();
        </script>
        <?php
        get_footer();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function validate_cpf( string $cpf ): bool {
        if ( strlen( $cpf ) !== 11 || preg_match( '/(\d)\1{10}/', $cpf ) ) {
            return false;
        }
        for ( $t = 9; $t < 11; $t++ ) {
            $sum = 0;
            for ( $i = 0; $i < $t; $i++ ) {
                $sum += (int) $cpf[ $i ] * ( $t + 1 - $i );
            }
            $digit = ( ( 10 * $sum ) % 11 ) % 10;
            if ( (int) $cpf[ $t ] !== $digit ) {
                return false;
            }
        }
        return true;
    }

    private function uf_list(): array {
        return [
            'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG',
            'PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO',
        ];
    }

    /**
     * Fim do período gratuito (próxima cobrança mais próxima entre as assinaturas
     * que serão cobradas em ≤ DIAS_PRECOBRANCA), no fuso do site. Vazio se não houver.
     */
    private function trial_end_label( int $user_id ): string {
        if ( ! class_exists( 'Bebelume_NFSe_PMPro' ) ) {
            return '';
        }

        $subs = Bebelume_NFSe_PMPro::get_assinaturas_para_cobrar( $user_id, self::DIAS_PRECOBRANCA );
        $min  = '';
        foreach ( $subs as $s ) {
            $d = isset( $s->next_payment_date ) ? (string) $s->next_payment_date : '';
            if ( $d === '' || $d === '0000-00-00 00:00:00' ) {
                continue;
            }
            if ( $min === '' || $d < $min ) {
                $min = $d;
            }
        }
        if ( $min === '' ) {
            return '';
        }

        try {
            $dt = ( new DateTimeImmutable( $min . ' UTC' ) )->setTimezone( wp_timezone() );
            return $dt->format( 'd/m/Y \à\s H:i' );
        } catch ( \Throwable $e ) {
            return '';
        }
    }

    /**
     * Exposição p/ avisos na área de conta: espelha a regra do bloqueio
     * (flag + classe NFS-e + cache de 5min) sem interceptar a navegação.
     */
    public static function is_block_active( int $user_id ): bool {
        if ( ! defined( 'BBL_FISCAL_BLOCK' ) || ! BBL_FISCAL_BLOCK ) {
            return false;
        }
        if ( ! class_exists( 'Bebelume_NFSe_PMPro' ) ) {
            return false;
        }
        return self::get_instance()->block_required( $user_id );
    }

    private function is_account_route(): bool {
        $ids = array_filter( array_map( 'intval', [
            pmpro_getOption( 'account_page_id' ),
            pmpro_getOption( 'cancel_page_id' ),
            pmpro_getOption( 'invoice_page_id' ),
            pmpro_getOption( 'member_profile_edit_page_id' ),
            pmpro_getOption( 'billing_page_id' ), // desativada p/ redirect, inofensivo
        ] ) );
        return ! empty( $ids ) && is_page( $ids );
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Fiscal_Block::get_instance();
} );
