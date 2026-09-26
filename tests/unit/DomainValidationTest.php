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
		$this->resetTestState();

		parent::setUp();
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
		$this->assertSame( '', $result['domain'] );
		$this->assertStringNotContainsString( $domain, $result['error'] );
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
				'expected_error' => 'HTTPS is required.',
			),
			'path'                => array(
				'domain'         => 'https://example.com/file.css',
				'expected_error' => 'File paths are not allowed; use domains only.',
			),
			'query'               => array(
				'domain'         => 'https://example.com?cache=bust',
				'expected_error' => 'Query parameters, fragments, and credentials are not allowed.',
			),
			'credentials'         => array(
				'domain'         => 'https://user:pass@example.com',
				'expected_error' => 'Query parameters, fragments, and credentials are not allowed.',
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
				'expected_error' => 'Invalid hostname.',
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
				'expected_error' => 'Invalid hostname.',
			),
			'invalid-port'        => array(
				'domain'         => 'https://example.com:0',
				'expected_error' => 'Invalid port.',
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
				'message' => 'The preconnect list could not be fully accepted: 1 line was rejected. Line 2: File paths are not allowed; use domains only.',
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
		$this->assertSame( 'https://cdn.example.com', $cdn_hint['href'] );
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
	 * Raw bytes must not be normalized into a different accepted origin.
	 *
	 * @since Unreleased
	 * @return void
	 */
	public function test_encoded_and_control_input_is_rejected_without_disclosure(): void {
		foreach ( array( 'https://exa%6dple.com', "https://exa\tmple.com", 'https://example.com\\secret', 'https://example.com/<token>' ) as $input ) {
			$result = es_optimizer_validate_single_domain( $input );
			$this->assertFalse( $result['valid'] );
			$this->assertSame( '', $result['domain'] );
			$this->assertStringNotContainsString( $input, $result['error'] );
		}
	}

	/**
	 * Preconnect has a stricter port policy than the shared origin validator.
	 *
	 * @since Unreleased
	 * @return void
	 */
	public function test_context_port_policy(): void {
		$this->assertTrue( es_optimizer_validate_domain_for_context( 'https://example.com:443', 'preconnect' )['valid'] );
		$this->assertFalse( es_optimizer_validate_domain_for_context( 'https://example.com:8443', 'preconnect' )['valid'] );
		$this->assertTrue( es_optimizer_validate_domain_for_context( 'https://example.com:8443', 'dns_prefetch' )['valid'] );
	}

	/**
	 * Exact budgets pass; one byte or nonblank line over the limit fails.
	 *
	 * @since Unreleased
	 * @return void
	 */
	public function test_raw_budget_boundaries_before_normalization(): void {
		$origin = 'https://example.com';
		$this->assertSame( '', es_optimizer_get_domain_list_budget_error( str_repeat( ' ', 32768 ) ) );
		$this->assertNotSame( '', es_optimizer_get_domain_list_budget_error( str_repeat( ' ', 32769 ) ) );
		$this->assertSame( '', es_optimizer_get_domain_list_budget_error( str_pad( $origin, 512 ) ) );
		$this->assertNotSame( '', es_optimizer_get_domain_list_budget_error( str_pad( $origin, 513 ) ) );
		$this->assertSame( '', es_optimizer_get_domain_list_budget_error( implode( "\n", array_fill( 0, 100, $origin ) ) ) );
		$this->assertNotSame( '', es_optimizer_get_domain_list_budget_error( implode( "\n", array_fill( 0, 101, $origin ) ) ) );
	}

	/**
	 * Malformed and oversize submissions preserve only the affected prior field.
	 *
	 * @since Unreleased
	 * @return void
	 */
	public function test_malformed_missing_and_oversize_fields_preserve_prior_values(): void {
		$previous                       = es_optimizer_get_default_options();
		$previous['preconnect_domains'] = 'https://prior.example.com';
		update_option( 'es_optimizer_options', $previous );
		foreach ( array( array(), new stdClass(), null, 42, false, str_repeat( 'x', 32769 ) ) as $invalid ) {
			$result = es_optimizer_validate_options(
				array( 'preconnect_domains' => $invalid, 'dns_prefetch_domains' => 'https://valid.example.com' )
			);
			$this->assertSame( $previous['preconnect_domains'], $result['preconnect_domains'] );
			$this->assertSame( 'https://valid.example.com', $result['dns_prefetch_domains'] );
		}
		$this->assertSame( $previous['preconnect_domains'], es_optimizer_validate_options( array() )['preconnect_domains'] );
		$this->assertSame( '', es_optimizer_validate_options( array( 'preconnect_domains' => '' ) )['preconnect_domains'] );
	}

	/**
	 * Warning samples contain reasons and counts without rejected URL secrets.
	 *
	 * @since Unreleased
	 * @return void
	 */
	public function test_safe_bounded_warning_preserves_foreign_notices(): void {
		global $wp_settings_errors;
		add_settings_error( 'foreign', 'unchanged', 'Other settings saved.', 'success' );
		$input = implode( "\n", array_fill( 0, 4, 'https://user:synthetic-secret@example.com' ) );
		es_optimizer_validate_domain_list( $input, 'preconnect' );
		es_optimizer_validate_domain_list( $input, 'preconnect' );
		$this->assertCount( 2, $wp_settings_errors );
		$this->assertSame( 'Other settings saved.', $wp_settings_errors[0]['message'] );
		$this->assertStringContainsString( '4 lines were rejected.', $wp_settings_errors[1]['message'] );
		$this->assertStringContainsString( 'Line 3:', $wp_settings_errors[1]['message'] );
		$this->assertStringNotContainsString( 'Line 4:', $wp_settings_errors[1]['message'] );
		$this->assertStringNotContainsString( 'synthetic-secret', $wp_settings_errors[1]['message'] );
		$this->assertStringNotContainsString( 'example.com', $wp_settings_errors[1]['message'] );
	}

	/**
	 * Old unsupported or oversized rows are not rewritten by hint reads.
	 *
	 * @since Unreleased
	 * @return void
	 */
	public function test_legacy_rows_are_retained_but_not_emitted(): void {
		foreach ( array( 'https://example.com:8443', str_repeat( 'x', 32769 ) ) as $old ) {
			$options                       = es_optimizer_get_default_options();
			$options['enable_preconnect']  = 1;
			$options['preconnect_domains'] = $old;
			update_option( 'es_optimizer_options', $options );
			$this->assertSame( array(), es_optimizer_add_preconnect_resource_hints( array(), 'preconnect' ) );
			$this->assertSame( $old, get_option( 'es_optimizer_options' )['preconnect_domains'] );
		}
	}

	/**
	 * Mixed foreign hints remain untouched and repeated calls do not duplicate.
	 *
	 * @since Unreleased
	 * @return void
	 */
	public function test_foreign_hint_shapes_and_exact_membership(): void {
		$options                       = es_optimizer_get_default_options();
		$options['enable_preconnect']  = 1;
		$options['preconnect_domains'] = 'https://example.com';
		update_option( 'es_optimizer_options', $options );
		$foreign = array( array( 'href' => new stdClass() ), null, 7, array( 'href' => 'https://example.com', 'crossorigin' => 'use-credentials' ) );
		$this->assertSame( $foreign, es_optimizer_add_preconnect_resource_hints( $foreign, 'preconnect' ) );
		$hints = es_optimizer_add_preconnect_resource_hints( array(), 'preconnect' );
		$this->assertSame( $hints, es_optimizer_add_preconnect_resource_hints( $hints, 'preconnect' ) );
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
