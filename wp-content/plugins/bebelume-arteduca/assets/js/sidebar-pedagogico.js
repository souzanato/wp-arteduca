(function () {
    'use strict';

    // Debug: loga o que está disponível no wp
    console.log('[Bebelume] wp.editor:', typeof wp.editor, wp.editor ? Object.keys(wp.editor).join(', ') : 'N/A');
    console.log('[Bebelume] wp.editPost:', typeof wp.editPost, wp.editPost ? Object.keys(wp.editPost).join(', ') : 'N/A');

    if (!window.wp || !wp.plugins || !wp.element || !wp.components || !wp.data) {
        console.warn('[Bebelume] wp não disponível');
        return;
    }

    var el              = wp.element.createElement;
    var Fragment        = wp.element.Fragment;
    var __              = wp.i18n.__;
    var registerPlugin  = wp.plugins.registerPlugin;
    var useSelect       = wp.data.useSelect;
    var useDispatch     = wp.data.useDispatch;
    var CheckboxControl = wp.components.CheckboxControl;
    var PanelBody       = wp.components.PanelBody;

    // Tenta todas as fontes possíveis
    var PluginDocumentSettingPanel =
        (wp.editor        && wp.editor.PluginDocumentSettingPanel)        ||
        (wp.editPost      && wp.editPost.PluginDocumentSettingPanel)      ||
        (wp.editorPlugin  && wp.editorPlugin.PluginDocumentSettingPanel)  ||
        null;

    console.log('[Bebelume] PluginDocumentSettingPanel:', PluginDocumentSettingPanel ? 'FOUND' : 'NOT FOUND');

    var DIREITOS = (window.bebelumePedagogico || {}).direitos || [];
    var CAMPOS   = (window.bebelumePedagogico || {}).campos   || [];

    console.log('[Bebelume] DIREITOS:', DIREITOS);
    console.log('[Bebelume] CAMPOS:', CAMPOS);

    function useMeta(key) {
        var meta = useSelect(function (select) {
            var store = select('core/editor');
            return store ? (store.getEditedPostAttribute('meta') || {}) : {};
        });
        var dispatch = useDispatch('core/editor');
        var lista = Array.isArray(meta[key]) ? meta[key] : [];

        function toggle(item) {
            var atual = lista.slice();
            var idx = atual.indexOf(item);
            if (idx === -1) { atual.push(item); } else { atual.splice(idx, 1); }
            var patch = {};
            patch[key] = atual;
            dispatch.editPost({ meta: patch });
        }
        return { lista: lista, toggle: toggle };
    }

    function CheckList(props) {
        var opcoes   = props.opcoes || [];
        var marcados = props.marcados || [];
        var onToggle = props.onToggle;
        var cor      = props.cor || '#2A4582';

        if (opcoes.length === 0) {
            return el('p', {
                style: { color: '#999', fontSize: '12px', margin: '4px 0 8px', fontStyle: 'italic' }
            }, __('Nenhum item cadastrado.', 'bebelume-arteduca'));
        }

        return el('div', null,
            opcoes.map(function (opcao) {
                var marcado = marcados.indexOf(opcao) !== -1;
                return el('div', {
                    key: opcao,
                    style: {
                        padding: '4px 8px',
                        marginBottom: '3px',
                        borderRadius: '4px',
                        background: marcado ? 'rgba(42,69,130,0.07)' : 'transparent',
                        borderLeft: marcado ? '3px solid ' + cor : '3px solid transparent',
                    }
                },
                el(CheckboxControl, {
                    label: opcao,
                    checked: marcado,
                    onChange: function () { onToggle(opcao); },
                    __nextHasNoMarginBottom: true,
                }));
            })
        );
    }

    // Se PluginDocumentSettingPanel não existir, usa PanelBody dentro de PluginSidebar como fallback
    function PedagogicoPanel() {
        var direitosMeta = useMeta('_bebelume_direitos');
        var camposMeta   = useMeta('_bebelume_campos');

        if (PluginDocumentSettingPanel) {
            return el(Fragment, null,
                el(PluginDocumentSettingPanel, {
                    name:        'bebelume-direitos-panel',
                    title:       __('Direitos de Aprendizagem', 'bebelume-arteduca'),
                    initialOpen: true,
                },
                    el(CheckList, {
                        opcoes: DIREITOS, marcados: direitosMeta.lista,
                        onToggle: direitosMeta.toggle, cor: '#2A4582',
                    })
                ),
                el(PluginDocumentSettingPanel, {
                    name:        'bebelume-campos-panel',
                    title:       __('Campos de Experiência', 'bebelume-arteduca'),
                    initialOpen: true,
                },
                    el(CheckList, {
                        opcoes: CAMPOS, marcados: camposMeta.lista,
                        onToggle: camposMeta.toggle, cor: '#5C4F93',
                    })
                )
            );
        }

        // Fallback: PluginSidebar separada
        var PluginSidebar = (wp.editor && wp.editor.PluginSidebar) || (wp.editPost && wp.editPost.PluginSidebar);
        var PluginSidebarMoreMenuItem = (wp.editor && wp.editor.PluginSidebarMoreMenuItem) || (wp.editPost && wp.editPost.PluginSidebarMoreMenuItem);

        if (!PluginSidebar) {
            console.warn('[Bebelume] Nenhum painel disponível');
            return null;
        }

        return el(Fragment, null,
            el(PluginSidebarMoreMenuItem, { target: 'bebelume-pedagogico-sidebar' },
                __('🎓 Pedagógico', 'bebelume-arteduca')
            ),
            el(PluginSidebar, {
                name: 'bebelume-pedagogico-sidebar',
                title: __('Pedagógico', 'bebelume-arteduca'),
            },
                el(PanelBody, { title: __('Direitos de Aprendizagem', 'bebelume-arteduca'), initialOpen: true },
                    el(CheckList, {
                        opcoes: DIREITOS, marcados: direitosMeta.lista,
                        onToggle: direitosMeta.toggle, cor: '#2A4582',
                    })
                ),
                el(PanelBody, { title: __('Campos de Experiência', 'bebelume-arteduca'), initialOpen: true },
                    el(CheckList, {
                        opcoes: CAMPOS, marcados: camposMeta.lista,
                        onToggle: camposMeta.toggle, cor: '#5C4F93',
                    })
                )
            )
        );
    }

    registerPlugin('bebelume-pedagogico', { render: PedagogicoPanel });

})();
