<?php
/**
 * Template Name: ArtEduca Início
 * Template Post Type: page
 * Description: Landing page principal do Bebelume ArtEduca
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<style>
    /* Reset para este template */
    .arteduca-inicio * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Ocultar elementos do tema */
    .arteduca-inicio .entry-header,
    .arteduca-inicio .entry-footer,
    .arteduca-inicio .page-header {
        display: none !important;
    }

    /* CSS Variables */
    :root {
        --azul: #2E5B9B;
        --roxo: #6B4A9E;
        --vermelho: #D9534F;
        --amarelo: #F0AD4E;
        --bg-primary: #FAFAF8;
        --bg-secondary: #F5F3ED;
        --text-primary: #1a1a1a;
        --text-secondary: #666;
        --text-muted: #999;
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
        --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
        --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.12);
        --shadow-xl: 0 16px 48px rgba(0, 0, 0, 0.15);
        --space-xs: 0.5rem;
        --space-sm: 1rem;
        --space-md: 2rem;
        --space-lg: 3rem;
        --space-xl: 5rem;
    }

    /* Container principal */
    .arteduca-inicio {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
        background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        color: var(--text-primary);
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
        min-height: 100vh;
    }

    /* Header bar */
    .arteduca-header-bar {
        height: 6px;
        background: linear-gradient(90deg, 
            var(--azul) 0%, var(--azul) 25%, 
            var(--roxo) 25%, var(--roxo) 50%, 
            var(--vermelho) 50%, var(--vermelho) 75%, 
            var(--amarelo) 75%, var(--amarelo) 100%
        );
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* Hero */
    .arteduca-hero {
        max-width: 1200px;
        margin: 0 auto;
        padding: var(--space-xl) var(--space-md) var(--space-lg);
        text-align: center;
    }

    .arteduca-hero-title {
        font-size: clamp(2.5rem, 6vw, 4.5rem);
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin-bottom: var(--space-sm);
        background: linear-gradient(135deg, var(--azul), var(--roxo));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .arteduca-hero-subtitle {
        font-size: clamp(1.125rem, 2vw, 1.5rem);
        font-weight: 400;
        color: var(--text-secondary);
        margin-bottom: var(--space-lg);
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Busca */
    .arteduca-search-container {
        max-width: 800px;
        margin: 0 auto var(--space-xl);
        padding: 0 var(--space-md);
    }

    .arteduca-search-wrapper {
        position: relative;
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow-lg);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .arteduca-search-wrapper:focus-within {
        box-shadow: var(--shadow-xl);
        transform: translateY(-2px);
    }

    .arteduca-search-icon {
        position: absolute;
        left: 2rem;
        top: 50%;
        transform: translateY(-50%);
        width: 28px;
        height: 28px;
        color: var(--amarelo);
        transition: color 0.3s;
    }

    .arteduca-search-wrapper:focus-within .arteduca-search-icon {
        color: var(--azul);
    }

    .arteduca-search-input {
        width: 100%;
        padding: 1.75rem 2rem 1.75rem 5rem;
        font-size: clamp(1.125rem, 2vw, 1.375rem);
        font-weight: 500;
        border: 2px solid transparent;
        border-radius: 16px;
        background: transparent;
        color: var(--text-primary);
        outline: none;
        transition: border-color 0.3s;
    }

    .arteduca-search-input::placeholder {
        color: var(--text-muted);
        font-weight: 400;
    }

    .arteduca-search-input:focus {
        border-color: var(--azul);
    }

    /* Seção Campos */
    .arteduca-campos-section {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 var(--space-md) var(--space-xl);
    }

    .arteduca-section-header {
        text-align: center;
        margin-bottom: var(--space-lg);
    }

    .arteduca-section-title {
        font-size: clamp(1.75rem, 3vw, 2.5rem);
        font-weight: 700;
        letter-spacing: -0.01em;
        margin-bottom: var(--space-xs);
    }

    .arteduca-section-subtitle {
        font-size: clamp(1rem, 1.5vw, 1.125rem);
        color: var(--text-secondary);
        font-weight: 400;
    }

    /* Grid */
    .arteduca-campos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }

    /* Cards */
    .arteduca-campo-card {
        position: relative;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        padding: var(--space-lg) var(--space-md);
        box-shadow: var(--shadow-md);
        border: 1px solid rgba(255, 255, 255, 0.5);
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .arteduca-campo-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--azul), var(--roxo));
        opacity: 0;
        transition: opacity 0.3s;
    }

    .arteduca-campo-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-xl);
    }

    .arteduca-campo-card:hover::before {
        opacity: 1;
    }

    .arteduca-campo-card:nth-child(1)::before { background: linear-gradient(90deg, var(--amarelo), var(--vermelho)); }
    .arteduca-campo-card:nth-child(2)::before { background: linear-gradient(90deg, var(--azul), var(--roxo)); }
    .arteduca-campo-card:nth-child(3)::before { background: linear-gradient(90deg, var(--roxo), var(--vermelho)); }
    .arteduca-campo-card:nth-child(4)::before { background: linear-gradient(90deg, var(--amarelo), var(--azul)); }
    .arteduca-campo-card:nth-child(5)::before { background: linear-gradient(90deg, var(--vermelho), var(--amarelo)); }
    .arteduca-campo-card:nth-child(6)::before { background: linear-gradient(90deg, var(--azul), var(--amarelo)); }

    .arteduca-campo-icon-wrapper {
        width: 120px;
        height: 120px;
        margin: 0 auto var(--space-md);
    }

    .arteduca-campo-icon-circle {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s;
    }

    .arteduca-campo-card:hover .arteduca-campo-icon-circle {
        box-shadow: var(--shadow-md);
        transform: scale(1.05);
    }

    .arteduca-campo-icon {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .arteduca-campo-content {
        text-align: center;
    }

    .arteduca-campo-name {
        font-size: clamp(1rem, 1.25vw, 1.125rem);
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-primary);
        line-height: 1.3;
        margin-bottom: var(--space-xs);
        letter-spacing: 0.02em;
    }

    .arteduca-campo-count {
        font-size: 0.875rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    /* Card TODOS */
    .arteduca-campo-card:last-child {
        background: linear-gradient(135deg, var(--azul), var(--roxo));
        color: white;
        border: none;
    }

    .arteduca-campo-card:last-child .arteduca-campo-name {
        color: white;
    }

    .arteduca-campo-card:last-child .arteduca-campo-count {
        color: rgba(255, 255, 255, 0.8);
    }

    .arteduca-campo-card:last-child .arteduca-campo-icon-circle {
        background: rgba(255, 255, 255, 0.2);
    }

    /* Footer CTAs */
    .arteduca-footer-ctas {
        max-width: 1200px;
        margin: var(--space-xl) auto 0;
        padding: 0 var(--space-md) var(--space-xl);
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: var(--space-md);
    }

    .arteduca-cta-card {
        position: relative;
        padding: var(--space-lg);
        border-radius: 24px;
        color: white;
        cursor: pointer;
        overflow: hidden;
        min-height: 280px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: var(--shadow-lg);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
    }

    .arteduca-cta-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.3));
        opacity: 0;
        transition: opacity 0.3s;
    }

    .arteduca-cta-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-xl);
    }

    .arteduca-cta-card:hover::before {
        opacity: 1;
    }

    .arteduca-cta-red {
        background: linear-gradient(135deg, var(--vermelho), #c44842);
    }

    .arteduca-cta-yellow {
        background: linear-gradient(135deg, var(--amarelo), #d99c48);
    }

    .arteduca-cta-content {
        position: relative;
        z-index: 1;
    }

    .arteduca-cta-title {
        font-size: clamp(1.75rem, 3vw, 2.25rem);
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: var(--space-sm);
        text-transform: uppercase;
        letter-spacing: -0.01em;
    }

    .arteduca-cta-description {
        font-size: 1.125rem;
        font-weight: 400;
        opacity: 0.95;
        line-height: 1.5;
    }

    /* Responsivo */
    @media (max-width: 768px) {
        .arteduca-hero {
            padding: var(--space-lg) var(--space-md);
        }

        .arteduca-campos-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-sm);
        }

        .arteduca-campo-card {
            padding: var(--space-md) var(--space-sm);
        }

        .arteduca-campo-icon-wrapper {
            width: 80px;
            height: 80px;
        }

        .arteduca-footer-ctas {
            grid-template-columns: 1fr;
        }

        .arteduca-cta-card {
            min-height: 200px;
        }
    }

    @media (max-width: 480px) {
        .arteduca-campos-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="arteduca-inicio">
    <!-- Barra colorida -->
    <div class="arteduca-header-bar"></div>

    <!-- Hero -->
    <section class="arteduca-hero">
        <h1 class="arteduca-hero-title">Bebelume ArtEduca</h1>
        <p class="arteduca-hero-subtitle">Explore atividades pedagógicas alinhadas aos Campos de Experiência da BNCC</p>
    </section>

    <!-- Busca -->
    <div class="arteduca-search-container">
        <div class="arteduca-search-wrapper">
            <svg class="arteduca-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="text" class="arteduca-search-input" placeholder="Buscar por palavra-chave, tema ou atividade...">
        </div>
    </div>

    <!-- Campos -->
    <section class="arteduca-campos-section">
        <div class="arteduca-section-header">
            <h2 class="arteduca-section-title">Navegue por Campo de Experiência</h2>
            <p class="arteduca-section-subtitle">Selecione um campo para explorar atividades específicas</p>
        </div>

        <div class="arteduca-campos-grid">
            <?php
            // URL base do plugin
            $plugin_url = BEBELUME_ARTEDUCA_PLUGIN_URL;
            
            // Array de campos
            $campos = array(
                array(
                    'nome' => 'O Eu, o Outro,<br>o Nós',
                    'icon' => 'eu-outro-nos.png',
                    'count' => '45',
                    'link' => '#'
                ),
                array(
                    'nome' => 'Escuta, Fala,<br>Pensamento e Imaginação',
                    'icon' => 'escuta-fala.png',
                    'count' => '52',
                    'link' => '#'
                ),
                array(
                    'nome' => 'Espaço, Tempos, Quantidades,<br>Relações e Transformações',
                    'icon' => 'espaco-tempos.png',
                    'count' => '38',
                    'link' => '#'
                ),
                array(
                    'nome' => 'Corpo, Gestos<br>e Movimentos',
                    'icon' => 'corpo-gestos.png',
                    'count' => '41',
                    'link' => '#'
                ),
                array(
                    'nome' => 'Traços, Sons,<br>Cores e Formas',
                    'icon' => 'tracos-sons.png',
                    'count' => '48',
                    'link' => '#'
                ),
                array(
                    'nome' => 'Todos os Campos',
                    'icon' => 'todos.png',
                    'count' => '224',
                    'link' => '#'
                )
            );

            foreach ($campos as $campo) :
            ?>
            <a href="<?php echo esc_url($campo['link']); ?>" class="arteduca-campo-card">
                <div class="arteduca-campo-icon-wrapper">
                    <div class="arteduca-campo-icon-circle">
                        <img src="<?php echo esc_url($plugin_url . 'assets/images/campos/' . $campo['icon']); ?>" 
                             alt="<?php echo strip_tags($campo['nome']); ?>" 
                             class="arteduca-campo-icon">
                    </div>
                </div>
                <div class="arteduca-campo-content">
                    <h3 class="arteduca-campo-name"><?php echo $campo['nome']; ?></h3>
                    <p class="arteduca-campo-count"><?php echo $campo['count']; ?> atividades</p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Footer CTAs -->
    <div class="arteduca-footer-ctas">
        <a href="#" class="arteduca-cta-card arteduca-cta-red">
            <div class="arteduca-cta-content">
                <h2 class="arteduca-cta-title">Apresentação<br>ArtEduca</h2>
                <p class="arteduca-cta-description">Conheça a metodologia e os fundamentos pedagógicos</p>
            </div>
        </a>
        <a href="#" class="arteduca-cta-card arteduca-cta-yellow">
            <div class="arteduca-cta-content">
                <h2 class="arteduca-cta-title">As Telas na<br>Primeira Infância</h2>
                <p class="arteduca-cta-description">Orientações sobre uso consciente de tecnologia</p>
            </div>
        </a>
    </div>
</div>

<?php
get_footer();
?>
