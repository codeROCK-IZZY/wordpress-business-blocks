import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
	Button,
	CheckboxControl,
} from '@wordpress/components';
import { updateItem, removeItem } from '../../shared/list';

const TYPES = [
	'LocalBusiness',
	'Dentist',
	'MedicalClinic',
	'VeterinaryCare',
	'Restaurant',
	'CafeOrCoffeeShop',
	'Store',
	'HairSalon',
	'AutoRepair',
	'LegalService',
	'ProfessionalService',
];

export default function Edit( { attributes, setAttributes } ) {
	const { days, closures, showStatus, schema, businessType, phone } =
		attributes;

	return (
		<div { ...useBlockProps( { className: 'bb-hours bb-editing' } ) }>
			<InspectorControls>
				<PanelBody title={ __( 'Display', 'business-blocks' ) }>
					<ToggleControl
						label={ __(
							'Show "Open now" badge',
							'business-blocks'
						) }
						checked={ showStatus }
						onChange={ ( v ) => setAttributes( { showStatus: v } ) }
					/>
					<ToggleControl
						label={ __(
							'Add LocalBusiness structured data',
							'business-blocks'
						) }
						checked={ schema }
						onChange={ ( v ) => setAttributes( { schema: v } ) }
					/>
					<SelectControl
						label={ __( 'Business type', 'business-blocks' ) }
						value={ businessType }
						options={ TYPES.map( ( t ) => ( {
							label: t,
							value: t,
						} ) ) }
						onChange={ ( v ) =>
							setAttributes( { businessType: v } )
						}
					/>
					<TextControl
						label={ __( 'Phone (optional)', 'business-blocks' ) }
						type="tel"
						value={ phone }
						onChange={ ( v ) => setAttributes( { phone: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<table className="bb-hours-edit">
				<thead>
					<tr>
						<th scope="col">{ __( 'Day', 'business-blocks' ) }</th>
						<th scope="col">
							{ __( 'Opens', 'business-blocks' ) }
						</th>
						<th scope="col">
							{ __( 'Closes', 'business-blocks' ) }
						</th>
						<th scope="col">
							{ __( 'Closed', 'business-blocks' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{ days.map( ( row, i ) => (
						<tr key={ row.day }>
							<th scope="row">{ row.day }</th>
							<td>
								<input
									type="time"
									aria-label={ `${ row.day } ${ __(
										'opens',
										'business-blocks'
									) }` }
									value={ row.open }
									disabled={ row.closed }
									onChange={ ( e ) =>
										setAttributes( {
											days: updateItem( days, i, {
												open: e.target.value,
											} ),
										} )
									}
								/>
							</td>
							<td>
								<input
									type="time"
									aria-label={ `${ row.day } ${ __(
										'closes',
										'business-blocks'
									) }` }
									value={ row.close }
									disabled={ row.closed }
									onChange={ ( e ) =>
										setAttributes( {
											days: updateItem( days, i, {
												close: e.target.value,
											} ),
										} )
									}
								/>
							</td>
							<td>
								<CheckboxControl
									__nextHasNoMarginBottom
									label={ __( 'Closed', 'business-blocks' ) }
									hideLabelFromVision
									checked={ row.closed }
									onChange={ ( v ) =>
										setAttributes( {
											days: updateItem( days, i, {
												closed: v,
											} ),
										} )
									}
								/>
							</td>
						</tr>
					) ) }
				</tbody>
			</table>
			<p className="bb-hint">
				{ __(
					'A closing time earlier than the opening time means the business closes after midnight.',
					'business-blocks'
				) }
			</p>
			<h4>{ __( 'Holiday closures', 'business-blocks' ) }</h4>
			{ closures.map( ( c, i ) => (
				<div className="bb-closure-edit" key={ i }>
					<input
						type="date"
						aria-label={ __( 'Date', 'business-blocks' ) }
						value={ c.date }
						onChange={ ( e ) =>
							setAttributes( {
								closures: updateItem( closures, i, {
									date: e.target.value,
								} ),
							} )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						hideLabelFromVision
						label={ __( 'Label', 'business-blocks' ) }
						placeholder={ __(
							'e.g. Christmas Day',
							'business-blocks'
						) }
						value={ c.label }
						onChange={ ( v ) =>
							setAttributes( {
								closures: updateItem( closures, i, {
									label: v,
								} ),
							} )
						}
					/>
					<Button
						size="small"
						isDestructive
						icon="trash"
						label={ __( 'Remove', 'business-blocks' ) }
						onClick={ () =>
							setAttributes( {
								closures: removeItem( closures, i ),
							} )
						}
					/>
				</div>
			) ) }
			<Button
				variant="secondary"
				onClick={ () =>
					setAttributes( {
						closures: [ ...closures, { date: '', label: '' } ],
					} )
				}
			>
				{ __( 'Add a closure', 'business-blocks' ) }
			</Button>
		</div>
	);
}
