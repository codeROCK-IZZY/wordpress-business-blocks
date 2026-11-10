import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { TextareaControl, TextControl, Button } from '@wordpress/components';
import { updateItem, removeItem } from '../../shared/list';

export default function Edit( { attributes, setAttributes } ) {
	const { items } = attributes;
	const set = ( next ) => setAttributes( { items: next } );
	return (
		<div
			{ ...useBlockProps( { className: 'bb-testimonials bb-editing' } ) }
		>
			{ items.map( ( t, i ) => (
				<div className="bb-testimonial" key={ i }>
					<TextareaControl
						label={ __( 'Quote', 'business-blocks' ) }
						value={ t.quote }
						onChange={ ( v ) =>
							set( updateItem( items, i, { quote: v } ) )
						}
					/>
					<TextControl
						label={ __( 'Name', 'business-blocks' ) }
						value={ t.name }
						onChange={ ( v ) =>
							set( updateItem( items, i, { name: v } ) )
						}
					/>
					<TextControl
						label={ __( 'Role or location', 'business-blocks' ) }
						value={ t.role }
						onChange={ ( v ) =>
							set( updateItem( items, i, { role: v } ) )
						}
					/>
					<Button
						size="small"
						isDestructive
						icon="trash"
						label={ __( 'Remove', 'business-blocks' ) }
						disabled={ items.length === 1 }
						onClick={ () => set( removeItem( items, i ) ) }
					/>
				</div>
			) ) }
			<Button
				variant="secondary"
				onClick={ () =>
					set( [ ...items, { quote: '', name: '', role: '' } ] )
				}
			>
				{ __( 'Add testimonial', 'business-blocks' ) }
			</Button>
		</div>
	);
}
