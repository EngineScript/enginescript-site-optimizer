<?php
/**
 * Tests for the evidence and safety around autoloaded options: the check and
 * undo after a change, usage recording, and the code search.
 *
 * @package EngineScript_Site_Optimizer
 */

/**
 * Exercises includes/autoload-insight.php and the check in includes/autoload-page.php.
 */
final class AutoloadInsightTest extends EsOptimizerTestCase {

	/**
	 * Temporary folder for the code search.
	 *
	 * @var string
	 */
	private $folder = '';

	/**
	 * Remove the database double, request data, and the temporary folder.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'], $_SERVER['REQUEST_METHOD'], $_COOKIE['wordpress_logged_in_test'] );
		$_POST = array();
		$_GET  = array();

		if ( '' !== $this->folder ) {
			$this->remove( $this->folder );
		}

		delete_option( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION );
		delete_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION );
		parent::tearDown();
	}

	/**
	 * Build a database double whose flag reads return the rows and whose updates succeed.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows for get_results().
	 * @return void
	 */
	private function database( array $rows ): void {
		$database            = new wpdb();
		$database->results   = $rows;
		$database->responder = static fn ( string $sql ): int => str_starts_with( $sql, 'UPDATE' ) ? 1 : 0;
		$GLOBALS['wpdb']     = $database;
	}

	/**
	 * Post a switch-off of one option and return the redirect.
	 *
	 * @return string|null Redirect address.
	 */
	private function post_switch_off(): ?string {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = array(
			'es_optimizer_autoload_action' => 'switch_off',
			'option_names'                 => array( 'plugin_cache' ),
		);

		return es_optimizer_autoload_process_request();
	}

	/**
	 * After a switch-off the home page and the dashboard are loaded; a server error undoes the change.
	 *
	 * @return void
	 */
	public function test_server_error_after_a_change_undoes_it(): void {
		$this->database( array( array( 'option_name' => 'plugin_cache', 'autoload' => 'on', 'option_bytes' => 9 ) ) );
		$GLOBALS['es_optimizer_test']['http'] = static fn ( string $url ): int => str_contains( $url, 'wp-admin' ) ? 500 : 200;

		$url = $this->post_switch_off();

		$this->assertStringContainsString( 'es_changed=1&es_check=failed&es_undone=1', (string) $url );
		$this->assertSame( array(), es_optimizer_autoload_get_log(), 'The undo restored the option and emptied the log.' );
		$this->assertStringContainsString( "UPDATE wp_options SET autoload = on WHERE option_name = plugin_cache AND autoload = 'off'", implode( "\n", $GLOBALS['wpdb']->queries ) );
		$requests = $GLOBALS['es_optimizer_test']['requests'];
		$this->assertStringStartsWith( 'https://site.example/?es-optimizer-check=', $requests[0][0], 'The home page, past any page cache.' );
		$this->assertArrayNotHasKey( 'cookies', $requests[0][1], 'The home page loads as a visitor.' );
		$this->assertSame( 'https://site.example/wp-admin/', $requests[1][0] );
		$this->assertArrayHasKey( 'cookies', $requests[1][1], 'The dashboard loads as the administrator.' );
	}

	/**
	 * A check that cannot reach the site undoes nothing and says so; a passing check says the pages load.
	 *
	 * @return void
	 */
	public function test_unreachable_or_passing_checks(): void {
		$this->database( array( array( 'option_name' => 'plugin_cache', 'autoload' => 'on', 'option_bytes' => 9 ) ) );
		$GLOBALS['es_optimizer_test']['http'] = static fn (): WP_Error => new WP_Error( 'http_request_failed', 'cURL error 28' );
		$this->assertStringContainsString( 'es_check=unchecked&es_undone=0', (string) $this->post_switch_off() );
		$this->assertArrayHasKey( 'plugin_cache', es_optimizer_autoload_get_log(), 'Nothing is undone without proof of a failure.' );

		$this->assertSame( 'ok', ( static function (): string {
			$GLOBALS['es_optimizer_test']['http'] = null;
			return es_optimizer_autoload_check_site();
		} )() );

		$lines = es_optimizer_autoload_get_result_lines( array( 'view' => 'loaded', 'changed' => 1, 'skipped' => array(), 'check' => 'failed', 'undone' => 1, 'notice' => '' ) );
		$this->assertSame( 'error', $lines['type'] );
		$this->assertSame( array( 'After the change, the home page or the dashboard answered with a server error, so the change was undone: 1 option is loaded on every page again.' ), $lines['lines'], 'No claim that the option is switched off.' );
		$lines = es_optimizer_autoload_get_result_lines( array( 'view' => 'loaded', 'changed' => 2, 'skipped' => array(), 'check' => 'ok', 'undone' => 0, 'notice' => '' ) );
		$this->assertSame( array( '2 options are no longer loaded on every page.', 'The home page and the dashboard still load.' ), $lines['lines'] );
		$this->assertSame( 'warning', $lines['type'] );
	}

	/**
	 * Only WordPress's own login cookies are sent back to the site.
	 *
	 * @return void
	 */
	public function test_check_sends_only_login_cookies(): void {
		if ( ! defined( 'LOGGED_IN_COOKIE' ) ) {
			define( 'LOGGED_IN_COOKIE', 'wordpress_logged_in_test' );
		}
		$_COOKIE[ LOGGED_IN_COOKIE ] = "abc\n<b>";
		$_COOKIE['other_cookie']     = 'secret';

		$this->assertSame( array( LOGGED_IN_COOKIE => 'abc' ), es_optimizer_autoload_auth_cookies(), 'Tags and line breaks are removed, as from any text.' );
		unset( $_COOKIE['other_cookie'] );
	}

	/**
	 * Starting a recording stores its end time, autoloaded; stopping and clearing delete what they must.
	 *
	 * @return void
	 */
	public function test_recording_start_stop_and_clear(): void {
		update_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION, array( 'pages' => 3, 'options' => array() ), false );
		$this->assertTrue( es_optimizer_autoload_start_recording( 60 ) );
		$until = (int) get_option( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION );
		$this->assertEqualsWithDelta( time() + 3600, $until, 5 );
		$this->assertFalse( get_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION ), 'A new recording replaces the last results.' );

		es_optimizer_autoload_start_recording( 7 );
		$this->assertEqualsWithDelta( time() + 600, (int) get_option( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION ), 5, 'Any other length is 10 minutes.' );

		update_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION, array( 'pages' => 3, 'options' => array() ), false );
		$this->assertTrue( es_optimizer_autoload_stop_recording() );
		$this->assertSame( 3, es_optimizer_autoload_get_usage()['pages'], 'Stopping keeps the results.' );
		$this->assertTrue( es_optimizer_autoload_clear_usage() );
		$this->assertSame( 0, es_optimizer_autoload_get_usage()['pages'] );
	}

	/**
	 * A recording hooks in only while it runs, only on page loads, and ends itself when its time is up.
	 *
	 * @return void
	 */
	public function test_recording_starts_only_while_it_runs(): void {
		es_optimizer_autoload_init_recording();
		$this->assertFalse( es_optimizer_test_hook_priority( 'pre_option', 'es_optimizer_autoload_record_read' ), 'Nothing runs, and no query is made, without a recording.' );

		$GLOBALS['es_optimizer_test']['alloptions'] = array( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION => (string) ( time() + 60 ) );
		$GLOBALS['es_optimizer_test']['ajax']       = true;
		es_optimizer_autoload_init_recording();
		$this->assertFalse( es_optimizer_test_hook_priority( 'pre_option', 'es_optimizer_autoload_record_read' ), 'AJAX requests are not pages.' );

		$GLOBALS['es_optimizer_test']['ajax'] = false;
		es_optimizer_autoload_init_recording();
		$this->assertSame( 10, es_optimizer_test_hook_priority( 'pre_option', 'es_optimizer_autoload_record_read' ) );
		$this->assertSame( 10, es_optimizer_test_hook_priority( 'shutdown', 'es_optimizer_autoload_save_reads' ) );

		es_optimizer_test_reset();
		update_option( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION, (string) ( time() - 1 ) );
		$GLOBALS['es_optimizer_test']['alloptions'] = array( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION => (string) ( time() - 1 ) );
		es_optimizer_autoload_init_recording();
		$this->assertFalse( get_option( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION ), 'An expired recording ends.' );
		$this->assertFalse( es_optimizer_test_hook_priority( 'pre_option', 'es_optimizer_autoload_record_read' ) );
	}

	/**
	 * A file maps to the plugin or theme it belongs to.
	 *
	 * @return void
	 */
	public function test_reader_from_file(): void {
		$roots = array(
			'plugin' => array( '/srv/wp/wp-content/plugins/', '/srv/wp/wp-content/mu-plugins/' ),
			'theme'  => array( '/srv/wp/wp-content/themes/' ),
		);

		$this->assertSame( 'plugin:elementor', es_optimizer_autoload_reader_from_file( '/srv/wp/wp-content/plugins/elementor/core/app.php', $roots ) );
		$this->assertSame( 'plugin:hello', es_optimizer_autoload_reader_from_file( '/srv/wp/wp-content/plugins/hello.php', $roots ), 'A single-file plugin.' );
		$this->assertSame( 'plugin:loader', es_optimizer_autoload_reader_from_file( '/srv/wp/wp-content/mu-plugins/loader.php', $roots ) );
		$this->assertSame( 'theme:astra', es_optimizer_autoload_reader_from_file( '/srv/wp/wp-content/themes/astra/functions.php', $roots ) );
		$this->assertNull( es_optimizer_autoload_reader_from_file( '/srv/wp/wp-includes/option.php', $roots ) );
	}

	/**
	 * Each read option is counted once per page, with the first plugin or theme that read it.
	 *
	 * @return void
	 */
	public function test_saved_reads_count_pages_and_keep_the_reader(): void {
		update_option(
			ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION,
			array(
				'pages'   => 2,
				'options' => array(
					'seen'      => array( 'pages' => 2, 'reader' => 'core' ),
					'attributed' => array( 'pages' => 1, 'reader' => 'plugin:first' ),
				),
			),
			false
		);
		es_optimizer_autoload_record_read( false, 'seen' );
		es_optimizer_autoload_record_read( false, 'seen' );
		es_optimizer_autoload_record_read( false, 'attributed' );
		$this->assertSame( 'kept', es_optimizer_autoload_record_read( 'kept', 'new_option' ), 'The filter passes the value on.' );

		es_optimizer_autoload_save_reads();
		$usage = es_optimizer_autoload_get_usage();

		$this->assertSame( 3, $usage['pages'] );
		$this->assertSame( 3, $usage['options']['seen']['pages'], 'Two reads on one page count once.' );
		$this->assertSame( 'plugin:first', $usage['options']['attributed']['reader'], 'The first known reader stays.' );
		$this->assertSame( 1, $usage['options']['new_option']['pages'] );
		$this->assertFalse( $GLOBALS['es_optimizer_test']['option_autoload'][ ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION ], 'The results are never autoloaded.' );
	}

	/**
	 * Stored results that are malformed read as empty, and short names are not searched.
	 *
	 * @return void
	 */
	public function test_stored_evidence_is_read_defensively(): void {
		update_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION, 'not an array', false );
		$this->assertSame( array( 'pages' => 0, 'options' => array() ), es_optimizer_autoload_get_usage() );

		update_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION, array( 'pages' => '4', 'options' => array( 'good' => array( 'pages' => 2 ), 'bad' => 'x' ) ), false );
		$this->assertSame( array( 'pages' => 4, 'options' => array( 'good' => array( 'pages' => 2, 'reader' => 'core' ) ) ), es_optimizer_autoload_get_usage() );

		$this->assertNull( es_optimizer_autoload_code_entry( 'x' ) );
		$this->assertNull( es_optimizer_autoload_code_entry( array( 'found' => 'x' ) ) );
		$this->assertSame( array( 'found' => array( 'plugin:a' ), 'searched' => false ), es_optimizer_autoload_code_entry( array( 'found' => array( 'plugin:a', 3 ) ) ) );

		$this->assertSame( array( 'results' => array(), 'complete' => true ), es_optimizer_autoload_find_in_code( array( 'abc', '' ), array() ), 'Names under four characters match too much code to mean anything.' );
	}

	/**
	 * Each check result and recording action has its own message.
	 *
	 * @return void
	 */
	public function test_check_and_notice_messages(): void {
		$this->assertSame( 'The home page and the dashboard still load.', es_optimizer_autoload_get_check_message( 'ok', 0 ) );
		$this->assertStringEndsWith( 'so the change was undone: 3 options are loaded on every page again.', es_optimizer_autoload_get_check_message( 'failed', 3 ) );
		$this->assertStringStartsWith( 'The site could not be checked', es_optimizer_autoload_get_check_message( 'unchecked', 0 ) );
		$this->assertSame( '', es_optimizer_autoload_get_check_message( 'none', 0 ) );

		$this->assertStringStartsWith( 'Recording stopped.', es_optimizer_autoload_get_notice_message( 'recording_stopped' ) );
		$this->assertSame( 'The recorded results were cleared.', es_optimizer_autoload_get_notice_message( 'results_cleared' ) );
		$this->assertStringStartsWith( 'The recording could not be changed.', es_optimizer_autoload_get_notice_message( 'anything else' ) );
	}

	/**
	 * The code search finds quoted names in PHP files, skips what it must, and stops at its time limit.
	 *
	 * @return void
	 */
	public function test_code_search(): void {
		$this->folder = sys_get_temp_dir() . '/es-optimizer-search-' . getmypid();
		$this->write( 'plugins/seo/inc/settings.php', "<?php get_option( 'wpseo_titles' ); update_option( \"wpseo_social\", 1 );" );
		$this->write( 'plugins/seo/node_modules/x.php', "<?php get_option( 'hidden_in_modules' );" );
		$this->write( 'plugins/seo/readme.txt', "'wpseo_in_text'" );
		$this->write( 'themes/astra/functions.php', "<?php \$a = get_option( 'astra-settings' ); // wpseo_titles unquoted" );
		$sources = array(
			array( 'type' => 'plugin', 'slug' => 'seo', 'name' => 'SEO', 'active' => true, 'keys' => array(), 'path' => $this->folder . '/plugins/seo' ),
			array( 'type' => 'theme', 'slug' => 'astra', 'name' => 'Astra', 'active' => true, 'keys' => array(), 'path' => $this->folder . '/themes/astra' ),
		);

		$search = es_optimizer_autoload_find_in_code( array( 'wpseo_titles', 'wpseo_social', 'astra-settings', 'hidden_in_modules', 'wpseo_in_text', 'gone_option', 'ab' ), $sources );

		$this->assertTrue( $search['complete'] );
		$this->assertSame( array( 'plugin:seo' ), $search['results']['wpseo_titles']['found'], 'An unquoted mention is not a use.' );
		$this->assertSame( array( 'plugin:seo' ), $search['results']['wpseo_social']['found'] );
		$this->assertSame( array( 'theme:astra' ), $search['results']['astra-settings']['found'] );
		$this->assertSame( array( 'found' => array(), 'searched' => true ), $search['results']['hidden_in_modules'], 'node_modules is skipped.' );
		$this->assertSame( array(), $search['results']['wpseo_in_text']['found'], 'Only PHP files are read.' );
		$this->assertSame( array( 'found' => array(), 'searched' => true ), $search['results']['gone_option'] );
		$this->assertArrayNotHasKey( 'ab', $search['results'], 'Names under four characters are not searched.' );

		$timed_out = es_optimizer_autoload_find_in_code( array( 'wpseo_titles', 'gone_option' ), $sources, -1.0 );
		$this->assertFalse( $timed_out['complete'] );
		$this->assertFalse( $timed_out['results']['gone_option']['searched'], 'An unfinished search proves nothing.' );

		$this->assertTrue( es_optimizer_autoload_search_and_keep( array( 'wpseo_titles' ), $sources ) );
		$this->assertSame( array( 'plugin:seo' ), es_optimizer_autoload_get_code_results( $sources )['wpseo_titles']['found'] );
		$this->assertSame( array(), es_optimizer_autoload_get_code_results( array_slice( $sources, 0, 1 ) ), 'Kept results end when the plugins or themes change.' );
	}

	/**
	 * The recording actions post through the checked form and come back with a notice.
	 *
	 * @return void
	 */
	public function test_recording_actions_and_notices(): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = array( 'es_optimizer_autoload_action' => 'record_start', 'minutes' => '60' );

		$this->assertStringEndsWith( '&es_notice=recording_started', (string) es_optimizer_autoload_process_request() );
		$this->assertSame( array( 'es_optimizer_autoload' ), $GLOBALS['es_optimizer_test']['nonce_checked'] );

		$_POST = array( 'es_optimizer_autoload_action' => 'record_stop' );
		$this->assertStringEndsWith( '&es_notice=recording_stopped', (string) es_optimizer_autoload_process_request() );
		$_POST = array( 'es_optimizer_autoload_action' => 'record_clear' );
		$this->assertStringEndsWith( '&es_notice=results_cleared', (string) es_optimizer_autoload_process_request() );

		$_GET  = array( 'es_notice' => 'recording_started' );
		$lines = es_optimizer_autoload_get_result_lines( (array) es_optimizer_autoload_read_result() );
		$this->assertSame( 'success', $lines['type'] );
		$this->assertStringStartsWith( 'Recording started.', $lines['lines'][0] );
	}

	/**
	 * The list shows the recording controls, the code search button with its nonce, and the pages that read each option.
	 *
	 * @return void
	 */
	public function test_list_shows_the_evidence(): void {
		$database          = new wpdb();
		$database->var     = 2;
		$database->results = array(
			array( 'option_name' => 'seen_option', 'option_bytes' => 900, 'autoload' => 'on' ),
			array( 'option_name' => 'unseen_option', 'option_bytes' => 600, 'autoload' => 'on' ),
		);
		$GLOBALS['wpdb']   = $database;
		update_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION, array( 'pages' => 4, 'options' => array( 'seen_option' => array( 'pages' => 4, 'reader' => 'core' ) ) ), false );
		$_GET = array( 'page' => 'es-optimizer-settings', 'tab' => 'autoload' );

		$page = $this->capture( 'es_optimizer_settings_page' );

		$this->assertStringContainsString( 'value="record_start"', $page );
		$this->assertStringContainsString( 'value="record_clear"', $page );
		$this->assertStringContainsString( 'name="es_find" value="nonce-es_optimizer_autoload_find"', $page );
		$this->assertStringContainsString( '<td class="column-usage">4 of 4 recorded pages</td>', $page );
		$this->assertStringContainsString( '<td class="column-usage">No recorded page</td>', $page );
		$this->assertStringNotContainsString( 'The installed plugins and themes were searched', $page, 'The search runs only from its button.' );

		$_GET['es_find'] = 'forged';
		$this->assertStringNotContainsString( 'The installed plugins and themes were searched', $this->capture( 'es_optimizer_settings_page' ), 'A wrong nonce runs nothing.' );
		$_GET['es_find'] = 'nonce-es_optimizer_autoload_find';
		$this->assertStringContainsString( 'The installed plugins and themes were searched', $this->capture( 'es_optimizer_settings_page' ) );

		$GLOBALS['es_optimizer_test']['alloptions'] = array( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION => (string) ( time() + 300 ) );
		$_GET = array( 'page' => 'es-optimizer-settings', 'tab' => 'autoload' );
		$page = $this->capture( 'es_optimizer_settings_page' );
		$this->assertStringContainsString( 'value="record_stop"', $page );
		$this->assertStringContainsString( '4 pages so far.', $page );
	}

	/**
	 * Write a file under the temporary folder.
	 *
	 * @param string $relative Path below the folder.
	 * @param string $content  Content.
	 * @return void
	 */
	private function write( string $relative, string $content ): void {
		$path = $this->folder . '/' . $relative;

		if ( ! is_dir( dirname( $path ) ) ) {
			mkdir( dirname( $path ), 0777, true );
		}

		file_put_contents( $path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture.
	}

	/**
	 * Remove a folder and everything in it.
	 *
	 * @param string $path Folder.
	 * @return void
	 */
	private function remove( string $path ): void {
		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST ) as $item ) {
			// The path is a folder the test created under the system temp folder.
			// nosemgrep: php.lang.security.unlink-use.unlink-use
			$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- Test fixture cleanup.
		}

		rmdir( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- Test fixture cleanup.
	}
}
