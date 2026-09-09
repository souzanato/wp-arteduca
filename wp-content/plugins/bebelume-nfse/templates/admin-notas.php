<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap bbl-nfse-wrap">

    <h1 class="bbl-nfse-title">
        <span class="dashicons dashicons-media-document"></span>
        NFS-e Emitidas
    </h1>

    <!-- Filtros de status -->
    <div class="bbl-nfse-filters">
        <?php
        $filtros = [ '' => 'Todas', 'pendente' => 'Pendentes', 'processando' => 'Processando',
                     'aprovada' => 'Aprovadas', 'reprovada' => 'Reprovadas',
                     'cancelada' => 'Canceladas', 'erro' => 'Com Erro' ];
        foreach ( $filtros as $val => $label ) :
            $url     = add_query_arg( [ 'page' => 'bbl-nfse', 'status_filter' => $val ], admin_url( 'admin.php' ) );
            $ativo   = $status_filter === $val ? 'active' : '';
        ?>
            <a href="<?php echo esc_url( $url ); ?>" class="bbl-filter-btn <?php echo $ativo; ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Tabela de notas -->
    <div class="bbl-nfse-table-wrap">
        <?php if ( empty( $notas ) ) : ?>
            <div class="bbl-empty">
                <span class="dashicons dashicons-media-document"></span>
                <p>Nenhuma NFS-e encontrada.</p>
            </div>
        <?php else : ?>
        <table class="bbl-nfse-table wp-list-table widefat">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pedido PMPro</th>
                    <th>Usuário</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Nº Nota</th>
                    <th>Emitida em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $notas as $nota ) :
                $user       = get_userdata( $nota->user_id );
                $nome       = $user ? $user->display_name : "Usuário #{$nota->user_id}";
                $email      = $user ? $user->user_email : '';
                $sl         = $status_labels[ $nota->status ] ?? [ 'label' => ucfirst( $nota->status ), 'color' => '#888' ];
            ?>
                <tr data-id="<?php echo (int) $nota->id; ?>">
                    <td><?php echo (int) $nota->id; ?></td>
                    <td>
                        <?php if ( $nota->order_id ) :
                            $url = admin_url( 'admin.php?page=pmpro-orders&order=' . $nota->order_id );
                        ?>
                            <a href="<?php echo esc_url( $url ); ?>" target="_blank">#<?php echo (int) $nota->order_id; ?></a>
                        <?php else : ?>
                            <span class="bbl-badge-teste" title="Emitida pela tela de Teste de Emissão">Teste</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html( $nome ); ?></strong><br>
                        <small><?php echo esc_html( $email ); ?></small>
                    </td>
                    <td><strong>R$ <?php echo number_format( (float) $nota->valor, 2, ',', '.' ); ?></strong></td>
                    <td>
                        <span class="bbl-badge" style="background:<?php echo esc_attr( $sl['color'] ); ?>">
                            <?php echo esc_html( $sl['label'] ); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ( $nota->numero_nfse ) : ?>
                            <strong><?php echo esc_html( $nota->numero_nfse ); ?></strong>
                        <?php else : ?>—<?php endif; ?>
                    </td>
                    <td><?php
                        $ts = $nota->created_at ? strtotime( $nota->created_at ) : 0;
                        echo $ts > 0 ? esc_html( date_i18n( 'd/m/Y H:i', $ts ) ) : '—';
                    ?></td>
                    <td class="bbl-actions">
                        <!-- PDF -->
                        <?php if ( $nota->link_pdf ) : ?>
                            <a href="<?php echo esc_url( $nota->link_pdf ); ?>" target="_blank" class="button button-small" title="Baixar PDF">
                                <span class="dashicons dashicons-pdf"></span> PDF
                            </a>
                        <?php endif; ?>

                        <!-- XML -->
                        <?php if ( $nota->link_xml ) : ?>
                            <a href="<?php echo esc_url( $nota->link_xml ); ?>" target="_blank" class="button button-small" title="Baixar XML">
                                <span class="dashicons dashicons-download"></span> XML
                            </a>
                        <?php endif; ?>

                        <!-- Checar status agora -->
                        <?php if ( in_array( $nota->status, [ 'pendente', 'processando' ] ) && $nota->searchkey ) : ?>
                            <button class="button button-small bbl-btn-check" data-id="<?php echo (int) $nota->id; ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
                                <span class="dashicons dashicons-update"></span> Atualizar
                            </button>
                        <?php endif; ?>

                        <!-- Cancelar -->
                        <?php if ( in_array( $nota->status, [ 'pendente', 'processando', 'aprovada' ] ) && $nota->searchkey ) : ?>
                            <button class="button button-small bbl-btn-cancel" data-id="<?php echo (int) $nota->id; ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
                                <span class="dashicons dashicons-no-alt"></span> Cancelar
                            </button>
                        <?php endif; ?>

                        <!-- Reemitir -->
                        <?php if ( in_array( $nota->status, [ 'erro', 'reprovada' ] ) && $nota->payload ) : ?>
                            <button class="button button-small bbl-btn-reemit" data-id="<?php echo (int) $nota->id; ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
                                <span class="dashicons dashicons-controls-repeat"></span> Reemitir
                            </button>
                        <?php endif; ?>

                        <!-- Detalhes / resposta API -->
                        <button class="button button-small bbl-btn-detail" data-resposta="<?php echo esc_attr( $nota->resposta ); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginação -->
        <?php if ( $pages > 1 ) : ?>
        <div class="bbl-pagination">
            <?php for ( $i = 1; $i <= $pages; $i++ ) :
                $url = add_query_arg( [ 'page' => 'bbl-nfse', 'paged' => $i, 'status_filter' => $status_filter ], admin_url( 'admin.php' ) );
                $ativo = $i === $page_num ? 'current' : '';
            ?>
                <a href="<?php echo esc_url( $url ); ?>" class="bbl-page-btn <?php echo $ativo; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php endif; // empty notas ?>
    </div><!-- .bbl-nfse-table-wrap -->

</div><!-- .bbl-nfse-wrap -->

<!-- Modal de detalhe da resposta -->
<div id="bbl-modal-detail" style="display:none;">
    <div class="bbl-modal-overlay"></div>
    <div class="bbl-modal-box">
        <button class="bbl-modal-close">&times;</button>
        <h3>Resposta da API</h3>
        <pre id="bbl-modal-json"></pre>
    </div>
</div>

<!-- Modal de cancelamento -->
<div id="bbl-modal-cancel" style="display:none;">
    <div class="bbl-modal-overlay"></div>
    <div class="bbl-modal-box">
        <button class="bbl-modal-close">&times;</button>
        <h3>Cancelar NFS-e</h3>
        <p>Selecione o motivo do cancelamento:</p>
        <select id="bbl-cancel-motivo">
            <option value="1">1 – Erro de Emissão</option>
            <option value="2">2 – Serviço Não Prestado</option>
            <option value="3">3 – Erro de Assinatura</option>
            <option value="4">4 – Duplicidade de Nota</option>
            <option value="5">5 – Erro de Processamento</option>
        </select>
        <div class="bbl-modal-actions">
            <button id="bbl-cancel-confirm" class="button button-primary">Confirmar Cancelamento</button>
            <button class="button bbl-modal-close-btn">Voltar</button>
        </div>
        <input type="hidden" id="bbl-cancel-id">
        <input type="hidden" id="bbl-cancel-nonce">
    </div>
</div>

<script>
(function($) {
    const ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

    // Abrir detalhe
    $(document).on('click', '.bbl-btn-detail', function() {
        // Usar getAttribute para pegar a string raw sem parse automático do jQuery
        let raw = this.getAttribute('data-resposta') || '';
        try { raw = JSON.stringify(JSON.parse(raw), null, 2); } catch(e) {}
        $('#bbl-modal-json').text(raw || 'Sem resposta registrada.');
        $('#bbl-modal-detail').show();
    });

    // Fechar modais
    $(document).on('click', '.bbl-modal-close, .bbl-modal-overlay, .bbl-modal-close-btn', function() {
        $('.bbl-modal-detail, #bbl-modal-detail, #bbl-modal-cancel').hide();
    });

    // Atualizar status agora
    $(document).on('click', '.bbl-btn-check', function() {
        const btn = $(this), id = btn.data('id'), nonce = btn.data('nonce');
        btn.prop('disabled', true).text('Consultando...');
        $.post(ajaxUrl, { action: 'bbl_nfse_check_now', id, nonce }, function(res) {
            if (res.success) {
                alert('Status: ' + res.data.status + (res.data.numero_nfse ? ' | Nota: ' + res.data.numero_nfse : ''));
                location.reload();
            } else {
                alert('Erro: ' + res.data);
                btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Atualizar');
            }
        });
    });

    // Abrir modal de cancelamento
    $(document).on('click', '.bbl-btn-cancel', function() {
        $('#bbl-cancel-id').val($(this).data('id'));
        $('#bbl-cancel-nonce').val($(this).data('nonce'));
        $('#bbl-modal-cancel').show();
    });

    // Confirmar cancelamento
    $('#bbl-cancel-confirm').on('click', function() {
        const id     = $('#bbl-cancel-id').val();
        const nonce  = $('#bbl-cancel-nonce').val();
        const motivo = $('#bbl-cancel-motivo').val();
        $(this).prop('disabled', true).text('Cancelando...');
        $.post(ajaxUrl, { action: 'bbl_nfse_cancelar', id, nonce, motivo }, function(res) {
            alert(res.success ? res.data : 'Erro: ' + res.data);
            if (res.success) location.reload();
            else $('#bbl-cancel-confirm').prop('disabled', false).text('Confirmar Cancelamento');
        });
    });

    // Reemitir
    $(document).on('click', '.bbl-btn-reemit', function() {
        if (!confirm('Deseja reemitir esta nota?')) return;
        const btn = $(this), id = btn.data('id'), nonce = btn.data('nonce');
        btn.prop('disabled', true).text('Reenviando...');
        $.post(ajaxUrl, { action: 'bbl_nfse_reemitir', id, nonce }, function(res) {
            alert(res.success ? res.data : 'Erro: ' + res.data);
            if (res.success) location.reload();
            else btn.prop('disabled', false).html('<span class="dashicons dashicons-controls-repeat"></span> Reemitir');
        });
    });

})(jQuery);
</script>
