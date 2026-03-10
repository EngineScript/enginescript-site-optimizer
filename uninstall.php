<?php
/**
 * Uninstall handler for EngineScript Site Optimizer
 *
 * Fired when the plugin is uninstalled (deleted) from the WordPress admin.
 * Removes all plugin options from the database.
 *
 * @package EngineScript_Site_Optimizer
 * @since   2.0.1
 */

// Security: Abort if not called by WordPress uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options.
delete_option( 'es_optimizer_options' );
