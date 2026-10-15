<?php
/**
 * Tests for reading, validating, and reporting the plugin's option.
 *
 * @package EngineScript_Site_Optimizer
 */

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Defaults, normalization, the save path, and the notices it registers.
 */
final class OptionsAndNoticesTest extends EsOptimizerTestCase {

	/**
	 * Eleven flags, all off, and two empty lists.
	 *
	 * @return void
	 */
	public function test_defaults(): void {
		$defaults = es_optimizer_get_default_options();

		$this->assertCount( 13, $defaults );
		$this->assertSame( es_optimizer_get_boolean_option_keys(), array_keys( array_diff_key( $defaults, array_flip( array( 'preconnect_domains', 'dns_prefetch_domains' ) ) ) ) );
		$this->assertSame( array( 0 ), array_values( array_unique( array_intersect_key( $defaults, array_flip( es_optimizer_get_boolean_option_keys() ) ) ) ) );
		$this->assertSame( '', $defaults['preconnect_domains'] );
		$this->assertSame( '', $defaults['dns_prefetch_domains'] );
		$this->assertSame( $defaults, es_optimizer_get_options(), 'With nothing stored, the defaults are read.' );
	}

	/**
	 * Provide stored flag values and how WordPress reads them.
	 *
	 * @return array<string, array{0: mixed, 1: int}>
	 */
	public static function flagProvider(): array {
		return array(
			'integer 1'        => array( 1, 1 ),
			'string 1'         => array( '1', 1 ),
			'true'             => array( true, 1 ),
			'word yes'         => array( 'yes', 1 ),
			'word off'         => array( 'off', 1 ),
			'word false'       => array( 'false', 0 ),
			'word FALSE'       => array( 'FALSE', 0 ),
			'word False'       => array( 'False', 0 ),
			'false with space' => array( 'false ', 1 ),
			'string 0'         => array( '0', 0 ),
			'integer 0'        => array( 0, 0 ),
			'false'            => array( false, 0 ),
			'empty string'     => array( '', 0 ),
			'null'             => array( null, 0 ),
			'empty array'      => array( array(), 0 ),
			'non-empty array'  => array( array( 0 ), 1 ),
		);
	}

	/**
	 * A stored flag is read as WordPress reads a boolean (CR-SEM-003).
	 *
	 * @param mixed $stored   Stored value.
	 * @param int   $expected Normalized flag.
	 * @return void
	 */
	#[DataProvider( 'flagProvider' )]
	public function test_stored_flags_read_like_core_booleans( mixed $stored, int $expected ): void {
		update_option( 'es_optimizer_options', array( 'disable_emojis' => $stored ) );

		$this->assertSame( $expected, es_optimizer_get_options()['disable_emojis'] );
		$this->assertSame( 1 === $expected, es_optimizer_is_option_enabled( 'disable_emojis' ) );
	}

	/**
	 * A submitted flag is read the same way.
	 *
	 * @param mixed $submitted Submitted value.
	 * @param int   $expected  Saved flag.
	 * @return void
	 */
	#[DataProvider( 'flagProvider' )]
	public function test_submitted_flags_read_like_core_booleans( mixed $submitted, int $expected ): void {
		$this->assertSame( $expected, es_optimizer_validate_options( array( 'disable_emojis' => $submitted ) )['disable_emojis'] );
	}

	/**
	 * Unusable stored values fall back to the defaults; unknown keys are dropped.
	 *
	 * @return void
	 */
	public function test_stored_option_is_normalized(): void {
		foreach ( array( 'a string', 7, null, false ) as $stored ) {
			update_option( 'es_optimizer_options', $stored );
			$this->assertSame( es_optimizer_get_default_options(), es_optimizer_get_options(), var_export( $stored, true ) );
		}

		update_option( 'es_optimizer_options', array( 'unknown' => 1, 'remove_wp_version' => '1', 'preconnect_domains' => 12345, 'dns_prefetch_domains' => array( 'x' ) ) );
		$options = es_optimizer_get_options();
		$this->assertArrayNotHasKey( 'unknown', $options );
		$this->assertSame( 1, $options['remove_wp_version'] );
		$this->assertSame( '12345', $options['preconnect_domains'] );
		$this->assertSame( '', $options['dns_prefetch_domains'] );
		$this->assertFalse( es_optimizer_is_option_enabled( 'not_an_option' ) );
	}

	/**
	 * The normalizer, called directly, treats a stored value that is not an array as empty.
	 *
	 * @return void
	 */
	public function test_the_normalizer_accepts_any_stored_value(): void {
		$defaults = es_optimizer_get_default_options();

		foreach ( array( 'a string', 7, null, false, new stdClass() ) as $stored ) {
			$this->assertSame( $defaults, es_optimizer_normalize_stored_options( $stored, $defaults ), get_debug_type( $stored ) );
		}

		$this->assertSame( 1, es_optimizer_normalize_stored_options( array( 'disable_emojis' => 'on' ), $defaults )['disable_emojis'] );
	}

	/**
	 * A submission that is not an array keeps the stored settings and says so, without a confirmation.
	 *
	 * @return void
	 */
	public function test_a_submission_that_is_not_an_array(): void {
		$this->store_options( array( 'remove_rsd_link' => 1, 'preconnect_domains' => 'https://kept.example.org' ) );

		$this->assertSame( es_optimizer_get_options(), es_optimizer_validate_options( 'not-an-array' ) );
		$this->assertSame( array( 'es_optimizer_options/options_invalid/warning' ), $this->notice_keys() );
		$this->assertSame( array( 'The submitted settings have an invalid format. Your previous settings were retained.' ), $this->notice_messages() );
	}

	/**
	 * An unchecked box is absent from a submission and saves as off; an absent list keeps its stored value.
	 *
	 * @return void
	 */
	public function test_a_submission_without_some_fields(): void {
		$this->store_options( array( 'remove_rsd_link' => 1, 'preconnect_domains' => 'https://kept.example.org' ) );
		$saved = es_optimizer_validate_options( array( 'disable_emojis' => '1' ) );

		$this->assertSame( 1, $saved['disable_emojis'] );
		$this->assertSame( 0, $saved['remove_rsd_link'] );
		$this->assertSame( 'https://kept.example.org', $saved['preconnect_domains'] );
		$this->assertSame( array(), $this->notice_keys(), 'Nothing rejected: WordPress adds its own confirmation.' );
	}

	/**
	 * A save with a rejected line confirms itself under the native setting and code (CR-UI-001).
	 *
	 * @return void
	 */
	public function test_a_save_with_a_warning_is_confirmed_once(): void {
		es_optimizer_validate_options( array( 'preconnect_domains' => "https://ok.example.org\nnot a url" ) );
		$this->assertSame( array( 'es_optimizer_options/preconnect_security/warning', 'general/settings_updated/success' ), $this->notice_keys() );
		$this->assertSame( 'Settings saved.', $this->notice_messages()[1] );

		es_optimizer_validate_options( array( 'preconnect_domains' => "https://ok.example.org\nnot a url" ) );
		$this->assertSame( array( 'es_optimizer_options/preconnect_security/warning', 'general/settings_updated/success' ), $this->notice_keys(), 'Validated twice, as on a first insert: one of each.' );
	}

	/**
	 * A confirmation that is already there is not doubled, and the list validator alone adds none.
	 *
	 * @return void
	 */
	public function test_the_confirmation_is_not_doubled_or_added_by_the_list_validator(): void {
		add_settings_error( 'general', 'settings_updated', 'Saved by other code.', 'success' );
		es_optimizer_validate_options( array( 'dns_prefetch_domains' => 'not a url' ) );
		$this->assertSame( array( 'general/settings_updated/success', 'es_optimizer_options/dns_prefetch_security/warning' ), $this->notice_keys() );

		$GLOBALS['wp_settings_errors'] = array();
		es_optimizer_validate_domain_list( "https://ok.example.org\nnot a url", 'preconnect' );
		$this->assertSame( array( 'es_optimizer_options/preconnect_security/warning' ), $this->notice_keys() );
	}

	/**
	 * A corrupt notice list does not stop the confirmation code.
	 *
	 * @return void
	 */
	public function test_the_confirmation_tolerates_a_corrupt_notice_list(): void {
		foreach ( array( 'a string', 7, null, array( 'x', 3, array( 'setting' => array() ) ) ) as $corrupt ) {
			$GLOBALS['wp_settings_errors'] = $corrupt;
			es_optimizer_confirm_save_beside_warnings();
			$this->assertSame( $corrupt, $GLOBALS['wp_settings_errors'] );
		}
	}

	/**
	 * Each rejected line's reason is a sentence; a list cut short says so in words (O-016).
	 *
	 * @return void
	 */
	public function test_rejected_lines_notice_text(): void {
		es_optimizer_validate_domain_list( "https://ok.example.org\nhttp://plain.example.org", 'preconnect' );
		$this->assertSame( array( 'The preconnect list could not be fully accepted: 1 line was rejected. Line 2: HTTPS is required.' ), $this->notice_messages() );

		$GLOBALS['wp_settings_errors'] = array();
		es_optimizer_validate_domain_list( "http://a.example.org\nhttps://ok.example.org\nhttps://b.example.org/path\nhttps://c.example.org:8443", 'preconnect' );
		$this->assertSame(
			array( 'The preconnect list could not be fully accepted: 3 lines were rejected. Line 1: HTTPS is required. Line 3: File paths are not allowed; use domains only. Line 4: Preconnect supports HTTPS port 443 only.' ),
			$this->notice_messages()
		);

		$GLOBALS['wp_settings_errors'] = array();
		es_optimizer_validate_domain_list( "http://a.example.org\nnot a url\nhttps://ok.example.org\nhttps://127.0.0.1\nhttps://x.example.org:99999\nhttps://y.example.org?q=1", 'dns_prefetch' );
		$message = $this->notice_messages()[0];
		$this->assertStringStartsWith( 'The DNS prefetch list could not be fully accepted: 5 lines were rejected. Line 1: HTTPS is required. Line 2: ', $message );
		$this->assertStringEndsWith( ' Line 4: IP addresses and private, local, or reserved domain names are not allowed. Further rejected lines are not listed.', $message );
		$this->assertSame( 3, substr_count( $message, 'Line ' ) );
		$this->assertDoesNotMatchRegularExpression( '/\.,|\.\.|\s{2}/', $message );
	}

	/**
	 * A rejected line's reason never repeats what was submitted.
	 *
	 * @return void
	 */
	public function test_notices_do_not_repeat_submitted_text(): void {
		es_optimizer_validate_domain_list( "https://user:secret@token.example.org\nhttps://<b>markup</b>.example.org", 'preconnect' );
		$message = implode( ' ', $this->notice_messages() );

		$this->assertStringNotContainsString( 'secret', $message );
		$this->assertStringNotContainsString( 'token.example', $message );
		$this->assertStringNotContainsString( '<b>', $message );
	}

	/**
	 * A list over a limit, or a list that is not text, keeps the stored list and says why.
	 *
	 * @return void
	 */
	public function test_lists_that_cannot_be_read_keep_the_stored_list(): void {
		$this->store_options( array( 'preconnect_domains' => 'https://kept.example.org', 'dns_prefetch_domains' => 'https://kept-dns.example.org' ) );
		$saved = es_optimizer_validate_options( array( 'preconnect_domains' => array( 'x' ), 'dns_prefetch_domains' => str_repeat( 'x', 40000 ) ) );

		$this->assertSame( 'https://kept.example.org', $saved['preconnect_domains'] );
		$this->assertSame( 'https://kept-dns.example.org', $saved['dns_prefetch_domains'] );
		$this->assertSame(
			array(
				'The preconnect list could not be fully accepted: Enter domains as text. Your previous list was retained.',
				'The DNS prefetch list could not be fully accepted: Each domain list is limited to 32768 bytes. Your previous list was retained.',
				'Settings saved.',
			),
			$this->notice_messages()
		);
	}

	/**
	 * A plugin warning is registered once per code, beside other code's notices.
	 *
	 * @return void
	 */
	public function test_warnings_are_registered_once_per_code(): void {
		add_settings_error( 'other_plugin', 'preconnect_security', 'Not ours.', 'warning' );
		es_optimizer_add_settings_warning( 'preconnect_security', 'First.' );
		es_optimizer_add_settings_warning( 'preconnect_security', 'Second.' );
		es_optimizer_add_settings_warning( 'dns_prefetch_security', 'Third.' );

		$this->assertSame( array( 'Not ours.', 'First.', 'Third.' ), $this->notice_messages() );
	}

	/**
	 * Entries of the notice list that are not arrays are passed over when a warning is registered.
	 *
	 * @return void
	 */
	public function test_a_warning_is_registered_beside_corrupt_notice_entries(): void {
		$lookalike = new ArrayObject(
			array(
				'setting' => 'es_optimizer_options',
				'code'    => 'preconnect_security',
			)
		);

		$GLOBALS['wp_settings_errors'] = array( 'a string', 7, null, array( 'setting' => 'es_optimizer_options' ), $lookalike );
		es_optimizer_add_settings_warning( 'preconnect_security', 'First.' );
		es_optimizer_add_settings_warning( 'preconnect_security', 'Second.' );

		$this->assertCount( 6, $GLOBALS['wp_settings_errors'], 'An object that only looks like a notice does not count as one.' );
		$this->assertSame( 'First.', $GLOBALS['wp_settings_errors'][5]['message'] );
	}

	/**
	 * The three budgets, each with its own reason.
	 *
	 * @return void
	 */
	public function test_budget_reasons(): void {
		$line = 'https://a.example.org';

		$this->assertSame( '', es_optimizer_get_domain_list_budget_error( implode( "\n", array_fill( 0, 100, $line ) ) . "\n\n   \n" ) );
		$this->assertSame( 'Each domain list is limited to 100 nonblank lines, including duplicates and invalid entries.', es_optimizer_get_domain_list_budget_error( implode( "\r\n", array_fill( 0, 101, $line ) ) ) );
		$this->assertSame( 'Each nonblank domain line is limited to 512 bytes.', es_optimizer_get_domain_list_budget_error( str_repeat( 'a', 513 ) ) );
		$this->assertSame( 'Each domain list is limited to 32768 bytes.', es_optimizer_get_domain_list_budget_error( str_repeat( ' ', 32769 ) ) );
	}

	/**
	 * The lists that reach a page: each context's policy, no duplicates, nothing from a list over a limit.
	 *
	 * @return void
	 */
	public function test_validated_domains_per_context(): void {
		$this->store_options(
			array(
				'preconnect_domains'   => "https://a.example.org\nhttps://a.example.org/\nhttps://port.example.org:8443\nnot a url",
				'dns_prefetch_domains' => "https://a.example.org\nhttps://port.example.org:8443",
			)
		);

		$this->assertSame( array( 'https://a.example.org' ), es_optimizer_get_validated_domains( 'preconnect_domains' ) );
		$this->assertSame( array( 'https://a.example.org', 'https://port.example.org:8443' ), es_optimizer_get_validated_domains( 'dns_prefetch_domains' ) );
		$this->assertTrue( es_optimizer_has_unsupported_preconnect_domains( 'https://port.example.org:8443' ) );
		$this->assertFalse( es_optimizer_has_unsupported_preconnect_domains( "https://a.example.org\nnot a url" ) );
		$this->assertFalse( es_optimizer_has_unsupported_preconnect_domains( implode( "\n", array_fill( 0, 101, 'https://port.example.org:8443' ) ) ) );

		$this->store_options( array( 'preconnect_domains' => implode( "\n", array_fill( 0, 101, 'https://a.example.org' ) ) ) );
		$this->assertSame( array(), es_optimizer_get_validated_domains( 'preconnect_domains' ) );
	}
}
