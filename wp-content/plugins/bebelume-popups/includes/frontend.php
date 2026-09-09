<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_footer', 'bpp_render_popups' );
function bpp_render_popups() {
    if ( is_admin() ) return;
    if ( ! is_singular() ) return;

    $post_id  = get_the_ID();
    $selected = get_post_meta( $post_id, '_bpp_selected_popups', true );
    if ( empty( $selected ) || ! is_array( $selected ) ) return;

    $popups = get_posts([
        'post_type'      => 'bpp_popup',
        'post__in'       => array_map('intval', $selected),
        'posts_per_page' => -1,
    ]);

    if ( empty( $popups ) ) return;

    wp_enqueue_style(  'bpp-front', BPP_URL . 'front/css/front.css', [], BPP_VERSION );
    wp_enqueue_script( 'bpp-front', BPP_URL . 'front/js/front.js', [], BPP_VERSION, true );

    foreach ( $popups as $popup ) {
        // PMPro: skip popup if user is a member of any excluded level
        if ( is_user_logged_in() && function_exists( 'pmpro_hasMembershipLevel' ) ) {
            $exclude_levels = get_post_meta( $popup->ID, '_bpp_pmpro_exclude_levels', true );
            if ( is_array( $exclude_levels ) && ! empty( $exclude_levels ) ) {
                $exclude_levels = array_map( 'intval', $exclude_levels );
                if ( pmpro_hasMembershipLevel( $exclude_levels ) ) {
                    continue; // user is a member of an excluded level — skip this popup
                }
            }
        }

        $html     = get_post_meta( $popup->ID, '_bpp_code',     true );
        $css      = get_post_meta( $popup->ID, '_bpp_css',      true );
        $backdrop = (int) get_post_meta( $popup->ID, '_bpp_backdrop', true );
        $position = get_post_meta( $popup->ID, '_bpp_position', true ) ?: 'center-center';

        [ $v_pos ] = explode( '-', $position, 2 );
        $dialog_class = $v_pos === 'top' ? 'modal-dialog-top' : ( $v_pos === 'bottom' ? 'modal-dialog-bottom' : '' );

        $modal_id = 'bpp-modal-' . $popup->ID;

        if ( $css ) {
            echo '<style id="bpp-style-' . $popup->ID . '">' . wp_strip_all_tags( $css ) . '</style>';
        }
        ?>
        <div class="modal fade bpp-modal"
             id="<?php echo esc_attr($modal_id); ?>"
             data-bs-backdrop="<?php echo $backdrop ? 'true' : 'false'; ?>"
             data-bs-keyboard="true"
             tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog <?php echo esc_attr($dialog_class); ?>">
                <div class="modal-content bpp-modal-content">
                    <?php echo $html; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
