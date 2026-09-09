<?php
/**
 * Template para Posts (artigos individuais)
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
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            
            <?php
            // Renderiza o conteúdo do post (inclui os blocos do Gutenberg)
            the_content();
            ?>
            
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php
get_footer();