<?php
/**
 * Bebelume Plyr — render.php
 * Gera o HTML do player com as opções configuradas no editor.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$a = $attributes;

// ── URL / provider ──────────────────────────────────────────────────────────
$video_url  = ! empty( $a['videoUrl'] )  ? esc_url( $a['videoUrl'] )  : '';
$video_type = ! empty( $a['videoType'] ) ? $a['videoType']            : 'html5';
$poster_url = ! empty( $a['posterUrl'] ) ? esc_url( $a['posterUrl'] ) : '';
$title      = ! empty( $a['title'] )     ? esc_attr( $a['title'] )    : '';

// Se o tipo for vimeo/youtube mas a URL parecer um arquivo direto (.mp4, .webm),
// trata como html5 para o Plyr não tentar usar o embed da API
if ( in_array( $video_type, array( 'vimeo', 'youtube' ) ) ) {
    if ( preg_match( '/\.(mp4|webm|ogg|mov)(\?|$)/i', $video_url ) ) {
        $video_type = 'html5';
    }
}

if ( empty( $video_url ) ) {
    echo '<div style="padding:2em;text-align:center;color:#999;border:2px dashed #ccc;border-radius:4px;">'
       . '🎬 Nenhum vídeo selecionado.</div>';
    return;
}

// ── Controles ───────────────────────────────────────────────────────────────
$controls = array();
if ( ! empty( $a['ctrlPlayLarge'] ) )   $controls[] = 'play-large';
if ( ! empty( $a['ctrlRestart'] ) )     $controls[] = 'restart';
if ( ! empty( $a['ctrlRewind'] ) )      $controls[] = 'rewind';
if ( ! empty( $a['ctrlPlay'] ) )        $controls[] = 'play';
if ( ! empty( $a['ctrlFastFwd'] ) )     $controls[] = 'fast-forward';
if ( ! empty( $a['ctrlProgress'] ) )    $controls[] = 'progress';
if ( ! empty( $a['ctrlCurrentTime'] ) ) $controls[] = 'current-time';
if ( ! empty( $a['ctrlDuration'] ) )    $controls[] = 'duration';
if ( ! empty( $a['ctrlMute'] ) )        $controls[] = 'mute';
if ( ! empty( $a['ctrlVolume'] ) )      $controls[] = 'volume';
if ( ! empty( $a['ctrlCaptions'] ) )    $controls[] = 'captions';
if ( ! empty( $a['ctrlSettings'] ) )    $controls[] = 'settings';
if ( ! empty( $a['ctrlPip'] ) )         $controls[] = 'pip';
if ( ! empty( $a['ctrlAirplay'] ) )     $controls[] = 'airplay';
if ( ! empty( $a['ctrlFullscreen'] ) )  $controls[] = 'fullscreen';

// ── Config Plyr (JSON para data-plyr-config) ─────────────────────────────────
$plyr_config = array(
    'controls'     => $controls,
    'autoplay'     => ! empty( $a['autoplay'] ),
    'muted'        => ! empty( $a['muted'] ),
    'loop'         => array( 'active' => ! empty( $a['loop'] ) ),
    'autopause'    => isset( $a['autopause'] )   ? (bool) $a['autopause']   : true,
    'playsinline'  => isset( $a['playsinline'] ) ? (bool) $a['playsinline'] : true,
    'clickToPlay'  => isset( $a['clickToPlay'] ) ? (bool) $a['clickToPlay'] : true,
    'hideControls' => isset( $a['hideControls'] )? (bool) $a['hideControls']: true,
    'seekTime'     => isset( $a['seekTime'] )    ? (int)  $a['seekTime']    : 10,
    'volume'       => isset( $a['volume'] )      ? (float)$a['volume']      : 1,
    'speed'        => array(
        'selected' => isset( $a['speed'] ) ? (float) $a['speed'] : 1,
        'options'  => array( 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2 ),
    ),
    'ratio'        => ! empty( $a['ratio'] ) ? $a['ratio'] : '16:9',
    'i18n'         => array(
        'restart'         => 'Reiniciar',
        'rewind'          => 'Retroceder {seektime}s',
        'play'            => 'Reproduzir',
        'pause'           => 'Pausar',
        'fastForward'     => 'Avançar {seektime}s',
        'seek'            => 'Buscar',
        'seekLabel'       => '{currentTime} de {duration}',
        'played'          => 'Reproduzido',
        'buffered'        => 'Carregado',
        'currentTime'     => 'Tempo atual',
        'duration'        => 'Duração',
        'volume'          => 'Volume',
        'mute'            => 'Mudo',
        'unmute'          => 'Ativar som',
        'enableCaptions'  => 'Ativar legendas',
        'disableCaptions' => 'Desativar legendas',
        'enterFullscreen' => 'Tela cheia',
        'exitFullscreen'  => 'Sair da tela cheia',
        'frameTitle'      => 'Player para {title}',
        'captions'        => 'Legendas',
        'settings'        => 'Configurações',
        'pip'             => 'Picture-in-Picture',
        'menuBack'        => 'Voltar',
        'speed'           => 'Velocidade',
        'normal'          => 'Normal',
        'quality'         => 'Qualidade',
        'loop'            => 'Loop',
        'start'           => 'Início',
        'end'             => 'Fim',
        'all'             => 'Tudo',
        'reset'           => 'Redefinir',
        'disabled'        => 'Desativado',
        'enabled'         => 'Ativado',
        'advertisement'   => 'Anúncio',
    ),
);

$plyr_config_json = esc_attr( wp_json_encode( $plyr_config ) );

// ── CSS vars de cor (inline no wrapper) ─────────────────────────────────────
$css_vars = array();
if ( ! empty( $a['colorMain'] ) )           $css_vars[] = '--plyr-color-main:' . esc_attr( $a['colorMain'] );
if ( ! empty( $a['colorControl'] ) )        $css_vars[] = '--plyr-video-control-color:' . esc_attr( $a['colorControl'] );
if ( ! empty( $a['colorControlHover'] ) )   $css_vars[] = '--plyr-video-control-color-hover:' . esc_attr( $a['colorControlHover'] );
if ( ! empty( $a['colorControlBgHover'] ) ) $css_vars[] = '--plyr-video-control-background-hover:' . esc_attr( $a['colorControlBgHover'] );
if ( ! empty( $a['colorProgress'] ) )       $css_vars[] = '--plyr-range-fill-background:' . esc_attr( $a['colorProgress'] );

$style_attr = ! empty( $css_vars ) ? ' style="' . implode( ';', $css_vars ) . '"' : '';

// ── Gera ID único para este player ──────────────────────────────────────────
static $bbl_plyr_count = 0;
$bbl_plyr_count++;
$player_id = 'bbl-plyr-' . $bbl_plyr_count;

// ── HTML ────────────────────────────────────────────────────────────────────
$ratio       = ! empty( $a['ratio'] ) ? $a['ratio'] : '16:9';
$ratio_class = 'bbl-ratio-' . str_replace( ':', 'x', $ratio ); // ex: bbl-ratio-16x9

$wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'bbl-plyr-wrapper ' . $ratio_class ) );
?>
<div <?php echo $wrapper_attrs; ?><?php echo $style_attr; ?>>
<?php if ( $video_type === 'html5' ) : ?>
    <video
        id="<?php echo esc_attr( $player_id ); ?>"
        class="bbl-plyr-video"
        playsinline
        controls
        <?php echo $poster_url ? 'poster="' . $poster_url . '" data-poster="' . $poster_url . '"' : ''; ?>
        <?php echo $title      ? 'title="' . $title . '"' : ''; ?>
        data-ratio="<?php echo esc_attr( ! empty( $a['ratio'] ) ? $a['ratio'] : '16:9' ); ?>"
        data-plyr-config='<?php echo $plyr_config_json; ?>'
    >
        <source src="<?php echo $video_url; ?>" type="video/mp4" />
    </video>

<?php elseif ( $video_type === 'youtube' || $video_type === 'vimeo' ) :
    // Extrai o ID do embed se for URL completa
    $embed_id = $video_url;
    if ( $video_type === 'youtube' ) {
        if ( preg_match( '/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video_url, $m ) ) {
            $embed_id = $m[1];
        }
    } elseif ( $video_type === 'vimeo' ) {
        // Tenta extrair ID numérico de qualquer URL do Vimeo
        // Formatos: vimeo.com/123456, player.vimeo.com/video/123456,
        //           progressive_redirect/playback/123456/...
        if ( preg_match( '/(?:vimeo\.com\/(?:video\/)?|playback\/)(\d+)/', $video_url, $m ) ) {
            $embed_id = $m[1];
        }
        // Se ainda for uma URL longa (arquivo direto .mp4), usa como html5 embed
        // O Plyr aceita URL direta como embed_id quando não é ID numérico
    }
?>
    <div
        id="<?php echo esc_attr( $player_id ); ?>"
        class="bbl-plyr-embed plyr__video-embed"
        data-plyr-provider="<?php echo esc_attr( $video_type ); ?>"
        data-plyr-embed-id="<?php echo esc_attr( $embed_id ); ?>"
        data-plyr-config='<?php echo $plyr_config_json; ?>'
        <?php echo $title ? 'title="' . $title . '"' : ''; ?>
    ></div>
<?php endif; ?>
</div>
