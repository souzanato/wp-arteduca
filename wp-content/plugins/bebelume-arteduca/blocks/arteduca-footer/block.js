(function() {
  var registerBlockType = wp.blocks.registerBlockType;
  var InnerBlocks      = wp.blockEditor.InnerBlocks;
  var useBlockProps    = wp.blockEditor.useBlockProps;
  var el               = wp.element.createElement;

  registerBlockType('bebelume/arteduca-footer', {
    edit: function() {
      var blockProps = useBlockProps({
        className: 'arteduca-footer-editor-wrapper'
      });

      return el(
        'div',
        blockProps,
        el(
          'div',
          { className: 'arteduca-footer-editor-label' },
          '📦 ArtEduca Footer — conteúdo será movido para o footer'
        ),
        el(
          'div',
          { className: 'arteduca-footer-innerblocks' },
          el(InnerBlocks, {
            templateLock: false,
            renderAppender: InnerBlocks.ButtonBlockAppender
          })
        )
      );
    },

    save: function() {
      var blockProps = useBlockProps.save({
        className: 'arteduca-footer-block'
      });

      return el(
        'div',
        blockProps,
        el(InnerBlocks.Content)
      );
    }
  });
})();
