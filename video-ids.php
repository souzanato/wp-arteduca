<?php
/**
 * Script Inteligente para Excluir Vídeos Não Utilizados
 * 
 * Este script busca AUTOMATICAMENTE todos os vídeos que estão sendo
 * usados em QUALQUER playlist do site e exclui apenas os não utilizados.
 * 
 * IMPORTANTE: Faça backup do banco de dados antes de executar!
 * 
 * Uso: Faça upload para a raiz do WordPress e acesse via navegador
 */

// Segurança básica
if (!isset($_GET['acao'])) {
    die('
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Excluir Vídeos Não Utilizados (Dinâmico)</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
            .warning { background: #fff3cd; border-left: 5px solid #ffc107; padding: 20px; margin: 20px 0; }
            .danger { background: #f8d7da; border-left: 5px solid #dc3545; padding: 20px; margin: 20px 0; }
            .info { background: #d1ecf1; border-left: 5px solid #17a2b8; padding: 20px; margin: 20px 0; }
            .success { background: #d4edda; border-left: 5px solid #28a745; padding: 20px; margin: 20px 0; }
            .button { display: inline-block; padding: 15px 30px; margin: 10px 5px; text-decoration: none; 
                     border-radius: 5px; font-weight: bold; border: none; cursor: pointer; }
            .btn-danger { background: #dc3545; color: white; }
            .btn-info { background: #17a2b8; color: white; }
            .btn-success { background: #28a745; color: white; }
            h1 { color: #333; }
            h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        </style>
    </head>
    <body>
        <h1>🎯 Exclusão Inteligente de Vídeos</h1>
        
        <div class="info">
            <h3>🧠 Como funciona:</h3>
            <ol>
                <li>Busca <strong>TODAS as páginas e posts</strong> do site</li>
                <li>Encontra <strong>TODOS os blocos de playlist</strong> (video-playlist-block)</li>
                <li>Extrai <strong>TODOS os IDs de vídeos</strong> que estão sendo usados</li>
                <li>Mantém <strong>APENAS</strong> os vídeos que estão em alguma playlist</li>
                <li>Move os vídeos não utilizados para a lixeira</li>
            </ol>
        </div>
        
        <div class="warning">
            <h3>⚠️ Antes de continuar:</h3>
            <ul>
                <li>✅ Fiz backup completo do banco de dados</li>
                <li>✅ Entendo que vídeos não usados em nenhuma playlist serão movidos para lixeira</li>
                <li>✅ Posso recuperar da lixeira se necessário</li>
            </ul>
        </div>
        
        <h3>Escolha uma ação:</h3>
        <a href="?acao=analisar" class="button btn-info">📊 1. ANALISAR (Ver quais serão excluídos)</a>
        <a href="?acao=excluir" class="button btn-danger">🗑️ 2. EXCLUIR (Mover para lixeira)</a>
        
        <p style="margin-top: 30px; color: #666;">
            <small>Primeiro execute "ANALISAR" para ver o que será feito.</small>
        </p>
    </body>
    </html>
    ');
}

// Carregar WordPress
require_once('wp-load.php');

global $wpdb;

// Função para extrair IDs de vídeos do post_content
function extrair_video_ids($post_content) {
    $video_ids = array();
    
    // Padrão: "videoPostIds":[123,456,789]
    if (preg_match_all('/"videoPostIds"\s*:\s*\[([0-9,\s]+)\]/', $post_content, $matches)) {
        foreach ($matches[1] as $match) {
            // Separar os números
            $ids = preg_split('/[,\s]+/', trim($match), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($ids as $id) {
                if (is_numeric($id) && $id > 0) {
                    $video_ids[] = (int)$id;
                }
            }
        }
    }
    
    return $video_ids;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Exclusão Inteligente de Vídeos</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border-left: 5px solid #28a745; padding: 20px; margin: 20px 0; }
        .error { background: #f8d7da; border-left: 5px solid #dc3545; padding: 20px; margin: 20px 0; }
        .warning { background: #fff3cd; border-left: 5px solid #ffc107; padding: 20px; margin: 20px 0; }
        .info { background: #d1ecf1; border-left: 5px solid #17a2b8; padding: 20px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; position: sticky; top: 0; }
        tr:hover { background: #f8f9fa; }
        tr.excluir { opacity: 0.6; background: #fff3cd; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-top: 40px; }
        .stats { display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap; }
        .stat-box { flex: 1; min-width: 200px; padding: 20px; background: #f8f9fa; border-radius: 5px; text-align: center; }
        .stat-number { font-size: 48px; font-weight: bold; color: #007bff; margin: 10px 0; }
        .stat-label { color: #666; font-size: 14px; }
        .button { display: inline-block; padding: 12px 24px; margin: 10px 5px; text-decoration: none; 
                 border-radius: 5px; font-weight: bold; border: none; cursor: pointer; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .playlist-info { background: #e7f3ff; padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🎯 Exclusão Inteligente de Vídeos - Análise Dinâmica</h1>

<?php

$acao = $_GET['acao'];

// ETAPA 1: Buscar todas as páginas/posts com playlists
echo "<h2>📄 Etapa 1: Buscando Páginas com Playlists</h2>";

$paginas_com_playlists = $wpdb->get_results("
    SELECT ID, post_title, post_type, post_status, post_content
    FROM {$wpdb->posts}
    WHERE post_content LIKE '%video-playlist/playlist-block%'
    AND post_status IN ('publish', 'draft', 'pending', 'private')
    ORDER BY post_title
");

echo "<div class='info'>";
echo "<strong>📊 Encontradas " . count($paginas_com_playlists) . " páginas/posts com playlists de vídeo</strong>";
echo "</div>";

if (count($paginas_com_playlists) > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Título</th><th>Tipo</th><th>Status</th><th>Vídeos na Página</th></tr>";
    foreach ($paginas_com_playlists as $pagina) {
        $video_ids_pagina = extrair_video_ids($pagina->post_content);
        echo "<tr>";
        echo "<td><strong>{$pagina->ID}</strong></td>";
        echo "<td>{$pagina->post_title}</td>";
        echo "<td>{$pagina->post_type}</td>";
        echo "<td>{$pagina->post_status}</td>";
        echo "<td>" . count($video_ids_pagina) . " vídeos</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// ETAPA 2: Extrair TODOS os IDs de vídeos usados
echo "<h2>🎬 Etapa 2: Extraindo IDs de Vídeos</h2>";

$todos_video_ids_usados = array();
$detalhes_por_pagina = array();

foreach ($paginas_com_playlists as $pagina) {
    $video_ids = extrair_video_ids($pagina->post_content);
    
    if (!empty($video_ids)) {
        $todos_video_ids_usados = array_merge($todos_video_ids_usados, $video_ids);
        $detalhes_por_pagina[] = array(
            'pagina_id' => $pagina->ID,
            'pagina_titulo' => $pagina->post_title,
            'video_ids' => $video_ids
        );
    }
}

// Remover duplicatas
$todos_video_ids_usados = array_unique($todos_video_ids_usados);
sort($todos_video_ids_usados);

echo "<div class='playlist-info'>";
echo "<h3>📋 Detalhes das Playlists:</h3>";
foreach ($detalhes_por_pagina as $detalhe) {
    echo "<strong>• {$detalhe['pagina_titulo']}</strong> (ID: {$detalhe['pagina_id']}): " . 
         count($detalhe['video_ids']) . " vídeos - IDs: " . 
         implode(', ', array_slice($detalhe['video_ids'], 0, 10)) . 
         (count($detalhe['video_ids']) > 10 ? '...' : '') . "<br>";
}
echo "</div>";

echo "<div class='success'>";
echo "<h3>✅ Total de vídeos ÚNICOS sendo usados em playlists: <strong>" . count($todos_video_ids_usados) . "</strong></h3>";
echo "<p style='font-size:12px;'><strong>IDs:</strong> " . implode(', ', $todos_video_ids_usados) . "</p>";
echo "</div>";

// ETAPA 3: Buscar TODOS os posts de vídeo
echo "<h2>🎥 Etapa 3: Analisando Todos os Vídeos</h2>";

$todos_videos = $wpdb->get_results("
    SELECT DISTINCT p.ID, p.post_title, p.post_status, p.post_date
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE pm.meta_key = '_vpb_content_type'
    AND pm.meta_value = 'video'
    ORDER BY p.ID
");

$videos_manter = array();
$videos_excluir = array();

foreach ($todos_videos as $video) {
    if (in_array($video->ID, $todos_video_ids_usados)) {
        $videos_manter[] = $video;
    } else {
        $videos_excluir[] = $video;
    }
}

// Estatísticas
echo '<div class="stats">';

echo '<div class="stat-box">';
echo '<div class="stat-number">' . count($todos_videos) . '</div>';
echo '<div class="stat-label">Total de Vídeos</div>';
echo '</div>';

echo '<div class="stat-box">';
echo '<div class="stat-number" style="color:#28a745;">' . count($videos_manter) . '</div>';
echo '<div class="stat-label">Serão Mantidos<br>(em uso nas playlists)</div>';
echo '</div>';

echo '<div class="stat-box">';
echo '<div class="stat-number" style="color:#dc3545;">' . count($videos_excluir) . '</div>';
echo '<div class="stat-label">Serão Excluídos<br>(não estão em nenhuma playlist)</div>';
echo '</div>';

echo '</div>';

// ETAPA 4: Executar exclusão se solicitado
if ($acao === 'excluir' && count($videos_excluir) > 0) {
    
    echo "<h2>🗑️ Etapa 4: Executando Exclusão</h2>";
    
    $ids_excluir = array_map(function($v) { return $v->ID; }, $videos_excluir);
    $ids_string = implode(',', $ids_excluir);
    
    // Soft delete - mover para lixeira
    $resultado = $wpdb->query("
        UPDATE {$wpdb->posts}
        SET post_status = 'trash'
        WHERE ID IN ($ids_string)
        AND post_status != 'trash'
    ");
    
    echo "<div class='success'>";
    echo "<h3>✅ Exclusão Concluída!</h3>";
    echo "<p><strong>$resultado vídeos movidos para a lixeira com sucesso!</strong></p>";
    echo "<p>Os vídeos podem ser recuperados através do WordPress admin se necessário.</p>";
    echo "<p><a href='/wp-admin/edit.php?post_status=trash&post_type=post'>Ver vídeos na lixeira →</a></p>";
    echo "</div>";
    
} elseif ($acao === 'excluir' && count($videos_excluir) == 0) {
    echo "<div class='info'>";
    echo "<h3>ℹ️ Nenhum vídeo para excluir</h3>";
    echo "<p>Todos os vídeos existentes estão sendo utilizados em alguma playlist.</p>";
    echo "</div>";
}

// Mostrar vídeos que serão mantidos
if (count($videos_manter) > 0) {
    echo "<h2>✅ Vídeos Mantidos (" . count($videos_manter) . ")</h2>";
    echo "<p><em>Estes vídeos estão sendo usados em pelo menos uma playlist e serão preservados.</em></p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Título</th><th>Status</th><th>Data</th></tr>";
    foreach ($videos_manter as $video) {
        echo "<tr>";
        echo "<td><strong>{$video->ID}</strong></td>";
        echo "<td>{$video->post_title}</td>";
        echo "<td><span style='color:#28a745;'>●</span> {$video->post_status}</td>";
        echo "<td>" . date('d/m/Y', strtotime($video->post_date)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Mostrar vídeos que serão excluídos
if (count($videos_excluir) > 0) {
    echo "<h2>🗑️ Vídeos que Serão Excluídos (" . count($videos_excluir) . ")</h2>";
    echo "<p><em>Estes vídeos NÃO estão sendo usados em nenhuma playlist do site.</em></p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Título</th><th>Status</th><th>Data</th></tr>";
    foreach ($videos_excluir as $video) {
        echo "<tr class='excluir'>";
        echo "<td><strong>{$video->ID}</strong></td>";
        echo "<td>{$video->post_title}</td>";
        echo "<td>{$video->post_status}</td>";
        echo "<td>" . date('d/m/Y', strtotime($video->post_date)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Botões de ação
if ($acao === 'analisar' && count($videos_excluir) > 0) {
    echo "<div style='margin: 40px 0; padding: 30px; background: #f8f9fa; border-radius: 10px; text-align: center;'>";
    echo "<h3>Pronto para Excluir?</h3>";
    echo "<p>Revise a lista acima. Se estiver tudo correto:</p>";
    echo "<a href='?acao=excluir' class='button btn-danger' onclick='return confirm(\"Tem certeza? " . count($videos_excluir) . " vídeos serão movidos para a lixeira.\")'>🗑️ CONFIRMAR E EXCLUIR</a>";
    echo " <a href='?' class='button btn-success'>↩️ Voltar</a>";
    echo "</div>";
} elseif ($acao === 'excluir') {
    echo "<div style='margin: 40px 0; padding: 30px; background: #d4edda; border-radius: 10px; text-align: center;'>";
    echo "<h3>✅ Concluído!</h3>";
    echo "<p><a href='/canal-bebelume/' class='button btn-success' target='_blank'>🔍 Testar Página Canal Bebelume</a></p>";
    echo "<p><a href='?' class='button btn-success'>🔄 Executar Nova Análise</a></p>";
    echo "<p style='margin-top:20px; font-size:12px; color:#666;'>Lembre-se de deletar este arquivo do servidor por segurança!</p>";
    echo "</div>";
}

?>

</body>
</html>
