<?php
/**
 * Plugin Name: Bebelume NFS-e
 * Description: Emissão automática de NFS-e via TransmiteNota integrada ao PMPro. Emite somente em pagamentos reais (total > 0).
 * Version: 1.1.0
 * Author: Bebelume
 * License: GPL v2 or later
 * Text Domain: bebelume-nfse
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BBL_NFSE_VERSION',   '1.1.0' );
define( 'BBL_NFSE_DIR',       plugin_dir_path( __FILE__ ) );
define( 'BBL_NFSE_URL',       plugin_dir_url( __FILE__ ) );
define( 'BBL_NFSE_BASENAME',  plugin_basename( __FILE__ ) );
define( 'BBL_NFSE_TABLE',     'bebelume_nfse' );

require_once BBL_NFSE_DIR . 'includes/class-api-client.php';
require_once BBL_NFSE_DIR . 'includes/class-nfse-db.php';
require_once BBL_NFSE_DIR . 'includes/class-pmpro-integration.php';
require_once BBL_NFSE_DIR . 'includes/class-email.php';
require_once BBL_NFSE_DIR . 'includes/class-admin.php';

register_activation_hook( __FILE__, [ 'Bebelume_NFSe_DB', 'create_table' ] );

// Limpa o cron ao desativar o plugin (evita evento órfão na wp_options)
register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'bebelume_nfse_check_pending' );
} );

add_action( 'plugins_loaded', function () {
    Bebelume_NFSe_PMPro::init();
    Bebelume_NFSe_Admin::init();
} );

// Cron para checar status de notas pendentes
add_action( 'bebelume_nfse_check_pending', [ 'Bebelume_NFSe_PMPro', 'check_pending_notes' ] );

if ( ! wp_next_scheduled( 'bebelume_nfse_check_pending' ) ) {
    wp_schedule_event( time(), 'hourly', 'bebelume_nfse_check_pending' );
}
