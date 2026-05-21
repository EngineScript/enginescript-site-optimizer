<?php
/**
 * Domain validation and resource hint tests.
 *
 * @package EngineScript_Site_Optimizer
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for optimizer domain validation.
 */
final class DomainValidationTest extends TestCase {
	/**
	 * Reset options between tests.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetTestState();
	}

	/**
	 * Reset options after tests.
	 */
	protected function tearDown(): void {
		$this->resetTestState();

		parent::tearDown();
	}

	/**
	 * Reset mutable WordPress test state used by these tests.
	 */
	private function resetTestState(): void {
		global $wp_settings_errors;

		delete_option( 'es_optimizer_options' );
		es_optimizer_clear_options_cache();
		$wp_settings_errors = array();
	}

	/**
	 * Get registered settings errors from the WordPress test state.
	 *
	 * @return array<int, array<string, string>> Registered settings errors.
	 */
	private function get_settings_errors(): array {
		global $wp_settings_errors;

		return is_array( $wp_settings_errors ) ? $wp_settings_errors : array();
	}

	/**
	 * Clean HTTPS domains are accepted and normalized.
	 *
	 * @param string $domain   Domain to validate.
	 * @param string $expected Expected normalized domain.
	 */
	#[DataProvider( 'acceptedDomainProvider' )]
	public function test_accepts_clean_https_domains( string $domain, string $expected ): void {
		$result = es_optimizer_validate_single_domain( $domain );

		$this->assertTrue( $result['valid'] );
		$this->assertSame( $expected, $result['domain'] );
	}

	/**
	 * Provide accepted domains.
	 *
	 * @return array<string, array{domain: string, expected: string}>
	 */
	public static function acceptedDomainProvider(): array {
		return array(
			'uppercase-with-root-path' => array(
				'domain'   => 'https://Fonts.GStatic.com/',
				'expected' => 'https://fonts.gstatic.com',
			),
			'default-https-port'      => array(
				'domain'   => 'https://static.example.com:443',
				'expected' => 'https://static.example.com',
			),
			'custom-port'             => array(
				'domain'   => 'https://cdn.example.com:8443',
				'expected' => 'https://cdn.example.com:8443',
			),
		);
	}

	/**
	 * Unsafe or unclean domains are rejected.
	 *
	 * @param string $domain         Domain to validate.
	 * @param string $expected_error Expected error message fragment.
	 */
	#[DataProvider( 'rejectedDomainProvider' )]
	public function test_rejects_unsafe_or_unclean_domains( string $domain, string $expected_error ): void {
		$result = es_optimizer_validate_single_domain( $domain );

		$this->assertFalse( $result['valid'] );
		$this->assertStringContainsString( $domain, $result['error'] );
		$this->assertStringContainsString( $expected_error, $result['error'] );
	}

	/**
	 * Provide rejected domains.
	 *
	 * @return array<string, array{domain: string, expected_error: string}>
	 */
	public static function rejectedDomainProvider(): array {
		return array(
			'http'                => array(
				'domain'         => 'http://example.com',
				'expected_error' => 'invalid URL',
			),
			'path'                => array(
				'domain'         => 'https://example.com/file.css',
				'expected_error' => 'file paths are not allowed; use domains only',
			),
			'query'               => array(
				'domain'         => 'https://example.com?cache=bust',
				'expected_error' => 'query parameters, fragments, and credentials are not allowed',
			),
			'credentials'         => array(
				'domain'         => 'https://user:pass@example.com',
				'expected_error' => 'query parameters, fragments, and credentials are not allowed',
			),
			'localhost'           => array(
				'domain'         => 'https://localhost',
				'expected_error' => 'IP addresses and private, local, or reserved hosts are not allowed',
			),
			'localhost-subdomain' => array(
				'domain'         => 'https://cdn.localhost',
				'expected_error' => 'IP addresses and private, local, or reserved hosts are not allowed',
			),
			'special-use-domain'  => array(
				'domain'         => 'https://cache.internal',
				'expected_error' => 'IP addresses and private, local, or reserved hosts are not allowed',
			),
			'single-label-host'   => array(
				'domain'         => 'https://cdn',
				'expected_error' => 'invalid hostname',
			),
			'public-ip'           => array(
				'domain'         => 'https://8.8.8.8',
				'expected_error' => 'IP addresses and private, local, or reserved hosts are not allowed',
			),
			'private-ip'          => array(
				'domain'         => 'https://192.168.1.1',
				'expected_error' => 'IP addresses and private, local, or reserved hosts are not allowed',
			),
			'obfuscated-ip'       => array(
				'domain'         => 'https://127.000.000.001',
				'expected_error' => 'IP addresses and private, local, or reserved hosts are not allowed',
			),
			'invalid-label'       => array(
				'domain'         => 'https://-example.com',
				'expected_error' => 'invalid URL',
			),
			'invalid-port'        => array(
				'domain'         => 'https://example.com:0',
				'expected_error' => 'invalid port',
			),
		);
	}

	/**
	 * Domain lists are cleaned, filtered, and deduplicated.
	 */
	public function test_domain_list_keeps_unique_clean_https_domains(): void {
		$input = "https://fonts.googleapis.com\nhttps://example.com/path\nhttps://fonts.googleapis.com\nhttps://cdn.example.com";

		$result = es_optimizer_validate_domain_list( $input, 'preconnect' );

		$this->assertSame(
			"https://fonts.googleapis.com\nhttps://cdn.example.com",
			$result
		);
		$this->assertContains(
			array(
				'setting' => 'es_optimizer_options',
				'code'    => 'preconnect_security',
				'message' => 'Some preconnect domains were rejected for security reasons: https://example.com/path (file paths are not allowed; use domains only)',
				'type'    => 'warning',
			),
			$this->get_settings_errors()
		);
	}

	/**
	 * Resource hints use the native WordPress wp_resource_hints filter contract.
	 */
	public function test_resource_hints_use_wordpress_filter_contract(): void {
		$options                         = es_optimizer_get_default_options();
		$options['enable_preconnect']    = 1;
		$options['preconnect_domains']   = "https://fonts.gstatic.com\nhttps://cdn.example.com";
		$options['enable_dns_prefetch']  = 1;
		$options['dns_prefetch_domains'] = 'https://static.example.com';

		update_option( 'es_optimizer_options', $options );
		es_optimizer_clear_options_cache();

		$preconnect_hints  = es_optimizer_add_preconnect_resource_hints( array(), 'preconnect' );
		$dns_prefetch_urls = es_optimizer_add_dns_prefetch_resource_hints( array(), 'dns-prefetch' );
		$cdn_hint          = $this->find_resource_hint_by_href( $preconnect_hints, 'https://cdn.example.com' );

		$this->assertContains( array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' ), $preconnect_hints );
		$this->assertIsArray( $cdn_hint );
		$this->assertArrayNotHasKey( 'crossorigin', $cdn_hint );
		$this->assertContains( 'https://static.example.com', $dns_prefetch_urls );
	}

	/**
	 * Resource hints are not added when their feature flags are disabled.
	 */
	public function test_resource_hints_are_not_added_when_feature_flags_are_disabled(): void {
		$options                         = es_optimizer_get_default_options();
		$options['enable_preconnect']    = 0;
		$options['preconnect_domains']   = 'https://cdn.example.com';
		$options['enable_dns_prefetch']  = 0;
		$options['dns_prefetch_domains'] = 'https://static.example.com';

		update_option( 'es_optimizer_options', $options );
		es_optimizer_clear_options_cache();

		$existing_preconnect_hints = array(
			array( 'href' => 'https://existing.example.com' ),
		);
		$existing_dns_prefetch_urls = array( 'https://existing.example.com' );

		$this->assertSame(
			$existing_preconnect_hints,
			es_optimizer_add_preconnect_resource_hints( $existing_preconnect_hints, 'preconnect' )
		);
		$this->assertSame(
			$existing_dns_prefetch_urls,
			es_optimizer_add_dns_prefetch_resource_hints( $existing_dns_prefetch_urls, 'dns-prefetch' )
		);
	}

	/**
	 * Find a resource hint by href.
	 *
	 * @param array<int, array<string, mixed>|string> $hints Resource hints.
	 * @param string                                  $href  Hint href to locate.
	 * @return array<string, mixed>|null Matching hint, if present.
	 */
	private function find_resource_hint_by_href( array $hints, string $href ): ?array {
		foreach ( $hints as $hint ) {
			if ( is_array( $hint ) && $href === ( $hint['href'] ?? null ) ) {
				return $hint;
			}
		}

		return null;
	}
}
