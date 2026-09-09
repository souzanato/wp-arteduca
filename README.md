# wp-arteduca

Código do site **Bebelume ArtEduca** — plataforma WordPress de conteúdo educativo
por assinatura (mensal/anual), com gerenciamento de associados e emissão de notas
fiscais eletrônicas (NFS-e).

> Este repositório contém **apenas código**. Mídia (`uploads`), banco de dados, logs
> e arquivos com segredos **não** são versionados (ver `.gitignore`).

## Stack

- **WordPress** + tema ativo `bebelume` (`wp-content/themes/bebelume`)
- **Paid Memberships Pro (PMPro)** — assinaturas e cobranças via **Stripe**
- Plugins próprios em `wp-content/plugins/bebelume-*`:
  - `bebelume-nfse` — emissão e gestão de NFS-e
  - `bebelume-pmpro-profile` — checkout, perfil, bloqueio de dados fiscais
  - `bebelume-users` — criação de contas
  - demais: vídeo, cabeçalhos, pixel, popups, roles, delay de assinatura, etc.
- `mu-plugins/` — pequenos ajustes globais

## Estrutura

| Caminho | Conteúdo |
|---|---|
| `wp-content/plugins/bebelume-*` | Código próprio da Bebelume |
| `wp-content/themes/bebelume` | Tema ativo (assets + blocos compilados em `build/`) |
| `wp-content/mu-plugins` | Must-use plugins |

## Como subir um ambiente novo

1. Instale o WordPress e rode `wp language core install pt_BR`
   (traduções ficam fora do repositório).
2. Gere o `wp-config.php` a partir do `wp-config-sample.php` e preencha
   credenciais do banco e as salts.
3. Defina em `wp-config.php` (nunca commitar) as constantes usadas pelo código:
   - `BBL_GOOGLE_CLIENT_SECRET` — client secret do OAuth de "Entrar com Google"
     (lido por `bebelume-pmpro-profile`; ausente → login Google falha de forma segura).
4. Restaure `uploads/` e o conteúdo a partir de um backup de produção.
5. O build do tema (`build/`) já vai versionado; `node_modules` só é necessário
   para recompilar blocos.

## Notas

- **Segredos nunca entram no repositório.** O GitHub (secret scanning) bloqueia
  pushes que contenham credenciais.
- O `.git` interno do tema `bebelume` foi removido do docroot para permitir o
  versionamento único; o histórico original está preservado fora do servidor.
