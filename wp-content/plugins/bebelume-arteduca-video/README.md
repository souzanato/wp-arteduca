# Bebelume ArtEduca Video Plugin

Plugin WordPress para adicionar vídeos HTML5 com o player **Plyr.io** e customização de cores no editor Gutenberg.

## 📋 Características

- ✅ **Plyr.io 3.8.4** - Player moderno e acessível
- ✅ Bloco Gutenberg personalizado
- ✅ Player de vídeo HTML5 responsivo
- ✅ Campo para URL do vídeo
- ✅ **Seletor de Thumbnail da Galeria do WordPress**
  - 📷 Selecione imagens da biblioteca de mídia
  - 🖼️ Preview da thumbnail selecionada
  - ↔️ Troque ou remova a imagem facilmente
- ✅ Campo para descrição
- ✅ **18 Customizações de cores e estilos via painel:**
  - 🎨 Cores principais (cor primária, fundo do vídeo)
  - 🎮 Controles de vídeo (4 opções de cores)
  - 📊 Barra de progresso (4 opções incluindo altura)
  - 📋 Menus (3 opções)
  - 💬 Legendas (2 opções)
  - 📐 Dimensões e espaçamentos (3 opções)
- ✅ Interface em Português
- ✅ Totalmente responsivo
- ✅ **Funciona sem build** (sem necessidade de npm/webpack)
- ✅ Painéis organizados por categoria
- ✅ Preview em tempo real no editor

## 🎨 Tecnologias

- **Plyr.io 3.8.4** - Player HTML5 moderno
- WordPress 6.9+
- Blocos Gutenberg (JavaScript Vanilla - sem React/JSX)
- CSS Custom Properties para personalização

## 🚀 Instalação

### Opção 1: Upload via WordPress Admin

1. Faça o download de todos os arquivos do plugin
2. Crie uma pasta chamada `bebelume-arteduca-video`
3. Coloque todos os arquivos dentro desta pasta:
   - `bebelume-arteduca-video.php`
   - `block.js`
   - `frontend.js`
   - `editor.css`
   - `style.css`
   - `README.md`
4. Compacte a pasta em formato `.zip`
5. No WordPress, vá em **Plugins > Adicionar novo > Enviar plugin**
6. Faça o upload do arquivo `.zip`
7. Ative o plugin

### Opção 2: Upload via FTP

1. Faça o download de todos os arquivos
2. Envie a pasta `bebelume-arteduca-video` para `/wp-content/plugins/`
3. No WordPress, vá em **Plugins** e ative o plugin

## 📁 Estrutura de Arquivos

```
bebelume-arteduca-video/
├── bebelume-arteduca-video.php  (arquivo principal do plugin)
├── block.js                     (JavaScript do bloco Gutenberg)
├── frontend.js                  (JavaScript para inicializar o Plyr)
├── editor.css                   (estilos do editor)
├── style.css                    (estilos do frontend)
└── README.md                    (este arquivo)
```

## 🎯 Como Usar

1. Crie ou edite uma página/post no WordPress
2. No editor Gutenberg, clique no botão **+** para adicionar um bloco
3. Procure por "**Bebelume ArtEduca Video**"
4. Clique no bloco para adicioná-lo
5. No painel lateral direito:
   - **Adicione a URL do vídeo** (campo de texto)
   - **Selecione uma thumbnail** da galeria do WordPress:
     - Clique em "📷 Selecionar da Galeria"
     - Escolha uma imagem da galeria ou faça upload de uma nova
     - Você verá um preview da imagem selecionada
     - Pode trocar ou remover a imagem a qualquer momento
   - **Adicione uma descrição** (opcional)
6. Explore os 7 painéis de customização:
   - **📹 Configurações do Vídeo** - URL, thumbnail e descrição
   - **🎨 Cores Principais** - Cor primária e fundo
   - **🎮 Controles de Vídeo** - 4 opções de cores para os controles
   - **📊 Barra de Progresso** - Cores e altura da barra
   - **📋 Menus** - Customização dos menus de configuração
   - **💬 Legendas** - Cores das legendas
   - **📐 Dimensões e Espaçamentos** - Tamanhos e espaçamentos

7. Personalize conforme necessário - todas as alterações aparecem em tempo real no preview!

### Campos Disponíveis

#### 📹 Configurações do Vídeo
- **URL do Vídeo**: Link direto para o arquivo de vídeo (MP4, WebM, OGG)
- **Thumbnail/Poster do Vídeo**: Selecione uma imagem da galeria do WordPress ou faça upload - exibida antes de reproduzir o vídeo (JPG, PNG, WebP)
- **Descrição**: Texto descritivo que aparece abaixo do vídeo

#### 🎨 Cores Principais
- **Cor Principal**: Define a cor dos botões play, barra de progresso e elementos interativos (padrão: `#00b3ff`)
- **Fundo do Vídeo**: Cor de fundo do container do vídeo (útil para vídeos com canal alfa)

#### 🎮 Controles de Vídeo
- **Fundo dos Controles**: Cor de fundo da barra de controles (padrão: gradiente transparente)
- **Cor dos Ícones/Texto**: Cor dos ícones e texto dos controles (padrão: `#ffffff`)
- **Cor ao Passar o Mouse**: Cor dos controles quando o mouse passa por cima
- **Fundo ao Passar o Mouse**: Cor de fundo dos controles ao passar o mouse

#### 📊 Barra de Progresso
- **Cor de Preenchimento**: Cor da barra de progresso preenchida (padrão: usa cor principal)
- **Cor da Bolinha (Thumb)**: Cor do controle deslizante (padrão: `#ffffff`)
- **Cor do Trilho**: Cor de fundo da barra de progresso
- **Altura do Trilho**: Altura da barra de progresso (padrão: `5px`)

#### 📋 Menus
- **Fundo do Menu**: Cor de fundo dos menus de configuração
- **Cor do Texto do Menu**: Cor do texto nos menus
- **Arredondamento do Menu**: Border-radius dos menus (padrão: `4px`)

#### 💬 Legendas
- **Fundo das Legendas**: Cor de fundo das legendas
- **Cor do Texto das Legendas**: Cor do texto das legendas

#### 📐 Dimensões e Espaçamentos
- **Tamanho dos Ícones**: Tamanho dos ícones dos controles (padrão: `18px`)
- **Espaçamento entre Controles**: Espaço entre os botões de controle (padrão: `10px`)
- **Arredondamento dos Controles**: Border-radius dos botões (padrão: `3px`)

## 🎥 Formatos de Vídeo Suportados

O Plyr e HTML5 suportam:
- **MP4** (H.264) - **Recomendado** para melhor compatibilidade
- **WebM**
- **OGG**

**Recomendação**: Use MP4 com codec H.264 para garantir compatibilidade com todos os navegadores.

## 🔧 Requisitos

- WordPress 6.9 ou superior
- PHP 7.4 ou superior
- Navegador moderno com suporte a HTML5

## 📱 Responsividade

O plugin é totalmente responsivo e se adapta automaticamente a diferentes tamanhos de tela:
- Desktop
- Tablet
- Mobile

## 🌐 CDN

O plugin carrega o Plyr.io automaticamente via CDN (Cloudflare):
- CSS: `https://cdn.plyr.io/3.8.4/plyr.css`
- JS: `https://cdn.plyr.io/3.8.4/plyr.js`

## 🎨 Customização Avançada

O plugin usa **CSS Custom Properties** do Plyr para customização. Você pode adicionar CSS adicional no seu tema para mais personalizações:

```css
.bebelume-video-wrapper {
  --plyr-color-main: #e91e63;
  --plyr-video-controls-background: rgba(0, 0, 0, 0.8);
  --plyr-range-fill-background: #e91e63;
}
```

### CSS Custom Properties Disponíveis via Painel

O plugin expõe as seguintes propriedades do Plyr no painel do Gutenberg:

| Propriedade CSS | Campo no Painel | Descrição |
|----------------|-----------------|-----------|
| `--plyr-color-main` | Cor Principal | Cor primária da UI (botões, progresso) |
| `--plyr-video-background` | Fundo do Vídeo | Cor de fundo do vídeo |
| `--plyr-video-controls-background` | Fundo dos Controles | Fundo da barra de controles |
| `--plyr-video-control-color` | Cor dos Ícones/Texto | Cor dos controles |
| `--plyr-video-control-color-hover` | Cor ao Passar o Mouse | Cor dos controles no hover |
| `--plyr-video-control-background-hover` | Fundo ao Passar o Mouse | Fundo dos controles no hover |
| `--plyr-range-fill-background` | Cor de Preenchimento | Cor da barra de progresso |
| `--plyr-range-thumb-background` | Cor da Bolinha | Cor do thumb da barra |
| `--plyr-video-range-track-background` | Cor do Trilho | Fundo da barra de progresso |
| `--plyr-range-track-height` | Altura do Trilho | Altura da barra de progresso |
| `--plyr-menu-background` | Fundo do Menu | Cor de fundo dos menus |
| `--plyr-menu-color` | Cor do Texto do Menu | Cor do texto nos menus |
| `--plyr-menu-radius` | Arredondamento do Menu | Border-radius dos menus |
| `--plyr-captions-background` | Fundo das Legendas | Cor de fundo das legendas |
| `--plyr-captions-text-color` | Cor do Texto das Legendas | Cor do texto das legendas |
| `--plyr-control-icon-size` | Tamanho dos Ícones | Tamanho dos ícones |
| `--plyr-control-spacing` | Espaçamento entre Controles | Espaço entre controles |
| `--plyr-control-radius` | Arredondamento dos Controles | Border-radius dos botões |

### Outras Propriedades do Plyr

Para customizações ainda mais avançadas, consulte a [documentação completa do Plyr](https://github.com/sampotts/plyr#customizing-the-css) que lista todas as 60+ CSS Custom Properties disponíveis.

## 🔍 Features do Plyr

- Controles acessíveis e amigáveis
- Suporte a legendas (WebVTT)
- Picture-in-Picture
- Fullscreen
- Controle de velocidade
- Controle de volume
- Teclado shortcuts
- API JavaScript completa

## 🆘 Suporte

Para suporte ou questões:
- Entre em contato através do site Bebelume
- Abra uma issue no repositório do projeto

## 📝 Changelog

### Versão 1.0.0
- ✅ Integração com Plyr.io 3.8.4
- ✅ Bloco Gutenberg personalizado
- ✅ Campo para URL do vídeo
- ✅ **Seletor de Thumbnail integrado com a Galeria do WordPress**
  - Selecione imagens da biblioteca de mídia
  - Preview da thumbnail no painel
  - Botões para trocar ou remover imagem
- ✅ **18 opções de customização** organizadas em 7 painéis:
  - Cores principais (2 opções)
  - Controles de vídeo (4 opções)
  - Barra de progresso (4 opções)
  - Menus (3 opções)
  - Legendas (2 opções)
  - Dimensões e espaçamentos (3 opções)
- ✅ Suporte a descrição de vídeo
- ✅ Interface em Português
- ✅ Preview em tempo real no editor
- ✅ Totalmente responsivo
- ✅ Funciona sem build (JavaScript vanilla)

## 📄 Licença

GPL v2 or later

## 🙏 Créditos

- [Plyr.io](https://plyr.io/) - Amazing HTML5 media player by Sam Potts
- Plugin desenvolvido por Bebelume

## 📚 Links Úteis

- [Documentação do Plyr](https://github.com/sampotts/plyr)
- [Plyr.io Demo](https://plyr.io)
- [WordPress Gutenberg Handbook](https://developer.wordpress.org/block-editor/)

---

**Versão**: 1.0.0  
**Última atualização**: Janeiro 2026
