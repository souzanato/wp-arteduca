import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
export default function Save({ attributes }) {
  const { backgroundImage, backgroundColor, link, invertLayout, hasImage, showClouds, showGarden } = attributes;
  const blockProps = useBlockProps.save({ className: 'bebelume-nivel w-100' });
  const content = (<div className="andar-content"><InnerBlocks.Content /></div>);
  const andarClasses = `bebelume-andar terreo-castelinho-bebelume ${invertLayout ? 'invertido' : ''} ${hasImage ? 'has-image' : ''}`.trim();
  const hasCustomBackground = backgroundImage && backgroundImage !== '';
  const inlineStyle = hasCustomBackground ? { backgroundImage: `url(${backgroundImage})`, backgroundColor } : null;
  return (
    <section {...blockProps}>
      {showClouds && (
        <div className="clouds-container">
          <div className="cloud cloud-1"></div>
          <div className="cloud cloud-2"></div>
          <div className="cloud cloud-3"></div>
        </div>
      )}
      {showGarden && (
        <div className="garden-container">
          <div className="colinas">
            <div className="colina colina1"></div>
            <div className="colina colina2"></div>
            <div className="colina colina3"></div>
          </div>
          <div className="campo"></div>
          <div className="borboleta borboleta1">
            <div className="asa asa-esquerda"></div>
            <div className="asa asa-direita"></div>
            <div className="corpo-borboleta"></div>
          </div>
          <div className="borboleta borboleta2">
            <div className="asa asa-esquerda"></div>
            <div className="asa asa-direita"></div>
            <div className="corpo-borboleta"></div>
          </div>
          <div className="borboleta borboleta3">
            <div className="asa asa-esquerda"></div>
            <div className="asa asa-direita"></div>
            <div className="corpo-borboleta"></div>
          </div>
          <div className="flores-container">
            
            <div className="arbustos">
              <div className="arbusto" style={{ left: '0%' }}></div>
              <div className="arbusto" style={{ left: '10%', bottom: '-6em' }}></div>
              <div className="arbusto" style={{ left: '80%' }}></div>
              <div className="arbusto" style={{ left: '90%', bottom: '-6em' }}></div>
            </div>

            {/* Linha de plantinhas (frente) */}
            <div className="plantinhas">
              <div className="plantinha-bebelume" style={{ left: '5%' }}></div>
              <div className="plantinha-bebelume" style={{ left: '20%' }}></div>
              <div className="plantinha-bebelume" style={{ left: '35%' }}></div>
              <div className="plantinha-bebelume" style={{ left: '50%' }}></div>
              <div className="plantinha-bebelume" style={{ left: '65%' }}></div>
              <div className="plantinha-bebelume" style={{ left: '80%' }}></div>
            </div>            
          </div>
        </div>
      )}
      {link ? (
        <a href={link} className={`${andarClasses} d-block text-decoration-none`} {...(inlineStyle && { style: inlineStyle })}>
          {content}
        </a>
      ) : (
        <div className={andarClasses} {...(inlineStyle && { style: inlineStyle })}>
          {content}
        </div>
      )}
    </section>
  );
}