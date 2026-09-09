<?php
/**
 * Plugin Name: PMPro Email Diagnostic
 * Plugin URI: https://github.com/seu-usuario/pmpro-email-diagnostic
 * Description: Ferramenta completa de diagnóstico e teste de emails do Paid Memberships Pro
 * Version: 1.0.0
 * Author: Bebelume Team
 * Author URI: https://bebelume.com.br
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pmpro-email-diagnostic
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit('Acesso direto não permitido.');
}

// Define constantes do plugin
define('PMPRO_EMAIL_DIAG_VERSION', '1.0.0');
define('PMPRO_EMAIL_DIAG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PMPRO_EMAIL_DIAG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PMPRO_EMAIL_DIAG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe Principal do Plugin
 */
class PMPro_Email_Diagnostic_Plugin {
    
    /**
     * Instância única do plugin (Singleton)
     */
    private static $instance = null;
    
    /**
     * Retorna a instância única do plugin
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor privado (Singleton)
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Inicializa os hooks do WordPress
     */
    private function init_hooks() {
        // Ativação e desativação
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_post_pmpro_test_email', array($this, 'handle_test_email'));
        add_action('admin_notices', array($this, 'display_test_result'));
        
        // Links na página de plugins
        add_filter('plugin_action_links_' . PMPRO_EMAIL_DIAG_PLUGIN_BASENAME, array($this, 'add_plugin_links'));
    }
    
    /**
     * Carrega dependências
     */
    private function load_dependencies() {
        require_once PMPRO_EMAIL_DIAG_PLUGIN_DIR . 'includes/class-email-tester.php';
        require_once PMPRO_EMAIL_DIAG_PLUGIN_DIR . 'includes/class-email-logger.php';
        require_once PMPRO_EMAIL_DIAG_PLUGIN_DIR . 'includes/class-admin-page.php';
    }
    
    /**
     * Ativação do plugin
     */
    public function activate() {
        // Criar tabela de logs (se necessário)
        $this->create_log_table();
        
        // Definir versão
        update_option('pmpro_email_diag_version', PMPRO_EMAIL_DIAG_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Desativação do plugin
     */
    public function deactivate() {
        // Limpar cache se necessário
        delete_transient('pmpro_email_diag_cache');
    }
    
    /**
     * Cria tabela de logs de email
     */
    private function create_log_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pmpro_email_diagnostic_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            test_type varchar(50) NOT NULL,
            recipient_email varchar(255) NOT NULL,
            subject varchar(500) DEFAULT NULL,
            status varchar(20) NOT NULL,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY created_at (created_at),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Adiciona menu no admin
     */
    public function add_admin_menu() {
        $page = add_management_page(
            __('PMPro Email Diagnostic', 'pmpro-email-diagnostic'),
            __('PMPro Email Test', 'pmpro-email-diagnostic'),
            'manage_options',
            'pmpro-email-diagnostic',
            array('PMPro_Email_Diag_Admin_Page', 'render')
        );
        
        // Hook para carregar assets apenas nesta página
        add_action('load-' . $page, array($this, 'load_admin_page'));
    }
    
    /**
     * Carrega recursos da página admin
     */
    public function load_admin_page() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Carrega CSS e JS do admin
     */
    public function enqueue_admin_assets($hook) {
        // Apenas na página do plugin
        if ($hook !== 'tools_page_pmpro-email-diagnostic') {
            return;
        }
        
        wp_enqueue_style(
            'pmpro-email-diag-admin',
            PMPRO_EMAIL_DIAG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PMPRO_EMAIL_DIAG_VERSION
        );
        
        wp_enqueue_script(
            'pmpro-email-diag-admin',
            PMPRO_EMAIL_DIAG_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PMPRO_EMAIL_DIAG_VERSION,
            true
        );
        
        wp_localize_script('pmpro-email-diag-admin', 'pmproEmailDiag', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pmpro_email_diag_nonce')
        ));
    }
    
    /**
     * Processa envio de email de teste
     */
    public function handle_test_email() {
        check_admin_referer('pmpro_test_email_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'pmpro-email-diagnostic'));
        }
        
        $test_email = sanitize_email($_POST['test_email']);
        $test_type = sanitize_text_field($_POST['test_type']);
        
        $tester = new PMPro_Email_Tester();
        $result = $tester->send_test($test_email, $test_type);
        
        // Salvar log
        $logger = new PMPro_Email_Logger();
        $logger->log($test_type, $test_email, $result);
        
        // Redirecionar com resultado
        $redirect = add_query_arg(array(
            'page' => 'pmpro-email-diagnostic',
            'test_sent' => $result['success'] ? '1' : '0',
            'test_email' => urlencode($test_email),
            'test_type' => $test_type,
            'error_message' => $result['success'] ? '' : urlencode($result['message'])
        ), admin_url('tools.php'));
        
        wp_redirect($redirect);
        exit;
    }
    
    /**
     * Exibe resultado do teste
     */
    public function display_test_result() {
        if (!isset($_GET['test_sent']) || !isset($_GET['page']) || $_GET['page'] !== 'pmpro-email-diagnostic') {
            return;
        }
        
        $success = $_GET['test_sent'] === '1';
        $email = isset($_GET['test_email']) ? urldecode($_GET['test_email']) : '';
        $type = isset($_GET['test_type']) ? sanitize_text_field($_GET['test_type']) : '';
        $error = isset($_GET['error_message']) ? urldecode($_GET['error_message']) : '';
        
        $class = $success ? 'notice-success' : 'notice-error';
        $icon = $success ? '✅' : '❌';
        
        if ($success) {
            $message = sprintf(
                __('Email de teste enviado com sucesso para: %s', 'pmpro-email-diagnostic'),
                '<strong>' . esc_html($email) . '</strong>'
            );
        } else {
            $message = sprintf(
                __('Falha ao enviar email de teste para: %s', 'pmpro-email-diagnostic'),
                '<strong>' . esc_html($email) . '</strong>'
            );
        }
        
        ?>
        <div class="notice <?php echo esc_attr($class); ?> is-dismissible">
            <p><?php echo $icon; ?> <?php echo $message; ?></p>
            <?php if (!$success && !empty($error)): ?>
                <p><strong><?php _e('Erro:', 'pmpro-email-diagnostic'); ?></strong> <?php echo esc_html($error); ?></p>
            <?php endif; ?>
            <?php if (!$success): ?>
                <p>
                    <?php _e('Verifique os logs de erro do WordPress em', 'pmpro-email-diagnostic'); ?>
                    <code>wp-content/debug.log</code>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Adiciona links na página de plugins
     */
    public function add_plugin_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('tools.php?page=pmpro-email-diagnostic') . '">' . __('Testar Emails', 'pmpro-email-diagnostic') . '</a>',
            '<a href="https://github.com/seu-usuario/pmpro-email-diagnostic" target="_blank">' . __('Documentação', 'pmpro-email-diagnostic') . '</a>',
        );
        
        return array_merge($plugin_links, $links);
    }
}

/**
 * Inicializa o plugin
 */
function pmpro_email_diagnostic_init() {
    return PMPro_Email_Diagnostic_Plugin::get_instance();
}

// Iniciar o plugin
pmpro_email_diagnostic_init();
