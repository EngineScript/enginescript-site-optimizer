<?php
/**
 * Tests for the origin rules that Phase 11 of the review tightened.
 *
 * @package EngineScript_Site_Optimizer
 */

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The text must be the origin itself; whole-value patterns end at the end of the text.
 */
final class OriginRulesTest extends EsOptimizerTestCase {

	/**
	 * Provide inputs that PHP's URL parser reads leniently, and that the plugin must reject (CR-SEM-005).
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function lenientShapeProvider(): array {
		$inputs = array(
			'https://cdn.example.org:8$443',
			'https://example.org:4a3',
			'https://example.org:443.5',
			'https://example.org:+443',
			'https://example.org: 443',
			'https://example.org:443}',
			'https://example.org:8443x',
			'https://example.org:08443',
			'https://example.org:0443',
			'https://example.org:',
			'https://example.org:/',
			'https://[example.org]',
			'https://[example.org]:8443',
		);

		return array_combine( $inputs, array_map( static fn ( string $input ): array => array( $input ), $inputs ) );
	}

	/**
	 * Malformed ports and a bracketed host are rejected, not rewritten into another origin.
	 *
	 * @param string $input Input text.
	 * @return void
	 */
	#[DataProvider( 'lenientShapeProvider' )]
	public function test_text_that_is_not_an_origin_is_rejected( string $input ): void {
		$result = es_optimizer_validate_single_domain( $input );

		$this->assertFalse( $result['valid'] );
		$this->assertSame( '', $result['domain'] );
		$this->assertSame( 'Invalid domain entry.', $result['error'] );
	}

	/**
	 * Provide accepted spellings of an origin and the origin they mean.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function acceptedSpellingProvider(): array {
		return array(
			'upper case, default port, slash' => array( 'https://EXAMPLE.COM:443/', 'https://example.com' ),
			'scheme in capitals'              => array( 'HTTPS://Example.org/', 'https://example.org' ),
			'spaces around a custom port'     => array( ' https://example.org:8443 ', 'https://example.org:8443' ),
			'highest port'                    => array( 'https://example.org:65535', 'https://example.org:65535' ),
			'punycode'                        => array( 'https://xn--mnchen-3ya.de:443/', 'https://xn--mnchen-3ya.de' ),
		);
	}

	/**
	 * Letter case, one trailing slash, and an explicit default port may differ; nothing else.
	 *
	 * @param string $input    Input text.
	 * @param string $expected Canonical origin.
	 * @return void
	 */
	#[DataProvider( 'acceptedSpellingProvider' )]
	public function test_accepted_spellings_give_their_origin( string $input, string $expected ): void {
		$result = es_optimizer_validate_single_domain( $input );

		$this->assertTrue( $result['valid'] );
		$this->assertSame( $expected, $result['domain'] );
		$this->assertSame( $expected, es_optimizer_validate_single_domain( $expected )['domain'], 'An accepted origin validates to itself.' );
	}

	/**
	 * The canonical-origin helper on its own.
	 *
	 * @return void
	 */
	public function test_canonical_origin_helper(): void {
		$this->assertSame( 'https://example.org', es_optimizer_get_canonical_origin( 'https://example.org', 'example.org', null ) );
		$this->assertSame( 'https://example.org', es_optimizer_get_canonical_origin( 'https://example.org:443', 'example.org', 443 ) );
		$this->assertSame( 'https://example.org:8443', es_optimizer_get_canonical_origin( 'https://example.org:8443/', 'example.org', 8443 ) );
		$this->assertSame( '', es_optimizer_get_canonical_origin( 'https://example.org:8443x', 'example.org', 8443 ) );
		$this->assertSame( '', es_optimizer_get_canonical_origin( 'https://example.org//', 'example.org', null ) );
		$this->assertSame( '', es_optimizer_get_canonical_origin( 'https://other.example.org', 'example.org', null ) );
	}

	/**
	 * Provide endings that a whole-value check must not swallow (recurring check L1).
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function endingProvider(): array {
		return array(
			'line feed'       => array( "\n" ),
			'carriage return' => array( "\r" ),
			'CR LF'           => array( "\r\n" ),
			'two line feeds'  => array( "\n\n" ),
			'vertical tab'    => array( "\x0b" ),
			'NUL'             => array( "\x00" ),
			'no-break space'  => array( "\xc2\xa0" ),
		);
	}

	/**
	 * Every name check rejects a value with a trailing line break or invisible character (CR-SEM-001).
	 *
	 * @param string $ending Ending.
	 * @return void
	 */
	#[DataProvider( 'endingProvider' )]
	public function test_name_checks_end_at_the_end_of_the_text( string $ending ): void {
		$this->assertFalse( es_optimizer_is_valid_resource_hint_hostname( 'example.org' . $ending ) );
		$this->assertFalse( es_optimizer_looks_like_obfuscated_ip_host( '0x7f.0.0.1' . $ending ) );
		$this->assertFalse( es_optimizer_validate_single_domain( 'https://example.org' . $ending )['valid'] );
		$this->assertSame( '', es_optimizer_get_canonical_origin( 'https://example.org' . $ending, 'example.org', null ) );
	}

	/**
	 * The same checks accept the value without the ending (the control for the test above).
	 *
	 * @return void
	 */
	public function test_name_checks_accept_the_value_without_an_ending(): void {
		$this->assertTrue( es_optimizer_is_valid_resource_hint_hostname( 'example.org' ) );
		$this->assertTrue( es_optimizer_looks_like_obfuscated_ip_host( '0x7f.0.0.1' ) );
		$this->assertTrue( es_optimizer_looks_like_obfuscated_ip_host( '2130706433' ) );
		$this->assertTrue( es_optimizer_validate_single_domain( 'https://example.org' )['valid'] );
		$this->assertFalse( es_optimizer_is_valid_resource_hint_hostname( 'example.123' ), 'A numeric top-level label is not a name.' );
		$this->assertFalse( es_optimizer_is_valid_resource_hint_hostname( 'localhost' ), 'One label is not enough.' );
		$this->assertFalse( es_optimizer_is_valid_resource_hint_hostname( 'example.org.' ) );
		$this->assertFalse( es_optimizer_is_valid_resource_hint_hostname( '-a.example.org' ) );
		$this->assertFalse( es_optimizer_is_valid_resource_hint_hostname( str_repeat( 'a', 63 ) . '.' . str_repeat( 'b', 63 ) . '.' . str_repeat( 'c', 63 ) . '.' . str_repeat( 'd', 62 ) . '.org' ) );
	}

	/**
	 * Special-use names are rejected, the two added in Phase 11 included; a label that only contains one is not.
	 *
	 * @return void
	 */
	public function test_special_use_names(): void {
		foreach ( array( 'foo.alt', 'foo.onion', 'onion', 'sub.foo.onion', 'a.example', 'printer.home.arpa', 'a.internal', 'a.invalid', 'a.local', 'localhost', 'a.test' ) as $host ) {
			$this->assertTrue( es_optimizer_is_special_use_hostname( $host ), $host );
		}
		foreach ( array( 'onion.example.org', 'salt.example.org', 'alt.example.org', 'example.salt', 'example.org' ) as $host ) {
			$this->assertFalse( es_optimizer_is_special_use_hostname( $host ), $host );
		}
		$this->assertSame( 'IP addresses and private, local, or reserved domain names are not allowed.', es_optimizer_validate_single_domain( 'https://foo.onion' )['error'] );
	}

	/**
	 * Provide inputs and the reason each is rejected for.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function reasonProvider(): array {
		return array(
			'markup'            => array( 'https://<b>.example.org', 'Encoded characters, backslashes, markup, and control characters are not allowed.' ),
			'percent encoding'  => array( 'https://ex%61mple.org', 'Encoded characters, backslashes, markup, and control characters are not allowed.' ),
			'backslash'         => array( 'https://example.org\\x', 'Encoded characters, backslashes, markup, and control characters are not allowed.' ),
			'blank'             => array( '   ', 'Empty domain.' ),
			'unparsable'        => array( 'https:///example.org', 'Invalid domain entry.' ),
			'plain HTTP'        => array( 'http://example.org', 'HTTPS is required.' ),
			'no scheme'         => array( 'example.org', 'HTTPS is required.' ),
			'path'              => array( 'https://example.org/path', 'File paths are not allowed; use domains only.' ),
			'double slash'      => array( 'https://example.org//', 'File paths are not allowed; use domains only.' ),
			'query'             => array( 'https://example.org?x=1', 'Query parameters, fragments, and credentials are not allowed.' ),
			'credentials'       => array( 'https://user@example.org', 'Query parameters, fragments, and credentials are not allowed.' ),
			'port out of range' => array( 'https://example.org:70000', 'Invalid domain entry.' ),
			'IP address'        => array( 'https://127.0.0.1', 'IP addresses and private, local, or reserved domain names are not allowed.' ),
			'obfuscated IP'     => array( 'https://0x7f.1', 'IP addresses and private, local, or reserved domain names are not allowed.' ),
			'bad label'         => array( 'https://a_b.example.org', 'Invalid domain name.' ),
			'hex last label'    => array( 'https://cdn.0x1f', 'Invalid domain name.' ),
			'bare 0x label'     => array( 'https://cdn.0x', 'Invalid domain name.' ),
			'upper-case hex'    => array( 'https://example.0XFF', 'Invalid domain name.' ),
		);
	}

	/**
	 * Each rejection names its reason, and the reason never repeats the input.
	 *
	 * @param string $input  Input text.
	 * @param string $reason Expected reason.
	 * @return void
	 */
	#[DataProvider( 'reasonProvider' )]
	public function test_each_rejection_names_its_reason( string $input, string $reason ): void {
		$result = es_optimizer_validate_single_domain( $input );

		$this->assertFalse( $result['valid'] );
		$this->assertSame( $reason, $result['error'] );
	}

	/**
	 * Preconnect takes no custom port; DNS prefetch does.
	 *
	 * @return void
	 */
	public function test_context_policy_for_ports(): void {
		$this->assertSame( 'Preconnect supports HTTPS port 443 only.', es_optimizer_validate_domain_for_context( 'https://example.org:8443', 'preconnect' )['error'] );
		$this->assertTrue( es_optimizer_validate_domain_for_context( 'https://example.org:443', 'preconnect' )['valid'] );
		$this->assertSame( 'https://example.org:8443', es_optimizer_validate_domain_for_context( 'https://example.org:8443', 'dns_prefetch' )['domain'] );
	}
}
