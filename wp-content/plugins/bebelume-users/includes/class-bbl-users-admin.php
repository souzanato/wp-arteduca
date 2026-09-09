<?php
/**
 * Menus e telas do admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBL_Users_Admin {

	const CAPABILITY = 'create_users';
	const SLUG       = 'bebelume-users';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_init', array( $this, 'handle_post' ) );
	}

	/* ---------------------------------------------------------------------
	 * Menu
	 * ------------------------------------------------------------------ */

	public function menu() {
		add_menu_page(
			'Bebelume Users',
			'Bebelume Users',
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render_adicionar' ),
			'dashicons-groups',
			58
		);

		add_submenu_page(
			self::SLUG,
			'Adicionar usuários',
			'Adicionar usuários',
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render_adicionar' )
		);

		$counts  = BBL_Users_DB::count_by_status();
		$pending = (int) $counts[ BBL_Users_DB::STATUS_RASCUNHO ];
		$label   = 'Criar usuários';

		if ( $pending > 0 ) {
			$label .= ' <span class="update-plugins count-' . $pending . '"><span class="plugin-count">' . $pending . '</span></span>';
		}

		add_submenu_page(
			self::SLUG,
			'Criar usuários',
			$label,
			self::CAPABILITY,
			self::SLUG . '-criar',
			array( $this, 'render_criar' )
		);

		add_submenu_page(
			self::SLUG,
			'Configurações',
			'Configurações',
			'manage_options',
			self::SLUG . '-config',
			array( $this, 'render_config' )
		);
	}

	/**
	 * URL de uma das telas do plugin.
	 */
	public static function url( $page = self::SLUG, $args = array() ) {
		$args = array_merge( array( 'page' => $page ), $args );
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/* ---------------------------------------------------------------------
	 * Assets
	 * ------------------------------------------------------------------ */

	public function assets( $hook ) {
		if ( false === strpos( $hook, self::SLUG ) ) {
			return;
		}

		wp_enqueue_style( 'bbl-users-admin', BBL_USERS_URL . 'assets/css/admin.css', array(), BBL_USERS_VERSION );
		wp_enqueue_script( 'bbl-users-admin', BBL_USERS_URL . 'assets/js/admin.js', array( 'jquery' ), BBL_USERS_VERSION, true );

		wp_localize_script(
			'bbl-users-admin',
			'bblUsers',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'bbl_users' ),
				'chunkSize' => (int) apply_filters( 'bbl_users_chunk_size', 5 ),
				'defaults'  => array(
					'novo'      => BBL_Users_Mailer::default_body(),
					'existente' => BBL_Users_Mailer::default_body_existing(),
				),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * POST
	 * ------------------------------------------------------------------ */

	public function handle_post() {
		if ( empty( $_POST['bbl_users_action'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['bbl_users_action'] ) );

		check_admin_referer( 'bbl_users_' . $action );

		switch ( $action ) {
			case 'save_smtp':
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( 'Sem permissão.' );
				}
				BBL_Users_Mailer::save_smtp_options( wp_unslash( $_POST['smtp'] ?? array() ) ); // phpcs:ignore
				BBL_Users_Mailer::save_email_options( wp_unslash( $_POST['email'] ?? array() ) ); // phpcs:ignore
				$this->redirect_config( 'email', 'salvo' );
				break;

			case 'save_password':
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( 'Sem permissão.' );
				}
				BBL_Users_Password::save_options( wp_unslash( $_POST['password'] ?? array() ) ); // phpcs:ignore
				$this->redirect_config( 'senha', 'salvo' );
				break;

			case 'limpar_processados':
				if ( ! current_user_can( self::CAPABILITY ) ) {
					wp_die( 'Sem permissão.' );
				}
				BBL_Users_DB::delete_by_status( BBL_Users_DB::STATUS_CRIADO );
				BBL_Users_DB::delete_by_status( BBL_Users_DB::STATUS_EXISTENTE );
				wp_safe_redirect( self::url( self::SLUG . '-criar', array( 'bbl_msg' => 'limpo' ) ) );
				exit;
		}
	}

	private function redirect_config( $tab, $msg ) {
		wp_safe_redirect(
			self::url(
				self::SLUG . '-config',
				array(
					'tab'     => $tab,
					'bbl_msg' => $msg,
				)
			)
		);
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Telas
	 * ------------------------------------------------------------------ */

	public function render_adicionar() {
		$this->guard( self::CAPABILITY );
		require BBL_USERS_DIR . 'templates/admin-adicionar.php';
	}

	public function render_criar() {
		$this->guard( self::CAPABILITY );
		require BBL_USERS_DIR . 'templates/admin-criar.php';
	}

	public function render_config() {
		$this->guard( 'manage_options' );

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'email'; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! in_array( $tab, array( 'email', 'senha', 'diagnostico' ), true ) ) {
			$tab = 'email';
		}

		require BBL_USERS_DIR . 'templates/admin-config.php';
	}

	private function guard( $cap ) {
		if ( ! current_user_can( $cap ) ) {
			wp_die( 'Você não tem permissão para acessar esta página.' );
		}
	}

	/* ---------------------------------------------------------------------
	 * Helpers de exibição
	 * ------------------------------------------------------------------ */

	public static function status_badge( $status ) {
		$map = array(
			BBL_Users_DB::STATUS_RASCUNHO  => array( 'Rascunho', 'rascunho' ),
			BBL_Users_DB::STATUS_CRIADO    => array( 'Criado', 'criado' ),
			BBL_Users_DB::STATUS_EXISTENTE => array( 'Já existia', 'existente' ),
			BBL_Users_DB::STATUS_ERRO      => array( 'Erro', 'erro' ),
		);

		$item = $map[ $status ] ?? array( $status, 'rascunho' );

		return '<span class="bbl-badge bbl-badge--' . esc_attr( $item[1] ) . '">' . esc_html( $item[0] ) . '</span>';
	}

	/**
	 * Aviso no topo das telas, quando houver.
	 */
	public static function notice() {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( empty( $_GET['bbl_msg'] ) ) {
			return;
		}

		$msg = sanitize_key( wp_unslash( $_GET['bbl_msg'] ) );
		// phpcs:enable

		$map = array(
			'salvo' => array( 'success', 'Configurações salvas.' ),
			'limpo' => array( 'success', 'Linhas já processadas foram removidas da lista.' ),
		);

		if ( ! isset( $map[ $msg ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $map[ $msg ][0] ),
			esc_html( $map[ $msg ][1] )
		);
	}
}
