# Bebelume Profile - Plugin WordPress

Plugin de perfil leve e colorido para WordPress, com design limpo e amigável, integrado com Paid Memberships Pro.

## 🆕 Nova Funcionalidade: Tela de Registro Separada

### Visão Geral
Agora o plugin inclui uma tela de registro dedicada e separada do processo de associação/pagamento. Esta funcionalidade permite que usuários criem contas gratuitas antes de escolher um plano de assinatura.

**🔒 SEGURANÇA:** A rota é criada **diretamente no código** usando Rewrite Rules do WordPress. Não é uma página que pode ser deletada acidentalmente por usuários!

### Endereços Disponíveis

O plugin registra **automaticamente** duas rotas:

- **`/auth/registro/`** ← Recomendado (padrão usado nos links)
- **`/registro/`** ← Alternativa

Ambas funcionam! Escolha a que preferir.

### Características da Tela de Registro

#### Design e Estética
- **Estilo consistente**: Mantém a mesma paleta de cores e tipografia do plugin
- **Gradiente vibrante**: Cabeçalho com gradiente rosa (#E73665) para ciano (#00BCD4)
- **Ícones Font Awesome**: Interface intuitiva com ícones descritivos
- **Responsivo**: Adapta-se perfeitamente a dispositivos móveis

#### Funcionalidades
- **Campos do formulário**:
  - Nome e Sobrenome
  - Email (com validação)
  - Senha (com opção de visualizar/ocultar)
  - Confirmação de senha
  - Checkbox de aceitação de termos

- **Validações incluídas**:
  - Verificação de email único
  - Senha mínima de 8 caracteres
  - Confirmação de senha idêntica
  - Campos obrigatórios

- **Username automático**: Gera automaticamente um username único no formato `user-{hash8digitos}`

- **Login automático**: Após o registro, o usuário é automaticamente logado

- **Badges de confiança**: Exibe selos de segurança e rapidez no cadastro

- **Seção de benefícios**: Mostra 4 vantagens de criar uma conta

### Como Usar

#### Configuração Automática ✨

**NÃO É NECESSÁRIO CRIAR NENHUMA PÁGINA!**

O plugin cria automaticamente as rotas quando ativado:

1. **Ative o plugin** no WordPress
2. **Acesse:** `seusite.com/auth/registro/` ← Pronto!
3. **Ou acesse:** `seusite.com/registro/` ← Também funciona!

#### Após Ativar o Plugin:

- Visite **Configurações → Links Permanentes** 
- Clique em **"Salvar Alterações"** (para recriar as regras de URL)
- Pronto! A rota `/auth/registro/` está ativa

#### Links Automáticos

O plugin adiciona automaticamente links para `/auth/registro/` em:

- **Página de Checkout**: Link "Criar Conta Gratuita" no rodapé (apenas para visitantes não logados)
- **Página de Níveis**: CTA destacado antes da mensagem final (apenas para visitantes não logados)
- **Página da Conta**: Card "Indique um Amigo" na sidebar (para usuários logados compartilharem)

#### Shortcode (Opcional)

Embora não seja necessário, você ainda pode usar o shortcode em qualquer página:
```
[bebelume_register]
```

#### Personalizar URL

Se quiser usar apenas `/registro/` ao invés de `/auth/registro/`:

Edite os arquivos:
- `templates/checkout.php`
- `templates/levels.php`
- `templates/account.php`

E troque:
```php
home_url('/auth/registro/')
```
Por:
```php
home_url('/registro/')
```

#### Personalizar o Redirecionamento

Por padrão, após o registro, o usuário é redirecionado para a página de níveis. Você pode alterar isso usando o filtro:

```php
add_filter('bbl_register_redirect_url', function($url) {
    return home_url('/bem-vindo'); // Redireciona para página customizada
});
```

### Estrutura de Arquivos

```
bebelume-profile/
├── templates/
│   ├── register.php          ← Nova tela de registro
│   ├── account.php            (modificado - inclui card de indicação)
│   ├── checkout.php           (modificado - inclui link de registro)
│   ├── levels.php             (modificado - inclui CTA de registro)
│   └── ...
├── assets/
│   └── css/
│       └── main.css           (modificado - novos estilos para registro)
└── bebelume-profile.php       (modificado - adiciona shortcode)
```

### Estilo e Paleta de Cores

O template de registro usa as variáveis CSS do plugin:

```css
--bbl-pink: #E73665
--bbl-cyan: #00BCD4
--bbl-yellow: #FFD54F
--bbl-green: #66BB6A
--bbl-white: #FFFFFF
--bbl-bg-light: #F5F9FC
--bbl-text-primary: #2C3E50
```

### Requisitos

- WordPress 6.0+
- PHP 7.4+
- Paid Memberships Pro (plugin)

### Instalação

1. Faça upload da pasta `bebelume-profile` para `/wp-content/plugins/`
2. Ative o plugin no painel do WordPress
3. Crie a página de registro com o shortcode `[bebelume_register]`

### Customização

#### Modificar Campos do Formulário

Os campos estão no arquivo `templates/register.php`. Você pode adicionar ou remover campos conforme necessário.

#### Alterar Estilos

Os estilos estão embutidos no template `register.php` e também no arquivo `assets/css/main.css` para os links nas outras páginas.

#### Modificar Mensagens

Todas as mensagens de erro e sucesso estão no arquivo `templates/register.php` e podem ser facilmente editadas ou traduzidas.

### Segurança

- Todos os dados são sanitizados antes de serem salvos
- Validação de email duplicado
- Username único gerado automaticamente
- Senhas hashadas pelo WordPress
- Proteção CSRF (verifica nonce)
- **🔒 Rota protegida**: A página de registro não pode ser deletada, pois é criada via código (não é uma página do WordPress)

### Fluxo de Registro

1. Usuário acessa `/auth/registro/` ou `/registro/`
2. Preenche o formulário
3. Sistema valida os dados
4. Cria o usuário com username automático
5. Faz login automático
6. Redireciona para página de níveis (ou personalizada)

### Vantagens da Implementação via Código

✅ **Não pode ser deletada** por usuários ou administradores  
✅ **Sempre disponível** após ativação do plugin  
✅ **URL limpa e profissional** (`/auth/registro/`)  
✅ **Sem dependência** de páginas do WordPress  
✅ **Mais rápida** - não consulta banco de dados para encontrar a página  
✅ **Fácil manutenção** - tudo no código do plugin

### Troubleshooting

**Erro 404 na página de registro?**
- Vá em **Configurações → Links Permanentes**
- Clique em **"Salvar Alterações"**
- Isso recria as regras de URL

**Links não funcionam?**
- Verifique se o plugin está ativado
- Desative e reative o plugin (isso recria as regras)

### Suporte

Para questões ou suporte, entre em contato através do email configurado no template de account.php.

### Changelog

#### Versão 1.2.0
- 🔒 **BREAKING:** Rota agora é criada no código (não precisa mais criar página)
- ✨ Suporte para `/auth/registro/` e `/registro/`
- 🛡️ Proteção contra deleção acidental
- ⚡ Performance melhorada (não consulta banco de dados)
- 📝 README atualizado com instruções simplificadas

#### Versão 1.1.0
- ✨ Adicionada tela de registro separada
- ✨ Shortcode `[bebelume_register]` 
- ✨ Links automáticos nas páginas de checkout, níveis e conta
- ✨ Card de indicação na página da conta
- 🎨 Novos estilos CSS para os componentes de registro
- 📱 Interface totalmente responsiva

#### Versão 1.0.0
- Lançamento inicial do plugin

### Licença

GPL v2 ou posterior

### Autor

Bebelume Team

---

**Dica**: Personalize os links dos Termos de Uso e Política de Privacidade no arquivo `templates/register.php` para apontar para as páginas corretas do seu site.
