import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { heading, text, button, url, phone } = attributes;
	return (
		<section { ...useBlockProps( { className: 'bb-cta' } ) }>
			<InspectorControls>
				<PanelBody title={ __( 'Button', 'business-blocks' ) }>
					<TextControl
						label={ __( 'Button link', 'business-blocks' ) }
						type="url"
						value={ url }
						onChange={ ( v ) => setAttributes( { url: v } ) }
					/>
					<TextControl
						label={ __(
							'Phone number (optional)',
							'business-blocks'
						) }
						type="tel"
						value={ phone }
						onChange={ ( v ) => setAttributes( { phone: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<RichText
				tagName="h2"
				className="bb-cta-heading"
				placeholder={ __( 'Heading…', 'business-blocks' ) }
				value={ heading }
				allowedFormats={ [] }
				onChange={ ( v ) => setAttributes( { heading: v } ) }
			/>
			<RichText
				tagName="p"
				className="bb-cta-text"
				placeholder={ __( 'A short line of text…', 'business-blocks' ) }
				value={ text }
				allowedFormats={ [ 'core/bold', 'core/italic' ] }
				onChange={ ( v ) => setAttributes( { text: v } ) }
			/>
			<RichText
				tagName="span"
				className="bb-cta-button wp-element-button"
				placeholder={ __( 'Button text', 'business-blocks' ) }
				value={ button }
				allowedFormats={ [] }
				onChange={ ( v ) => setAttributes( { button: v } ) }
			/>
		</section>
	);
}
