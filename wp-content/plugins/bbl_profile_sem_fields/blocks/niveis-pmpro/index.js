(function (blocks, element, blockEditor, components, serverSideRender) {
    var el = element.createElement;
    var useBlockProps = blockEditor.useBlockProps;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var Spinner = components.Spinner;
    var ServerSideRender = serverSideRender;
    var useEffect = element.useEffect;
    var useState = element.useState;

    blocks.registerBlockType('bebelume/niveis-pmpro', {

        edit: function (props) {
            var a = props.attributes;
            var set = props.setAttributes;
            var blockProps = useBlockProps();

            var groups = useState([])[0];
            var setGroups = useState([])[1];
            var loading = useState(true)[0];
            var setLoading = useState(true)[1];

            // Corrigindo useState para funcionar corretamente
            var groupsState = element.useState([]);
            var groupsList = groupsState[0];
            var setGroupsList = groupsState[1];

            var loadingState = element.useState(true);
            var isLoading = loadingState[0];
            var setIsLoading = loadingState[1];

            useEffect(function () {
                wp.apiFetch({ path: '/bebelume/v1/pmpro-groups' })
                    .then(function (data) {
                        setGroupsList(data || []);
                        setIsLoading(false);
                    })
                    .catch(function () {
                        setIsLoading(false);
                    });
            }, []);

            var groupOptions = [{ label: '— Selecionar grupo —', value: 0 }].concat(
                groupsList.map(function (g) {
                    return { label: g.name, value: g.id };
                })
            );

            return el('div', blockProps,
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Grupo de Níveis', initialOpen: true },
                        isLoading
                            ? el(Spinner)
                            : el(SelectControl, {
                                label: 'Grupo PMPro',
                                value: a.groupId,
                                options: groupOptions,
                                onChange: function (val) {
                                    var id = parseInt(val, 10);
                                    var found = groupsList.find(function (g) { return g.id === id; });
                                    set({
                                        groupId: id,
                                        groupName: found ? found.name : ''
                                    });
                                }
                            })
                    )
                ),
                a.groupId
                    ? el(ServerSideRender, {
                        block: 'bebelume/niveis-pmpro',
                        attributes: a
                    })
                    : el('div', {
                        style: {
                            padding: '2em',
                            textAlign: 'center',
                            color: '#999',
                            border: '2px dashed #ccc',
                            borderRadius: '4px'
                        }
                    }, '👥 Selecione um grupo de níveis PMPro no painel lateral.')
            );
        },

        save: function () {
            return null; // render_callback PHP
        }
    });

}(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.serverSideRender
));
