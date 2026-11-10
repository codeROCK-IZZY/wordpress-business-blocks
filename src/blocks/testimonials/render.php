<?php
/**
 * Testimonials: <figure> with <blockquote> and <figcaption>, the accessible pattern for attributed quotes.
 *
 * @package BusinessBlocks
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

$bb_items = array_values( array_filter( (array) ( $attributes['items'] ?? array() ), static fn ( $t ): bool => is_array( $t ) && '' !== trim( (string) ( $t['quote'] ?? '' ) ) ) );
if ( ! $bb_items ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'bb-testimonials' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes wrapper attributes. ?>>
	<?php foreach ( $bb_items as $bb_t ) : ?>
		<figure class="bb-testimonial">
			<blockquote><p><?php echo esc_html( (string) $bb_t['quote'] ); ?></p></blockquote>
			<?php if ( '' !== (string) ( $bb_t['name'] ?? '' ) ) : ?>
				<figcaption><span class="bb-testimonial-name"><?php echo esc_html( (string) $bb_t['name'] ); ?></span><?php echo '' !== (string) ( $bb_t['role'] ?? '' ) ? '<span class="bb-testimonial-role">' . esc_html( (string) $bb_t['role'] ) . '</span>' : ''; ?></figcaption>
			<?php endif; ?>
		</figure>
	<?php endforeach; ?>
</div>
