# PMPro Email Diagnostic

🔍 **Ferramenta completa de diagnóstico e teste de emails do Paid Memberships Pro**

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)

## 📋 Descrição

Plugin WordPress que ajuda a diagnosticar e resolver problemas de envio de emails no **Paid Memberships Pro**. Testa diferentes tipos de emails, verifica configurações e fornece soluções para problemas comuns.

## ✨ Características

- ✅ **Teste de Emails** - Envie emails de teste com um clique
- 📊 **Estatísticas** - Veja taxa de sucesso dos envios
- 📝 **Histórico** - Registra todos os testes realizados
- ⚙️ **Verificação de Configurações** - Analisa setup do WordPress e PMPro
- 🔧 **Soluções Integradas** - Guias para resolver problemas comuns
- 🎨 **Interface Moderna** - Design clean e profissional

## 🚀 Instalação

### Método 1: Via WordPress Admin

1. Faça download do arquivo `.zip`
2. Vá em **Plugins → Adicionar Novo → Enviar Plugin**
3. Selecione o arquivo e clique em **Instalar Agora**
4. Ative o plugin

### Método 2: Via FTP

1. Extraia o arquivo `.zip`
2. Faça upload da pasta `pmpro-email-diagnostic` para `/wp-content/plugins/`
3. Ative o plugin no WordPress

### Método 3: Via WP-CLI

```bash
wp plugin install pmpro-email-diagnostic.zip --activate
```

## 📖 Como Usar

1. Após ativar, vá em **Ferramentas → PMPro Email Test**
2. Escolha o tipo de teste desejado
3. Informe o email de destino
4. Clique em **Enviar Email de Teste**
5. Verifique sua caixa de entrada (e SPAM)

## 🧪 Tipos de Teste

### WordPress wp_mail()
Testa a função básica de email do WordPress. Se falhar, o problema está na configuração do servidor.

### PMPro - Email de Checkout
Testa o email enviado após um checkout bem-sucedido.

### PMPro - Email de Admin
Testa emails enviados para administradores.

### PMPro - Email de Boas-vindas
Testa o email de boas-vindas enviado a novos membros.

## ⚙️ Configurações Verificadas

O plugin verifica automaticamente:

- ✅ Email padrão do WordPress
- ✅ Disponibilidade da função `mail()`
- ✅ Configuração de SMTP
- ✅ Email remetente do PMPro
- ✅ Nome remetente do PMPro
- ✅ Templates de email ativos

## 🔧 Soluções para Problemas Comuns

### Emails não estão sendo enviados?

#### 1. Instalar Plugin SMTP

A função `mail()` do PHP é bloqueada na maioria dos servidores. Instale um plugin SMTP:

- **WP Mail SMTP** (recomendado)
- **Easy WP SMTP**
- **Post SMTP Mailer**

#### 2. Configurar Email do PMPro

Vá em **Memberships → Settings → Email** e configure:
- Email remetente (use um email válido do seu domínio)
- Nome do remetente

#### 3. Verificar SPAM

Sempre verifique a pasta de spam/lixo eletrônico do email de destino.

#### 4. Usar Serviços Externos

Configure SMTP com:
- Gmail (500 emails/dia grátis)
- SendGrid (100 emails/dia grátis)
- Mailgun
- Amazon SES

## 📊 Estatísticas e Logs

O plugin mantém registro de todos os testes realizados:

- Total de testes
- Testes com sucesso
- Testes falhados
- Taxa de sucesso
- Histórico detalhado

## 🛠️ Requisitos

- WordPress 5.0 ou superior
- PHP 7.2 ou superior
- Paid Memberships Pro (opcional, mas recomendado)

## 🤝 Contribuindo

Contribuições são bem-vindas! Para contribuir:

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

## 📝 Changelog

### 1.0.0 - 2026-01-02
- 🎉 Lançamento inicial
- ✅ Teste de emails WP e PMPro
- 📊 Sistema de estatísticas
- 📝 Registro de logs
- 🎨 Interface admin moderna

## 📄 Licença

Este projeto está licenciado sob a GPL v2 ou posterior - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 👨‍💻 Autor

**Bebelume Team**
- Website: [https://bebelume.com.br](https://bebelume.com.br)
- Email: contato@bebelume.com.br

## 🙏 Créditos

- Desenvolvido para uso com [Paid Memberships Pro](https://www.paidmembershipspro.com/)
- Inspirado nas necessidades reais de debugging de emails

## 📧 Suporte

Para suporte e dúvidas:

1. Abra uma [issue no GitHub](https://github.com/seu-usuario/pmpro-email-diagnostic/issues)
2. Envie email para: contato@bebelume.com.br
3. Consulte a [documentação](https://github.com/seu-usuario/pmpro-email-diagnostic/wiki)

## ⭐ Se este plugin foi útil

Se este plugin ajudou você, considere:
- Dar uma ⭐ no GitHub
- Compartilhar com outros desenvolvedores
- Contribuir com melhorias

---

**Desenvolvido com ❤️ pela equipe Bebelume**
