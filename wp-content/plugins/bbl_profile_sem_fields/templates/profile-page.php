<?php
/**
 * Template da Página de Perfil - Bebelume
 * Version: 3.0 - IDÊNTICO ao Account
 */

if (!defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();
$user_id = get_current_user_id();

// Buscar dados do usuário
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);
?>

<!-- Google Fonts - Nunito -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

<!-- Font Awesome CDN -->

<?php 
// Carregar o header do tema
get_header(); 
?>

<div class="bebelume-account-wrapper">
    
    <div class="bebelume-account-grid">
        
        <div class="bebelume-account-main">
            
            <!-- Cabeçalho de Boas-vindas -->
            <div class="bebelume-welcome-header">
                <div class="welcome-avatar">
                    <?php echo get_avatar($user_id, 80); ?>
                </div>
                <div class="welcome-info">
                    <h1><i class="fa-solid fa-user-circle"></i> Meu Perfil</h1>
                    <p class="welcome-subtitle">Edite suas informações pessoais</p>
                </div>
            </div>

            <!-- Formulário -->
            <form id="bebelumeProfileForm">
                
                <!-- Informações Pessoais -->
                <div class="bebelume-card">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-id-card"></i> Informações Pessoais</h2>
                    </div>
                    <div class="card-content">
                        <div class="info-grid" style="gap: 1.5rem;">
                            <div class="info-item" style="flex-direction: column; align-items: flex-start; background: transparent; border: none; padding: 0;">
                                <span class="info-label" style="margin-bottom: 0.5rem;">
                                    <i class="fa-solid fa-user"></i> Nome
                                </span>
                                <input type="text" id="firstName" name="first_name" value="<?php echo esc_attr($first_name); ?>" 
                                       class="bbl-input" placeholder="Digite seu nome">
                            </div>
                            
                            <div class="info-item" style="flex-direction: column; align-items: flex-start; background: transparent; border: none; padding: 0;">
                                <span class="info-label" style="margin-bottom: 0.5rem;">
                                    <i class="fa-solid fa-user"></i> Sobrenome
                                </span>
                                <input type="text" id="lastName" name="last_name" value="<?php echo esc_attr($last_name); ?>" 
                                       class="bbl-input" placeholder="Digite seu sobrenome">
                            </div>
                            
                            <div class="info-item" style="flex-direction: column; align-items: flex-start; background: transparent; border: none; padding: 0; grid-column: 1 / -1;">
                                <span class="info-label" style="margin-bottom: 0.5rem; width: 100%;">
                                    <i class="fa-solid fa-envelope"></i> E-mail
                                </span>
                                <div style="position: relative; width: 100%;">
                                    <input type="email" id="userEmail" value="<?php echo esc_attr($user->user_email); ?>" 
                                           class="bbl-input" readonly style="background: #f9fafb; cursor: not-allowed;">
                                    <span style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: #9ca3af;">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Segurança -->
                <div class="bebelume-card">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-lock"></i> Segurança</h2>
                    </div>
                    <div class="card-content">
                        <div class="info-grid" style="gap: 1.5rem;">
                            <div class="info-item" style="flex-direction: column; align-items: flex-start; background: transparent; border: none; padding: 0;">
                                <span class="info-label" style="margin-bottom: 0.5rem;">
                                    <i class="fa-solid fa-key"></i> Nova Senha (opcional)
                                </span>
                                <input type="password" id="newPassword" name="new_password" 
                                       class="bbl-input" placeholder="Deixe em branco para não alterar">
                            </div>
                            
                            <div class="info-item" style="flex-direction: column; align-items: flex-start; background: transparent; border: none; padding: 0;">
                                <span class="info-label" style="margin-bottom: 0.5rem;">
                                    <i class="fa-solid fa-check-double"></i> Confirmar Nova Senha
                                </span>
                                <input type="password" id="confirmPassword" name="confirm_password" 
                                       class="bbl-input" placeholder="Confirme a nova senha">
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: flex-start; gap: 0.75rem; background: #eff6ff; border-left: 3px solid #3b82f6; padding: 1rem; border-radius: 8px; color: #1e40af; font-size: 0.875rem; margin-top: 1.5rem;">
                            <i class="fa-solid fa-circle-info" style="color: #3b82f6; font-size: 1rem; flex-shrink: 0; margin-top: 0.125rem;"></i>
                            <span>A senha deve ter no mínimo 8 caracteres. Deixe em branco se não quiser alterar.</span>
                        </div>
                    </div>
                </div>

                <!-- Ações -->
                <div class="bebelume-card" style="border: none; box-shadow: none; background: transparent;">
                    <div class="membership-actions" style="justify-content: flex-end; margin: 0;">
                        <button type="button" class="btn btn-outline" onclick="history.back()">
                            <i class="fa-solid fa-arrow-left"></i> Voltar
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveProfile">
                            <i class="fa-solid fa-floppy-disk"></i> Salvar Alterações
                        </button>
                    </div>
                </div>
                
            </form>

        </div>

    </div>

</div>

<!-- Toast -->
<div class="bbl-toast" id="toast">
    <div class="bbl-toast-icon" id="toastIcon">
        <i class="fa-solid fa-circle-check"></i>
    </div>
    <div class="bbl-toast-message" id="toastMessage"></div>
</div>

<?php 
// Carregar o footer do tema
get_footer(); 
?>

<style>
/* ========== FORÇAR FUNDO AZUL ========== */
html, body, body.wp-admin, #wpcontent, #wpbody, #wpbody-content, .wrap, #wpwrap {
    background-color: #00BCD4 !important;
    background: #00BCD4 !important;
}

/* ========== ESCONDER ADMIN ========== */
#wpadminbar, #adminmenumain, #adminmenuback, #adminmenuwrap, #wpfooter {
    display: none !important;
}

#wpcontent {
    margin-left: 0 !important;
    padding-left: 0 !important;
}

body.wp-admin, html.wp-toolbar {
    padding-top: 0 !important;
}

#wpbody-content {
    padding: 0 !important;
}

.wrap > h1, .wrap > hr {
    display: none !important;
}

/* ========== BEBELUME ACCOUNT PAGE STYLES ========== */

:root {
    --bbl-pink: #FF6B9D;
}

.bebelume-account-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.bebelume-account-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    max-width: 900px;
    margin: 0 auto;
}

@media (max-width: 968px) {
    .bebelume-account-grid {
        grid-template-columns: 1fr;
    }
}

/* ========== CARDS ========== */

.bebelume-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.bebelume-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.card-header {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    color: #1f2937;
    padding: 1.25rem 1.5rem;
}

.card-header h2,
.card-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 700;
    font-family: 'Nunito', sans-serif !important;
    color: var(--bbl-pink) !important;
}

.card-header i {
    margin-right: 0.5rem;
    color: var(--bbl-pink);
}

.card-content {
    padding: 1.5rem;
}

/* ========== WELCOME HEADER ========== */

.bebelume-welcome-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 2rem;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    color: #1f2937;
    transition: box-shadow 0.2s;
}

.bebelume-welcome-header:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.welcome-avatar img {
    border-radius: 50%;
    border: 3px solid #e5e7eb;
}

.welcome-info h1 {
    margin: 0;
    font-size: 1.875rem;
    font-weight: 800;
    font-family: 'Nunito', sans-serif !important;
    color: var(--bbl-pink) !important;
}

.welcome-info h1 i {
    color: var(--bbl-pink);
    margin-right: 0.5rem;
}

.welcome-subtitle {
    margin: 0.5rem 0 0;
    color: #6b7280;
    font-size: 0.95rem;
}

/* ========== INFO GRID ========== */

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #f3f4f6;
}

.info-label {
    color: #6b7280;
    font-weight: 500;
    display: flex;
    align-items: center;
}

.info-label i {
    margin-right: 0.5rem;
    width: 16px;
    text-align: center;
}

.info-value {
    color: #111827;
    font-weight: 600;
}

/* ========== INPUTS ========== */

.bbl-input {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: all 0.2s;
    font-family: inherit;
}

.bbl-input:focus {
    outline: none;
    border-color: var(--bbl-pink);
    box-shadow: 0 0 0 3px rgba(255, 107, 157, 0.1);
}

.bbl-input::placeholder {
    color: #9ca3af;
}

/* ========== BUTTONS ========== */

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    text-align: center;
    font-size: 0.9375rem;
}

.btn i {
    font-size: 1rem;
}

.btn-primary {
    background: #111827;
    color: white;
}

.btn-primary:hover {
    background: #1f2937;
    box-shadow: 0 4px 12px rgba(17, 24, 39, 0.25);
}

.btn-outline {
    background: transparent;
    border: 1.5px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.membership-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
    justify-content: center;
}

@media (max-width: 768px) {
    .bebelume-welcome-header {
        flex-direction: column;
        text-align: center;
    }
    
    .welcome-info h1 {
        font-size: 1.5rem;
    }
    
    .membership-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}

/* ========== TOAST ========== */

.bbl-toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    opacity: 0;
    transform: translateY(100px);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    z-index: 9999;
    border: 1px solid #e5e7eb;
    max-width: 400px;
}

.bbl-toast.show {
    opacity: 1;
    transform: translateY(0);
}

.bbl-toast-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.25rem;
}

.bbl-toast.success .bbl-toast-icon {
    background: #d1fae5;
    color: #059669;
}

.bbl-toast.error .bbl-toast-icon {
    background: #fee2e2;
    color: #dc2626;
}

.bbl-toast-message {
    color: #374151;
    font-weight: 500;
    font-size: 0.9375rem;
}

@media (max-width: 640px) {
    .bbl-toast {
        bottom: 1rem;
        right: 1rem;
        left: 1rem;
        max-width: none;
    }
}
</style>