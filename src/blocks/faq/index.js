import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import './style.scss';

// Dynamic block: rendered in PHP, so changing the markup never invalidates saved posts.
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
