<?php
/**
 * Arquivo: includes/class-pmpro-integration.php
 * Integração com Paid Memberships Pro
 * 
 * Verifica proteção de conteúdo e acesso de usuários aos vídeos
 * 
 * @package Video_Playlist_Block
 * @since 2.1.3
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class VPB_PMPro_Integration {
    
    /**
     * Verifica se PMPro está ativo
     */
    public static function is_pmpro_active() {
        return defined('PMPRO_VERSION') && function_exists('pmpro_hasMembershipLevel');
    }
    
    /**
     * Verifica o acesso de um vídeo (post)
     * 
     * USA O SISTEMA DE CATEGORIAS DO PMPRO
     * O PMPro permite proteger categorias, e posts com essas categorias são automaticamente protegidos.
     * 
     * @param int $post_id ID do post de vídeo
     * @return array Informações de acesso
     */
    public static function check_video_access($post_id) {
        // Resultado padrão (vídeo não protegido)
        $result = array(
            'is_protected' => false,
            'can_access' => true,
            'required_levels' => array(),
            'user_levels' => array(),
            'is_logged_in' => is_user_logged_in(),
            'message' => ''
        );
        
        // Se não for um post válido
        if (empty($post_id)) {
            return $result;
        }
        
        // Se PMPro não está ativo, retorna acesso livre
        if (!self::is_pmpro_active()) {
            return $result;
        }
        
        // DEBUG - Ver o que está acontecendo
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== DEBUG VIDEO ACCESS - Post ID: " . $post_id . " ===");
            error_log("Usuário logado: " . (is_user_logged_in() ? 'SIM (ID: ' . get_current_user_id() . ')' : 'NÃO'));
            error_log("Usuário é admin: " . (current_user_can('administrator') ? 'SIM' : 'NÃO'));
        }
        
        // ✅ CORRIGIDO: Sem o terceiro parâmetro TRUE
        // Verifica se usuário tem acesso ao post
        $has_access = pmpro_has_membership_access($post_id);
        
        // DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("pmpro_has_membership_access retornou: " . ($has_access ? 'TRUE (pode acessar)' : 'FALSE (bloqueado)'));
        }
        
        // Se tem acesso, retorna livre
        if ($has_access) {
            return $result;
        }
        
        // Não tem acesso = vídeo protegido
        $result['is_protected'] = true;
        $result['can_access'] = false;
        
        // ✅ CORRIGIDO: Pega níveis necessários usando a forma correta
        $post_levels = self::get_post_membership_levels($post_id);
        
        // DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Níveis necessários: " . print_r($post_levels, true));
        }
        
        if (!empty($post_levels) && is_array($post_levels)) {
            foreach ($post_levels as $level) {
                $result['required_levels'][] = array(
                    'id' => $level->id,
                    'name' => $level->name
                );
            }
            
            // Monta mensagem
            $level_names = array_map(function($level) {
                return $level->name;
            }, $post_levels);
            
            if (count($level_names) === 1) {
                $result['message'] = 'Este vídeo é exclusivo para assinantes do plano: ' . $level_names[0];
            } else {
                $result['message'] = 'Este vídeo é exclusivo para assinantes dos planos: ' . implode(', ', $level_names);
            }
        } else {
            $result['message'] = 'Este vídeo é exclusivo para membros.';
        }
        
        // Pega níveis do usuário atual (se logado)
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $user_levels = pmpro_getMembershipLevelsForUser($user_id);
            
            if (!empty($user_levels)) {
                foreach ($user_levels as $level) {
                    $result['user_levels'][] = array(
                        'id' => $level->id,
                        'name' => $level->name
                    );
                }
            }
            
            // DEBUG
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Níveis do usuário: " . print_r($user_levels, true));
            }
        }
        
        // DEBUG final
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Resultado final - is_protected: " . ($result['is_protected'] ? 'true' : 'false'));
            error_log("Resultado final - can_access: " . ($result['can_access'] ? 'true' : 'false'));
            error_log("=== FIM DEBUG ===");
        }
        
        return $result;
    }
    
    /**
     * ✅ NOVA FUNÇÃO: Pega os níveis necessários para um post
     * Verifica tanto proteção direta quanto por categoria
     */
    private static function get_post_membership_levels($post_id) {
        global $wpdb;
        
        $levels = array();
        
        // 1. Verifica proteção direta no post (metabox)
        $direct_levels = $wpdb->get_results($wpdb->prepare("
            SELECT m.id, m.name
            FROM {$wpdb->pmpro_memberships_pages} mp
            INNER JOIN {$wpdb->pmpro_membership_levels} m ON mp.membership_id = m.id
            WHERE mp.page_id = %d
        ", $post_id));
        
        if (!empty($direct_levels)) {
            $levels = array_merge($levels, $direct_levels);
        }
        
        // 2. Verifica proteção por categoria
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            $category_ids = array_map(function($cat) {
                return $cat->term_id;
            }, $categories);
            
            $placeholders = implode(',', array_fill(0, count($category_ids), '%d'));
            
            $category_levels = $wpdb->get_results($wpdb->prepare("
                SELECT DISTINCT m.id, m.name
                FROM {$wpdb->pmpro_memberships_categories} mc
                INNER JOIN {$wpdb->pmpro_membership_levels} m ON mc.membership_id = m.id
                WHERE mc.category_id IN ($placeholders)
            ", $category_ids));
            
            if (!empty($category_levels)) {
                $levels = array_merge($levels, $category_levels);
            }
        }
        
        // Remove duplicatas (se post tem proteção direta E por categoria)
        $unique_levels = array();
        $seen_ids = array();
        
        foreach ($levels as $level) {
            if (!in_array($level->id, $seen_ids)) {
                $unique_levels[] = $level;
                $seen_ids[] = $level->id;
            }
        }
        
        return $unique_levels;
    }
    
    /**
     * Busca o bbl_pagina_planos dos níveis exigidos pelo vídeo.
     * Retorna a URL do primeiro nível que tiver o campo preenchido.
     *
     * @param array $required_levels Array de ['id' => ..., 'name' => ...]
     * @return string URL da página de planos ou string vazia
     */
    public static function get_plans_url_for_levels( $required_levels ) {
        if ( empty( $required_levels ) ) {
            return '';
        }

        foreach ( $required_levels as $level ) {
            $level_id = isset( $level['id'] ) ? intval( $level['id'] ) : 0;
            if ( ! $level_id ) continue;

            $url = get_option( 'bbl_level_pagina_planos_' . $level_id, '' );
            if ( ! empty( $url ) ) {
                return esc_url( $url );
            }
        }

        return '';
    }

    /**
     * Obtém URLs de redirecionamento
     * 
     * @return array URLs para login e membership
     */
    public static function get_redirect_urls() {
        $urls = array(
            'login' => wp_login_url(get_permalink()),
            'membership' => home_url('/membership/')
        );
        
        // Se PMPro está ativo, usa página de níveis
        if (self::is_pmpro_active()) {
            $levels_page_id = get_option('pmpro_levels_page_id');
            if ($levels_page_id) {
                $urls['membership'] = get_permalink($levels_page_id);
            }
        }
        
        return $urls;
    }

    /**
     * Debug de acesso a vídeo
     * 
     * @param int $post_id
     * @return array
     */
    public static function debug_video_access($post_id) {
        $result = self::check_video_access($post_id);
        
        echo '<pre>';
        echo "POST ID: $post_id\n";
        echo "Is Protected: " . ($result['is_protected'] ? 'SIM' : 'NÃO') . "\n";
        echo "Can Access: " . ($result['can_access'] ? 'SIM' : 'NÃO') . "\n";
        echo "Message: " . $result['message'] . "\n";
        echo "Required Levels: " . print_r($result['required_levels'], true) . "\n";
        echo "User Levels: " . print_r($result['user_levels'], true) . "\n";
        echo "Is Logged In: " . ($result['is_logged_in'] ? 'SIM' : 'NÃO') . "\n";
        echo '</pre>';
        
        return $result;
    }
}