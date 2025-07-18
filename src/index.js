import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import './editor.css';
import Edit from './edit';
import save from './save';

registerBlockType('bf-secret-file-downloader/downloader', {
    edit: Edit,
    save,
});