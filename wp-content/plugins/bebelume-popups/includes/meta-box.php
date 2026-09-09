<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', 'bpp_add_meta_box' );
function bpp_add_meta_box() {
    $screens = [ 'post', 'page' ];
    foreach ( $screens as $screen ) {
        add_meta_box(
            'bpp_selector',
            'Bebelume Popups',
            'bpp_meta_box_render',
            $screen,
            'side',
            'default'
        );
    }
}

function bpp_meta_box_render( $post ) {
    wp_nonce_field( 'bpp_meta_box', 'bpp_meta_box_nonce' );

    $selected = get_post_meta( $post->ID, '_bpp_selected_popups', true );
    $selected = is_array( $selected ) ? $selected : [];

    $popups = get_posts([
        'post_type'      => 'bpp_popup',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    if ( empty( $popups ) ) {
        echo '<p style="color:#888;font-size:12px;">Nenhum popup criado ainda. <a href="' . admin_url('admin.php?page=bpp-create') . '">Criar popup</a></p>';
        return;
    }
    ?>
    <div class="bpp-meta-list">
        <?php foreach ( $popups as $popup ) : ?>
            <label class="bpp-meta-item">
                <input type="checkbox"
                    name="bpp_selected_popups[]"
                    value="<?php echo $popup->ID; ?>"
                    <?php checked( in_array( $popup->ID, array_map('intval', $selected) ) ); ?>>
                <span><?php echo esc_html( $popup->post_title ); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <style>
        .bpp-meta-list { display:flex; flex-direction:column; gap:6px; }
        .bpp-meta-item { display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; }
        .bpp-meta-item input { margin:0; }
    </style>
    <?php
}

add_action( 'save_post', 'bpp_save_meta_box' );
function bpp_save_meta_box( $post_id ) {
    if ( ! isset( $_POST['bpp_meta_box_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['bpp_meta_box_nonce'], 'bpp_meta_box' ) ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $selected = isset( $_POST['bpp_selected_popups'] )
        ? array_map( 'intval', $_POST['bpp_selected_popups'] )
        : [];

    update_post_meta( $post_id, '_bpp_selected_popups', $selected );
}
