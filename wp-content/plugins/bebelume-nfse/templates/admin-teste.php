<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap bbl-nfse-wrap">

    <h1 class="bbl-nfse-title">
        <span class="dashicons dashicons-hammer"></span>
        Teste de Emissão
    </h1>

    <div class="bbl-ambiente-banner <?php echo $is_homologacao ? 'bbl-homologacao' : 'bbl-producao'; ?>">
        <?php if ( $is_homologacao ) : ?>
            🧪 <strong>Homologação</strong> — as notas emitidas aqui são de teste e não têm validade fiscal.
        <?php else : ?>
            🔴 <strong>ATENÇÃO: ambiente de PRODUÇÃO</strong> — qualquer nota emitida aqui é
            <strong>real, tem validade fiscal e gera imposto</strong>. Para testar, troque o
            ambiente para Homologação em
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=bbl-nfse-settings' ) ); ?>">Configurações</a>.
        <?php endif; ?>
    </div>

    <div class="bbl-settings-card">
        <h2>💰 Dados da nota</h2>
        <table class="form-table">
            <tr>
                <th><label for="bbl-teste-valor">Valor (R$)</label></th>
                <td>
                    <input type="number" id="bbl-teste-valor" step="0.01" min="0.01"
                           value="1.00" class="regular-text" style="max-width:160px" />
                    <p class="description">Valor do serviço na nota. Use um valor baixo para testar.</p>
                </td>
            </tr>
            <tr>
                <th><label for="bbl-teste-descricao">Descrição do serviço</label></th>
                <td>
                    <input type="text" id="bbl-teste-descricao" class="large-text"
                           placeholder="<?php echo esc_attr( $desc_padrao ); ?>" />
                    <p class="description">Deixe em branco para usar a descrição das configurações.</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="bbl-settings-card">
        <h2>👤 Tomador</h2>

        <p>
            <label>
                <input type="radio" name="bbl-teste-origem" value="usuario" checked />
                Usar os dados de um <strong>usuário existente</strong>
            </label>
            &nbsp;&nbsp;
            <label>
                <input type="radio" name="bbl-teste-origem" value="manual" />
                Preencher <strong>manualmente</strong>
            </label>
        </p>

        <!-- Origem: usuário existente -->
        <table class="form-table bbl-origem bbl-origem-usuario">
            <tr>
                <th><label for="bbl-teste-user">Usuário</label></th>
                <td>
                    <?php
                    wp_dropdown_users( [
                        'id'               => 'bbl-teste-user',
                        'name'             => 'bbl_teste_user',
                        'selected'         => get_current_user_id(),
                        'show'             => 'display_name_with_login',
                        'number'           => 200,
                        'orderby'          => 'registered',
                        'order'            => 'DESC',
                    ] );
                    ?>
                    <button type="button" class="button" id="bbl-teste-carregar">Carregar dados</button>
                    <p class="description">
                        Lê CPF e endereço do perfil, exatamente como a emissão automática faria.
                        Clique em <em>Carregar dados</em> para conferir antes de enviar.
                    </p>
                    <div id="bbl-teste-preview" class="bbl-teste-preview" style="display:none;"></div>
                </td>
            </tr>
        </table>

        <!-- Origem: manual -->
        <table class="form-table bbl-origem bbl-origem-manual" style="display:none;">
            <tr>
                <th><label for="bbl-m-nome">Nome / Razão social</label></th>
                <td><input type="text" id="bbl-m-nome" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="bbl-m-email">E-mail</label></th>
                <td><input type="email" id="bbl-m-email" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="bbl-m-cpf">CPF / CNPJ</label></th>
                <td><input type="text" id="bbl-m-cpf" class="regular-text" placeholder="somente números" /></td>
            </tr>
            <tr>
                <th><label for="bbl-m-telefone">Telefone</label></th>
                <td><input type="text" id="bbl-m-telefone" class="regular-text" placeholder="61999998888" /></td>
            </tr>
            <tr>
                <th><label for="bbl-m-endereco">Endereço</label></th>
                <td><input type="text" id="bbl-m-endereco" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="bbl-m-bairro">Bairro</label></th>
                <td><input type="text" id="bbl-m-bairro" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="bbl-m-municipio">Município</label></th>
                <td><input type="text" id="bbl-m-municipio" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="bbl-m-uf">UF</label></th>
                <td><input type="text" id="bbl-m-uf" class="small-text" maxlength="2" placeholder="DF" /></td>
            </tr>
            <tr>
                <th><label for="bbl-m-cep">CEP</label></th>
                <td><input type="text" id="bbl-m-cep" class="regular-text" placeholder="70862020" /></td>
            </tr>
        </table>
    </div>

    <div class="bbl-settings-card">
        <?php if ( ! $is_homologacao ) : ?>
            <p class="bbl-teste-confirma">
                <label>
                    <input type="checkbox" id="bbl-teste-confirmo" />
                    Entendi que estou em <strong>produção</strong> e que esta nota será
                    <strong>real</strong>, com validade fiscal.
                </label>
            </p>
        <?php endif; ?>

        <p>
            <button type="button" id="bbl-teste-enviar" class="button button-primary button-hero"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>">
                <span class="dashicons dashicons-upload"></span>
                Enviar nota de teste
            </button>
        </p>

        <div id="bbl-teste-resultado" style="display:none;"></div>
    </div>

</div><!-- .bbl-nfse-wrap -->

<script>
(function($) {
    const ajaxUrl        = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
    const isHomologacao  = <?php echo $is_homologacao ? 'true' : 'false'; ?>;
    const urlNotas       = '<?php echo esc_js( admin_url( 'admin.php?page=bbl-nfse' ) ); ?>';

    // Alterna entre origem usuário / manual
    $('input[name="bbl-teste-origem"]').on('change', function() {
        const manual = this.value === 'manual';
        $('.bbl-origem-manual').toggle(manual);
        $('.bbl-origem-usuario').toggle(!manual);
    });

    // Pré-visualiza os dados lidos do perfil
    $('#bbl-teste-carregar').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).text('Carregando...');
        $.post(ajaxUrl, {
            action: 'bbl_nfse_teste_preview',
            nonce: $('#bbl-teste-enviar').data('nonce'),
            user_id: $('#bbl-teste-user').val()
        }, function(res) {
            btn.prop('disabled', false).text('Carregar dados');
            if (!res.success) { $('#bbl-teste-preview').hide(); alert('Erro: ' + res.data); return; }
            $('#bbl-teste-preview').html(res.data.html).show();
        });
    });

    // Envia a nota
    $('#bbl-teste-enviar').on('click', function() {
        const btn   = $(this);
        const valor = parseFloat($('#bbl-teste-valor').val());

        if (!valor || valor <= 0) { alert('Informe um valor maior que zero.'); return; }

        if (!isHomologacao) {
            if (!$('#bbl-teste-confirmo').is(':checked')) {
                alert('Você está em PRODUÇÃO. Marque a confirmação antes de enviar.');
                return;
            }
            if (!confirm('Esta nota será REAL, com validade fiscal. Continuar?')) return;
        }

        const dados = {
            action:    'bbl_nfse_teste_emitir',
            nonce:     btn.data('nonce'),
            valor:     valor,
            descricao: $('#bbl-teste-descricao').val(),
            origem:    $('input[name="bbl-teste-origem"]:checked').val(),
            user_id:   $('#bbl-teste-user').val(),
            nome:      $('#bbl-m-nome').val(),
            email:     $('#bbl-m-email').val(),
            cpf_cnpj:  $('#bbl-m-cpf').val(),
            telefone:  $('#bbl-m-telefone').val(),
            endereco:  $('#bbl-m-endereco').val(),
            bairro:    $('#bbl-m-bairro').val(),
            municipio: $('#bbl-m-municipio').val(),
            uf:        $('#bbl-m-uf').val(),
            cep:       $('#bbl-m-cep').val()
        };

        btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Enviando...');
        $('#bbl-teste-resultado').hide();

        $.post(ajaxUrl, dados, function(res) {
            btn.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Enviar nota de teste');

            const box = $('#bbl-teste-resultado');
            if (res.success) {
                box.html(
                    '<div class="bbl-teste-ok"><strong>✅ ' + res.data.mensagem + '</strong>' +
                    (res.data.id ? ' <a href="' + urlNotas + '">Ver na listagem →</a>' : '') + '</div>' +
                    '<h4>Payload enviado</h4><pre>' + res.data.payload + '</pre>' +
                    '<h4>Resposta da API</h4><pre>' + res.data.resposta + '</pre>'
                ).show();
            } else {
                const d = res.data || {};
                box.html(
                    '<div class="bbl-teste-erro"><strong>❌ ' + (d.mensagem || d) + '</strong></div>' +
                    (d.payload  ? '<h4>Payload enviado</h4><pre>' + d.payload + '</pre>'   : '') +
                    (d.resposta ? '<h4>Resposta da API</h4><pre>' + d.resposta + '</pre>' : '')
                ).show();
            }
        }).fail(function(xhr) {
            btn.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Enviar nota de teste');
            $('#bbl-teste-resultado')
                .html('<div class="bbl-teste-erro"><strong>❌ Falha na requisição (HTTP ' + xhr.status + ')</strong></div>')
                .show();
        });
    });

})(jQuery);
</script>
