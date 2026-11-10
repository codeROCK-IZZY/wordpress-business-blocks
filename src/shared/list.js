/**
 * Immutable helpers for list attributes (FAQ items, plans, days...). Pure functions, unit-tested with Jest.
 */

export function updateItem( list, index, patch ) {
	return list.map( ( item, i ) =>
		i === index ? { ...item, ...patch } : item
	);
}

export function removeItem( list, index ) {
	return list.filter( ( _, i ) => i !== index );
}

export function moveItem( list, index, by ) {
	const to = index + by;
	if ( to < 0 || to >= list.length ) {
		return list;
	}
	const next = [ ...list ];
	[ next[ index ], next[ to ] ] = [ next[ to ], next[ index ] ];
	return next;
}
