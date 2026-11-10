<?php
/**
 * Team member card. The photo's alt text is the person's name unless the image has its own alt text.
 *
 * @package BusinessBlocks
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

$bb_name = trim( (string) ( $attributes['name'] ?? '' ) );
if ( '' === $bb_name ) {
	return;
}
$bb_media = (int) ( $attributes['mediaId'] ?? 0 );
$bb_alt   = $bb_media ? trim( (string) get_post_meta( $bb_media, '_wp_attachment_image_alt', true ) ) : '';
?>
<article <?php echo get_block_wrapper_attributes( array( 'class' => 'bb-person' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes wrapper attributes. ?>>
	<?php
	if ( $bb_media ) {
		echo wp_get_attachment_image(
			$bb_media,
			'medium',
			false,
			array(
				'class'   => 'bb-person-photo',
				'alt'     => '' !== $bb_alt ? $bb_alt : $bb_name,
				'loading' => 'lazy',
			)
		);
	}
	?>
	<h3 class="bb-person-name"><?php echo esc_html( $bb_name ); ?></h3>
	<?php if ( '' !== (string) ( $attributes['role'] ?? '' ) ) : ?>
		<p class="bb-person-role"><?php echo esc_html( (string) $attributes['role'] ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== (string) ( $attributes['bio'] ?? '' ) ) : ?>
		<p class="bb-person-bio"><?php echo esc_html( (string) $attributes['bio'] ); ?></p>
	<?php endif; ?>
	<?php $bb_email = sanitize_email( (string) ( $attributes['email'] ?? '' ) ); ?>
	<?php $bb_li = esc_url( (string) ( $attributes['linkedin'] ?? '' ), array( 'https' ) ); ?>
	<?php if ( '' !== $bb_email || '' !== $bb_li ) : ?>
		<p class="bb-person-links">
			<?php if ( '' !== $bb_email ) : ?>
				<a href="mailto:<?php echo esc_attr( antispambot( $bb_email ) ); ?>"><?php esc_html_e( 'Email', 'business-blocks' ); ?><span class="screen-reader-text"> <?php echo esc_html( $bb_name ); ?></span></a>
			<?php endif; ?>
			<?php if ( '' !== $bb_li ) : ?>
				<a href="<?php echo $bb_li; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped with esc_url above. ?>" rel="noopener"><?php esc_html_e( 'LinkedIn', 'business-blocks' ); ?><span class="screen-reader-text"> <?php echo esc_html( $bb_name ); ?></span></a>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</article>
