import { __ } from '@wordpress/i18n';
import { 
    useBlockProps,
    InspectorControls,
    MediaUpload,
    MediaUploadCheck 
} from '@wordpress/block-editor';
import { 
    PanelBody, 
    Button, 
    ToggleControl,
    ColorPicker 
} from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const { backgroundImage, backgroundColor, showClouds, showSmoke } = attributes;
    const blockProps = useBlockProps({
        className: 'editor-telhado-castelinho-bebelume',
    });

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Configurações do Telhado do Castelinho Bebelume', 'bebelume-theme')}>
                    
                    {/* Upload de Imagem de Fundo */}
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={(media) => setAttributes({ backgroundImage: media.url })}
                            allowedTypes={['image']}
                            value={backgroundImage}
                            render={({ open }) => (
                                <div>
                                    <Button 
                                        onClick={open}
                                        variant="secondary"
                                    >
                                        {backgroundImage ? __('Trocar Imagem', 'bebelume-theme') : __('Adicionar Imagem', 'bebelume-theme')}
                                    </Button>
                                    {backgroundImage && (
                                        <Button 
                                            onClick={() => setAttributes({ backgroundImage: '' })}
                                            variant="link"
                                            isDestructive
                                        >
                                            {__('Remover Imagem', 'bebelume-theme')}
                                        </Button>
                                    )}
                                </div>
                            )}
                        />
                    </MediaUploadCheck>

                    {/* Cor de Fundo */}
                    <p style={{ marginTop: '20px' }}>{__('Cor de Fundo', 'bebelume-theme')}</p>
                    <ColorPicker
                        color={backgroundColor}
                        onChangeComplete={(color) => setAttributes({ backgroundColor: color.hex })}
                    />

                    {/* Toggle Nuvens */}
                    <ToggleControl
                        label={__('Mostrar Nuvens', 'bebelume-theme')}
                        checked={showClouds}
                        onChange={(value) => setAttributes({ showClouds: value })}
                    />

                    {/* Toggle Fumacinha */}
                    <ToggleControl
                        label={__('Mostrar Fumacinha', 'bebelume-theme')}
                        checked={showSmoke}
                        onChange={(value) => setAttributes({ showSmoke: value })}
                    />

                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <div 
                    className="preview-telhado-castelinho-bebelume"
                    style={{
                        backgroundImage: backgroundImage ? `url(${backgroundImage})` : 'none',
                        backgroundColor: backgroundColor,
                        backgroundSize: '100% 100%',
                        backgroundPosition: 'center',
                        minHeight: '200px',
                        position: 'relative',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        border: '2px dashed #ccc',
                    }}
                >
                    <p style={{ 
                        background: 'rgba(255,255,255,0.8)', 
                        padding: '10px', 
                        borderRadius: '5px' 
                    }}>
                        🏠 {__('Telhado do Castelinho Bebelume', 'bebelume-theme')}
                    </p>
                    
                    {showClouds && (
                        <span style={{
                            position: 'absolute',
                            top: '10px',
                            right: '10px',
                            fontSize: '12px',
                            background: 'white',
                            padding: '5px',
                            borderRadius: '3px'
                        }}>☁️ Nuvens</span>
                    )}
                    
                    {showSmoke && (
                        <span style={{
                            position: 'absolute',
                            top: '10px',
                            left: '10px',
                            fontSize: '12px',
                            background: 'white',
                            padding: '5px',
                            borderRadius: '3px'
                        }}>💨 Fumaça</span>
                    )}
                </div>
            </div>
        </>
    );
}