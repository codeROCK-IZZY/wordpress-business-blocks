import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
	Button,
} from '@wordpress/components';
import { updateItem, removeItem, moveItem } from '../../shared/list';

export default function Edit( { attributes, setAttributes } ) {
	const { plans, currency } = attributes;
	const set = ( next ) => setAttributes( { plans: next } );
	return (
		<div { ...useBlockProps( { className: 'bb-pricing bb-editing' } ) }>
			<InspectorControls>
				<PanelBody title={ __( 'Pricing', 'business-blocks' ) }>
					<TextControl
						label={ __( 'Currency symbol', 'business-blocks' ) }
						value={ currency }
						onChange={ ( v ) => setAttributes( { currency: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			{ plans.map( ( p, i ) => (
				<div
					className={ `bb-plan${
						p.highlight ? ' is-highlighted' : ''
					}` }
					key={ i }
				>
					<TextControl
						label={ __( 'Plan name', 'business-blocks' ) }
						value={ p.name }
						onChange={ ( v ) =>
							set( updateItem( plans, i, { name: v } ) )
						}
					/>
					<TextControl
						label={ __( 'Price', 'business-blocks' ) }
						value={ p.price }
						onChange={ ( v ) =>
							set( updateItem( plans, i, { price: v } ) )
						}
					/>
					<TextControl
						label={ __( 'Per (e.g. month)', 'business-blocks' ) }
						value={ p.period }
						onChange={ ( v ) =>
							set( updateItem( plans, i, { period: v } ) )
						}
					/>
					<TextareaControl
						label={ __(
							'Features, one per line',
							'business-blocks'
						) }
						value={ p.features }
						onChange={ ( v ) =>
							set( updateItem( plans, i, { features: v } ) )
						}
					/>
					<TextControl
						label={ __( 'Button text', 'business-blocks' ) }
						value={ p.button }
						onChange={ ( v ) =>
							set( updateItem( plans, i, { button: v } ) )
						}
					/>
					<TextControl
						label={ __( 'Button link', 'business-blocks' ) }
						type="url"
						value={ p.url }
						onChange={ ( v ) =>
							set( updateItem( plans, i, { url: v } ) )
						}
					/>
					<ToggleControl
						label={ __( 'Highlight this plan', 'business-blocks' ) }
						checked={ p.highlight }
						onChange={ ( v ) =>
							set( updateItem( plans, i, { highlight: v } ) )
						}
					/>
					<div className="bb-row-actions">
						<Button
							size="small"
							icon="arrow-left-alt2"
							label={ __( 'Move left', 'business-blocks' ) }
							disabled={ i === 0 }
							onClick={ () => set( moveItem( plans, i, -1 ) ) }
						/>
						<Button
							size="small"
							icon="arrow-right-alt2"
							label={ __( 'Move right', 'business-blocks' ) }
							disabled={ i === plans.length - 1 }
							onClick={ () => set( moveItem( plans, i, 1 ) ) }
						/>
						<Button
							size="small"
							isDestructive
							icon="trash"
							label={ __( 'Remove plan', 'business-blocks' ) }
							disabled={ plans.length === 1 }
							onClick={ () => set( removeItem( plans, i ) ) }
						/>
					</div>
				</div>
			) ) }
			<Button
				variant="secondary"
				onClick={ () =>
					set( [
						...plans,
						{
							name: '',
							price: '',
							period: 'month',
							features: '',
							button: '',
							url: '',
							highlight: false,
						},
					] )
				}
			>
				{ __( 'Add plan', 'business-blocks' ) }
			</Button>
		</div>
	);
}
