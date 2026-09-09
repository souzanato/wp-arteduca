/**
 * Bloco Sobre Nós - Entry Point
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import './style.css';
import './editor.css';

registerBlockType('bebelume/sobre-nos', {
	edit: Edit,
	save,
});
