<?php
/**
 * Tests for the autoloaded-options code: what may be switched off, the log, the
 * changes in both directions, the screen, and the warning before uninstall.
 *
 * The SQL itself, core's cache handling, and uninstall on a real database are
 * covered by the generated WordPress suite.
 *
 * @package EngineScript_Site_Optimizer
 */

/**
 * Exercises includes/autoload.php and includes/autoload-page.php with the doubles from the bootstrap.
 */
final class AutoloadTest extends EsOptimizerTestCase {

	/**
	 * Remove the database double and request data after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'], $_SERVER['REQUEST_METHOD'] );
		$_POST = array();
		$_GET  = array();
		parent::tearDown();
	}

	/**
	 * Build a database double whose reads return the given rows and whose updates answer with $updated.
	 *
	 * @param array<int, array<string, mixed>>|null $rows    Rows for get_results(), or null for a failed read.
	 * @param int|false                             $updated Row count for UPDATE queries.
	 * @return wpdb The double, also set as the global.
	 */
	private function database( ?array $rows = array(), int|false $updated = 1 ): wpdb {
		$database            = new wpdb();
		$database->results   = $rows;
		$database->responder = static fn ( string $sql ): int|false => str_starts_with( $sql, 'UPDATE' ) ? $updated : 0;
		$GLOBALS['wpdb']     = $database;

		return $database;
	}

	/**
	 * A stored row as the flag read returns it.
	 *
	 * @param string $name     Option name.
	 * @param string $autoload Stored flag.
	 * @param int    $bytes    Stored size.
	 * @return array<string, mixed> Row.
	 */
	private function row( string $name, string $autoload, int $bytes = 10 ): array {
		return array(
			'option_name'  => $name,
			'autoload'     => $autoload,
			'option_bytes' => $bytes,
		);
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
	 * Core options, widget and theme options, the roles row, the two names WordPress refuses, and the plugin's own are locked.
	 *
	 * @return void
	 */
	public function test_locked_options(): void {
		$this->database();

		foreach ( array( 'siteurl', 'home', 'active_plugins', 'cron', 'rewrite_rules', 'sidebars_widgets', 'widget_text', 'theme_mods_twentytwentyfive', 'wp_user_roles', 'alloptions', 'notoptions', 'es_optimizer_options', 'es_optimizer_autoload_changes', 'WPLANG' ) as $name ) {
			$this->assertTrue( es_optimizer_autoload_is_locked( $name ), $name );
		}

		foreach ( array( 'woocommerce_version', 'elementor_log', 'other_user_roles', 'widgets', 'Siteurl' ) as $name ) {
			$this->assertFalse( es_optimizer_autoload_is_locked( $name ), $name );
		}

		$GLOBALS['wpdb']->prefix = 'site2_';
		$this->assertTrue( es_optimizer_autoload_is_locked( 'site2_user_roles' ) );
		$this->assertFalse( es_optimizer_autoload_is_locked( 'wp_user_roles' ), 'Only the current table prefix holds the roles.' );
	}

	/**
	 * Submitted names are kept only when they are strings WordPress can store, once each, and at most 100.
	 *
	 * @return void
	 */
	public function test_names_are_normalized_and_bounded(): void {
		$this->assertSame( array( 'names' => array(), 'invalid' => 0 ), es_optimizer_autoload_normalize_names( 'one' ) );

		$normalized = es_optimizer_autoload_normalize_names( array( 'a', 'a', '', 7, array( 'b' ), str_repeat( 'x', 192 ), str_repeat( 'y', 191 ), 'quote\'"<name>' ) );
		$this->assertSame( array( 'a', str_repeat( 'y', 191 ), 'quote\'"<name>' ), $normalized['names'] );
		$this->assertSame( 4, $normalized['invalid'], 'An empty string, a number, an array, and a 192-byte name.' );

		$many = es_optimizer_autoload_normalize_names( array_map( static fn ( int $i ): string => "opt_{$i}", range( 1, 105 ) ) );
		$this->assertCount( 100, $many['names'] );
		$this->assertSame( 5, $many['invalid'], 'Names over the limit are counted, not silently dropped.' );
	}

	/**
	 * A log entry that is not well formed is dropped; a stored value that is not an array reads as empty.
	 *
	 * @return void
	 */
	public function test_log_is_read_defensively(): void {
		$this->store_log( array( 'not a log' ) );
		$this->assertSame( array(), es_optimizer_autoload_get_log() );

		$this->store_log(
			array(
				'good'       => array( 'previous' => 'auto', 'time' => 5 ),
				'no_time'    => array( 'previous' => 'yes' ),
				'was_off'    => array( 'previous' => 'off', 'time' => 1 ),
				'bad_flag'   => array( 'previous' => "on'; DROP", 'time' => 1 ),
				'not_loaded' => array( 'previous' => 'auto-off', 'time' => 1 ),
				'legacy_no'  => array( 'previous' => 'no', 'time' => 1 ),
				'not_array'  => 'auto',
				''           => array( 'previous' => 'on', 'time' => 1 ),
				'no_flag'    => array( 'time' => 1 ),
			)
		);

		$this->assertSame(
			array(
				'good'    => array( 'previous' => 'auto', 'time' => 5 ),
				'no_time' => array( 'previous' => 'yes', 'time' => 0 ),
			),
			es_optimizer_autoload_get_log()
		);
	}

	/**
	 * Switching off checks each stored flag, logs the earlier flag first, then asks core to change only the candidates.
	 *
	 * @return void
	 */
	public function test_switch_off_logs_first_and_skips_what_it_must(): void {
		$this->database(
			array(
				$this->row( 'big_plugin_cache', 'auto' ),
				$this->row( 'legacy_yes', 'yes' ),
				$this->row( 'already_off', 'off' ),
				$this->row( 'large_auto_off', 'auto-off' ),
				$this->row( 'siteurl', 'on' ),
			)
		);
		$GLOBALS['es_optimizer_test']['on_delete'] = null;

		$result = es_optimizer_autoload_switch_off( array( 'big_plugin_cache', 'legacy_yes', 'already_off', 'large_auto_off', 'siteurl', 'gone', 42 ) );

		$this->assertSame( array( 'big_plugin_cache', 'legacy_yes' ), $result['changed'] );
		$this->assertSame( array( 'invalid' => 1, 'not_autoloaded' => 2, 'locked' => 1, 'missing' => 1 ), $result['skipped'] );
		$this->assertSame( array( array( 'big_plugin_cache' => false, 'legacy_yes' => false ) ), $GLOBALS['es_optimizer_test']['autoload_set'] );

		$log = es_optimizer_autoload_get_log();
		$this->assertSame( array( 'big_plugin_cache', 'legacy_yes' ), array_keys( $log ) );
		$this->assertSame( 'auto', $log['big_plugin_cache']['previous'] );
		$this->assertSame( 'yes', $log['legacy_yes']['previous'] );
		$this->assertGreaterThan( 0, $log['big_plugin_cache']['time'] );
		$this->assertFalse( $GLOBALS['es_optimizer_test']['option_autoload'][ ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION ], 'The log itself is never autoloaded.' );
	}

	/**
	 * An option core could not change is taken out of the log again; one logged earlier keeps its first flag.
	 *
	 * @return void
	 */
	public function test_switch_off_failure_and_an_earlier_entry(): void {
		$this->database( array( $this->row( 'fails', 'on' ), $this->row( 'again', 'on' ) ) );
		$this->store_log( array( 'again' => array( 'previous' => 'auto', 'time' => 7 ) ) );
		$GLOBALS['es_optimizer_test']['autoload_results'] = array( 'fails' => false );

		$result = es_optimizer_autoload_switch_off( array( 'fails', 'again' ) );

		$this->assertSame( array( 'again' ), $result['changed'] );
		$this->assertSame( array( 'failed' => 1 ), $result['skipped'] );
		$this->assertSame(
			array( 'again' => array( 'previous' => 'auto', 'time' => 7 ) ),
			es_optimizer_autoload_get_log(),
			'Switched back on by other code and off again: the first flag stays, and the failed option is not logged.'
		);
	}

	/**
	 * Nothing changes when the stored flags cannot be read or the log cannot be saved.
	 *
	 * @return void
	 */
	public function test_switch_off_changes_nothing_without_a_read_or_a_log(): void {
		$this->database( null );
		$this->assertSame( array( 'changed' => array(), 'skipped' => array( 'failed' => 1 ) ), es_optimizer_autoload_switch_off( array( 'any' ) ) );

		$this->database( array( $this->row( 'any', 'on' ) ) );
		$GLOBALS['es_optimizer_test']['fail_update'] = array( ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION );
		$this->assertSame( array( 'changed' => array(), 'skipped' => array( 'failed' => 1 ) ), es_optimizer_autoload_switch_off( array( 'any' ) ) );
		$this->assertArrayNotHasKey( 'autoload_set', $GLOBALS['es_optimizer_test'], 'No flag changes without a log to undo it.' );
	}

	/**
	 * Switching on restores the exact earlier flag while "off" is still stored, and clears the caches as core does.
	 *
	 * @return void
	 */
	public function test_switch_on_restores_the_exact_flag(): void {
		$database = $this->database( array(), 1 );
		$this->store_log( array( 'cache_a' => array( 'previous' => 'auto', 'time' => 1 ) ) );

		$result = es_optimizer_autoload_switch_on( array( 'cache_a', 'never_logged' ) );

		$this->assertSame( array( 'cache_a' ), $result['changed'] );
		$this->assertSame( array( 'not_logged' => 1 ), $result['skipped'] );
		$this->assertSame( array( "UPDATE wp_options SET autoload = auto WHERE option_name = cache_a AND autoload = 'off'" ), $database->queries );
		$this->assertSame( array( 'options:cache_a', 'options:alloptions' ), $GLOBALS['es_optimizer_test']['cache_deleted'] );
		$this->assertSame( array(), es_optimizer_autoload_get_log() );
		$this->assertFalse( get_option( ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION ), 'An empty log is deleted.' );
	}

	/**
	 * An option something else already changed is only taken off the log; a failed update keeps it there.
	 *
	 * @return void
	 */
	public function test_switch_on_changed_elsewhere_and_failure(): void {
		$this->database( array(), 0 );
		$this->store_log( array( 'moved' => array( 'previous' => 'on', 'time' => 1 ) ) );
		$this->assertSame( array( 'changed' => array(), 'skipped' => array( 'changed_elsewhere' => 1 ) ), es_optimizer_autoload_switch_on( array( 'moved' ) ) );
		$this->assertSame( array(), es_optimizer_autoload_get_log() );
		$this->assertArrayNotHasKey( 'cache_deleted', $GLOBALS['es_optimizer_test'], 'No change, no cache to clear.' );

		$this->database( array(), false );
		$this->store_log( array( 'stuck' => array( 'previous' => 'on', 'time' => 1 ) ) );
		$this->assertSame( array( 'changed' => array(), 'skipped' => array( 'failed' => 1 ) ), es_optimizer_autoload_switch_on( array( 'stuck' ) ) );
		$this->assertArrayHasKey( 'stuck', es_optimizer_autoload_get_log() );
	}

	/**
	 * Restoring everything deletes the log only when every option was restored or is no longer switched off.
	 *
	 * @return void
	 */
	public function test_restore_all(): void {
		$this->database( array(), 1 );
		$this->store_log( array( 'one' => array( 'previous' => 'on', 'time' => 1 ), 'two' => array( 'previous' => 'auto-on', 'time' => 2 ) ) );
		$this->assertSame( array(), es_optimizer_autoload_restore_all() );
		$this->assertFalse( get_option( ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION ) );

		$this->database( array(), false );
		$this->store_log( array( 'one' => array( 'previous' => 'on', 'time' => 1 ) ) );
		$this->assertSame( array( 'one' ), es_optimizer_autoload_restore_all() );
		$this->assertArrayHasKey( 'one', es_optimizer_autoload_get_log(), 'Kept for another attempt.' );
	}

	/**
	 * On a network, the site is listed while it has logged changes, and the list goes when it is empty.
	 *
	 * @return void
	 */
	public function test_network_list_of_sites_with_changes(): void {
		es_optimizer_autoload_save_log( array( 'a' => array( 'previous' => 'on', 'time' => 1 ) ) );
		$this->assertArrayNotHasKey( 'site_options', $GLOBALS['es_optimizer_test'], 'A single site keeps no network list.' );

		$GLOBALS['es_optimizer_test']['multisite'] = true;
		$GLOBALS['es_optimizer_test']['blog_id']   = 3;
		es_optimizer_autoload_save_log( array( 'a' => array( 'previous' => 'on', 'time' => 1 ) ) );
		$this->assertSame( array( 3 ), get_site_option( ES_SITE_OPTIMIZER_AUTOLOAD_SITES_OPTION ) );

		$GLOBALS['es_optimizer_test']['blog_id'] = 5;
		es_optimizer_autoload_save_log( array( 'b' => array( 'previous' => 'on', 'time' => 1 ) ) );
		$this->assertSame( array( 3, 5 ), get_site_option( ES_SITE_OPTIMIZER_AUTOLOAD_SITES_OPTION ) );

		es_optimizer_autoload_save_log( array() );
		$GLOBALS['es_optimizer_test']['blog_id'] = 3;
		es_optimizer_autoload_save_log( array() );
		$this->assertFalse( get_site_option( ES_SITE_OPTIMIZER_AUTOLOAD_SITES_OPTION ), 'Deleted once no site has changes.' );
	}

	/**
	 * The source is the plugin itself, core, a transient, the code that read it, the code that contains it, or the longest matching folder name or text domain.
	 *
	 * @return void
	 */
	public function test_source_guess(): void {
		$this->database();
		$sources = array(
			es_optimizer_autoload_plugin_source( 'woocommerce/woocommerce.php', array( 'Name' => 'WooCommerce', 'TextDomain' => 'woocommerce' ), true, WP_PLUGIN_DIR ),
			es_optimizer_autoload_plugin_source( 'woocommerce-payments/woocommerce-payments.php', array( 'Name' => 'WooPayments' ), false, WP_PLUGIN_DIR ),
			es_optimizer_autoload_plugin_source( 'wordpress-seo/wp-seo.php', array( 'Name' => 'Yoast SEO', 'TextDomain' => 'wordpress-seo' ), true, WP_PLUGIN_DIR ),
			es_optimizer_autoload_plugin_source( 'wp-mail-smtp/wp_mail_smtp.php', array( 'Name' => 'WP Mail SMTP', 'TextDomain' => 'wp-mail-smtp' ), true, WP_PLUGIN_DIR ),
			es_optimizer_autoload_plugin_source( 'ab/ab.php', array( 'Name' => 'Too short', 'TextDomain' => 'ab' ), true, WP_PLUGIN_DIR ),
			es_optimizer_autoload_plugin_source( 'hello.php', array( 'Name' => 'Hello Dolly', 'TextDomain' => 'hello-dolly' ), false, WP_PLUGIN_DIR ),
		);
		$GLOBALS['es_optimizer_test']['themes'] = array( 'astra' => new Es_Optimizer_Test_Theme( array( 'Name' => 'Astra', 'TextDomain' => 'astra' ), '/srv/wp/wp-content/themes/astra' ) );
		$GLOBALS['es_optimizer_test']['stylesheet'] = 'astra';
		$sources = array_merge( $sources, array_values( array_filter( es_optimizer_autoload_get_sources(), static fn ( array $s ): bool => 'theme' === $s['type'] ) ) );

		$this->assertSame( 'this', es_optimizer_autoload_guess_source( 'es_optimizer_autoload_usage', $sources )['type'] );
		$this->assertSame( 'core', es_optimizer_autoload_guess_source( 'rewrite_rules', $sources )['type'] );
		$this->assertSame( 'transient', es_optimizer_autoload_guess_source( '_transient_feed_x', $sources )['type'] );
		$this->assertSame( 'transient', es_optimizer_autoload_guess_source( '_site_transient_update_core', $sources )['type'] );
		$this->assertSame( 'WooCommerce (active plugin)', es_optimizer_autoload_guess_source( 'woocommerce_version', $sources )['label'] );
		$this->assertSame( 'WooPayments (inactive plugin)', es_optimizer_autoload_guess_source( 'woocommerce_payments_account', $sources )['label'], 'The longest key wins.' );
		$this->assertSame( 'WP Mail SMTP (active plugin)', es_optimizer_autoload_guess_source( '_WP_Mail_SMTP_settings', $sources )['label'], 'Case and leading underscores are ignored.' );
		$this->assertSame( 'Astra (active theme)', es_optimizer_autoload_guess_source( 'astra-settings', $sources )['label'], 'Themes count too.' );
		$this->assertSame( 'Hello Dolly (inactive plugin)', es_optimizer_autoload_guess_source( 'hello_dolly_lyrics', $sources )['label'], 'The text domain counts, with underscores.' );
		$this->assertSame( 'unknown', es_optimizer_autoload_guess_source( 'ab_settings', $sources )['type'], 'Keys under three characters match too much.' );
		$this->assertSame( 'unknown', es_optimizer_autoload_guess_source( 'woocommercex', $sources )['type'], 'A key must end at a separator.' );
		$this->assertSame( 'unknown', es_optimizer_autoload_guess_source( 'wpseo_titles', $sources )['type'], 'A prefix no folder or text domain shows needs the code search.' );

		$code = array( 'found' => array( 'plugin:wordpress-seo' ), 'searched' => true );
		$this->assertSame( 'Yoast SEO (active plugin)', es_optimizer_autoload_guess_source( 'wpseo_titles', $sources, $code )['label'], 'The code search names the owner.' );
		$this->assertSame( 'orphan', es_optimizer_autoload_guess_source( 'gone_plugin_cache', $sources, array( 'found' => array(), 'searched' => true ) )['type'] );
		$this->assertSame( 'unknown', es_optimizer_autoload_guess_source( 'gone_plugin_cache', $sources, array( 'found' => array(), 'searched' => false ) )['type'], 'A search that ran out of time proves nothing.' );
		$this->assertSame( 'Astra (active theme)', es_optimizer_autoload_guess_source( 'woocommerce_version', $sources, $code, 'theme:astra' )['label'], 'The recorded reader comes first.' );
		$this->assertSame( '/srv/wp/wp-content/plugins/hello.php', $sources[5]['path'], 'A single-file plugin is its file.' );
	}

	/**
	 * Each logged option shows whether it is still off, was loaded again or changed by other code, or was deleted.
	 *
	 * @return void
	 */
	public function test_logged_rows_report_their_state(): void {
		$this->database( array( $this->row( 'still_off', 'off', 40 ), $this->row( 'back_on', 'on' ), $this->row( 'other', 'auto-off' ) ) );
		$this->store_log(
			array(
				'still_off' => array( 'previous' => 'auto', 'time' => 30 ),
				'back_on'   => array( 'previous' => 'on', 'time' => 10 ),
				'other'     => array( 'previous' => 'on', 'time' => 20 ),
				'removed'   => array( 'previous' => 'on', 'time' => 10 ),
			)
		);

		$rows = es_optimizer_autoload_get_logged_rows();

		$this->assertSame( array( 'back_on', 'removed', 'other', 'still_off' ), array_column( $rows, 'name' ), 'Oldest first, then by name.' );
		$this->assertSame( array( 'reenabled', 'deleted', 'changed', 'off' ), array_column( $rows, 'state' ) );
		$this->assertSame( 40, $rows[3]['bytes'] );

		$this->database( null );
		$this->assertNull( es_optimizer_autoload_get_logged_rows() );
	}

	/**
	 * A request is refused without the capability or a valid nonce, and an unknown action stops.
	 *
	 * @return void
	 */
	public function test_request_checks(): void {
		$this->assertNull( es_optimizer_autoload_process_request(), 'Not a POST request.' );

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertNull( es_optimizer_autoload_process_request(), 'A POST without the action is not ours.' );

		$_POST = array( 'es_optimizer_autoload_action' => 'switch_off', 'option_names' => array( 'x' ) );
		$GLOBALS['es_optimizer_test']['capabilities'] = array( 'read' );
		$this->assertStops( 'You do not have sufficient permissions to change autoloaded options.' );

		$GLOBALS['es_optimizer_test']['capabilities'] = array( 'manage_options' );
		$GLOBALS['es_optimizer_test']['nonce_ok']     = false;
		$this->assertStops( 'The link you followed has expired.' );
		$this->assertArrayNotHasKey( 'autoload_set', $GLOBALS['es_optimizer_test'] );

		$GLOBALS['es_optimizer_test']['nonce_ok'] = true;
		$_POST['es_optimizer_autoload_action']    = 'drop_table';
		$this->assertStops( 'Unknown action.' );
		$this->assertSame( array( 'es_optimizer_autoload', 'es_optimizer_autoload' ), $GLOBALS['es_optimizer_test']['nonce_checked'], 'The capability is checked before the nonce.' );
	}

	/**
	 * A valid request applies the change and redirects with its counts.
	 *
	 * @return void
	 */
	public function test_request_redirects_with_the_result(): void {
		$this->database( array( $this->row( 'plugin_cache', 'on' ), $this->row( 'siteurl', 'on' ) ) );
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = array(
			'es_optimizer_autoload_action' => 'switch_off',
			'option_names'                 => array( 'plugin_cache', 'siteurl' ),
		);

		$this->assertSame(
			'https://site.example/wp-admin/options-general.php?page=es-optimizer-settings&tab=autoload&view=loaded&es_changed=1&es_check=ok&es_undone=0&es_skipped_locked=1',
			es_optimizer_autoload_process_request()
		);

		$_POST = array(
			'es_optimizer_autoload_action' => 'switch_on',
			'option_names'                 => array( 'plugin_cache' ),
		);
		$this->assertSame( 'https://site.example/wp-admin/options-general.php?page=es-optimizer-settings&tab=autoload&view=changed&es_changed=1', es_optimizer_autoload_process_request() );
	}

	/**
	 * Every reason has a message, and the result notice prints them escaped.
	 *
	 * @return void
	 */
	public function test_result_messages(): void {
		foreach ( array( 'locked', 'missing', 'not_autoloaded', 'failed', 'invalid', 'not_logged', 'changed_elsewhere', 'log_failed' ) as $reason ) {
			$this->assertNotSame( '', es_optimizer_autoload_get_skip_message( $reason, 2 ), $reason );
		}
		$this->assertSame( '', es_optimizer_autoload_get_skip_message( 'unknown', 1 ) );
		$this->assertSame( '1 option no longer exists.', es_optimizer_autoload_get_skip_message( 'missing', 1 ) );

		$this->assertSame( '', $this->capture( 'es_optimizer_autoload_render_result' ) );

		$_GET   = array( 'es_changed' => '2', 'es_skipped_locked' => '1' );
		$output = $this->capture( 'es_optimizer_autoload_render_result' );
		$this->assertStringContainsString( '<div class="notice notice-warning"><p>2 options are no longer loaded on every page.</p><p>1 locked option', $output );

		$_GET = array( 'es_changed' => '0' );
		$this->assertStringContainsString( 'Nothing was selected, so nothing was changed.', $this->capture( 'es_optimizer_autoload_render_result' ) );

		$GLOBALS['es_optimizer_test']['translations'] = array( '%d option no longer exists.' => '<b>%d</b>' );
		$_GET = array( 'es_changed' => '0', 'es_skipped_missing' => '1' );
		$this->assertStringContainsString( '&lt;b&gt;1&lt;/b&gt;', $this->capture( 'es_optimizer_autoload_render_result' ) );
	}

	/**
	 * The list prints names as text, locks core rows without a checkbox, and labels every checkbox.
	 *
	 * @return void
	 */
	public function test_loaded_list_markup(): void {
		$database          = $this->database();
		$database->var     = 2;
		$database->row     = array( 'option_count' => 2, 'option_bytes' => 1500 );
		$database->results = array(
			array( 'option_name' => '<script>alert(1)</script>', 'option_bytes' => 900, 'autoload' => 'on' ),
			array( 'option_name' => 'rewrite_rules', 'option_bytes' => 600, 'autoload' => 'on' ),
		);
		$_GET = array( 'page' => 'es-optimizer-settings', 'tab' => 'autoload' );

		$page = $this->capture( 'es_optimizer_settings_page' );

		$this->assertStringNotContainsString( '<script>alert(1)', $page );
		$this->assertStringContainsString( 'value="&lt;script&gt;alert(1)&lt;/script&gt;"', $page );
		$this->assertSame( 1, substr_count( $page, 'name="option_names[]"' ), 'The locked row has no checkbox.' );
		$this->assertStringContainsString( '<code>rewrite_rules</code> <span class="description">(locked)</span>', $page );
		$this->assertStringContainsString( '2 options are loaded on every page, 1500 B in total.', $page );
		$this->assertStringContainsString( 'name="es_optimizer_autoload_action" value="switch_off"', $page );
		$this->assertStringContainsString( 'name="_wpnonce" value="nonce-es_optimizer_autoload"', $page );
		$this->assertStringContainsString( '<label for="es-optimizer-select-all">', $page );
		$this->assertStringContainsString( '<label for="es-optimizer-option-0"><span class="screen-reader-text">', $page );
		$this->assertStringContainsString( '<p><strong>What it does:</strong> ', $page );
		$this->assertStringNotContainsString( 'When you delete Site Optimizer', $page, 'No uninstall notice without changes.' );

		$this->store_log( array( 'x' => array( 'previous' => 'on', 'time' => 1 ) ) );
		$this->assertStringContainsString( 'When you delete Site Optimizer, it first loads every option it switched off', $this->capture( 'es_optimizer_settings_page' ) );
	}

	/**
	 * The settings page shows the Autoloaded Options tab instead of the settings form, and still refuses other users.
	 *
	 * @return void
	 */
	public function test_tab_on_the_settings_page(): void {
		$this->database();
		$_GET = array( 'page' => 'es-optimizer-settings', 'tab' => 'autoload' );
		$page = $this->capture( 'es_optimizer_settings_page' );

		$this->assertStringContainsString( 'class="nav-tab nav-tab-active" aria-current="page">Autoloaded Options</a>', $page );
		$this->assertStringNotContainsString( 'action="options.php"', $page, 'The settings form is on the other tab.' );
		$this->assertStringContainsString( '<input type="hidden" name="tab" value="autoload" />', $page, 'The search stays on this tab.' );
		$this->assertStringContainsString( 'class="current" aria-current="page">Loaded on every page</a>', $page );
		$this->assertStringContainsString( 'view=changed">Switched off by Site Optimizer (0)</a>', $page );

		$_GET = array( 'page' => 'es-optimizer-settings', 'tab' => '<script>' );
		$this->assertStringContainsString( 'action="options.php"', $this->capture( 'es_optimizer_settings_page' ), 'Any other tab value shows the settings.' );

		$GLOBALS['es_optimizer_test']['capabilities'] = array( 'read' );
		$_GET = array( 'page' => 'es-optimizer-settings', 'tab' => 'autoload' );

		try {
			$this->capture( 'es_optimizer_settings_page' );
			$this->fail( 'The tab rendered for a user without manage_options.' );
		} catch ( Es_Optimizer_Test_Die $stop ) {
			$this->assertSame( 'You do not have sufficient permissions to access this page.', $stop->getMessage() );
		}
	}

	/**
	 * The settings page sends the tab's form posts to the request handler.
	 *
	 * @return void
	 */
	public function test_settings_page_handles_the_tab_requests(): void {
		es_optimizer_add_settings_page();

		$this->assertSame( 10, es_optimizer_test_hook_priority( 'load-settings_page_es-optimizer-settings', 'es_optimizer_autoload_handle_request' ) );
	}

	/**
	 * The Plugins screen warns only while changes exist: the site's log, or on the network screen the list of sites.
	 *
	 * @return void
	 */
	public function test_plugin_row_warning(): void {
		$this->assertSame( '', $this->capture( 'es_optimizer_autoload_plugin_row_notice' ) );

		$this->store_log( array( 'x' => array( 'previous' => 'on', 'time' => 1 ) ) );
		$this->assertStringContainsString( 'Deleting the plugin loads them on every page again', $this->capture( 'es_optimizer_autoload_plugin_row_notice' ) );

		$GLOBALS['es_optimizer_test']['capabilities'] = array( 'read' );
		$this->assertSame( '', $this->capture( 'es_optimizer_autoload_plugin_row_notice' ) );

		$GLOBALS['es_optimizer_test']['capabilities']  = array( 'manage_options' );
		$GLOBALS['es_optimizer_test']['network_admin'] = true;
		$this->assertSame( '', $this->capture( 'es_optimizer_autoload_plugin_row_notice' ), 'The network screen reads the network list, which is empty.' );
		$GLOBALS['es_optimizer_test']['site_options'][ ES_SITE_OPTIMIZER_AUTOLOAD_SITES_OPTION ] = array( 4 );
		$this->assertStringContainsString( 'colspan="4"', $this->capture( 'es_optimizer_autoload_plugin_row_notice' ) );
	}

	/**
	 * Assert that processing the current request stops with a message.
	 *
	 * @param string $message Expected message.
	 * @return void
	 */
	private function assertStops( string $message ): void {
		try {
			es_optimizer_autoload_process_request();
			$this->fail( 'The request was processed: ' . $message );
		} catch ( Es_Optimizer_Test_Die $stop ) {
			$this->assertSame( $message, $stop->getMessage() );
		}
	}
}
