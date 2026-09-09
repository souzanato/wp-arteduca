(function (blocks, element, blockEditor) {
    var el = element.createElement;
    var InnerBlocks = blockEditor.InnerBlocks;
    var useBlockProps = blockEditor.useBlockProps;

    var andar_icon = el('svg', {
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
        el('rect', { x: '2', y: '4', width: '20', height: '16', rx: '1' })
    );

    blocks.registerBlockType('bebelume/andar', {
        icon: andar_icon,

        edit: function () {
            var blockProps = useBlockProps({
                className: 'bebelume-nivel'
            });

            return el(
                'section',
                blockProps,
                el('div', { className: 'bebelume-andar bebelume-associacoes-andar' },
                    el('div', { className: 'andar-content d-flex' },
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
                el('div', { className: 'bebelume-andar bebelume-associacoes-andar' },
                    el('div', { className: 'andar-content d-flex' },
                        el(InnerBlocks.Content, {})
                    )
                )
            );
        }
    });

}(window.wp.blocks, window.wp.element, window.wp.blockEditor));
