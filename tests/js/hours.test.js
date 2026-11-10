/**
 * The browser's "Open now" logic must agree with the PHP engine on every generated case, including the
 * daylight-saving weekends of eight time zones (cases from eval/hours_cases.php).
 */
import { status, zonedTime, localParts } from '../../src/shared/hours';
import cases from './fixtures/hours-cases.json';

describe( 'opening hours in the browser', () => {
	it( 'matches PHP on every generated case', () => {
		const mismatches = cases.filter( ( c ) => {
			const s = status( c.now, c.week, c.closures, c.tz );
			return s.open !== c.open || s.change !== c.change;
		} );
		expect( cases.length ).toBeGreaterThan( 3000 );
		expect( mismatches ).toEqual( [] );
	} );

	it( 'converts wall-clock times across a DST change', () => {
		// 09:00 in London on the Monday after the clocks went forward is 08:00 UTC.
		expect(
			new Date(
				zonedTime( 2026, 3, 30, 9, 0, 'Europe/London' )
			).toISOString()
		).toBe( '2026-03-30T08:00:00.000Z' );
		// 01:45 does exist the next night (closing time of an overnight opening).
		expect(
			new Date(
				zonedTime( 2026, 3, 30, 1, 45, 'Europe/London' )
			).toISOString()
		).toBe( '2026-03-30T00:45:00.000Z' );
	} );

	it( 'reads local parts in the target zone', () => {
		const p = localParts(
			Date.UTC( 2026, 10, 10, 23, 30 ),
			'Australia/Sydney'
		);
		expect( [ p.y, p.m, p.d, p.hh, p.mm, p.weekday ] ).toEqual( [
			2026,
			11,
			11,
			10,
			30,
			'Wednesday',
		] );
	} );
} );
