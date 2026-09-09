# Bebelume ArtEduca — Contexto técnico completo da solução

> **Raiz do projeto (local):** `/Users/renato/dev/arteduca-wp` — espelho do mesmo repositório
> que roda em produção no servidor em `/opt/wp-arteduca`. Este documento descreve a solução
> como um todo: negócio, código, banco de dados, integrações e processo operacional.
>
> Versão do relatório: **09/09/2026**. WordPress **7.1**, PHP **8.4.24**.
> Site: `https://arteduca.bebelume.com.br` · Nome do site: **Bebelume** · Fuso: `America/Sao_Paulo` (UTC−3).
> Repositório GitHub: `https://github.com/souzanato/wp-arteduca` (**público**, branch `main`).

---

## 1. O que o projeto faz

A **Bebelume** é um ecossistema de educação infantil. O site **ArtEduca** é uma plataforma
WordPress de **conteúdo educativo por assinatura** (planos **mensal** e **anual**) voltada a
professores/escolas de Educação Infantil, com conteúdo pedagógico alinhado à **BNCC**.

Em cima do WordPress + **Paid Memberships Pro (PMPro)**, a solução:

1. **Vende assinaturas recorrentes** cobradas em **Stripe** (cartão), com **trial de 30 dias**.
2. **Libera/revoga conteúdo** conforme o nível da assinatura do usuário.
3. **Bloqueia o site** para membros com dados fiscais incompletos quando há cobrança próxima
   (garante que toda cobrança real tenha **Nota Fiscal de Serviço Eletrônica — NFS-e**).
4. **Emite NFS-e automaticamente** para cada pagamento real, via provedor **TransmiteNota**,
   e entrega o PDF/XML por e-mail quando a nota é aprovada.
5. Cobre o **ciclo de vida do assinante**: cadastro (e-mail/senha **ou** Google), confirmação de
   e-mail, complemento de cadastro, checkout, cobrança recorrente, falha de pagamento,
   cancelamento "no fim do período" e re-assinatura.
6. Faz **rastreamento de marketing** (Meta Pixel + Conversions API), **webhooks n8n** de
   primeira compra e um **gerenciador de contas em lote** (criação de usuários em massa).

Existem **dois "produtos/silos"** que compartilham o mesmo WordPress (histórico):
**Canal Bebelume** e **Bebelume ArtEduca**. O produto ativo hoje é o ArtEduca, com 2 níveis de
assinatura; o Canal Bebelume segue suportado pela mesma base (blocos, satélite, contas) mas seus
níveis foram desativados/excluídos do nível ativo.

---

## 2. Stack e arquitetura

| Camada | Tecnologia |
|---|---|
| CMS | WordPress 7.1 (instalação raiz, single-site, prefixo `wp_`) |
| Assinaturas | Paid Memberships Pro (PMPro) **3.6.5** — gateway **Stripe** |
| Pagamentos | Stripe (ambiente **live**), cobrança via **Checkout Session** / subscriptions |
| Nota fiscal | API **TransmiteNota** (NFS-e) — emissão, consulta, cancelamento |
| Front-end | Tema clássico `bebelume` (PHP + Gutenberg **blocos dinâmicos** compilados com webpack) |
| Banco | MySQL/MariaDB do servidor (mesmo host usado pelos "satélites" `wp_canal` e `wp_arteduca`) |
| Caching | WP Super Cache (`advanced-cache.php` drop-in presente) |
| SEO | The SEO Framework (`autodescription`) |
| E-mail transacional | `wp_mail` via **SMTP Gmail** (mu-plugin `bebelume-smtp`) + templates HTML próprios |
| Servidor web | nginx (nega dotfiles — `.git` no docroot não é acessível via web) |

### Arquitetura de dados em destaque

- Usuários, pedidos e assinaturas vivem nas tabelas padrão PMPro (prefixed `wp_`).
- Duas tabelas **próprias** (criadas por plugins da casa): `wp_bebelume_nfse` (notas) e
  `wp_bebelume_users_draft` (rascunhos de criação em lote).
- Não há CPT de "conteúdo pedagógico": o conteúdo educacional são **posts/páginas padrão**
  marcados com **metadados pedagógicos** (`_bebelume_campos`, `_bebelume_direitos`,
  `_bebelume_body_classes`) e restritos por nível do PMPro.

### Relação de domínios/DBs ("satélites")

O mesmo código/modelo pode atuar como site-satélite. Há referência a conexões diretas via MySQL
às bases `wp_canal` e `wp_arteduca` e REST do PMPro entre instalações (ver §9 — `BBL_Satellite_Memberships`).

---

## 3. Estrutura completa do repositório (raiz)

```
/Users/renato/dev/arteduca-wp/
├── wp-admin/  wp-includes/          # núcleo WordPress (versionado)
├── wp-content/
│   ├── themes/bebelume/             # tema ativo (103 arquivos)
│   ├── plugins/                     # 21 plugins (código)
│   ├── mu-plugins/                  # 3 must-use
│   ├── cache/  upgrade/  languages/ # NÃO versionados (re-baixáveis/gerados)
│   └── uploads/                     # NÃO versionado (mídia de produção)
├── debug.php                        # script dev: debug completo da instalação
├── video-ids.php                    # script dev: excluir vídeos não usados em playlists
├── .gitignore                       # define o que fica fora do repo (segredos, mídia, logs)
└── contexto.md                      # este documento
```

Fora do versionamento (`gitignore`): `wp-config.php` (+ backups `wp-config.php.bak-*`), `uploads/`,
logs (`*.log`), `backups/` (dumps), `.claude/`, cache WP Super Cache, traduções não-`pt_BR` do
núcleo e do PMPro, `node_modules` do tema. **Regra de ouro: segredos nunca entram no repositório**
(constantes no `wp-config.php`, que não é versionado).

### Constantes/segredos definidos no `wp-config.php` (NÃO versionado)

- `BBL_FISCAL_BLOCK` (bool) — **feature flag** que liga a tela bloqueante de dados fiscais.
- `BBL_GOOGLE_CLIENT_SECRET` — client secret do OAuth "Entrar com Google".
- Constantes SMTP (`SMTP_HOST/PORT/USER/PASS/SECURE/FROM/NAME`) usadas pelo mu-plugin de e-mail.
- Application Passwords dos satélites (`BBL_CANAL_WP_USER/APP_PASS`, `BBL_ARTEDUCA_WP_USER/APP_PASS`)
  e outras credenciais de banco/salts.

---

## 4. Plugins — propósito de cada um

### 4.1 Plugins da casa (`wp-content/plugins/bebelume-*`)

#### `bebelume-pmpro-profile` (v1.0.0) — o "núcleo de produto"
Maior plugin próprio. Agrega **autenticação, perfil, checkout, cobrança e regras de negócio** do
ecossistema. O arquivo principal apenas requer módulos em `includes/` (cada um uma classe) e injeta
CSS do PMPro no front. Módulos e responsabilidades:

| Módulo | Papel |
|---|---|
| `class-auth.php` | Redesenho da tela de login WP (logo, estilos, JS), **botão Google**, tradução de erros/mensagens, redirects de login/logout/registro, regra de forçar `wp-login.php` e redirecionar slug de login personalizado, hosts de redirect permitidos. |
| `class-register.php` | Tela de registro custom (campos, validação com **reCAPTCHA v3** — site/secret key fixas no código, threshold 0.5), gravação de campos no `user_register`, injeção de username. |
| `class-google-auth.php` | **OAuth 2.0 "Entrar com Google"** (fluxo completo: state/nonce CSRF → troca `code` por token → `userinfo` → loga ou **cria usuário**). Client ID fixo; client secret vindo de `BBL_GOOGLE_CLIENT_SECRET`; usuário Google sempre marcado com e-mail confirmado; marca meta `bbl_google_id/avatar/auth_provider=google`. |
| `class-complete-profile.php` | Fluxo **"complete seu cadastro"**: página/tela de complemento (senha opcional etc.), meta `cpf`/`cpf_formatted` na fonte legada, logout de todos os dispositivos (AJAX). |
| `class-email-confirmation.php` | **Confirmação de e-mail no registro**: cria `bbl_email_confirmed=0` + token; bloqueia login de não-confirmados (`authenticate`); telas "verifique seu e-mail" com reenvio; link confirma e redireciona para completar cadastro. Metas: `bbl_email_confirmed`, `bbl_confirm_token`. |
| `class-pmpro-integration.php` | Pontes com o PMPro: página de **billing desativada** (redirect p/ conta), **sobrescreve shortcodes** `[pmpro_account|billing|cancel|confirmation|invoice|levels]` por templates do plugin (`templates/pmpro/*.php`), registra esses templates como path custom do PMPro, redireciona não-admins do `wp-admin`, esconde admin bar, **página de conteúdo restrito** custom e remove banner de login do PMPro. |
| `class-checkout-fields.php` | Campos no checkout: nome/sobrenome, **card "Dados fiscais"** (CPF + endereço) que aparece para **retornantes** antes do pagamento, cache de campos entre ida e volta da Stripe, sincronização dos campos fiscais para as metas PMPro (`pmpro_b*`, `cpf`), validação via `pmpro_registration_checks`, AJAX `bbl_check_retornante`. |
| `class-checkout.php` | Checkout PMPro com marca Bebelume: resumo do plano no topo, selos de confiança, bandeiras de cartão, **checkbox de Termos/Política (modais)**, botão de pagamento custom, traduções de textos (`pmpro_level_cost_text`, `gettext`), CEP via ViaCEP no checkout, bloco "logado como…". **Textos legais:** Termos (seção 4 detalha quando a tela bloqueante de dados fiscais aparece durante o trial) e Política (constantes `TERMOS_ATUALIZACAO=06/09/2026`, `PRIVACIDADE_ATUALIZACAO=04/09/2026`). |
| `class-checkout-processing.php` | **Overlay full-screen** durante o processamento do pagamento (anti duplo-clique), reusado no cancelamento. |
| `class-brute-force.php` | Proteção de login: 5 tentativas em janela de 15 min → trava 15 min (transientes). |
| `class-expired-link.php` | UI de "link expirado" em ações de e-mail (login). |
| `class-google-password.php` | Aviso/hint para usuários que criaram conta via Google definirem senha. |
| `class-emails.php` (`BBL_Emails`) | **E-mails transacionais WP** em HTML/Branding Bebelume: novo usuário (override `wp_new_user_notification_email`), redefinição de senha, senha alterada, e-mail alterado. Força `text/html`. |
| `class-pmpro-emails.php` (`BBL_PMPro_Emails`) | Reescreve os **templates de e-mail do PMPro em pt-BR** com o shell HTML Bebelume (filtros `pmpro_email_subject/body/data/header/footer`, prioridade 20 para rodar após o `pmpro_kses`). Cobre os templates padrão do PMPro (checkout, confirmação, falha, cancelamento etc.). |
| `class-satellite-memberships.php` | Lê **assinaturas/orders de outros sites Bebelume** (satélites) via REST do PMPro + acesso MySQL direto às bases `wp_canal`/`wp_arteduca`, usado pela área de conta para exibir o histórico agregado. |
| `class-n8n-webhook.php` | Campo por nível **"Webhook n8n"**; na **primeira** compra do usuário no nível dispara o payload para a URL (ex.: `https://automacao.bematendido.com.br/webhook/bebelume`), com página de teste no admin. |
| `class-fiscal-block.php` | **Tela bloqueante de dados fiscais** (feature flag `BBL_FISCAL_BLOCK`): regra de bloqueio, render da tela, autopreenchimento ViaCEP, máscaras, validação de CPF, link "Fazer isso depois" para a conta, reemissão de NFS-e pendente ao salvar. Detalhe em §6.8. |
| `class-cancel-at-period-end.php` | Intercepta cancelamento self-service e usa **`cancel_at_period_end`** no Stripe (mantém acesso até o fim do ciclo pago). Grava meta `bbl_cancel_at_period_end` na subscription. |
| `class-prevent-duplicate-subscription.php` | **Anti-duplicidade**: no máx. 1 subscription que vai cobrar por (usuário, plano). Camada A: em `pmpro_added_subscription`, cancela no gateway qualquer sub "extra" cobrável (mantém a de menor id). Camada B: em `pmpro_checkout_order_creation_checks`, aborta novo checkout se já existe sub cobrável. "Cobrável" = `active/trialing` sem `bbl_cancel_at_period_end=1`. |
| `class-billing-failure.php` | Reage a `pmpro_subscription_payment_failed`: guarda **motivo da recusa** por e-mail (consumido pelo template de e-mail) e alimenta avisos. |
| `class-cancel-notice.php` | Banner no topo da página de entrada do satélite quando a sub está agendada para encerrar no fim do período (meta `bbl_cancel_at_period_end`) — espelha a regra do template de conta. |

Assets do plugin: `assets/css/login.css`, `assets/css/pmpro.css`, `assets/js/login-validation.js`,
logo/imagens; `tools/bbl-monitor.php` e `tools/bbl-reconcile.php` (scripts de manutenção);
`templates/pmpro/{account,billing,cancel,confirmation,invoice,levels}.php` (**os templates vivos das
páginas PMPro** — sobrepostos por shortcode).

#### `bebelume-nfse` (v1.1.0) — NFS-e (TransmiteNota)
Integração PMPro ↔ API de NFS-e. Emite **somente pagamentos reais** (`status=success` e `total>0`).
- `includes/class-api-client.php` — wrapper dos endpoints TransmiteNota (ver §9).
- `includes/class-nfse-db.php` — CRUD da tabela `wp_bebelume_nfse`.
- `includes/class-pmpro-integration.php` — gatilhos de emissão e regras:
  - `pmpro_subscription_payment_completed` (cobrança recorrente via webhook Stripe),
  - `pmpro_updated_order` (pagamento direto sem trial),
  - `pmpro_after_checkout` (cobrança imediata no checkout — retornante) via `after_checkout_emit_nfse`,
  - trava anti-corrida por transiente `bbl_nfse_lock_{order_id}` (2 min),
  - **cron** `bebelume_nfse_check_pending`: consulta notas pendentes e atualiza status;
    quando uma nota passa a `aprovada`, envia e-mail via WordPress (PDF/XML anexos) se a opção
    `bbl_nfse_email_wp_aprovacao=1`, e dispara `bbl_nfse_status_updated`.
- `includes/class-email.php` (`Bebelume_NFSe_Email`) — monta e envia o e-mail da nota.
- `includes/class-admin.php` + `templates/admin-{notas,settings,teste}.php` — painel: listagem de
  notas com status, configurações (CNPJ, ambiente, natureza, serviço, alíquota, iss, chaves), tela de
  teste (emissão avulsa) e botões de atualizar/cancelar por nota.

#### `bebelume-users` (v1.1.1) — criação de contas em lote
Gerenciamento administrativo de usuários com senha gerada automaticamente e envio por e-mail via
SMTP Gmail. Fluxo: admin adiciona nome/e-mail (fila `wp_bebelume_users_draft`, status `rascunho`) →
processa → cria usuário WP (username `user-XXXXXXXX`), gera senha, atribui nível PMPro e envia o
e-mail de boas-vindas (template em opção `bbl_users_email`). Classes:
`class-bbl-users-db` (tabela draft), `class-bbl-users-crypto` (cifra AES-256-CBC da senha de app do
Gmail, chave derivada das salts), `class-bbl-users-password` (gerador de senha configurável),
`class-bbl-users-mailer` (SMTP Gmail + envio), `class-bbl-users-creator` (criação a partir do
rascunho), `class-bbl-users-admin/ajax/diagnostics`. Opções: `bbl_users_db_version`, `bbl_users_email`,
`bbl_users_smtp`, `bbl_users_password`; telas em `templates/admin-*.php`.

#### `bebelume-subscription-delay` (v "4.1.0 DEBUG") — trial e anti-trial na Stripe
Controla o trial no nível e a **reassinatura**:
- Adiciona campo "Trial Grátis (dias)" por nível (opção `pmpro_level_{id}_delay_days`; ativo: **30**).
- `pmpro_stripe_checkout_session_parameters` (prio 999): força `trial_period_days` na Checkout
  Session para novos usuários; para **retornantes remove o trial** (Stripe rejeita `0`). Versão DEBUG:
  loga os parâmetros (com redação de PII) quando `BBL_SUB_DELAY_DEBUG` estiver definido.
- `pmpro_checkout_level` (prio 20): `charge_returning_on_signup` — se o usuário **já assinou antes**
  (qualquer subscription ou order `success`, por id de usuário **ou e-mail** do POST), define
  `initial_payment = billing_amount` e zera trial → **reassinatura cobra desde o dia 1** (anti-trial).

#### `bebelume-pixel` (v3.0.1) — Meta Pixel + Conversions API
Pagamento **on-site** (sem webhook). Eventos:
- `InitiateCheckout` (Pixel, ao abrir o checkout), `CompleteRegistration` e `Purchase`
  (Pixel + CAPI em `pmpro_after_checkout`, com `StartTrial` quando o pagamento é trial) e
  `AddToCart`.
- Guarda atribuição `_fbc`/`_fbp` no `user_meta` (`bebelume_fbc`/`bebelume_fbp`) no login/registro.
- CAPI envia eventos com hashing SHA-256 de PII e event_id deduplicado. Config: `bebelume_pixel_id`,
  `bebelume_capi_token` (segredo), `bebelume_test_mode`, `bebelume_capi_test_event_code`.

#### `bebelume-headers` (v2.0.0)
Gerencia snippets injetados no `<head>` de todas as páginas a partir do painel
(opção `bebelume_header_scripts`, lista de {name, active, code}).

#### `bebelume-popups` (v1.0.0)
CPT `bpp_popup` + meta-box de configuração; exibe popups informativos no front com estética
customizável por página/post (`admin/css`,`front/css/js`, `includes/{post-type,admin-menu,meta-box,frontend}.php`).

#### `bebelume-arteduca` (v1.38.2) — conteúdo educacional (blocos + CRUD pedagógico)
Plugin de **blocos Gutenberg personalizados** para o conteúdo educacional e **CRUD pedagógico**:
- `includes/meta-pedagogico.php` — registra `_bebelume_direitos` e `_bebelume_campos` (arrays) para
  `post` e `page` (REST) + sidebar do editor (`sidebar-pedagogico.js`) listando Direitos de
  Aprendizagem e Campos de Experiência (BNCC).
- `includes/crud-pedagogico.php` — admin "ArtEduca → Conteúdo Pedagógico": gerencia as **listas**
  (Campos de Experiência / Direitos de Aprendizagem) e **imagens** por campo. Dados em options:
  `bebelume_campos_experiencia`, `bebelume_campos_images`, `bebelume_direitos_aprendizagem`.
- `includes/custom-body-classes.php` — meta-box `_bebelume_body_classes` → `body_class`.
- `includes/paginas-arteduca.php` — registra template de página "ArtEduca início"
  (`page-arteduca-inicio.php`), redireciona a página inicial do produto por nível e admin de páginas.
- **Blocos** (17, em `blocks/`): `accordion-tipo1/2`, `arteduca-footer`, `buscador-arteduca`,
  `campos-pedagogicos` (render.php), `documento`, `filtro-arteduca`, `icon-box`, `link-externo`,
  `marketing-capa/conteudo/texto/titulo`, `titulo-arteduca`.
- Assets: `frontend.css`, `sidebar-pedagogico.js`, `lucide.min.js`, fontes, imagens por campo.

#### `bebelume-arteduca-video` (v1.2.0)
Bloco Gutenberg de **player de vídeo HTML5 com Plyr.io** e customização de cores (registra Plyr do CDN).

#### `bebelume-arteduca-musica-v1.8.5-VIDEO-HEIGHT-FIX` (v1.0.0)
Bloco de **player de música estilo Spotify** (Plyr + Lucide) com playlists e integração com posts
de vídeo (build antigo do player; correção de altura de vídeo no nome).

#### `bebelume-roles` (v1.0.0 — INATIVO)
CPT `bebelume_profile` de perfis de acesso ao wp-admin (quais menus/submenus cada perfil vê).

#### `bbl_profile_sem_fields` (v1.3.0) — "Bebelume Profile" (legado, ativo)
Plugin antigo de perfil/tema "leve e colorido" com blocos de personagem
(`andar`, `terreo`, `telhado`, `sobre-nos`/niveis, `plyr`) e templates PMPro espelho
(`templates/{account,billing,cancel,checkout,confirmation,invoice,levels,login,register,...}.php`).
Registra username aleatório (`init`) e integração PMPro própria. **Não** é a fonte dos templates
PMPro atuais (esses vêm de `bebelume-pmpro-profile/templates/pmpro`), mas segue ativo por blocos e
estilos. Inclui `assets/js/plyr-init.js` (inicializa o Plyr) e css custom de Plyr.

### 4.2 mu-plugins (`wp-content/mu-plugins`)

| Arquivo | Função |
|---|---|
| `bebelume-smtp.php` | Configura `wp_mail` para **SMTP Gmail** (TLS, porta 587) a partir das constantes `SMTP_*` do `wp-config.php`. |
| `bebelume-stripe-meta.php` | Filtro `pmpro_stripe_checkout_session_parameters`: injeta **`fbc`, `fbp` e `site_source`** nos `metadata` da Checkout Session (Match Quality da Meta CAPI). Fontes: cookie `_fbc`/`_fbp` nativo → cookie `bebelume_fbc/fbp` → `user_meta`. |
| `bebelume-test-price-0.50.php` | **HARNESS DE TESTE TEMPORÁRIO** (não é produto). Força `billing_amount=0.50` no nível 3 **apenas** para 2 contas de teste (`hylozero/hylozero@gmail.com` user 186; `user-6f60fbd8/souzanato84@gmail.com` user 188). **Deve ser removido no encerramento (item #19).** |

### 4.3 Plugins de terceiros ativos (e utilidade)

| Plugin | Papel |
|---|---|
| `paid-memberships-pro` 3.6.5 | Motor de assinaturas (ver §5). |
| `wp-super-cache` 3.0.3 | Cache de página (drop-in `advanced-cache.php`). |
| `autodescription` 5.1.2 | SEO (meta tags, schema). |
| `akismet` 5.6 | Anti-spam. |
| `video-playlist-block` 2.1.1 | Bloco de playlists de vídeo com slider/modal sobre **posts** + integração PMPro (respeita `pmpro_has_membership_access`). |
| `download-plugins-dashboard` 1.9.9 | Dev: baixar ZIP de plugins/temas pelo painel. |
| `duplicate-page-off` 4.5.6 | Dev: duplicar posts/páginas. |
| `reenviar-plugin` 1.3.0 | Dev: reenviar/substituir plugin por upload. |
| `password-protected` 2.7.12 | Utilitário de manutenção — **instalado mas desligado** (`password_protected_status=0`). |
| `pmpro-update-manager` (INATIVO) | Updates de add-ons PMPro. |
| `pmpro-email-diagnostic` (INATIVO) | Diagnóstico de e-mails do PMPro (loga em `wp_pmpro_email_diagnostic_log`). |

---

## 5. Paid Memberships Pro — configuração

### 5.1 Níveis de assinatura (`wp_pmpro_membership_levels`)

| id | Nome | billing_amount | ciclo | trial | Stripe product (`pmpro_membership_levelmeta`) |
|---|---|---|---|---|---|
| **3** | Bebelume ArtEduca – Plano Mensal | R$ 29,90 | 1 Month | 30 dias (via delay) | `prod_UQR7WEyR7CZGa2` |
| **6** | Bebelume ArtEduca – Plano Anual | R$ 290,00 | 1 Year | 30 dias (via delay) | `prod_UAqq0S48gApiX1` |

Níveis **excluídos** (`1,2,4,5` Canal Bebelume Mensal/Anual e variantes) e `7,8` ainda deixaram
linhas em `wp_pmpro_membership_levelmeta` (mensagens/Stripe products) e mapeamentos de páginas.

Configuração de **trial por nível** fica em options `pmpro_level_3_delay_days = 30` e
`pmpro_level_6_delay_days = 30` (campo do plugin `bebelume-subscription-delay`).

### 5.2 Páginas PMPro (`wp_options` `pmpro_*_page_id` → post)

| Página | slug | papel |
|---|---|---|
| Níveis | `planos` | listagem de planos (`[pmpro_levels]`) |
| Checkout | `pagamento-da-associacao` | checkout (`[pmpro_checkout]`) |
| Confirmação | `confirmacao-da-associacao` | pós-checkout (`[pmpro_confirmation]`) |
| Conta | `conta-de-associacao` | minidashboard (`[pmpro_account]`) |
| Cancelamento | `cancelamento-da-associacao` | cancelar assinatura (`[pmpro_cancel]`) |
| Pedidos/Faturas | `pedidos-de-associacao` | histórico (`[pmpro_invoice]`) |
| Billing | `informacoes-de-cobranca` | **desativada** (redirect p/ conta) |
| Seu Perfil | `seu-perfil` | perfil/membro (`[pmpro_member_profile_edit]`) |

### 5.3 Opções globais relevantes do PMPro

- Gateway **Stripe**, ambiente **live**, moeda **BRL** (`pmpro_gateway`, `pmpro_gateway_environment`, `pmpro_currency`).
- Chave secreta Stripe Connect live em option `pmpro_live_stripe_connect_secretkey` (segredo, fora do repo).
- Cada nível tem `stripe_product_id`, mensagem de conta (`membership_account_message`) e
  `confirmation_in_email` em `wp_pmpro_membership_levelmeta`.
- Webhook do Stripe: secret em `bebelume_stripe_webhook_secret` (segredo).

---

## 6. Fluxos de negócio — execução passo a passo

### 6.1 Cadastro de conta
1. **Registro padrão WP** (`/wp-login.php?action=register`) customizado por `class-auth` + `class-register`
   (reCAPTCHA v3, campos de nome). Alternativa: **"Continuar com Google"** (`class-google-auth`).
2. Ao criar o usuário (`user_register`):
   - `class-email-confirmation` marca `bbl_email_confirmed=0` e grava `bbl_confirm_token`.
   - `bebelume-pixel` salva `_fbp`/`_fbc` (atribuição) e dispara `CompleteRegistration` (fora do checkout PMPro).
3. E-mail de boas-vindas/confirmação via `BBL_Emails` (SMTP Gmail).
4. Login de não-confirmados é **bloqueado** → tela "verifique seu e-mail" (reenvio). Confirma →
   `class-complete-profile` redireciona para completar cadastro.
5. Usernames são aleatórios (`user-XXXXXXXX`) ou derivados do e-mail no fluxo Google.

### 6.2 Checkout (novo usuário / trial de 30 dias)
1. Usuário escolhe plano em `/planos/`.
2. Checkout PMPro custom (`class-checkout`): resumo do plano, selos, campos de nome e **CPF** para novos.
3. `bebelume-subscription-delay`: usuário **novo** → Checkout Session Stripe com
   `subscription_data.trial_period_days = 30` e `initial_payment = 0`. Stripe cria subscription em
   `trialing`; o **primeiro débito real** acontece ao fim do trial.
4. `bebelume-stripe-meta` injeta `fbc/fbp/site_source` em `metadata`.
5. `bebelume-pixel`: `InitiateCheckout` no preheader.
6. Pagamento on-site confirmado → `pmpro_after_checkout`:
   - `class-checkout-fields` salva nome + dados fiscais (`cpf`, `cpf_formatted`, `pmpro_b*`);
   - `bebelume-pixel`: evento `StartTrial` (novo) ou `Purchase` (retornante) no Pixel + CAPI;
   - `class-n8n-webhook`: se for a **primeira** compra do usuário no nível, dispara o webhook n8n;
   - a NFS-e **não** é emitida no trial (`total = 0`; emissão é gated por `total>0`).
7. E-mails PMPro de confirmação (templates pt-BR `BBL_PMPro_Emails`).

### 6.3 Reassinatura / retornante (cobra desde o dia 1)
1. Checkout detecta "retornante" (usuário já teve subscription ou order `success` — chave é o
   **e-mail/conta**): `charge_returning_on_signup` define `initial_payment = billing_amount`,
   `trial_amount/trial_limit = 0`; `modify_and_debug_stripe_params` remove `trial_period_days`.
2. `class-checkout-fields` exige o **card "Dados fiscais"** (CPF + endereço) antes do pagamento
   (obrigatório p/ retornante; campos não bloqueiam cadastro de novos usuários).
3. Pagamento imediato (total > 0, sem trial) → `pmpro_after_checkout` dispara **emissão da NFS-e**
   (`after_checkout_emit_nfse` → `maybe_emit_nfse`). Nota sai daqui porque o fluxo não passa por
   `updateStatus()` (ver §6.7).
4. `bebelume-pixel`: evento `Purchase`.

### 6.4 Cobrança recorrente e webhook Stripe
1. Stripe cobra o cartão no fim de cada ciclo.
2. Webhook `invoice.payment_succeeded` → PMPro cria/atualiza **order `success`** e a subscription
   avança (`next_payment_date` em `wp_pmpro_subscriptions`).
3. PMPro dispara `pmpro_subscription_payment_completed` → **NFS-e é emitida** (se `total>0` e ainda
   não emitida) → payload fiscal → TransmiteNota → registro `pendente` na `wp_bebelume_nfse`.
4. Cron interno `bebelume_nfse_check_pending` consulta a nota; quando `aprovada`, persiste
   `numero_nfse`/`link_pdf`/`link_xml` e envia o e-mail com anexos (opção `bbl_nfse_email_wp_aprovacao`).

### 6.5 Falha de pagamento
`pmpro_subscription_payment_failed` (webhook `invoice.payment_failed`) →
`class-billing-failure` captura o **motivo da recusa** (declínio) por e-mail → o template de e-mail
pt-BR de falha consome o motivo. **Cartão recusado não concede acesso** (o acesso de membro só é
criado no ramo de sucesso de `pmpro_complete_checkout`; ordem fica com status `error`/`refunded`).

### 6.6 Cancelamento
1. Cancelamento self-service (`/cancelamento-da-associacao/`).
2. `class-cancel-at-period-end` intercepta: em vez de cancelar imediato, envia `cancel_at_period_end`
   ao Stripe e grava meta `bbl_cancel_at_period_end=1` na subscription → **acesso mantido até o fim do
   ciclo pago**.
3. Webhook `customer.subscription.deleted` finaliza localmente no fim do período.
4. Template de conta mostra status/cronograma; página de entrada do conteúdo exibe o **banner âmbar**
   de "encerrando no fim do período" (`class-cancel-notice`).
5. Anti-duplicidade (`class-prevent-duplicate-subscription`) garante que sub agendada para encerrar
   não é tratada como "cobrável" e permite reassinatura no mesmo plano sem cancelar/duplicar.

### 6.7 NFS-e — emissão e ciclo de vida (detalhe)
- **Gatilhos** (só `success` e `total > 0`): cobrança recorrente, pagamento direto, checkout de
  retornante (`pmpro_after_checkout`).
- **Dados do tomador** vêm do perfil (`user_meta`): CPF (`cpf`, formatado `cpf_formatted`),
  telefone/endereço/bairro/município/UF/CEP (`pmpro_bphone`, `pmpro_baddress1`, `pmpro_baddress2`
  [= bairro, convenção], `pmpro_bcity`, `pmpro_bstate`, `pmpro_bzipcode`). Bairro vazio → fallback
  "Centro". Validação exige CPF ≥11 dígitos, telefone, endereço, município, UF, CEP ≥8.
- **Payload** (`montar_payload`): natureza 1, tipo_serviço `01.09`, código `01090`, descrição
  "Assinatura de serviço digital", alíquota 2, ISS não retido (2), tomador em maiúsculas
  (mb_strtoupper), datas com `current_time()` (fuso do site — corrige emissão pós-21h que saía com
  dia seguinte).
- **Registro**: inserido em `wp_bebelume_nfse` com `searchkey`, status `pendente`; trava
  `bbl_nfse_lock_{order_id}` evita duplicidade em corrida entre hooks.
- **Cron** consulta `ConsultarEmissaoNotaNfse`; normaliza status da TransmiteNota
  (`aguardando processamento`→`pendente`, `em andamento`→`processando`, `aprovada`/`aprovada com
  correcao`→`aprovada`, `cancelada`→`cancelada`, `reprovada`→`reprovada`) e dispara e-mail ao aprovar.
- **E-mail da nota**: o endpoint próprio da TransmiteNota (`EnviarEmailNfse`) está **fora do ar** —
  envio passou a ser via WordPress (`Bebelume_NFSe_Email`) com PDF/XML anexos, ligado pela opção
  `bbl_nfse_email_wp_aprovacao`.
- **Admin**: listagem com botões de atualizar status e cancelar nota; tela de teste (emissão avulsa,
  `order_id=0`).

### 6.8 Bloqueio de dados fiscais (tela bloqueante) — regra de produto
Flag `BBL_FISCAL_BLOCK` + módulo `BBL_Fiscal_Block`.
1. **Quando bloqueia** (computado e cacheado 5 min em transiente `bbl_fiscal_block_{user_id}`),
   para usuário logado no front, fora de admin/AJAX/cron/REST/login:
   - dados fiscais **incompletos** (mesma validação da NFS-e) E
   - (a) existe subscription `active`/`trialing` com `next_payment_date` **≤ 7 dias**
     (`DIAS_PRECOBRANCA = 7`; no trial, o fim do trial **é** a `next_payment_date` da sub `trialing`,
     não há coluna própria) — ou (b) existe **pedido pago sem nota aprovada** (backstop pós-cobrança).
   - Dados completos → nunca bloqueia (mesmo se a API de NFS-e falhar).
2. **O que é permitido com o bloqueio ativo**: apenas a **área de conta** e telas associadas —
   `account`, `cancel`, `invoice`, `seu-perfil` (páginas PMPro; billing é redirect). Todas as demais
   rotas renderizam a tela bloqueante.
3. **Tela** (render com `get_header/get_footer`, card próprio): logo, título/subtítulo, aviso de que
   nenhuma cobrança é feita naquele momento, campos **CEP → Endereço → Bairro(opcional) → Município+UF
   → CPF → Telefone** com máscaras JS, **busca ViaCEP** preenchendo endereço/bairro/município/UF,
   validação de CPF, botão "Salvar e continuar", link **"Fazer isso depois" → conta**, e rodapé com o
   fim do período gratuito quando aplicável.
4. **Ao salvar**: grava `cpf`/`cpf_formatted` e `pmpro_b*`, limpa o transiente, **reemite NFS-e** de
   pedidos pagos sem nota (`reemitir_apos_dados`), redireciona para a home.
5. A **área de conta** mostra **banner âmbar** com aviso da pendência quando `is_block_active()` (usa
   a mesma regra + cache), apontando para "completar cadastro" (a tela bloqueante).

> **Impacto no trial (contrato):** durante o período gratuito, a tela pode surgir nos 7 dias que
> antecedem a **primeira cobrança** se o cadastro estiver incompleto. Nenhuma cobrança é feita ao
> salvar; o trial continua com a mesma data de término; a área de conta segue acessível. Isto está
> refletido nos **Termos de Uso** (seção 4; atualização 06/09/2026).

### 6.9 E-mails
- **SMTP Gmail** (mu-plugin) para toda a saída via `wp_mail`.
- `BBL_Emails`: e-mails WP de conta em HTML/marca (novo usuário, reset/senha/e-mail).
- `BBL_PMPro_Emails`: templates do PMPro em pt-BR com shell Bebelume; placeholders `!!variavel!!` do
  PMPro continuam resolvidos depois do filtro; rodam em prioridade 20 (após `pmpro_kses`).
- **NFS-e aprovada**: e-mail próprio com PDF/XML (ver §6.7).
- Registro de logs de diagnóstico de e-mail (plugin `pmpro-email-diagnostic`, hoje inativo) na tabela
  `wp_pmpro_email_diagnostic_log`.
- Regra de casa: **senha nunca é enviada em texto claro por e-mail**.

### 6.10 Marketing e automação
- **Meta Pixel + CAPI** (`bebelume-pixel` + `bebelume-stripe-meta`): eventos + `fbc/fbp/site_source`
  nos metadata do Stripe para deduplicação/match quality.
- **n8n** (`class-n8n-webhook`): webhook de primeira compra por nível
  (`bbl_n8n_webhook_3`/`_6` = `https://automacao.bematendido.com.br/webhook/bebelume`).
- **Headers** e **popups**: scripts globais e avisos customizados.

### 6.11 Área de conta (dashboard do membro)
Template `templates/pmpro/account.php` (sobrescreve `[pmpro_account]`):
- Modo **overview** (hub) e modo **detalhe** da assinatura (query `?plano/level`).
- Mostra assinaturas locais **e de satélites** (Canal/ArtEduca — `BBL_Satellite_Memberships`), com
  histórico de pedidos agregado (via MySQL direto nas bases satélites).
- Banner de **cancelamento agendado** (âmbar), pill de status no overview, mensagens e mapas de
  botões por estado; **banner âmbar de dados fiscais** quando `BBL_Fiscal_Block::is_block_active()`.
- `class-cancel-notice` replica o banner na página de entrada de conteúdo.

### 6.12 Conteúdo educacional e restrição
- Posts/páginas marcados com metadados BNCC (`_bebelume_campos`, `_bebelume_direitos`,
  `_bebelume_body_classes`) exibidos em hubs por nível.
- Páginas de produto restritas por nível via `pmpro_memberships_pages` (mapeamentos históricos dos
  níveis 1/2: 56 páginas cada; ArtEduca: o hub por nível). Acesso ao conteúdo = regras de página do
  PMPro + página de "sem acesso" custom (`render_restricted_content`) + hubs de entrada
  (`bbl_level_pagina_inicio_*`, `bbl_level_pagina_planos_*`, imagens `bbl_level_imagem_*`).
- Restrição em posts de vídeo respeitada por `video-playlist-block`.

---

## 7. Banco de dados — estrutura completa

Prefixo `wp_`. Datas do PMPro são armazenadas em **UTC**; exibição em `America/Sao_Paulo`. No PMPro 3.x
as assinaturas são tabelas próprias (`pmpro_subscriptions` + meta), com `next_payment_date`.

### 7.1 Tabelas (lista)

Núcleo WP: `wp_options`, `wp_users`, `wp_usermeta`, `wp_posts`, `wp_postmeta`, `wp_comments`,
`wp_commentmeta`, `wp_terms`, `wp_termmeta`, `wp_term_taxonomy`, `wp_term_relationships`,
`wp_links` (site único).

PMPro: `wp_pmpro_membership_levels`, `wp_pmpro_membership_levelmeta`, `wp_pmpro_membership_orders`,
`wp_pmpro_membership_ordermeta`, `wp_pmpro_memberships_users`, `wp_pmpro_subscriptions`,
`wp_pmpro_subscriptionmeta`, `wp_pmpro_discount_codes`, `wp_pmpro_discount_codes_levels`,
`wp_pmpro_discount_codes_uses`, `wp_pmpro_memberships_categories`, `wp_pmpro_memberships_pages`,
`wp_pmpro_groups`, `wp_pmpro_membership_levels_groups`.

Próprias: `wp_bebelume_nfse`, `wp_bebelume_users_draft`.

Outras: `wp_actionscheduler_*` (Action Scheduler), `wp_pp_activity_logs`, `wp_pmpro_email_diagnostic_log`
(plugin diagnóstico inativo).

### 7.2 Tabelas próprias — colunas

**`wp_bebelume_nfse`** — notas fiscais:
`id` (PK, bigint unsigned, auto) · `order_id` (bigint unsigned, índice, 0 = nota avulsa/teste) ·
`user_id` (índice) · `searchkey` (varchar 120, índice — chave da TransmiteNota) ·
`status` (varchar 30, índice; valores: `pendente`, `processando`, `aprovada`, `cancelada`,
`reprovada`, `erro`) · `numero_nfse` (varchar 30) · `link_pdf` · `link_xml` (text) · `valor`
(decimal 10,2) · `payload` (longtext, JSON enviado) · `resposta` (longtext, JSON devolvido, inclui
histórico de envio de e-mail) · `created_at` · `updated_at` (datetime).

**`wp_bebelume_users_draft`** — fila de criação de usuários em lote:
`id` (PK auto) · `nome` (varchar 190) · `email` (varchar 190, índice) · `status` (varchar 20,
`rascunho`→…; índice) · `membership_level` (int) · `user_id` (bigint, preenchido após criar) ·
`username` (varchar 60) · `email_enviado` (tinyint 1) · `erro` (text nullable) · `criado_em`
(datetime) · `processado_em` (datetime nullable).

### 7.3 PMPro 3.x — colunas principais

**`wp_pmpro_membership_levels`**: `id`, `name`, `description`, `confirmation`, `initial_payment`,
`billing_amount`, `cycle_number`, `cycle_period` (`Month`/`Year`), `billing_limit`, `trial_amount`,
`trial_limit`, `allow_signups`, `expiration_number`, `expiration_period`.
**`wp_pmpro_membership_levelmeta`**: `meta_id`, `pmpro_membership_level_id`, `meta_key`, `meta_value`.
Keys em uso: `stripe_product_id`, `membership_account_message`, `confirmation_in_email`.

**`wp_pmpro_subscriptions`** (o "coração" no 3.x): `id`, `user_id`, `membership_level_id`, `gateway`,
`gateway_environment`, `subscription_transaction_id` (id da sub no Stripe), `status`
(`active`/`trialing`/`cancelled`/…), `startdate`, `enddate`, **`next_payment_date`** (índice — fim do
trial ou próxima cobrança; base das regras de bloqueio fiscal e anti-trial), `billing_amount`
(decimal 18,8), `cycle_number`, `cycle_period`, `billing_limit`, `trial_amount`, `trial_limit`,
`modified`.
**`wp_pmpro_subscriptionmeta`**: `meta_id`, `pmpro_subscription_id`, `meta_key`, `meta_value`. Key
própria em uso: **`bbl_cancel_at_period_end`** (1 = sub agendada para encerrar no fim do período,
não vai mais cobrar).

**`wp_pmpro_membership_orders`**: `id`, `code` (uniq), `session_id`, `user_id`, `membership_id`,
`paypal_token`, `billing_name`, `billing_street`/`_street2`/`_city`/`_state`/`_zip`/`_country`/
`_phone`, `subtotal`, `tax`, `checkout_id`, `total`, `payment_type`, `cardtype`, `accountnumber`
(mascarado), `expirationmonth/year`, `status` (`success`/`error`/`refunded`/`pending`/…),
`gateway`, `gateway_environment`, `payment_transaction_id`, `subscription_transaction_id`,
`timestamp`, `affiliate_id/subid`, `notes`.
**`wp_pmpro_membership_ordermeta`**: `meta_id`, `pmpro_membership_order_id`, `meta_key`, `meta_value`.

**`wp_pmpro_memberships_users`** (vínculo atual/histórico): `id`, `user_id`, `membership_id`,
`code_id`, `initial_payment`, `billing_amount`, `cycle_number`, `cycle_period`, `billing_limit`,
`trial_amount`, `trial_limit`, `status`, `startdate`, `enddate`, `modified`.

Cupons: `wp_pmpro_discount_codes` (`code`, `starts`, `expires`, `uses`, `one_use_per_user`),
`wp_pmpro_discount_codes_levels`, `wp_pmpro_discount_codes_uses`.
Restrição por conteúdo: `wp_pmpro_memberships_pages` (`membership_id`, `page_id`) e
`wp_pmpro_memberships_categories` (`membership_id`, `category_id`).
Grupos de níveis: `wp_pmpro_groups`, `wp_pmpro_membership_levels_groups`.

### 7.4 Campos do usuário (`wp_usermeta`) — chaves usadas pela solução

Criação/autenticação (prefixo `bbl_`):
- `bbl_email_confirmed` (0/1) · `bbl_confirm_token` — confirmação de e-mail.
- `bbl_google_id`, `bbl_google_avatar`, `bbl_auth_provider` (`google`) — login Google.

Dados fiscais / cobrança (escritos pelo checkout, complete-profile e fiscal block; **lidos pela
NFS-e**):
- `cpf` (só dígitos), `cpf_formatted` (XXX.XXX.XXX-XX).
- `pmpro_bphone` (telefone), `pmpro_baddress1` (endereço), `pmpro_baddress2` (**bairro**, convenção),
  `pmpro_bcity` (município), `pmpro_bstate` (UF), `pmpro_bzipcode` (CEP).
- Nativos PMPro: `pmpro_bemail`, `pmpro_bfirstname`, `pmpro_blastname`, `pmpro_baddress1/2` etc.

Stripe/marca: `pmpro_stripe_customerid` (customer Stripe), `pmpro_logins`, `pmpro_visits`,
`pmpro_views`, `pmpro_archived_notifications`, `pmpro_expiration_notice_1`.
Marketing/atribuição: `bebelume_fbc`, `bebelume_fbp`.

### 7.5 `wp_options` — chaves de configuração (agrupadas)

- PMPro de página: `pmpro_account_page_id`, `pmpro_cancel_page_id`, `pmpro_checkout_page_id`,
  `pmpro_confirmation_page_id`, `pmpro_invoice_page_id`, `pmpro_levels_page_id`,
  `pmpro_billing_page_id`, `pmpro_member_profile_edit_page_id`.
- PMPro/Stripe: `pmpro_gateway=stripe`, `pmpro_gateway_environment=live`, `pmpro_currency=BRL`,
  `pmpro_live_stripe_connect_secretkey` (segredo).
- Trial: `pmpro_level_3_delay_days=30`, `pmpro_level_6_delay_days=30`.
- NFS-e: `bbl_nfse_ambiente=producao`, `bbl_nfse_cnpj`, `bbl_nfse_api_key` (espelho legado),
  `bbl_nfse_debug`, `bbl_nfse_https`, `bbl_nfse_email_wp_aprovacao=1`, e `bbl_nfse_settings`
  (array: `natureza_operacao`, `tipo_servico`, `codigo_servico`, `descricao_servico`,
  `valor_aliquota`, `iss_retido`, `api_key_homologacao`, `api_key_producao`).
- Content-hub por nível: `bbl_level_pagina_inicio_{1,2,3,6}`, `bbl_level_pagina_planos_{1,2,3,6}`,
  `bbl_level_imagem_{1,2,3,6}`; páginas `bebelume_pagina_logado_com_assinatura` (1964, "Home –
  ArtEduca"), `bebelume_pagina_logado_sem_assinatura` / `bebelume_pagina_nao_logado` (2321,
  "Bebelume ArtEduca — capa").
- Webhooks n8n: `bbl_n8n_webhook_3`, `bbl_n8n_webhook_6`.
- Pedagogia: `bebelume_campos_experiencia` (5 campos), `bebelume_campos_images`,
  `bebelume_direitos_aprendizagem` (6 direitos).
- Meta Pixel/CAPI: `bebelume_pixel_id`, `bebelume_capi_token` (segredo), `bebelume_test_mode`,
  `bebelume_capi_test_event_code`; headers: `bebelume_header_scripts`.
- Segredos/webhooks: `bebelume_stripe_webhook_secret`, `bebelume_api_jwt_secret` (segredo).
- Users em lote: `bbl_users_db_version`, `bbl_users_email` (assunto/corpo), `bbl_users_smtp`,
  `bbl_users_password`.
- Transientes (runtime): bloqueio fiscal `bbl_fiscal_block_{user_id}` (5 min), trava NFS-e
  `bbl_nfse_lock_{order_id}` (2 min), `bbl_google_state_*` (10 min), anti-brute-force.

### 7.6 Volume aproximado (snapshot 09/09/2026 — muda com o tempo)
~122 usuários · 99 posts publicados · 21 páginas publicadas · 40 pedidos · 30 subscriptions ·
43 vínculos atuais/históricos (`memberships_users`) · 7 notas fiscais · 6 rascunhos de criação em lote.

---

## 8. Tema ativo (`wp-content/themes/bebelume`)

- **Bebelume Theme v1.0.0** — tema clássico PHP com **blocos Gutenberg dinâmicos**.
- Cabeçalho de tema só no `style.css`; estilos reais em `assets/css/main.css`; JS em `assets/js/main.js`.
- PHP: `functions.php`, `header.php`, `footer.php`, `front-page.php` (animação "casinha/castelinho"),
  `page.php`, `single.php`, `index.php`; `inc/enqueue.php` (assets), `inc/register-blocks.php`.
- Blocos **compilados** (webpack) em `build/` (versionado) e fontes em `src/blocks/`:
  `andar-casinha-bebelume`, `andar-castelinho-bebelume`, `sobre-nos`, `telhado-casinha-bebelume`,
  `telhado-castelinho-bebelume`, `telhado-passarinhos-bebelume`, `terreo-casinha-bebelume`,
  `terreo-castelinho-bebelume` (personagens/telhado/terréo coloridos da identidade).
- Fontes/imagens ilustradas em `assets/images/*`.
- **Nota de versionamento:** o `.git` histórico do tema (77 MB) foi movido para backup
  (`/home/claudeops/backups/arteduca/06092026-tema-bebelume-git/.git`) para permitir versionar os
  arquivos do tema normalmente; o histórico original está preservado fora do servidor.

---

## 9. Integrações e APIs externas

| Serviço | Uso | Onde/segredo |
|---|---|---|
| **Stripe** | Assinaturas, Checkout Session, customers, webhooks (`invoice.payment_succeeded/failed`, `customer.subscription.*`). | Chave em `pmpro_live_stripe_connect_secretkey` (option). Webhook secret `bebelume_stripe_webhook_secret`. IDs de produto por nível em `stripe_product_id`. |
| **TransmiteNota (NFS-e)** | Emissão/consulta/cancelamento de NFS-e. Host `api1.transmitenota.com.br`; path `/api/producao/` (ativo) e `/api/homologacao/`; POST JSON `{ApiKey, Cnpj, Dados}`. Endpoints: `EnviarNfse/`, `ConsultarEmissaoNotaNfse/` (`{searchkey}`), `CancelarNfse/` (`{searchkey, motivo_cancelamento}`), `ConsultarCancelamentoNfse/`, `ConsultarPDFNfse/`, `ConsultarXMLNfse/`, `EnviarEmailNfse/` (**fora do ar** — não usar). CNPJ 34.916.482/0001-46. Chaves em options NFS-e (segredo). |
| **Google OAuth** | "Continuar com Google". Client ID `33347749194-…apps.googleusercontent.com`; endpoints `accounts.google.com/o/oauth2/v2/auth`, `oauth2.googleapis.com/token`, `www.googleapis.com/oauth2/v3/userinfo`; redirect dinâmico `site_url('/wp-login.php?action=google_callback')` (origens autorizadas no Google Console). Secret em `BBL_GOOGLE_CLIENT_SECRET` (wp-config). |
| **Google reCAPTCHA v3** | Registro (site/secret key fixas em `class-register.php`, threshold 0.5). |
| **ViaCEP** | Busca de endereço por CEP no checkout e na tela bloqueante (`https://viacep.com.br/ws/{8}/json/`). |
| **Meta (Facebook) Pixel + CAPI** | Eventos de conversão; token CAPI em `bebelume_capi_token` (segredo); hashing SHA-256. |
| **n8n (bematendido)** | Webhook de primeira compra por nível: `https://automacao.bematendido.com.br/webhook/bebelume`. |
| **E-mail (SMTP Gmail)** | SMTP do Google via constantes `SMTP_*` no wp-config. |
| **REST PMPro + MySQL entre sites** | `BBL_Satellite_Memberships`: `GET /wp-json/pmpro/v1/get_membership_levels_for_user?user_id=…` com Application Password + SELECT direto nas bases `wp_canal`/`wp_arteduca`. Credenciais em constantes `BBL_CANAL_*`/`BBL_ARTEDUCA_*` (wp-config). |
| **Google Fonts** | Nunito (interface/login/e-mails) e demais fontes por CDN. |
| **Plyr.io / Lucide** | Players de vídeo/música e ícones (CDN). |

---

## 10. Segurança, regras de produto e decisões de casa

- **Segredos fora do código**: nenhuma credencial no repositório (público). O GitHub bloqueia push
  com segredo (secret scanning); política: tudo sensível em `wp-config.php`/options fora do repo.
- **Trial único por e-mail/conta**; **reassinatura cobra desde o dia 1** (anti-trial) —
  `bebelume-subscription-delay`. Assinante que já teve conta não ganha novo trial.
- **1 subscription cobrável por (usuário, plano)** (`class-prevent-duplicate-subscription`).
- **Cancelamento no fim do período** (não perde acesso na hora) — `class-cancel-at-period-end`.
- **Acesso exige pagamento real**: cartão recusado não libera conteúdo.
- **NFS-e a cada pagamento real**, com trava anti-corrida, reemissão pós-dados fiscais e e-mail ao
  aprovar (TransmiteNota sem e-mail próprio).
- **Dados fiscais bloqueiam o site** quando cobrança chega sem nota — a área de conta fica livre; o
  membro completa CPF/endereço para destravar (flag `BBL_FISCAL_BLOCK`).
- **Login protegido**: brute-force (5 tentativas/15 min), confirmação de e-mail obrigatória,
  reCAPTCHA no registro; não-admins são redirecionados para fora do `wp-admin` e sem admin bar.
- **Email**: senha nunca em claro; templates HTML de marca; testes com `pre_wp_mail` antes de envio
  real (aprovação explícita para disparos reais).
- **Fuso**: datas do PMPro são UTC; relatórios exibidos em Brasília com rótulo; emissão de NFS-e usa
  `current_time()` para sair no dia correto BRT.
- **Operação**: edições em produção passam por backup com `.orig` + `acoes.md` + `rollback.sh`,
  instalação preservando dono/modo (`sudo install -o www-data -g www-data`), validação com `php -l` e
  `wp eval`/`eval-file`.
- **Versionamento**: `git` roda como root (`sudo git -C /opt/wp-arteduca …`); identidade local
  `souzanato <souzanato@users.noreply.github.com>`; push por chave SSH deploy; antes de push, varredura
  de segredos.

---

## 11. Pendências / itens abertos (estado em 09/09/2026)

- **Remover o mu-plugin de teste `bebelume-test-price-0.50.php`** (override R$0,50 do nível 3 para as
  contas de teste) e fazer o estorno/limpeza das assinaturas de teste (encerramento #19).
- Limpar `next_payment_date` fake da assinatura de teste do user 188 (usada para testar a tela bloqueante).
- Investigar conta 187 (`anapaula.secretariaescolar@gmail.com`).
- Nota fiscal do pedido 79 (cliente real) ainda `pendente` — backfill/e-mail adiados (05/09/2026).
- Recomendado (não feito): rotacionar o Google OAuth client secret que já esteve em repouso em
  logs/git locais.

---

*Fim do contexto. Sempre que algo mudar (níveis, regras de produto, novas integrações), atualizar
este arquivo para manter o mapa da solução fiel ao código e ao banco.*
