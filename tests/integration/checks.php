<?php
/**
 * Integration checks inside WordPress (run with: wp eval-file checks.php).
 *
 * @package BusinessBlocks
 */

$failures = 0;
$check    = static function ( string $name, bool $ok, string $detail = '' ) use ( &$failures ): void {
	echo ( $ok ? '  ok    ' : '  FAIL  ' ) . $name . ( $ok || '' === $detail ? '' : ' (' . $detail . ')' ) . "\n";
	$failures += $ok ? 0 : 1;
};
$jsonld   = static function ( string $html ): array {
	preg_match_all( '#<script type="application/ld\+json">(.*?)</script>#s', $html, $m );
	return array_map( static fn ( string $j ) => json_decode( $j, true ), $m[1] );
};

$registry = WP_Block_Type_Registry::get_instance();
$names    = array( 'faq', 'business-hours', 'pricing-table', 'testimonials', 'team-member', 'call-to-action' );
$missing  = array_filter( $names, static fn ( string $n ): bool => ! $registry->is_registered( 'business-blocks/' . $n ) );
$check( 'all six blocks registered from build/', ! $missing, implode( ', ', $missing ) );
$check( 'blocks are dynamic (rendered in PHP)', ! array_filter( $names, static fn ( string $n ): bool => ! $registry->get_registered( 'business-blocks/' . $n )->is_dynamic() ) );

$html  = do_blocks( get_page_by_path( 'blocks-demo' )->post_content );
$ld    = $jsonld( $html );
$types = array_column( $ld, '@type' );
$check( 'FAQPage and Dentist JSON-LD emitted and valid', in_array( 'FAQPage', $types, true ) && in_array( 'Dentist', $types, true ) && ! in_array( null, $ld, true ), implode( ',', array_map( 'strval', $types ) ) );
$faq = current( array_filter( $ld, static fn ( $d ): bool => 'FAQPage' === ( $d['@type'] ?? '' ) ) );
$check( 'FAQPage has one Question per item with accepted answers', 3 === count( $faq['mainEntity'] ) && str_contains( $faq['mainEntity'][1]['acceptedAnswer']['text'], '£55' ) );
$biz = current( array_filter( $ld, static fn ( $d ): bool => 'Dentist' === ( $d['@type'] ?? '' ) ) );
$check( 'opening hours grouped, closures as special hours', array( 'Monday', 'Tuesday', 'Thursday' ) === $biz['openingHoursSpecification'][0]['dayOfWeek'] && array( '2026-12-25', '2026-12-26' ) === array_values( array_filter( array_column( $biz['openingHoursSpecification'], 'validThrough' ) ) ) );
$check( 'no Review markup for testimonials', ! preg_match( '/"@type":"(Review|AggregateRating)"/', $html ) );
$check( 'FAQ uses details/summary, first item open', 3 === substr_count( $html, '<details class="bb-faq-item"' ) && str_contains( $html, '<details class="bb-faq-item" open>' ) );
$check( 'hours: today marked, status badge, live data for the browser', str_contains( $html, 'aria-current="date"' ) && str_contains( $html, 'bb-hours-status' ) && str_contains( $html, 'data-hours=' ) && str_contains( $html, 'Europe\/London' ) );
$check( 'phone numbers are tappable links', str_contains( $html, 'href="tel:01615550142"' ) );
$check( 'highlighted plan and per-plan accessible button labels', str_contains( $html, 'bb-plan is-highlighted' ) && str_contains( $html, '<span class="screen-reader-text"> (Complete)</span>' ) );
$check( 'testimonials as figure/blockquote/figcaption', 2 === substr_count( $html, '<figure class="bb-testimonial">' ) && str_contains( $html, '<figcaption>' ) );
$check( 'team email obfuscated against harvesters', str_contains( $html, 'bb-person' ) && ! str_contains( $html, 'mailto:amara@riverside-dental.example' ) );

$hostile = do_blocks( get_page_by_path( 'hostile-faq' )->post_content );
$check( 'hostile FAQ: no script or event handler survives', 0 === preg_match( '/<script>alert|onerror=/i', $hostile ) );
$check( 'hostile FAQ: JSON-LD still valid and cannot close its script element', 1 === substr_count( strtolower( $hostile ), '</script' ) && is_array( $jsonld( $hostile )[0] ?? null ) );

$check( 'empty blocks render nothing', '' === trim( do_blocks( '<!-- wp:business-blocks/faq {"items":[{"question":"","answer":""}]} /-->' ) ) && '' === trim( do_blocks( '<!-- wp:business-blocks/team-member /-->' ) ) );

echo $failures ? "\n{$failures} integration check(s) failed\n" : "\nall integration checks passed\n";
if ( $failures ) {
	WP_CLI::halt( 1 );
}
