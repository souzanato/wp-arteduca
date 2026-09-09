# Bloco Bebelume Andar Tipo 1 - Com Jardim 🌸

## Modificações Realizadas

Adicionei um jardim animado ao bloco WordPress "Andar Tipo 1", seguindo todas as regras especificadas:

### O que foi modificado:

1. **block.json**
   - Adicionado novo atributo: `showGarden` (boolean, padrão: false)

2. **edit.js**
   - Adicionado controle toggle "Mostrar Jardim" no painel de configurações
   - Adicionado indicador visual "🌸 Jardim" no preview do editor quando ativado

3. **save.js**
   - Adicionado container `.garden-container` com todos os elementos do jardim
   - O jardim é renderizado condicionalmente baseado no atributo `showGarden`

4. **style.css**
   - Adicionados todos os estilos do jardim (sem céu e sol, conforme solicitado)
   - Jardim posicionado atrás de `.andar-content` usando z-index
   - Inclui: colinas, campo, árvore, flores variadas e borboletas animadas
   - Totalmente responsivo

## Como usar:

1. Substitua os arquivos do bloco pelos arquivos modificados
2. No editor do WordPress, ao adicionar ou editar o bloco "Andar Tipo 1":
   - Abra o painel lateral de configurações (Inspector)
   - Encontre o toggle "Mostrar Jardim"
   - Ative para adicionar o jardim de fundo ao andar

## Características do Jardim:

✅ Posicionado atrás do conteúdo (.andar-content)
✅ Não inclui céu nem sol (apenas elementos terrestres)
✅ Pode ser ativado/desativado facilmente
✅ Funciona em conjunto com as nuvens
✅ Compatível com o modo invertido do bloco
✅ Totalmente responsivo
✅ Borboletas animadas
✅ Flores coloridas (margaridas, rosa, laranja, amarela, vermelha)
✅ Árvore com copa
✅ Colinas em diferentes tons de verde

## Elementos do Jardim:

- 🏔️ **Colinas**: 3 colinas em tons de verde sobrepostas
- 🌱 **Campo**: Gradiente de verde vibrante
- 🌳 **Árvore**: Com tronco e copa no canto direito
- 🌸 **Flores**: 13 flores variadas (margaridas brancas e flores coloridas)
- 🦋 **Borboletas**: 3 borboletas animadas voando em padrões diferentes

## Compatibilidade:

- ✅ Funciona com imagem de fundo personalizada
- ✅ Funciona com modo invertido
- ✅ Funciona com nuvens ativas
- ✅ Não interfere com links
- ✅ Totalmente responsivo (mobile, tablet, desktop)

## Notas Técnicas:

- O jardim usa `pointer-events: none` para não interferir com cliques
- O conteúdo (.andar-content) tem `z-index: 10` para ficar acima do jardim
- Todas as animações são em CSS puro (sem JavaScript)
- Usa clamp() para valores responsivos
