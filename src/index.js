import { registerBlockType } from '@wordpress/blocks';
import './editor.css';
import Edit from './edit';
import save from './save';

registerBlockType('bf-basic-guard/downloader', {
	edit: Edit,
	save,
});