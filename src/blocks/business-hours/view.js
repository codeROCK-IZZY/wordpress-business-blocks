/**
 * Recomputes the "Open now" badge in the browser, so it is right even when the page came from a cache.
 */
import { status } from '../../shared/hours';

function fmt( ms, tz, withDay ) {
	return new Intl.DateTimeFormat(
		document.documentElement.lang || undefined,
		{
			timeZone: tz,
			hour: 'numeric',
			minute: '2-digit',
			...( withDay ? { weekday: 'short' } : {} ),
		}
	).format( new Date( ms ) );
}

function refresh( el ) {
	let data;
	try {
		data = JSON.parse( el.getAttribute( 'data-hours' ) );
	} catch ( e ) {
		return;
	}
	const badge = el.querySelector( '.bb-hours-status' );
	if ( ! badge || ! data.tz ) {
		return;
	}
	const now = Date.now();
	const s = status( now, data.week, data.closures, data.tz );
	const soon = s.change && s.change - now < 24 * 3600 * 1000;
	badge.classList.toggle( 'is-open', s.open );
	badge.classList.toggle( 'is-closed', ! s.open );
	let text;
	if ( s.open ) {
		text = data.i18n.openUntil.replace(
			'%s',
			fmt( s.change, data.tz, false )
		);
	} else if ( s.change ) {
		text = data.i18n.closedOpens.replace(
			'%s',
			fmt( s.change, data.tz, ! soon )
		);
	} else {
		text = data.i18n.closed;
	}
	badge.textContent = text;
}

document.querySelectorAll( '.bb-hours[data-hours]' ).forEach( ( el ) => {
	refresh( el );
	setInterval( () => refresh( el ), 60 * 1000 );
} );
