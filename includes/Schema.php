<?php
/**
 * schema.org JSON-LD for the blocks, built as arrays and encoded safely for a <script> element.
 *
 * Only types search engines accept for these uses are emitted: FAQPage for FAQs and LocalBusiness opening hours.
 * Testimonials get no Review markup, because self-written reviews of your own business are not eligible for review
 * rich results and can draw a manual action.
 *
 * @package BusinessBlocks
 */

namespace BusinessBlocks;

/**
 * Builders and the encoder.
 */
final class Schema {

	/**
	 * FAQPage from question/answer pairs (answers may contain simple HTML, which is allowed in acceptedAnswer.text).
	 *
	 * @param list<array{question: string, answer: string}> $items Items.
	 * @return array<string, mixed>|null Null when there is nothing to describe.
	 */
	public static function faq( array $items ): ?array {
		$entities = array();
		foreach ( $items as $item ) {
			$q = trim( (string) preg_replace( '/\s+/u', ' ', strip_tags( (string) ( $item['question'] ?? '' ) ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Pure class, tested without WordPress.
			$a = trim( (string) ( $item['answer'] ?? '' ) );
			if ( '' === $q || '' === trim( strip_tags( $a ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Pure class, tested without WordPress.
				continue;
			}
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $q,
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $a,
				),
			);
		}
		if ( ! $entities ) {
			return null;
		}
		return array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		);
	}

	/**
	 * LocalBusiness with opening hours.
	 *
	 * @param string $name  Business name.
	 * @param string $type  schema.org type (LocalBusiness or a subtype such as Dentist).
	 * @param Hours  $hours Hours.
	 * @param string $url   Site URL.
	 * @param string $phone Telephone.
	 * @return array<string, mixed>
	 */
	public static function business( string $name, string $type, Hours $hours, string $url, string $phone = '' ): array {
		$data = array(
			'@context'                  => 'https://schema.org',
			'@type'                     => preg_match( '/^[A-Z][A-Za-z]{2,40}$/', $type ) ? $type : 'LocalBusiness',
			'name'                      => $name,
			'url'                       => $url,
			'openingHoursSpecification' => $hours->schema(),
		);
		if ( '' !== $phone ) {
			$data['telephone'] = $phone;
		}
		return $data;
	}

	/**
	 * A <script type="application/ld+json"> element. JSON_HEX_TAG turns "<" and ">" into < and >, so text
	 * such as "</script><script>alert(1)</script>" in an FAQ answer cannot close the element.
	 *
	 * @param array<string, mixed>|null $data Data.
	 */
	public static function script( ?array $data ): string {
		if ( null === $data ) {
			return '';
		}
		$json = json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Needs the JSON_HEX_* flags explicitly; pure class.
			$data,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
		return false === $json ? '' : '<script type="application/ld+json">' . $json . '</script>';
	}
}
