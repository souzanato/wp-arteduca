<?php
/**
 * Classe responsável por enviar emails de teste
 * 
 * @package PMPro_Email_Diagnostic
 */

if (!defined('ABSPATH')) {
    exit;
}

class PMPro_Email_Tester {
    
    /**
     * Envia email de teste
     * 
     * @param string $to Email de destino
     * @param string $type Tipo de teste
     * @return array Resultado ['success' => bool, 'message' => string]
     */
    public function send_test($to, $type) {
        switch ($type) {
            case 'wp_mail':
                return $this->test_wp_mail($to);
            
            case 'pmpro_checkout':
                return $this->test_pmpro_email($to, 'checkout');
            
            case 'pmpro_admin':
                return $this->test_pmpro_email($to, 'admin');
            
            case 'pmpro_welcome':
                return $this->test_pmpro_email($to, 'welcome');
            
            default:
                return array(
                    'success' => false,
                    'message' => __('Tipo de teste inválido', 'pmpro-email-diagnostic')
                );
        }
    }
    
    /**
     * Testa wp_mail básico
     */
    private function test_wp_mail($to) {
        $subject = '[TESTE] ' . __('Email do WordPress', 'pmpro-email-diagnostic');
        
        $message = __('Este é um email de teste enviado via wp_mail().', 'pmpro-email-diagnostic') . "\n\n";
        $message .= __('Data/Hora:', 'pmpro-email-diagnostic') . ' ' . date('Y-m-d H:i:s') . "\n";
        $message .= __('Site:', 'pmpro-email-diagnostic') . ' ' . get_bloginfo('name') . "\n";
        $message .= __('URL:', 'pmpro-email-diagnostic') . ' ' . home_url() . "\n\n";
        $message .= __('Se você recebeu este email, a função wp_mail() está funcionando corretamente!', 'pmpro-email-diagnostic');
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        return array(
            'success' => $sent,
            'message' => $sent 
                ? __('Email enviado com sucesso', 'pmpro-email-diagnostic')
                : __('Falha ao enviar email', 'pmpro-email-diagnostic')
        );
    }
    
    /**
     * Testa email do PMPro
     */
    private function test_pmpro_email($to, $template) {
        if (!function_exists('pmpro_getOption')) {
            return array(
                'success' => false,
                'message' => __('PMPro não está ativo', 'pmpro-email-diagnostic')
            );
        }
        
        $subjects = array(
            'checkout' => __('Email de Checkout', 'pmpro-email-diagnostic'),
            'admin' => __('Email Administrativo', 'pmpro-email-diagnostic'),
            'welcome' => __('Email de Boas-vindas', 'pmpro-email-diagnostic')
        );
        
        $subject = '[TESTE PMPro] ' . $subjects[$template];
        
        $message = sprintf(
            __('Este é um teste de email do tipo: %s', 'pmpro-email-diagnostic'),
            '<strong>' . $subjects[$template] . '</strong>'
        ) . "\n\n";
        
        $message .= __('Informações do teste:', 'pmpro-email-diagnostic') . "\n";
        $message .= __('Data/Hora:', 'pmpro-email-diagnostic') . ' ' . date('Y-m-d H:i:s') . "\n";
        $message .= __('Email Remetente PMPro:', 'pmpro-email-diagnostic') . ' ' . pmpro_getOption('from_email') . "\n";
        $message .= __('Nome Remetente PMPro:', 'pmpro-email-diagnostic') . ' ' . pmpro_getOption('from_name') . "\n\n";
        
        $message .= __('Se você recebeu este email, o PMPro está configurado corretamente para enviar emails!', 'pmpro-email-diagnostic');
        
        // Usar filtros do PMPro
        $from_email = pmpro_getOption('from_email');
        $from_name = pmpro_getOption('from_name');
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8'
        );
        
        if ($from_email) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }
        
        $sent = wp_mail($to, $subject, wpautop($message), $headers);
        
        return array(
            'success' => $sent,
            'message' => $sent 
                ? __('Email PMPro enviado com sucesso', 'pmpro-email-diagnostic')
                : __('Falha ao enviar email PMPro', 'pmpro-email-diagnostic')
        );
    }
    
    /**
     * Verifica configuração do servidor de email
     */
    public function check_server_config() {
        $config = array();
        
        // Função mail disponível
        $config['mail_function'] = function_exists('mail');
        
        // SMTP configurado
        $config['smtp_configured'] = defined('SMTP_HOST') && defined('SMTP_PORT');
        
        // Email do WordPress
        $config['wp_email'] = get_option('admin_email');
        
        // PMPro configurado
        if (function_exists('pmpro_getOption')) {
            $config['pmpro_from_email'] = pmpro_getOption('from_email');
            $config['pmpro_from_name'] = pmpro_getOption('from_name');
        } else {
            $config['pmpro_from_email'] = null;
            $config['pmpro_from_name'] = null;
        }
        
        // PHPMailer
        global $phpmailer;
        if (is_object($phpmailer)) {
            $config['mailer'] = $phpmailer->Mailer;
        } else {
            $config['mailer'] = 'mail';
        }
        
        return $config;
    }
}
