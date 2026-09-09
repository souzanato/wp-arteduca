<?php
/**
 * bbl_processing_overlay() — Overlay de processamento em tela cheia
 *
 * Overlay mobile-first reutilizado no checkout (bloqueio contra duplo clique
 * durante a criação assíncrona do PaymentMethod na Stripe) e no cancelamento
 * (submit síncrono, evita duplo clique e passa feedback imediato ao usuário).
 *
 * Parâmetros (todos opcionais, com defaults):
 *  - overlay_id       : id raiz do overlay (e base para o botão de retry)
 *  - form_selector    : seletor CSS do <form>
 *  - button_selector  : seletor CSS do botão de submit a desabilitar
 *  - title / sub      : textos do card (aceitam HTML simples)
 *  - secure           : exibe a nota "Pagamento 100% seguro pela Stripe"
 *  - auto_release     : libera a tela quando o script reabilita o botão (erro async)
 *  - retry_ms         : após N ms mostra o link de emergência "Recarregar a página"
 *  - logo / logo_alt  : imagem do card
 *
 * @param array $args Configuração do overlay.
 */
function bbl_processing_overlay( array $args = [] ): void {
    $args = wp_parse_args( $args, [
        'overlay_id'      => 'bbl-processing',
        'form_selector'   => '.pmpro_form, #pmpro_form',
        'button_selector' => '#pmpro_btn-submit',
        'title'           => 'Seu pedido está sendo processado',
        'sub'             => 'Não feche nem atualize esta página.<br>Isso pode levar alguns instantes.',
        'secure'          => true,
        'auto_release'    => true,
        'retry_ms'        => 25000,
        'logo'            => home_url( '/wp-content/uploads/2026/07/logo@3x-2048x351-3.png' ),
        'logo_alt'        => get_bloginfo( 'name' ),
    ] );

    $overlay_id = sanitize_html_class( $args['overlay_id'] );
    $retry_id   = $overlay_id . '-retry';
    $title      = $args['title'];
    $sub        = $args['sub'];
    $secure     = (bool) $args['secure'];
    $logo       = esc_url( $args['logo'] );
    $logo_alt   = esc_attr( $args['logo_alt'] );

    $cfg_json = wp_json_encode( [
        'overlayId'   => $overlay_id,
        'retryId'     => $retry_id,
        'formSel'     => $args['form_selector'],
        'btnSel'      => $args['button_selector'],
        'autoRelease' => (bool) $args['auto_release'],
        'retryMs'     => (int) $args['retry_ms'],
    ] );
    ?>
    <style>
    .bbl-processing-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
        background: rgba(255, 255, 255, .9);
        -webkit-backdrop-filter: blur(6px);
        backdrop-filter: blur(6px);
        font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .bbl-processing-overlay.bbl-processing-hidden { display: none !important; }
    .bbl-processing-card {
        width: 100%;
        max-width: 400px;
        box-sizing: border-box;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 18px 60px rgba(30, 15, 25, .16);
        padding: 34px 24px 26px;
        text-align: center;
    }
    .bbl-processing-logo {
        display: block;
        max-width: 210px;
        width: 72%;
        height: auto;
        margin: 0 auto 22px;
    }
    .bbl-processing-spinner {
        width: 46px;
        height: 46px;
        border: 4px solid rgba(235, 42, 97, .18);
        border-top-color: #eb2a61;
        border-radius: 50%;
        margin: 0 auto 18px;
        animation: bbl-proc-spin .8s linear infinite;
    }
    @keyframes bbl-proc-spin { to { transform: rotate(360deg); } }
    .bbl-processing-title {
        margin: 0 0 8px;
        font-size: 19px;
        line-height: 1.3;
        font-weight: 800;
        color: #1a1a1a;
    }
    .bbl-processing-sub {
        margin: 0 0 18px;
        font-size: 14px;
        line-height: 1.5;
        color: #666;
    }
    .bbl-processing-secure {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        background: #f4f6fb;
        border: 1px solid #e7ebf3;
        border-radius: 12px;
        padding: 12px 13px;
        text-align: left;
        font-size: 12.5px;
        line-height: 1.45;
        color: #4b5563;
    }
    .bbl-processing-secure svg { flex: none; margin-top: 1px; color: #16a34a; }
    .bbl-processing-retry {
        display: none;
        margin: 16px auto 0;
        background: none;
        border: 0;
        padding: 0;
        font-family: inherit;
        font-size: 13px;
        color: #eb2a61;
        cursor: pointer;
    }
    .bbl-processing-retry.bbl-visible { display: inline-block; }
    @media (max-width: 420px) {
        .bbl-processing-card { padding: 26px 18px 22px; border-radius: 16px; }
        .bbl-processing-title { font-size: 17px; }
    }
    </style>

    <div id="<?php echo $overlay_id; ?>"
         class="bbl-processing-overlay bbl-processing-hidden"
         role="status" aria-live="polite" aria-hidden="true">
        <div class="bbl-processing-card">
            <img class="bbl-processing-logo"
                 src="<?php echo $logo; ?>"
                 alt="<?php echo $logo_alt; ?>"
                 width="2048" height="351" />
            <div class="bbl-processing-spinner" aria-hidden="true"></div>
            <p class="bbl-processing-title"><?php echo $title; ?></p>
            <p class="bbl-processing-sub"><?php echo $sub; ?></p>
            <?php if ( $secure ) : ?>
            <div class="bbl-processing-secure">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>Pagamento 100% seguro e criptografado pela <strong>Stripe</strong>. Nós não armazenamos os dados do seu cartão.</span>
            </div>
            <?php endif; ?>
            <button type="button" id="<?php echo $retry_id; ?>" class="bbl-processing-retry">
                Continua processando há muito tempo? Recarregar a página
            </button>
        </div>
    </div>

    <script>
    (function () {
        var C = <?php echo $cfg_json; ?>;
        var overlay = document.getElementById(C.overlayId);
        var form    = C.formSel ? document.querySelector(C.formSel) : null;
        var btn     = C.btnSel ? document.querySelector(C.btnSel) : null;
        if (!overlay || !form) return;

        var processing = false;
        var retryTimer = null;
        var watcher    = null;

        function show() {
            if (processing) return;
            processing = true;
            if (btn) {
                btn.disabled = true;
                btn.setAttribute('aria-disabled', 'true');
            }
            overlay.classList.remove('bbl-processing-hidden');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            retryTimer = window.setTimeout(function () {
                var retry = document.getElementById(C.retryId);
                if (retry && processing) retry.classList.add('bbl-visible');
            }, C.retryMs);
        }

        function hide() {
            if (!processing) return;
            processing = false;
            if (btn) {
                btn.disabled = false;
                btn.removeAttribute('aria-disabled');
            }
            overlay.classList.add('bbl-processing-hidden');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            if (retryTimer) window.clearTimeout(retryTimer);
        }

        // Dispara APÓS a validação nativa do navegador (o submit só ocorre se válido).
        form.addEventListener('submit', function (e) {
            // Não bloqueia outros botões de submit que porventura existam no form.
            if (btn && e.submitter && e.submitter.type === 'submit' && e.submitter !== btn) {
                return;
            }
            show();
        });

        // Checkout: o PMPro/Stripe reabilita o botão somente quando há erro
        // (cartão recusado, auth falhou). Detecta e libera a tela.
        if (C.autoRelease) {
            watcher = window.setInterval(function () {
                if (processing && btn && !btn.disabled) hide();
            }, 150);
        }

        window.addEventListener('pagehide', function () {
            if (watcher) window.clearInterval(watcher);
            if (retryTimer) window.clearTimeout(retryTimer);
        });

        // Saída de emergência (caso raríssimo de travamento).
        var retryBtn = document.getElementById(C.retryId);
        if (retryBtn) {
            retryBtn.addEventListener('click', function () {
                window.location.reload();
            });
        }
    })();
    </script>
    <?php
}
