<?php
/**
 * Base class for the unit suite.
 *
 * @package EngineScript_Site_Optimizer
 */

use PHPUnit\Framework\TestCase;

/**
 * Starts every test from a clean record of calls, no stored option, and no notices.
 */
abstract class EsOptimizerTestCase extends TestCase {

	/**
	 * Reset the stand-ins for WordPress.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->reset();
	}

	/**
	 * Leave nothing behind for the next test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->reset();
		parent::tearDown();
	}

	/**
	 * Clear the recorded calls, the stored option, the notices, and the field descriptions.
	 *
	 * @return void
	 */
	private function reset(): void {
		es_optimizer_test_reset();
		delete_option( 'es_optimizer_options' );
		$GLOBALS['wp_settings_errors'] = array();
		unset( $GLOBALS['es_optimizer_field_descriptions'] );
	}

	/**
	 * Store the plugin's option, starting from the defaults.
	 *
	 * @param array<string, mixed> $values Values to store over the defaults.
	 * @return void
	 */
	protected function store_options( array $values ): void {
		update_option( 'es_optimizer_options', array_merge( es_optimizer_get_default_options(), $values ) );
	}

	/**
	 * Return what a callback prints.
	 *
	 * @param callable $callback Callback that prints.
	 * @return string Printed output.
	 */
	protected function capture( callable $callback ): string {
		ob_start();

		try {
			$callback();
		} finally {
			$output = (string) ob_get_clean();
		}

		return $output;
	}

	/**
	 * The notices registered so far, as setting/code/type triples.
	 *
	 * @return array<int, string> One entry per notice.
	 */
	protected function notice_keys(): array {
		$keys = array();

		foreach ( (array) $GLOBALS['wp_settings_errors'] as $error ) {
			$keys[] = $error['setting'] . '/' . $error['code'] . '/' . $error['type'];
		}

		return $keys;
	}

	/**
	 * The messages of the notices registered so far.
	 *
	 * @return array<int, string> Messages.
	 */
	protected function notice_messages(): array {
		return array_column( (array) $GLOBALS['wp_settings_errors'], 'message' );
	}

	/**
	 * The recorded calls to remove_action() and remove_filter(), as "hook callback priority" lines.
	 *
	 * @return array<int, string> One line per removal.
	 */
	protected function removals(): array {
		return array_map(
			static fn ( array $removal ): string => $removal[0] . ' ' . $removal[1] . ' ' . $removal[2],
			$GLOBALS['es_optimizer_test']['removed']
		);
	}
}
