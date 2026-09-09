# 🧾 Bebelume NFS-e

> Plugin WordPress para emissão automática de NFS-e via API TransmiteNota,
> integrado ao Paid Memberships Pro (PMPro).

![Version](https://img.shields.io/badge/version-1.1.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0+-green)
![PHP](https://img.shields.io/badge/PHP-8.0+-purple)

---

## ✨ O que faz

- **Emissão automática** de NFS-e a cada pagamento real aprovado no PMPro
- **Respeita o período trial** — só emite quando `total > 0` e `status = success`
- **Cron job** para atualizar status de notas pendentes a cada hora
- **Painel admin** para listar, filtrar, cancelar, reemitir e baixar PDF/XML
- **Configuração simples** via tela de settings no WordPress

---

## 📦 Estrutura

```
bebelume-nfse/
├── bebelume-nfse.php                  # Plugin principal + cron
├── includes/
│   ├── class-api-client.php           # Wrapper completo da API TransmiteNota
│   ├── class-nfse-db.php              # CRUD da tabela wp_bebelume_nfse
│   ├── class-pmpro-integration.php    # Hook PMPro → emissão automática
│   └── class-admin.php               # Menu, settings, AJAX handlers
├── templates/
│   ├── admin-notas.php               # Lista de NFS-e com ações
│   └── admin-settings.php            # Página de configuração
└── assets/
    └── css/admin.css
```

---

## 🚀 Instalação

1. Faça upload da pasta `bebelume-nfse` para `/wp-content/plugins/`
2. Ative o plugin em **Plugins → Ativar**
3. Vá em **NFS-e → Configurações** e preencha:
   - API Key da TransmiteNota
   - CNPJ do prestador (Bebelume)
   - Dados fiscais (natureza, código de serviço, alíquota ISS)

---

## ⚙️ Configuração

### Credenciais
| Campo | Onde encontrar |
|-------|---------------|
| **API Key** | Painel TransmiteNota → API |
| **CNPJ** | CNPJ da Bebelume cadastrado na TransmiteNota |

### Dados fiscais
Consulte seu contador para preencher corretamente:
- **Natureza da Operação** — geralmente `1` (Tributação no Município)
- **Código Tipo de Serviço** — cadastrado na sua prefeitura
- **Código do Serviço** — item da nota
- **Alíquota ISS (%)** — varia por município
- **ISS Retido** — normalmente `Não` para serviços digitais

---

## 👤 Campos de perfil necessários

O plugin lê estes `user_meta` de cada usuário para emitir a nota:

| Chave | Campo | Origem | Obrigatório |
|-------|-------|--------|-------------|
| `cpf` | CPF (só dígitos) | bebelume-profile | ✅ |
| `cpf_formatted` | CPF com máscara (fallback) | bebelume-profile | — |
| `pmpro_bphone` | Telefone com DDD | PMPro (checkout) | ✅ |
| `pmpro_baddress1` | Logradouro | PMPro (checkout) | ✅ |
| `pmpro_baddress2` | Bairro (convenção Bebelume) | PMPro (checkout) | — |
| `pmpro_bcity` | Cidade | PMPro (checkout) | ✅ |
| `pmpro_bstate` | Estado (2 letras) | PMPro (checkout) | ✅ |
| `pmpro_bzipcode` | CEP (8 dígitos) | PMPro (checkout) | ✅ |

> O CPF é coletado por `class-pmpro-fields.php` do plugin **bebelume-profile**.
> Os campos de endereço e telefone já são coletados **nativamente** pelo PMPro
> no checkout (campos de billing) — nenhum campo extra precisa ser criado.
>
> Se `pmpro_baddress2` estiver vazio, a nota é emitida com o bairro `Centro`
> como fallback e um aviso é gravado no debug.log.

---

## 🔄 Fluxo de emissão

```
Checkout PMPro
     │
     ▼
pmpro_updated_order
     │
     ├─ total = 0?  ──── SIM ──► Ignora (período trial/gratuito)
     │
     └─ total > 0 + status = success
          │
          ▼
     Busca user_meta do comprador
          │
          ▼
     Monta JSON → EnviarNfse (TransmiteNota)
          │
          ├─ Retorna searchkey → salva no banco (status: pendente)
          │
          └─ Erro → salva no banco (status: erro)

Cron (a cada hora)
     │
     ▼
ConsultarEmissaoNotaNfse para cada nota pendente/processando
     │
     ├─ Aprovada → salva link PDF/XML
     ├─ Reprovada → marca para reemissão manual
     └─ Cancelada → atualiza status
```

---

## 🖥️ Painel Admin

**NFS-e → Notas Emitidas**

Filtros por status: Todas / Pendentes / Processando / Aprovadas / Reprovadas / Canceladas / Com Erro

Ações disponíveis por nota:
| Ação | Quando disponível |
|------|------------------|
| 📄 PDF | Nota aprovada |
| 📥 XML | Nota aprovada |
| 🔄 Atualizar | Pendente / Processando |
| ❌ Cancelar | Pendente / Processando / Aprovada |
| 🔁 Reemitir | Erro / Reprovada |
| 👁️ Ver resposta API | Sempre |

---

## 🏗️ Banco de dados

Tabela criada na ativação: `wp_bebelume_nfse`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | BIGINT PK | ID interno |
| `order_id` | BIGINT | ID do pedido PMPro |
| `user_id` | BIGINT | ID do usuário WP |
| `searchkey` | VARCHAR | Chave da TransmiteNota |
| `status` | VARCHAR | pendente / processando / aprovada / reprovada / cancelada / erro |
| `numero_nfse` | VARCHAR | Número da nota (após aprovação) |
| `link_pdf` | TEXT | URL do PDF |
| `link_xml` | TEXT | URL do XML |
| `valor` | DECIMAL | Valor da nota |
| `payload` | LONGTEXT | JSON enviado (para reemissão) |
| `resposta` | LONGTEXT | JSON retornado pela API |
| `created_at` | DATETIME | Data de criação |
| `updated_at` | DATETIME | Última atualização |

---

## 🔌 Hooks disponíveis

```php
// Disparado quando o status de uma nota é atualizado pelo cron
add_action( 'bbl_nfse_status_updated', function( $nota_id, $status, $resultado ) {
    // $nota_id  — ID interno da nota
    // $status   — novo status (aprovada, reprovada, etc)
    // $resultado — array com dados da resposta da API
}, 10, 3 );
```

---

## 🔒 Segurança

- Verificação de nonce em todas as requisições AJAX
- `current_user_can('manage_options')` em todas as ações admin
- Sanitização de todos os inputs
- Escape de todos os outputs

---

## 📝 Changelog

### 1.0.0 (2026-03)
- 🎉 Lançamento inicial
- ✨ Emissão automática integrada ao PMPro
- 🔄 Cron de atualização de status
- 🖥️ Painel admin com listagem, filtros e ações
- ⚙️ Tela de configuração completa

---

## 🧪 Teste de emissão

**NFS-e → Teste de Emissão** permite emitir uma nota avulsa sem precisar forjar
um pedido no PMPro. Informe o valor, escolha o tomador e envie.

- **Tomador por usuário** — lê CPF e endereço do perfil exatamente como a emissão
  automática faria. O botão *Carregar dados* mostra o que foi lido e avisa se
  algum campo obrigatório está faltando (útil para diagnosticar por que a
  emissão de um cliente específico falhou).
- **Tomador manual** — preencha os campos à mão para testar sem depender de
  nenhum cadastro.
- O payload enviado e a resposta crua da API aparecem na tela.
- A nota é gravada na tabela com `order_id = 0` e aparece na listagem com a
  etiqueta **Teste**, mantendo os botões Atualizar / Cancelar / Reemitir.

> Em **Produção** a tela exige uma confirmação extra, porque a nota emitida ali
> é real e tem validade fiscal. Para testar, use **Homologação**.

---

## 📝 Changelog

### 1.1.0
- **Novo:** tela **Teste de Emissão** (valor + tomador → enviar), com
  pré-visualização dos dados do perfil, exibição do payload e da resposta da API,
  e trava de confirmação no ambiente de produção.
- Notas emitidas pela tela de teste aparecem na listagem com a etiqueta *Teste*.
- Internamente, a montagem do payload foi extraída para
  `montar_payload()` / `tomador_from_user()` / `validar_tomador()`, reaproveitada
  pela emissão automática e pela tela de teste. O payload gerado pelo fluxo
  automático permanece byte a byte idêntico ao da 1.0.1.

### 1.0.1
- **Corrigido:** `Requires PHP` estava declarado como 7.4, mas o código usa union
  types (PHP 8.0+) — em 7.4 o plugin nem carregava.
- **Corrigido:** `data_emissao` e `data_competencia` usavam `date()` (fuso do
  servidor, normalmente UTC). Notas emitidas após as 21h saíam com a data do dia
  seguinte. Agora usam `current_time()`.
- **Corrigido:** nomes com acento saíam truncados na nota (`JOSé` em vez de
  `JOSÉ`) — `strtoupper()` trocado por `mb_strtoupper()`.
- **Corrigido:** a reemissão manual reenviava o payload com a data original da
  falha. Agora as datas são atualizadas para o dia do reenvio.
- **Adicionado:** opção **HTTPS** nas configurações (desligada por padrão — teste
  em homologação antes de ligar em produção).
- **Adicionado:** trava de 2 min por pedido contra emissão duplicada quando
  checkout e webhook do Stripe disparam juntos.
- **Melhorado:** o cron só consulta notas pendentes dos últimos 7 dias
  (filtro `bbl_nfse_dias_consulta_pendente`). O botão "Atualizar" no admin
  continua funcionando para qualquer nota.
- **Melhorado:** a ApiKey passou a ser lida direto do ambiente ativo, sem
  depender da ordem dos sanitize callbacks.
- **Melhorado:** logs da emissão agora respeitam a opção "Modo Debug", e o corpo
  bruto da resposta é logado antes do parse (facilita diagnosticar
  "Resposta inválida da API").
- **Adicionado:** `register_deactivation_hook` para limpar o cron órfão.
