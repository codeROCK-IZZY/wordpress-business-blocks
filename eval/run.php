<?php
/**
 * Evaluation: structured data under hostile input, opening hours against a reference, browser/PHP parity,
 * front-end weight, and the axe-core audit (eval/reports/a11y.json, written by eval/a11y.py when available).
 * Writes eval/reports/report.{json,md,html}; with --gate exits 1 if a check fails.
 *
 * Usage: php eval/run.php [--gate]
 *
 * @package BusinessBlocks
 */

namespace BusinessBlocks\Evaluation;

use BusinessBlocks\Hours;
use BusinessBlocks\Schema;

require dirname( __DIR__ ) . '/includes/Hours.php';
require dirname( __DIR__ ) . '/includes/Schema.php';

$gate = in_array( '--gate', $argv, true );
$root = dirname( __DIR__ );

// ------------------------------------------------------------ 1. structured data with hostile content
mt_srand( 7 );
$hostile    = array( '</script><script>alert(1)</script>', '</SCRIPT >', '<!--', '-->', ']]>', '"', "'", '\\', "\u{2028}", "\u{2029}", '<img src=x onerror=alert(1)>', '&lt;b&gt;', '😀', 'ﬁ', "\0" );
$ld_cases   = 2000;
$ld_broken  = 0;
$ld_escaped = 0;
for ( $i = 0; $i < $ld_cases; $i++ ) {
	$items = array();
	for ( $k = 0, $n = mt_rand( 1, 6 ); $k < $n; $k++ ) {
		$items[] = array(
			'question' => 'Question ' . $k . ' ' . $hostile[ mt_rand( 0, count( $hostile ) - 1 ) ] . ' ' . $hostile[ mt_rand( 0, count( $hostile ) - 1 ) ],
			'answer'   => '<p>Answer ' . $hostile[ mt_rand( 0, count( $hostile ) - 1 ) ] . '</p>',
		);
	}
	$html = Schema::script( Schema::faq( $items ) );
	$json = substr( $html, strlen( '<script type="application/ld+json">' ), -strlen( '</script>' ) );
	$data = json_decode( $json, true );
	if ( 1 !== substr_count( strtolower( $html ), '</script' ) || str_contains( $json, '<!--' ) || ! is_array( $data ) || 'FAQPage' !== ( $data['@type'] ?? '' ) ) {
		++$ld_broken;
	}
	if ( count( $data['mainEntity'] ?? array() ) === count( $items ) ) {
		++$ld_escaped;
	}
}

// ------------------------------------------------------------ 2. opening hours against a reference
$reference = static function ( array $days, array $closed, \DateTimeZone $tz, int $ts ): bool {
	$map = array();
	foreach ( $days as $d ) {
		if ( empty( $d['closed'] ) && null !== Hours::clock( $d['open'] ) && null !== Hours::clock( $d['close'] ) && Hours::clock( $d['open'] ) !== Hours::clock( $d['close'] ) ) {
			$map[ $d['day'] ] = array( Hours::clock( $d['open'] ), Hours::clock( $d['close'] ) );
		}
	}
	$today = ( new \DateTimeImmutable( '@' . $ts ) )->setTimezone( $tz )->format( 'Y-m-d' );
	foreach ( array( gmdate( 'Y-m-d', (int) strtotime( $today . ' 00:00 UTC -1 day' ) ), $today ) as $ymd ) {
		$day = gmdate( 'l', (int) strtotime( $ymd . ' 12:00 UTC' ) );
		if ( isset( $closed[ $ymd ] ) || ! isset( $map[ $day ] ) ) {
			continue;
		}
		[ $o, $c ] = $map[ $day ];
		$start     = ( new \DateTimeImmutable( $ymd . 'T' . $o . ':00', $tz ) )->getTimestamp();
		$end_date  = $c <= $o ? gmdate( 'Y-m-d', (int) strtotime( $ymd . ' 12:00 UTC +1 day' ) ) : $ymd;
		$end       = ( new \DateTimeImmutable( $end_date . 'T' . $c . ':00', $tz ) )->getTimestamp();
		if ( $ts >= $start && $ts < $end ) {
			return true;
		}
	}
	return false;
};
mt_srand( 99 );
$zones    = array( 'Europe/London', 'America/New_York', 'America/Los_Angeles', 'Australia/Sydney', 'Asia/Kolkata', 'Europe/Berlin', 'America/Sao_Paulo', 'UTC' );
$anchors  = array( '2026-03-29 00:00', '2026-03-08 00:00', '2026-10-25 00:00', '2026-11-01 00:00', '2026-04-05 00:00', '2026-10-04 00:00', '2026-06-15 00:00' );
$h_checks = 0;
$h_bad    = 0;
for ( $case = 0; $case < 400; $case++ ) {
	$tz   = new \DateTimeZone( $zones[ $case % count( $zones ) ] );
	$days = array();
	foreach ( Hours::DAYS as $d ) {
		$o      = sprintf( '%02d:%02d', mt_rand( 0, 23 ), 15 * mt_rand( 0, 3 ) );
		$c      = sprintf( '%02d:%02d', mt_rand( 0, 23 ), 15 * mt_rand( 0, 3 ) );
		$days[] = array(
			'day'    => $d,
			'open'   => $o,
			'close'  => $c,
			'closed' => 0 === mt_rand( 0, 5 ),
		);
	}
	$base  = new \DateTimeImmutable( $anchors[ $case % count( $anchors ) ], $tz );
	$cdate = $base->modify( '+' . mt_rand( 0, 2 ) . ' day' )->format( 'Y-m-d' );
	$hours = new Hours( $days, array( array( 'date' => $cdate ) ), $tz );
	for ( $m = 0; $m < 3 * 24 * 60; $m += 29 ) {
		$ts = $base->getTimestamp() + $m * 60;
		++$h_checks;
		if ( $reference( $days, array( $cdate => true ), $tz, $ts ) !== $hours->status( ( new \DateTimeImmutable( '@' . $ts ) )->setTimezone( $tz ) )['open'] ) {
			++$h_bad;
		}
	}
}

// ------------------------------------------------------------ 3. browser/PHP parity (enforced by Jest)
$fixture = $root . '/tests/js/fixtures/hours-cases.json';
$parity  = is_readable( $fixture ) ? count( (array) json_decode( (string) file_get_contents( $fixture ), true ) ) : 0;

// ------------------------------------------------------------ 4. front-end weight
$weight = array();
$found  = glob( $root . '/build/blocks/*/block.json' );
foreach ( is_array( $found ) ? $found : array() as $json ) {
	$dir             = dirname( $json );
	$name            = basename( $dir );
	$gz              = static fn ( string $f ): int => is_readable( $f ) ? strlen( (string) gzencode( (string) file_get_contents( $f ), 9 ) ) : 0;
	$weight[ $name ] = array(
		'css_gz'     => $gz( $dir . '/style-index.css' ),
		'view_js_gz' => $gz( $dir . '/view.js' ),
		'editor_gz'  => $gz( $dir . '/index.js' ),
	);
}
$front_css = array_sum( array_column( $weight, 'css_gz' ) );
$front_js  = array_sum( array_column( $weight, 'view_js_gz' ) );

// ------------------------------------------------------------ 5. accessibility
$a11y_file = __DIR__ . '/reports/a11y.json';
$a11y      = is_readable( $a11y_file ) ? json_decode( (string) file_get_contents( $a11y_file ), true ) : null;

$checks = array(
	array( 'FAQ JSON-LD that breaks out of its script or is invalid, hostile input', $ld_broken . ' / ' . $ld_cases, '= 0', 0 === $ld_broken ),
	array( '"Open now" disagreements with the reference, 8 zones incl. DST changes', $h_bad . ' / ' . number_format( $h_checks ), '= 0', 0 === $h_bad ),
	array( 'Browser and PHP agree on generated cases (Jest)', number_format( $parity ) . ' cases', '≥ 3,000', $parity >= 3000 ),
	array( 'Front-end CSS for all six blocks, gzipped', $front_css . ' B', '≤ 3 KB', $front_css <= 3072 ),
	array( 'Front-end JavaScript (hours badge only), gzipped', $front_js . ' B', '≤ 2 KB', $front_js <= 2048 ),
);
if ( is_array( $a11y ) ) {
	$checks[] = array( 'axe-core WCAG 2.1 A/AA violations in the blocks', count( $a11y['violations'] ), '= 0', 0 === count( $a11y['violations'] ) );
	$checks[] = array( 'FAQ questions reachable by keyboard', $a11y['faq_keyboard'] ? 'yes' : 'no', 'yes', (bool) $a11y['faq_keyboard'] );
}
$passed = count( array_filter( $checks, static fn ( array $c ): bool => $c[3] ) );

$out = __DIR__ . '/reports';
if ( ! is_dir( $out ) ) {
	mkdir( $out, 0777, true );
}
file_put_contents(
	$out . '/report.json',
	json_encode(
		array(
			'structured_data' => array(
				'cases'  => $ld_cases,
				'broken' => $ld_broken,
			),
			'hours'           => array(
				'checks'     => $h_checks,
				'mismatches' => $h_bad,
				'parity'     => $parity,
			),
			'weight'          => $weight,
			'a11y'            => $a11y,
			'checks'          => array_map(
				static fn ( array $c ): array => array(
					'check'  => $c[0],
					'value'  => $c[1],
					'target' => $c[2],
					'pass'   => $c[3],
				),
				$checks
			),
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
	) . "\n"
);

$md = array( '# Business Blocks: evaluation', '', '## Gate: ' . $passed . ' / ' . count( $checks ) . ' checks passed', '', '| Check | Value | Target | |', '|---|---|---|---|' );
foreach ( $checks as $c ) {
	$md[] = sprintf( '| %s | %s | %s | %s |', $c[0], $c[1], $c[2], $c[3] ? 'pass' : 'FAIL' );
}
$md[] = '';
$md[] = '## Front-end weight per block (gzipped)';
$md[] = '';
$md[] = '| Block | CSS | Front-end JS | Editor JS (editor only) |';
$md[] = '|---|---:|---:|---:|';
foreach ( $weight as $name => $w ) {
	$md[] = sprintf( '| %s | %d B | %s | %d B |', $name, $w['css_gz'], $w['view_js_gz'] ? $w['view_js_gz'] . ' B' : 'none', $w['editor_gz'] );
}
file_put_contents( $out . '/report.md', implode( "\n", $md ) . "\n" );

$h    = static fn ( $v ): string => htmlspecialchars( (string) $v, ENT_QUOTES );
$rows = '';
foreach ( $checks as $c ) {
	$rows .= sprintf( '<tr><th scope="row">%s</th><td class="num">%s</td><td>%s</td><td class="%s">%s</td></tr>', $h( $c[0] ), $h( $c[1] ), $h( $c[2] ), $c[3] ? 'pass' : 'fail', $c[3] ? '✓ pass' : '✗ fail' );
}
$wrows = '';
$max   = max( 1, ...array_values( array_map( static fn ( array $w ): int => $w['editor_gz'], $weight ) ) );
foreach ( $weight as $name => $w ) {
	$wrows .= sprintf(
		'<tr><th scope="row">%s</th><td><svg width="240" height="12" role="img" aria-label="CSS %d bytes"><rect width="%.1f" height="12" rx="3" fill="#3987e5"/></svg> <span class="v">%d B</span></td><td class="num">%s</td><td><svg width="240" height="12" role="img" aria-label="Editor %d bytes"><rect width="%.1f" height="12" rx="3" fill="#5c6370"/></svg> <span class="v">%d B</span></td></tr>',
		$h( $name ),
		$w['css_gz'],
		max( 2, 240 * $w['css_gz'] / $max ),
		$w['css_gz'],
		$w['view_js_gz'] ? $w['view_js_gz'] . ' B' : '—',
		$w['editor_gz'],
		max( 2, 240 * $w['editor_gz'] / $max ),
		$w['editor_gz']
	);
}
$html  = '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Business Blocks: evaluation</title><style>
:root{--bg:#0f1115;--card:#171a21;--ink:#e8eaee;--muted:#9aa3b2;--line:#2a2f3a}
body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}
main{max-width:1180px;margin:0 auto;padding:32px 24px}h1{font-size:26px;margin:0 0 4px}h2{font-size:18px;margin:28px 0 10px}
.sub{color:var(--muted);margin:0 0 20px;max-width:960px}.tiles{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.tile{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:14px 16px}.tile b{display:block;font-size:28px}.tile span{color:var(--muted);font-size:13px}
table{width:100%;border-collapse:collapse;background:var(--card);border:1px solid var(--line);border-radius:10px;overflow:hidden}
th,td{padding:9px 12px;border-bottom:1px solid var(--line);text-align:left;vertical-align:middle}thead th{color:var(--muted);font-weight:600;font-size:13px}
.num{text-align:right}.v{color:var(--muted);font-size:13px;margin-left:6px}.pass{color:#5cc98a}.fail{color:#d95926}
</style></head><body><main><h1>Business Blocks: evaluation</h1><p class="sub">Six dynamic Gutenberg blocks. Structured data under hostile input, the "Open now" logic in eight time zones across daylight-saving changes, browser/PHP agreement, the weight visitors download, and an axe-core audit of the rendered blocks.</p>';
$html .= '<div class="tiles"><div class="tile"><b>' . $h( $ld_broken ) . '</b><span>of ' . $h( number_format( $ld_cases ) ) . ' hostile FAQ sets break the JSON-LD</span></div>'
	. '<div class="tile"><b>' . $h( $h_bad ) . '</b><span>"Open now" errors in ' . $h( number_format( $h_checks ) ) . ' checks across DST changes</span></div>'
	. '<div class="tile"><b>' . ( is_array( $a11y ) ? $h( count( $a11y['violations'] ) ) : '–' ) . '</b><span>axe-core WCAG 2.1 AA violations' . ( is_array( $a11y ) ? ' (' . $h( $a11y['passes'] ) . ' rules passed)' : '' ) . '</span></div>'
	. '<div class="tile"><b>' . $h( round( ( $front_css + $front_js ) / 1024, 1 ) ) . ' KB</b><span>everything visitors download for all six blocks, gzipped</span></div></div>';
$html .= '<h2>Gate: ' . $h( $passed . ' / ' . count( $checks ) ) . '</h2><table><thead><tr><th>Check</th><th class="num">value</th><th>target</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table>';
$html .= '<h2>Weight per block, gzipped (editor scripts load only in the editor)</h2><table><thead><tr><th>Block</th><th>front-end CSS</th><th class="num">front-end JS</th><th>editor JS</th></tr></thead><tbody>' . $wrows . '</tbody></table></main></body></html>';
file_put_contents( $out . '/report.html', $html );

echo implode( "\n", $md ), "\n";
if ( $gate && count( $checks ) !== $passed ) {
	fwrite( STDERR, "Gate failed\n" );
	exit( 1 );
}
