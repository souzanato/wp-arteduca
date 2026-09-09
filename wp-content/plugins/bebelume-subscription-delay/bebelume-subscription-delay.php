<?php
/**
 * Plugin Name: Bebelume - Subscription Delay DEBUG
 * Description: VERSÃO DEBUG - Mostra o que está sendo enviado ao Stripe
 * Version: 4.1.0 DEBUG
 * Author: Bebelume
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bebelume_Subscription_Delay_Debug {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('pmpro_membership_level_after_other_settings', array($this, 'add_delay_field'));
        add_action('pmpro_save_membership_level', array($this, 'save_delay_field'));

        // PRIORIDADE ALTA para executar DEPOIS de todos os outros
        add_filter('pmpro_stripe_checkout_session_parameters', array($this, 'modify_and_debug_stripe_params'), 999, 3);

        add_filter('pmpro_get_membership_level', array($this, 'add_delay_to_level'), 10, 2);

        // Retornante cobra na contratação. Prioridade 20: roda DEPOIS do override de preço
        // (priority 10), quando billing_amount já está com o valor final.
        add_filter('pmpro_checkout_level', array($this, 'charge_returning_on_signup'), 20, 1);
    }

    public function add_delay_field($level) {
        $delay_days = get_option('pmpro_level_' . $level->id . '_delay_days', '');
        ?>
        <h3 class="topborder">Configurações de Trial (Bebelume DEBUG)</h3>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row" valign="top">
                        <label for="bebelume_delay_days">Trial Grátis (dias)</label>
                    </th>
                    <td>
                        <input type="number"
                               id="bebelume_delay_days"
                               name="bebelume_delay_days"
                               value="<?php echo esc_attr($delay_days); ?>"
                               min="0"
                               max="730"
                               step="1"
                               style="width: 100px;">
                        <p class="description">
                            <strong>VERSÃO DEBUG:</strong> Para registrar no log o que está sendo enviado ao Stripe,
                            adicione <code>define('BBL_SUB_DELAY_DEBUG', true);</code> ao <code>wp-config.php</code>.<br>
                            Configure aqui: <strong>30 dias</strong><br>
                            Depois faça checkout e veja o arquivo: <code>/wp-content/debug.log</code>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function save_delay_field($level_id) {
        if (isset($_POST['bebelume_delay_days'])) {
            $delay_days = intval($_POST['bebelume_delay_days']);
            if ($delay_days > 730) {
                $delay_days = 730;
            }
            update_option('pmpro_level_' . $level_id . '_delay_days', $delay_days);
        }
    }

    public function add_delay_to_level($level, $level_id) {
        if (!empty($level) && !empty($level_id)) {
            $delay_days = get_option('pmpro_level_' . $level_id . '_delay_days', 0);
            $level->subscription_delay = intval($delay_days);
        }
        return $level;
    }

    /**
     * Retornante cobra na contratação: define initial_payment = billing_amount para o gateway
     * Stripe entrar no caminho 'combine' (1ª cobrança recorrente imediata, sem trial).
     * Novo usuário mantém initial_payment = 0 → trial padrão do gateway (30 dias).
     * Prioridade 20 (depois do override de preço, que roda em 10).
     */
    public function charge_returning_on_signup($level) {
        if (empty($level) || empty($level->id)) {
            return $level;
        }

        if (!$this->is_returning_at_checkout()) {
            return $level;
        }

        if (!empty($level->billing_amount)) {
            $level->initial_payment = (float) $level->billing_amount;
        }
        if (isset($level->trial_amount)) {
            $level->trial_amount = 0;
        }
        if (isset($level->trial_limit)) {
            $level->trial_limit = 0;
        }

        return $level;
    }

    /**
     * O usuário do checkout já assinou antes (qualquer subscription ou order success)?
     * Identidade confiável neste ponto do fluxo: usuário logado e/ou e-mail do POST (bemail).
     */
    private function is_returning_at_checkout(): bool {
        $user_id = get_current_user_id();
        if (!empty($user_id) && $this->user_jah_assinou((int) $user_id, '')) {
            return true;
        }

        $email = '';
        if (!empty($_POST['bemail'])) {
            $email = sanitize_email(wp_unslash($_POST['bemail']));
        }
        if (!empty($email) && $this->user_jah_assinou(0, $email)) {
            return true;
        }

        return false;
    }

    // Só escreve no log se o debug estiver ligado explicitamente via wp-config.
    private function log($message) {
        if (defined('BBL_SUB_DELAY_DEBUG') && BBL_SUB_DELAY_DEBUG) {
            error_log('[bbl-sub-delay] ' . $message);
        }
    }

    // Mascara valores de chaves com PII/identificadores antes de dump em log.
    private function redact_sensitive($data) {
        if (!is_array($data)) {
            return $data;
        }

        $sensitive_keys = array(
            'customer',
            'customer_email',
            'client_reference_id',
            'email',
            'receipt_email',
        );

        $result = array();
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $sensitive_keys, true)) {
                $result[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $result[$key] = $this->redact_sensitive($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Já teve subscription (qualquer status) ou order success?
     * Chave anti-trial = e-mail/conta (decisão Leo/Clarisse — ago/2026).
     */
    private function user_jah_assinou($user_id, $email): bool {
        global $wpdb;

        $check = function (int $uid) use ($wpdb): bool {
            $subs = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_subscriptions WHERE user_id = %d", $uid
            ));
            if ($subs > 0) {
                return true;
            }
            $orders = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_membership_orders WHERE user_id = %d AND status = 'success'", $uid
            ));
            return $orders > 0;
        };

        if (!empty($user_id) && $check((int) $user_id)) {
            return true;
        }

        if (!empty($email)) {
            $user = get_user_by('email', $email);
            if (!empty($user) && $check((int) $user->ID)) {
                return true;
            }
        }

        return false;
    }

    /**
     * FUNÇÃO DE DEBUG - Registra TUDO que está sendo enviado ao Stripe
     */
    public function modify_and_debug_stripe_params($checkout_session_params, $morder, $customer) {
        // Pegar o nível do pedido
        $level_id = $morder->membership_id;

        // Cobrança imediata já configurada no pmpro_checkout_level (retornante:
        // initial_payment = billing_amount → gateway segue o caminho 'combine', sem trial).
        // Não forçar trial_period_days por cima aqui.
        $checkout_level = $morder->getMembershipLevelAtCheckout();
        if (!empty($checkout_level->initial_payment)) {
            $this->log('Cobrança imediata já configurada (initial_payment=' . $checkout_level->initial_payment . ') — params mantidos intactos.');
            return $checkout_session_params;
        }

        // LOG 1: O que chegou ANTES da nossa modificação
        $this->log('===============================================');
        $this->log('STRIPE CHECKOUT SESSION PARAMS');
        $this->log('===============================================');
        $this->log('Level ID: ' . $level_id);
        $this->log('Params ANTES da modificação:');
        $this->log(print_r($this->redact_sensitive($checkout_session_params), true));

        if (!empty($level_id)) {
            $delay_days = get_option('pmpro_level_' . $level_id . '_delay_days', 0);
            $delay_days = intval($delay_days);

            $this->log('Delay configurado no plugin: ' . $delay_days . ' dias');

            $is_returning = $this->user_jah_assinou($morder->user_id ?? 0, $morder->Email ?? '');
            $this->log('Já assinou antes (reassinatura): ' . ($is_returning ? 'SIM' : 'não'));

            if ($is_returning) {
                // Reassinatura: cobra desde o dia 1. Stripe rejeita trial_period_days=0,
                // então REMOVEMOS a chave para o primeiro faturamento ser imediato.
                if (isset($checkout_session_params['subscription_data']['trial_period_days'])) {
                    unset($checkout_session_params['subscription_data']['trial_period_days']);
                }
                $this->log('Trial REMOVIDO — reassinatura cobra desde o dia 1.');
            } elseif ($delay_days > 0) {
                if ($delay_days > 730) {
                    $delay_days = 730;
                }

                // FORÇAR o trial_period_days
                if (isset($checkout_session_params['subscription_data'])) {
                    $checkout_session_params['subscription_data']['trial_period_days'] = $delay_days;
                } else {
                    $checkout_session_params['subscription_data'] = array(
                        'trial_period_days' => $delay_days
                    );
                }

                $this->log('Trial period FORÇADO para: ' . $delay_days . ' dias');
            }
        }

        // LOG 2: O que vai ser enviado DEPOIS da nossa modificação
        $this->log('-----------------------------------------------');
        $this->log('Params DEPOIS da modificação:');
        $this->log(print_r($this->redact_sensitive($checkout_session_params), true));
        $this->log('===============================================');

        return $checkout_session_params;
    }
}

function bebelume_subscription_delay_debug_init() {
    if (!class_exists('MemberOrder')) {
        add_action('admin_notices', 'bebelume_subscription_delay_debug_notice');
        return;
    }

    Bebelume_Subscription_Delay_Debug::get_instance();
}
add_action('plugins_loaded', 'bebelume_subscription_delay_debug_init');

function bebelume_subscription_delay_debug_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong>Bebelume - Subscription Delay DEBUG</strong> requer o plugin
            <strong>Paid Memberships Pro</strong> para funcionar.
        </p>
    </div>
    <?php
}
