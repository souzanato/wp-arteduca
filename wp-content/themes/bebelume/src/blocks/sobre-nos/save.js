/**
 * Bloco Sobre Nós - Save (Front-end)
 */

import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
	const { title, content, buttonText, buttonUrl } = attributes;
	const blockProps = useBlockProps.save({
		className: 'bebelume-sobre-nos-block'
	});

	return (
		<div {...blockProps}>
			<div className="sobre-nos-container">
				{/* Grid: 2 colunas (Bootstrap-like) */}
				<div className="content-wrapper">
					{/* Coluna 1: Título (col-6) */}
					<div className="title-section">
						<h2 className="sobre-nos-title">{title}</h2>
					</div>

					{/* Coluna 2: Conteúdo (col-6) */}
					<div className="text-section">
						<div className="sobre-nos-content">
							{content && content.split('\n').map((paragraph, index) => (
								<p key={index}>{paragraph}</p>
							))}
						</div>

						<div className="button-wrapper">
							<a href={buttonUrl} className="sobre-nos-button">
								{buttonText}
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}