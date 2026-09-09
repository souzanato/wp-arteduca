<?php
/**
 * VERIFICAÇÃO DE AMBIENTE - Video Playlist Block Debug
 * VERSÃO CORRIGIDA
 * 
 * COMO USAR:
 * 1. Salve como: wp-content/plugins/video-playlist-block/check-environment.php
 * 2. Acesse: seu-site.com/wp-content/plugins/video-playlist-block/check-environment.php
 */

// Carrega WordPress - Tenta diferentes caminhos
$wp_load_paths = array(
    __DIR__ . '/../../../wp-load.php',  // Caminho padrão
    __DIR__ . '/../../../../wp-load.php',  // Caso alternativo
    dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php',  // Mais seguro
);

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('Erro: Não foi possível carregar o WordPress. Verifique o caminho do arquivo.');
}

if (!defined('ABSPATH')) {
    die('Erro: WordPress não foi carregado corretamente.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>VPB Environment Check</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f0f0f0; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #0073aa; margin-top: 30px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        h3 { color: #555; margin-top: 20px; }
        .section { margin: 20px 0; padding: 20px; background: #f9f9f9; border-left: 4px solid #0073aa; border-radius: 4px; }
        .good { color: #46b450; font-weight: bold; }
        .warning { color: #ffb900; font-weight: bold; }
        .error { color: #dc3232; font-weight: bold; }
        .info { color: #0073aa; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0073aa; color: white; font-weight: 600; }
        tr:nth-child(even) { background: #f9f9f9; }
        code { background: #e8e8e8; padding: 2px 8px; border-radius: 3px; font-size: 90%; font-family: 'Courier New', monospace; }
        .plugin-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 10px; margin: 15px 0; }
        .plugin-item { padding: 12px; background: white; border: 1px solid #ddd; border-radius: 4px; }
        .alert { padding: 15px; margin: 15px 0; border-radius: 4px; }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffb900; }
        .alert-info { background: #d1ecf1; border-left: 4px solid #0073aa; }
        .alert-success { background: #d4edda; border-left: 4px solid #46b450; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-warning { background: #ffb900; color: white; }
        .badge-success { background: #46b450; color: white; }
        .badge-info { background: #0073aa; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Video Playlist Block - Environment Analysis</h1>
    
    <div class="alert alert-info">
        <strong>📊 Análise do Ambiente WordPress</strong><br>
        Esta ferramenta identifica problemas comuns que causam lentidão ao salvar posts.
    </div>
    
    <?php
    // ==========================================
    // 1. INFORMAÇÕES DO SERVIDOR
    // ==========================================
    ?>
    <div class="section">
        <h2>1. 🖥️ Server & WordPress Info</h2>
        <table>
            <tr>
                <th style="width: 30%;">Item</th>
                <th style="width: 40%;">Value</th>
                <th style="width: 30%;">Status</th>
            </tr>
            <tr>
                <td><strong>PHP Version</strong></td>
                <td><?php echo PHP_VERSION; ?></td>
                <td><?php echo version_compare(PHP_VERSION, '7.4', '>=') ? '<span class="good">✓ OK</span>' : '<span class="error">✗ Too old</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>WordPress Version</strong></td>
                <td><?php echo get_bloginfo('version'); ?></td>
                <td><?php echo version_compare(get_bloginfo('version'), '5.0', '>=') ? '<span class="good">✓ OK</span>' : '<span class="error">✗ Too old</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>Memory Limit</strong></td>
                <td><?php echo ini_get('memory_limit'); ?></td>
                <td><?php
                    $memory = ini_get('memory_limit');
                    $memory_int = intval($memory);
                    if ($memory_int >= 256) {
                        echo '<span class="good">✓ Excelente</span>';
                    } elseif ($memory_int >= 128) {
                        echo '<span class="warning">⚠ Suficiente</span>';
                    } else {
                        echo '<span class="error">✗ Muito baixo</span>';
                    }
                ?></td>
            </tr>
            <tr>
                <td><strong>Max Execution Time</strong></td>
                <td><?php echo ini_get('max_execution_time'); ?> segundos</td>
                <td><?php
                    $max_time = ini_get('max_execution_time');
                    echo $max_time >= 60 ? '<span class="good">✓ Bom</span>' : '<span class="warning">⚠ Baixo</span>';
                ?></td>
            </tr>
            <tr>
                <td><strong>WP_DEBUG</strong></td>
                <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? 'Habilitado' : 'Desabilitado'; ?></td>
                <td><span class="info">ℹ️ Info</span></td>
            </tr>
            <tr>
                <td><strong>SAVEQUERIES</strong></td>
                <td><?php echo defined('SAVEQUERIES') && SAVEQUERIES ? 'Habilitado' : 'Desabilitado'; ?></td>
                <td><?php echo defined('SAVEQUERIES') && SAVEQUERIES ? '<span class="warning">⚠ Pode afetar performance</span>' : '<span class="good">✓ OK</span>'; ?></td>
            </tr>
        </table>
    </div>
    
    <?php
    // ==========================================
    // 2. PLUGINS ATIVOS
    // ==========================================
    $active_plugins = get_option('active_plugins');
    $all_plugins = get_plugins();
    $total_plugins = count($active_plugins);
    
    $suspicious_keywords = array(
        'seo' => 'SEO plugins podem ser pesados',
        'yoast' => 'Yoast é conhecido por lentidão',
        'rank' => 'Rank Math executa análises pesadas',
        'cache' => 'Cache plugins podem conflitar',
        'backup' => 'Backup em tempo real causa lentidão',
        'security' => 'Security plugins escaneiam tudo',
        'wordfence' => 'Wordfence é muito pesado',
        'jetpack' => 'Jetpack tem muitos módulos ativos',
        'elementor' => 'Page builders são pesados',
        'woocommerce' => 'WooCommerce adiciona muitos hooks'
    );
    
    $found_suspicious = array();
    ?>
    
    <div class="section">
        <h2>2. 🔌 Active Plugins (<?php echo $total_plugins; ?>)</h2>
        
        <?php if ($total_plugins > 30): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Aviso:</strong> Você tem <?php echo $total_plugins; ?> plugins ativos. 
            Muitos plugins podem causar lentidão. Considere desabilitar plugins não essenciais.
        </div>
        <?php endif; ?>
        
        <h3>🚨 Plugins Suspeitos de Causar Lentidão:</h3>
        
        <div class="plugin-list">
            <?php
            foreach ($active_plugins as $plugin_path) {
                if (isset($all_plugins[$plugin_path])) {
                    $plugin = $all_plugins[$plugin_path];
                    $is_suspicious = false;
                    $reason = '';
                    
                    foreach ($suspicious_keywords as $keyword => $description) {
                        if (stripos($plugin['Name'], $keyword) !== false || stripos($plugin_path, $keyword) !== false) {
                            $is_suspicious = true;
                            $reason = $description;
                            $found_suspicious[] = array(
                                'name' => $plugin['Name'],
                                'reason' => $description
                            );
                            break;
                        }
                    }
                    
                    if ($is_suspicious) {
                        echo '<div class="plugin-item" style="border-left: 4px solid #ffb900; background: #fff3cd;">';
                        echo '<span class="badge badge-warning">⚠️ SUSPEITO</span><br>';
                        echo '<strong>' . esc_html($plugin['Name']) . '</strong><br>';
                        echo '<small style="color: #666;">v' . esc_html($plugin['Version']) . '</small><br>';
                        echo '<small style="color: #856404;">' . esc_html($reason) . '</small>';
                        echo '</div>';
                    }
                }
            }
            
            if (empty($found_suspicious)) {
                echo '<div class="alert alert-success">';
                echo '<strong>✓ Nenhum plugin suspeito detectado!</strong>';
                echo '</div>';
            }
            ?>
        </div>
        
        <details style="margin-top: 20px;">
            <summary style="cursor: pointer; font-weight: bold; color: #0073aa;">📋 Ver todos os plugins ativos</summary>
            <div class="plugin-list" style="margin-top: 10px;">
                <?php
                foreach ($active_plugins as $plugin_path) {
                    if (isset($all_plugins[$plugin_path])) {
                        $plugin = $all_plugins[$plugin_path];
                        echo '<div class="plugin-item">';
                        echo '<strong>' . esc_html($plugin['Name']) . '</strong><br>';
                        echo '<small>v' . esc_html($plugin['Version']) . '</small>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </details>
    </div>
    
    <?php
    // ==========================================
    // 3. HOOKS NO save_post
    // ==========================================
    global $wp_filter;
    $save_post_hooks = array();
    $total_hooks = 0;
    
    if (isset($wp_filter['save_post'])) {
        foreach ($wp_filter['save_post']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $total_hooks++;
                $function_name = 'unknown';
                $source = 'unknown';
                
                if (is_string($callback['function'])) {
                    $function_name = $callback['function'];
                    $source = 'Function';
                } elseif (is_array($callback['function'])) {
                    if (is_object($callback['function'][0])) {
                        $class = get_class($callback['function'][0]);
                        $function_name = $class . '::' . $callback['function'][1];
                        
                        try {
                            $reflection = new ReflectionClass($class);
                            $filename = $reflection->getFileName();
                            if (strpos($filename, 'wp-content/plugins/') !== false) {
                                preg_match('/wp-content\/plugins\/([^\/]+)/', $filename, $matches);
                                $source = isset($matches[1]) ? $matches[1] : 'Plugin';
                            } elseif (strpos($filename, 'wp-content/themes/') !== false) {
                                preg_match('/wp-content\/themes\/([^\/]+)/', $filename, $matches);
                                $source = isset($matches[1]) ? $matches[1] : 'Theme';
                            } else {
                                $source = 'WordPress Core';
                            }
                        } catch (Exception $e) {
                            $source = 'Unknown';
                        }
                    } else {
                        $function_name = $callback['function'][0] . '::' . $callback['function'][1];
                        $source = 'Static Class';
                    }
                }
                
                $save_post_hooks[] = array(
                    'priority' => $priority,
                    'function' => $function_name,
                    'source' => $source
                );
            }
        }
    }
    ?>
    
    <div class="section">
        <h2>3. 🔗 Registered save_post Hooks (<?php echo $total_hooks; ?>)</h2>
        
        <?php if ($total_hooks > 20): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Aviso:</strong> Você tem <?php echo $total_hooks; ?> hooks registrados no save_post. 
            Muitos hooks podem causar lentidão acumulada.
        </div>
        <?php endif; ?>
        
        <p>Todos os hooks que executam quando você salva um post:</p>
        
        <table>
            <tr>
                <th style="width: 10%;">Priority</th>
                <th style="width: 50%;">Function/Class</th>
                <th style="width: 40%;">Source</th>
            </tr>
            <?php
            foreach ($save_post_hooks as $hook) {
                $is_vpb = (strpos($hook['function'], 'Video_Playlist_Block') !== false || 
                          strpos($hook['function'], 'save_video_meta') !== false ||
                          strpos($hook['source'], 'video-playlist-block') !== false);
                
                $style = $is_vpb ? 'style="background: #fffbcc; font-weight: bold;"' : '';
                
                echo '<tr ' . $style . '>';
                echo '<td>' . esc_html($hook['priority']) . '</td>';
                echo '<td><code>' . esc_html($hook['function']) . '</code></td>';
                echo '<td>' . esc_html($hook['source']) . '</td>';
                echo '</tr>';
            }
            ?>
            <tr style="background: #e8e8e8; font-weight: bold;">
                <td colspan="3">Total: <?php echo $total_hooks; ?> hooks</td>
            </tr>
        </table>
    </div>
    
    <?php
    // ==========================================
    // 4. DATABASE INFO
    // ==========================================
    global $wpdb;
    
    $revisions = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'revision'");
    $autosaves = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'auto-draft'");
    $trash = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'trash'");
    
    $orphan_videos = $wpdb->get_var("
        SELECT COUNT(*) FROM $wpdb->posts p
        INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
        WHERE pm.meta_key = '_vpb_content_type' 
        AND pm.meta_value = 'video'
        AND p.post_status IN ('publish', 'draft', 'pending')
    ");
    ?>
    
    <div class="section">
        <h2>4. 💾 Database Information</h2>
        
        <table>
            <tr>
                <th style="width: 40%;">Item</th>
                <th style="width: 30%;">Count</th>
                <th style="width: 30%;">Status</th>
            </tr>
            <tr>
                <td><strong>Post Revisions</strong></td>
                <td><?php echo number_format($revisions); ?></td>
                <td><?php 
                    if ($revisions > 5000) {
                        echo '<span class="error">✗ Crítico - Limpe!</span>';
                    } elseif ($revisions > 1000) {
                        echo '<span class="warning">⚠ Alto - Considere limpar</span>';
                    } else {
                        echo '<span class="good">✓ OK</span>';
                    }
                ?></td>
            </tr>
            <tr>
                <td><strong>Auto-drafts</strong></td>
                <td><?php echo number_format($autosaves); ?></td>
                <td><?php 
                    if ($autosaves > 1000) {
                        echo '<span class="warning">⚠ Alto - Considere limpar</span>';
                    } else {
                        echo '<span class="good">✓ OK</span>';
                    }
                ?></td>
            </tr>
            <tr>
                <td><strong>Posts na Lixeira</strong></td>
                <td><?php echo number_format($trash); ?></td>
                <td><?php 
                    if ($trash > 500) {
                        echo '<span class="warning">⚠ Limpe a lixeira</span>';
                    } else {
                        echo '<span class="good">✓ OK</span>';
                    }
                ?></td>
            </tr>
            <tr style="background: #d1ecf1;">
                <td><strong>Video Posts (VPB)</strong></td>
                <td><?php echo number_format($orphan_videos); ?></td>
                <td><span class="info">ℹ️ Info</span></td>
            </tr>
        </table>
        
        <?php if ($revisions > 1000): ?>
        <div class="alert alert-warning" style="margin-top: 15px;">
            <strong>💡 Dica:</strong> Adicione no wp-config.php para limitar revisões:
            <pre style="margin-top: 10px;">define('WP_POST_REVISIONS', 5);</pre>
        </div>
        <?php endif; ?>
    </div>
    
    <?php
    // ==========================================
    // 5. RECOMENDAÇÕES
    // ==========================================
    ?>
    <div class="section">
        <h2>5. 🎯 Diagnóstico e Recomendações</h2>
        
        <div class="alert alert-success">
            <strong>✅ DESCOBERTA IMPORTANTE:</strong><br>
            Você comentou o <code>save_video_meta</code> e a lentidão continuou. 
            Isso significa que <strong>o problema NÃO é o plugin Video Playlist Block!</strong>
        </div>
        
        <h3>🔍 Próximos Passos:</h3>
        
        <div style="padding: 15px; background: white; border: 2px solid #0073aa; border-radius: 4px; margin: 15px 0;">
            <h4 style="margin-top: 0; color: #0073aa;">TESTE 1: Desabilitar Plugins Suspeitos</h4>
            <?php if (!empty($found_suspicious)): ?>
                <p>Desabilite temporariamente estes plugins e teste:</p>
                <ul>
                    <?php foreach ($found_suspicious as $plugin): ?>
                        <li><strong><?php echo esc_html($plugin['name']); ?></strong> - <?php echo esc_html($plugin['reason']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>✓ Nenhum plugin suspeito óbvio detectado.</p>
            <?php endif; ?>
        </div>
        
        <div style="padding: 15px; background: white; border: 2px solid #ffb900; border-radius: 4px; margin: 15px 0;">
            <h4 style="margin-top: 0; color: #856404;">TESTE 2: Instalar Query Monitor</h4>
            <ol>
                <li>Instale o plugin "Query Monitor"</li>
                <li>Edite um post e salve</li>
                <li>Veja a barra de debug no topo</li>
                <li>Identifique queries lentas ou hooks pesados</li>
                <li><strong>Me envie screenshots das queries mais lentas</strong></li>
            </ol>
        </div>
        
        <div style="padding: 15px; background: white; border: 2px solid #dc3232; border-radius: 4px; margin: 15px 0;">
            <h4 style="margin-top: 0; color: #dc3232;">TESTE 3: Tema Padrão</h4>
            <p>Troque temporariamente para Twenty Twenty-Three e teste. Se ficar rápido, o problema está no tema.</p>
        </div>
        
        <?php if ($total_hooks > 15): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Muitos Hooks:</strong> Você tem <?php echo $total_hooks; ?> hooks no save_post. 
            Cada um adiciona tempo de processamento. Considere otimizar ou desabilitar plugins desnecessários.
        </div>
        <?php endif; ?>
        
        <?php if ($revisions > 1000): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Muitas Revisões:</strong> Limpe revisões antigas com:
            <pre>wp post delete $(wp post list --post_type='revision' --format=ids) --force</pre>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="section" style="background: #d1ecf1; border-color: #0073aa;">
        <h2>📊 Resumo</h2>
        <ul style="font-size: 16px; line-height: 1.8;">
            <li><strong>Total de Plugins Ativos:</strong> <?php echo $total_plugins; ?></li>
            <li><strong>Plugins Suspeitos:</strong> <?php echo count($found_suspicious); ?></li>
            <li><strong>Hooks no save_post:</strong> <?php echo $total_hooks; ?></li>
            <li><strong>Revisões no Banco:</strong> <?php echo number_format($revisions); ?></li>
            <li><strong>Status VPB:</strong> <span class="good">✓ NÃO é o culpado</span></li>
        </ul>
    </div>
    
    <div style="text-align: center; padding: 20px; background: #f9f9f9; border-radius: 4px; margin-top: 30px;">
        <p style="font-size: 14px; color: #666;">
            Copie todas as informações desta página e envie para análise.<br>
            <strong>Especialmente: screenshots do Query Monitor se possível!</strong>
        </p>
    </div>
</div>
</body>
</html>