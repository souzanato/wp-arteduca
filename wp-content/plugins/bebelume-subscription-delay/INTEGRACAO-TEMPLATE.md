# 🎨 INTEGRAÇÃO COM O TEMPLATE LEVELS.PHP

## 📝 Como usar o plugin no seu código

Depois de instalar e configurar o plugin, o objeto `$level` terá a propriedade `subscription_delay` disponível.

---

## ✅ CÓDIGO RECOMENDADO

Adicione este código no seu `templates/levels.php`:

```php
<?php
foreach ($pmpro_levels as $level) {
    // ... seu código anterior ...
    
    // ✅ NOVO: Verifica se tem trial de 30 dias
    $pagamento_inicial_zero = (floatval($level->initial_payment) == 0);
    $eh_mensal = (strtolower($level->cycle_period) == 'month');
    $tem_delay_30_dias = (isset($level->subscription_delay) && intval($level->subscription_delay) == 30);
    
    // Define o texto do botão
    if ($pagamento_inicial_zero && $eh_mensal) {
        // Mensal com initial = 0 → automaticamente 30 dias grátis
        $texto_botao_selecionar = 'Experimente 30 dias grátis';
    } elseif ($tem_delay_30_dias) {
        // Tem delay de 30 dias configurado → 30 dias grátis
        $texto_botao_selecionar = 'Experimente 30 dias grátis';
    } else {
        $texto_botao_selecionar = 'Selecionar';
    }
?>

    <!-- Seu HTML aqui -->
    <div class="nivel-acao">
        <a href="<?php echo esc_url(pmpro_url("checkout", "?pmpro_level=" . $level->id, "https")) ?>">
            <?php echo esc_html($texto_botao_selecionar); ?>
        </a>
    </div>

<?php } ?>
```

---

## 📊 RESULTADO ESPERADO

| Nível | Configuração | Texto do Botão |
|-------|-------------|----------------|
| **Mensal** | Initial = 0, Billing = R$ 9,90/mês, Delay = (vazio) | "Experimente 30 dias grátis" |
| **Anual** | Initial = 0, Billing = R$ 89,90/ano, Delay = 30 | "Experimente 30 dias grátis" |

---

## 🔍 VERIFICAÇÃO (DEBUG)

Para testar se o delay está funcionando:

```php
<?php
// DEBUG - REMOVER DEPOIS
echo '<pre>';
echo 'Level ID: ' . $level->id . "\n";
echo 'Initial Payment: ' . $level->initial_payment . "\n";
echo 'Billing Amount: ' . $level->billing_amount . "\n";
echo 'Cycle Period: ' . $level->cycle_period . "\n";
echo 'Subscription Delay: ' . (isset($level->subscription_delay) ? $level->subscription_delay : 'não definido') . "\n";
echo '</pre>';
?>
```

---

## 🎯 EXEMPLO COMPLETO

```php
<?php
$contador = 0;
foreach ($pmpro_levels as $level) {
    $user_level = pmpro_getSpecificMembershipLevelForUser($current_user->ID, $level->id);
    $has_level = !empty($user_level);
    
    // Classes CSS
    $classe_cor = ($contador % 2 == 0) ? 'pmpro-pink-bkg' : 'pmpro-yellow-bkg';
    $contador++;
    
    // Tipo do plano
    $eh_mensal = (strtolower($level->cycle_period) == 'month');
    $eh_anual = (strtolower($level->cycle_period) == 'year');
    
    if ($eh_mensal) {
        $tipo_plano = 'MENSAL';
        $periodo_texto = 'MÊS';
    } elseif ($eh_anual) {
        $tipo_plano = 'ANUAL';
        $periodo_texto = 'ANO';
    }
    
    // Preço
    $preco_formatado = number_format($level->billing_amount, 2, ',', '.');
    
    // ✅ VERIFICA TRIAL
    $pagamento_inicial_zero = (floatval($level->initial_payment) == 0);
    $tem_delay_30_dias = (isset($level->subscription_delay) && intval($level->subscription_delay) == 30);
    
    if ($pagamento_inicial_zero && $eh_mensal) {
        $texto_botao_selecionar = 'Experimente 30 dias grátis';
    } elseif ($tem_delay_30_dias) {
        $texto_botao_selecionar = 'Experimente 30 dias grátis';
    } else {
        $texto_botao_selecionar = 'Selecionar';
    }
?>

    <div class="col-md-4 col-sm-12 <?php echo esc_attr($classe_cor); ?>">
        <div class="nivel-item">
            
            <!-- Tipo -->
            <p class="nivel-tipo"><?php echo esc_html($tipo_plano); ?></p>
            
            <!-- Preço -->
            <div class="nivel-preco-novo">
                <p class="preco-valor">R$ <?php echo esc_html($preco_formatado); ?></p>
                <p class="preco-periodo">/POR <?php echo esc_html($periodo_texto); ?></p>
            </div>
            
            <!-- Descrição -->
            <?php if (!empty($level->description)) { ?>
                <div class="nivel-descricao">
                    <?php echo wp_kses_post(wpautop($level->description)); ?>
                </div>
            <?php } ?>
            
            <!-- Botão -->
            <div class="nivel-acao">
                <?php if (!$has_level) { ?>
                    <a href="<?php echo esc_url(pmpro_url("checkout", "?pmpro_level=" . $level->id, "https")) ?>">
                        <?php echo esc_html($texto_botao_selecionar); ?>
                    </a>
                <?php } ?>
            </div>
            
        </div>
    </div>

<?php } ?>
```

---

## ✨ PRONTO!

Agora seu template exibirá "Experimente 30 dias grátis" nos níveis corretos automaticamente! 🎉
