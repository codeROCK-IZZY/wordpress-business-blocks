<?php
/**
 * Pricing table.
 *
 * @package BusinessBlocks
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

$bb_plans = array_values( array_filter( (array) ( $attributes['plans'] ?? array() ), static fn ( $p ): bool => is_array( $p ) && '' !== trim( (string) ( $p['name'] ?? '' ) ) ) );
if ( ! $bb_plans ) {
	return;
}
$bb_currency = (string) ( $attributes['currency'] ?? '$' );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'bb-pricing' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes wrapper attributes. ?>>
	<?php foreach ( $bb_plans as $bb_plan ) : ?>
		<article class="bb-plan<?php echo empty( $bb_plan['highlight'] ) ? '' : ' is-highlighted'; ?>">
			<?php if ( ! empty( $bb_plan['highlight'] ) ) : ?>
				<p class="bb-plan-badge"><?php esc_html_e( 'Most popular', 'business-blocks' ); ?></p>
			<?php endif; ?>
			<h3 class="bb-plan-name"><?php echo esc_html( (string) $bb_plan['name'] ); ?></h3>
			<p class="bb-plan-price">
				<span class="bb-plan-amount"><?php echo esc_html( $bb_currency . (string) ( $bb_plan['price'] ?? '' ) ); ?></span>
				<?php if ( '' !== (string) ( $bb_plan['period'] ?? '' ) ) : ?>
					<span class="bb-plan-period">/ <?php echo esc_html( (string) $bb_plan['period'] ); ?></span>
				<?php endif; ?>
			</p>
			<ul class="bb-plan-features">
				<?php foreach ( array_filter( array_map( 'trim', explode( "\n", (string) ( $bb_plan['features'] ?? '' ) ) ) ) as $bb_feature ) : ?>
					<li><?php echo esc_html( $bb_feature ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( '' !== (string) ( $bb_plan['button'] ?? '' ) && '' !== (string) ( $bb_plan['url'] ?? '' ) ) : ?>
				<a class="bb-plan-button wp-element-button" href="<?php echo esc_url( (string) $bb_plan['url'] ); ?>"><?php echo esc_html( (string) $bb_plan['button'] ); ?><span class="screen-reader-text"> (<?php echo esc_html( (string) $bb_plan['name'] ); ?>)</span></a>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
</div>
