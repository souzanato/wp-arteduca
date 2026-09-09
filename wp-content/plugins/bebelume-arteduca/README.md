# Bebelume ArtEduca - Plugin WordPress

Plugin de blocos Gutenberg personalizados para conteúdo educacional.

## 📋 Descrição

O **Bebelume ArtEduca** é um plugin WordPress que adiciona 5 blocos Gutenberg personalizados para criar conteúdo educacional interativo e visualmente atraente.

## 🎨 Blocos Incluídos

### 1. Título ArtEduca
Título decorativo com barra colorida superior e fundo customizável.

**Características:**
- Barra colorida fixa com 5 cores (paleta Bebelume)
- Fundo do título customizável (padrão: preto)
- Texto editável em caixa alta
- Ideal para seções de destaque

### 2. Accordion ArtEduca - Tipo 1
Accordion expansível de nível principal com cabeçalho colorido e ícone animado.

**Características:**
- Cabeçalho com cor customizável
- Paleta rápida: Azul (#2A4582), Roxo (#5C4F93), Vermelho (#D75F4D), Amarelo (#EAB35E)
- Ícone triangular que rotaciona ao abrir/fechar
- **Campo para inserir ícone HTML customizado** (ex: webfonts como Font Awesome)
- Título com formatação rica (negrito, itálico, sublinhado, tachado, link)
- Aceita qualquer bloco WordPress dentro
- Apenas um accordion aberto por vez (comportamento padrão Bootstrap)

### 3. Accordion ArtEduca - Tipo 2
Accordion secundário com botão arredondado estilo "pill".

**Características:**
- Botão arredondado com cor customizável (padrão: cinza claro)
- **Campo para inserir ícone HTML customizado** (ex: webfonts como Font Awesome)
- Título com formatação rica (negrito, itálico, sublinhado, tachado, link)
- Aceita qualquer bloco WordPress dentro
- Pode ser usado sozinho ou dentro do Accordion Tipo 1
- Apenas um accordion aberto por vez

### 4. Bebelume Icon Box
Caixa com ícone Lucide e texto lado a lado.

**Características:**
- Seletor com 45+ ícones Lucide prontos para usar
- Editor de rich text para o conteúdo
- Layout responsivo com ícone sempre centralizado
- Ícone em círculo colorido (cor padrão: #2A4582 - azul Bebelume)
- Perfeito para:
  - Listas de recursos e benefícios
  - Instruções passo a passo
  - Destaques de conteúdo
  - Pontos importantes
  
**Ícones disponíveis incluem:**
heart, star, lightbulb, baby, book, palette, film, video, music, pencil, scissors, gamepad, puzzle, camera, users, info, check, sparkles, trophy, rainbow e muitos mais!

### 5. Bebelume Doc Download
Bloco para disponibilizar documentos para download com visual profissional.

**Características:**
- Upload de documentos via biblioteca de mídia do WordPress
- Título e descrição editáveis com rich text
- Preview do arquivo com extensão, nome e tamanho
- Badge visual da extensão (PDF, DOCX, XLS, ZIP, TXT)
- Botão de download estilizado com ícone
- Cores customizáveis (fundo do card e botão de download)
- Design responsivo com efeitos hover
- Perfeito para:
  - Apostilas e materiais didáticos
  - Documentos administrativos
  - Listas de exercícios
  - Materiais complementares
  - Informes e comunicados

**Tipos de arquivo suportados:**
PDF, Word (DOC/DOCX), Excel (XLS/XLSX), TXT, ZIP

## 🚀 Instalação

### Via WordPress Admin
1. Faça upload do arquivo zip em **Plugins > Adicionar Novo > Enviar Plugin**
2. Ative o plugin

### Via FTP
1. Descompacte o arquivo zip
2. Faça upload da pasta `bebelume-arteduca` para `/wp-content/plugins/`
3. Ative o plugin no painel do WordPress

## 🔧 Desenvolvimento

### Pré-requisitos
- Node.js 14+
- npm ou yarn
- WordPress 6.0+
- PHP 7.4+

### Build do Plugin

```bash
# Instalar dependências
npm install

# Build para produção
npm run build

# Desenvolvimento (watch mode)
npm start
```

## 📦 Estrutura de Arquivos

```
bebelume-arteduca/
├── bebelume-arteduca.php       # Arquivo principal do plugin
├── package.json                 # Dependências Node
├── assets/
│   ├── css/
│   │   └── frontend.css         # Estilos do frontend
│   └── js/
│       └── frontend.js          # JavaScript dos accordions
├── blocks/                      # Blocos compilados
│   ├── titulo-arteduca/
│   │   ├── block.json
│   │   ├── block.js
│   │   └── style.css
│   ├── accordion-tipo1/
│   │   ├── block.json
│   │   ├── block.js
│   │   └── style.css
│   ├── accordion-tipo2/
│   │   ├── block.json
│   │   ├── block.js
│   │   └── style.css
│   ├── icon-box/
│   │   ├── block.json
│   │   ├── block.js
│   │   └── style.css
│   └── documento/
│       ├── block.json
│       ├── block.js
│       └── style.css
├── README.md
├── CHANGELOG.md
└── DOC-BLOCO-DOCUMENTO.md      # Documentação do bloco Doc Download
```

## 🎯 Como Usar

### No Editor Gutenberg

1. Clique no botão **+** para adicionar um novo bloco
2. Procure por "Bebelume ArtEduca" ou encontre os blocos na categoria **Bebelume ArtEduca**
3. Selecione o bloco desejado
4. Personalize cores e conteúdo através do painel lateral (Inspector Controls)

### Usando Ícones HTML (Webfonts)

Ambos os accordions suportam ícones HTML customizados. Para usar:

1. Adicione um bloco Accordion (Tipo 1 ou Tipo 2)
2. No painel lateral direito, encontre o campo **"Ícone HTML (opcional)"**
3. Cole o código HTML do seu ícone. Exemplos:
   - Font Awesome: `<i class="fas fa-book"></i>`
   - Material Icons: `<i class="material-icons">menu_book</i>`
   - Bootstrap Icons: `<i class="bi bi-book"></i>`
4. O ícone aparecerá antes do título no frontend

**Nota:** Certifique-se de que a biblioteca de ícones (Font Awesome, Material Icons, etc.) está carregada no seu tema WordPress.

### Exemplos de Uso

**Estrutura Típica:**
```
┌─ Título ArtEduca: "INTRODUÇÃO"
│
├─ Bebelume Icon Box: Dica importante
│
├─ Accordion Tipo 1: "ATIVIDADES PARA FAZER COM AS CRIANÇAS"
│  ├─ Accordion Tipo 2: "zero a 1 ano e 6 meses"
│  │  └─ Parágrafo com descrição da atividade
│  └─ Accordion Tipo 2: "1 ano e 7 meses a 3 anos"
│     └─ Parágrafo com descrição
│
├─ Bebelume Doc Download: "Apostila Completa 2025"
│  └─ PDF disponível para download
│
└─ Accordion Tipo 1: "MATERIAL DE ESTUDOS PARA PROFESSORES"
   ├─ Vídeo embutido
   ├─ Accordion Tipo 2: "Para Refletir"
   ├─ Accordion Tipo 2: "Saiba Mais"
   └─ Bebelume Doc Download: "Guia do Professor"
```

## 🎨 Paleta de Cores Padrão

- **Azul Escuro:** #2A4582
- **Roxo:** #5C4F93
- **Vermelho:** #D75F4D
- **Amarelo:** #EAB35E
- **Preto:** #000000 (padrão para Título ArtEduca)
- **Cinza Claro:** #D1D1D1 (padrão para Accordion Tipo 2)

## ⚙️ Requisitos Técnicos

- **WordPress:** 6.0 ou superior
- **PHP:** 7.4 ou superior
- **Bootstrap:** O tema deve incluir Bootstrap JS (para funcionalidade de collapse)

## 🔍 Recursos Principais

✅ **100% Gutenberg** - Blocos nativos do editor de blocos  
✅ **Responsivo** - Design adaptável para mobile, tablet e desktop  
✅ **Acessível** - Seguem padrões de acessibilidade (ARIA)  
✅ **Customizável** - Cores e conteúdo totalmente editáveis  
✅ **InnerBlocks** - Aceita qualquer bloco WordPress dentro dos accordions  
✅ **Bootstrap Ready** - Usa Bootstrap collapse (já incluído no tema)  
✅ **Performance** - CSS minificado e otimizado  
✅ **Upload de Mídia** - Integração nativa com biblioteca do WordPress  
✅ **Rich Text** - Formatação completa em todos os blocos  
✅ **Lucide Icons** - Ícones modernos via CDN

## 📝 Notas de Desenvolvimento

- O plugin utiliza Bootstrap para a funcionalidade de accordion (collapse)
- Certifique-se de que seu tema tenha Bootstrap JS carregado
- Os accordions funcionam com comportamento "um aberto por vez"
- IDs únicos são gerados automaticamente para cada accordion

## 🐛 Solução de Problemas

**Os accordions não abrem/fecham:**
- Verifique se o Bootstrap JS está carregado no tema
- Confira o console do navegador para erros JavaScript

**Cores não aparecem:**
- Execute `npm run build` para compilar os blocos
- Limpe o cache do navegador
- Verifique se o arquivo `frontend.css` está sendo carregado

## 📄 Licença

GPL v2 or later

## 👥 Autor

**Bebelume**
- Website: https://bebelume.com.br

## 📞 Suporte

Para suporte ou dúvidas, entre em contato através do site oficial.

---

**Versão:** 1.3.0  
**Última atualização:** Janeiro 2025
