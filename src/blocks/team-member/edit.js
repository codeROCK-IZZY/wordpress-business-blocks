import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import { TextControl, TextareaControl, Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export default function Edit( { attributes, setAttributes } ) {
	const { mediaId, name, role, bio, email, linkedin } = attributes;
	const image = useSelect(
		( select ) => ( mediaId ? select( 'core' ).getMedia( mediaId ) : null ),
		[ mediaId ]
	);
	return (
		<div { ...useBlockProps( { className: 'bb-person bb-editing' } ) }>
			<MediaUploadCheck>
				<MediaUpload
					allowedTypes={ [ 'image' ] }
					value={ mediaId }
					onSelect={ ( m ) => setAttributes( { mediaId: m.id } ) }
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open }>
							{ image ? (
								<img
									src={ image.source_url }
									alt=""
									className="bb-person-photo"
								/>
							) : (
								__( 'Choose photo', 'business-blocks' )
							) }
						</Button>
					) }
				/>
			</MediaUploadCheck>
			<TextControl
				label={ __( 'Name', 'business-blocks' ) }
				value={ name }
				onChange={ ( v ) => setAttributes( { name: v } ) }
			/>
			<TextControl
				label={ __( 'Role', 'business-blocks' ) }
				value={ role }
				onChange={ ( v ) => setAttributes( { role: v } ) }
			/>
			<TextareaControl
				label={ __( 'Short bio', 'business-blocks' ) }
				value={ bio }
				onChange={ ( v ) => setAttributes( { bio: v } ) }
			/>
			<TextControl
				label={ __( 'Email (optional)', 'business-blocks' ) }
				type="email"
				value={ email }
				onChange={ ( v ) => setAttributes( { email: v } ) }
			/>
			<TextControl
				label={ __( 'LinkedIn URL (optional)', 'business-blocks' ) }
				type="url"
				value={ linkedin }
				onChange={ ( v ) => setAttributes( { linkedin: v } ) }
			/>
		</div>
	);
}
