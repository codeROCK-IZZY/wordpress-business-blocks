import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	RichText,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Button } from '@wordpress/components';
import { updateItem, removeItem, moveItem } from '../../shared/list';

export default function Edit( { attributes, setAttributes } ) {
	const { items, openFirst, schema } = attributes;
	const set = ( next ) => setAttributes( { items: next } );

	return (
		<div { ...useBlockProps( { className: 'bb-faq bb-editing' } ) }>
			<InspectorControls>
				<PanelBody title={ __( 'FAQ settings', 'business-blocks' ) }>
					<ToggleControl
						label={ __(
							'Open the first question',
							'business-blocks'
						) }
						checked={ openFirst }
						onChange={ ( v ) => setAttributes( { openFirst: v } ) }
					/>
					<ToggleControl
						label={ __(
							'Add FAQPage structured data',
							'business-blocks'
						) }
						help={ __(
							'Helps search engines understand the questions. Use only one FAQ block with this on per page.',
							'business-blocks'
						) }
						checked={ schema }
						onChange={ ( v ) => setAttributes( { schema: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			{ items.map( ( item, i ) => (
				<div className="bb-faq-edit" key={ i }>
					<RichText
						tagName="p"
						className="bb-faq-question"
						aria-label={ __( 'Question', 'business-blocks' ) }
						placeholder={ __( 'Question…', 'business-blocks' ) }
						value={ item.question }
						allowedFormats={ [] }
						onChange={ ( v ) =>
							set( updateItem( items, i, { question: v } ) )
						}
					/>
					<RichText
						tagName="div"
						multiline="p"
						className="bb-faq-answer"
						aria-label={ __( 'Answer', 'business-blocks' ) }
						placeholder={ __( 'Answer…', 'business-blocks' ) }
						value={ item.answer }
						allowedFormats={ [
							'core/bold',
							'core/italic',
							'core/link',
						] }
						onChange={ ( v ) =>
							set( updateItem( items, i, { answer: v } ) )
						}
					/>
					<div className="bb-row-actions">
						<Button
							size="small"
							icon="arrow-up-alt2"
							label={ __( 'Move up', 'business-blocks' ) }
							disabled={ i === 0 }
							onClick={ () => set( moveItem( items, i, -1 ) ) }
						/>
						<Button
							size="small"
							icon="arrow-down-alt2"
							label={ __( 'Move down', 'business-blocks' ) }
							disabled={ i === items.length - 1 }
							onClick={ () => set( moveItem( items, i, 1 ) ) }
						/>
						<Button
							size="small"
							isDestructive
							icon="trash"
							label={ __( 'Remove question', 'business-blocks' ) }
							disabled={ items.length === 1 }
							onClick={ () => set( removeItem( items, i ) ) }
						/>
					</div>
				</div>
			) ) }
			<Button
				variant="secondary"
				onClick={ () =>
					set( [ ...items, { question: '', answer: '' } ] )
				}
			>
				{ __( 'Add question', 'business-blocks' ) }
			</Button>
		</div>
	);
}
