<?php
/**
 * Plugin Name: Reenviar Plugin
 * Plugin URI:  https://example.com
 * Description: Adiciona botão "Reenviar plugin" na lista de plugins, permitindo substituir qualquer plugin via upload de .zip.
 * Version:     1.3.0
 * Author:      Claude
 * License:     GPL2
 * Text Domain: reenviar-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Reenviar_Plugin {

    public function __construct() {
        add_filter( 'plugin_action_links', [ $this, 'add_reenviar_link' ], 10, 2 );
        add_action( 'admin_footer-plugins.php', [ $this, 'render_modal' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_reenviar_plugin_upload', [ $this, 'handle_upload' ] );
        add_action( 'wp_ajax_reenviar_plugin_activate', [ $this, 'handle_activate' ] );
    }

    /**
     * Adiciona o link "Reenviar plugin" na linha de cada plugin.
     */
    public function add_reenviar_link( $actions, $plugin_file ) {
        $link = sprintf(
            '<a href="#" class="reenviar-plugin-btn" data-plugin="%s" style="color:#d63638;font-weight:600;">%s</a>',
            esc_attr( $plugin_file ),
            esc_html__( 'Reenviar plugin', 'reenviar-plugin' )
        );
        $actions['reenviar'] = $link;
        return $actions;
    }

    /**
     * Carrega CSS e JS na página de plugins.
     */
    public function enqueue_assets( $hook ) {
        if ( $hook !== 'plugins.php' ) {
            return;
        }
        // Inline CSS
        $css = '
        #reenviar-plugin-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.65);
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }
        #reenviar-plugin-overlay.active {
            display: flex;
        }
        #reenviar-plugin-modal {
            background: #fff;
            border-radius: 8px;
            padding: 32px 36px;
            width: 460px;
            max-width: 95vw;
            box-shadow: 0 8px 40px rgba(0,0,0,.25);
            position: relative;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        #reenviar-plugin-modal h2 {
            margin: 0 0 6px;
            font-size: 18px;
            color: #1d2327;
        }
        #reenviar-plugin-modal p.subtitle {
            margin: 0 0 20px;
            color: #646970;
            font-size: 13px;
        }
        #reenviar-plugin-modal .plugin-slug-label {
            display: inline-block;
            background: #f0f0f1;
            border-radius: 4px;
            padding: 2px 8px;
            font-family: monospace;
            font-size: 12px;
            color: #3c434a;
            margin-bottom: 18px;
        }
        #reenviar-plugin-dropzone {
            border: 2px dashed #c3c4c7;
            border-radius: 6px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            background: #fafafa;
            margin-bottom: 18px;
        }
        #reenviar-plugin-dropzone.dragover {
            border-color: #2271b1;
            background: #f0f6fc;
        }
        #reenviar-plugin-dropzone svg {
            display: block;
            margin: 0 auto 10px;
        }
        #reenviar-plugin-dropzone p {
            margin: 0;
            color: #646970;
            font-size: 13px;
        }
        #reenviar-plugin-dropzone p strong {
            color: #2271b1;
        }
        #reenviar-plugin-file-name {
            font-size: 13px;
            color: #1d2327;
            margin-bottom: 16px;
            word-break: break-all;
            display: none;
        }
        #reenviar-plugin-file-name span {
            background: #e7f3ff;
            border: 1px solid #72aee6;
            border-radius: 4px;
            padding: 4px 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .reenviar-plugin-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        #reenviar-plugin-submit {
            background: #2271b1;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }
        #reenviar-plugin-submit:hover:not(:disabled) {
            background: #135e96;
        }
        #reenviar-plugin-submit:disabled {
            opacity: .6;
            cursor: default;
        }
        #reenviar-plugin-cancel {
            background: #f6f7f7;
            color: #3c434a;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 13px;
            cursor: pointer;
        }
        #reenviar-plugin-cancel:hover {
            background: #f0f0f1;
        }
        #reenviar-plugin-close {
            position: absolute;
            top: 14px;
            right: 16px;
            background: none;
            border: none;
            font-size: 22px;
            color: #787c82;
            cursor: pointer;
            line-height: 1;
        }
        #reenviar-plugin-close:hover { color: #1d2327; }
        #reenviar-plugin-progress {
            display: none;
            margin-bottom: 14px;
        }
        #reenviar-plugin-progress-bar-wrap {
            background: #f0f0f1;
            border-radius: 4px;
            height: 8px;
            overflow: hidden;
            margin-top: 6px;
        }
        #reenviar-plugin-progress-bar {
            height: 8px;
            background: #2271b1;
            width: 0%;
            transition: width .3s;
            border-radius: 4px;
        }
        #reenviar-plugin-status {
            font-size: 12px;
            color: #646970;
        }
        #reenviar-plugin-result {
            display: none;
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 14px;
        }
        #reenviar-plugin-result.success {
            background: #edfaef;
            border: 1px solid #68de7c;
            color: #1a7024;
        }
        #reenviar-plugin-result.error {
            background: #fcf0f1;
            border: 1px solid #f86368;
            color: #8a1f1f;
        }
        ';
        wp_add_inline_style( 'wp-admin', $css );

        // Inline JS
        $nonce = wp_create_nonce( 'reenviar_plugin_nonce' );
        $ajax_url = admin_url( 'admin-ajax.php' );
        $js = <<<JS
(function(){
    var overlay   = null,
        modal     = null,
        fileInput = null,
        currentPlugin = '';

    document.addEventListener('DOMContentLoaded', function(){
        overlay   = document.getElementById('reenviar-plugin-overlay');
        modal     = document.getElementById('reenviar-plugin-modal');
        fileInput = document.getElementById('reenviar-plugin-file-input');

        // Abrir modal
        document.querySelectorAll('.reenviar-plugin-btn').forEach(function(btn){
            btn.addEventListener('click', function(e){
                e.preventDefault();
                currentPlugin = this.dataset.plugin;
                openModal(currentPlugin);
            });
        });

        // Fechar modal
        document.getElementById('reenviar-plugin-close').addEventListener('click', closeModal);
        document.getElementById('reenviar-plugin-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function(e){ if(e.target === overlay) closeModal(); });

        // Dropzone click
        document.getElementById('reenviar-plugin-dropzone').addEventListener('click', function(){
            fileInput.click();
        });

        // Drag & drop
        var dz = document.getElementById('reenviar-plugin-dropzone');
        dz.addEventListener('dragover', function(e){ e.preventDefault(); dz.classList.add('dragover'); });
        dz.addEventListener('dragleave', function(){ dz.classList.remove('dragover'); });
        dz.addEventListener('drop', function(e){
            e.preventDefault();
            dz.classList.remove('dragover');
            if(e.dataTransfer.files[0]) setFile(e.dataTransfer.files[0]);
        });

        // File input change
        fileInput.addEventListener('change', function(){
            if(this.files[0]) setFile(this.files[0]);
        });

        // Submit
        document.getElementById('reenviar-plugin-submit').addEventListener('click', doUpload);
    });

    function openModal(pluginFile){
        resetModal();
        document.getElementById('reenviar-plugin-slug').textContent = pluginFile;
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(){
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        resetModal();
    }

    function resetModal(){
        fileInput.value = '';
        document.getElementById('reenviar-plugin-file-name').style.display = 'none';
        document.getElementById('reenviar-plugin-file-name').querySelector('span').textContent = '';
        document.getElementById('reenviar-plugin-submit').disabled = true;
        document.getElementById('reenviar-plugin-progress').style.display = 'none';
        document.getElementById('reenviar-plugin-progress-bar').style.width = '0%';
        document.getElementById('reenviar-plugin-status').textContent = '';
        document.getElementById('reenviar-plugin-result').style.display = 'none';
        document.getElementById('reenviar-plugin-result').className = '';
        document.getElementById('reenviar-plugin-result').textContent = '';
        document.getElementById('reenviar-plugin-dropzone').style.display = '';
        document.getElementById('reenviar-plugin-cancel').textContent = 'Cancelar';
    }

    function setFile(file){
        if(!file.name.match(/\.zip$/i)){
            alert('Por favor, selecione um arquivo .zip válido.');
            return;
        }
        var nameEl = document.getElementById('reenviar-plugin-file-name');
        nameEl.style.display = 'block';
        nameEl.querySelector('span').textContent = '📦 ' + file.name;
        document.getElementById('reenviar-plugin-submit').disabled = false;
    }

    function doUpload(){
        var file = fileInput.files[0];
        if(!file){ alert('Selecione um arquivo .zip primeiro.'); return; }

        var formData = new FormData();
        formData.append('action', 'reenviar_plugin_upload');
        formData.append('nonce', '{$nonce}');
        formData.append('plugin_file', currentPlugin);
        formData.append('zip_file', file);

        document.getElementById('reenviar-plugin-submit').disabled = true;
        document.getElementById('reenviar-plugin-cancel').disabled = true;
        document.getElementById('reenviar-plugin-progress').style.display = 'block';
        document.getElementById('reenviar-plugin-dropzone').style.display = 'none';
        document.getElementById('reenviar-plugin-file-name').style.display = 'none';

        var steps = ['Enviando arquivo…', 'Desativando plugin…', 'Excluindo plugin…', 'Instalando novo plugin…', 'Ativando plugin…'];
        var stepIdx = 0;
        var bar = document.getElementById('reenviar-plugin-progress-bar');
        var statusEl = document.getElementById('reenviar-plugin-status');

        statusEl.textContent = steps[0];
        bar.style.width = '5%';

        var interval = setInterval(function(){
            stepIdx++;
            if(stepIdx < steps.length){
                statusEl.textContent = steps[stepIdx];
                bar.style.width = (stepIdx * 20 + 5) + '%';
            }
        }, 800);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '{$ajax_url}');
        xhr.onload = function(){
            clearInterval(interval);
            var resultEl = document.getElementById('reenviar-plugin-result');
            resultEl.style.display = 'block';
            try {
                var resp = JSON.parse(xhr.responseText);
                if(resp.success){
                    if(resp.data.need_activate){
                        // Segundo request: ativar num processo PHP limpo
                        statusEl.textContent = 'Ativando plugin...';
                        bar.style.width = '90%';
                        resultEl.style.display = 'none';

                        var fd2 = new FormData();
                        fd2.append('action', 'reenviar_plugin_activate');
                        fd2.append('nonce', resp.data.activate_nonce);
                        fd2.append('new_plugin', resp.data.new_plugin);

                        var xhr2 = new XMLHttpRequest();
                        xhr2.open('POST', '{$ajax_url}');
                        xhr2.onload = function(){
                            bar.style.width = '100%';
                            statusEl.textContent = '';
                            resultEl.style.display = 'block';
                            try {
                                var r2 = JSON.parse(xhr2.responseText);
                                if(r2.success){
                                    resultEl.className = 'success';
                                    resultEl.textContent = '✅ ' + (r2.data.message || 'Plugin substituído e ativado com sucesso!');
                                    document.getElementById('reenviar-plugin-cancel').disabled = false;
                                    document.getElementById('reenviar-plugin-cancel').textContent = 'Fechar (recarregando…)';
                                    setTimeout(function(){ window.location.reload(); }, 2000);
                                } else {
                                    resultEl.className = 'error';
                                    resultEl.textContent = '❌ ' + (r2.data || 'Erro ao ativar.');
                                    document.getElementById('reenviar-plugin-submit').disabled = false;
                                    document.getElementById('reenviar-plugin-cancel').disabled = false;
                                }
                            } catch(e2) {
                                resultEl.className = 'error';
                                resultEl.textContent = '❌ Resposta inválida ao ativar: ' + xhr2.responseText.substring(0,200);
                                document.getElementById('reenviar-plugin-submit').disabled = false;
                                document.getElementById('reenviar-plugin-cancel').disabled = false;
                            }
                        };
                        xhr2.onerror = function(){
                            resultEl.style.display = 'block';
                            resultEl.className = 'error';
                            resultEl.textContent = '❌ Falha de conexão ao ativar.';
                            document.getElementById('reenviar-plugin-submit').disabled = false;
                            document.getElementById('reenviar-plugin-cancel').disabled = false;
                        };
                        xhr2.send(fd2);
                    } else {
                        bar.style.width = '100%';
                        resultEl.className = 'success';
                        resultEl.textContent = '✅ ' + (resp.data.message || 'Plugin substituído e ativado com sucesso!');
                        document.getElementById('reenviar-plugin-cancel').disabled = false;
                        document.getElementById('reenviar-plugin-cancel').textContent = 'Fechar (recarregando…)';
                        setTimeout(function(){ window.location.reload(); }, 2000);
                    }
                } else {
                    resultEl.className = 'error';
                    resultEl.textContent = '❌ ' + (resp.data || 'Erro desconhecido.');
                    document.getElementById('reenviar-plugin-submit').disabled = false;
                    document.getElementById('reenviar-plugin-cancel').disabled = false;
                }
            } catch(e) {
                resultEl.className = 'error';
                resultEl.textContent = '❌ Resposta inválida do servidor: ' + xhr.responseText.substring(0,200);
                document.getElementById('reenviar-plugin-submit').disabled = false;
                document.getElementById('reenviar-plugin-cancel').disabled = false;
            }
        };
        xhr.onerror = function(){
            clearInterval(interval);
            var resultEl = document.getElementById('reenviar-plugin-result');
            resultEl.style.display = 'block';
            resultEl.className = 'error';
            resultEl.textContent = '❌ Falha na conexão. Tente novamente.';
            document.getElementById('reenviar-plugin-submit').disabled = false;
            document.getElementById('reenviar-plugin-cancel').disabled = false;
        };
        xhr.send(formData);
    }
})();
JS;
        wp_add_inline_script( 'jquery', $js );
    }

    /**
     * Renderiza o HTML do modal e o overlay no rodapé da página de plugins.
     */
    public function render_modal() {
        ?>
        <div id="reenviar-plugin-overlay">
            <div id="reenviar-plugin-modal" role="dialog" aria-modal="true" aria-labelledby="reenviar-modal-title">
                <button id="reenviar-plugin-close" aria-label="Fechar">&times;</button>
                <h2 id="reenviar-modal-title">Reenviar Plugin</h2>
                <p class="subtitle">Faça upload do <strong>.zip</strong> do novo plugin. O plugin atual será <strong>desativado, excluído</strong> e o novo será <strong>instalado e ativado</strong> automaticamente.</p>
                <div class="plugin-slug-label" id="reenviar-plugin-slug"></div>

                <div id="reenviar-plugin-dropzone" tabindex="0" role="button" aria-label="Selecionar arquivo .zip">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <p><strong>Clique para selecionar</strong> ou arraste o arquivo .zip aqui</p>
                </div>

                <div id="reenviar-plugin-file-name"><span></span></div>

                <div id="reenviar-plugin-progress">
                    <div id="reenviar-plugin-status"></div>
                    <div id="reenviar-plugin-progress-bar-wrap">
                        <div id="reenviar-plugin-progress-bar"></div>
                    </div>
                </div>

                <div id="reenviar-plugin-result"></div>

                <input type="file" id="reenviar-plugin-file-input" accept=".zip" style="display:none;">

                <div class="reenviar-plugin-actions">
                    <button id="reenviar-plugin-cancel">Cancelar</button>
                    <button id="reenviar-plugin-submit" disabled>Instalar agora</button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handler AJAX: recebe o .zip, desativa, exclui e instala o plugin.
     * A ativação é feita num segundo request para evitar "Cannot redeclare"
     * quando o plugin antigo ainda está carregado na memória PHP.
     */
    public function handle_upload() {
        @set_time_limit( 300 );

        if ( ! check_ajax_referer( 'reenviar_plugin_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Nonce inválido. Recarregue a página e tente novamente.' );
        }
        if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'delete_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
            wp_send_json_error( 'Permissão insuficiente.' );
        }

        $plugin_file = isset( $_POST['plugin_file'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_file'] ) ) : '';
        if ( empty( $plugin_file ) ) {
            wp_send_json_error( 'Plugin não especificado.' );
        }

        // Verifica upload
        if ( empty( $_FILES['zip_file'] ) || ! isset( $_FILES['zip_file']['tmp_name'] ) ) {
            wp_send_json_error( 'Nenhum arquivo recebido.' );
        }
        $f = $_FILES['zip_file'];
        if ( $f['error'] !== UPLOAD_ERR_OK ) {
            $msgs = [
                1 => 'Arquivo maior que upload_max_filesize no php.ini.',
                2 => 'Arquivo maior que MAX_FILE_SIZE no formulário.',
                3 => 'Upload incompleto. Tente novamente.',
                4 => 'Nenhum arquivo selecionado.',
                6 => 'Pasta temporária ausente no servidor.',
                7 => 'Sem permissão de escrita em disco.',
                8 => 'Upload bloqueado por extensão PHP.',
            ];
            $msg = isset( $msgs[ $f['error'] ] ) ? $msgs[ $f['error'] ] : 'Erro no upload (código ' . $f['error'] . ').';
            wp_send_json_error( $msg );
        }
        if ( $f['size'] === 0 ) {
            wp_send_json_error( 'O arquivo está vazio.' );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        // 1. Desativar
        if ( is_plugin_active( $plugin_file ) ) {
            deactivate_plugins( $plugin_file );
        }

        // 2. Excluir
        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname( $plugin_file );
        if ( dirname( $plugin_file ) !== '.' && file_exists( $plugin_dir ) ) {
            delete_plugins( [ $plugin_file ] );
        } elseif ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
            @unlink( WP_PLUGIN_DIR . '/' . $plugin_file );
        }

        // 3. Mover o zip para path temporário com extensão .zip
        $tmp_dir  = get_temp_dir();
        $zip_path = $tmp_dir . 'reenviar-plugin-' . uniqid() . '.zip';

        if ( ! move_uploaded_file( $f['tmp_name'], $zip_path ) ) {
            wp_send_json_error( 'Não foi possível salvar o arquivo temporário. Verifique permissões em: ' . $tmp_dir );
        }

        // 4. Instalar via Plugin_Upgrader
        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );
        $result   = $upgrader->install( $zip_path );

        @unlink( $zip_path );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( 'Erro na instalação: ' . $result->get_error_message() );
        }
        if ( ! $result ) {
            $errors = $skin->get_errors();
            $msg = ( is_wp_error( $errors ) && $errors->has_errors() )
                ? $errors->get_error_message()
                : 'Falha na instalação. Certifique-se de que o .zip contém um plugin WordPress válido.';
            wp_send_json_error( $msg );
        }

        // 5. Descobrir o slug do novo plugin
        $new_plugin = $upgrader->plugin_info();
        if ( empty( $new_plugin ) ) {
            wp_send_json_error( 'Plugin instalado, mas não foi possível identificá-lo para ativar. Ative manualmente.' );
        }

        // 6. NÃO ativar aqui — retornar o slug para o JS ativar num segundo request.
        //    Isso evita o fatal "Cannot redeclare" pois o plugin antigo ainda está
        //    carregado na memória PHP desta requisição.
        $activate_nonce = wp_create_nonce( 'reenviar_activate_nonce' );
        wp_send_json_success( [
            'need_activate'  => true,
            'new_plugin'     => $new_plugin,
            'activate_nonce' => $activate_nonce,
            'message'        => 'Plugin instalado! Ativando...',
        ] );
    }

    /**
     * Segundo AJAX: ativa o plugin já instalado num processo PHP limpo,
     * sem o código do plugin antigo na memória.
     */
    public function handle_activate() {
        if ( ! check_ajax_referer( 'reenviar_activate_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Nonce de ativação inválido.' );
        }
        if ( ! current_user_can( 'activate_plugins' ) ) {
            wp_send_json_error( 'Permissão insuficiente para ativar plugins.' );
        }

        $new_plugin = isset( $_POST['new_plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['new_plugin'] ) ) : '';
        if ( empty( $new_plugin ) ) {
            wp_send_json_error( 'Slug do plugin não informado.' );
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $activate = activate_plugin( $new_plugin );
        if ( is_wp_error( $activate ) ) {
            wp_send_json_error( 'Plugin instalado mas não ativado: ' . $activate->get_error_message() );
        }

        wp_send_json_success( [
            'message' => 'Plugin substituído e ativado com sucesso! (' . esc_html( $new_plugin ) . ')',
        ] );
    }
}

new Reenviar_Plugin();
