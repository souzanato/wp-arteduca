# Bebelume Users

Criação de usuários em lote para o ecossistema Bebelume, com senha gerada automaticamente,
envio pelo SMTP do Gmail e atribuição de nível PMPro.

Versão 1.1.1 · Requer WordPress 6.0+ e PHP 7.4+

---

## O fluxo

A criação acontece em dois momentos separados, de propósito:

**1. Adicionar** — você cola a lista e ela fica salva como rascunho. Nenhum usuário é criado,
nenhuma senha é gerada, nenhum e-mail sai. Dá para editar e remover linhas à vontade.

**2. Criar** — ação separada, com confirmação. É aqui que a senha é gerada, a conta criada,
o nível atribuído e o e-mail enviado. Depois disso não tem como voltar atrás no e-mail.

---

## Instalação

1. Envie o ZIP em **Plugins → Adicionar Novo → Enviar Plugin**
2. Ative
3. Vá em **Bebelume Users → Configurações → Diagnóstico** e confira o ambiente
4. Configure o e-mail na aba **E-mail**

---

## Configurar o Gmail

O Gmail não aceita mais a senha normal da conta. É preciso uma **Senha de app**:

1. A conta precisa ter **verificação em duas etapas** ativada
2. Acesse <https://myaccount.google.com/apppasswords>
3. Gere uma senha de app e cole no campo (os espaços são removidos automaticamente)

| Campo | Valor |
|---|---|
| Servidor | `smtp.gmail.com` |
| Porta | `587` |
| Criptografia | TLS |
| Usuário | o e-mail completo da conta |
| Senha | a senha de app de 16 caracteres |

**Limites de envio do Google:** cerca de 500 mensagens por dia em conta gratuita,
2.000 por dia no Workspace. Lotes maiores precisam ser divididos entre dias.

### Guardar a senha fora do banco

A senha fica cifrada no banco usando as salts do `wp-config.php`. Para não guardá-la
lá de jeito nenhum, defina no `wp-config.php`:

```php
define( 'BBL_USERS_GMAIL_PASS', 'suasenhadeapp' );
```

A constante tem prioridade sobre o campo da tela.

### Conflito com outros plugins de SMTP

Se o site já usa WP Mail SMTP ou similar, **desmarque** a opção
"Usar esta conta para todos os e-mails do site". Assim só as mensagens deste plugin
passam pelo Gmail e o resto continua no caminho de antes.

---

## Formatos aceitos na lista

Uma pessoa por linha. Todos estes funcionam:

```
Maria Souza, maria@exemplo.com
João Pereira; joao@exemplo.com
Ana Lima	ana@exemplo.com
Carlos Dias <carlos@exemplo.com>
semnome@exemplo.com
```

A tabulação permite colar direto de uma planilha.

---

## Senha gerada

Configurável em **Configurações → Senha**: comprimento (6 a 64), maiúsculas, minúsculas,
números, símbolos e o conjunto de símbolos permitidos.

A senha sempre inclui ao menos um caractere de cada tipo marcado, é gerada com
`random_int()` (CSPRNG) e embaralhada com Fisher-Yates.

A opção "não usar caracteres parecidos entre si" remove `0 O 1 l I |` e aspas, que são
a maior fonte de erro quando a pessoa copia a senha da mensagem.

---

## Detalhes do ambiente Bebelume

### wp_users é uma VIEW no canal e no arteduca

Nos satélites, `wp_users` e `wp_usermeta` são VIEWs apontando para o banco do hub.
Consequências práticas:

- O usuário criado no canal **aparece nos três sites**. É uma conta só.
- O nível PMPro, ao contrário, é **local**: fica só no site onde o lote rodou.
- O INSERT depende dos **GRANTs cruzados** no MySQL. Sem eles, a criação falha.

Use **Configurações → Diagnóstico → Teste de criação** antes de rodar um lote grande.
Ele cria um usuário descartável e o remove, provando que o INSERT atravessa a VIEW.

### Quando o e-mail já tem conta

Cenário comum: a pessoa já assina o canal e você está adicionando ela no arteduca.
Nesse caso o plugin **não cria conta nova e não troca a senha**. Ele apenas atribui
o nível à conta existente e, se você quiser, envia uma mensagem avisando do novo acesso
(sem senha, porque a senha antiga continua valendo).

Essas linhas aparecem com o selo **Já existia**.

### Login gerado

Segue o padrão `user-a7f3c2d9`, o mesmo do `bebelume-pmpro-profile`. A pessoa entra
com o e-mail, não com esse login.

---

## Processamento em blocos

O lote é processado em blocos de 5 linhas por requisição, para não estourar o timeout
do PHP. A barra de progresso mostra o andamento e cada linha é marcada individualmente.

Se a conexão cair no meio, as linhas já processadas continuam marcadas como criadas.
Basta recarregar a página e rodar de novo: só o que ficou em rascunho é retomado.

Para mudar o tamanho do bloco:

```php
add_filter( 'bbl_users_chunk_size', function () { return 10; } );
```

---

## Personalizar as mensagens

Em **Configurações → E-mail** você edita assunto e corpo de duas mensagens distintas:

**Mensagem de boas-vindas** — vai para quem teve a conta criada agora. É a única que leva a senha.

**Mensagem para quem já tem conta** — vai para quem já existia na base e só recebeu o nível.
Pode ser desligada, e aí essas pessoas recebem o acesso sem nenhum e-mail.

O corpo usa o editor visual do WordPress, com alternância para HTML na aba "Texto".
Cada mensagem tem:

- **Ver prévia** — monta a mensagem com dados fictícios, exatamente como ela vai chegar
- **Restaurar texto padrão** — devolve o texto original

### Marcadores

Clique em qualquer marcador para inseri-lo onde o cursor estava, seja no assunto ou no corpo.

| Marcador | Conteúdo |
|---|---|
| `{nome}` | Nome informado na lista |
| `{email}` | E-mail da pessoa |
| `{usuario}` | Login gerado (`user-a7f3c2d9`) |
| `{senha}` | Senha gerada — só na mensagem de conta nova |
| `{site}` | Nome do site |
| `{url_site}` | Endereço do site |
| `{url_login}` | Página de login (usa a do PMPro quando existe) |
| `{nivel}` | Nome do nível PMPro atribuído |

Marcador desconhecido não é substituído: sai no e-mail do jeito que foi escrito. Vale conferir
a grafia na prévia. Se você usar `{senha}` na mensagem de quem já tem conta, a prévia avisa —
o marcador sairia vazio, porque a senha antiga dessa pessoa é preservada.

---

## Permissões

- **Adicionar** e **Criar**: capacidade `create_users`
- **Configurações**: capacidade `manage_options`

Todos os endpoints AJAX verificam nonce e capacidade.

---

## Estrutura

```
bebelume-users/
├── bebelume-users.php
├── includes/
│   ├── class-bbl-users-crypto.php        # cifra a senha de app
│   ├── class-bbl-users-db.php            # tabela de rascunho
│   ├── class-bbl-users-password.php      # gerador de senha
│   ├── class-bbl-users-mailer.php        # SMTP do Gmail e mensagens
│   ├── class-bbl-users-creator.php       # cria usuário, nível e e-mail
│   ├── class-bbl-users-diagnostics.php   # checagens de ambiente
│   ├── class-bbl-users-admin.php         # menus e telas
│   └── class-bbl-users-ajax.php          # endpoints
├── templates/
│   ├── admin-adicionar.php
│   ├── admin-criar.php
│   └── admin-config.php
└── assets/
    ├── css/admin.css
    └── js/admin.js
```

A tabela criada é `{prefixo}_bebelume_users_draft`, local ao site.
