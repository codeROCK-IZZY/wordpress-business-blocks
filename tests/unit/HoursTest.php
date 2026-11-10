<?php
/**
 * Opening hours: hand-picked cases, then random schedules checked against a minute-by-minute reference in real
 * time zones across daylight-saving changes.
 *
 * @package BusinessBlocks
 */

namespace BusinessBlocks\Tests;

use BusinessBlocks\Hours;
use PHPUnit\Framework\TestCase;

final class HoursTest extends TestCase {

	private function week( array $overrides = array() ): array {
		$days = array();
		foreach ( Hours::DAYS as $d ) {
			$days[] = $overrides[ $d ] ?? array(
				'day'    => $d,
				'open'   => '09:00',
				'close'  => '17:00',
				'closed' => false,
			);
		}
		return $days;
	}

	public function test_open_and_closed_with_next_change(): void {
		$tz = new \DateTimeZone( 'Europe/London' );
		$h  = new Hours(
			$this->week(
				array(
					'Sunday' => array(
						'day'    => 'Sunday',
						'closed' => true,
					),
				)
			),
			array(),
			$tz
		);
		$s  = $h->status( new \DateTimeImmutable( '2026-11-10 10:30', $tz ) ); // Tuesday.
		$this->assertTrue( $s['open'] );
		$this->assertSame( '2026-11-10 17:00', $s['change']->format( 'Y-m-d H:i' ) );
		$s = $h->status( new \DateTimeImmutable( '2026-11-14 18:00', $tz ) ); // Saturday evening → Monday 9:00.
		$this->assertFalse( $s['open'] );
		$this->assertSame( '2026-11-16 09:00', $s['change']->format( 'Y-m-d H:i' ) );
		$this->assertFalse( $h->status( new \DateTimeImmutable( '2026-11-10 17:00', $tz ) )['open'], 'closing time is exclusive' );
		$this->assertTrue( $h->status( new \DateTimeImmutable( '2026-11-10 09:00', $tz ) )['open'], 'opening time is inclusive' );
	}

	public function test_overnight_hours_belong_to_the_day_they_start(): void {
		$tz = new \DateTimeZone( 'America/New_York' );
		$h  = new Hours(
			$this->week(
				array(
					'Friday'   => array(
						'day'   => 'Friday',
						'open'  => '18:00',
						'close' => '02:00',
					),
					'Saturday' => array(
						'day'    => 'Saturday',
						'closed' => true,
					),
				)
			),
			array(),
			$tz
		);
		$this->assertTrue( $h->status( new \DateTimeImmutable( '2026-11-14 01:30', $tz ) )['open'], 'Saturday 1:30 am is Friday night' );
		$this->assertFalse( $h->status( new \DateTimeImmutable( '2026-11-14 02:30', $tz ) )['open'] );
	}

	public function test_closures_cancel_the_opening_that_starts_that_day(): void {
		$tz = new \DateTimeZone( 'Europe/London' );
		$h  = new Hours(
			$this->week(),
			array(
				array(
					'date'  => '2026-12-25',
					'label' => 'Christmas Day',
				),
			),
			$tz
		);
		$s  = $h->status( new \DateTimeImmutable( '2026-12-25 11:00', $tz ) );
		$this->assertFalse( $s['open'] );
		$this->assertSame( 'Christmas Day', $s['closure'] );
		$this->assertSame( '2026-12-26 09:00', $s['change']->format( 'Y-m-d H:i' ) );
	}

	public function test_times_are_normalised_and_bad_input_ignored(): void {
		$this->assertSame( '09:05', Hours::clock( '9:05' ) );
		$this->assertSame( '09:05', Hours::clock( '9.05' ) );
		$this->assertSame( '00:00', Hours::clock( '24:00' ) );
		$this->assertNull( Hours::clock( '25:00' ) );
		$this->assertNull( Hours::clock( 'noon' ) );
		$h = new Hours(
			array(
				array(
					'day'   => 'Funday',
					'open'  => '09:00',
					'close' => '17:00',
				),
				array(
					'day'   => 'Monday',
					'open'  => 'x',
					'close' => '17:00',
				),
			),
			array( array( 'date' => '2026-02-30' ) ),
			new \DateTimeZone( 'UTC' )
		);
		$this->assertSame( array(), array_filter( $h->week() ) );
		$this->assertSame( array(), $h->upcoming_closures( new \DateTimeImmutable( '2026-01-01' ) ) );
	}

	public function test_schema_groups_identical_days(): void {
		$h      = new Hours(
			$this->week(
				array(
					'Saturday' => array(
						'day'   => 'Saturday',
						'open'  => '10:00',
						'close' => '14:00',
					),
					'Sunday'   => array(
						'day'    => 'Sunday',
						'closed' => true,
					),
				)
			),
			array( array( 'date' => '2026-12-25' ) ),
			new \DateTimeZone( 'UTC' )
		);
		$schema = $h->schema();
		$this->assertSame( array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ), $schema[0]['dayOfWeek'] );
		$this->assertSame( 'Saturday', $schema[1]['dayOfWeek'] );
		$this->assertSame( '2026-12-25', $schema[2]['validFrom'] );
	}

	/**
	 * Minute-by-minute reference: walk local wall-clock minutes from two days before, tracking open intervals
	 * built by string arithmetic on local dates, with no DateTimeImmutable::modify and no shared code.
	 */
	private static function reference_open( array $days, array $closed, \DateTimeZone $tz, int $ts ): bool {
		$map = array();
		foreach ( $days as $d ) {
			if ( empty( $d['closed'] ) ) {
				$map[ $d['day'] ] = array( $d['open'], $d['close'] );
			}
		}
		$today = date_create_immutable( '@' . $ts )->setTimezone( $tz )->format( 'Y-m-d' );
		foreach ( array( gmdate( 'Y-m-d', (int) strtotime( $today . ' 00:00 UTC -1 day' ) ), $today ) as $ymd ) {
			$day = gmdate( 'l', (int) strtotime( $ymd . ' 12:00 UTC' ) );
			if ( isset( $closed[ $ymd ] ) || ! isset( $map[ $day ] ) ) {
				continue;
			}
			[ $o, $c ] = $map[ $day ];
			$start     = date_create_immutable( $ymd . 'T' . $o . ':00', $tz )->getTimestamp();
			$end_date  = $c <= $o ? gmdate( 'Y-m-d', (int) strtotime( $ymd . ' 12:00 UTC +1 day' ) ) : $ymd;
			$end       = date_create_immutable( $end_date . 'T' . $c . ':00', $tz )->getTimestamp();
			if ( $ts >= $start && $ts < $end ) {
				return true;
			}
		}
		return false;
	}

	public function test_random_schedules_match_the_reference_across_dst(): void {
		mt_srand( 11 );
		$zones = array( 'Europe/London', 'America/New_York', 'Australia/Sydney', 'Asia/Kolkata', 'UTC' );
		// Include the DST change weekends for these zones in 2026.
		$anchors = array( '2026-03-29 00:30', '2026-03-08 01:30', '2026-10-25 00:30', '2026-11-01 00:30', '2026-04-05 01:30', '2026-07-15 12:00' );
		$checked = 0;
		for ( $case = 0; $case < 200; $case++ ) {
			$tz   = new \DateTimeZone( $zones[ $case % count( $zones ) ] );
			$days = array();
			foreach ( Hours::DAYS as $d ) {
				$closed = 0 === mt_rand( 0, 5 );
				$o      = sprintf( '%02d:%02d', mt_rand( 0, 23 ), 15 * mt_rand( 0, 3 ) );
				$c      = sprintf( '%02d:%02d', mt_rand( 0, 23 ), 15 * mt_rand( 0, 3 ) );
				$days[] = array(
					'day'    => $d,
					'open'   => $o,
					'close'  => $c === $o ? '23:45' : $c,
					'closed' => $closed,
				);
			}
			$base    = new \DateTimeImmutable( $anchors[ $case % count( $anchors ) ], $tz );
			$closed  = array( $base->modify( '+1 day' )->format( 'Y-m-d' ) => 'Holiday' );
			$h       = new Hours(
				$days,
				array(
					array(
						'date'  => array_key_first( $closed ),
						'label' => 'Holiday',
					),
				),
				$tz
			);
			$compare = array();
			foreach ( $days as $d ) {
				$compare[] = array(
					'day'    => $d['day'],
					'open'   => Hours::clock( $d['open'] ),
					'close'  => Hours::clock( $d['close'] ),
					'closed' => $d['closed'],
				);
			}
			for ( $m = 0; $m < 72 * 60; $m += 37 ) {
				$ts = $base->getTimestamp() + $m * 60;
				$this->assertSame(
					self::reference_open( $compare, $closed, $tz, $ts ),
					$h->status( ( new \DateTimeImmutable( '@' . $ts ) )->setTimezone( $tz ) )['open'],
					sprintf( 'case %d at %s %s', $case, $tz->getName(), ( new \DateTimeImmutable( '@' . $ts ) )->setTimezone( $tz )->format( 'D Y-m-d H:i T' ) )
				);
				++$checked;
			}
		}
		$this->assertGreaterThan( 20000, $checked );
	}
}
