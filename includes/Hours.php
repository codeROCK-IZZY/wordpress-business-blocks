<?php
/**
 * Opening hours: is the business open at a moment, when does that change, and the schema.org description.
 *
 * Works in the site's time zone with wall-clock times, so 9:00 means 9:00 local time on both sides of a daylight
 * saving change. Supports overnight hours (22:00–02:00 belongs to the day it starts) and dated closures
 * (a closure cancels the opening that starts on that date).
 *
 * @package BusinessBlocks
 */

namespace BusinessBlocks;

/**
 * Pure logic, no WordPress calls.
 */
final class Hours {

	public const DAYS = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );

	/**
	 * Normalised weekly schedule: day => [open "HH:MM", close "HH:MM"] or null when closed.
	 *
	 * @var array<string, array{0: string, 1: string}|null>
	 */
	private array $week = array();

	/**
	 * Closed dates (Y-m-d) => label.
	 *
	 * @var array<string, string>
	 */
	private array $closures = array();

	/**
	 * Build from block attributes.
	 *
	 * @param list<array<string, mixed>> $days     [{day, open, close, closed}].
	 * @param list<array<string, mixed>> $closures [{date, label}].
	 * @param \DateTimeZone              $tz       Site time zone.
	 */
	public function __construct( array $days, array $closures, private readonly \DateTimeZone $tz ) {
		foreach ( self::DAYS as $d ) {
			$this->week[ $d ] = null;
		}
		foreach ( $days as $row ) {
			$day = (string) ( $row['day'] ?? '' );
			if ( ! array_key_exists( $day, $this->week ) || ! empty( $row['closed'] ) ) {
				continue;
			}
			$open  = self::clock( (string) ( $row['open'] ?? '' ) );
			$close = self::clock( (string) ( $row['close'] ?? '' ) );
			if ( null !== $open && null !== $close && $open !== $close ) {
				$this->week[ $day ] = array( $open, $close );
			}
		}
		foreach ( $closures as $c ) {
			$date = (string) ( $c['date'] ?? '' );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && checkdate( (int) substr( $date, 5, 2 ), (int) substr( $date, 8, 2 ), (int) substr( $date, 0, 4 ) ) ) {
				$this->closures[ $date ] = mb_substr( (string) ( $c['label'] ?? '' ), 0, 80 );
			}
		}
	}

	/**
	 * "9:5", "09:05", "9.05" → "09:05"; anything else → null.
	 *
	 * @param string $t Time.
	 */
	public static function clock( string $t ): ?string {
		if ( ! preg_match( '/^\s*(\d{1,2})[:.](\d{2})\s*$/', $t, $m ) || (int) $m[1] > 24 || (int) $m[2] > 59 || ( 24 === (int) $m[1] && 0 !== (int) $m[2] ) ) {
			return null;
		}
		return sprintf( '%02d:%02d', 24 === (int) $m[1] ? 0 : (int) $m[1], (int) $m[2] ); // 24:00 is midnight.
	}

	/**
	 * The opening that starts on a local date, as [start, end], or null.
	 *
	 * @param \DateTimeImmutable $date Any moment on that local date.
	 * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}|null
	 */
	public function opening_on( \DateTimeImmutable $date ): ?array {
		$local = $date->setTimezone( $this->tz );
		$ymd   = $local->format( 'Y-m-d' );
		$hours = $this->week[ $local->format( 'l' ) ] ?? null;
		if ( null === $hours || isset( $this->closures[ $ymd ] ) ) {
			return null;
		}
		// Overnight hours close on the next calendar date. Build that date first and only then attach the time:
		// "01:45 on the night the clocks go forward, plus a day" would be 02:45, because 01:45 did not exist.
		$end_ymd = $hours[1] <= $hours[0] ? ( new \DateTimeImmutable( $ymd . ' 12:00', new \DateTimeZone( 'UTC' ) ) )->modify( '+1 day' )->format( 'Y-m-d' ) : $ymd;
		$start   = new \DateTimeImmutable( $ymd . ' ' . $hours[0], $this->tz );
		$end     = new \DateTimeImmutable( $end_ymd . ' ' . $hours[1], $this->tz );
		return array( $start, $end );
	}

	/**
	 * Open or closed at a moment, and the next change.
	 *
	 * @param \DateTimeImmutable $now Moment.
	 * @return array{open: bool, change: \DateTimeImmutable|null, closure: string}
	 */
	public function status( \DateTimeImmutable $now ): array {
		$today = $now->setTimezone( $this->tz );
		foreach ( array( $today->modify( '-1 day' ), $today ) as $day ) {
			$o = $this->opening_on( $day );
			if ( null !== $o && $now >= $o[0] && $now < $o[1] ) {
				return array(
					'open'    => true,
					'change'  => $o[1],
					'closure' => '',
				);
			}
		}
		$next = null;
		for ( $i = 0; $i < 14 && null === $next; $i++ ) {
			$o = $this->opening_on( $today->modify( "+{$i} day" ) );
			if ( null !== $o && $o[0] > $now ) {
				$next = $o[0];
			}
		}
		return array(
			'open'    => false,
			'change'  => $next,
			'closure' => $this->closures[ $today->format( 'Y-m-d' ) ] ?? '',
		);
	}

	/**
	 * The weekly schedule for display.
	 *
	 * @return array<string, array{0: string, 1: string}|null>
	 */
	public function week(): array {
		return $this->week;
	}

	/**
	 * Dated closures from today on, soonest first.
	 *
	 * @param \DateTimeImmutable $now Now.
	 * @return array<string, string>
	 */
	public function upcoming_closures( \DateTimeImmutable $now ): array {
		$today = $now->setTimezone( $this->tz )->format( 'Y-m-d' );
		$out   = array_filter( $this->closures, static fn ( string $d ): bool => $d >= $today, ARRAY_FILTER_USE_KEY );
		ksort( $out );
		return $out;
	}

	/**
	 * schema.org OpeningHoursSpecification entries, grouped by identical hours.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function schema(): array {
		$groups = array();
		foreach ( $this->week as $day => $h ) {
			if ( null !== $h ) {
				$groups[ $h[0] . '-' . $h[1] ][] = $day;
			}
		}
		$out = array();
		foreach ( $groups as $key => $days ) {
			[ $open, $close ] = explode( '-', $key );
			$out[]            = array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => count( $days ) > 1 ? $days : $days[0],
				'opens'     => $open,
				'closes'    => $close,
			);
		}
		foreach ( $this->closures as $date => $label ) {
			$out[] = array(
				'@type'        => 'OpeningHoursSpecification',
				'opens'        => '00:00',
				'closes'       => '00:00',
				'validFrom'    => $date,
				'validThrough' => $date,
			);
		}
		return $out;
	}
}
