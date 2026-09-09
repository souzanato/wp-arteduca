<?php
/**
 * Template Footer - Rodapé Verde com Menu
 * 
 * @package Bebelume_Theme
 * @since 1.0.0
 */
?>

<footer class="site-footer bg-success text-white mt-0">
    <div class="container py-4">

        <!-- Área de Blocos Gutenberg -->
        <?php if ( is_active_sidebar( 'footer-blocks' ) ) : ?>
            <div class="row mb-4">
                <div class="col-12">
                    <?php dynamic_sidebar( 'footer-blocks' ); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Menu do Rodapé -->
        <div class="row">
            <div class="col-12">
                <?php
                if (has_nav_menu('footer-menu')) {
                    wp_nav_menu(array(
                        'theme_location' => 'footer-menu',
                        'container'      => 'nav',
                        'container_class' => 'd-flex justify-content-center',
                        'menu_class'     => 'nav',
                        'fallback_cb'    => false,
                        'depth'          => 1,
                        'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                    ));
                }
                ?>
            </div>
        </div>

        <!-- Copyright -->
        <div class="row mt-3 pt-3 border-top border-white border-opacity-25">
            <div class="col-12 text-center">
                <p class="mb-0 small opacity-75">
                    &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. Todos os direitos reservados.
                </p>
            </div>
        </div>

    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
