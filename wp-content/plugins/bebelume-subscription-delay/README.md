# Bebelume - Subscription Delay

**Versão:** 1.0.0  
**Compatível com:** Paid Memberships Pro v3.6+  
**Licença:** GPL v2 ou posterior

---

## 📝 Descrição

Plugin WordPress que adiciona funcionalidade de **delay customizado** para assinaturas do Paid Memberships Pro.

Permite oferecer **30 dias grátis em planos anuais** (ou qualquer outro período) sem precisar do add-on pago "Subscription Delays".

---

## ✨ Características

- ✅ Adiciona campo "Delay da Assinatura" em cada nível do PMPro
- ✅ Usuário ganha acesso imediato ao conteúdo
- ✅ Primeira cobrança acontece após X dias
- ✅ Interface integrada ao painel do PMPro
- ✅ Leve e sem dependências extras
- ✅ 100% gratuito e open source

---

## 🚀 Instalação

### Método 1: Upload via WordPress Admin

1. Faça upload do arquivo `bebelume-subscription-delay.zip`
2. Vá em **Plugins → Adicionar novo → Fazer upload**
3. Selecione o arquivo .zip
4. Clique em **Instalar agora**
5. Clique em **Ativar**

### Método 2: Upload via FTP

1. Extraia o arquivo .zip
2. Faça upload da pasta `bebelume-subscription-delay` para `/wp-content/plugins/`
3. Vá em **Plugins** no WordPress Admin
4. Ative **Bebelume - Subscription Delay**

---

## ⚙️ Configuração

### 1. Configurar o Delay

1. Vá em **Memberships → Settings → Levels**
2. Edite o nível desejado (ex: Plano Anual)
3. Role até o final da página
4. Encontre **"Configurações de Trial (Bebelume)"**
5. Digite o número de dias no campo **"Delay da Assinatura (dias)"**
   - Exemplo: `30` para 30 dias grátis
6. Salve o nível

### 2. Configurar o Nível

Para oferecer 30 dias grátis em um plano anual:

```
Initial Payment: 0
Recurring Subscription: ✓
Billing Amount: R$ 89,90 per 1 Year
Delay da Assinatura: 30
```

**Resultado:**
- Usuário se cadastra → R$ 0,00 (não paga)
- Acesso imediato ao conteúdo
- Após 30 dias → Primeira cobrança de R$ 89,90
- Depois disso → Cobra R$ 89,90 a cada 1 ano

---

## 💡 Casos de Uso

### Plano Mensal com 7 dias grátis
```
Initial Payment: 0
Billing Amount: R$ 9,90 per 1 Month
Delay da Assinatura: 7
```

### Plano Anual com 1 mês grátis
```
Initial Payment: 0
Billing Amount: R$ 99,90 per 1 Year
Delay da Assinatura: 30
```

### Plano sem trial (comportamento padrão)
```
Initial Payment: R$ 9,90
Billing Amount: R$ 9,90 per 1 Month
Delay da Assinatura: (deixe vazio ou 0)
```

---

## 🔧 Integração com o Template

No seu arquivo `templates/levels.php`, você pode verificar se há delay:

```php
// Verifica se tem trial de 30 dias
$tem_delay_30_dias = (isset($level->subscription_delay) && intval($level->subscription_delay) == 30);

if ($tem_delay_30_dias) {
    echo 'Experimente 30 dias grátis';
} else {
    echo 'Selecionar';
}
```

---

## 🛠️ Requisitos

- WordPress 5.0 ou superior
- PHP 7.0 ou superior
- Paid Memberships Pro (plugin gratuito)

---

## 📋 Como Funciona Tecnicamente

O plugin:

1. Adiciona um campo customizado na página de edição de níveis
2. Salva o valor no banco de dados do WordPress (wp_options)
3. Injeta a propriedade `subscription_delay` no objeto `$level`
4. Intercepta o hook `pmpro_checkout_start_date`
5. Modifica a data de início da assinatura adicionando X dias

---

## 🐛 Solução de Problemas

### O campo não aparece na página de edição

**Solução:** Certifique-se de que o Paid Memberships Pro está instalado e ativo.

### A cobrança não está sendo adiada

**Solução:** 
1. Verifique se o valor do delay foi salvo (edite o nível novamente)
2. Certifique-se de que `Initial Payment = 0`
3. Limpe o cache do site

### Como verificar se está funcionando?

Crie um teste de checkout e verifique no painel do PMPro em **Memberships → Orders** a data da próxima cobrança.

---

## 🆘 Suporte

Para questões técnicas ou bugs, entre em contato com a equipe Bebelume.

---

## 📄 Licença

Este plugin é licenciado sob GPL v2 ou posterior.

---

## 👨‍💻 Desenvolvido por

**Bebelume**  
Com ❤️ para a comunidade WordPress

---

**Versão:** 1.0.0  
**Última atualização:** Dezembro 2024
