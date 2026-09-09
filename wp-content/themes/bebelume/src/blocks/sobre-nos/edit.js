/**
 * Bloco Sobre Nós - Editor com campos na sidebar
 */

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit({ attributes, setAttributes }) {
	const { title, content, buttonText, buttonUrl } = attributes;
	const blockProps = useBlockProps({
		className: 'bebelume-sobre-nos-block'
	});

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Configuração do Conteúdo', 'bebelume')} initialOpen={true}>
					<TextControl
						label={__('Título', 'bebelume')}
						value={title}
						onChange={(value) => setAttributes({ title: value })}
						help={__('Ex: SOBRE NÓS', 'bebelume')}
					/>
					<TextareaControl
						label={__('Descrição', 'bebelume')}
						value={content}
						onChange={(value) => setAttributes({ content: value })}
						help={__('Digite o texto sobre a empresa', 'bebelume')}
						rows={10}
					/>
				</PanelBody>

				<PanelBody title={__('Configuração do Botão', 'bebelume')} initialOpen={false}>
					<TextControl
						label={__('Texto do Botão', 'bebelume')}
						value={buttonText}
						onChange={(value) => setAttributes({ buttonText: value })}
					/>
					<TextControl
						label={__('URL do Botão', 'bebelume')}
						value={buttonUrl}
						onChange={(value) => setAttributes({ buttonUrl: value })}
						type="url"
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="sobre-nos-container">
					{/* Grid: 2 colunas (Bootstrap-like) */}
					<div className="content-wrapper">
						{/* Coluna 1: Título (col-6) */}
						<div className="title-section">
							<h2 className="sobre-nos-title">{title || 'SOBRE NÓS'}</h2>
						</div>

						{/* Coluna 2: Conteúdo (col-6) */}
						<div className="text-section">
							<div className="sobre-nos-content">
								{content ? (
									content.split('\n').map((paragraph, index) => (
										<p key={index}>{paragraph}</p>
									))
								) : (
									<p style={{ opacity: 0.5 }}>Digite o texto na barra lateral →</p>
								)}
							</div>

							<div className="button-wrapper">
								<span className="sobre-nos-button">{buttonText}</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</>
	);
}