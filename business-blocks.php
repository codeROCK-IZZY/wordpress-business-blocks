<?php
/**
 * Plugin Name:       Business Blocks
 * Plugin URI:        https://github.com/codeROCK-IZZY/wordpress-business-blocks
 * Description:       Gutenberg blocks for small-business sites: FAQ with structured data, business hours with a live "Open now" badge, pricing table, testimonials, team member and call to action.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Israel Rocklyn
 * Author URI:        https://israelrocklyn.info
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       business-blocks
 *
 * @package BusinessBlocks
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/Hours.php';
require_once __DIR__ . '/includes/Schema.php';

/**
 * Register the block category and every block built into build/blocks.
 */
function business_blocks_register(): void {
	$business_blocks_found = glob( __DIR__ . '/build/blocks/*/block.json' );
	foreach ( is_array( $business_blocks_found ) ? $business_blocks_found : array() as $business_blocks_json ) {
		register_block_type( dirname( $business_blocks_json ) );
	}
}
add_action( 'init', 'business_blocks_register' );

add_filter(
	'block_categories_all',
	static function ( array $categories ): array {
		array_unshift(
			$categories,
			array(
				'slug'  => 'business-blocks',
				'title' => __( 'Business', 'business-blocks' ),
				'icon'  => null,
			)
		);
		return $categories;
	}
);
