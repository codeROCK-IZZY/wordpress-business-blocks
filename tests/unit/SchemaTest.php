<?php
/**
 * Structured data: shape, filtering, and that no content can break out of the <script> element.
 *
 * @package BusinessBlocks
 */

namespace BusinessBlocks\Tests;

use BusinessBlocks\Hours;
use BusinessBlocks\Schema;
use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase {

	public function test_faq_page_shape(): void {
		$data = Schema::faq(
			array(
				array(
					'question' => '  Do you  open on <b>Sundays</b>? ',
					'answer'   => '<p>No, we are closed.</p>',
				),
				array(
					'question' => 'Empty answer',
					'answer'   => '<p> </p>',
				),
				array(
					'question' => '',
					'answer'   => 'Orphan answer',
				),
			)
		);
		$this->assertSame( 'FAQPage', $data['@type'] );
		$this->assertCount( 1, $data['mainEntity'] );
		$this->assertSame( 'Do you open on Sundays?', $data['mainEntity'][0]['name'] );
		$this->assertSame( '<p>No, we are closed.</p>', $data['mainEntity'][0]['acceptedAnswer']['text'] );
		$this->assertNull( Schema::faq( array() ) );
	}

	public function test_script_cannot_be_closed_by_content(): void {
		$payloads = array( '</script><script>alert(1)</script>', '<!--', ']]>', "\u{2028}line", '"quoted" & \'apos\'' );
		foreach ( $payloads as $p ) {
			$html = Schema::script(
				Schema::faq(
					array(
						array(
							'question' => 'Q ' . $p,
							'answer'   => 'A ' . $p,
						),
					)
				)
			);
			$this->assertSame( 1, substr_count( strtolower( $html ), '</script' ), $p );
			$this->assertStringNotContainsString( '<!--', substr( $html, 35 ), $p );
			$json = substr( $html, strlen( '<script type="application/ld+json">' ), -strlen( '</script>' ) );
			$this->assertSame( trim( (string) preg_replace( '/\s+/u', ' ', strip_tags( 'Q ' . $p ) ) ), json_decode( $json, true )['mainEntity'][0]['name'] ?? null, 'still valid JSON: ' . $p );
		}
	}

	public function test_business_type_is_restricted(): void {
		$hours = new Hours( array(), array(), new \DateTimeZone( 'UTC' ) );
		$this->assertSame( 'Dentist', Schema::business( 'X', 'Dentist', $hours, 'https://x.example/' )['@type'] );
		$this->assertSame( 'LocalBusiness', Schema::business( 'X', 'Dentist","@id":"evil', $hours, 'https://x.example/' )['@type'] );
	}
}
