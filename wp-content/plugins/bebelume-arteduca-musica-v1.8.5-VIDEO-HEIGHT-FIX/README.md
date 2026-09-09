# 🎵 Bebelume ArtEduca Música

**Player de música estilo Spotify para WordPress com Gutenberg**

---

## 📋 Descrição

Plugin que transforma vídeos em um player de música profissional estilo Spotify, com suporte a playlists customizadas, controles completos e design moderno.

---

## ✨ Características

### **🎸 Player Estilo Spotify**
- Design dark mode profissional
- Player fixo no rodapé (estilo Spotify)
- Layout responsivo e moderno

### **🎵 Funcionalidades**
- ✅ Criar playlists personalizadas
- ✅ Adicionar músicas manualmente
- ✅ Importar vídeos de posts existentes
- ✅ Alternar entre modo vídeo e áudio
- ✅ Thumbnails/capas personalizadas
- ✅ Informações de título e autor

### **🎮 Controles Completos**
- ⏯️ Play/Pause
- ⏭️ Próxima música
- ⏮️ Música anterior
- 🔀 Modo aleatório (shuffle)
- 🔁 Repetir (all/one/off)
- 🔊 Controle de volume
- ⏱️ Barra de progresso clicável
- 📱 100% responsivo

### **🎬 Dois Modos de Exibição**
1. **Modo Vídeo**: Exibe o vídeo tocando
2. **Modo Áudio**: Mostra thumbnail grande + áudio apenas

---

## 🚀 Instalação

1. Faça upload da pasta `bebelume-arteduca-musica` para `/wp-content/plugins/`
2. Ative o plugin no WordPress
3. Adicione o bloco "Bebelume ArtEduca Música" em qualquer página/post

---

## 🎯 Como Usar

### **1. Criar Playlist**
1. Adicione o bloco "Bebelume ArtEduca Música"
2. Defina o título da playlist na sidebar

### **2. Adicionar Músicas**

#### **Opção A: Importar dos Posts**
1. Clique em "🎬 Importar dos Posts"
2. Selecione vídeos da biblioteca
3. Músicas são adicionadas automaticamente

#### **Opção B: Adicionar Manualmente**
1. Clique em "➕ Adicionar Manualmente"
2. Preencha:
   - URL do vídeo/áudio
   - Título da música
   - Autor/Artista
   - Thumbnail (opcional)
   - Toggle "Mostrar Vídeo"

### **3. Organizar Playlist**
- Use botões ↑↓ para reordenar
- Clique 🗑️ para remover
- Edite qualquer campo a qualquer momento

### **4. Publicar**
- Publique a página
- Player aparece com design Spotify
- Player fixo no rodapé para navegação contínua

---

## 🎨 Design

### **Cores Spotify**
- Verde: `#1db954`
- Fundo escuro: `#121212`
- Cards: `#181818`
- Hover: `#282828`
- Texto: `#ffffff` / `#b3b3b3`

### **Layout**
- Player principal: vídeo médio ou thumbnail grande
- Lista de tracks clicável
- Player fixo no rodapé com controles completos
- Animações suaves

---

## 📦 Dependências

- **Plyr.js 3.8.4** (carregado via CDN)
- **WordPress 5.0+**
- **PHP 7.0+**

---

## 🔧 Integração

### **Com Video Playlist Block**
O plugin se integra automaticamente com o plugin "Video Playlist Block":
- Importa posts de vídeo via REST API
- Endpoint: `/wp-json/vpb/v1/video-posts`
- Mantém título, URL e thumbnail

---

## 🎯 Casos de Uso

### **1. Canções de Ninar**
```
Playlist: Canções de Ninar para Bebês
- Dorme meu anjo
- Brilha brilha estrelinha
- Acalanto
Modo: Apenas áudio (thumbnails grandes)
```

### **2. Vídeos Educativos**
```
Playlist: Aprenda as Cores
- Vídeo Vermelho
- Vídeo Azul
- Vídeo Verde
Modo: Mostrar vídeo
```

### **3. Álbum Musical**
```
Playlist: Melhores Hits 2025
- 10 músicas
- Capa do álbum
- Modo shuffle
Modo: Apenas áudio
```

---

## 🎓 Tecnologias

- **React** (Gutenberg)
- **Plyr.js** (player de vídeo/áudio)
- **CSS Grid/Flexbox** (layout)
- **Vanilla JavaScript** (frontend)
- **WordPress REST API** (importação)

---

## 📱 Responsivo

- ✅ Desktop (1920px+)
- ✅ Tablet (768px - 1024px)
- ✅ Mobile (320px - 767px)
- ✅ Touch-friendly

---

## 🔒 Segurança

- Sanitização de inputs
- Escape de outputs
- Validação de URLs
- Permissões do WordPress

---

## 🐛 Troubleshooting

### **Vídeo não carrega**
- Verifique URL do vídeo
- Teste URL diretamente no navegador
- Confira formato (MP4, WebM, OGG)

### **Player não aparece**
- Limpe cache do navegador
- Verifique console (F12) para erros
- Certifique-se que Plyr carregou

### **Importação não funciona**
- Verifique se plugin "Video Playlist Block" está ativo
- Teste endpoint: `/wp-json/vpb/v1/video-posts`
- Confirme que existem posts de vídeo

---

## 📊 Performance

- Carregamento lazy de vídeos
- CSS otimizado (~10KB)
- JavaScript leve (~15KB)
- Apenas 1 vídeo carregado por vez

---

## 🎉 Recursos Futuros

- [ ] Equalizer visual
- [ ] Letras sincronizadas
- [ ] Playlists compartilháveis
- [ ] Download de playlists
- [ ] Temas de cores customizados
- [ ] Mini player flutuante

---

## 📝 Changelog

### v1.0.0 (2026-01-09)
- 🎉 Lançamento inicial
- ✅ Player estilo Spotify
- ✅ Importação de vídeos
- ✅ Shuffle e repeat
- ✅ Player fixo
- ✅ Design responsivo

---

## 👨‍💻 Desenvolvedor

**Bebelume**  
https://bebelume.com

---

## 📄 Licença

GPL v2 or later

---

## 🌟 Suporte

Para suporte, reporte bugs ou solicite features:
- Email: suporte@bebelume.com
- WordPress: https://bebelume.com/suporte

---

## 🙏 Créditos

- **Plyr.js**: https://plyr.io
- **Design inspirado em**: Spotify
- **Desenvolvido por**: Claude + Renato de Souza

---

# 🎵 Transforme vídeos em experiências musicais! 🎵
