<?php
/**
 * Plugin Name: Bebelume Users
 * Plugin URI: https://bebelume.com.br
 * Description: Gerenciamento de usuários do Bebelume. Criação em lote com senha gerada automaticamente, envio por SMTP do Gmail e atribuição de nível PMPro.
 * Version: 1.1.1
 * Author: Bebelume
 * Author URI: https://bebelume.com.br
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bebelume-users
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BBL_USERS_VERSION', '1.1.1' );
define( 'BBL_USERS_FILE', __FILE__ );
define( 'BBL_USERS_DIR', plugin_dir_path( __FILE__ ) );
define( 'BBL_USERS_URL', plugin_dir_url( __FILE__ ) );

require_once BBL_USERS_DIR . 'includes/class-bbl-users-crypto.php';
require_once BBL_USERS_DIR . 'includes/class-bbl-users-db.php';
require_once BBL_USERS_DIR . 'includes/class-bbl-users-password.php';
require_once BBL_USERS_DIR . 'includes/class-bbl-users-mailer.php';
require_once BBL_USERS_DIR . 'includes/class-bbl-users-creator.php';
require_once BBL_USERS_DIR . 'includes/class-bbl-users-diagnostics.php';
require_once BBL_USERS_DIR . 'includes/class-bbl-users-admin.php';
require_once BBL_USERS_DIR . 'includes/class-bbl-users-ajax.php';

register_activation_hook( __FILE__, array( 'BBL_Users_DB', 'install' ) );

/**
 * Inicializa o plugin.
 */
function bbl_users_init() {
	BBL_Users_DB::maybe_upgrade();
	BBL_Users_Mailer::instance()->hooks();

	if ( is_admin() ) {
		BBL_Users_Admin::instance()->hooks();
		BBL_Users_Ajax::instance()->hooks();
	}
}
add_action( 'plugins_loaded', 'bbl_users_init' );
