import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody, Button, ToggleControl, ColorPicker, TextControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

export default function Edit({ attributes, setAttributes, clientId }) {
  const { backgroundImage, backgroundColor, link, invertLayout, hasImage, showClouds, showGarden } = attributes;
  const blockProps = useBlockProps({ className: 'editor-terreo-casinha-bebelume' });

  const innerBlocks = useSelect((select) => {
    const { getBlock } = select('core/block-editor');
    const block = getBlock(clientId);
    return block?.innerBlocks || [];
  }, [clientId]);

  useEffect(() => {
    const checkForImages = (blocks) => {
      for (const block of blocks) {
        if (block.name === 'core/image') return true;
        if (block.innerBlocks && block.innerBlocks.length > 0) {
          if (checkForImages(block.innerBlocks)) return true;
        }
      }
      return false;
    };
    const foundImage = checkForImages(innerBlocks);
    if (foundImage !== hasImage) setAttributes({ hasImage: foundImage });
  }, [innerBlocks, hasImage, setAttributes]);

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Configurações do Andar', 'bebelume-theme')}>
          <MediaUploadCheck>
            <MediaUpload
              onSelect={(media) => setAttributes({ backgroundImage: media.url })}
              allowedTypes={['image']}
              value={backgroundImage}
              render={({ open }) => (
                <div>
                  <Button onClick={open} variant="secondary">
                    {backgroundImage ? __('Trocar Imagem de Fundo', 'bebelume-theme') : __('Adicionar Imagem de Fundo', 'bebelume-theme')}
                  </Button>
                  {backgroundImage && (
                    <Button onClick={() => setAttributes({ backgroundImage: '' })} variant="link" isDestructive>
                      {__('Remover Imagem', 'bebelume-theme')}
                    </Button>
                  )}
                </div>
              )}
            />
          </MediaUploadCheck>

          <p style={{ marginTop: '20px' }}>{__('Cor de Fundo', 'bebelume-theme')}</p>
          <ColorPicker color={backgroundColor} onChangeComplete={(color) => setAttributes({ backgroundColor: color.hex })} />

          <TextControl
            label={__('Link (URL)', 'bebelume-theme')}
            value={link}
            onChange={(value) => setAttributes({ link: value })}
            placeholder="https://exemplo.com"
            help={__('Deixe vazio se não quiser link', 'bebelume-theme')}
          />

          <ToggleControl
            label={__('Inverter Layout', 'bebelume-theme')}
            checked={invertLayout}
            onChange={(value) => setAttributes({ invertLayout: value })}
          />

          <ToggleControl
            label={__('Mostrar Nuvens', 'bebelume-theme')}
            checked={showClouds}
            onChange={(value) => setAttributes({ showClouds: value })}
          />

          <ToggleControl
            label={__('Mostrar Jardim', 'bebelume-theme')}
            checked={showGarden}
            onChange={(value) => setAttributes({ showGarden: value })}
          />

          {hasImage && (
            <p style={{ marginTop: '20px', padding: '10px', background: '#e7f7e7', borderRadius: '4px', fontSize: '12px' }}>
              ✓ {__('Imagem detectada no conteúdo', 'bebelume-theme')}
            </p>
          )}
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <div
          className="preview-terreo-casinha-bebelume"
          style={{
            backgroundImage: backgroundImage ? `url(${backgroundImage})` : 'none',
            backgroundColor: backgroundColor,
            backgroundSize: '100% 100%',
            backgroundPosition: 'center',
            minHeight: '300px',
            position: 'relative',
            padding: '20px',
            border: '2px dashed #0073aa',
            transform: invertLayout ? 'scaleX(-1)' : 'none',
          }}
        >
          {showClouds && (
            <span
              style={{
                position: 'absolute',
                top: '10px',
                right: '10px',
                fontSize: '12px',
                background: 'white',
                padding: '5px',
                borderRadius: '3px',
              }}
            >
              ☁️ Nuvens
            </span>
          )}

          {showGarden && (
            <span
              style={{
                position: 'absolute',
                top: '10px',
                left: '10px',
                fontSize: '12px',
                background: 'white',
                padding: '5px',
                borderRadius: '3px',
              }}
            >
              🌸 Jardim
            </span>
          )}

          <div style={{ transform: invertLayout ? 'scaleX(-1)' : 'none' }}>
            <div
              style={{
                background: 'rgba(255,255,255,0.9)',
                padding: '10px',
                borderRadius: '5px',
                marginBottom: '15px',
                display: 'inline-block',
              }}
            >
              🏠 {__('Andar Tipo 1', 'bebelume-theme')}
            </div>
            <div style={{ minHeight: '200px', background: 'rgba(255,255,255,0.5)', padding: '15px', borderRadius: '5px' }}>
              <InnerBlocks allowedBlocks={true} />
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
