<?php
/**
 * BBL_Checkout_Fields — Campos de Nome e Sobrenome no checkout
 *
 * Nome e Sobrenome são coletados no cadastro (novo usuário). Na REASSINATURA
 * (retornante), o checkout coleta CPF + endereço num card "Dados fiscais"
 * obrigatório, para emissão da NFS-e no ato da cobrança. Novo cadastro (primeira
 * assinatura) NÃO vê esses campos — os dados entram depois (complete profile /
 * tela de dados fiscais).
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BBL_Checkout_Fields {

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
        // Nome e Sobrenome (só para novos usuários)
        add_action( 'pmpro_checkout_after_username',    [ $this, 'add_name_fields' ] );
        add_action( 'pmpro_after_checkout',             [ $this, 'save_name_fields' ], 10, 2 );
        add_filter( 'pmpro_registration_checks',        [ $this, 'validate_name_fields' ] );

        // Cache antes do redirect Stripe / restore após retorno
        add_filter( 'pmpro_registration_checks',        [ $this, 'cache_fields_before_stripe_redirect' ], 99 );
        add_action( 'pmpro_after_checkout',             [ $this, 'restore_fields_from_cache' ], 5, 2 );

        // Sincroniza com campos nativos PMPro
        add_action( 'pmpro_after_checkout',             [ $this, 'sync_billing_fields' ], 20, 2 );

        // Dados fiscais na reassinatura (CPF + endereço) — card antes do Pagamento.
        // Só retornantes (já tiveram sub ou order) veem; novo cadastro não.
        add_action( 'pmpro_checkout_after_billing_fields', [ $this, 'add_fiscal_card' ] );
        add_filter( 'pmpro_hide_billing_address_fields',   [ $this, 'hide_native_billing_for_retornante' ] );
        add_action( 'wp_ajax_bbl_check_retornante',        [ $this, 'ajax_check_retornante' ] );
        add_action( 'wp_ajax_nopriv_bbl_check_retornante', [ $this, 'ajax_check_retornante' ] );
        add_filter( 'pmpro_registration_checks',           [ $this, 'validate_fiscal_fields' ] );
        add_action( 'pmpro_after_checkout',                [ $this, 'save_fiscal_fields' ], 12, 2 );
    }

    // ── Nome e Sobrenome ──────────────────────────────────────────────────────

    public function add_name_fields(): void {
        if ( is_user_logged_in() ) return;
        $first = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last  = sanitize_text_field( $_POST['last_name']  ?? '' );
        ?>
        <div class="pmpro_form_field pmpro_form_field-text pmpro_form_field-first_name">
            <label for="first_name" class="pmpro_form_label">
                Nome <span class="pmpro_asterisk"><abbr title="Campo obrigatório">*</abbr></span>
            </label>
            <input id="first_name" name="first_name" type="text"
                   class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required"
                   value="<?php echo esc_attr( $first ); ?>" required>
        </div>
        <div class="pmpro_form_field pmpro_form_field-text pmpro_form_field-last_name">
            <label for="last_name" class="pmpro_form_label">
                Sobrenome <span class="pmpro_asterisk"><abbr title="Campo obrigatório">*</abbr></span>
            </label>
            <input id="last_name" name="last_name" type="text"
                   class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required"
                   value="<?php echo esc_attr( $last ); ?>" required>
        </div>
        <?php
    }

    public function validate_name_fields( bool $ok ): bool {
        if ( is_user_logged_in() ) return $ok;
        if ( empty( $_POST['first_name'] ) ) { pmpro_setMessage( 'O campo Nome é obrigatório.', 'pmpro_error' ); $ok = false; }
        if ( empty( $_POST['last_name'] ) )  { pmpro_setMessage( 'O campo Sobrenome é obrigatório.', 'pmpro_error' ); $ok = false; }
        return $ok;
    }

    public function save_name_fields( int $user_id, $order ): void {
        if ( ! empty( $_POST['first_name'] ) ) update_user_meta( $user_id, 'first_name', sanitize_text_field( $_POST['first_name'] ) );
        if ( ! empty( $_POST['last_name'] ) )  update_user_meta( $user_id, 'last_name',  sanitize_text_field( $_POST['last_name'] ) );
    }

    public function cache_fields_before_stripe_redirect( bool $ok ): bool {
        if ( ! $ok ) return $ok;
        $email = sanitize_email( $_POST['bemail'] ?? $_POST['username'] ?? '' );
        if ( empty( $email ) && is_user_logged_in() ) $email = wp_get_current_user()->user_email;
        if ( empty( $email ) ) return $ok;
        $cache = [
            'first_name' => sanitize_text_field( $_POST['first_name'] ?? '' ),
            'last_name'  => sanitize_text_field( $_POST['last_name']  ?? '' ),
        ];
        foreach ( $this->fiscal_field_keys() as $key ) {
            $cache[ $key ] = sanitize_text_field( $_POST[ $key ] ?? '' );
        }
        set_transient( 'bbl_fields_' . md5( $email ), $cache, HOUR_IN_SECONDS * 2 );
        return $ok;
    }

    public function restore_fields_from_cache( int $user_id, $order ): void {
        if ( ! empty( $_POST['first_name'] ) || ! empty( $_POST['last_name'] ) ) return;
        $user = get_userdata( $user_id );
        if ( ! $user ) return;
        $data = get_transient( 'bbl_fields_' . md5( $user->user_email ) );
        if ( empty( $data ) ) return;
        foreach ( $data as $key => $value ) { if ( ! empty( $value ) ) $_POST[ $key ] = $value; }
        delete_transient( 'bbl_fields_' . md5( $user->user_email ) );
    }

    public function sync_billing_fields( int $user_id, $order ): void {
        $first = get_user_meta( $user_id, 'first_name', true );
        $last  = get_user_meta( $user_id, 'last_name',  true );
        if ( $first ) update_user_meta( $user_id, 'pmpro_bfirstname', $first );
        if ( $last )  update_user_meta( $user_id, 'pmpro_blastname',  $last );
        $user = get_userdata( $user_id );
        if ( $user ) update_user_meta( $user_id, 'pmpro_bemail', $user->user_email );
    }

    // ── Dados fiscais na reassinatura ─────────────────────────────────────────

    /**
     * Card "Dados fiscais" no checkout, posicionado antes do card de pagamento
     * (hook pmpro_checkout_after_billing_fields). Renderiza o formulário sempre,
     * mas fica oculto (display:none) para quem NÃO é retornante — se o e-mail
     * digitado pertencer a um retornante, o JS revela o card.
     * Para retornante logado os campos são obrigatórios.
     */
    public function add_fiscal_card(): void {
        if ( ! function_exists( 'pmpro_is_checkout' ) || ! pmpro_is_checkout() ) {
            return;
        }

        $show_now = false;
        if ( is_user_logged_in() ) {
            $show_now = $this->user_jah_assinou( get_current_user_id(), '' );
        }

        $v = $this->fiscal_field_values();

        $label_required = '<span class="pmpro_asterisk"><abbr title="Campo obrigatório">*</abbr></span>';

        // Só os campos ficam obrigatórios quando o card está visível (retornante). Se o card
        // estiver oculto (novo usuário) e os inputs seguissem `required`, o browser bloquearia
        // o submit ("invalid form control is not focusable"). No caso de visitante retornante
        // revelado via JS, o showFiscal() reaplica o required.
        $req = $show_now ? ' required' : '';

        // Reassinatura cobra no ato (sem novo trial): o nível global já vem filtrado por
        // pmpro_checkout_level p/ retornante logado; o fallback p/ billing_amount cobre o
        // visitante retornante revelado via AJAX (nível ainda no preço de trial no GET).
        global $pmpro_level;
        $valor_hoje_txt = '';
        if ( ! empty( $pmpro_level ) ) {
            $valor_hoje = (float) $pmpro_level->initial_payment;
            if ( $valor_hoje <= 0 ) {
                $valor_hoje = (float) $pmpro_level->billing_amount;
            }
            if ( $valor_hoje > 0 ) {
                $valor_hoje_txt = number_format( $valor_hoje, 2, ',', '.' );
            }
        }
        ?>
        <fieldset id="pmpro_fiscal_fields" class="pmpro_form_fieldset pmpro_fiscal_fields"
                  data-show="<?php echo $show_now ? '1' : '0'; ?>"
                  <?php echo $show_now ? '' : 'style="display:none;"'; ?>>
            <div class="bbl-reassa-note" role="alert">
                <span class="bbl-reassa-note__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <div class="bbl-reassa-note__body">
                    <p class="bbl-reassa-note__title">Você já usou seu período grátis</p>
                    <p class="bbl-reassa-note__text">O período gratuito vale uma única vez por conta. Nesta reativação <strong>não há novo período de teste</strong> — a primeira cobrança é feita hoje, na hora.</p>
                    <?php if ( '' !== $valor_hoje_txt ) : ?>
                        <p class="bbl-reassa-note__charge"><span class="bbl-reassa-note__charge-label">Cobrança hoje</span><strong>R$ <?php echo esc_html( $valor_hoje_txt ); ?></strong></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pmpro_card">
                <div class="pmpro_card_content">
                    <legend class="pmpro_form_legend">
                        <h2 class="pmpro_form_heading pmpro_font-large">
                            <span style="display:inline-flex;align-items:center;color:#eb2a61;margin-right:8px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            </span>
                            Dados fiscais
                        </h2>
                    </legend>
                    <p class="pmpro_form_hint" style="margin:-4px 0 16px;font-size:.9em;line-height:1.5;color:#666;">
                        Precisamos do seu CPF e endereço para emitir a <strong>nota fiscal</strong>.
                        Confirme os dados abaixo.
                    </p>
                    <div class="pmpro_form_fields">
                        <div class="pmpro_form_field pmpro_form_field-text pmpro_form_field-bbl_cpf">
                            <label for="bbl_cpf" class="pmpro_form_label">CPF <?php echo $label_required; ?></label>
                            <input id="bbl_cpf" name="bbl_cpf" type="text" inputmode="numeric" maxlength="14"
                                   placeholder="000.000.000-00" autocomplete="off"
                                   class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required"
                                   value="<?php echo esc_attr( $v['cpf'] ); ?>"<?php echo $req; ?> />
                        </div>
                        <div class="pmpro_form_field pmpro_form_field-text pmpro_form_field-bbl_telefone">
                            <label for="bbl_telefone" class="pmpro_form_label">Telefone / WhatsApp <?php echo $label_required; ?></label>
                            <input id="bbl_telefone" name="bbl_telefone" type="tel" autocomplete="tel"
                                   placeholder="(00) 00000-0000"
                                   class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required"
                                   value="<?php echo esc_attr( $v['telefone'] ); ?>"<?php echo $req; ?> />
                        </div>
                        <div class="pmpro_form_field pmpro_form_field-text pmpro_form_field-bbl_cep">
                            <label for="bbl_cep" class="pmpro_form_label">CEP <?php echo $label_required; ?></label>
                            <input id="bbl_cep" name="bbl_cep" type="text" inputmode="numeric" maxlength="9"
                                   placeholder="00000-000" autocomplete="postal-code"
                                   class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required"
                                   value="<?php echo esc_attr( $v['cep'] ); ?>"<?php echo $req; ?> />
                            <small class="pmpro_form_hint" id="bbl_cep_hint" style="display:block;margin-top:4px;">
                                Digite o CEP e o endereço é preenchido automaticamente.
                            </small>
                        </div>
                        <div class="pmpro_form_field pmpro_form_field-text pmpro_form_field-bbl_endereco">
                            <label for="bbl_endereco" class="pmpro_form_label">Endereço <?php echo $label_required; ?></label>
                            <input id="bbl_endereco" name="bbl_endereco" type="text" autocomplete="street-address"
                                   placeholder="Rua, número"
                                   class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required"
                                   value="<?php echo esc_attr( $v['endereco'] ); ?>"<?php echo $req; ?> />
                        </div>
                        <div class="pmpro_form_field pmpro_form_field-text pmpro_form_field-bbl_bairro">
                            <label for="bbl_bairro" class="pmpro_form_label">Bairro <span class="pmpro_form_hint-inline">(opcional)</span></label>
                            <input id="bbl_bairro" name="bbl_bairro" type="text"
                                   class="pmpro_form_input pmpro_form_input-text"
                                   value="<?php echo esc_attr( $v['bairro'] ); ?>" />
                        </div>
                        <div class="pmpro_form_fields pmpro_cols-2">
                            <div class="pmpro_form_field pmpro_form_field-text pmpro_form_field-bbl_municipio">
                                <label for="bbl_municipio" class="pmpro_form_label">Município <?php echo $label_required; ?></label>
                                <input id="bbl_municipio" name="bbl_municipio" type="text"
                                       class="pmpro_form_input pmpro_form_input-text pmpro_form_input-required"
                                       value="<?php echo esc_attr( $v['municipio'] ); ?>"<?php echo $req; ?> />
                            </div>
                            <div class="pmpro_form_field pmpro_form_field-select pmpro_form_field-bbl_uf">
                                <label for="bbl_uf" class="pmpro_form_label">UF <?php echo $label_required; ?></label>
                                <select id="bbl_uf" name="bbl_uf"
                                        class="pmpro_form_input pmpro_form_input-select pmpro_form_input-required"<?php echo $req; ?>>
                                    <option value="">—</option>
                                    <?php foreach ( $this->uf_list() as $sigla ) : ?>
                                        <option value="<?php echo esc_attr( $sigla ); ?>" <?php selected( $v['uf'], $sigla ); ?>><?php echo esc_html( $sigla ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
        <?php
        $this->render_fiscal_card_js( $show_now );
    }

    private function render_fiscal_card_js( bool $show_now ): void {
        ?>
        <script>
        (function () {
            var fieldset = document.getElementById('pmpro_fiscal_fields');
            if (!fieldset) return;

            function mask(v, type) {
                var d = v.replace(/\D/g, '');
                if (type === 'cpf') {
                    d = d.substring(0, 11);
                    d = d.replace(/(\d{3})(\d)/, '$1.$2');
                    d = d.replace(/(\d{3})(\d)/, '$1.$2');
                    d = d.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                    return d;
                }
                if (type === 'cep') {
                    d = d.substring(0, 8);
                    return d.replace(/(\d{5})(\d)/, '$1-$2');
                }
                if (type === 'tel') {
                    d = d.substring(0, 11);
                    if (d.length <= 2) return d ? '(' + d : '';
                    var out = '(' + d.slice(0, 2) + ') ';
                    var rest = d.slice(2);
                    if (d.length > 10) {
                        out += rest.slice(0, 5) + '-' + rest.slice(5);
                    } else if (d.length > 6) {
                        out += rest.slice(0, 4) + '-' + rest.slice(4);
                    } else {
                        out += rest;
                    }
                    return out;
                }
                return v;
            }
            function bindMask(id, type) {
                var el = document.getElementById(id);
                if (!el) return;
                el.value = mask(el.value, type); // formata valor inicial (prefill)
                el.addEventListener('input', function () { this.value = mask(this.value, type); });
            }
            bindMask('bbl_cpf', 'cpf');
            bindMask('bbl_cep', 'cep');
            bindMask('bbl_telefone', 'tel');

            function hideNativeBilling() {
                var nat = document.getElementById('pmpro_billing_address_fields');
                if (nat) nat.style.display = 'none';
            }
            // Retornante logado tem required no markup (servidor). Visitante retornante é revelado
            // pelo AJAX: aqui o required é reaplicado para o browser validar antes do submit.
            function setFiscalRequired(req) {
                ['bbl_cpf', 'bbl_telefone', 'bbl_cep', 'bbl_endereco', 'bbl_municipio'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) el.required = req;
                });
                var uf = document.getElementById('bbl_uf');
                if (uf) uf.required = req;
            }
            function showFiscal() {
                fieldset.style.display = 'block';
                hideNativeBilling();
                setFiscalRequired(true);
            }

            // Autofill de endereço pelo CEP (viacep) — dispara quando os 8 dígitos
            // são digitados; feedback de status no hint abaixo do campo CEP.
            var cep = document.getElementById('bbl_cep');
            var cepHint = document.getElementById('bbl_cep_hint');
            if (cep) cep.addEventListener('input', function () {
                var d = this.value.replace(/\D/g, '');
                if (d.length !== 8) {
                    if (cepHint) cepHint.textContent = 'Digite o CEP e o endereço é preenchido automaticamente.';
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

            // Retornante logado: card já visível; esconde o endereço nativo do PMPro.
            if (fieldset.dataset.show === '1') { hideNativeBilling(); return; }

            // Visitante: revela o card quando o e-mail digitado já é de retornante.
            var emailInput = document.getElementById('bemail');
            if (!emailInput) return;
            var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
            var nonce   = <?php echo wp_json_encode( wp_create_nonce( 'bbl_check_retornante' ) ); ?>;
            var timer;
            function checkRetornante() {
                var email = (emailInput.value || '').trim();
                if (!email) return;
                fetch(ajaxUrl + '?action=bbl_check_retornante&_wpnonce=' + nonce + '&email=' + encodeURIComponent(email))
                    .then(function (r) { return r.json(); })
                    .then(function (d) { if (d && d.retornante) showFiscal(); })
                    .catch(function () {});
            }
            emailInput.addEventListener('blur', function () {
                clearTimeout(timer);
                timer = setTimeout(checkRetornante, 300);
            });
        })();
        </script>
        <?php
    }

    /**
     * Retornante logado já tem (ou terá) o card de dados fiscais com os campos de
     * endereço — o endereço de cobrança nativo do PMPro fica oculto para não haver
     * dois formulários de endereço na reassinatura.
     */
    public function hide_native_billing_for_retornante( bool $hide ): bool {
        if ( is_user_logged_in() && $this->user_jah_assinou( get_current_user_id(), '' ) ) {
            return true;
        }
        return $hide;
    }

    /**
     * Valores exibidos (POST primeiro, depois user_meta) — espelha as metas
     * usadas pela NFS-e / tela de dados fiscais.
     */
    private function fiscal_field_values(): array {
        $uid = get_current_user_id();
        $map = [
            'cpf'       => 'cpf',
            'telefone'  => 'pmpro_bphone',
            'endereco'  => 'pmpro_baddress1',
            'bairro'    => 'pmpro_baddress2',
            'municipio' => 'pmpro_bcity',
            'uf'        => 'pmpro_bstate',
            'cep'       => 'pmpro_bzipcode',
        ];
        $out = [];
        foreach ( $map as $field => $meta ) {
            $post = 'bbl_' . $field;
            $val  = sanitize_text_field( $_POST[ $post ] ?? '' );
            if ( '' === $val && $uid ) {
                $val = (string) get_user_meta( $uid, $meta, true );
            }
            if ( 'cpf' === $field && $val ) {
                $val = preg_replace( '/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', substr( preg_replace( '/\D/', '', $val ), 0, 11 ) );
            } elseif ( 'cep' === $field && $val ) {
                $val = preg_replace( '/(\d{5})(\d)/', '$1-$2', substr( preg_replace( '/\D/', '', $val ), 0, 8 ) );
            } elseif ( 'uf' === $field ) {
                $val = strtoupper( $val );
            }
            $out[ $field ] = $val;
        }
        return $out;
    }

    private function fiscal_field_keys(): array {
        return [
            'bbl_cpf', 'bbl_telefone', 'bbl_endereco', 'bbl_bairro',
            'bbl_municipio', 'bbl_uf', 'bbl_cep',
        ];
    }

    public function ajax_check_retornante(): void {
        check_ajax_referer( 'bbl_check_retornante' );
        $email = sanitize_email( $_GET['email'] ?? '' );
        if ( empty( $email ) ) {
            wp_send_json_success( [ 'retornante' => false ] );
        }
        wp_send_json_success( [ 'retornante' => $this->user_jah_assinou( 0, $email ) ] );
    }

    public function validate_fiscal_fields( bool $ok ): bool {
        $uid = get_current_user_id();
        if ( $uid ) {
            $retornante = $this->user_jah_assinou( $uid, '' );
        } else {
            $email = sanitize_email( $_POST['bemail'] ?? '' );
            if ( empty( $email ) ) {
                $email = sanitize_text_field( $_POST['username'] ?? '' );
            }
            $retornante = $this->user_jah_assinou( 0, $email );
        }

        // Primeira assinatura: os campos nem aparecem — nada a validar aqui.
        if ( ! $retornante ) {
            return $ok;
        }

        $cpf = preg_replace( '/\D/', '', (string) ( $_POST['bbl_cpf'] ?? '' ) );
        $uf  = strtoupper( sanitize_text_field( $_POST['bbl_uf'] ?? '' ) );
        $cep = preg_replace( '/\D/', '', (string) ( $_POST['bbl_cep'] ?? '' ) );

        $erros = [];
        if ( empty( $cpf ) ) {
            $erros[] = 'O CPF é obrigatório para reativar a assinatura.';
        } elseif ( ! $this->validate_cpf( $cpf ) ) {
            $erros[] = 'O CPF informado é inválido.';
        }
        if ( empty( $_POST['bbl_telefone'] ) ) $erros[] = 'Informe seu telefone.';
        if ( empty( $_POST['bbl_endereco'] ) ) $erros[] = 'Informe seu endereço.';
        if ( empty( $_POST['bbl_municipio'] ) ) $erros[] = 'Informe seu município.';
        if ( strlen( $uf ) !== 2 )              $erros[] = 'Selecione a UF.';
        if ( strlen( $cep ) < 8 )               $erros[] = 'Informe um CEP válido (8 dígitos).';

        if ( ! empty( $erros ) ) {
            pmpro_setMessage( implode( ' ', $erros ), 'pmpro_error' );
            return false;
        }
        return $ok;
    }

    public function save_fiscal_fields( int $user_id, $order ): void {
        $cpf = preg_replace( '/\D/', '', (string) ( $_POST['bbl_cpf'] ?? '' ) );
        if ( empty( $cpf ) || ! $this->validate_cpf( $cpf ) ) {
            return;
        }
        $cpf_formatted = preg_replace( '/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf );

        update_user_meta( $user_id, 'cpf',           $cpf );
        update_user_meta( $user_id, 'cpf_formatted', $cpf_formatted );
        update_user_meta( $user_id, 'pmpro_bphone',    sanitize_text_field( $_POST['bbl_telefone'] ?? '' ) );
        update_user_meta( $user_id, 'pmpro_baddress1', sanitize_text_field( $_POST['bbl_endereco'] ?? '' ) );
        update_user_meta( $user_id, 'pmpro_baddress2', sanitize_text_field( $_POST['bbl_bairro'] ?? '' ) );
        update_user_meta( $user_id, 'pmpro_bcity',     sanitize_text_field( $_POST['bbl_municipio'] ?? '' ) );
        update_user_meta( $user_id, 'pmpro_bstate',    strtoupper( sanitize_text_field( $_POST['bbl_uf'] ?? '' ) ) );
        update_user_meta( $user_id, 'pmpro_bzipcode',  preg_replace( '/\D/', '', (string) ( $_POST['bbl_cep'] ?? '' ) ) );

        // Dados completos: garante que a tela bloqueante de dados fiscais não trave.
        delete_transient( 'bbl_fiscal_block_' . $user_id );
    }

    private function uf_list(): array {
        return [
            'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG',
            'PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO',
        ];
    }

    /**
     * Já teve subscription (qualquer status) ou order success?
     * Chave = e-mail/conta (decisão Leo/Clarisse — ago/2026).
     */
    private function user_jah_assinou( int $user_id, string $email ): bool {
        global $wpdb;

        $check = function ( int $uid ) use ( $wpdb ): bool {
            $subs = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_subscriptions WHERE user_id = %d", $uid
            ) );
            if ( $subs > 0 ) {
                return true;
            }
            $orders = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_membership_orders WHERE user_id = %d AND status = 'success'", $uid
            ) );
            return $orders > 0;
        };

        if ( $user_id && $check( $user_id ) ) {
            return true;
        }

        if ( ! empty( $email ) ) {
            $user = get_user_by( 'email', $email );
            if ( ! empty( $user ) && $check( (int) $user->ID ) ) {
                return true;
            }
        }

        return false;
    }

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
}

add_action( 'plugins_loaded', function() {
    BBL_Checkout_Fields::get_instance();
} );
