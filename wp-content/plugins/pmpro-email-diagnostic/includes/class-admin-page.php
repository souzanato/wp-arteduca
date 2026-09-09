<?php
/**
 * Classe da página administrativa do plugin
 * 
 * @package PMPro_Email_Diagnostic
 */

if (!defined('ABSPATH')) {
    exit;
}

class PMPro_Email_Diag_Admin_Page {
    
    /**
     * Renderiza a página principal
     */
    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'pmpro-email-diagnostic'));
        }
        
        $current_user = wp_get_current_user();
        $tester = new PMPro_Email_Tester();
        $logger = new PMPro_Email_Logger();
        $stats = $logger->get_stats();
        ?>
        <div class="wrap pmpro-email-diag-wrap">
            <h1 class="wp-heading-inline">
                📧 <?php _e('PMPro Email Diagnostic', 'pmpro-email-diagnostic'); ?>
            </h1>
            <span class="pmpro-version">v<?php echo PMPRO_EMAIL_DIAG_VERSION; ?></span>
            
            <hr class="wp-header-end">
            
            <!-- Estatísticas Rápidas -->
            <?php if ($stats['total'] > 0): ?>
            <div class="pmpro-stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label"><?php _e('Total de Testes', 'pmpro-email-diagnostic'); ?></div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $stats['success']; ?></div>
                    <div class="stat-label"><?php _e('Sucessos', 'pmpro-email-diagnostic'); ?></div>
                </div>
                <div class="stat-card error">
                    <div class="stat-icon">❌</div>
                    <div class="stat-value"><?php echo $stats['failed']; ?></div>
                    <div class="stat-label"><?php _e('Falhas', 'pmpro-email-diagnostic'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-value"><?php echo $stats['success_rate']; ?>%</div>
                    <div class="stat-label"><?php _e('Taxa de Sucesso', 'pmpro-email-diagnostic'); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="pmpro-grid">
                
                <!-- Coluna Esquerda -->
                <div class="pmpro-main-column">
                    
                    <!-- Configurações do Sistema -->
                    <div class="postbox">
                        <h2 class="hndle">
                            <span>🔍 <?php _e('Configurações de Email', 'pmpro-email-diagnostic'); ?></span>
                        </h2>
                        <div class="inside">
                            <?php self::render_email_config(); ?>
                        </div>
                    </div>
                    
                    <!-- Configurações do PMPro -->
                    <div class="postbox">
                        <h2 class="hndle">
                            <span>⚙️ <?php _e('Configurações do PMPro', 'pmpro-email-diagnostic'); ?></span>
                        </h2>
                        <div class="inside">
                            <?php self::render_pmpro_settings(); ?>
                        </div>
                    </div>
                    
                    <!-- Logs Recentes -->
                    <div class="postbox">
                        <h2 class="hndle">
                            <span>📋 <?php _e('Histórico de Testes', 'pmpro-email-diagnostic'); ?></span>
                        </h2>
                        <div class="inside">
                            <?php self::render_recent_logs($logger); ?>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Coluna Direita -->
                <div class="pmpro-sidebar-column">
                    
                    <!-- Teste de Email -->
                    <div class="postbox">
                        <h2 class="hndle">
                            <span>🧪 <?php _e('Testar Envio de Email', 'pmpro-email-diagnostic'); ?></span>
                        </h2>
                        <div class="inside">
                            <?php self::render_test_form($current_user); ?>
                        </div>
                    </div>
                    
                    <!-- Guia Rápido -->
                    <div class="postbox">
                        <h2 class="hndle">
                            <span>📖 <?php _e('Guia Rápido', 'pmpro-email-diagnostic'); ?></span>
                        </h2>
                        <div class="inside">
                            <?php self::render_quick_guide(); ?>
                        </div>
                    </div>
                    
                    <!-- Soluções Comuns -->
                    <div class="postbox">
                        <h2 class="hndle">
                            <span>🔧 <?php _e('Soluções Comuns', 'pmpro-email-diagnostic'); ?></span>
                        </h2>
                        <div class="inside">
                            <?php self::render_solutions(); ?>
                        </div>
                    </div>
                    
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza configurações de email
     */
    private static function render_email_config() {
        $from_email = get_option('admin_email');
        $blogname = get_option('blogname');
        $mail_available = function_exists('mail');
        $smtp_configured = defined('SMTP_HOST');
        
        ?>
        <table class="widefat striped">
            <tbody>
                <tr>
                    <td width="40%"><strong><?php _e('Email do WordPress:', 'pmpro-email-diagnostic'); ?></strong></td>
                    <td><?php echo esc_html($from_email); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Nome do Site:', 'pmpro-email-diagnostic'); ?></strong></td>
                    <td><?php echo esc_html($blogname); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Método de Envio:', 'pmpro-email-diagnostic'); ?></strong></td>
                    <td>
                        <?php if ($smtp_configured): ?>
                            <span class="status-badge success">✅ SMTP Configurado</span>
                        <?php else: ?>
                            <span class="status-badge warning">⚠️ PHP mail() (padrão)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Função mail() disponível:', 'pmpro-email-diagnostic'); ?></strong></td>
                    <td>
                        <?php if ($mail_available): ?>
                            <span class="status-badge success">✅ Sim</span>
                        <?php else: ?>
                            <span class="status-badge error">❌ Não - emails NÃO funcionarão!</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Renderiza configurações do PMPro
     */
    private static function render_pmpro_settings() {
        if (!function_exists('pmpro_getOption')) {
            ?>
            <div class="notice notice-warning inline">
                <p>❌ <strong><?php _e('PMPro não está ativo!', 'pmpro-email-diagnostic'); ?></strong></p>
            </div>
            <?php
            return;
        }
        
        $email_from = pmpro_getOption('from_email');
        $email_name = pmpro_getOption('from_name');
        
        ?>
        <table class="widefat striped">
            <tbody>
                <tr>
                    <td width="40%"><strong><?php _e('Email Remetente:', 'pmpro-email-diagnostic'); ?></strong></td>
                    <td>
                        <?php if (!empty($email_from)): ?>
                            <?php echo esc_html($email_from); ?>
                        <?php else: ?>
                            <span class="status-badge warning">⚠️ <?php _e('Não configurado', 'pmpro-email-diagnostic'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Nome Remetente:', 'pmpro-email-diagnostic'); ?></strong></td>
                    <td>
                        <?php if (!empty($email_name)): ?>
                            <?php echo esc_html($email_name); ?>
                        <?php else: ?>
                            <span class="status-badge warning">⚠️ <?php _e('Não configurado', 'pmpro-email-diagnostic'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <a href="<?php echo admin_url('admin.php?page=pmpro-emailsettings'); ?>" class="button button-secondary">
                            <?php _e('Configurar PMPro Email Settings', 'pmpro-email-diagnostic'); ?>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Renderiza logs recentes
     */
    private static function render_recent_logs($logger) {
        $logs = $logger->get_recent_logs(10);
        
        if (empty($logs)): ?>
            <p><?php _e('Nenhum teste realizado ainda.', 'pmpro-email-diagnostic'); ?></p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Data/Hora', 'pmpro-email-diagnostic'); ?></th>
                        <th><?php _e('Para', 'pmpro-email-diagnostic'); ?></th>
                        <th><?php _e('Tipo', 'pmpro-email-diagnostic'); ?></th>
                        <th><?php _e('Status', 'pmpro-email-diagnostic'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($log->created_at))); ?></td>
                        <td><?php echo esc_html($log->recipient_email); ?></td>
                        <td><?php echo esc_html($log->test_type); ?></td>
                        <td>
                            <?php if ($log->status === 'success'): ?>
                                <span class="status-badge success">✅ <?php _e('Enviado', 'pmpro-email-diagnostic'); ?></span>
                            <?php else: ?>
                                <span class="status-badge error" title="<?php echo esc_attr($log->error_message); ?>">
                                    ❌ <?php _e('Falhou', 'pmpro-email-diagnostic'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif;
    }
    
    /**
     * Renderiza formulário de teste
     */
    private static function render_test_form($user) {
        ?>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="pmpro-test-form">
            <input type="hidden" name="action" value="pmpro_test_email">
            <?php wp_nonce_field('pmpro_test_email_nonce'); ?>
            
            <p>
                <label for="test_email"><strong><?php _e('Email de Destino:', 'pmpro-email-diagnostic'); ?></strong></label>
                <input type="email" name="test_email" id="test_email" 
                       value="<?php echo esc_attr($user->user_email); ?>" 
                       class="widefat" required>
            </p>
            
            <p>
                <label for="test_type"><strong><?php _e('Tipo de Teste:', 'pmpro-email-diagnostic'); ?></strong></label>
                <select name="test_type" id="test_type" class="widefat">
                    <option value="wp_mail"><?php _e('WordPress wp_mail()', 'pmpro-email-diagnostic'); ?></option>
                    <option value="pmpro_checkout"><?php _e('PMPro - Email de Checkout', 'pmpro-email-diagnostic'); ?></option>
                    <option value="pmpro_admin"><?php _e('PMPro - Email de Admin', 'pmpro-email-diagnostic'); ?></option>
                    <option value="pmpro_welcome"><?php _e('PMPro - Email de Boas-vindas', 'pmpro-email-diagnostic'); ?></option>
                </select>
            </p>
            
            <p>
                <button type="submit" class="button button-primary button-hero" style="width: 100%;">
                    📤 <?php _e('Enviar Email de Teste', 'pmpro-email-diagnostic'); ?>
                </button>
            </p>
        </form>
        <?php
    }
    
    /**
     * Renderiza guia rápido
     */
    private static function render_quick_guide() {
        ?>
        <ol class="pmpro-guide-list">
            <li><?php _e('Escolha o tipo de teste', 'pmpro-email-diagnostic'); ?></li>
            <li><?php _e('Informe seu email', 'pmpro-email-diagnostic'); ?></li>
            <li><?php _e('Clique em "Enviar"', 'pmpro-email-diagnostic'); ?></li>
            <li><?php _e('Verifique sua caixa de entrada', 'pmpro-email-diagnostic'); ?></li>
            <li><?php _e('Confira também o SPAM', 'pmpro-email-diagnostic'); ?></li>
        </ol>
        <?php
    }
    
    /**
     * Renderiza soluções comuns
     */
    private static function render_solutions() {
        ?>
        <div class="pmpro-solutions">
            <h4>❌ <?php _e('Emails não estão sendo enviados?', 'pmpro-email-diagnostic'); ?></h4>
            
            <h5>1. <?php _e('Instalar Plugin SMTP', 'pmpro-email-diagnostic'); ?></h5>
            <p><?php _e('Recomendamos:', 'pmpro-email-diagnostic'); ?></p>
            <ul>
                <li><strong>WP Mail SMTP</strong></li>
                <li><strong>Easy WP SMTP</strong></li>
                <li><strong>Post SMTP Mailer</strong></li>
            </ul>
            
            <h5>2. <?php _e('Configurar PMPro', 'pmpro-email-diagnostic'); ?></h5>
            <p><?php _e('Ir em:', 'pmpro-email-diagnostic'); ?></p>
            <p><strong>Memberships → Settings → Email</strong></p>
            
            <h5>3. <?php _e('Verificar SPAM', 'pmpro-email-diagnostic'); ?></h5>
            <p><?php _e('Sempre verifique a pasta de lixo eletrônico!', 'pmpro-email-diagnostic'); ?></p>
        </div>
        <?php
    }
}
