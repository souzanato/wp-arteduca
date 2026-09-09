# 🎨 Bebelume Profile

> Plugin WordPress leve e colorido para gerenciamento de perfil de usuário com design clean e amigável.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0+-green)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple)
![License](https://img.shields.io/badge/license-GPL--2.0-orange)

## ✨ Características

### 🎨 Design Limpo e Claro
- Fundo branco e claro
- Cores vibrantes inspiradas no Bebelume
- Tipografia amigável (Nunito + Quicksand)
- Layout simples e direto
- Sem elementos complexos ou dark mode

### 📝 Formulário Simples
- Campos básicos de perfil
- Validação em tempo real
- Contador de caracteres
- Salvamento via AJAX
- Notificações toast

### 📱 Responsivo
- Mobile-first
- Layout adaptativo
- Funciona perfeitamente em todos os dispositivos

### ⚡ Performance
- CSS e JS minificados
- Carregamento rápido
- Sem dependências pesadas
- Código otimizado

## 📦 Instalação

### Método 1: Upload via WordPress

1. Baixe o arquivo ZIP do plugin
2. Vá em **WordPress Admin → Plugins → Adicionar Novo → Upload**
3. Escolha o arquivo ZIP
4. Clique em **Instalar Agora** e depois **Ativar**

### Método 2: FTP

1. Faça upload da pasta `bebelume-profile` para `/wp-content/plugins/`
2. Vá em **WordPress Admin → Plugins**
3. Ative o plugin **Bebelume Profile**

## 🎮 Uso

Após ativar o plugin, você verá um novo item no menu lateral:

**Meu Perfil** 👤

Clique nele para acessar a página de perfil.

### Campos Disponíveis

- **Nome** - Seu primeiro nome
- **Sobrenome** - Seu sobrenome
- **E-mail** - E-mail (somente leitura, verificado)
- **Website** - Seu site ou portfolio
- **Sobre você** - Biografia (até 500 caracteres)

### Funcionalidades

- ✅ Salvamento automático via AJAX
- ✅ Notificações de sucesso/erro
- ✅ Contador de caracteres na biografia
- ✅ Validação de campos
- ✅ Avatar do Gravatar

## ⚙️ Customização

### Cores

Para alterar as cores, edite as variáveis CSS em `/assets/css/main.css`:

```css
:root {
    --bbl-pink: #E73665;
    --bbl-cyan: #00BCD4;
    --bbl-yellow: #FFD54F;
    --bbl-green: #66BB6A;
    /* ... */
}
```

### Hooks WordPress

#### Filtros

```php
// Adicionar campos customizados
add_filter('bbl_profile_fields', function($fields) {
    $fields['phone'] = array(
        'label' => 'Telefone',
        'type' => 'tel',
        'placeholder' => '(00) 00000-0000'
    );
    return $fields;
});
```

#### Actions

```php
// Executar após salvar perfil
add_action('bbl_profile_saved', function($user_id, $data) {
    // Sua lógica aqui
    error_log('Perfil salvo: ' . $user_id);
}, 10, 2);
```

## 🎨 Estrutura do Código

```
bebelume-profile/
├── bebelume-profile.php     # Arquivo principal
├── assets/
│   ├── css/
│   │   └── main.css         # Estilos
│   ├── js/
│   │   └── main.js          # JavaScript
│   └── images/              # Imagens (futuro)
├── templates/
│   └── profile-page.php     # Template da página
├── includes/                # Classes PHP (futuro)
├── README.md
└── LICENSE.txt
```

## 🔒 Segurança

- ✅ Sanitização de inputs
- ✅ Verificação de nonce
- ✅ Escape de outputs
- ✅ Permissões verificadas
- ✅ Proteção contra SQL injection
- ✅ Proteção contra XSS

## 🚀 Performance

- ⚡ CSS: ~8KB
- ⚡ JS: ~3KB
- ⚡ Carregamento condicional
- ⚡ Sem bibliotecas externas pesadas

## 📱 Compatibilidade

- ✅ WordPress 6.0+
- ✅ PHP 7.4+
- ✅ Todos os navegadores modernos
- ✅ Mobile, Tablet, Desktop

## 🐛 Troubleshooting

### O plugin não aparece no menu
- Verifique se o plugin está ativado
- Verifique permissões do usuário

### Perfil não salva
- Verifique se o JavaScript está carregando
- Verifique o console do navegador
- Verifique permissões de escrita

### Estilos não aparecem
- Limpe o cache do navegador
- Limpe o cache do WordPress
- Verifique se o CSS está carregando

## 📝 Changelog

### 1.0.0 (2025-12-26)
- 🎉 Lançamento inicial
- ✨ Interface limpa e colorida
- 📝 Formulário básico de perfil
- 📱 Design responsivo
- ⚡ Performance otimizada

## 🤝 Contribuindo

Contribuições são bem-vindas!

1. Fork o projeto
2. Crie uma branch (`git checkout -b feature/NovaFuncionalidade`)
3. Commit suas mudanças (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/NovaFuncionalidade`)
5. Abra um Pull Request

## 📄 Licença

GPL v2 or later - Você é livre para usar, modificar e distribuir este plugin.

## 🙏 Créditos

- Design: Inspirado no Bebelume
- Fontes: Google Fonts (Nunito, Quicksand)
- Cores: Paleta vibrante e amigável

## 📞 Suporte

- 🐛 Issues: GitHub Issues
- 💬 Discussões: GitHub Discussions
- 📧 Email: seu-email@example.com

---

**Desenvolvido com ❤️ para WordPress**
