(function (blocks, element, blockEditor) {
    var el = element.createElement;
    var InnerBlocks = blockEditor.InnerBlocks;
    var useBlockProps = blockEditor.useBlockProps;

    var terreo_icon = el('svg', {
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
        el('path', { d: 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z' }),
        el('polyline', { points: '9 22 9 12 15 12 15 22' })
    );

    blocks.registerBlockType('bebelume/associacoes-terreo', {
        icon: terreo_icon,

        edit: function () {
            var blockProps = useBlockProps({
                className: 'bebelume-nivel w-100 branco'
            });

            return el(
                'section',
                blockProps,
                // Placeholder do jardim no editor
                el('div', { className: 'bbl-terreo-garden-placeholder' },
                    el('span', {}, '🌿 Jardim animado (visível no frontend)')
                ),
                el('div', { className: 'bebelume-andar terreo-casinha-bebelume has-image' },
                    el('div', { className: 'andar-content' },
                        el('div', { className: 'row bebelume-level-teasers' },
                            el(InnerBlocks, {})
                        )
                    )
                )
            );
        },

        save: function () {
            return el(InnerBlocks.Content, {});
        }
    });

}(window.wp.blocks, window.wp.element, window.wp.blockEditor));
