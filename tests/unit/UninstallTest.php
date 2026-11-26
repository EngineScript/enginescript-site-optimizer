<?php
/**
 * Tests for uninstall.php: removing the plugin's option on one site and on every site of a network,
 * and each way the removal can fail. UninstallFailureTest covers the stop before file deletion.
 *
 * @package EngineScript_Site_Optimizer
 */

/**
 * Exercises the uninstall handler with the wpdb and multisite doubles from the bootstrap.
 */
final class UninstallTest extends EsOptimizerTestCase {

	/**
	 * Load uninstall.php once. Loading it also runs it: on one site, with no stored option, it
	 * deletes nothing and verifies the option is gone.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::load_uninstall( self::database( static fn (): int => 0 ) );
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
	 * Define the uninstall constant and load the file, unless it is already loaded.
	 *
	 * @param wpdb $database Database double for the run on load.
	 * @return void
	 */
	private static function load_uninstall( wpdb $database ): void {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'enginescript-site-optimizer/enginescript-site-optimizer.php' );
		}

		$GLOBALS['wpdb'] = $database;
		require_once dirname( __DIR__, 2 ) . '/uninstall.php';
	}

	/**
	 * Build a database double and make it the global $wpdb.
	 *
	 * @param callable $responder Answers each query (SQL, double) with a row count or false.
	 * @return wpdb The double.
	 */
	private static function database( callable $responder ): wpdb {
		$database            = new wpdb();
		$database->responder = $responder;
		$GLOBALS['wpdb']     = $database;

		return $database;
	}

	/**
	 * Build a network double: sites with the given IDs, and per-site option rows.
	 *
	 * @param array<int, int>      $site_ids   Site IDs, ascending.
	 * @param array<int, int|bool> $option_row Row count the option check returns per site (default 0).
	 * @param array<int, int>      $limits     Highest site ID returned by each limit read, in order.
	 * @return wpdb The double.
	 */
	private function network( array $site_ids, array $option_row = array(), array $limits = array() ): wpdb {
		$GLOBALS['es_optimizer_test']['multisite'] = true;
		$limits                                    = $limits ?: array( max( $site_ids ) );

		return self::database(
			static function ( string $sql, wpdb $db ) use ( $site_ids, $option_row, &$limits ): int|false {
				if ( str_contains( $sql, 'ORDER BY blog_id DESC' ) ) {
					$db->var = count( $limits ) > 1 ? array_shift( $limits ) : $limits[0];

					return 1;
				}

				if ( preg_match( '/blog_id > (\d+) AND blog_id <= (\d+)/', $sql, $match ) ) {
					$page    = array_values(
						array_filter( $site_ids, static fn ( int $id ): bool => $id > (int) $match[1] && $id <= (int) $match[2] )
					);
					$db->col = array_slice( $page, 0, 100 );

					return count( $db->col );
				}

				return $option_row[ get_current_blog_id() ] ?? 0;
			}
		);
	}

	/**
	 * Run a callback and return the message of the failure it raises.
	 *
	 * @param callable $callback Callback expected to fail.
	 * @return string Failure message.
	 */
	private function failure( callable $callback ): string {
		try {
			$callback();
		} catch ( RuntimeException $error ) {
			return $error->getMessage();
		}

		$this->fail( 'The callback did not fail.' );
	}

	public function test_single_site_removes_the_option_and_restores_error_suppression(): void {
		$this->store_options( array() );
		$database = self::database( static fn (): int => 0 );

		es_optimizer_uninstall_options();

		$this->assertFalse( get_option( 'es_optimizer_options' ) );
		$this->assertFalse( $database->suppressing );
		$this->assertStringContainsString( "option_name = es_optimizer_options", $database->queries[0] );
	}

	public function test_single_site_fails_when_the_option_is_still_stored(): void {
		self::database( static fn (): int => 1 );

		$this->assertSame( 'The settings of site 1 are still stored after deletion.', $this->failure( 'es_optimizer_uninstall_options' ) );
	}

	public function test_single_site_fails_when_the_check_cannot_run(): void {
		self::database( static fn (): bool => false );

		$this->assertSame( 'The settings of site 1 could not be verified as removed.', $this->failure( 'es_optimizer_uninstall_options' ) );
	}

	public function test_single_site_fails_when_the_current_site_changes_during_deletion(): void {
		self::database( static fn (): int => 0 );
		$GLOBALS['es_optimizer_test']['on_delete'] = static function (): void {
			$GLOBALS['es_optimizer_test']['blog_id'] = 9;
		};

		$this->assertSame(
			'The current site changed while the settings of site 1 were being deleted.',
			$this->failure( 'es_optimizer_uninstall_current_site_options' )
		);
	}

	public function test_every_step_fails_without_the_database_object(): void {
		unset( $GLOBALS['wpdb'] );
		$expected = 'The WordPress database object is unavailable.';

		$this->assertSame( $expected, $this->failure( 'es_optimizer_uninstall_options' ) );
		$this->assertSame( $expected, $this->failure( 'es_optimizer_uninstall_get_site_limit' ) );
		$this->assertSame( $expected, $this->failure( static fn () => es_optimizer_uninstall_get_site_ids( 0, 5 ) ) );
		$this->assertSame( $expected, $this->failure( 'es_optimizer_uninstall_current_site_options' ) );
	}

	public function test_network_removes_the_option_from_every_site_and_switches_back(): void {
		$database = $this->network( array( 1, 2, 3 ) );

		es_optimizer_uninstall_options();

		$this->assertSame( 1, get_current_blog_id() );
		$this->assertSame( array(), $GLOBALS['_wp_switched_stack'] );
		$this->assertFalse( $GLOBALS['switched'] );
		$this->assertSame( 3, $GLOBALS['es_optimizer_test']['flushes'] );
		$this->assertFalse( $database->suppressing );
	}

	public function test_network_reads_sites_in_pages_of_one_hundred(): void {
		$database = $this->network( range( 1, 150 ) );

		es_optimizer_uninstall_network_options();

		$pages = array_filter( $database->queries, static fn ( string $sql ): bool => str_contains( $sql, 'blog_id >' ) );
		$this->assertCount( 2, $pages );
		$this->assertSame( 150, $GLOBALS['es_optimizer_test']['flushes'] );
	}

	public function test_network_stops_when_no_site_is_left_below_the_limit(): void {
		$this->network( array( 1 ), array(), array( 5 ) );

		es_optimizer_uninstall_network_options();

		$this->assertSame( 1, $GLOBALS['es_optimizer_test']['flushes'] );
	}

	public function test_network_with_no_sites_does_nothing(): void {
		$GLOBALS['es_optimizer_test']['multisite'] = true;
		self::database( static fn (): int => 0 );

		es_optimizer_uninstall_network_options();

		$this->assertSame( 0, $GLOBALS['es_optimizer_test']['flushes'] );
	}

	public function test_network_passes_over_a_site_that_cannot_be_switched_to(): void {
		$this->network( array( 1, 2, 3 ) );
		$GLOBALS['es_optimizer_test']['fail_switch'] = array( 2 );

		$message = $this->failure( 'es_optimizer_uninstall_network_options' );

		$this->assertSame( 'Settings could not be removed from 1 site. Site ID: 2. WordPress could not switch to site 2.', $message );
		$this->assertSame( 3, $GLOBALS['es_optimizer_test']['flushes'] );
		$this->assertSame( 1, get_current_blog_id() );
	}

	public function test_network_lists_at_most_twenty_failed_sites(): void {
		$this->network( range( 1, 25 ), array_fill_keys( range( 1, 25 ), 1 ) );

		$message = $this->failure( 'es_optimizer_uninstall_network_options' );

		$this->assertStringStartsWith(
			'Settings could not be removed from 25 sites. Site IDs, at most the first 20: ' . implode( ', ', range( 1, 20 ) ) . '.',
			$message
		);
		$this->assertStringEndsWith( 'The settings of site 1 are still stored after deletion.', $message );
	}

	public function test_network_fails_when_a_site_is_created_during_removal(): void {
		$this->network( array( 1, 2 ), array(), array( 2, 3 ) );

		$this->assertSame( 'A site was created during removal.', $this->failure( 'es_optimizer_uninstall_network_options' ) );
	}

	public function test_site_limit_is_zero_for_an_empty_directory_and_fails_on_a_bad_read(): void {
		self::database( static fn (): int => 0 );
		$this->assertSame( 0, es_optimizer_uninstall_get_site_limit() );

		self::database( static fn (): int => 2 );
		$this->assertSame( 'The list of network sites could not be read.', $this->failure( 'es_optimizer_uninstall_get_site_limit' ) );
	}

	public function test_site_ids_reject_bad_reads(): void {
		$read = 'The list of network sites could not be read.';
		$bad  = 'The list of network sites returned an invalid site ID.';

		self::database( static fn (): bool => false );
		$this->assertSame( $read, $this->failure( static fn () => es_optimizer_uninstall_get_site_ids( 0, 5 ) ) );

		self::database( static fn (): int => 101 );
		$this->assertSame( $read, $this->failure( static fn () => es_optimizer_uninstall_get_site_ids( 0, 500 ) ) );

		$database      = self::database( static fn (): int => 2 );
		$database->col = array( 3 );
		$this->assertSame( $read, $this->failure( static fn () => es_optimizer_uninstall_get_site_ids( 0, 5 ) ) );

		foreach ( array( array( 2, 2 ), array( 3, 2 ), array( 6 ), array( 'two' ), array( null ) ) as $column ) {
			$database      = self::database( static fn (): int => count( $column ) );
			$database->col = $column;
			$this->assertSame( $bad, $this->failure( static fn () => es_optimizer_uninstall_get_site_ids( 0, 5 ) ) );
		}
	}

	public function test_site_ids_accept_numeric_strings_in_ascending_order(): void {
		$database      = self::database( static fn (): int => 2 );
		$database->col = array( '2', '4' );

		$this->assertSame( array( 2, 4 ), es_optimizer_uninstall_get_site_ids( 1, 5 ) );
	}

	public function test_switch_state_of_an_unexpected_type_is_refused(): void {
		$GLOBALS['_wp_switched_stack'] = 'not a stack';

		$this->assertSame( 'The site-switch state could not be read.', $this->failure( 'es_optimizer_uninstall_get_switch_state' ) );
	}

	public function test_context_restoration_fails_when_switching_back_fails(): void {
		switch_to_blog( 5 );
		$GLOBALS['es_optimizer_test']['fail_restore'] = true;
		$context = array(
			'site_id'  => 1,
			'stack'    => array(),
			'switched' => false,
		);

		$this->assertSame( 'WordPress could not switch back to the original site.', $this->failure( static fn () => es_optimizer_uninstall_restore_context( $context ) ) );
	}

	public function test_context_restoration_fails_when_the_stack_does_not_shrink(): void {
		switch_to_blog( 5 );
		$GLOBALS['es_optimizer_test']['restore_noop'] = true;
		$context = array(
			'site_id'  => 1,
			'stack'    => array(),
			'switched' => false,
		);

		$this->assertSame( 'WordPress could not switch back to the original site.', $this->failure( static fn () => es_optimizer_uninstall_restore_context( $context ) ) );
	}

	public function test_context_restoration_fails_when_the_site_differs_at_the_same_depth(): void {
		$GLOBALS['es_optimizer_test']['blog_id'] = 4;
		$context                                  = array(
			'site_id'  => 1,
			'stack'    => array(),
			'switched' => false,
		);

		$this->assertSame( 'WordPress could not switch back to the original site.', $this->failure( static fn () => es_optimizer_uninstall_restore_context( $context ) ) );
	}

	public function test_a_failure_that_leaves_another_site_in_place_stops_the_uninstall(): void {
		$this->network( array( 1, 2 ) );
		$GLOBALS['es_optimizer_test']['fail_switch']  = array( 2 );
		$GLOBALS['es_optimizer_test']['fail_restore'] = true;

		$this->assertSame( 'WordPress could not switch back to the original site.', $this->failure( static fn () => es_optimizer_uninstall_try_site_options( 2 ) ) );
	}

	public function test_failure_message_names_the_reason_only_for_its_own_failures(): void {
		$generic = 'Site Optimizer settings could not be fully removed. The plugin files were retained. Pause site and settings changes, then retry removal.';

		$this->assertSame( $generic, es_optimizer_uninstall_get_failure_message( new RuntimeException( 'Database password in an error' ) ) );
		$this->assertSame( $generic, es_optimizer_uninstall_get_failure_message( new LogicException( 'Other', ES_SITE_OPTIMIZER_UNINSTALL_ERROR ) ) );
		$this->assertSame(
			$generic . ' Reason: A site was created during removal.',
			es_optimizer_uninstall_get_failure_message( new RuntimeException( 'A site was created during removal.', ES_SITE_OPTIMIZER_UNINSTALL_ERROR ) )
		);
	}
}
