<?php
/**
 * Creates a demo page using every block, a page using none, and a page with hostile FAQ content
 * (run with: wp eval-file setup.php). Idempotent. All names and details are invented.
 *
 * @package BusinessBlocks
 */

$bb_block = static fn ( string $name, array $attrs ): string => '<!-- wp:business-blocks/' . $name . ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . " /-->\n\n";

$demo  = "<!-- wp:paragraph -->\n<p>Family-run dental practice in the heart of Riverside.</p>\n<!-- /wp:paragraph -->\n\n";
$demo .= $bb_block(
	'business-hours',
	array(
		'businessType' => 'Dentist',
		'phone'        => '0161 555 0142',
		'days'         => array(
			array(
				'day'    => 'Monday',
				'open'   => '08:00',
				'close'  => '18:00',
				'closed' => false,
			),
			array(
				'day'    => 'Tuesday',
				'open'   => '08:00',
				'close'  => '18:00',
				'closed' => false,
			),
			array(
				'day'    => 'Wednesday',
				'open'   => '08:00',
				'close'  => '20:00',
				'closed' => false,
			),
			array(
				'day'    => 'Thursday',
				'open'   => '08:00',
				'close'  => '18:00',
				'closed' => false,
			),
			array(
				'day'    => 'Friday',
				'open'   => '08:00',
				'close'  => '17:00',
				'closed' => false,
			),
			array(
				'day'    => 'Saturday',
				'open'   => '09:00',
				'close'  => '13:00',
				'closed' => false,
			),
			array(
				'day'    => 'Sunday',
				'open'   => '',
				'close'  => '',
				'closed' => true,
			),
		),
		'closures'     => array(
			array(
				'date'  => '2026-12-25',
				'label' => 'Christmas Day',
			),
			array(
				'date'  => '2026-12-26',
				'label' => 'Boxing Day',
			),
		),
	)
);
$demo .= $bb_block(
	'faq',
	array(
		'openFirst' => true,
		'items'     => array(
			array(
				'question' => 'Do you take new NHS patients?',
				'answer'   => 'We have limited NHS places. Join the waiting list at reception or by phone.',
			),
			array(
				'question' => 'How much is a check-up?',
				'answer'   => 'A private check-up is <strong>£55</strong> and includes X-rays when needed.',
			),
			array(
				'question' => 'Do you see children?',
				'answer'   => 'Yes, children are seen free of charge until their 18th birthday.',
			),
		),
	)
);
$demo .= $bb_block(
	'pricing-table',
	array(
		'currency' => '£',
		'plans'    => array(
			array(
				'name'      => 'Essential',
				'price'     => '14',
				'period'    => 'month',
				'features'  => "Two check-ups a year\nTwo hygienist visits\n10% off treatment",
				'button'    => 'Join Essential',
				'url'       => '/join/',
				'highlight' => false,
			),
			array(
				'name'      => 'Complete',
				'price'     => '22',
				'period'    => 'month',
				'features'  => "Two check-ups a year\nFour hygienist visits\nEmergency cover\n15% off treatment",
				'button'    => 'Join Complete',
				'url'       => '/join/',
				'highlight' => true,
			),
			array(
				'name'      => 'Family',
				'price'     => '49',
				'period'    => 'month',
				'features'  => "Two adults and up to three children\nAll Complete benefits",
				'button'    => 'Join Family',
				'url'       => '/join/',
				'highlight' => false,
			),
		),
	)
);
$demo .= $bb_block(
	'testimonials',
	array(
		'items' => array(
			array(
				'quote' => 'The calmest dental visit I have ever had. They explained everything before starting.',
				'name'  => 'Priya S.',
				'role'  => 'Patient since 2021',
			),
			array(
				'quote' => 'Our kids actually look forward to their check-ups now.',
				'name'  => 'Daniel M.',
				'role'  => 'Parent',
			),
		),
	)
);
$demo .= $bb_block(
	'team-member',
	array(
		'name'  => 'Dr. Amara Okafor',
		'role'  => 'Principal dentist',
		'bio'   => 'Amara qualified in 2010 and leads the practice. She has a special interest in nervous patients.',
		'email' => 'amara@riverside-dental.example',
	)
);
$demo .= $bb_block(
	'call-to-action',
	array(
		'heading' => 'Book your check-up',
		'text'    => 'New patients are welcome. Most appointments are available <strong>within a week</strong>.',
		'button'  => 'Book online',
		'url'     => '/book/',
		'phone'   => '0161 555 0142',
	)
);

$pages = array(
	'blocks-demo' => array( 'Our practice', $demo ),
	'plain-page'  => array( 'About us', "<!-- wp:paragraph -->\n<p>A page that uses no Business Blocks.</p>\n<!-- /wp:paragraph -->" ),
	'hostile-faq' => array(
		'Hostile FAQ',
		$bb_block(
			'faq',
			array(
				'items' => array(
					array(
						'question' => '</script><script>alert(1)</script> Is this safe?',
						'answer'   => 'Yes <script>alert(2)</script><img src=x onerror=alert(3)> it is.',
					),
				),
			)
		),
	),
);
foreach ( $pages as $slug => [ $title, $content ] ) {
	$existing = get_page_by_path( $slug );
	wp_insert_post(
		array(
			'ID'           => $existing ? $existing->ID : 0,
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $title,
			'post_content' => wp_slash( $content ),
		)
	);
}
update_option( 'timezone_string', 'Europe/London' );
echo "demo pages ready\n";
