<?php
/**
 * Plugin Name: EngineScript Site Optimizer
 * Plugin URI: https://github.com/EngineScript/enginescript-site-optimizer
 * Description: Optimizes WordPress by removing unnecessary features and scripts to improve performance
 * Version: 2.1.0
 * Author: EngineScript
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: enginescript-site-optimizer
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Tested up to: 7.0
 * Security: Follows OWASP security guidelines and WordPress best practices
 *
 * @package EngineScript_Site_Optimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Define plugin version.
if ( ! defined( 'ES_SITE_OPTIMIZER_VERSION' ) ) {
	define( 'ES_SITE_OPTIMIZER_VERSION', '2.1.0' );
}

if ( ! defined( 'ES_SITE_OPTIMIZER_FILE' ) ) {
	define( 'ES_SITE_OPTIMIZER_FILE', __FILE__ );
}

if ( ! defined( 'ES_SITE_OPTIMIZER_PATH' ) ) {
	define( 'ES_SITE_OPTIMIZER_PATH', plugin_dir_path( __FILE__ ) );
}

require_once __DIR__ . '/includes/options.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/frontend.php';
require_once __DIR__ . '/includes/bootstrap.php';
