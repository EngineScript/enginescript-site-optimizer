<?php
/**
 * Plugin bootstrap and WordPress hook registration.
 *
 * @package EngineScript_Site_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Initialize the EngineScript Site Optimizer plugin.
 *
 * @since 1.6.0
 */
function es_optimizer_init_plugin(): void {
	es_optimizer_clear_options_cache();
	es_optimizer_init_admin();
	es_optimizer_init_frontend_optimizations();
	es_optimizer_init_plugin_links();
}

/**
 * Plugin activation hook.
 *
 * @since 1.6.0
 * @param bool $network_wide Whether the plugin is being network activated.
 */
function es_optimizer_activate_plugin( bool $network_wide = false ): void {
	unset( $network_wide );

	if ( false === get_option( 'es_optimizer_options' ) ) {
		add_option( 'es_optimizer_options', es_optimizer_get_default_options() );
	}

	es_optimizer_clear_options_cache();
}

/**
 * Plugin deactivation hook.
 *
 * @since 1.6.0
 * @param bool $network_wide Whether the plugin is being network deactivated.
 */
function es_optimizer_deactivate_plugin( bool $network_wide = false ): void {
	unset( $network_wide );

	es_optimizer_clear_options_cache();
}

/**
 * Initialize admin-related functionality.
 *
 * @since 1.6.0
 */
function es_optimizer_init_admin(): void {
	if ( ! is_admin() ) {
		return;
	}

	add_action( 'admin_init', 'es_optimizer_init_settings' );
	add_action( 'admin_menu', 'es_optimizer_add_settings_page' );
}

/**
 * Initialize frontend optimization functionality.
 *
 * @since 1.6.0
 */
function es_optimizer_init_frontend_optimizations(): void {
	add_action( 'init', 'es_optimizer_disable_emojis' );
	add_action( 'wp_default_scripts', 'es_optimizer_remove_jquery_migrate' );
	add_action( 'wp_enqueue_scripts', 'es_optimizer_disable_classic_theme_styles', 100 );
	add_action( 'init', 'es_optimizer_remove_header_items' );
	add_action( 'init', 'es_optimizer_remove_recent_comments_style' );
	add_filter( 'wp_resource_hints', 'es_optimizer_add_preconnect_resource_hints', 10, 2 );
	add_filter( 'wp_resource_hints', 'es_optimizer_add_dns_prefetch_resource_hints', 10, 2 );
	add_action( 'init', 'es_optimizer_disable_jetpack_ads' );
	add_action( 'init', 'es_optimizer_disable_post_via_email' );
}

/**
 * Initialize plugin action links.
 *
 * @since 1.6.0
 */
function es_optimizer_init_plugin_links(): void {
	$plugin_basename = plugin_basename( ES_SITE_OPTIMIZER_FILE );
	add_filter( "plugin_action_links_{$plugin_basename}", 'es_optimizer_add_settings_link' );
}

add_action( 'plugins_loaded', 'es_optimizer_init_plugin' );
add_action( 'update_option_es_optimizer_options', 'es_optimizer_clear_options_cache', 10, 0 );
register_activation_hook( ES_SITE_OPTIMIZER_FILE, 'es_optimizer_activate_plugin' );
register_deactivation_hook( ES_SITE_OPTIMIZER_FILE, 'es_optimizer_deactivate_plugin' );
