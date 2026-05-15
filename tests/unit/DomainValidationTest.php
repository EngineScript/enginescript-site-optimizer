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

		delete_option( 'es_optimizer_options' );
		es_optimizer_clear_options_cache();
	}

	/**
	 * Clean HTTPS domains are accepted and normalized.
	 */
	public function test_accepts_clean_https_domains(): void {
		$result = es_optimizer_validate_single_domain( 'https://Fonts.GStatic.com/' );

		$this->assertTrue( $result['valid'] );
		$this->assertSame( 'https://fonts.gstatic.com', $result['domain'] );
	}

	/**
	 * Unsafe or unclean domains are rejected.
	 *
	 * @param string $domain Domain to validate.
	 */
	#[DataProvider( 'rejectedDomainProvider' )]
	public function test_rejects_unsafe_or_unclean_domains( string $domain ): void {
		$result = es_optimizer_validate_single_domain( $domain );

		$this->assertFalse( $result['valid'] );
		$this->assertNotSame( '', $result['error'] );
	}

	/**
	 * Provide rejected domains.
	 *
	 * @return array<string, array{domain: string}>
	 */
	public static function rejectedDomainProvider(): array {
		return array(
			'http'                => array( 'domain' => 'http://example.com' ),
			'path'                => array( 'domain' => 'https://example.com/file.css' ),
			'query'               => array( 'domain' => 'https://example.com?cache=bust' ),
			'credentials'         => array( 'domain' => 'https://user:pass@example.com' ),
			'localhost'           => array( 'domain' => 'https://localhost' ),
			'localhost-subdomain' => array( 'domain' => 'https://cdn.localhost' ),
			'special-use-domain'  => array( 'domain' => 'https://cache.internal' ),
			'single-label-host'   => array( 'domain' => 'https://cdn' ),
			'public-ip'           => array( 'domain' => 'https://8.8.8.8' ),
			'private-ip'          => array( 'domain' => 'https://192.168.1.1' ),
			'obfuscated-ip'       => array( 'domain' => 'https://127.000.000.001' ),
			'invalid-label'       => array( 'domain' => 'https://-example.com' ),
			'invalid-port'        => array( 'domain' => 'https://example.com:0' ),
		);
	}

	/**
	 * Domain lists are cleaned, filtered, and deduplicated.
	 */
	public function test_domain_list_keeps_unique_clean_https_domains(): void {
		$input = "https://fonts.googleapis.com\nhttps://example.com/path\nhttps://fonts.googleapis.com\nhttps://cdn.example.com";

		$this->assertSame(
			"https://fonts.googleapis.com\nhttps://cdn.example.com",
			es_optimizer_validate_domain_list( $input, 'preconnect' )
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

		$this->assertContains( array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' ), $preconnect_hints );
		$this->assertContains( array( 'href' => 'https://cdn.example.com' ), $preconnect_hints );
		$this->assertContains( 'https://static.example.com', $dns_prefetch_urls );
	}
}
