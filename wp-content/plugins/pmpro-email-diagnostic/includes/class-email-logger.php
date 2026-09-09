<?php
/**
 * Classe responsável por registrar logs de testes de email
 * 
 * @package PMPro_Email_Diagnostic
 */

if (!defined('ABSPATH')) {
    exit;
}

class PMPro_Email_Logger {
    
    /**
     * Nome da tabela de logs
     */
    private $table_name;
    
    /**
     * Construtor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pmpro_email_diagnostic_log';
    }
    
    /**
     * Registra um log de teste de email
     * 
     * @param string $test_type Tipo de teste
     * @param string $recipient Email destinatário
     * @param array $result Resultado do teste
     * @return int|false ID do log inserido ou false em caso de erro
     */
    public function log($test_type, $recipient, $result) {
        global $wpdb;
        
        $data = array(
            'test_type' => $test_type,
            'recipient_email' => $recipient,
            'subject' => $this->get_subject_by_type($test_type),
            'status' => $result['success'] ? 'success' : 'failed',
            'error_message' => $result['success'] ? null : $result['message'],
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        $inserted = $wpdb->insert(
            $this->table_name,
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        return $inserted ? $wpdb->insert_id : false;
    }
    
    /**
     * Obtém logs recentes
     * 
     * @param int $limit Número de logs
     * @return array
     */
    public function get_recent_logs($limit = 20) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
        
        return $results ? $results : array();
    }
    
    /**
     * Obtém estatísticas de testes
     * 
     * @return array
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total de testes
        $stats['total'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name}"
        );
        
        // Testes com sucesso
        $stats['success'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'success'"
        );
        
        // Testes falhados
        $stats['failed'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'failed'"
        );
        
        // Taxa de sucesso
        $stats['success_rate'] = $stats['total'] > 0 
            ? round(($stats['success'] / $stats['total']) * 100, 2) 
            : 0;
        
        // Último teste
        $last_test = $wpdb->get_row(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT 1"
        );
        
        $stats['last_test'] = $last_test;
        
        return $stats;
    }
    
    /**
     * Limpa logs antigos
     * 
     * @param int $days Dias para manter
     * @return int Número de logs deletados
     */
    public function cleanup_old_logs($days = 30) {
        global $wpdb;
        
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE created_at < %s",
            $date
        ));
        
        return $deleted;
    }
    
    /**
     * Obtém assunto do email por tipo
     * 
     * @param string $type
     * @return string
     */
    private function get_subject_by_type($type) {
        $subjects = array(
            'wp_mail' => '[TESTE] Email do WordPress',
            'pmpro_checkout' => '[TESTE PMPro] Email de Checkout',
            'pmpro_admin' => '[TESTE PMPro] Email Administrativo',
            'pmpro_welcome' => '[TESTE PMPro] Email de Boas-vindas'
        );
        
        return isset($subjects[$type]) ? $subjects[$type] : '[TESTE]';
    }
}
