<?php
/**
 * Tests for what the plugin registers with WordPress.
 *
 * @package EngineScript_Site_Optimizer
 */

/**
 * Hooks, priorities, and the absence of lifecycle callbacks.
 */
final class PluginWiringTest extends EsOptimizerTestCase {

	/**
	 * Loading the plugin file registers the start-up hook and Jetpack's three filters, and nothing else.
	 *
	 * Jetpack reads its filters during plugins_loaded, before init, so they must exist when the plugin loads (CR-API-003).
	 *
	 * @return void
	 */
	public function test_loading_the_plugin_registers_the_start_up_hook_and_the_jetpack_filters(): void {
		$loaded  = $GLOBALS['es_optimizer_test_load_time'];
		$jetpack = array( 'jetpack_just_in_time_msgs', 'jetpack_show_promotions', 'jetpack_blaze_enabled' );

		$this->assertSame( array_merge( array( 'plugins_loaded' ), $jetpack ), array_keys( $loaded['hooks'] ) );
		$this->assertSame( array( 10 => array( array( 'callback' => 'es_optimizer_init_plugin', 'args' => 1 ) ) ), $loaded['hooks']['plugins_loaded'] );
		foreach ( $jetpack as $hook ) {
			$this->assertSame( array( PHP_INT_MAX => array( array( 'callback' => 'es_optimizer_filter_jetpack_ads', 'args' => 1 ) ) ), $loaded['hooks'][ $hook ], $hook );
		}
		$this->assertSame( array(), $loaded['removed'] );
		$this->assertEmpty( $GLOBALS['es_optimizer_test_activation_hooks'] ?? array(), 'Activation needs no callback: settings stay virtual until a save.' );
		$this->assertEmpty( $GLOBALS['es_optimizer_test_deactivation_hooks'] ?? array(), 'Deactivation needs no callback: settings are kept.' );
		$this->assertSame( dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR . 'enginescript-site-optimizer.php', ES_SITE_OPTIMIZER_FILE );
	}

	/**
	 * Front-end start-up registers each callback with its priority and argument count, and nothing more.
	 *
	 * @return void
	 */
	public function test_front_end_start_up_registers_every_callback(): void {
		es_optimizer_init_plugin();

		$expected = array(
			array( 'init', 'es_optimizer_disable_emojis', 10, 1 ),
			array( 'init', 'es_optimizer_remove_header_items', 10, 1 ),
			array( 'init', 'es_optimizer_remove_recent_comments_style', 10, 1 ),
			array( 'init', 'es_optimizer_disable_post_via_email', 10, 1 ),
			array( 'wp_default_scripts', 'es_optimizer_remove_jquery_migrate', 10, 1 ),
			array( 'wp_enqueue_scripts', 'es_optimizer_disable_classic_theme_styles', 100, 1 ),
			array( 'template_redirect', 'es_optimizer_remove_feed_generator', 10, 1 ),
			array( 'wp_resource_hints', 'es_optimizer_add_preconnect_resource_hints', 10, 2 ),
			array( 'wp_resource_hints', 'es_optimizer_add_dns_prefetch_resource_hints', 10, 2 ),
			array( 'tiny_mce_plugins', 'es_optimizer_disable_emojis_tinymce', 10, 1 ),
			array( 'plugin_action_links_enginescript-site-optimizer.php', 'es_optimizer_add_settings_link', 10, 1 ),
		);

		$registered = array();
		foreach ( $GLOBALS['es_optimizer_test']['hooks'] as $hook => $by_priority ) {
			foreach ( $by_priority as $priority => $entries ) {
				foreach ( $entries as $entry ) {
					$registered[] = array( $hook, $entry['callback'], $priority, $entry['args'] );
				}
			}
		}

		$this->assertEqualsCanonicalizing( $expected, $registered, 'Exactly these callbacks, no admin hook on the front end.' );
		foreach ( $expected as $row ) {
			$this->assertTrue( function_exists( $row[1] ), $row[1] . ' exists.' );
		}
	}

	/**
	 * In the admin area start-up also registers the settings, the admin emoji removal, and the menu.
	 *
	 * @return void
	 */
	public function test_admin_start_up_adds_the_settings_hooks(): void {
		$GLOBALS['es_optimizer_test']['is_admin'] = true;
		es_optimizer_init_plugin();

		$this->assertSame( 10, es_optimizer_test_hook_priority( 'admin_init', 'es_optimizer_init_settings' ) );
		$this->assertSame( 10, es_optimizer_test_hook_priority( 'admin_init', 'es_optimizer_disable_admin_emojis' ) );
		$this->assertSame( 10, es_optimizer_test_hook_priority( 'admin_menu', 'es_optimizer_add_settings_page' ) );
		$this->assertSame( 10, es_optimizer_test_hook_priority( 'init', 'es_optimizer_disable_emojis' ), 'Front-end hooks are registered in the admin area too.' );
	}

	/**
	 * The late filters run last, so that the plugin's answer is the one WordPress sees.
	 *
	 * @return void
	 */
	public function test_late_filters_are_registered_at_the_highest_priority(): void {
		es_optimizer_remove_recent_comments_style();
		es_optimizer_disable_jetpack_ads();
		es_optimizer_disable_post_via_email();

		$this->assertSame( PHP_INT_MAX, es_optimizer_test_hook_priority( 'show_recent_comments_widget_style', 'es_optimizer_filter_recent_comments_style' ) );
		foreach ( array( 'jetpack_just_in_time_msgs', 'jetpack_show_promotions', 'jetpack_blaze_enabled' ) as $hook ) {
			$this->assertSame( PHP_INT_MAX, es_optimizer_test_hook_priority( $hook, 'es_optimizer_filter_jetpack_ads' ), $hook );
		}
		$this->assertSame( PHP_INT_MAX, es_optimizer_test_hook_priority( 'enable_post_by_email_configuration', 'es_optimizer_filter_post_via_email' ) );
	}

	/**
	 * The removed code stays removed (batch 15-L).
	 *
	 * @return void
	 */
	public function test_obsolete_callbacks_no_longer_exist(): void {
		foreach ( array( 'es_optimizer_disable_emojis_remove_dns_prefetch', 'es_optimizer_get_resource_hint_host', 'es_optimizer_activate_plugin', 'es_optimizer_deactivate_plugin', 'es_optimizer_resource_hint_exists' ) as $function ) {
			$this->assertFalse( function_exists( $function ), $function );
		}
		$this->assertFalse( defined( 'ES_SITE_OPTIMIZER_PATH' ) );
	}
}
