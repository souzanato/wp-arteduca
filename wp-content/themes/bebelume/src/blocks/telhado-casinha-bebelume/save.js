import { useBlockProps } from '@wordpress/block-editor';

export default function Save({ attributes }) {
    const { backgroundImage, backgroundColor, showClouds, showSmoke } = attributes;
    
    const blockProps = useBlockProps.save({
        className: 'bebelume-nivel',
        style: {
            position: 'relative',
        }
    });

    // IMPORTANTE: Só adiciona style inline se tiver imagem customizada
    const hasCustomBackground = backgroundImage && backgroundImage !== '';
    
    const inlineStyle = hasCustomBackground ? {
        backgroundImage: `url(${backgroundImage})`,
        backgroundColor: backgroundColor,
    } : null;

    return (
        <section {...blockProps}>
            {/* Nuvens */}
            {showClouds && (
                <div className="clouds-container">
                    <div className="cloud cloud-1"></div>
                    <div className="cloud cloud-2"></div>
                    <div className="cloud cloud-3"></div>
                </div>
            )}

            {/* Telhado */}
            <div 
                className="bebelume-andar telhado-casinha-bebelume"
                {...(inlineStyle && { style: inlineStyle })}
            >
                {/* Fumacinha */}
                {showSmoke && (
                    <div className="smoke-container">
                        <div className="smoke"></div>
                        <div className="smoke"></div>
                        <div className="smoke"></div>
                    </div>
                )}
            </div>
        </section>
    );
}