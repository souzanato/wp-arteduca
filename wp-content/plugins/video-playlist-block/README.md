# Video Playlist Block - Plugin WordPress

Plugin para criar listas de reprodução de vídeos com slider horizontal e modal full-screen.

## 📋 Funcionalidades

- ✅ Bloco Gutenberg customizado
- ✅ Interface administrativa intuitiva
- ✅ Slider horizontal responsivo com navegação
- ✅ Modal full-screen com fundo escuro
- ✅ Suporte a YouTube, Vimeo, Dailymotion e vídeos HTML5
- ✅ Upload de thumbnails personalizados
- ✅ Totalmente responsivo

## 🚀 Instalação

### Método 1: Upload via WordPress (Recomendado)

1. **Prepare o plugin:**
   - Copie todos os arquivos para uma pasta chamada `video-playlist-block`
   - Crie um arquivo ZIP da pasta completa

2. **Faça upload no WordPress:**
   - Acesse `Plugins > Adicionar Novo`
   - Clique em `Fazer Upload do Plugin`
   - Selecione o arquivo ZIP
   - Clique em `Instalar Agora`
   - Ative o plugin

### Método 2: Upload via FTP

1. Copie a pasta `video-playlist-block` para `/wp-content/plugins/`
2. Acesse o WordPress e ative o plugin em `Plugins`

## 📁 Estrutura de Arquivos

```
video-playlist-block/
├── video-playlist-block.php  (arquivo principal)
└── assets/
    ├── css/
    │   ├── style.css         (estilos do frontend)
    │   └── editor.css        (estilos do editor)
    └── js/
        ├── frontend.js       (funcionalidades do slider e modal)
        └── editor.js         (bloco Gutenberg)
```

## 🎯 Como Usar

### 1. Adicionar o Bloco

1. Edite uma página ou post
2. Clique no botão `+` para adicionar um bloco
3. Procure por "Lista de Vídeos" ou "Video Playlist"
4. Adicione o bloco à página

### 2. Configurar a Lista

**No painel lateral direito (Configurações do bloco):**

1. **Nome da Lista**: Digite o título da sua playlist (ex: "INSPIRA FUNDO I")
2. **Descrição da Lista**: Adicione uma descrição opcional

### 3. Adicionar Vídeos

**Para cada vídeo:**

1. Clique em `+ Adicionar Vídeo`
2. No painel que se abre, configure:
   - **Título do Vídeo**: Nome que aparecerá no modal
   - **Thumbnail**: Clique em "Selecionar Thumbnail" e escolha uma imagem
   - **URL do Vídeo**: Cole a URL completa do vídeo
   - **Descrição**: Texto que aparecerá abaixo do vídeo no modal

### 4. Organizar Vídeos

- Use os botões `↑ Mover para cima` e `↓ Mover para baixo` para reordenar
- Clique em `Remover Vídeo` para deletar um vídeo

### 5. Publicar

Clique em `Publicar` ou `Atualizar` para salvar suas alterações.

## 🎬 Formatos de Vídeo Suportados

### YouTube
```
https://www.youtube.com/watch?v=VIDEO_ID
https://youtu.be/VIDEO_ID
```

### Vimeo
```
https://vimeo.com/VIDEO_ID
```

### Dailymotion
```
https://www.dailymotion.com/video/VIDEO_ID
```

### Vídeos Diretos
```
https://seusite.com/video.mp4
```

## 🎨 Personalização

### Alterar Cores

Edite o arquivo `assets/css/style.css`:

```css
/* Cor de fundo do modal */
.video-modal-backdrop {
    background: rgba(0, 0, 0, 0.95); /* Ajuste o último valor para transparência */
}

/* Cor da borda dos thumbnails */
.video-thumb {
    border: 3px solid #e0e0e0; /* Altere a cor aqui */
}
```

### Alterar Tamanho dos Thumbnails

No arquivo `assets/css/style.css`:

```css
.video-slide {
    width: 200px; /* Largura do card */
}

.video-thumb {
    width: 200px;  /* Largura */
    height: 150px; /* Altura */
}
```

### Ajustar Velocidade do Slider

No arquivo `assets/js/frontend.js`:

```javascript
const scrollAmount = 250; // Pixels para rolar (ajuste este valor)
```

## 📱 Responsividade

O plugin é totalmente responsivo e se adapta automaticamente a:
- Desktops (1920px+)
- Tablets (768px - 1024px)
- Smartphones (320px - 767px)

## 🔧 Solução de Problemas

### Vídeo não carrega no modal

**Problema:** O vídeo não aparece quando clico no thumbnail.

**Soluções:**
1. Verifique se a URL do vídeo está correta
2. Teste se o vídeo abre diretamente no navegador
3. Certifique-se de que o vídeo não está privado ou restrito

### Thumbnails não aparecem

**Problema:** As imagens dos thumbnails não carregam.

**Soluções:**
1. Verifique se as imagens foram realmente enviadas
2. Teste a URL da imagem diretamente no navegador
3. Verifique as permissões da pasta de uploads do WordPress

### Slider não funciona

**Problema:** Os botões de navegação não respondem.

**Soluções:**
1. Verifique se o JavaScript está carregando (F12 > Console)
2. Desative outros plugins para testar conflitos
3. Limpe o cache do navegador e do WordPress

### Modal não fecha

**Problema:** Não consigo fechar o modal de vídeo.

**Soluções:**
1. Clique no X no canto superior direito
2. Clique fora do vídeo (na área escura)
3. Pressione a tecla ESC
4. Recarregue a página se necessário

## 🆘 Suporte

Para problemas ou dúvidas:
1. Verifique a seção de Solução de Problemas acima
2. Verifique o console do navegador (F12) para erros JavaScript
3. Ative o modo de depuração do WordPress (WP_DEBUG)

## 📝 Changelog

### Versão 1.0.0
- Lançamento inicial
- Bloco Gutenberg customizado
- Slider horizontal responsivo
- Modal full-screen
- Suporte a múltiplas plataformas de vídeo

## 📄 Licença

GPL v2 or later

## 👨‍💻 Créditos

Desenvolvido com base nos requisitos do Canal Bebelume.
