/**
 * Webpack config customizado para blocos Gutenberg
 */

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		// Bloco Sobre Nós
		'sobre-nos/index': path.resolve(process.cwd(), 'src/blocks/sobre-nos', 'index.js'),
		
		// Blocos existentes
		'telhado-casinha-bebelume/index': path.resolve(process.cwd(), 'src/blocks/telhado-casinha-bebelume', 'index.js'),
		'telhado-castelinho-bebelume/index': path.resolve(process.cwd(), 'src/blocks/telhado-castelinho-bebelume', 'index.js'),
		'telhado-passarinhos-bebelume/index': path.resolve(process.cwd(), 'src/blocks/telhado-passarinhos-bebelume', 'index.js'),
		'andar-castelinho-bebelume/index': path.resolve(process.cwd(), 'src/blocks/andar-castelinho-bebelume', 'index.js'),
		'andar-casinha-bebelume/index': path.resolve(process.cwd(), 'src/blocks/andar-casinha-bebelume', 'index.js'),
		'terreo-casinha-bebelume/index': path.resolve(process.cwd(), 'src/blocks/terreo-casinha-bebelume', 'index.js'),
		'terreo-castelinho-bebelume/index': path.resolve(process.cwd(), 'src/blocks/terreo-castelinho-bebelume', 'index.js'),
	},
	output: {
		filename: '[name].js',
		path: path.resolve(process.cwd(), 'build/blocks'),
	},
};