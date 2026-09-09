<?php
/**
 * Plugin Name: Bebelume Headers
 * Description: Gerencie scripts e códigos no <head> de todas as páginas pelo painel do WordPress.
 * Version: 2.0.0
 * Author: Bebelume
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Injetar scripts ativos no <head> ────────────────────────────────────────
function bebelume_inject_header_scripts() {
    $scripts = get_option( 'bebelume_header_scripts', [] );
    foreach ( $scripts as $script ) {
        if ( ! empty( $script['active'] ) ) {
            echo "\n<!-- Bebelume: " . esc_html( $script['name'] ) . " -->\n";
            echo $script['code'] . "\n";
        }
    }
}
add_action( 'wp_head', 'bebelume_inject_header_scripts' );

// ─── Menu no painel admin ─────────────────────────────────────────────────────
function bebelume_admin_menu() {
    add_menu_page(
        'Bebelume Headers',
        'Bebelume Headers',
        'manage_options',
        'bebelume-headers',
        'bebelume_admin_page',
        'dashicons-editor-code',
        80
    );
}
add_action( 'admin_menu', 'bebelume_admin_menu' );

// ─── Processar ações ──────────────────────────────────────────────────────────
function bebelume_handle_actions() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( ! isset( $_POST['bebelume_nonce'] ) || ! wp_verify_nonce( $_POST['bebelume_nonce'], 'bebelume_action' ) ) return;

    $scripts = get_option( 'bebelume_header_scripts', [] );

    if ( isset( $_POST['bebelume_save'] ) ) {
        $index = isset( $_POST['edit_index'] ) && $_POST['edit_index'] !== '' ? intval( $_POST['edit_index'] ) : null;
        $entry = [
            'name'   => sanitize_text_field( $_POST['script_name'] ),
            'code'   => wp_unslash( $_POST['script_code'] ),
            'active' => true,
        ];
        if ( $index !== null && isset( $scripts[ $index ] ) ) {
            $entry['active'] = $scripts[ $index ]['active'];
            $scripts[ $index ] = $entry;
        } else {
            $scripts[] = $entry;
        }
        update_option( 'bebelume_header_scripts', $scripts );
        wp_redirect( admin_url( 'admin.php?page=bebelume-headers&saved=1' ) );
        exit;
    }

    if ( isset( $_POST['bebelume_toggle'] ) ) {
        $i = intval( $_POST['toggle_index'] );
        if ( isset( $scripts[ $i ] ) ) {
            $scripts[ $i ]['active'] = ! $scripts[ $i ]['active'];
            update_option( 'bebelume_header_scripts', $scripts );
        }
        wp_redirect( admin_url( 'admin.php?page=bebelume-headers' ) );
        exit;
    }

    if ( isset( $_POST['bebelume_delete'] ) ) {
        $i = intval( $_POST['delete_index'] );
        if ( isset( $scripts[ $i ] ) ) {
            array_splice( $scripts, $i, 1 );
            update_option( 'bebelume_header_scripts', $scripts );
        }
        wp_redirect( admin_url( 'admin.php?page=bebelume-headers' ) );
        exit;
    }
}
add_action( 'admin_init', 'bebelume_handle_actions' );

// ─── Página admin ─────────────────────────────────────────────────────────────
function bebelume_admin_page() {
    $scripts    = get_option( 'bebelume_header_scripts', [] );
    $edit_index = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : null;
    $editing    = $edit_index !== null && isset( $scripts[ $edit_index ] ) ? $scripts[ $edit_index ] : null;
    ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Syne:wght@400;600;800&display=swap');
        #bebelume-wrap * { box-sizing: border-box; }
        #bebelume-wrap {
            font-family: 'Syne', sans-serif;
            max-width: 900px;
            margin: 30px 20px;
            color: #0f0f0f;
        }
        #bebelume-wrap h1 {
            font-size: 28px; font-weight: 800; letter-spacing: -0.5px;
            margin-bottom: 6px; display: flex; align-items: center; gap: 10px;
        }
        #bebelume-wrap h1 span.badge {
            font-size: 11px; font-weight: 600; background: #0f0f0f; color: #fff;
            padding: 3px 8px; border-radius: 20px; letter-spacing: 1px; text-transform: uppercase;
        }
        .bbl-subtitle { color: #666; font-size: 14px; margin-bottom: 32px; }
        .bbl-card {
            background: #fff; border: 1.5px solid #e5e5e5;
            border-radius: 12px; padding: 24px; margin-bottom: 32px;
        }
        .bbl-card h2 {
            font-size: 16px; font-weight: 700; margin: 0 0 20px;
            padding-bottom: 14px; border-bottom: 1.5px solid #f0f0f0;
        }
        .bbl-field { margin-bottom: 16px; }
        .bbl-field label {
            display: block; font-size: 12px; font-weight: 600;
            letter-spacing: .5px; text-transform: uppercase; color: #555; margin-bottom: 6px;
        }
        .bbl-field input[type=text] {
            width: 100%; padding: 10px 14px; border: 1.5px solid #e0e0e0;
            border-radius: 8px; font-family: 'Syne', sans-serif; font-size: 14px;
            transition: border-color .2s; outline: none;
        }
        .bbl-field input[type=text]:focus { border-color: #0f0f0f; }
        .bbl-field textarea {
            width: 100%; min-height: 160px; padding: 12px 14px;
            border: 1.5px solid #e0e0e0; border-radius: 8px;
            font-family: 'JetBrains Mono', monospace; font-size: 13px;
            line-height: 1.6; resize: vertical; transition: border-color .2s; outline: none;
        }
        .bbl-field textarea:focus { border-color: #0f0f0f; }
        .bbl-actions { display: flex; gap: 10px; align-items: center; }
        .bbl-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 20px; border-radius: 8px; font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 600; cursor: pointer;
            border: 1.5px solid transparent; transition: all .15s; text-decoration: none;
        }
        .bbl-btn-primary { background: #0f0f0f; color: #fff; }
        .bbl-btn-primary:hover { background: #333; color: #fff; }
        .bbl-btn-ghost { background: transparent; color: #555; border-color: #e0e0e0; }
        .bbl-btn-ghost:hover { border-color: #999; color: #0f0f0f; }
        .bbl-table-wrap { overflow-x: auto; }
        table.bbl-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .bbl-table thead th {
            text-align: left; font-size: 11px; font-weight: 600; letter-spacing: .6px;
            text-transform: uppercase; color: #888; padding: 0 14px 12px;
            border-bottom: 1.5px solid #f0f0f0;
        }
        .bbl-table tbody tr { border-bottom: 1px solid #f5f5f5; transition: background .1s; }
        .bbl-table tbody tr:hover { background: #fafafa; }
        .bbl-table td { padding: 14px; vertical-align: middle; }
        .bbl-table td.name { font-weight: 600; }
        .bbl-table td.code-preview {
            font-family: 'JetBrains Mono', monospace; font-size: 11px;
            color: #888; max-width: 300px; overflow: hidden;
            text-overflow: ellipsis; white-space: nowrap;
        }
        .bbl-toggle-wrap { display: flex; align-items: center; gap: 8px; }
        .bbl-toggle {
            position: relative; width: 36px; height: 20px; background: #e0e0e0;
            border-radius: 20px; cursor: pointer; transition: background .2s;
            border: none; padding: 0;
        }
        .bbl-toggle.on { background: #22c55e; }
        .bbl-toggle::after {
            content: ''; position: absolute; top: 3px; left: 3px;
            width: 14px; height: 14px; background: #fff; border-radius: 50%;
            transition: left .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }
        .bbl-toggle.on::after { left: 19px; }
        .bbl-status { font-size: 11px; font-weight: 600; color: #aaa; }
        .bbl-status.on { color: #22c55e; }
        .bbl-row-actions { display: flex; gap: 6px; }
        .bbl-btn-sm { padding: 5px 12px; font-size: 12px; border-radius: 6px; }
        .bbl-btn-danger { background: transparent; color: #ef4444; border-color: #fecaca; }
        .bbl-btn-danger:hover { background: #fef2f2; border-color: #ef4444; color: #ef4444; }
        .bbl-empty { text-align: center; padding: 40px; color: #aaa; font-size: 14px; }
        .bbl-notice {
            background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #166534;
            padding: 10px 16px; border-radius: 8px; font-size: 13px;
            font-weight: 600; margin-bottom: 20px;
        }
    </style>

    <div id="bebelume-wrap">
        <h1>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            Bebelume Headers
            <span class="badge">v2.0</span>
        </h1>
        <p class="bbl-subtitle">Gerencie scripts injetados no &lt;head&gt; de todas as páginas.</p>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
            <div class="bbl-notice">✓ Script salvo com sucesso!</div>
        <?php endif; ?>

        <div class="bbl-card">
            <h2><?php echo $editing ? '✏️ Editar script' : '＋ Adicionar novo script'; ?></h2>
            <form method="post">
                <?php wp_nonce_field( 'bebelume_action', 'bebelume_nonce' ); ?>
                <?php if ( $editing ) : ?>
                    <input type="hidden" name="edit_index" value="<?php echo $edit_index; ?>">
                <?php endif; ?>
                <div class="bbl-field">
                    <label>Nome do script</label>
                    <input type="text" name="script_name" placeholder="Ex: Meta Pixel, Google Analytics..." required
                        value="<?php echo $editing ? esc_attr( $editing['name'] ) : ''; ?>">
                </div>
                <div class="bbl-field">
                    <label>Código</label>
                    <textarea name="script_code" placeholder="Cole aqui o código HTML/JS..." required><?php echo $editing ? esc_textarea( $editing['code'] ) : ''; ?></textarea>
                </div>
                <div class="bbl-actions">
                    <button type="submit" name="bebelume_save" class="bbl-btn bbl-btn-primary">
                        <?php echo $editing ? '💾 Salvar alterações' : '＋ Adicionar script'; ?>
                    </button>
                    <?php if ( $editing ) : ?>
                        <a href="<?php echo admin_url( 'admin.php?page=bebelume-headers' ); ?>" class="bbl-btn bbl-btn-ghost">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="bbl-card">
            <h2>📋 Scripts cadastrados</h2>
            <?php if ( empty( $scripts ) ) : ?>
                <div class="bbl-empty">Nenhum script cadastrado ainda.</div>
            <?php else : ?>
            <div class="bbl-table-wrap">
                <table class="bbl-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Prévia do código</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $scripts as $i => $s ) : ?>
                        <tr>
                            <td class="name"><?php echo esc_html( $s['name'] ); ?></td>
                            <td class="code-preview"><?php echo esc_html( substr( $s['code'], 0, 80 ) ); ?>...</td>
                            <td>
                                <form method="post" style="margin:0">
                                    <?php wp_nonce_field( 'bebelume_action', 'bebelume_nonce' ); ?>
                                    <input type="hidden" name="toggle_index" value="<?php echo $i; ?>">
                                    <div class="bbl-toggle-wrap">
                                        <button type="submit" name="bebelume_toggle"
                                            class="bbl-toggle <?php echo ! empty( $s['active'] ) ? 'on' : ''; ?>"
                                            title="<?php echo ! empty( $s['active'] ) ? 'Desativar' : 'Ativar'; ?>">
                                        </button>
                                        <span class="bbl-status <?php echo ! empty( $s['active'] ) ? 'on' : ''; ?>">
                                            <?php echo ! empty( $s['active'] ) ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </div>
                                </form>
                            </td>
                            <td>
                                <div class="bbl-row-actions">
                                    <a href="<?php echo admin_url( 'admin.php?page=bebelume-headers&edit=' . $i ); ?>"
                                        class="bbl-btn bbl-btn-ghost bbl-btn-sm">✏️ Editar</a>
                                    <form method="post" style="margin:0" onsubmit="return confirm('Remover este script?')">
                                        <?php wp_nonce_field( 'bebelume_action', 'bebelume_nonce' ); ?>
                                        <input type="hidden" name="delete_index" value="<?php echo $i; ?>">
                                        <button type="submit" name="bebelume_delete" class="bbl-btn bbl-btn-danger bbl-btn-sm">🗑 Remover</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
