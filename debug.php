<?php
/**
 * ====================================================================
 * SCRIPT DE DEBUG COMPLETO - BEBELUME WORDPRESS
 * ====================================================================
 * 
 * INSTRUÇÕES:
 * 1. Salve como: debug.php
 * 2. Upload para: /opt/wp-bebelume/debug.php (raiz do WordPress)
 * 3. Execute via browser: https://bebelume.com.br/debug.php
 * 4. OU via terminal: php /opt/wp-bebelume/debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "====================================================================\n";
echo "🔍 DEBUG COMPLETO - BEBELUME WORDPRESS\n";
echo "====================================================================\n\n";

// ====================================================================
// 1. INFORMAÇÕES DO SISTEMA
// ====================================================================
echo "1️⃣  INFORMAÇÕES DO SISTEMA\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Diretório Atual: " . __DIR__ . "\n";
echo "Usuário: " . get_current_user() . "\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// ====================================================================
// 2. VERIFICAR ESTRUTURA DE DIRETÓRIOS
// ====================================================================
echo "2️⃣  ESTRUTURA DE DIRETÓRIOS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$wp_load = __DIR__ . '/wp-load.php';
$wp_config = __DIR__ . '/wp-config.php';
$plugins_dir = __DIR__ . '/wp-content/plugins';
$bebelume_plugin = $plugins_dir . '/bebelume-profile/bebelume-profile.php';

echo "wp-load.php existe? " . (file_exists($wp_load) ? "✅ SIM" : "❌ NÃO") . "\n";
echo "wp-config.php existe? " . (file_exists($wp_config) ? "✅ SIM" : "❌ NÃO") . "\n";
echo "wp-content/plugins/ existe? " . (file_exists($plugins_dir) ? "✅ SIM" : "❌ NÃO") . "\n";
echo "bebelume-profile.php existe? " . (file_exists($bebelume_plugin) ? "✅ SIM" : "❌ NÃO") . "\n\n";

// ====================================================================
// 3. PROCURAR TODAS AS VERSÕES DO PLUGIN
// ====================================================================
echo "3️⃣  PROCURAR TODAS AS VERSÕES DO PLUGIN\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$found_plugins = glob($plugins_dir . '/bebelume*/bebelume-profile.php');
echo "Versões encontradas: " . count($found_plugins) . "\n\n";

foreach ($found_plugins as $index => $plugin_file) {
    echo "🔹 Versão #" . ($index + 1) . ":\n";
    echo "   Arquivo: " . $plugin_file . "\n";
    echo "   Tamanho: " . filesize($plugin_file) . " bytes\n";
    echo "   Modificado: " . date('Y-m-d H:i:s', filemtime($plugin_file)) . "\n";
    
    // Contar linhas
    $lines = count(file($plugin_file));
    echo "   Linhas: " . $lines . "\n";
    
    // Ver linha 116
    $file_lines = file($plugin_file);
    if (isset($file_lines[115])) { // Array é 0-indexed
        echo "   Linha 116: " . trim($file_lines[115]) . "\n";
    }
    echo "\n";
}

// ====================================================================
// 4. VERIFICAR LINHA 116 DO PLUGIN PRINCIPAL
// ====================================================================
echo "4️⃣  ANÁLISE DA LINHA 116 (PROBLEMA REPORTADO)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (file_exists($bebelume_plugin)) {
    $lines = file($bebelume_plugin);
    
    echo "Total de linhas: " . count($lines) . "\n\n";
    
    echo "Contexto (linhas 110-125):\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    for ($i = 109; $i < 125; $i++) {
        if (isset($lines[$i])) {
            $line_num = $i + 1;
            $marker = ($line_num == 116) ? " ← ⚠️  LINHA DO ERRO" : "";
            printf("%3d: %s%s\n", $line_num, rtrim($lines[$i]), $marker);
        }
    }
    echo "\n";
    
    // Procurar por add_action na linha 116
    if (isset($lines[115])) {
        $line_116 = trim($lines[115]);
        if (strpos($line_116, 'add_action') !== false) {
            echo "⚠️  PROBLEMA ENCONTRADO!\n";
            echo "Linha 116 contém: add_action()\n";
            echo "Conteúdo: " . $line_116 . "\n\n";
        } else {
            echo "✅ Linha 116 NÃO contém add_action()\n";
            echo "Conteúdo: " . $line_116 . "\n\n";
        }
    }
} else {
    echo "❌ Plugin não encontrado em: " . $bebelume_plugin . "\n\n";
}

// ====================================================================
// 5. PROCURAR add_action() FORA DE CONTEXTO
// ====================================================================
echo "5️⃣  PROCURAR add_action() FORA DE FUNÇÕES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (file_exists($bebelume_plugin)) {
    $content = file_get_contents($bebelume_plugin);
    
    // Procurar padrões problemáticos
    $patterns = [
        'add_action\(' => 'add_action(',
        'add_filter\(' => 'add_filter(',
        'register_activation_hook' => 'register_activation_hook',
    ];
    
    foreach ($patterns as $pattern => $label) {
        preg_match_all('/' . preg_quote($pattern) . '/', $content, $matches, PREG_OFFSET_CAPTURE);
        $count = count($matches[0]);
        echo "Encontradas " . $count . " ocorrências de: " . $label . "\n";
    }
    echo "\n";
}

// ====================================================================
// 6. VERIFICAR PLUGINS ATIVOS
// ====================================================================
echo "6️⃣  PLUGINS ATIVOS (via banco de dados)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Tentar carregar WordPress sem executar plugins
define('WP_USE_THEMES', false);
define('SHORTINIT', true); // Carregamento mínimo

try {
    if (file_exists($wp_load)) {
        require_once($wp_load);
        
        // Pegar plugins ativos do banco
        global $wpdb;
        $active_plugins = get_option('active_plugins');
        
        if (is_array($active_plugins)) {
            echo "Total de plugins ativos: " . count($active_plugins) . "\n\n";
            foreach ($active_plugins as $plugin) {
                $marker = (strpos($plugin, 'bebelume') !== false) ? " ← ⚠️  ESTE!" : "";
                echo "  • " . $plugin . $marker . "\n";
            }
        } else {
            echo "❌ Não foi possível ler plugins ativos\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao carregar WordPress: " . $e->getMessage() . "\n";
}
echo "\n";

// ====================================================================
// 7. VERIFICAR HASH DO ARQUIVO
// ====================================================================
echo "7️⃣  HASH DO ARQUIVO (para confirmar versão)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (file_exists($bebelume_plugin)) {
    echo "MD5: " . md5_file($bebelume_plugin) . "\n";
    echo "SHA1: " . sha1_file($bebelume_plugin) . "\n";
    echo "Tamanho: " . filesize($bebelume_plugin) . " bytes\n";
    echo "Última modificação: " . date('Y-m-d H:i:s', filemtime($bebelume_plugin)) . "\n\n";
}

// ====================================================================
// 8. TESTE DE PARSE DO ARQUIVO
// ====================================================================
echo "8️⃣  TESTE DE PARSE (sintaxe PHP)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (file_exists($bebelume_plugin)) {
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($bebelume_plugin) . " 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        echo "✅ Sintaxe PHP válida!\n";
    } else {
        echo "❌ Erro de sintaxe encontrado:\n";
        echo implode("\n", $output) . "\n";
    }
    echo "\n";
}

// ====================================================================
// 9. BUSCAR FUNÇÕES PROBLEMÁTICAS
// ====================================================================
echo "9️⃣  BUSCAR FUNÇÕES POTENCIALMENTE PROBLEMÁTICAS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (file_exists($bebelume_plugin)) {
    $lines = file($bebelume_plugin);
    $in_function = false;
    $function_name = '';
    
    foreach ($lines as $num => $line) {
        $line_num = $num + 1;
        
        // Detectar início de função
        if (preg_match('/^\s*(public|private|protected)?\s*function\s+(\w+)/', $line, $matches)) {
            $in_function = true;
            $function_name = $matches[2];
        }
        
        // Procurar add_action/add_filter dentro de funções
        if ($in_function && (strpos($line, 'add_action(') !== false || strpos($line, 'add_filter(') !== false)) {
            // Verificar se não está em init_hooks ou __construct
            if (!in_array($function_name, ['init_hooks', '__construct', 'activate', 'deactivate'])) {
                echo "⚠️  Linha $line_num em função '$function_name': " . trim($line) . "\n";
            }
        }
        
        // Detectar fim de função (simplificado)
        if ($in_function && trim($line) === '}') {
            $in_function = false;
        }
    }
    echo "\n";
}

// ====================================================================
// 10. PROCURAR apply_non_admin_hooks
// ====================================================================
echo "🔟 PROCURAR FUNÇÃO apply_non_admin_hooks (CAUSA DO PROBLEMA)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (file_exists($bebelume_plugin)) {
    $content = file_get_contents($bebelume_plugin);
    
    if (strpos($content, 'apply_non_admin_hooks') !== false) {
        echo "❌ ENCONTRADO! A função apply_non_admin_hooks EXISTE!\n";
        echo "   Esta é a causa do problema com WP-CLI!\n\n";
        
        // Encontrar a linha
        $lines = file($bebelume_plugin);
        foreach ($lines as $num => $line) {
            if (strpos($line, 'apply_non_admin_hooks') !== false) {
                echo "   Linha " . ($num + 1) . ": " . trim($line) . "\n";
            }
        }
    } else {
        echo "✅ NÃO ENCONTRADO! O arquivo está correto!\n";
    }
    echo "\n";
}

// ====================================================================
// 11. COMPARAR COM BACKUP
// ====================================================================
echo "1️⃣1️⃣  PROCURAR ARQUIVOS DE BACKUP\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$backup_files = glob(__DIR__ . '/bebelume-profile*.php');
if (count($backup_files) > 0) {
    echo "Backups encontrados:\n";
    foreach ($backup_files as $backup) {
        echo "  • " . basename($backup) . "\n";
        echo "    Tamanho: " . filesize($backup) . " bytes\n";
        echo "    Modificado: " . date('Y-m-d H:i:s', filemtime($backup)) . "\n\n";
    }
} else {
    echo "Nenhum backup encontrado na raiz\n\n";
}

// ====================================================================
// 12. RECOMENDAÇÕES
// ====================================================================
echo "1️⃣2️⃣  RECOMENDAÇÕES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (file_exists($bebelume_plugin)) {
    $content = file_get_contents($bebelume_plugin);
    
    if (strpos($content, 'apply_non_admin_hooks') !== false) {
        echo "⚠️  AÇÃO NECESSÁRIA:\n\n";
        echo "1. O arquivo atual AINDA TEM a função problemática!\n";
        echo "2. O arquivo que enviei NÃO foi aplicado corretamente\n";
        echo "3. Execute:\n\n";
        echo "   cd /opt/wp-bebelume/wp-content/plugins/bebelume-profile/\n";
        echo "   cp bebelume-profile.php bebelume-profile-BACKUP-$(date +%Y%m%d-%H%M%S).php\n";
        echo "   # Faça upload do arquivo correto\n";
        echo "   # Substitua bebelume-profile.php\n\n";
    } else {
        echo "✅ Arquivo parece correto!\n\n";
        echo "Se ainda dá erro WP-CLI:\n";
        echo "1. Pode ter outro plugin causando problema\n";
        echo "2. Pode ter cache de opcodes ativo\n";
        echo "3. Execute: service php-fpm restart (ou php7.4-fpm)\n\n";
    }
}

echo "====================================================================\n";
echo "✅ DEBUG COMPLETO!\n";
echo "====================================================================\n\n";

echo "📋 Próximos passos:\n";
echo "1. Analise os resultados acima\n";
echo "2. Se encontrou 'apply_non_admin_hooks', o arquivo não foi substituído\n";
echo "3. Se NÃO encontrou, mas erro persiste, pode ser cache PHP\n";
echo "4. Mande o output deste script completo\n\n";
?>
