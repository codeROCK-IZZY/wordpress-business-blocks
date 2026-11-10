<?php
/**
 * Call to action.
 *
 * @package BusinessBlocks
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

$bb_heading = trim( wp_strip_all_tags( (string) ( $attributes['heading'] ?? '' ) ) );
$bb_url     = esc_url( (string) ( $attributes['url'] ?? '' ) );
$bb_phone   = trim( (string) ( $attributes['phone'] ?? '' ) );
if ( '' === $bb_heading && '' === $bb_url && '' === $bb_phone ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'bb-cta' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes wrapper attributes. ?>>
	<?php if ( '' !== $bb_heading ) : ?>
		<h2 class="bb-cta-heading"><?php echo esc_html( $bb_heading ); ?></h2>
	<?php endif; ?>
	<?php if ( '' !== (string) ( $attributes['text'] ?? '' ) ) : ?>
		<p class="bb-cta-text">
		<?php
		echo wp_kses(
			(string) $attributes['text'],
			array(
				'strong' => array(),
				'em'     => array(),
				'b'      => array(),
				'i'      => array(),
			)
		);
		?>
								</p>
	<?php endif; ?>
	<p class="bb-cta-actions">
		<?php if ( '' !== $bb_url && '' !== (string) ( $attributes['button'] ?? '' ) ) : ?>
			<a class="bb-cta-button wp-element-button" href="<?php echo $bb_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped with esc_url above. ?>"><?php echo esc_html( wp_strip_all_tags( (string) $attributes['button'] ) ); ?></a>
		<?php endif; ?>
		<?php if ( '' !== $bb_phone ) : ?>
			<a class="bb-cta-phone" href="tel:<?php echo esc_attr( (string) preg_replace( '/[^0-9+]/', '', $bb_phone ) ); ?>"><?php echo esc_html( $bb_phone ); ?></a>
		<?php endif; ?>
	</p>
</section>
