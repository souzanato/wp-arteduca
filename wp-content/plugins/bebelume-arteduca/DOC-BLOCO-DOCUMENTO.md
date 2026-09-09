# Bebelume Doc Download - Bloco de Documento

## 📄 Descrição

O **Bebelume Doc Download** é um bloco Gutenberg que permite disponibilizar documentos para download de forma visual e organizada, com título, descrição e informações sobre o arquivo.

## 🎨 Características

### Visual e Design
- Card com fundo customizável (padrão: cinza claro #F3F0EB)
- Ícone de documento grande centralizado (Lucide icons)
- Borda tracejada com efeito hover
- Design responsivo para mobile, tablet e desktop
- Botão de download estilizado com ícone

### Funcionalidades no Editor
- **Título editável** com rich text (negrito, itálico, sublinhado)
- **Descrição editável** com rich text completo (incluindo links)
- **Upload de documentos** via biblioteca de mídia do WordPress
- **Preview do arquivo** após upload com:
  - Badge da extensão do arquivo (PDF, DOCX, etc)
  - Nome do arquivo
  - Tamanho do arquivo (KB ou MB)
- **Botões de ação**: Trocar ou Remover documento
- **Customização de cores**:
  - Cor de fundo do card
  - Cor do botão de download

### Funcionalidades no Frontend
- Exibe todas as informações do documento
- Botão de download funcional
- Atributo `download` para forçar download do arquivo
- Efeitos hover no botão
- Ícones Lucide (file-text e download)

## 📦 Tipos de Arquivo Suportados

- **PDF**: `.pdf`
- **Word**: `.doc`, `.docx`
- **Excel**: `.xls`, `.xlsx`
- **Texto**: `.txt`
- **Compactados**: `.zip`

## 🎯 Como Usar

### No Editor Gutenberg

1. Clique no botão **+** para adicionar um novo bloco
2. Procure por "Bebelume Doc Download" ou encontre na categoria **Bebelume ArtEduca**
3. Edite o título e descrição clicando diretamente no texto
4. Clique em **"Fazer Upload do Documento"**
5. Selecione o arquivo da biblioteca de mídia ou faça upload de um novo
6. O bloco mostrará preview com nome, extensão e tamanho do arquivo

### Personalizando Cores

1. Com o bloco selecionado, abra o painel lateral direito
2. Em **"Configurações de Cores"**:
   - **Cor de Fundo**: Cor do card/container
   - **Cor do Botão**: Cor do botão de download
3. Use a paleta de cores Bebelume ou escolha cor customizada

### Exemplo de Uso

```
┌─────────────────────────────────┐
│  [ícone de documento]           │
│                                 │
│  Apostila Completa 2025         │
│  Material didático com todas    │
│  as atividades do ano letivo    │
│                                 │
│  [PDF] apostila-2025.pdf (2.5MB)│
│                                 │
│  [ ⬇ Baixar Documento ]         │
└─────────────────────────────────┘
```

## 🎨 Paleta de Cores Padrão

As mesmas cores da identidade Bebelume:

- **Azul Escuro:** #2A4582 (padrão do botão)
- **Roxo:** #5C4F93
- **Vermelho:** #D75F4D
- **Amarelo:** #EAB35E
- **Cinza Claro:** #F3F0EB (padrão do fundo)
- **Branco:** #FFFFFF

## 💡 Dicas de Uso

### Para Professores
- Upload de apostilas em PDF
- Materiais complementares
- Listas de exercícios
- Cronogramas e calendários

### Para Alunos
- Acesso rápido a materiais de estudo
- Download de trabalhos e atividades
- Documentos de referência

### Para Pais
- Informes e comunicados em PDF
- Calendários escolares
- Documentos administrativos

## 🔧 Atributos Salvos

O bloco salva os seguintes atributos:

```javascript
{
  titulo: string,           // Título do documento
  descricao: string,        // Descrição
  documentoId: number,      // ID na biblioteca de mídia
  documentoUrl: string,     // URL completa do arquivo
  documentoNome: string,    // Nome original do arquivo
  documentoTamanho: string, // Tamanho formatado (ex: "2.5 MB")
  corFundo: string,         // Cor de fundo do card
  corBotao: string          // Cor do botão de download
}
```

## 🚀 Recursos Técnicos

- ✅ **MediaUpload** - Upload nativo do WordPress
- ✅ **RichText** - Formatação de texto rica
- ✅ **ColorPalette** - Seletor de cores customizado
- ✅ **Lucide Icons** - Ícones modernos via CDN
- ✅ **Responsivo** - Adapta para todos os dispositivos
- ✅ **Acessível** - Link semântico com atributo download
- ✅ **Performance** - CSS otimizado e leve

## 📱 Comportamento Responsivo

### Desktop (> 768px)
- Layout centralizado com largura máxima
- Ícone grande (48px)
- Todos os elementos visíveis

### Tablet (768px)
- Ajusta padding e tamanhos
- Mantém layout horizontal

### Mobile (< 480px)
- Layout vertical centralizado
- Botão ocupa largura total
- Ícone reduzido (40px)
- Informações empilhadas

## 🐛 Solução de Problemas

**Documento não aparece após upload:**
- Verifique se o arquivo está nos formatos suportados
- Confirme que o arquivo foi carregado na biblioteca de mídia

**Botão de download não funciona:**
- Verifique se a URL do arquivo está acessível
- Alguns navegadores podem bloquear downloads automáticos

**Ícones não aparecem:**
- Confirme que Lucide está carregado (já incluído no plugin)
- Limpe o cache do navegador

## 📄 Compatibilidade

- **WordPress:** 6.0 ou superior
- **PHP:** 7.4 ou superior
- **Navegadores:** Modernos (Chrome, Firefox, Safari, Edge)

---

**Versão:** 1.3.0
**Última atualização:** Janeiro 2025
