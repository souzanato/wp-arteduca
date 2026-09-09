<?php
/**
 * Template da Página Inicial
 * 
 * @package Bebelume_Theme
 * @since 1.0.0
 */

get_header();
?>

<main id="main-content" class="site-main">
    <?php
    while (have_posts()) :
        the_post();
        the_content();
    endwhile;
    ?>
</main>

<?php
get_footer();