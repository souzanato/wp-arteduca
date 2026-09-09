<?php
/**
 * Bebelume Blocks — registro dos blocos Gutenberg
 *
 * Blocos:
 *  - bebelume/telhado      : bloco casca com passarinhos
 *  - bebelume/custom-fonts : parágrafo com webfont customizada
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Bebelume_Blocks {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init',                  array( $this, 'register_blocks' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_globals' ) );
        add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_frontend_fonts' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_fonts' ) );
        add_action( 'rest_api_init',         array( $this, 'register_rest_routes' ) );

        // AJAX: salvar fonte enviada pelo editor
        add_action( 'wp_ajax_bbl_save_font', array( $this, 'ajax_save_font' ) );
    }

    // -----------------------------------------------------------------------
    // Registro dos blocos
    // -----------------------------------------------------------------------

    public function register_blocks() {
        // Categoria personalizada
        add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 10, 2 );

        // Bloco: Telhado
        register_block_type(
            BBL_PROFILE_DIR . 'blocks/telhado/block.json'
        );

        // Bloco: Andar
        register_block_type(
            BBL_PROFILE_DIR . 'blocks/andar/block.json'
        );

        // Bloco: Níveis PMPro
        register_block_type(
            BBL_PROFILE_DIR . 'blocks/niveis-pmpro/block.json'
        );

        // Bloco: Custom Fonts
        register_block_type(
            BBL_PROFILE_DIR . 'blocks/custom-fonts/block.json',
            array(
                'render_callback' => array( $this, 'render_custom_fonts' ),
            )
        );

        // Bloco: Plyr
        register_block_type(
            BBL_PROFILE_DIR . 'blocks/plyr/block.json'
        );

        // Bloco: Térreo
        register_block_type(
            BBL_PROFILE_DIR . 'blocks/terreo/block.json',
            array( 'render_callback' => array( $this, 'render_terreo' ) )
        );
    }

    public function add_block_category( $categories ) {
        // Evitar duplicatas
        foreach ( $categories as $cat ) {
            if ( $cat['slug'] === 'bebelume' ) {
                return $categories;
            }
        }
        return array_merge(
            array(
                array(
                    'slug'  => 'bebelume',
                    'title' => 'Bebelume',
                    'icon'  => null,
                ),
            ),
            $categories
        );
    }

    // -----------------------------------------------------------------------
    // Render callbacks
    // -----------------------------------------------------------------------

    /**
     * Renderiza o bloco Custom Fonts no frontend.
     * Injeta @font-face inline se a fonte vier de um data-URL (upload direto).
     * Se vier de uma URL normal, apenas usa o font-family.
     */
    public function render_custom_fonts( $attributes, $content ) {
        $font_family     = ! empty( $attributes['fontFamily'] )     ? $attributes['fontFamily'] : '';
        $font_size       = isset( $attributes['fontSize'] )         ? intval( $attributes['fontSize'] ) : 16;
        $text_color      = ! empty( $attributes['textColor'] )      ? sanitize_hex_color( $attributes['textColor'] ) : '#000000';
        $bg_color        = ! empty( $attributes['backgroundColor'] ) ? sanitize_hex_color( $attributes['backgroundColor'] ) : '';
        $text_align      = ! empty( $attributes['textAlign'] )      ? $attributes['textAlign'] : 'left';
        $font_weight     = ! empty( $attributes['fontWeight'] )     ? $attributes['fontWeight'] : 'normal';
        $italic          = ! empty( $attributes['italic'] );
        $line_height     = ! empty( $attributes['lineHeight'] )     ? $attributes['lineHeight'] : '';
        $letter_spacing  = ! empty( $attributes['letterSpacing'] )  ? $attributes['letterSpacing'] : '';
        $text_decoration = ! empty( $attributes['textDecoration'] ) ? $attributes['textDecoration'] : '';
        $text_transform  = ! empty( $attributes['textTransform'] )  ? $attributes['textTransform'] : '';
        $text            = ! empty( $attributes['content'] )        ? $attributes['content'] : '';

        if ( $font_family ) {
            $this->maybe_inject_font_face( $font_family );
        }

        $style = sprintf(
            'font-size:%dpx;color:%s;text-align:%s;font-weight:%s;font-style:%s;%s%s%s%s%s%s',
            $font_size,
            $text_color,
            $text_align,
            esc_attr( $font_weight ),
            $italic ? 'italic' : 'normal',
            $font_family     ? 'font-family:"' . esc_attr( $font_family ) . '";' : '',
            $bg_color        ? 'background-color:' . $bg_color . ';' : '',
            $line_height     ? 'line-height:' . esc_attr( $line_height ) . ';' : '',
            $letter_spacing  ? 'letter-spacing:' . esc_attr( $letter_spacing ) . 'px;' : '',
            $text_decoration ? 'text-decoration:' . esc_attr( $text_decoration ) . ';' : '',
            $text_transform  ? 'text-transform:' . esc_attr( $text_transform ) . ';' : ''
        );

        $wrapper_attrs = get_block_wrapper_attributes( array(
            'class' => 'wp-block-bebelume-custom-fonts',
            'style' => $style,
        ) );

        return '<p ' . $wrapper_attrs . '>' . wp_kses_post( $text ) . '</p>';
    }

    // -----------------------------------------------------------------------
    // Gestão de fontes
    // -----------------------------------------------------------------------

    /**
     * Retorna as fontes salvas no banco (option).
     */
    public static function get_saved_fonts() {
        return get_option( 'bbl_custom_fonts', array() );
    }

    /**
     * Injeta @font-face no head do frontend para a fonte informada.
     */
    private function maybe_inject_font_face( $font_family ) {
        $fonts = self::get_saved_fonts();
        foreach ( $fonts as $font ) {
            if ( $font['name'] === $font_family && ! empty( $font['url'] ) ) {
                $css = '@font-face { font-family: "' . esc_attr( $font['name'] ) . '"; src: url("' . esc_url_raw( $font['url'] ) . '"); }';
                wp_add_inline_style( 'bbl-custom-fonts-style', $css );
                break;
            }
        }
    }

    /**
     * AJAX: salvar nova fonte enviada pelo editor no banco.
     */
    public function ajax_save_font() {
        check_ajax_referer( 'bbl_blocks_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Sem permissão.' );
        }

        $name = isset( $_POST['fontName'] ) ? sanitize_text_field( $_POST['fontName'] ) : '';
        $url  = isset( $_POST['fontUrl'] )  ? $_POST['fontUrl'] : ''; // data-URL, não sanitizar

        if ( ! $name || ! $url ) {
            wp_send_json_error( 'Dados incompletos.' );
        }

        $fonts = self::get_saved_fonts();

        // Evitar duplicatas
        foreach ( $fonts as $font ) {
            if ( $font['name'] === $name ) {
                wp_send_json_success( array( 'message' => 'Fonte já existente.', 'fonts' => $fonts ) );
            }
        }

        $fonts[] = array( 'name' => $name, 'url' => $url );
        update_option( 'bbl_custom_fonts', $fonts );

        wp_send_json_success( array( 'message' => 'Fonte salva!', 'fonts' => $fonts ) );
    }

    // -----------------------------------------------------------------------
    // Enqueue: variáveis globais para o editor
    // -----------------------------------------------------------------------

    public function enqueue_editor_globals() {
        wp_enqueue_script(
            'bbl-blocks-editor-globals',
            BBL_PROFILE_URL . 'assets/js/blocks-globals.js',
            array(),
            BBL_PROFILE_VERSION,
            false
        );

        wp_localize_script( 'bbl-blocks-editor-globals', 'bebelumeBlocks', array(
            'pluginUrl'   => BBL_PROFILE_URL,
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'bbl_blocks_nonce' ),
            'savedFonts'  => self::get_saved_fonts(),
        ) );

        // Injetar URLs dos passarinhos no editor (para preview idêntico ao frontend)
        $amarelo = esc_url( BBL_PROFILE_URL . 'blocks/telhado/assets/passarinho-amarelo.png' );
        $roxo    = esc_url( BBL_PROFILE_URL . 'blocks/telhado/assets/passarinho-roxo.png' );

        $css = "
.bebelume-andar.telhado-passarinhos-bebelume .andar-content::before {
    background-image: url('{$amarelo}') !important;
}
.bebelume-andar.telhado-passarinhos-bebelume .andar-content::after {
    background-image: url('{$roxo}') !important;
}";

        wp_register_style( 'bbl-telhado-editor-inline', false );
        wp_enqueue_style( 'bbl-telhado-editor-inline' );
        wp_add_inline_style( 'bbl-telhado-editor-inline', $css );
    }

    // -----------------------------------------------------------------------
    // Enqueue: @font-face das fontes salvas no frontend
    // -----------------------------------------------------------------------

    public function enqueue_frontend_fonts() {
        // Registrar um handle dummy para poder usar wp_add_inline_style
        wp_register_style( 'bbl-custom-fonts-style', false );
        wp_enqueue_style( 'bbl-custom-fonts-style' );

        // URLs dos passarinhos (sobrescreve URLs relativas do tema)
        $amarelo = esc_url( BBL_PROFILE_URL . 'blocks/telhado/assets/passarinho-amarelo.png' );
        $roxo    = esc_url( BBL_PROFILE_URL . 'blocks/telhado/assets/passarinho-roxo.png' );
        wp_add_inline_style( 'bbl-custom-fonts-style', "
.bebelume-andar.telhado-passarinhos-bebelume .andar-content::before {
    background-image: url('{$amarelo}') !important;
}
.bebelume-andar.telhado-passarinhos-bebelume .andar-content::after {
    background-image: url('{$roxo}') !important;
}" );

        $fonts = self::get_saved_fonts();
        if ( empty( $fonts ) ) return;

        $css = '';
        foreach ( $fonts as $font ) {
            if ( ! empty( $font['name'] ) && ! empty( $font['url'] ) ) {
                $css .= '@font-face { font-family: "' . esc_attr( $font['name'] ) . '"; src: url("' . esc_url_raw( $font['url'] ) . '"); }' . "\n";
            }
        }

        if ( $css ) {
            wp_add_inline_style( 'bbl-custom-fonts-style', $css );
        }
    }

    // -----------------------------------------------------------------------
    // Enqueue: @font-face das fontes salvas no editor também
    // -----------------------------------------------------------------------

    public function enqueue_editor_fonts() {
        wp_register_style( 'bbl-custom-fonts-editor', false );
        wp_enqueue_style( 'bbl-custom-fonts-editor' );

        $fonts = self::get_saved_fonts();
        if ( empty( $fonts ) ) return;

        $css = '';
        foreach ( $fonts as $font ) {
            if ( ! empty( $font['name'] ) && ! empty( $font['url'] ) ) {
                $css .= '@font-face { font-family: "' . esc_attr( $font['name'] ) . '"; src: url("' . esc_url_raw( $font['url'] ) . '"); }' . "\n";
            }
        }

        if ( $css ) {
            wp_add_inline_style( 'bbl-custom-fonts-editor', $css );
        }
    }

    // -----------------------------------------------------------------------
    // REST API: grupos PMPro para o editor
    // -----------------------------------------------------------------------

    public function register_rest_routes() {
        register_rest_route( 'bebelume/v1', '/pmpro-groups', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_pmpro_groups' ),
            'permission_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
        ) );
    }

    public function rest_get_pmpro_groups() {
        if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
            return new WP_Error( 'pmpro_inactive', 'PMPro não está ativo.', array( 'status' => 503 ) );
        }

        $groups = array();

        // pmpro_getGroupsForLevel não existe — usamos a tabela diretamente
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT g.id, g.name FROM {$wpdb->prefix}pmpro_groups g ORDER BY g.name ASC"
        );

        if ( $rows ) {
            foreach ( $rows as $row ) {
                $groups[] = array(
                    'id'   => intval( $row->id ),
                    'name' => $row->name,
                );
            }
        }

        return rest_ensure_response( $groups );
    }
    public function render_terreo( $attributes, $content ) {
        $garden = '
        <div class="garden-container">
            <div class="colinas">
                <div class="colina colina1"></div>
                <div class="colina colina2"></div>
                <div class="colina colina3"></div>
            </div>
            <div class="campo"></div>
            <div class="borboleta borboleta1">
                <div class="asa asa-esquerda"></div>
                <div class="asa asa-direita"></div>
                <div class="corpo-borboleta"></div>
            </div>
            <div class="borboleta borboleta2">
                <div class="asa asa-esquerda"></div>
                <div class="asa asa-direita"></div>
                <div class="corpo-borboleta"></div>
            </div>
            <div class="borboleta borboleta3">
                <div class="asa asa-esquerda"></div>
                <div class="asa asa-direita"></div>
                <div class="corpo-borboleta"></div>
            </div>
            <div class="flores-container">
                <div class="arbustos">
                    <div class="arbusto" style="left:0%"></div>
                    <div class="arbusto" style="left:10%;bottom:-6em"></div>
                    <div class="arbusto" style="left:80%"></div>
                    <div class="arbusto" style="left:90%;bottom:-6em"></div>
                </div>
                <div class="plantinhas">
                    <div class="plantinha-bebelume" style="left:5%"></div>
                    <div class="plantinha-bebelume" style="left:20%"></div>
                    <div class="plantinha-bebelume" style="left:35%"></div>
                    <div class="plantinha-bebelume" style="left:50%"></div>
                    <div class="plantinha-bebelume" style="left:65%"></div>
                    <div class="plantinha-bebelume" style="left:80%"></div>
                </div>
            </div>
        </div>';

        return sprintf(
            '<section class="wp-block-bebelume-terreo-casinha-bebelume bebelume-nivel w-100 branco">%s<div class="bebelume-andar terreo-associacoes-bebelume has-image"><div class="andar-content container-fluid"><div class="row bebelume-level-teasers">%s</div></div></div></section>',
            $garden,
            $content
        );
    }
}

// Inicializar
Bebelume_Blocks::get_instance();
