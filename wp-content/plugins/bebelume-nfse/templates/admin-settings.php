<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap bbl-nfse-wrap">

    <h1 class="bbl-nfse-title">
        <span class="dashicons dashicons-admin-settings"></span>
        Configurações NFS-e
    </h1>

    <?php settings_errors( 'bbl_nfse_group' ); ?>

    <?php
    $ambiente = get_option( 'bbl_nfse_ambiente', 'homologacao' );
    $is_homologacao = $ambiente === 'homologacao';

    // URL real usada pelo cliente da API (não hardcodar aqui de novo)
    $url_ativa = ( new Bebelume_NFSe_API_Client() )->get_base_url();
    ?>

    <!-- Banner de ambiente -->
    <div class="bbl-ambiente-banner <?php echo $is_homologacao ? 'bbl-homologacao' : 'bbl-producao'; ?>">
        <?php if ( $is_homologacao ) : ?>
            ⚠️ <strong>Ambiente de Homologação</strong> — As notas emitidas são de teste e não têm validade fiscal.
        <?php else : ?>
            🟢 <strong>Ambiente de Produção</strong> — As notas emitidas são reais e têm validade fiscal.
        <?php endif; ?>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields( 'bbl_nfse_group' ); ?>

        <!-- Ambiente -->
        <div class="bbl-settings-card">
            <h2>🌐 Ambiente</h2>
            <table class="form-table">
                <tr>
                    <th><label for="bbl_nfse_ambiente">Ambiente ativo</label></th>
                    <td>
                        <select id="bbl_nfse_ambiente" name="bbl_nfse_ambiente" onchange="this.form.submit()">
                            <option value="homologacao" <?php selected( $ambiente, 'homologacao' ); ?>>
                                🧪 Homologação (testes)
                            </option>
                            <option value="producao" <?php selected( $ambiente, 'producao' ); ?>>
                                🟢 Produção (notas reais)
                            </option>
                        </select>
                        <p class="description">
                            URL ativa: <code><?php echo esc_html( $url_ativa ); ?></code>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label for="bbl_nfse_https">Conexão segura (HTTPS)</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="bbl_nfse_https" name="bbl_nfse_https"
                                   value="1" <?php checked( $https, '1' ); ?> />
                            Enviar as requisições via HTTPS
                        </label>
                        <p class="description">
                            <strong>Desligado, a ApiKey e os dados do cliente (CPF, endereço, telefone)
                            trafegam sem criptografia.</strong> Recomendado ligar — mas
                            <em>teste primeiro em Homologação</em>: emita uma nota de teste e confirme
                            que ela é aceita. Se a TransmiteNota não responder em HTTPS,
                            desmarque e reporte para o suporte deles.
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Credenciais -->
        <div class="bbl-settings-card">
            <h2>🔑 Credenciais TransmiteNota</h2>
            <table class="form-table">

                <!-- Homologação -->
                <tr>
                    <th><label for="bbl_nfse_api_key_homologacao">API Key — Homologação</label></th>
                    <td>
                        <input type="text" id="bbl_nfse_api_key_homologacao"
                               name="bbl_nfse_settings[api_key_homologacao]"
                               value="<?php echo esc_attr( $settings['api_key_homologacao'] ?? '' ); ?>"
                               class="regular-text" placeholder="ApiKey de homologação" />
                        <p class="description">Usada quando o ambiente é <strong>Homologação</strong>.</p>
                    </td>
                </tr>

                <!-- Produção -->
                <tr>
                    <th><label for="bbl_nfse_api_key_producao">API Key — Produção</label></th>
                    <td>
                        <input type="text" id="bbl_nfse_api_key_producao"
                               name="bbl_nfse_settings[api_key_producao]"
                               value="<?php echo esc_attr( $settings['api_key_producao'] ?? '' ); ?>"
                               class="regular-text" placeholder="ApiKey de produção" />
                        <p class="description">Usada quando o ambiente é <strong>Produção</strong>.</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="bbl_nfse_cnpj">CNPJ do Prestador</label></th>
                    <td>
                        <input type="text" id="bbl_nfse_cnpj" name="bbl_nfse_cnpj"
                               value="<?php echo esc_attr( $cnpj ); ?>"
                               class="regular-text" placeholder="00.000.000/0000-00" />
                        <p class="description">CNPJ da Bebelume cadastrado na TransmiteNota.</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Dados Fiscais -->
        <div class="bbl-settings-card">
            <h2>🏛️ Dados Fiscais da Nota</h2>
            <table class="form-table">
                <tr>
                    <th><label for="bbl_natureza">Natureza da Operação</label></th>
                    <td>
                        <select id="bbl_natureza" name="bbl_nfse_settings[natureza_operacao]">
                            <?php
                            $naturezas = [
                                '0' => '0 – Não Informado',
                                '1' => '1 – Tributação no Município',
                                '2' => '2 – Tributação fora do Município',
                                '3' => '3 – Isenção',
                                '4' => '4 – Imune',
                                '5' => '5 – Exigibilidade Suspensa por Decisão Judicial',
                                '6' => '6 – Exigibilidade Suspensa por Procedimento Administrativo',
                            ];
                            $sel = $settings['natureza_operacao'] ?? '1';
                            foreach ( $naturezas as $v => $l ) :
                            ?>
                                <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $sel, $v ); ?>>
                                    <?php echo esc_html( $l ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="bbl_tipo_servico">Código Tipo de Serviço</label></th>
                    <td>
                        <input type="text" id="bbl_tipo_servico" name="bbl_nfse_settings[tipo_servico]"
                               value="<?php echo esc_attr( $settings['tipo_servico'] ?? '' ); ?>"
                               class="regular-text" placeholder="Ex: 01.09" />
                    </td>
                </tr>
                <tr>
                    <th><label for="bbl_codigo_servico">Código do Serviço (Item)</label></th>
                    <td>
                        <input type="text" id="bbl_codigo_servico" name="bbl_nfse_settings[codigo_servico]"
                               value="<?php echo esc_attr( $settings['codigo_servico'] ?? '' ); ?>"
                               class="regular-text" placeholder="Ex: 01090" />
                    </td>
                </tr>
                <tr>
                    <th><label for="bbl_desc_servico">Descrição do Serviço</label></th>
                    <td>
                        <input type="text" id="bbl_desc_servico" name="bbl_nfse_settings[descricao_servico]"
                               value="<?php echo esc_attr( $settings['descricao_servico'] ?? 'Assinatura de serviço digital' ); ?>"
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th><label for="bbl_aliquota">Alíquota ISS (%)</label></th>
                    <td>
                        <input type="number" step="0.01" min="0" max="100"
                               id="bbl_aliquota" name="bbl_nfse_settings[valor_aliquota]"
                               value="<?php echo esc_attr( $settings['valor_aliquota'] ?? '2' ); ?>"
                               class="small-text" /> %
                    </td>
                </tr>
                <tr>
                    <th><label for="bbl_iss_retido">ISS Retido?</label></th>
                    <td>
                        <select id="bbl_iss_retido" name="bbl_nfse_settings[iss_retido]">
                            <option value="2" <?php selected( $settings['iss_retido'] ?? '2', '2' ); ?>>Não</option>
                            <option value="1" <?php selected( $settings['iss_retido'] ?? '2', '1' ); ?>>Sim</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Avançado -->
        <div class="bbl-settings-card">
            <h2>⚙️ Avançado</h2>
            <table class="form-table">
                <tr>
                    <th><label for="bbl_nfse_debug">Modo Debug</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="bbl_nfse_debug" name="bbl_nfse_debug" value="1"
                                   <?php checked( $debug, '1' ); ?> />
                            Registrar chamadas à API no error_log do WordPress
                        </label>
                        <p class="description">Ative apenas em ambiente de testes.</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Campos utilizados -->
        <div class="bbl-settings-card bbl-info-card">
            <h2>ℹ️ Campos de perfil utilizados</h2>
            <p>O plugin lê os seguintes <code>user_meta</code> de cada usuário para emitir a nota:</p>
            <table class="bbl-meta-table">
                <thead><tr><th>user_meta key</th><th>Descrição</th><th>Origem</th><th>Obrigatório</th></tr></thead>
                <tbody>
                    <tr><td><code>cpf</code></td><td>CPF (só dígitos)</td><td>bebelume-profile checkout</td><td>✅</td></tr>
                    <tr><td><code>cpf_formatted</code></td><td>CPF formatado (fallback)</td><td>bebelume-profile checkout</td><td>—</td></tr>
                    <tr><td><code>pmpro_bphone</code></td><td>Telefone com DDD</td><td>PMPro checkout (Phone)</td><td>✅</td></tr>
                    <tr><td><code>pmpro_baddress1</code></td><td>Logradouro</td><td>PMPro checkout (Address 1)</td><td>✅</td></tr>
                    <tr><td><code>pmpro_baddress2</code></td><td>Bairro</td><td>PMPro checkout (Address 2)</td><td>—</td></tr>
                    <tr><td><code>pmpro_bcity</code></td><td>Cidade</td><td>PMPro checkout (City)</td><td>✅</td></tr>
                    <tr><td><code>pmpro_bstate</code></td><td>Estado (2 letras)</td><td>PMPro checkout (State)</td><td>✅</td></tr>
                    <tr><td><code>pmpro_bzipcode</code></td><td>CEP (8 dígitos)</td><td>PMPro checkout (Zip)</td><td>✅</td></tr>
                </tbody>
            </table>
        </div>

        <?php submit_button( 'Salvar Configurações' ); ?>
    </form>

</div>

<style>
.bbl-ambiente-banner {
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}
.bbl-homologacao {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    color: #92400e;
}
.bbl-producao {
    background: #d1fae5;
    border-left: 4px solid #22c55e;
    color: #065f46;
}
</style>
