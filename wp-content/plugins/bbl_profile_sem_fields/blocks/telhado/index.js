(function (blocks, element, blockEditor) {
    var el = element.createElement;
    var InnerBlocks = blockEditor.InnerBlocks;
    var useBlockProps = blockEditor.useBlockProps;

    var telhado_icon = el('svg', {
        xmlns: 'http://www.w3.org/2000/svg',
        viewBox: '0 0 24 24',
        width: '24',
        height: '24',
        fill: 'none',
        stroke: 'currentColor',
        strokeWidth: '2',
        strokeLinecap: 'round',
        strokeLinejoin: 'round'
    },
        // Caixinha com borda
        el('rect', { x: '2', y: '8', width: '20', height: '14', rx: '1' }),
        // Passarinho esquerdo (triângulo simples)
        el('path', { d: 'M4 8 Q5 4 7 5 Q6 7 4 8Z', fill: 'currentColor', stroke: 'none' }),
        // Passarinho direito
        el('path', { d: 'M20 8 Q19 4 17 5 Q18 7 20 8Z', fill: 'currentColor', stroke: 'none' })
    );

    blocks.registerBlockType('bebelume/telhado', {
        icon: telhado_icon,
        edit: function () {
            var blockProps = useBlockProps({
                className: 'bebelume-nivel'
            });

            return el(
                'section',
                blockProps,
                el('div', {
                    className: 'bebelume-andar telhado-passarinhos-bebelume bebelume-associacoes-telhado'
                },
                    el('div', { className: 'andar-content d-flex p-5' },
                        el(InnerBlocks, {})
                    )
                )
            );
        },

        save: function () {
            var blockProps = useBlockProps.save({
                className: 'bebelume-nivel'
            });

            return el(
                'section',
                blockProps,
                el('div', {
                    className: 'bebelume-andar telhado-passarinhos-bebelume bebelume-associacoes-telhado'
                },
                    el('div', { className: 'andar-content d-flex p-5' },
                        el(InnerBlocks.Content, {})
                    )
                )
            );
        }
    });

}(window.wp.blocks, window.wp.element, window.wp.blockEditor));
