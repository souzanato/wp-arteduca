<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'bpp_add_menu' );
function bpp_add_menu() {
    add_menu_page(
        'Bebelume Popups',
        'Bebelume Popups',
        'manage_options',
        'bpp-popups',
        'bpp_page_list',
        'dashicons-welcome-widgets-menus',
        30
    );

    add_submenu_page(
        'bpp-popups',
        'Popups',
        'Popups',
        'manage_options',
        'bpp-popups',
        'bpp_page_list'
    );

    add_submenu_page(
        'bpp-popups',
        'Criar Popup',
        'Criar Popup',
        'manage_options',
        'bpp-create',
        'bpp_page_create'
    );
}

/* ───────────────────────────── ASSETS ───────────────────────────── */
add_action( 'admin_enqueue_scripts', 'bpp_admin_assets' );
function bpp_admin_assets( $hook ) {
    $pages = [ 'toplevel_page_bpp-popups', 'bebelume-popups_page_bpp-create' ];
    if ( ! in_array( $hook, $pages ) ) return;

    // Monaco Editor via CDN
    wp_enqueue_script(
        'monaco-loader',
        'https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs/loader.js',
        [],
        null,
        true
    );

    wp_enqueue_style(  'bpp-admin', BPP_URL . 'admin/css/admin.css', [], BPP_VERSION );
    wp_enqueue_script( 'bpp-admin', BPP_URL . 'admin/js/admin.js', [ 'monaco-loader', 'jquery' ], BPP_VERSION, true );

    wp_localize_script( 'bpp-admin', 'BPP', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'bpp_nonce' ),
        'monaco_base' => 'https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs',
    ]);
}

/* ───────────────────────────── AJAX: SAVE ───────────────────────────── */
add_action( 'wp_ajax_bpp_save_popup', 'bpp_ajax_save_popup' );
function bpp_ajax_save_popup() {
    check_ajax_referer( 'bpp_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

    $id          = isset( $_POST['popup_id'] ) ? intval( $_POST['popup_id'] ) : 0;
    $title       = sanitize_text_field( $_POST['popup_title'] ?? '' );
    $description = sanitize_textarea_field( $_POST['popup_description'] ?? '' );
    $html_code   = wp_kses_post( $_POST['popup_code'] ?? '' );
    $css_code    = sanitize_textarea_field( $_POST['popup_css'] ?? '' );
    $backdrop    = (int) ( $_POST['popup_backdrop'] ?? 0 );
    $opacity     = floatval( $_POST['popup_opacity'] ?? 0.5 );
    $position    = sanitize_text_field( $_POST['popup_position'] ?? 'center-center' );

    $post_data = [
        'post_title'  => $title,
        'post_type'   => 'bpp_popup',
        'post_status' => 'publish',
    ];
    if ( $id ) $post_data['ID'] = $id;

    $post_id = $id ? wp_update_post( $post_data ) : wp_insert_post( $post_data );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( 'Erro ao salvar.' );
    }

    update_post_meta( $post_id, '_bpp_description', $description );
    update_post_meta( $post_id, '_bpp_code',        $html_code );
    update_post_meta( $post_id, '_bpp_css',         $css_code );
    update_post_meta( $post_id, '_bpp_backdrop',    $backdrop );
    update_post_meta( $post_id, '_bpp_opacity',     $opacity );
    update_post_meta( $post_id, '_bpp_position',    $position );

    // PMPro: save excluded membership levels
    $exclude_levels = isset( $_POST['pmpro_exclude_levels'] )
        ? array_map( 'intval', (array) $_POST['pmpro_exclude_levels'] )
        : [];
    update_post_meta( $post_id, '_bpp_pmpro_exclude_levels', $exclude_levels );

    wp_send_json_success( [ 'id' => $post_id ] );
}

/* ───────────────────────────── AJAX: DELETE ───────────────────────────── */
add_action( 'wp_ajax_bpp_delete_popup', 'bpp_ajax_delete_popup' );
function bpp_ajax_delete_popup() {
    check_ajax_referer( 'bpp_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

    $id = intval( $_POST['popup_id'] ?? 0 );
    if ( $id ) {
        wp_delete_post( $id, true );
        wp_send_json_success();
    }
    wp_send_json_error();
}

/* ───────────────────────────── AJAX: GET ONE ───────────────────────────── */
add_action( 'wp_ajax_bpp_get_popup', 'bpp_ajax_get_popup' );
function bpp_ajax_get_popup() {
    check_ajax_referer( 'bpp_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

    $id   = intval( $_GET['popup_id'] ?? 0 );
    $post = get_post( $id );
    if ( ! $post || $post->post_type !== 'bpp_popup' ) wp_send_json_error();

    wp_send_json_success([
        'id'          => $post->ID,
        'title'       => $post->post_title,
        'description' => get_post_meta( $id, '_bpp_description', true ),
        'code'        => get_post_meta( $id, '_bpp_code',        true ),
        'css'         => get_post_meta( $id, '_bpp_css',         true ),
        'backdrop'    => (int) get_post_meta( $id, '_bpp_backdrop', true ),
        'opacity'     => get_post_meta( $id, '_bpp_opacity',    true ),
        'position'    => get_post_meta( $id, '_bpp_position',   true ),
    ]);
}

/* ───────────────────────────── PAGE: LIST ───────────────────────────── */
function bpp_page_list() {
    $popups = get_posts([
        'post_type'      => 'bpp_popup',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    ?>
    <div class="wrap bpp-wrap">
        <div class="bpp-header">
            <h1>Bebelume Popups</h1>
            <a href="<?php echo admin_url('admin.php?page=bpp-create'); ?>" class="bpp-btn bpp-btn-primary">+ Criar Popup</a>
        </div>

        <?php if ( empty( $popups ) ) : ?>
            <div class="bpp-empty">
                <span class="dashicons dashicons-welcome-widgets-menus"></span>
                <p>Nenhum popup criado ainda.</p>
                <a href="<?php echo admin_url('admin.php?page=bpp-create'); ?>" class="bpp-btn bpp-btn-primary">Criar primeiro popup</a>
            </div>
        <?php else : ?>
            <table class="bpp-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Descrição</th>
                        <th>Posição</th>
                        <th>Backdrop</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $popups as $popup ) :
                    $position = get_post_meta( $popup->ID, '_bpp_position', true );
                    $backdrop = get_post_meta( $popup->ID, '_bpp_backdrop', true );
                    $opacity  = get_post_meta( $popup->ID, '_bpp_opacity', true );
                    $desc     = get_post_meta( $popup->ID, '_bpp_description', true );
                    $pos_labels = bpp_position_labels();
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html( $popup->post_title ); ?></strong></td>
                        <td><?php echo esc_html( $desc ); ?></td>
                        <td><span class="bpp-badge"><?php echo esc_html( $pos_labels[ $position ] ?? $position ); ?></span></td>
                        <td><?php echo $backdrop ? '<span class="bpp-badge bpp-badge-blue">Sim — ' . esc_html( $opacity ) . '</span>' : '<span class="bpp-badge bpp-badge-gray">Não</span>'; ?></td>
                        <td class="bpp-actions">
                            <a href="<?php echo admin_url( 'admin.php?page=bpp-create&edit=' . $popup->ID ); ?>" class="bpp-btn bpp-btn-sm">Editar</a>
                            <button class="bpp-btn bpp-btn-sm bpp-btn-danger bpp-delete" data-id="<?php echo $popup->ID; ?>">Excluir</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/* ───────────────────────────── PAGE: CREATE/EDIT ───────────────────────────── */
function bpp_page_create() {
    $edit_id = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : 0;
    $popup   = $edit_id ? get_post( $edit_id ) : null;

    $data = [
        'id'          => 0,
        'title'       => '',
        'description' => '',
        'code'        => '',
        'css'         => '',
        'backdrop'    => 0,
        'opacity'     => '0.5',
        'position'    => 'center-center',
    ];

    if ( $popup && $popup->post_type === 'bpp_popup' ) {
        $data = [
            'id'          => $popup->ID,
            'title'       => $popup->post_title,
            'description' => get_post_meta( $popup->ID, '_bpp_description', true ),
            'code'        => get_post_meta( $popup->ID, '_bpp_code',        true ),
            'css'         => get_post_meta( $popup->ID, '_bpp_css',         true ),
            'backdrop'    => (int) get_post_meta( $popup->ID, '_bpp_backdrop', true ),
            'opacity'     => get_post_meta( $popup->ID, '_bpp_opacity',    true ) ?: '0.5',
            'position'    => get_post_meta( $popup->ID, '_bpp_position',   true ) ?: 'center-center',
        ];
    }

    $pos_labels = bpp_position_labels();
    ?>
    <div class="wrap bpp-wrap">
        <div class="bpp-header">
            <h1><?php echo $data['id'] ? 'Editar Popup' : 'Criar Popup'; ?></h1>
            <a href="<?php echo admin_url('admin.php?page=bpp-popups'); ?>" class="bpp-btn">← Voltar</a>
        </div>

        <div class="bpp-form-card">
            <input type="hidden" id="bpp-popup-id" value="<?php echo esc_attr( $data['id'] ); ?>">

            <div class="bpp-field">
                <label for="bpp-title">Título <span class="bpp-required">*</span></label>
                <input type="text" id="bpp-title" value="<?php echo esc_attr( $data['title'] ); ?>" placeholder="Nome identificador do popup">
            </div>

            <div class="bpp-field">
                <label for="bpp-description">Descrição</label>
                <textarea id="bpp-description" rows="3" placeholder="Para que serve este popup..."><?php echo esc_textarea( $data['description'] ); ?></textarea>
            </div>

            <div class="bpp-field">
                <label>Código HTML</label>
                <p class="bpp-hint">💡 Para fechar o popup ao clicar, adicione a classe <code>close-btn</code> a qualquer <code>&lt;a&gt;</code> ou <code>&lt;button&gt;</code>. Exemplo: <code>&lt;a href="#" class="btn close-btn"&gt;Fechar&lt;/a&gt;</code></p>
                <div id="bpp-editor-html" class="bpp-monaco-editor"></div>
                <input type="hidden" id="bpp-code" value="">
            </div>

            <div class="bpp-field">
                <label>CSS</label>
                <div id="bpp-editor-css" class="bpp-monaco-editor"></div>
                <input type="hidden" id="bpp-css" value="">
            </div>

            <div class="bpp-row">
                <div class="bpp-field bpp-field-half">
                    <label>Posição</label>
                    <select id="bpp-position">
                        <?php foreach ( $pos_labels as $val => $label ) : ?>
                            <option value="<?php echo esc_attr($val); ?>" <?php selected( $data['position'], $val ); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bpp-field bpp-field-half">
                    <label>Backdrop</label>
                    <div class="bpp-backdrop-row">
                        <label class="bpp-toggle">
                            <input type="checkbox" id="bpp-backdrop" <?php checked( $data['backdrop'], 1 ); ?>>
                            <span class="bpp-toggle-slider"></span>
                            Ativar backdrop
                        </label>
                        <div id="bpp-opacity-wrap" class="bpp-opacity-wrap" style="<?php echo $data['backdrop'] ? '' : 'display:none'; ?>">
                            <label for="bpp-opacity">Opacidade</label>
                            <select id="bpp-opacity">
                                <?php
                                $opacities = ['0.1'=>'10%','0.2'=>'20%','0.3'=>'30%','0.4'=>'40%','0.5'=>'50%','0.6'=>'60%','0.7'=>'70%','0.8'=>'80%','0.9'=>'90%','1'=>'100%'];
                                foreach ( $opacities as $val => $label ) :
                                ?>
                                    <option value="<?php echo $val; ?>" <?php selected( $data['opacity'], $val ); ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // PMPro: list membership levels if plugin is active
            if ( function_exists( 'pmpro_getAllLevels' ) ) :
                $all_levels     = pmpro_getAllLevels( false, true );
                $blocked_levels = $data['id'] ? get_post_meta( $data['id'], '_bpp_pmpro_exclude_levels', true ) : [];
                $blocked_levels = is_array( $blocked_levels ) ? array_map( 'intval', $blocked_levels ) : [];
                if ( ! empty( $all_levels ) ) :
            ?>
            <div class="bpp-field bpp-field-pmpro">
                <label>Não exibir caso o usuário esteja associado em</label>
                <p class="bpp-hint">Se o visitante estiver logado e possuir uma das associações marcadas abaixo, o popup <strong>não será exibido</strong>.</p>
                <div class="bpp-pmpro-levels">
                    <?php foreach ( $all_levels as $level ) : ?>
                    <label class="bpp-pmpro-level-item">
                        <input type="checkbox"
                               name="bpp_pmpro_exclude[]"
                               value="<?php echo esc_attr( $level->id ); ?>"
                               <?php checked( in_array( (int) $level->id, $blocked_levels ) ); ?>>
                        <span><?php echo esc_html( $level->name ); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
                endif;
            endif;
            ?>

            <div class="bpp-form-footer">
                <button id="bpp-save" class="bpp-btn bpp-btn-primary bpp-btn-lg">
                    <span class="bpp-btn-label"><?php echo $data['id'] ? 'Salvar alterações' : 'Criar popup'; ?></span>
                </button>
                <span id="bpp-save-feedback" class="bpp-feedback"></span>
            </div>
        </div>
    </div>

    <script>
    window.BPP_INITIAL = {
        code: <?php echo json_encode( $data['code'] ); ?>,
        css:  <?php echo json_encode( $data['css'] ); ?>,
    };
    </script>
    <?php
}

function bpp_position_labels() {
    return [
        'top-left'      => 'Superior esquerdo',
        'top-center'    => 'Superior centro',
        'top-right'     => 'Superior direito',
        'center-left'   => 'Centro esquerdo',
        'center-center' => 'Centralizado',
        'center-right'  => 'Centro direito',
        'bottom-left'   => 'Inferior esquerdo',
        'bottom-center' => 'Inferior centro',
        'bottom-right'  => 'Inferior direito',
    ];
}
