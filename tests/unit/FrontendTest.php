<?php
/**
 * Tests for the front-end callbacks.
 *
 * @package EngineScript_Site_Optimizer
 */

/**
 * What each option removes or adds, and what other code's values pass through.
 */
final class FrontendTest extends EsOptimizerTestCase {

	/**
	 * A front-end request is neither admin, nor AJAX, nor the login screen's head (Q-005).
	 *
	 * @return void
	 */
	public function test_front_end_request(): void {
		$this->assertTrue( es_optimizer_is_frontend_request() );

		foreach ( array( 'is_admin', 'ajax' ) as $flag ) {
			es_optimizer_test_reset();
			$GLOBALS['es_optimizer_test'][ $flag ] = true;
			$this->assertFalse( es_optimizer_is_frontend_request(), $flag );
		}

		es_optimizer_test_reset();
		$GLOBALS['es_optimizer_test']['doing'] = 'login_head';
		$this->assertFalse( es_optimizer_is_frontend_request() );
		$GLOBALS['es_optimizer_test']['doing'] = 'wp_head';
		$this->assertTrue( es_optimizer_is_frontend_request() );
	}

	/**
	 * With the option on, the six emoji callbacks are removed, each at its priority; with it off, none.
	 *
	 * @return void
	 */
	public function test_emoji_removal(): void {
		es_optimizer_disable_emojis();
		es_optimizer_disable_admin_emojis();
		$this->assertSame( array(), $this->removals() );

		$this->store_options( array( 'disable_emojis' => 1 ) );
		es_optimizer_disable_emojis();
		$this->assertSame(
			array(
				'wp_head print_emoji_detection_script 7',
				'wp_print_styles print_emoji_styles 10',
				'embed_head print_emoji_detection_script 10',
				'the_content_feed wp_staticize_emoji 10',
				'comment_text_rss wp_staticize_emoji 10',
				'wp_mail wp_staticize_emoji_for_email 10',
			),
			$this->removals()
		);

		es_optimizer_test_reset();
		es_optimizer_disable_admin_emojis();
		$this->assertSame( array( 'admin_print_scripts print_emoji_detection_script 10', 'admin_print_styles print_emoji_styles 10' ), $this->removals() );
	}

	/**
	 * The editor loses only the emoji plugin; other code's values pass through (CR-SEM-002).
	 *
	 * @return void
	 */
	public function test_editor_plugins(): void {
		$plugins = array( 'lists', 'wpemoji', array( 'x' ), 'link' );
		$this->assertSame( $plugins, es_optimizer_disable_emojis_tinymce( $plugins ), 'Option off: unchanged.' );

		$this->store_options( array( 'disable_emojis' => 1 ) );
		$this->assertSame( array( 'lists', array( 'x' ), 'link' ), es_optimizer_disable_emojis_tinymce( $plugins ) );
		foreach ( array( null, false, 'wpemoji', 7 ) as $foreign ) {
			$this->assertSame( $foreign, es_optimizer_disable_emojis_tinymce( $foreign ) );
		}
	}

	/**
	 * jQuery loses its Migrate dependency on the front end only.
	 *
	 * @return void
	 */
	public function test_jquery_migrate(): void {
		$scripts                     = new WP_Scripts();
		$scripts->registered['jquery'] = (object) array( 'deps' => array( 'jquery-core', 'jquery-migrate', 'foreign' ) );

		es_optimizer_remove_jquery_migrate( $scripts );
		$this->assertSame( array( 'jquery-core', 'jquery-migrate', 'foreign' ), $scripts->registered['jquery']->deps, 'Option off: unchanged.' );

		$this->store_options( array( 'remove_jquery_migrate' => 1 ) );
		$GLOBALS['es_optimizer_test']['is_admin'] = true;
		es_optimizer_remove_jquery_migrate( $scripts );
		$this->assertSame( array( 'jquery-core', 'jquery-migrate', 'foreign' ), $scripts->registered['jquery']->deps, 'Admin: unchanged.' );

		$GLOBALS['es_optimizer_test']['is_admin'] = false;
		es_optimizer_remove_jquery_migrate( $scripts );
		$this->assertSame( array( 'jquery-core', 'foreign' ), $scripts->registered['jquery']->deps );

		$empty = new WP_Scripts();
		es_optimizer_remove_jquery_migrate( $empty );
		$this->assertSame( array(), $empty->registered, 'No jQuery registered: nothing to do.' );
	}

	/**
	 * The classic theme stylesheet is dequeued and deregistered when the option is on.
	 *
	 * @return void
	 */
	public function test_classic_theme_styles(): void {
		es_optimizer_disable_classic_theme_styles();
		$this->assertSame( array(), $GLOBALS['es_optimizer_test']['styles'] );

		$this->store_options( array( 'disable_classic_theme_styles' => 1 ) );
		es_optimizer_disable_classic_theme_styles();
		$this->assertSame( array( array( 'dequeue', 'classic-theme-styles' ), array( 'deregister', 'classic-theme-styles' ) ), $GLOBALS['es_optimizer_test']['styles'] );
	}

	/**
	 * Each header option removes exactly its callbacks: the version from the head and seven feeds, the shortlink from the head and the response header (CR-API-002).
	 *
	 * @return void
	 */
	public function test_header_cleanup_per_option(): void {
		es_optimizer_remove_header_items();
		$this->assertSame( array(), $this->removals() );

		$this->store_options( array( 'remove_wp_version' => 1 ) );
		es_optimizer_remove_header_items();
		$this->assertSame(
			array(
				'wp_head wp_generator 10',
				'commentsrss2_head the_generator 10',
				'rss_head the_generator 10',
				'rdf_header the_generator 10',
				'atom_head the_generator 10',
				'comments_atom_head the_generator 10',
				'opml_head the_generator 10',
				'app_head the_generator 10',
			),
			$this->removals(),
			'The RSS 2 action is shared with export files and waits for a feed request.'
		);

		es_optimizer_test_reset();
		$this->store_options( array( 'remove_rsd_link' => 1 ) );
		es_optimizer_remove_header_items();
		$this->assertSame( array( 'wp_head rsd_link 10' ), $this->removals() );

		es_optimizer_test_reset();
		$this->store_options( array( 'remove_shortlink' => 1 ) );
		es_optimizer_remove_header_items();
		$this->assertSame( array( 'wp_head wp_shortlink_wp_head 10', 'template_redirect wp_shortlink_header 11' ), $this->removals() );

		es_optimizer_test_reset();
		$this->store_options( array( 'remove_rest_api_link' => 1 ) );
		es_optimizer_remove_header_items();
		$this->assertSame( array( 'wp_head rest_output_link_wp_head 10', 'template_redirect rest_output_link_header 11' ), $this->removals() );

		es_optimizer_test_reset();
		$this->store_options( array( 'remove_oembed_links' => 1 ) );
		es_optimizer_remove_header_items();
		$this->assertSame( array( 'wp_head wp_oembed_add_discovery_links 4', 'wp_head wp_oembed_add_discovery_links 10' ), $this->removals(), 'WordPress hooks the discovery links twice.' );

		es_optimizer_test_reset();
		$this->store_options( array( 'remove_feed_links' => 1 ) );
		es_optimizer_remove_header_items();
		$this->assertSame( array( 'wp_head feed_links 2', 'wp_head feed_links_extra 3' ), $this->removals() );
	}

	/**
	 * The pattern-directory and AI filters answer false when their option is on, and pass the value through when it is off.
	 *
	 * @return void
	 */
	public function test_remote_patterns_and_ai_support_filters(): void {
		foreach ( array( true, null, 'foreign' ) as $incoming ) {
			$this->assertSame( $incoming, es_optimizer_filter_remote_patterns( $incoming ) );
			$this->assertSame( $incoming, es_optimizer_filter_ai_support( $incoming ) );
		}

		$this->store_options( array( 'disable_remote_patterns' => 1 ) );
		$this->assertFalse( es_optimizer_filter_remote_patterns( true ) );
		$this->assertTrue( es_optimizer_filter_ai_support( true ), 'Each filter follows its own option.' );

		$this->store_options( array( 'disable_ai_features' => 1 ) );
		$this->assertFalse( es_optimizer_filter_ai_support( true ) );
		$this->assertTrue( es_optimizer_filter_remote_patterns( true ) );
	}

	/**
	 * Heartbeat is deregistered on the front end only, with the option on.
	 *
	 * @return void
	 */
	public function test_frontend_heartbeat(): void {
		es_optimizer_disable_frontend_heartbeat();
		$this->assertSame( array(), $GLOBALS['es_optimizer_test']['scripts'], 'Option off.' );

		$this->store_options( array( 'heartbeat_control' => 1 ) );
		$GLOBALS['es_optimizer_test']['is_admin'] = true;
		es_optimizer_disable_frontend_heartbeat();
		$this->assertSame( array(), $GLOBALS['es_optimizer_test']['scripts'], 'Not in the admin area.' );

		$GLOBALS['es_optimizer_test']['is_admin'] = false;
		es_optimizer_disable_frontend_heartbeat();
		$this->assertSame( array( array( 'deregister', 'heartbeat' ) ), $GLOBALS['es_optimizer_test']['scripts'] );
	}

	/**
	 * In the admin area, outside the editors, Heartbeat slows to 120 seconds; a slower setting is kept.
	 *
	 * @return void
	 */
	public function test_admin_heartbeat_interval(): void {
		global $pagenow;

		$GLOBALS['es_optimizer_test']['is_admin'] = true;
		$pagenow                                  = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The test sets the admin page WordPress would set.

		$this->assertSame( array( 'nonce' => 'n' ), es_optimizer_filter_heartbeat_settings( array( 'nonce' => 'n' ) ), 'Option off.' );

		$this->store_options( array( 'heartbeat_control' => 1 ) );
		$this->assertSame( array( 'nonce' => 'n', 'interval' => 120 ), es_optimizer_filter_heartbeat_settings( array( 'nonce' => 'n' ) ) );
		$this->assertSame( array( 'interval' => 120 ), es_optimizer_filter_heartbeat_settings( array( 'interval' => 15 ) ) );
		$this->assertSame( array( 'interval' => 300 ), es_optimizer_filter_heartbeat_settings( array( 'interval' => 300 ) ), 'A slower interval from another plugin is kept.' );
		$this->assertSame( array( 'interval' => 120 ), es_optimizer_filter_heartbeat_settings( array( 'interval' => 'fast' ) ) );
		$this->assertNull( es_optimizer_filter_heartbeat_settings( null ), 'A foreign non-array passes through.' );

		foreach ( array( 'post.php', 'post-new.php', 'site-editor.php', 'customize.php' ) as $editor ) {
			$pagenow = $editor; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The test sets the admin page WordPress would set.
			$this->assertSame( array( 'interval' => 15 ), es_optimizer_filter_heartbeat_settings( array( 'interval' => 15 ) ), $editor );
		}

		$GLOBALS['es_optimizer_test']['is_admin'] = false;
		$pagenow                                  = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The test sets the admin page WordPress would set.
		$this->assertSame( array(), es_optimizer_filter_heartbeat_settings( array() ), 'The front end is handled by deregistering the script.' );
	}

	/**
	 * The RSS 2 generator goes only on a feed request with the option on, so an export file is untouched (D-012).
	 *
	 * @return void
	 */
	public function test_rss2_generator_waits_for_a_feed_request(): void {
		$this->store_options( array( 'remove_wp_version' => 1 ) );
		es_optimizer_remove_feed_generator();
		$this->assertSame( array(), $this->removals(), 'Not a feed: an export may be running.' );

		$GLOBALS['es_optimizer_test']['feed'] = true;
		es_optimizer_remove_feed_generator();
		$this->assertSame( array( 'rss2_head the_generator 10' ), $this->removals() );

		es_optimizer_test_reset();
		$GLOBALS['es_optimizer_test']['feed'] = true;
		$this->store_options( array( 'remove_wp_version' => 0 ) );
		es_optimizer_remove_feed_generator();
		$this->assertSame( array(), $this->removals(), 'Option off.' );
	}

	/**
	 * The late filters answer false when their option is on and pass the value through when it is off.
	 *
	 * @return void
	 */
	public function test_late_filter_answers(): void {
		foreach ( array( 'es_optimizer_filter_recent_comments_style' => 'remove_recent_comments_style', 'es_optimizer_filter_jetpack_ads' => 'disable_jetpack_ads', 'es_optimizer_filter_post_via_email' => 'disable_post_via_email' ) as $callback => $option ) {
			$this->store_options( array() );
			$this->assertSame( 'foreign', $callback( 'foreign' ), $callback . ' off' );
			$this->assertTrue( $callback( true ), $callback . ' off' );
			$this->store_options( array( $option => 1 ) );
			$this->assertFalse( $callback( 'foreign' ), $callback . ' on' );
		}
	}

	/**
	 * Preconnect: stored origins are added, crossorigin only for the font file host (CR-SEM-006), existing ones once.
	 *
	 * @return void
	 */
	public function test_preconnect_hints(): void {
		$this->store_options(
			array(
				'enable_preconnect'  => 1,
				'preconnect_domains' => "https://fonts.googleapis.com\nhttps://fonts.gstatic.com\nhttps://cdn.example.org\nhttps://port.example.org:8443",
			)
		);
		$foreign = array( 'https://cdn.example.org', array( 'href' => 'https://other.example.org', 'crossorigin' => 'use-credentials' ), 42 );

		$this->assertSame(
			array_merge( $foreign, array( array( 'href' => 'https://fonts.googleapis.com' ), array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' ) ) ),
			es_optimizer_add_preconnect_resource_hints( $foreign, 'preconnect' )
		);
	}

	/**
	 * The href of one existing hint: a string, or a string href in an array; anything else has none.
	 *
	 * @return void
	 */
	public function test_resource_hint_href(): void {
		$this->assertSame( 'https://a.example.org', es_optimizer_get_resource_hint_href( 'https://a.example.org' ) );
		$this->assertSame( 'https://b.example.org', es_optimizer_get_resource_hint_href( array( 'href' => 'https://b.example.org', 'crossorigin' => 'anonymous' ) ) );

		foreach ( array( array( 'href' => 42 ), array( 'href' => null ), array( 'https://c.example.org' ), array(), 42, null, false ) as $hint ) {
			$this->assertNull( es_optimizer_get_resource_hint_href( $hint ), var_export( $hint, true ) );
		}

		$this->assertSame(
			array( 'https://a.example.org' => true, 'https://b.example.org' => true ),
			es_optimizer_get_resource_hint_hrefs( array( 'https://a.example.org', array( 'href' => 'https://b.example.org' ), 42, array( 'href' => 7 ), 'https://a.example.org' ) )
		);
	}

	/**
	 * Preconnect adds nothing on the wrong relation, with the option off, outside the front end, or on the login screen.
	 *
	 * @return void
	 */
	public function test_preconnect_hints_are_left_alone_elsewhere(): void {
		$this->store_options( array( 'enable_preconnect' => 1, 'preconnect_domains' => 'https://cdn.example.org' ) );
		$hints = array( 'https://keep.example.org' );

		$this->assertSame( $hints, es_optimizer_add_preconnect_resource_hints( $hints, 'dns-prefetch' ) );
		$this->assertSame( $hints, es_optimizer_add_preconnect_resource_hints( $hints ), 'Applied with one argument.' );
		$this->assertSame( $hints, es_optimizer_add_preconnect_resource_hints( $hints, null ) );
		$GLOBALS['es_optimizer_test']['doing'] = 'login_head';
		$this->assertSame( $hints, es_optimizer_add_preconnect_resource_hints( $hints, 'preconnect' ), 'Login screen.' );
		$GLOBALS['es_optimizer_test']['doing'] = null;
		$GLOBALS['es_optimizer_test']['is_admin'] = true;
		$this->assertSame( $hints, es_optimizer_add_preconnect_resource_hints( $hints, 'preconnect' ), 'Admin.' );
		$GLOBALS['es_optimizer_test']['is_admin'] = false;
		$this->store_options( array( 'enable_preconnect' => 0, 'preconnect_domains' => 'https://cdn.example.org' ) );
		$this->assertSame( $hints, es_optimizer_add_preconnect_resource_hints( $hints, 'preconnect' ), 'Option off.' );
		foreach ( array( null, false, 'text', 7 ) as $foreign ) {
			$this->assertSame( $foreign, es_optimizer_add_preconnect_resource_hints( $foreign, 'preconnect' ) );
		}
	}

	/**
	 * DNS prefetch: stored origins are added once, ports included, and only on the front end.
	 *
	 * @return void
	 */
	public function test_dns_prefetch_hints(): void {
		$this->store_options( array( 'enable_dns_prefetch' => 1, 'dns_prefetch_domains' => "https://a.example.org\nhttps://port.example.org:8443" ) );

		$this->assertSame( array( 'https://a.example.org', 'https://port.example.org:8443' ), es_optimizer_add_dns_prefetch_resource_hints( array( 'https://a.example.org' ), 'dns-prefetch' ) );
		$this->assertSame( array(), es_optimizer_add_dns_prefetch_resource_hints( array(), 'preconnect' ) );
		$GLOBALS['es_optimizer_test']['ajax'] = true;
		$this->assertSame( array(), es_optimizer_add_dns_prefetch_resource_hints( array(), 'dns-prefetch' ), 'AJAX.' );
		$GLOBALS['es_optimizer_test']['ajax'] = false;
		$this->store_options( array( 'enable_dns_prefetch' => 0, 'dns_prefetch_domains' => 'https://a.example.org' ) );
		$this->assertSame( array(), es_optimizer_add_dns_prefetch_resource_hints( array(), 'dns-prefetch' ), 'Option off.' );
		$this->assertNull( es_optimizer_add_dns_prefetch_resource_hints( null, 'dns-prefetch' ) );
	}
}
