<?php
/**
 * Business hours: weekly table, holiday closures, an "Open now" badge (rendered here for visitors without
 * JavaScript and refreshed in the browser for cached pages), and LocalBusiness JSON-LD.
 *
 * @package BusinessBlocks
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

use BusinessBlocks\Hours;
use BusinessBlocks\Schema;

$bb_tz    = wp_timezone();
$bb_hours = new Hours( (array) ( $attributes['days'] ?? array() ), (array) ( $attributes['closures'] ?? array() ), $bb_tz );
$bb_now   = new DateTimeImmutable( 'now', $bb_tz );
$bb_state = $bb_hours->status( $bb_now );
$bb_fmt   = static fn ( string $hhmm ): string => wp_date( (string) get_option( 'time_format' ), ( new DateTimeImmutable( '2000-01-03 ' . $hhmm, $bb_tz ) )->getTimestamp(), $bb_tz );

if ( $bb_state['open'] && $bb_state['change'] ) {
	/* translators: %s: closing time */
	$bb_label = sprintf( __( 'Open now · closes %s', 'business-blocks' ), wp_date( (string) get_option( 'time_format' ), $bb_state['change']->getTimestamp(), $bb_tz ) );
} elseif ( $bb_state['change'] ) {
	$bb_soon = $bb_state['change']->getTimestamp() - $bb_now->getTimestamp() < DAY_IN_SECONDS;
	/* translators: %s: opening day and time */
	$bb_label = sprintf( __( 'Closed · opens %s', 'business-blocks' ), wp_date( ( $bb_soon ? '' : 'D ' ) . get_option( 'time_format' ), $bb_state['change']->getTimestamp(), $bb_tz ) );
} else {
	$bb_label = __( 'Closed', 'business-blocks' );
}
if ( '' !== $bb_state['closure'] ) {
	$bb_label .= ' (' . $bb_state['closure'] . ')';
}

$bb_client = array(
	'tz'       => $bb_tz->getName(),
	'week'     => $bb_hours->week(),
	'closures' => $bb_hours->upcoming_closures( $bb_now->modify( '-1 day' ) ),
	'i18n'     => array(
		/* translators: %s: time */
		'openUntil'   => __( 'Open now · closes %s', 'business-blocks' ),
		/* translators: %s: day and time */
		'closedOpens' => __( 'Closed · opens %s', 'business-blocks' ),
		'closed'      => __( 'Closed', 'business-blocks' ),
	),
);
// A UTC offset such as "+02:00" has no daylight saving rules; browsers need an IANA name to recompute safely.
$bb_iana   = str_contains( $bb_client['tz'], '/' ) || 'UTC' === $bb_client['tz'];
$bb_today  = $bb_now->format( 'l' );
$bb_extras = array( 'class' => 'bb-hours' );
if ( ! empty( $attributes['showStatus'] ) && $bb_iana ) {
	$bb_extras['data-hours'] = (string) wp_json_encode( $bb_client );
}
?>
<div <?php echo get_block_wrapper_attributes( $bb_extras ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes wrapper attributes. ?>>
	<?php if ( ! empty( $attributes['showStatus'] ) ) : ?>
		<p class="bb-hours-status <?php echo $bb_state['open'] ? 'is-open' : 'is-closed'; ?>" role="status"><?php echo esc_html( $bb_label ); ?></p>
	<?php endif; ?>
	<table class="bb-hours-table">
		<caption class="screen-reader-text"><?php esc_html_e( 'Opening hours', 'business-blocks' ); ?></caption>
		<tbody>
			<?php foreach ( $bb_hours->week() as $bb_day => $bb_h ) : ?>
				<tr<?php echo $bb_day === $bb_today ? ' class="is-today" aria-current="date"' : ''; ?>>
					<th scope="row"><?php echo esc_html( date_i18n( 'l', (int) strtotime( 'next ' . $bb_day ) ) ); ?></th>
					<td><?php echo null === $bb_h ? esc_html__( 'Closed', 'business-blocks' ) : esc_html( $bb_fmt( $bb_h[0] ) . ' – ' . $bb_fmt( $bb_h[1] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php $bb_closed = $bb_hours->upcoming_closures( $bb_now ); ?>
	<?php if ( $bb_closed ) : ?>
		<p class="bb-hours-closures-title"><?php esc_html_e( 'Upcoming closures', 'business-blocks' ); ?></p>
		<ul class="bb-hours-closures">
			<?php foreach ( array_slice( $bb_closed, 0, 6, true ) as $bb_date => $bb_why ) : ?>
				<li><time datetime="<?php echo esc_attr( $bb_date ); ?>"><?php echo esc_html( wp_date( (string) get_option( 'date_format' ), ( new DateTimeImmutable( $bb_date, $bb_tz ) )->getTimestamp(), $bb_tz ) ); ?></time><?php echo '' !== $bb_why ? ' · ' . esc_html( $bb_why ) : ''; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( ! empty( $attributes['phone'] ) ) : ?>
		<p class="bb-hours-phone"><a href="tel:<?php echo esc_attr( (string) preg_replace( '/[^0-9+]/', '', (string) $attributes['phone'] ) ); ?>"><?php echo esc_html( (string) $attributes['phone'] ); ?></a></p>
	<?php endif; ?>
</div>
<?php
if ( ! empty( $attributes['schema'] ) ) {
	echo Schema::script( Schema::business( wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ), (string) ( $attributes['businessType'] ?? 'LocalBusiness' ), $bb_hours, home_url( '/' ), (string) ( $attributes['phone'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with JSON_HEX_TAG.
}
