<?php
/**
 * Writes seeded opening-hours cases computed by the PHP engine to tests/js/fixtures/hours-cases.json.
 * The Jest test runs the browser implementation on the same inputs and must agree on every case.
 *
 * Usage: php eval/hours_cases.php
 *
 * @package BusinessBlocks
 */

require dirname( __DIR__ ) . '/includes/Hours.php';

use BusinessBlocks\Hours;

mt_srand( 2026 );
$zones   = array( 'Europe/London', 'America/New_York', 'America/Los_Angeles', 'Australia/Sydney', 'Asia/Kolkata', 'Europe/Berlin', 'America/Sao_Paulo', 'UTC' );
$anchors = array( '2026-03-29 00:00', '2026-03-08 00:00', '2026-10-25 00:00', '2026-11-01 00:00', '2026-04-05 00:00', '2026-10-04 00:00', '2026-06-15 00:00', '2026-12-24 00:00' );
$cases   = array();
for ( $i = 0; $i < 300; $i++ ) {
	$tz   = new DateTimeZone( $zones[ $i % count( $zones ) ] );
	$days = array();
	foreach ( Hours::DAYS as $d ) {
		$o      = sprintf( '%02d:%02d', mt_rand( 0, 23 ), 15 * mt_rand( 0, 3 ) );
		$c      = sprintf( '%02d:%02d', mt_rand( 0, 23 ), 15 * mt_rand( 0, 3 ) );
		$days[] = array(
			'day'    => $d,
			'open'   => $o,
			'close'  => $c === $o ? '23:45' : $c,
			'closed' => 0 === mt_rand( 0, 5 ),
		);
	}
	$base     = new DateTimeImmutable( $anchors[ $i % count( $anchors ) ], $tz );
	$closures = array(
		array(
			'date'  => $base->modify( '+' . mt_rand( 0, 2 ) . ' day' )->format( 'Y-m-d' ),
			'label' => 'Holiday',
		),
	);
	$h        = new Hours( $days, $closures, $tz );
	$closed   = array();
	foreach ( $closures as $c ) {
		$closed[ $c['date'] ] = $c['label'];
	}
	for ( $k = 0; $k < 12; $k++ ) {
		$now     = $base->getTimestamp() + mt_rand( 0, 3 * 86400 );
		$s       = $h->status( ( new DateTimeImmutable( '@' . $now ) )->setTimezone( $tz ) );
		$cases[] = array(
			'tz'       => $tz->getName(),
			'week'     => $h->week(),
			'closures' => $closed,
			'now'      => $now * 1000,
			'open'     => $s['open'],
			'change'   => null === $s['change'] ? null : $s['change']->getTimestamp() * 1000,
		);
	}
}
$dir = dirname( __DIR__ ) . '/tests/js/fixtures';
if ( ! is_dir( $dir ) ) {
	mkdir( $dir, 0777, true );
}
file_put_contents( $dir . '/hours-cases.json', json_encode( $cases ) . "\n" );
echo count( $cases ) . " cases written\n";
