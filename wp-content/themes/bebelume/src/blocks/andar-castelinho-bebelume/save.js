import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
export default function Save({ attributes }) {
  const { backgroundImage, backgroundColor, link, invertLayout, hasImage, showClouds, showGarden } = attributes;
  const blockProps = useBlockProps.save({ className: 'bebelume-nivel w-100' });
  const content = (<div className="andar-content"><InnerBlocks.Content /></div>);
  const andarClasses = `bebelume-andar andar-castelinho-bebelume ${invertLayout ? 'invertido' : ''} ${hasImage ? 'has-image' : ''}`.trim();
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
          <div className="arvore">
            <div className="tronco"></div>
            <div className="copa"></div>
          </div>
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
            <div className="flor margarida" style="left: 5%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro"></div></div></div>
            <div className="flor margarida" style="left: 15%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro"></div></div></div>
            <div className="flor margarida" style="left: 45%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro"></div></div></div>
            <div className="flor margarida" style="left: 58%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro"></div></div></div>
            <div className="flor rosa" style="left: 10%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro"></div></div></div>
            <div className="flor laranja" style="left: 22%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro"></div></div></div>
            <div className="flor amarela" style="left: 30%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro" style="background: #FF8C00;"></div></div></div>
            <div className="flor rosa" style="left: 38%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro"></div></div></div>
            <div className="flor laranja" style="left: 52%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro"></div></div></div>
            <div className="flor vermelha" style="left: 68%; bottom: 2%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro"></div></div></div>
            <div className="flor amarela" style="left: 75%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro" style="background: #FF6347;"></div></div></div>
            <div className="flor amarela" style="left: 85%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro" style="background: #FF8C00;"></div></div></div>
            <div className="flor laranja" style="left: 92%;"><div className="caule"></div><div className="petala-container"><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="petala"></div><div className="centro"></div></div></div>
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