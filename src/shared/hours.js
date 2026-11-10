/**
 * Opening-hours status in the browser, mirroring includes/Hours.php, so the "Open now" badge stays correct on
 * cached pages. Wall-clock times are interpreted in the site's IANA time zone with Intl, so daylight saving
 * changes are handled the same way as in PHP. Cross-checked against PHP on generated cases (tests/js).
 */

const DAYS = [
	'Monday',
	'Tuesday',
	'Wednesday',
	'Thursday',
	'Friday',
	'Saturday',
	'Sunday',
];

/**
 * Local calendar parts of an instant in a time zone.
 *
 * @param {number} ms Epoch milliseconds.
 * @param {string} tz IANA time zone.
 * @return {{y:number,m:number,d:number,hh:number,mm:number,weekday:string}} Parts.
 */
export function localParts( ms, tz ) {
	const f = new Intl.DateTimeFormat( 'en-US', {
		timeZone: tz,
		year: 'numeric',
		month: 'numeric',
		day: 'numeric',
		hour: 'numeric',
		minute: 'numeric',
		weekday: 'long',
		hourCycle: 'h23',
	} );
	const p = {};
	for ( const { type, value } of f.formatToParts( new Date( ms ) ) ) {
		p[ type ] = value;
	}
	return {
		y: +p.year,
		m: +p.month,
		d: +p.day,
		hh: +p.hour,
		mm: +p.minute,
		weekday: p.weekday,
	};
}

/**
 * Epoch milliseconds of a local wall-clock time in a time zone, resolved the way PHP's DateTime does:
 * a time in the autumn overlap takes the earlier (summer-time) reading; a time in the spring gap, which does not
 * exist, is read with the offset in force before the change (01:45 becomes 02:45 summer time).
 *
 * @param {number} y  Year.
 * @param {number} m  Month (1-12).
 * @param {number} d  Day.
 * @param {number} hh Hour.
 * @param {number} mm Minute.
 * @param {string} tz IANA time zone.
 * @return {number} Epoch milliseconds.
 */
export function zonedTime( y, m, d, hh, mm, tz ) {
	const wall = Date.UTC( y, m - 1, d, hh, mm );
	const offset = ( t ) => {
		const p = localParts( t, tz );
		return Date.UTC( p.y, p.m - 1, p.d, p.hh, p.mm ) - t;
	};
	const before = offset( wall - 12 * 3600 * 1000 );
	const after = offset( wall + 12 * 3600 * 1000 );
	const matches = [ before, after ]
		.map( ( o ) => wall - o )
		.filter( ( t ) => offset( t ) === wall - t );
	if ( matches.length ) {
		return Math.min( ...matches );
	}
	return wall - before;
}

function addDays( y, m, d, n ) {
	const dt = new Date( Date.UTC( y, m - 1, d + n ) );
	return {
		y: dt.getUTCFullYear(),
		m: dt.getUTCMonth() + 1,
		d: dt.getUTCDate(),
		weekday: DAYS[ ( dt.getUTCDay() + 6 ) % 7 ],
	};
}

function ymd( p ) {
	return `${ p.y }-${ String( p.m ).padStart( 2, '0' ) }-${ String(
		p.d
	).padStart( 2, '0' ) }`;
}

/**
 * The opening that starts on a local date.
 *
 * @param {Object} day      {y,m,d,weekday}.
 * @param {Object} week     Day name => [open, close] or null.
 * @param {Object} closures Y-m-d => label.
 * @param {string} tz       Time zone.
 * @return {number[]|null} [start, end] in epoch ms.
 */
export function openingOn( day, week, closures, tz ) {
	const hours = week[ day.weekday ];
	if ( ! hours || closures[ ymd( day ) ] !== undefined ) {
		return null;
	}
	const [ oh, om ] = hours[ 0 ].split( ':' ).map( Number );
	const [ ch, cm ] = hours[ 1 ].split( ':' ).map( Number );
	const start = zonedTime( day.y, day.m, day.d, oh, om, tz );
	const endDay =
		hours[ 1 ] <= hours[ 0 ] ? addDays( day.y, day.m, day.d, 1 ) : day;
	const end = zonedTime( endDay.y, endDay.m, endDay.d, ch, cm, tz );
	return [ start, end ];
}

/**
 * Open or closed now, and the next change.
 *
 * @param {number} now      Epoch ms.
 * @param {Object} week     Day name => [open, close] or null.
 * @param {Object} closures Y-m-d => label.
 * @param {string} tz       Time zone.
 * @return {{open: boolean, change: number|null}} Status.
 */
export function status( now, week, closures, tz ) {
	const p = localParts( now, tz );
	const today = { y: p.y, m: p.m, d: p.d, weekday: p.weekday };
	for ( const day of [ addDays( p.y, p.m, p.d, -1 ), today ] ) {
		const o = openingOn( day, week, closures, tz );
		if ( o && now >= o[ 0 ] && now < o[ 1 ] ) {
			return { open: true, change: o[ 1 ] };
		}
	}
	for ( let i = 0; i < 14; i++ ) {
		const o = openingOn( addDays( p.y, p.m, p.d, i ), week, closures, tz );
		if ( o && o[ 0 ] > now ) {
			return { open: false, change: o[ 0 ] };
		}
	}
	return { open: false, change: null };
}
