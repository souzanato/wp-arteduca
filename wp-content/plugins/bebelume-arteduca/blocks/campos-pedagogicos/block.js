(function (wp) {
    'use strict';

    var el              = wp.element.createElement;
    var __              = wp.i18n.__;
    var registerBlockType = wp.blocks.registerBlockType;
    var useSelect       = wp.data.useSelect;
    var useBlockProps   = wp.blockEditor.useBlockProps;

    registerBlockType('bebelume-arteduca/campos-pedagogicos', {
        title:    __('Campos Pedagógicos', 'bebelume-arteduca'),
        icon:     'welcome-learn-more',
        category: 'bebelume-arteduca',
        attributes: {},

        edit: function (props) {
            // useBlockProps é obrigatório no apiVersion 3 para o bloco ser selecionável
            var blockProps = useBlockProps({
                className: 'bebelume-campos-pedagogicos'
            });

            var meta = useSelect(function (select) {
                return select('core/editor').getEditedPostAttribute('meta') || {};
            });

            var direitos = Array.isArray(meta['_bebelume_direitos'])
                ? meta['_bebelume_direitos'].filter(Boolean) : [];
            var campos = Array.isArray(meta['_bebelume_campos'])
                ? meta['_bebelume_campos'].filter(Boolean) : [];

            var temConteudo = direitos.length > 0 || campos.length > 0;

            if (!temConteudo) {
                return el('div', blockProps,
                    el('div', { className: 'bebelume-preview-vazio' },
                        el('span', { style: { fontSize: '20px' } }, '🎓'),
                        el('p', null, __('Campos Pedagógicos', 'bebelume-arteduca')),
                        el('small', null, __('Use a aba "Post" na barra lateral para marcar os itens.', 'bebelume-arteduca'))
                    )
                );
            }

            return el('div', blockProps,
                direitos.length > 0 && el('div', { className: 'bebelume-campo-row' },
                    el('p', { className: 'bebelume-campo-titulo' }, __('Direitos de Aprendizagem', 'bebelume-arteduca')),
                    el('p', { className: 'bebelume-campo-valor' }, direitos.join('; '))
                ),
                campos.length > 0 && el('div', { className: 'bebelume-campo-row' },
                    el('p', { className: 'bebelume-campo-titulo' }, __('Campos de Experiência', 'bebelume-arteduca')),
                    el('p', { className: 'bebelume-campo-valor' }, campos.join('; '))
                )
            );
        },

        save: function () { return null; },

        deprecated: [{
            attributes: {
                direitosAprendizagem: { type: 'string', default: '' },
                camposExperiencia:    { type: 'string', default: '' },
                corFundo:   { type: 'string', default: '#F3F0EB' },
                corDestaque:{ type: 'string', default: '#2A4582' }
            },
            save: function() { return null; }
        }]
    });

})(window.wp);
