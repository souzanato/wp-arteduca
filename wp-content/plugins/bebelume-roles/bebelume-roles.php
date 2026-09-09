<?php
/**
 * Plugin Name: Bebelume Roles
 * Plugin URI: https://bebelume.com
 * Description: Crie perfis personalizados de acesso ao wp-admin, controlando quais menus e submenus cada perfil pode visualizar.
 * Version: 1.0.0
 * Author: Bebelume
 * Author URI: https://bebelume.com
 * Text Domain: bebelume-roles
 * Domain Path: /languages
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BEBELUME_ROLES_VERSION', '1.0.0' );
define( 'BEBELUME_ROLES_PATH', plugin_dir_path( __FILE__ ) );
define( 'BEBELUME_ROLES_URL', plugin_dir_url( __FILE__ ) );

/**
 * ============================================================
 *  CORE CLASS
 * ============================================================
 */
class Bebelume_Roles {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register custom post type
        add_action( 'init', array( $this, 'register_post_type' ) );

        // Ensure all profile roles exist in WordPress
        add_action( 'init', array( $this, 'register_profile_roles' ), 20 );

        // Admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 5 );

        // Capture menus (late priority)
        add_action( 'admin_menu', array( $this, 'capture_admin_menus' ), 9998 );

        // Filter menus based on profile (very late priority)
        add_action( 'admin_menu', array( $this, 'filter_admin_menus' ), 9999 );

        // Enqueue admin assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Add profile field to user edit page
        add_action( 'show_user_profile', array( $this, 'user_profile_field' ) );
        add_action( 'edit_user_profile', array( $this, 'user_profile_field' ) );
        add_action( 'personal_options_update', array( $this, 'save_user_profile_field' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_profile_field' ) );

        // Add column to users list
        add_filter( 'manage_users_columns', array( $this, 'add_users_column' ) );
        add_filter( 'manage_users_custom_column', array( $this, 'render_users_column' ), 10, 3 );

        // Block direct URL access to restricted pages
        add_action( 'admin_init', array( $this, 'block_restricted_access' ) );

        // Redirect after login to first allowed menu
        add_filter( 'login_redirect', array( $this, 'login_redirect' ), 99, 3 );

        // Sync capabilities on login
        add_action( 'wp_login', array( $this, 'sync_caps_on_login' ), 10, 2 );

        // Activation hook
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->register_post_type();
        flush_rewrite_rules();

        // Remove legacy generic role if it exists
        remove_role( 'bebelume_user' );
    }

    /**
     * Register all profile roles in WordPress on every init
     * This ensures roles always appear in the user role dropdown
     */
    public function register_profile_roles() {
        $profiles = get_posts( array(
            'post_type'      => 'bebelume_profile',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ) );

        if ( empty( $profiles ) ) {
            return;
        }

        foreach ( $profiles as $profile_id ) {
            $role_name = 'bebelume_profile_' . $profile_id;

            // Only create if it doesn't exist yet
            if ( get_role( $role_name ) ) {
                continue;
            }

            $caps = get_post_meta( $profile_id, '_bebelume_capabilities', true );
            if ( ! is_array( $caps ) || empty( $caps ) ) {
                $caps = array( 'read' => true );
            }

            add_role( $role_name, get_the_title( $profile_id ), $caps );
        }
    }

    /**
     * Register CPT for profiles
     */
    public function register_post_type() {
        register_post_type( 'bebelume_profile', array(
            'labels' => array(
                'name'               => __( 'Perfis', 'bebelume-roles' ),
                'singular_name'      => __( 'Perfil', 'bebelume-roles' ),
                'add_new'            => __( 'Criar Perfil', 'bebelume-roles' ),
                'add_new_item'       => __( 'Criar Novo Perfil', 'bebelume-roles' ),
                'edit_item'          => __( 'Editar Perfil', 'bebelume-roles' ),
                'view_item'          => __( 'Ver Perfil', 'bebelume-roles' ),
                'all_items'          => __( 'Todos os Perfis', 'bebelume-roles' ),
                'search_items'       => __( 'Buscar Perfis', 'bebelume-roles' ),
                'not_found'          => __( 'Nenhum perfil encontrado.', 'bebelume-roles' ),
                'not_found_in_trash' => __( 'Nenhum perfil encontrado na lixeira.', 'bebelume-roles' ),
            ),
            'public'       => false,
            'show_ui'      => false, // We handle UI ourselves
            'supports'     => array( 'title' ),
            'show_in_rest' => false,
        ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Bebelume Roles', 'bebelume-roles' ),
            __( 'Bebelume Roles', 'bebelume-roles' ),
            'manage_options',
            'bebelume-roles',
            array( $this, 'render_profiles_page' ),
            'dashicons-groups',
            71
        );

        add_submenu_page(
            'bebelume-roles',
            __( 'Todos os Perfis', 'bebelume-roles' ),
            __( 'Todos os Perfis', 'bebelume-roles' ),
            'manage_options',
            'bebelume-roles',
            array( $this, 'render_profiles_page' )
        );

        add_submenu_page(
            'bebelume-roles',
            __( 'Criar Perfil', 'bebelume-roles' ),
            __( 'Criar Perfil', 'bebelume-roles' ),
            'manage_options',
            'bebelume-roles-new',
            array( $this, 'render_edit_profile_page' )
        );

        add_submenu_page(
            'bebelume-roles',
            __( 'Editar Perfil', 'bebelume-roles' ),
            '',
            'manage_options',
            'bebelume-roles-edit',
            array( $this, 'render_edit_profile_page' )
        );
    }

    /**
     * Capture all registered admin menus
     */
    public function capture_admin_menus() {
        global $menu, $submenu;

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $captured = array();

        if ( ! empty( $menu ) ) {
            foreach ( $menu as $position => $item ) {
                // Skip separators
                if ( empty( $item[0] ) && empty( $item[3] ) ) {
                    continue;
                }

                $menu_slug  = isset( $item[2] ) ? $item[2] : '';
                $menu_title = isset( $item[0] ) ? wp_strip_all_tags( $item[0] ) : '';

                if ( empty( $menu_slug ) || empty( $menu_title ) ) {
                    continue;
                }

                // Remove notification bubbles from title
                $menu_title = preg_replace( '/<span.*<\/span>/', '', $menu_title );
                $menu_title = trim( $menu_title );

                $captured[ $menu_slug ] = array(
                    'title'    => $menu_title,
                    'slug'     => $menu_slug,
                    'icon'     => isset( $item[6] ) ? $item[6] : '',
                    'cap'      => isset( $item[1] ) ? $item[1] : '',
                    'children' => array(),
                );

                // Submenus
                if ( ! empty( $submenu[ $menu_slug ] ) ) {
                    foreach ( $submenu[ $menu_slug ] as $sub_item ) {
                        $sub_title = isset( $sub_item[0] ) ? wp_strip_all_tags( $sub_item[0] ) : '';
                        $sub_slug  = isset( $sub_item[2] ) ? $sub_item[2] : '';

                        $sub_title = preg_replace( '/<span.*<\/span>/', '', $sub_title );
                        $sub_title = trim( $sub_title );

                        if ( empty( $sub_slug ) || empty( $sub_title ) ) {
                            continue;
                        }

                        $captured[ $menu_slug ]['children'][ $sub_slug ] = array(
                            'title'       => $sub_title,
                            'slug'        => $sub_slug,
                            'parent_slug' => $menu_slug,
                            'cap'         => isset( $sub_item[1] ) ? $sub_item[1] : '',
                        );
                    }
                }
            }
        }

        // Store in transient for use in profile editor
        set_transient( 'bebelume_admin_menus', $captured, HOUR_IN_SECONDS );
    }

    /**
     * Filter admin menus based on user's assigned profile
     */
    public function filter_admin_menus() {
        // Admins are never filtered
        if ( current_user_can( 'manage_options' ) ) {
            return;
        }

        $user_id    = get_current_user_id();
        $profile_id = get_user_meta( $user_id, '_bebelume_profile_id', true );

        if ( empty( $profile_id ) ) {
            return;
        }

        $allowed_menus    = get_post_meta( $profile_id, '_bebelume_allowed_menus', true );
        $allowed_submenus = get_post_meta( $profile_id, '_bebelume_allowed_submenus', true );

        if ( ! is_array( $allowed_menus ) ) {
            $allowed_menus = array();
        }
        if ( ! is_array( $allowed_submenus ) ) {
            $allowed_submenus = array();
        }

        global $menu, $submenu;

        // Always allow these
        $always_allowed = array( 'profile.php' );

        if ( ! empty( $menu ) ) {
            foreach ( $menu as $position => $item ) {
                $menu_slug = isset( $item[2] ) ? $item[2] : '';

                if ( in_array( $menu_slug, $always_allowed, true ) ) {
                    continue;
                }

                if ( ! in_array( $menu_slug, $allowed_menus, true ) ) {
                    remove_menu_page( $menu_slug );
                } else {
                    // Filter submenus
                    if ( ! empty( $submenu[ $menu_slug ] ) ) {
                        foreach ( $submenu[ $menu_slug ] as $sub_position => $sub_item ) {
                            $sub_slug = isset( $sub_item[2] ) ? $sub_item[2] : '';
                            $sub_key  = $menu_slug . '::' . $sub_slug;

                            if ( ! in_array( $sub_key, $allowed_submenus, true ) ) {
                                remove_submenu_page( $menu_slug, $sub_slug );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Block direct URL access to restricted menu pages
     */
    public function block_restricted_access() {
        if ( current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
            return;
        }

        $user_id    = get_current_user_id();
        $profile_id = get_user_meta( $user_id, '_bebelume_profile_id', true );

        if ( empty( $profile_id ) ) {
            return;
        }

        $allowed_menus    = get_post_meta( $profile_id, '_bebelume_allowed_menus', true );
        $allowed_submenus = get_post_meta( $profile_id, '_bebelume_allowed_submenus', true );

        if ( ! is_array( $allowed_menus ) ) {
            $allowed_menus = array();
        }
        if ( ! is_array( $allowed_submenus ) ) {
            $allowed_submenus = array();
        }

        // Always allowed
        $always_allowed_pages = array( 'profile.php', 'admin-ajax.php', 'async-upload.php' );

        global $pagenow;

        if ( in_array( $pagenow, $always_allowed_pages, true ) ) {
            return;
        }

        // Check if current page is in allowed menus
        $current_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : $pagenow;

        $is_allowed = false;

        // Check top-level menus
        if ( in_array( $current_page, $allowed_menus, true ) ) {
            $is_allowed = true;
        }

        // Check submenus
        foreach ( $allowed_submenus as $sub_key ) {
            $parts = explode( '::', $sub_key );
            if ( isset( $parts[1] ) && $parts[1] === $current_page ) {
                $is_allowed = true;
                break;
            }
        }

        // Also check pagenow against allowed
        if ( in_array( $pagenow, $allowed_menus, true ) ) {
            $is_allowed = true;
        }

        foreach ( $allowed_submenus as $sub_key ) {
            $parts = explode( '::', $sub_key );
            if ( isset( $parts[1] ) && $parts[1] === $pagenow ) {
                $is_allowed = true;
                break;
            }
        }

        if ( ! $is_allowed ) {
            wp_safe_redirect( $this->get_first_allowed_url( $profile_id ) );
            exit;
        }
    }

    /**
     * Redirect user to first allowed menu after login
     */
    public function login_redirect( $redirect_to, $requested_redirect_to, $user ) {
        if ( is_wp_error( $user ) || ! is_object( $user ) ) {
            return $redirect_to;
        }

        // Don't redirect admins
        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            return $redirect_to;
        }

        $profile_id = get_user_meta( $user->ID, '_bebelume_profile_id', true );

        if ( empty( $profile_id ) ) {
            return $redirect_to;
        }

        return $this->get_first_allowed_url( $profile_id );
    }

    /**
     * Get the URL of the first allowed menu for a profile
     */
    private function get_first_allowed_url( $profile_id ) {
        $allowed_menus    = get_post_meta( $profile_id, '_bebelume_allowed_menus', true );
        $allowed_submenus = get_post_meta( $profile_id, '_bebelume_allowed_submenus', true );

        if ( ! is_array( $allowed_menus ) ) {
            $allowed_menus = array();
        }
        if ( ! is_array( $allowed_submenus ) ) {
            $allowed_submenus = array();
        }

        // Try to find the first allowed submenu (more precise)
        foreach ( $allowed_menus as $menu_slug ) {
            foreach ( $allowed_submenus as $sub_key ) {
                $parts = explode( '::', $sub_key );
                if ( count( $parts ) === 2 && $parts[0] === $menu_slug ) {
                    $sub_slug = $parts[1];
                    if ( strpos( $sub_slug, '.php' ) !== false ) {
                        return admin_url( $sub_slug );
                    }
                    return admin_url( 'admin.php?page=' . $sub_slug );
                }
            }

            // No submenu found, use the menu itself
            if ( strpos( $menu_slug, '.php' ) !== false ) {
                return admin_url( $menu_slug );
            }
            return admin_url( 'admin.php?page=' . $menu_slug );
        }

        // Fallback
        return admin_url( 'profile.php' );
    }

    /**
     * Sync capabilities when user logs in
     */
    public function sync_caps_on_login( $user_login, $user ) {
        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            return;
        }

        $profile_id = get_user_meta( $user->ID, '_bebelume_profile_id', true );
        if ( empty( $profile_id ) ) {
            return;
        }

        $caps = get_post_meta( $profile_id, '_bebelume_capabilities', true );
        if ( ! is_array( $caps ) || empty( $caps ) ) {
            return;
        }

        // Ensure role exists with correct caps
        $role_name = 'bebelume_profile_' . $profile_id;
        remove_role( $role_name );
        add_role( $role_name, get_the_title( $profile_id ), $caps );

        // Force role reload
        global $wp_roles;
        if ( isset( $wp_roles ) ) {
            $wp_roles->reinit();
        }

        // Set role and add caps directly
        $user->set_role( $role_name );
        foreach ( $caps as $cap => $grant ) {
            $user->add_cap( $cap, $grant );
        }
    }

    /**
     * Enqueue admin CSS/JS
     */
    public function enqueue_assets( $hook ) {
        $screens = array(
            'toplevel_page_bebelume-roles',
            'bebelume-roles_page_bebelume-roles-new',
            'bebelume-roles_page_bebelume-roles-edit',
        );

        if ( ! in_array( $hook, $screens, true ) ) {
            return;
        }

        wp_enqueue_style(
            'bebelume-roles-admin',
            BEBELUME_ROLES_URL . 'assets/admin.css',
            array(),
            BEBELUME_ROLES_VERSION
        );

        wp_enqueue_script(
            'bebelume-roles-admin',
            BEBELUME_ROLES_URL . 'assets/admin.js',
            array( 'jquery' ),
            BEBELUME_ROLES_VERSION,
            true
        );
    }

    /**
     * ============================================================
     *  RENDER: Profiles List
     * ============================================================
     */
    public function render_profiles_page() {
        // Handle delete action
        if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['profile_id'] ) ) {
            check_admin_referer( 'bebelume_delete_profile_' . intval( $_GET['profile_id'] ) );
            wp_delete_post( intval( $_GET['profile_id'] ), true );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Perfil excluído com sucesso.', 'bebelume-roles' ) . '</p></div>';
        }

        $profiles = get_posts( array(
            'post_type'      => 'bebelume_profile',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ) );

        ?>
        <div class="wrap bebelume-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Bebelume Roles', 'bebelume-roles' ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=bebelume-roles-new' ) ); ?>" class="page-title-action">
                <?php esc_html_e( 'Criar Perfil', 'bebelume-roles' ); ?>
            </a>
            <hr class="wp-header-end">

            <?php if ( empty( $profiles ) ) : ?>
                <div class="bebelume-empty-state">
                    <div class="bebelume-empty-icon">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <h2><?php esc_html_e( 'Nenhum perfil criado ainda', 'bebelume-roles' ); ?></h2>
                    <p><?php esc_html_e( 'Crie perfis para controlar quais menus do wp-admin cada tipo de usuário pode acessar.', 'bebelume-roles' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bebelume-roles-new' ) ); ?>" class="button button-primary button-hero">
                        <?php esc_html_e( 'Criar Primeiro Perfil', 'bebelume-roles' ); ?>
                    </a>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped bebelume-roles-table">
                    <thead>
                        <tr>
                            <th class="column-title"><?php esc_html_e( 'Perfil', 'bebelume-roles' ); ?></th>
                            <th class="column-description"><?php esc_html_e( 'Descrição', 'bebelume-roles' ); ?></th>
                            <th class="column-menus"><?php esc_html_e( 'Menus Permitidos', 'bebelume-roles' ); ?></th>
                            <th class="column-users"><?php esc_html_e( 'Usuários', 'bebelume-roles' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $profiles as $profile ) :
                            $description    = get_post_meta( $profile->ID, '_bebelume_description', true );
                            $allowed_menus  = get_post_meta( $profile->ID, '_bebelume_allowed_menus', true );
                            $menu_count     = is_array( $allowed_menus ) ? count( $allowed_menus ) : 0;

                            // Count users with this profile
                            $users = get_users( array(
                                'meta_key'   => '_bebelume_profile_id',
                                'meta_value' => $profile->ID,
                            ) );
                            $user_count = count( $users );

                            $edit_url   = admin_url( 'admin.php?page=bebelume-roles-edit&profile_id=' . $profile->ID );
                            $delete_url = wp_nonce_url(
                                admin_url( 'admin.php?page=bebelume-roles&action=delete&profile_id=' . $profile->ID ),
                                'bebelume_delete_profile_' . $profile->ID
                            );
                        ?>
                            <tr>
                                <td class="column-title">
                                    <strong>
                                        <a href="<?php echo esc_url( $edit_url ); ?>">
                                            <?php echo esc_html( $profile->post_title ); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Editar', 'bebelume-roles' ); ?></a> |
                                        </span>
                                        <span class="delete">
                                            <a href="<?php echo esc_url( $delete_url ); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e( 'Tem certeza que deseja excluir este perfil?', 'bebelume-roles' ); ?>');">
                                                <?php esc_html_e( 'Excluir', 'bebelume-roles' ); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td class="column-description">
                                    <?php echo esc_html( $description ); ?>
                                </td>
                                <td class="column-menus">
                                    <span class="bebelume-badge"><?php echo intval( $menu_count ); ?> <?php esc_html_e( 'menus', 'bebelume-roles' ); ?></span>
                                </td>
                                <td class="column-users">
                                    <span class="bebelume-badge bebelume-badge-blue"><?php echo intval( $user_count ); ?> <?php esc_html_e( 'usuários', 'bebelume-roles' ); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * ============================================================
     *  RENDER: Create / Edit Profile
     * ============================================================
     */
    public function render_edit_profile_page() {
        $profile_id  = isset( $_GET['profile_id'] ) ? intval( $_GET['profile_id'] ) : 0;
        $is_editing  = $profile_id > 0;
        $title       = '';
        $description = '';
        $saved_menus    = array();
        $saved_submenus = array();

        if ( $is_editing ) {
            $profile = get_post( $profile_id );
            if ( $profile && 'bebelume_profile' === $profile->post_type ) {
                $title          = $profile->post_title;
                $description    = get_post_meta( $profile_id, '_bebelume_description', true );
                $saved_menus    = get_post_meta( $profile_id, '_bebelume_allowed_menus', true );
                $saved_submenus = get_post_meta( $profile_id, '_bebelume_allowed_submenus', true );
            }
        }

        if ( ! is_array( $saved_menus ) ) {
            $saved_menus = array();
        }
        if ( ! is_array( $saved_submenus ) ) {
            $saved_submenus = array();
        }

        // Handle form submission
        if ( isset( $_POST['bebelume_save_profile'] ) && check_admin_referer( 'bebelume_save_profile_nonce' ) ) {
            $title       = sanitize_text_field( $_POST['profile_title'] );
            $description = sanitize_textarea_field( $_POST['profile_description'] );

            $allowed_menus    = isset( $_POST['allowed_menus'] ) ? array_map( 'sanitize_text_field', $_POST['allowed_menus'] ) : array();
            $allowed_submenus = isset( $_POST['allowed_submenus'] ) ? array_map( 'sanitize_text_field', $_POST['allowed_submenus'] ) : array();

            $post_data = array(
                'post_title'  => $title,
                'post_type'   => 'bebelume_profile',
                'post_status' => 'publish',
            );

            if ( $is_editing ) {
                $post_data['ID'] = $profile_id;
                wp_update_post( $post_data );
            } else {
                $profile_id = wp_insert_post( $post_data );
                $is_editing = true;
            }

            if ( $profile_id && ! is_wp_error( $profile_id ) ) {
                update_post_meta( $profile_id, '_bebelume_description', $description );
                update_post_meta( $profile_id, '_bebelume_allowed_menus', $allowed_menus );
                update_post_meta( $profile_id, '_bebelume_allowed_submenus', $allowed_submenus );

                // Update WordPress role capabilities based on selected menus
                $this->sync_profile_capabilities( $profile_id, $allowed_menus, $allowed_submenus );

                $saved_menus    = $allowed_menus;
                $saved_submenus = $allowed_submenus;

                echo '<div class="notice notice-success is-dismissible"><p>';
                esc_html_e( 'Perfil salvo com sucesso!', 'bebelume-roles' );
                echo '</p></div>';

                // Debug: show detected capabilities
                $debug = get_post_meta( $profile_id, '_bebelume_caps_debug', true );
                if ( $debug && ! empty( $debug['caps'] ) ) {
                    echo '<div class="notice notice-info is-dismissible"><p><strong>Debug — Capabilities detectadas:</strong><br>';
                    echo esc_html( implode( ', ', $debug['caps'] ) );
                    echo '<br><strong>Role:</strong> bebelume_profile_' . intval( $profile_id );
                    echo '</p></div>';
                }
            }
        }

        // Get all registered admin menus
        $all_menus = get_transient( 'bebelume_admin_menus' );
        if ( ! is_array( $all_menus ) ) {
            $all_menus = array();
        }

        // Remove our own plugin from the list
        unset( $all_menus['bebelume-roles'] );

        ?>
        <div class="wrap bebelume-wrap">
            <h1>
                <?php
                if ( $is_editing ) {
                    esc_html_e( 'Editar Perfil', 'bebelume-roles' );
                } else {
                    esc_html_e( 'Criar Novo Perfil', 'bebelume-roles' );
                }
                ?>
            </h1>
            <hr class="wp-header-end">

            <form method="post" class="bebelume-profile-form">
                <?php wp_nonce_field( 'bebelume_save_profile_nonce' ); ?>

                <div class="bebelume-form-section">
                    <h2><?php esc_html_e( 'Informações do Perfil', 'bebelume-roles' ); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="profile_title"><?php esc_html_e( 'Título', 'bebelume-roles' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="profile_title" name="profile_title"
                                       value="<?php echo esc_attr( $title ); ?>"
                                       class="regular-text" required
                                       placeholder="<?php esc_attr_e( 'Ex: Editor de Conteúdo', 'bebelume-roles' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="profile_description"><?php esc_html_e( 'Descrição', 'bebelume-roles' ); ?></label>
                            </th>
                            <td>
                                <textarea id="profile_description" name="profile_description"
                                          class="large-text" rows="3"
                                          placeholder="<?php esc_attr_e( 'Descreva o propósito deste perfil...', 'bebelume-roles' ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="bebelume-form-section">
                    <h2><?php esc_html_e( 'Menus Permitidos', 'bebelume-roles' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Selecione quais menus e submenus do wp-admin este perfil poderá acessar. O menu "Perfil" do usuário é sempre visível.', 'bebelume-roles' ); ?>
                    </p>

                    <div class="bebelume-menu-controls">
                        <button type="button" class="button bebelume-select-all"><?php esc_html_e( 'Selecionar Todos', 'bebelume-roles' ); ?></button>
                        <button type="button" class="button bebelume-deselect-all"><?php esc_html_e( 'Desmarcar Todos', 'bebelume-roles' ); ?></button>
                    </div>

                    <?php if ( empty( $all_menus ) ) : ?>
                        <div class="notice notice-warning inline">
                            <p>
                                <?php esc_html_e( 'Nenhum menu foi capturado ainda. Recarregue esta página para capturar os menus disponíveis.', 'bebelume-roles' ); ?>
                            </p>
                        </div>
                    <?php else : ?>
                        <div class="bebelume-menus-grid">
                            <?php foreach ( $all_menus as $menu_slug => $menu_data ) :
                                $menu_checked = in_array( $menu_slug, $saved_menus, true );
                                $icon_class = '';
                                if ( ! empty( $menu_data['icon'] ) && strpos( $menu_data['icon'], 'dashicons' ) !== false ) {
                                    $icon_class = $menu_data['icon'];
                                }
                            ?>
                                <div class="bebelume-menu-card <?php echo $menu_checked ? 'is-active' : ''; ?>">
                                    <div class="bebelume-menu-header">
                                        <label class="bebelume-menu-label">
                                            <input type="checkbox"
                                                   name="allowed_menus[]"
                                                   value="<?php echo esc_attr( $menu_slug ); ?>"
                                                   class="bebelume-menu-checkbox"
                                                   <?php checked( $menu_checked ); ?>>
                                            <?php if ( $icon_class ) : ?>
                                                <span class="dashicons <?php echo esc_attr( $icon_class ); ?>"></span>
                                            <?php endif; ?>
                                            <span class="bebelume-menu-title"><?php echo esc_html( $menu_data['title'] ); ?></span>
                                        </label>
                                        <?php if ( ! empty( $menu_data['children'] ) ) : ?>
                                            <button type="button" class="bebelume-toggle-children" aria-expanded="false">
                                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ( ! empty( $menu_data['children'] ) ) : ?>
                                        <div class="bebelume-submenu-list" style="display:none;">
                                            <?php foreach ( $menu_data['children'] as $sub_slug => $sub_data ) :
                                                $sub_key     = $menu_slug . '::' . $sub_slug;
                                                $sub_checked = in_array( $sub_key, $saved_submenus, true );
                                            ?>
                                                <label class="bebelume-submenu-label">
                                                    <input type="checkbox"
                                                           name="allowed_submenus[]"
                                                           value="<?php echo esc_attr( $sub_key ); ?>"
                                                           class="bebelume-submenu-checkbox"
                                                           <?php checked( $sub_checked ); ?>>
                                                    <span><?php echo esc_html( $sub_data['title'] ); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <p class="submit">
                    <input type="submit" name="bebelume_save_profile" class="button button-primary button-large"
                           value="<?php echo $is_editing ? esc_attr__( 'Atualizar Perfil', 'bebelume-roles' ) : esc_attr__( 'Criar Perfil', 'bebelume-roles' ); ?>">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bebelume-roles' ) ); ?>" class="button button-secondary">
                        <?php esc_html_e( 'Cancelar', 'bebelume-roles' ); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Sync capabilities for profile's role
     */
    public function sync_profile_capabilities( $profile_id, $allowed_menus, $allowed_submenus ) {
        global $menu, $submenu;

        // Start with basic read capability
        $capabilities = array( 'read' => true );

        // === SOURCE 1: Read capabilities directly from WordPress global $menu ===
        // This is the most reliable source — available during admin page load
        if ( ! empty( $menu ) ) {
            // Build a map: menu_slug => capability from the live global $menu
            $live_caps = array();
            foreach ( $menu as $item ) {
                $slug = isset( $item[2] ) ? $item[2] : '';
                $cap  = isset( $item[1] ) ? $item[1] : '';
                if ( $slug && $cap ) {
                    $live_caps[ $slug ] = $cap;
                }
            }

            // Add capabilities for each allowed top-level menu
            foreach ( $allowed_menus as $menu_slug ) {
                if ( isset( $live_caps[ $menu_slug ] ) ) {
                    $capabilities[ $live_caps[ $menu_slug ] ] = true;
                }
            }

            // Add capabilities for each allowed submenu
            foreach ( $allowed_submenus as $sub_key ) {
                $parts = explode( '::', $sub_key );
                if ( count( $parts ) !== 2 ) {
                    continue;
                }
                $parent_slug = $parts[0];
                $sub_slug    = $parts[1];

                if ( ! empty( $submenu[ $parent_slug ] ) ) {
                    foreach ( $submenu[ $parent_slug ] as $sub_item ) {
                        if ( isset( $sub_item[2] ) && $sub_item[2] === $sub_slug && ! empty( $sub_item[1] ) ) {
                            $capabilities[ $sub_item[1] ] = true;
                        }
                    }
                }
            }
        }

        // === SOURCE 2: Hardcoded fallback for core WordPress menus ===
        $menu_cap_map = array(
            'index.php'              => array( 'read' ),
            'edit.php'               => array( 'edit_posts', 'publish_posts', 'delete_posts' ),
            'upload.php'             => array( 'upload_files' ),
            'edit.php?post_type=page'=> array( 'edit_pages', 'publish_pages', 'delete_pages', 'edit_others_pages' ),
            'edit-comments.php'      => array( 'moderate_comments', 'edit_posts' ),
            'themes.php'             => array( 'switch_themes', 'edit_theme_options' ),
            'plugins.php'            => array( 'activate_plugins' ),
            'users.php'              => array( 'list_users', 'promote_users' ),
            'tools.php'              => array( 'edit_posts' ),
            'options-general.php'    => array( 'manage_options' ),
        );

        foreach ( $allowed_menus as $menu_slug ) {
            if ( isset( $menu_cap_map[ $menu_slug ] ) ) {
                foreach ( $menu_cap_map[ $menu_slug ] as $cap ) {
                    $capabilities[ $cap ] = true;
                }
            }
        }

        // Store capabilities in post meta (useful for debugging)
        update_post_meta( $profile_id, '_bebelume_capabilities', $capabilities );

        // Also store a debug log of detected caps
        update_post_meta( $profile_id, '_bebelume_caps_debug', array(
            'caps'       => array_keys( $capabilities ),
            'menus'      => $allowed_menus,
            'submenus'   => $allowed_submenus,
            'updated_at' => current_time( 'mysql' ),
        ) );

        // Create/update the WordPress role
        $role_name = 'bebelume_profile_' . $profile_id;
        remove_role( $role_name );
        add_role( $role_name, get_the_title( $profile_id ), $capabilities );
    }

    /**
     * Add Bebelume Profile field to user profile
     */
    public function user_profile_field( $user ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $profiles = get_posts( array(
            'post_type'      => 'bebelume_profile',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ) );

        $current_profile = get_user_meta( $user->ID, '_bebelume_profile_id', true );
        ?>
        <h3><?php esc_html_e( 'Bebelume Profile', 'bebelume-roles' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="bebelume_profile_id"><?php esc_html_e( 'Perfil de Acesso', 'bebelume-roles' ); ?></label></th>
                <td>
                    <select name="bebelume_profile_id" id="bebelume_profile_id">
                        <option value=""><?php esc_html_e( '— Nenhum (acesso padrão) —', 'bebelume-roles' ); ?></option>
                        <?php foreach ( $profiles as $profile ) : ?>
                            <option value="<?php echo esc_attr( $profile->ID ); ?>"
                                    <?php selected( $current_profile, $profile->ID ); ?>>
                                <?php echo esc_html( $profile->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'Selecione um perfil para restringir os menus visíveis para este usuário.', 'bebelume-roles' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save Bebelume Profile field on user update
     */
    public function save_user_profile_field( $user_id ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_POST['bebelume_profile_id'] ) ) {
            $profile_id = intval( $_POST['bebelume_profile_id'] );
            if ( $profile_id > 0 ) {
                update_user_meta( $user_id, '_bebelume_profile_id', $profile_id );

                $user = get_userdata( $user_id );
                if ( $user && ! in_array( 'administrator', $user->roles, true ) ) {
                    $caps = get_post_meta( $profile_id, '_bebelume_capabilities', true );
                    if ( is_array( $caps ) ) {
                        $role_name = 'bebelume_profile_' . $profile_id;

                        // Recreate role with latest caps
                        remove_role( $role_name );
                        add_role( $role_name, get_the_title( $profile_id ), $caps );

                        // Force WordPress to reload roles from DB
                        global $wp_roles;
                        if ( isset( $wp_roles ) ) {
                            $wp_roles->reinit();
                        }

                        // Assign role to user
                        $user->set_role( $role_name );

                        // Belt and suspenders: also grant capabilities directly to user
                        foreach ( $caps as $cap => $grant ) {
                            $user->add_cap( $cap, $grant );
                        }
                    }
                }
            } else {
                // Remove profile assignment
                $old_profile = get_user_meta( $user_id, '_bebelume_profile_id', true );
                delete_user_meta( $user_id, '_bebelume_profile_id' );

                // Reset to subscriber if they had a bebelume role
                $user = get_userdata( $user_id );
                if ( $user ) {
                    foreach ( (array) $user->roles as $role ) {
                        if ( strpos( $role, 'bebelume_profile_' ) === 0 ) {
                            $user->set_role( 'subscriber' );
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Add custom column to Users list
     */
    public function add_users_column( $columns ) {
        $columns['bebelume_profile'] = __( 'Perfil Bebelume', 'bebelume-roles' );
        return $columns;
    }

    /**
     * Render custom column in Users list
     */
    public function render_users_column( $value, $column_name, $user_id ) {
        if ( 'bebelume_profile' === $column_name ) {
            $profile_id = get_user_meta( $user_id, '_bebelume_profile_id', true );
            if ( $profile_id ) {
                $profile = get_post( $profile_id );
                if ( $profile ) {
                    return '<span class="bebelume-badge">' . esc_html( $profile->post_title ) . '</span>';
                }
            }
            return '—';
        }
        return $value;
    }

    /**
     * Get all profiles (helper)
     */
    public static function get_profiles() {
        return get_posts( array(
            'post_type'      => 'bebelume_profile',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ) );
    }
}

// Initialize
Bebelume_Roles::get_instance();
