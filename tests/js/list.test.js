import { updateItem, removeItem, moveItem } from '../../src/shared/list';

describe( 'list helpers', () => {
	const list = [ { a: 1 }, { a: 2 }, { a: 3 } ];

	it( 'updates one item without mutating the list', () => {
		const next = updateItem( list, 1, { a: 9, b: true } );
		expect( next ).toEqual( [ { a: 1 }, { a: 9, b: true }, { a: 3 } ] );
		expect( list[ 1 ] ).toEqual( { a: 2 } );
	} );

	it( 'removes an item', () => {
		expect( removeItem( list, 0 ) ).toEqual( [ { a: 2 }, { a: 3 } ] );
	} );

	it( 'moves items and ignores moves past either end', () => {
		expect( moveItem( list, 0, 1 ).map( ( x ) => x.a ) ).toEqual( [
			2, 1, 3,
		] );
		expect( moveItem( list, 2, -1 ).map( ( x ) => x.a ) ).toEqual( [
			1, 3, 2,
		] );
		expect( moveItem( list, 0, -1 ) ).toBe( list );
		expect( moveItem( list, 2, 1 ) ).toBe( list );
	} );
} );
