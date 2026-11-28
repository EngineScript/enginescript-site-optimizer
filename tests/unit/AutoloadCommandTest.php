<?php
/**
 * Tests for the WP-CLI commands that list and restore switched-off options.
 *
 * Running the command on a real site, including with every plugin skipped, is
 * covered by the EngineScript server suite.
 *
 * @package EngineScript_Site_Optimizer
 */

require_once dirname( __DIR__ ) . '/doubles-wp-cli.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-es-optimizer-autoload-command.php';

/**
 * Exercises includes/class-es-optimizer-autoload-command.php with the WP-CLI double.
 */
final class AutoloadCommandTest extends EsOptimizerTestCase {

	/**
	 * Forget the recorded output before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		WP_CLI::reset();
	}

	/**
	 * Remove the database double after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	/**
	 * Set a database double whose flag reads return the given rows and whose updates answer with $updated.
	 *
	 * @param array<int, array<string, mixed>>|null $rows    Rows for get_results(), or null for a failed read.
	 * @param int|false                             $updated Row count for UPDATE queries.
	 * @return void
	 */
	private function database( ?array $rows = array(), int|false $updated = 1 ): void {
		$database            = new wpdb();
		$database->results   = $rows;
		$database->responder = static fn ( string $sql ): int|false => str_starts_with( $sql, 'UPDATE' ) ? $updated : 0;
		$GLOBALS['wpdb']     = $database;
	}

	/**
	 * Store a log.
	 *
	 * @param array<string, mixed> $log Log value.
	 * @return void
	 */
	private function store_log( array $log ): void {
		$GLOBALS['es_optimizer_test_options'][ ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION ] = $log;
	}

	/**
	 * Run a command method and return the message it stopped with, or null when it finished.
	 *
	 * @param string                $method     Method name.
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 * @return string|null Error message, or null.
	 */
	private function run_command( string $method, array $args = array(), array $assoc_args = array() ): ?string {
		try {
			( new ES_Optimizer_Autoload_Command() )->$method( $args, $assoc_args );
		} catch ( Es_Optimizer_Test_Cli_Error $error ) {
			return $error->getMessage();
		}

		return null;
	}

	/**
	 * Loading the file registers the command group.
	 *
	 * @return void
	 */
	public function test_command_is_registered(): void {
		$this->assertSame( 'ES_Optimizer_Autoload_Command', WP_CLI::$commands['site-optimizer autoload'] ?? null );
	}

	/**
	 * The list shows each logged option with its earlier setting, its state now, and when it was switched off.
	 *
	 * @return void
	 */
	public function test_list_shows_the_logged_options(): void {
		$this->database( array( array( 'option_name' => 'old_cache', 'autoload' => 'off', 'option_bytes' => 40 ) ) );
		$this->store_log(
			array(
				'old_cache' => array( 'previous' => 'auto', 'time' => 1700000000 ),
				'gone'      => array( 'previous' => 'on', 'time' => 0 ),
			)
		);

		$this->assertNull( $this->run_command( 'list_changes', array(), array( 'format' => 'json' ) ) );
		$this->assertCount( 1, WP_CLI::$formatted );

		[ $format, $items, $fields ] = WP_CLI::$formatted[0];

		$this->assertSame( 'json', $format );
		$this->assertSame( array( 'option', 'setting', 'now', 'switched_off' ), $fields );
		$this->assertSame(
			array(
				array( 'option' => 'gone', 'setting' => 'on', 'now' => 'deleted', 'switched_off' => '' ),
				array( 'option' => 'old_cache', 'setting' => 'auto', 'now' => 'off', 'switched_off' => '2023-11-14 22:13 UTC' ),
			),
			$items
		);
	}

	/**
	 * The list uses a table by default, and stops when the stored flags cannot be read.
	 *
	 * @return void
	 */
	public function test_list_default_format_and_failed_read(): void {
		$this->database();
		$this->assertNull( $this->run_command( 'list_changes' ) );
		$this->assertSame( 'table', WP_CLI::$formatted[0][0] );
		$this->assertSame( array(), WP_CLI::$formatted[0][1] );

		$this->database( null );
		$this->store_log( array( 'x' => array( 'previous' => 'on', 'time' => 1 ) ) );
		$this->assertSame( 'The stored autoload settings could not be read.', $this->run_command( 'list_changes' ) );
	}

	/**
	 * Restoring named options reports what it restored and why it skipped the rest.
	 *
	 * @return void
	 */
	public function test_restore_named_options(): void {
		$this->database( array(), 1 );
		$this->store_log( array( 'old_cache' => array( 'previous' => 'auto', 'time' => 1 ) ) );

		$this->assertNull( $this->run_command( 'restore', array( 'old_cache', 'never_logged', '' ) ) );
		$this->assertSame(
			array(
				array( 'warning', '1 skipped: not a valid option name' ),
				array( 'warning', '1 skipped: not switched off by Site Optimizer' ),
				array( 'success', '1 restored.' ),
			),
			WP_CLI::$output
		);
		$this->assertSame( array(), es_optimizer_autoload_get_log() );
	}

	/**
	 * --all restores every logged option; an option other code changed is only taken off the list.
	 *
	 * @return void
	 */
	public function test_restore_all_options(): void {
		$this->database( array(), 0 );
		$this->store_log(
			array(
				'one' => array( 'previous' => 'on', 'time' => 1 ),
				'123' => array( 'previous' => 'auto-on', 'time' => 2 ),
			)
		);

		$this->assertNull( $this->run_command( 'restore', array(), array( 'all' => '1' ) ) );
		$this->assertSame(
			array(
				array( 'warning', '2 skipped: already changed or deleted by other code, taken off the list' ),
				array( 'success', '0 restored.' ),
			),
			WP_CLI::$output
		);
	}

	/**
	 * A failed update ends with an error, and the option stays on the list for another attempt.
	 *
	 * @return void
	 */
	public function test_restore_failure_is_an_error(): void {
		$this->database( array(), false );
		$this->store_log( array( 'stuck' => array( 'previous' => 'on', 'time' => 1 ) ) );

		$this->assertSame( '0 restored; some options could not be restored.', $this->run_command( 'restore', array( 'stuck' ) ) );
		$this->assertSame( array( 'warning', '1 skipped: the database update failed' ), WP_CLI::$output[0] );
		$this->assertArrayHasKey( 'stuck', es_optimizer_autoload_get_log() );
	}

	/**
	 * Without names or --all, or with nothing logged, the command stops with a hint.
	 *
	 * @return void
	 */
	public function test_restore_needs_something_to_restore(): void {
		$this->database();
		$this->assertSame( 'Name the options to restore, or use --all.', $this->run_command( 'restore' ) );
		$this->assertSame( 'Site Optimizer has not switched off any option.', $this->run_command( 'restore', array(), array( 'all' => '1' ) ) );
	}
}
