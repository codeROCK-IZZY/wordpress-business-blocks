<?php
/**
 * FAQ block: <details>/<summary> needs no JavaScript and is keyboard and screen-reader accessible by default.
 *
 * @package BusinessBlocks
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

use BusinessBlocks\Schema;

$bb_items = array_values(
	array_filter(
		(array) ( $attributes['items'] ?? array() ),
		static fn ( $i ): bool => is_array( $i ) && '' !== trim( wp_strip_all_tags( (string) ( $i['question'] ?? '' ) ) )
	)
);
if ( ! $bb_items ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'bb-faq' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes wrapper attributes. ?>>
	<?php foreach ( $bb_items as $bb_n => $bb_item ) : ?>
		<details class="bb-faq-item"<?php echo ( 0 === $bb_n && ! empty( $attributes['openFirst'] ) ) ? ' open' : ''; ?>>
			<summary class="bb-faq-question"><?php echo esc_html( wp_strip_all_tags( (string) $bb_item['question'] ) ); ?></summary>
			<div class="bb-faq-answer"><?php echo wp_kses_post( wpautop( (string) ( $bb_item['answer'] ?? '' ) ) ); ?></div>
		</details>
	<?php endforeach; ?>
</div>
<?php
if ( ! empty( $attributes['schema'] ) ) {
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with JSON_HEX_TAG, see Schema::script().
	echo Schema::script(
		Schema::faq(
			array_map(
				static fn ( array $i ): array => array(
					'question' => (string) $i['question'],
					'answer'   => wp_kses(
						(string) ( $i['answer'] ?? '' ),
						array(
							'a'      => array( 'href' => true ),
							'b'      => array(),
							'strong' => array(),
							'em'     => array(),
							'i'      => array(),
							'p'      => array(),
							'br'     => array(),
						)
					),
				),
				$bb_items
			)
		)
	);
	// phpcs:enable
}
