<?php
/**
 * Template Header - Topo com Login/Dropdown
 * 
 * @package Bebelume_Theme
 * @since 1.0.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Bebelume",
        "legalName": "Bebelume",
        "url": "https://bebelume.com.br",
        "logo": "https://bebelume.com.br/wp-content/uploads/2025/11/cropped-logo-bebelume-play.png",
        "description": "Portal Bebelume: filmes, séries, jogos e arte para a primeira infância. Conteúdos criativos para famílias, escolas e educadores.",
        "sameAs": [
        "https://www.instagram.com/bebelume/",
        "https://www.youtube.com/c/bebelume",
        "https://www.facebook.com/BEBELUME/"
        ]
      }
    </script>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-BM1PHW17PJ"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-BM1PHW17PJ');
    </script>

    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "vpikd5136o");
    </script>

</head>

<body <?php body_class(); ?>>

<!-- Spinner de Loading -->
<div id="page-spinner" class="spinner-backdrop">
    <div class="spinner-container">
        <img src="/wp-content/uploads/2026/03/play.png" alt="Carregando..." class="spinner-image">
        <div class="spinner-text">Carregando...</div>
    </div>
</div>

<?php wp_body_open(); ?>

<header class="site-header header-infantil py-3 position-relative overflow-hidden">
  <div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center">

      <!-- Logo -->
      <div class="navbar-brand m-0">
        <?php if (has_custom_logo()) : ?>
          <?php the_custom_logo(); ?>
        <?php else : ?>
          <a href="<?php echo esc_url(home_url('/')); ?>" class="text-decoration-none text-dark fw-bold fs-4">
            <?php bloginfo('name'); ?>
          </a>
        <?php endif; ?>
      </div>

      <!-- Elementos decorativos -->
      <div class="header-decorations d-none d-md-flex flex-grow-1 justify-content-center position-relative">
      </div>

      <!-- Área de Login/User -->
      <div class="header-user-area">
        <?php if (is_user_logged_in()) : 
          $current_user = wp_get_current_user();
          $user_id = get_current_user_id();
          $avatar_url = get_avatar_url($user_id, array('size' => 40));
          $display_name = $current_user->display_name;
        ?>
          
          <!-- Usuário Logado - Dropdown -->
          <div class="bbl-user-dropdown">
            <button class="bbl-user-toggle" id="userMenuToggle" aria-expanded="false">
              <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" class="bbl-user-avatar">
              <span class="bbl-user-name d-none d-sm-inline">Olá, <?php echo esc_html($display_name); ?></span>
              <svg class="bbl-dropdown-arrow" width="12" height="8" viewBox="0 0 12 8" fill="none">
                <path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
            
            <div class="bbl-user-menu" id="userMenu" aria-hidden="true">
              <div class="bbl-user-menu-header">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" class="bbl-user-menu-avatar">
                <div class="bbl-user-menu-info">
                  <strong><?php echo esc_html($display_name); ?></strong>
                  <span><?php echo esc_html($current_user->user_email); ?></span>
                </div>
              </div>
              
              <div class="bbl-user-menu-items">
                <a href="<?php echo esc_url(pmpro_url('account')); ?>" class="bbl-user-menu-item">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                  Minha conta
                </a>
                
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="bbl-user-menu-item bbl-user-menu-logout">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                  </svg>
                  👋 Sair
                </a>
              </div>
            </div>
          </div>
          
        <?php else : ?>
          
          <!-- Botão Entrar (Não logado) -->
          <a href="<?php echo esc_url(wp_login_url()); ?>" 
             class="btn btn-light px-4 py-2 rounded-pill fw-bold position-relative btn-entrar">
            ☀️ ENTRAR
          </a>
          
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="wave"></div>
</header>