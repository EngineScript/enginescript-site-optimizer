<?php
/**
 * Plugin Name: EngineScript Site Optimizer
 * Plugin URI: https://github.com/EngineScript/enginescript-site-optimizer
 * Description: Optimizes WordPress by removing unnecessary features and scripts to improve performance
 * Version: 2.0.0
 * Author: EngineScript
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: enginescript-site-optimizer
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Tested up to: 6.9
 * Security: Follows OWASP security guidelines and WordPress best practices
 *
 * @package EngineScript_Site_Optimizer
 */

/**
 * Security Implementation Notes:
 *
 * This plugin follows WordPress security best practices and OWASP guidelines:
 *
 * 1. Input Validation: All user inputs are validated before processing
 *    - Options are strictly type-checked (checkbox values limited to 0 or 1)
 *    - URLs undergo multi-layer validation (filter_var + WordPress sanitization)
 *
 * 2. Output Escaping: All outputs are properly escaped with context-appropriate functions
 *    - HTML content: esc_html(), esc_html_e()
 *    - Attributes: esc_attr()
 *    - URLs: esc_url(), esc_url_raw()
 *    - Textarea content: esc_textarea()
 *
 * 3. Capability Checks: All admin functions verify user permissions
 *    - current_user_can('manage_options') guards settings pages
 *
 * 4. Secure Coding Patterns:
 *    - Direct script access prevention
 *    - Proper use of WordPress hooks and filters
 *    - Code follows WordPress Plugin Handbook guidelines
 *
 * Some uses of echo/printf with proper escaping are unavoidable for HTML output,
 * and have been documented with phpcs:ignore comments explaining the security measures.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	// Security: Prevent direct script access (WordPress best practice).
	// This block prevents the script from being loaded directly via URL,
	// which could potentially bypass WordPress security mechanisms.
	return;
}

// Define plugin version.
if ( ! defined( 'ES_SITE_OPTIMIZER_VERSION' ) ) {
	define( 'ES_SITE_OPTIMIZER_VERSION', '2.0.0' );
}

/**
 * Initialize the EngineScript Site Optimizer plugin
 *
 * This function is hooked to 'plugins_loaded' to ensure all other plugins
 * have been loaded first, preventing potential conflicts and ensuring
 * WordPress core functions and other plugin APIs are available.
 *
 * @since 1.6.0
 */
function es_optimizer_init_plugin() {
	// Clear options cache to ensure fresh data after all plugins are loaded.
	es_optimizer_clear_options_cache();
	
	// Initialize admin functionality.
	es_optimizer_init_admin();
	
	// Initialize frontend optimizations.
	es_optimizer_init_frontend_optimizations();
	
	// Initialize plugin settings link.
	es_optimizer_init_plugin_links();
}
add_action( 'plugins_loaded', 'es_optimizer_init_plugin' );

/**
 * Plugin activation hook
 *
 * @since 1.6.0
 */
function es_optimizer_activate_plugin() {
	// Ensure default options are set on activation.
	if ( false === get_option( 'es_optimizer_options' ) ) {
		add_option( 'es_optimizer_options', es_optimizer_get_default_options() );
	}
	
	// Clear any cached data.
	es_optimizer_clear_options_cache();
}
register_activation_hook( __FILE__, 'es_optimizer_activate_plugin' );

/**
 * Plugin deactivation hook
 *
 * @since 1.6.0
 */
function es_optimizer_deactivate_plugin() {
	// Clear any cached data on deactivation.
	es_optimizer_clear_options_cache();
	
	// Note: We don't delete options on deactivation to preserve user settings.
	// Options are only deleted on plugin uninstall.
}
register_deactivation_hook( __FILE__, 'es_optimizer_deactivate_plugin' );

/**
 * Initialize admin-related functionality
 *
 * @since 1.6.0
 */
function es_optimizer_init_admin() {
	if ( is_admin() ) {
		add_action( 'admin_init', 'es_optimizer_init_settings' );
		add_action( 'admin_menu', 'es_optimizer_add_settings_page' );
	}
}

/**
 * Initialize frontend optimization functionality
 *
 * @since 1.6.0
 */
function es_optimizer_init_frontend_optimizations() {
	add_action( 'init', 'es_optimizer_disable_emojis' );
	add_action( 'wp_default_scripts', 'es_optimizer_remove_jquery_migrate' );
	add_action( 'wp_enqueue_scripts', 'es_optimizer_disable_classic_theme_styles', 100 );
	add_action( 'init', 'es_optimizer_remove_header_items' );
	add_action( 'init', 'es_optimizer_remove_recent_comments_style' );
	add_action( 'wp_head', 'es_optimizer_add_preconnect', 0 );
	add_action( 'wp_head', 'es_optimizer_add_dns_prefetch', 0 );
	add_action( 'init', 'es_optimizer_disable_jetpack_ads' );
	add_action( 'init', 'es_optimizer_disable_post_via_email' );
}

/**
 * Initialize plugin action links
 *
 * @since 1.6.0
 */
function es_optimizer_init_plugin_links() {
	$plugin_basename = plugin_basename( __FILE__ );
	add_filter( "plugin_action_links_{$plugin_basename}", 'es_optimizer_add_settings_link' );
}

/**
 * Initialize the plugin settings
 *
 * @since 1.0.0
 */
function es_optimizer_init_settings() {
	// Register settings.
	register_setting(
		'es_optimizer_settings',
		'es_optimizer_options',
		array(
			'sanitize_callback' => 'es_optimizer_validate_options',
			'default'           => es_optimizer_get_default_options(),
		)
	);

	// Register default options if they don't exist.
	if ( false === get_option( 'es_optimizer_options' ) ) {
		add_option( 'es_optimizer_options', es_optimizer_get_default_options() );
	}
}

/**
 * Get default plugin options
 *
 * @since 1.0.0
 * @return array Default options.
 */
function es_optimizer_get_default_options() {
	return array(
		'disable_emojis'               => 0,
		'remove_jquery_migrate'        => 0,
		'disable_classic_theme_styles' => 0,
		'remove_wp_version'            => 0,
		'remove_rsd_link'              => 0,
		'remove_wlw_manifest'          => 0,
		'remove_shortlink'             => 0,
		'remove_recent_comments_style' => 0,
		'enable_preconnect'            => 0,
		'preconnect_domains'           => implode(
			"\n",
			array(
				'https://fonts.googleapis.com',
				'https://fonts.gstatic.com',
				'https://s.w.org',
				'https://wordpress.com',
				'https://cdnjs.cloudflare.com',
				'https://www.googletagmanager.com',
			)
		),
		'enable_dns_prefetch'          => 0,
		'dns_prefetch_domains'         => 'https://adservice.google.com',
		'disable_jetpack_ads'          => 0,
		'disable_post_via_email'       => 0,
	);
}

/**
 * Get cached plugin options to reduce database queries
 *
 * @since 1.5.13
 * @param bool $force_refresh Whether to force a fresh database read.
 * @return array Plugin options.
 */
function es_optimizer_get_options( $force_refresh = false ) {
	static $cached_options = null;

	if ( null === $cached_options || $force_refresh ) {
		$cached_options = get_option( 'es_optimizer_options', es_optimizer_get_default_options() );
	}

	return $cached_options;
}

/**
 * Clear the options cache (used when options are updated)
 *
 * @since 1.5.13
 */
function es_optimizer_clear_options_cache() {
	es_optimizer_get_options( true );
}

/**
 * Add settings page to the admin menu
 *
 * @since 1.0.0
 */
function es_optimizer_add_settings_page() {
	add_options_page(
		__( 'Site Optimizer Settings', 'enginescript-site-optimizer' ),
		__( 'Site Optimizer', 'enginescript-site-optimizer' ),
		'manage_options',
		'es-optimizer-settings',
		'es_optimizer_settings_page'
	);
}

/**
 * Render the settings page
 *
 * @since 1.0.0
 */
function es_optimizer_settings_page() {
	// Security: Check user capabilities before displaying the page.
	// This prevents unauthorized access to plugin settings.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'enginescript-site-optimizer' ) );
	}

	$options = es_optimizer_get_options();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Site Optimizer Settings', 'enginescript-site-optimizer' ); ?></h1>
		<p><?php esc_html_e( 'Select which optimizations you want to enable and customize the DNS prefetch domains.', 'enginescript-site-optimizer' ); ?></p>

		<form method="post" action="options.php">
			<?php
			settings_fields( 'es_optimizer_settings' );
			?>

			<table class="form-table">
				<?php
				// Render performance optimization options.
				es_optimizer_render_performance_options( $options );

				// Render header cleanup options.
				es_optimizer_render_header_options( $options );

				// Render additional features.
				es_optimizer_render_additional_options( $options );
				?>
			</table>

			<p class="submit">
				<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'enginescript-site-optimizer' ); ?>" />
			</p>
		</form>

		<hr>
		<p>
			<?php esc_html_e( 'This plugin is part of the EngineScript project.', 'enginescript-site-optimizer' ); ?>
			<a href="https://github.com/EngineScript/EngineScript" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Visit the EngineScript GitHub page', 'enginescript-site-optimizer' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * Render performance optimization options
 *
 * @since 1.0.0
 * @param array $options Plugin options.
 */
function es_optimizer_render_performance_options( $options ) {
	// Emoji settings.
	es_optimizer_render_checkbox_option(
		$options,
		'disable_emojis',
		__( 'Disable WordPress Emojis', 'enginescript-site-optimizer' ),
		__( 'Remove emoji scripts and styles to improve page load time', 'enginescript-site-optimizer' )
	);

	// jQuery Migrate settings.
	es_optimizer_render_checkbox_option(
		$options,
		'remove_jquery_migrate',
		__( 'Remove jQuery Migrate', 'enginescript-site-optimizer' ),
		__( 'Remove jQuery Migrate script (may affect compatibility with very old plugins)', 'enginescript-site-optimizer' )
	);

	// Classic Theme Styles settings.
	es_optimizer_render_checkbox_option(
		$options,
		'disable_classic_theme_styles',
		__( 'Disable Classic Theme Styles', 'enginescript-site-optimizer' ),
		__( 'Remove classic theme styles added in WordPress 6.1+', 'enginescript-site-optimizer' )
	);
}

/**
 * Render header cleanup options
 *
 * @since 1.0.0
 * @param array $options Plugin options.
 */
function es_optimizer_render_header_options( $options ) {
	// WordPress Version settings.
	es_optimizer_render_checkbox_option(
		$options,
		'remove_wp_version',
		__( 'Remove WordPress Version', 'enginescript-site-optimizer' ),
		__( 'Remove WordPress version from header (security benefit)', 'enginescript-site-optimizer' )
	);

	// RSD Link settings.
	es_optimizer_render_checkbox_option(
		$options,
		'remove_rsd_link',
		__( 'Remove RSD Link', 'enginescript-site-optimizer' ),
		__( 'Remove Really Simple Discovery (RSD) link from header', 'enginescript-site-optimizer' )
	);

	// WLW Manifest settings.
	es_optimizer_render_checkbox_option(
		$options,
		'remove_wlw_manifest',
		__( 'Remove WLW Manifest', 'enginescript-site-optimizer' ),
		__( 'Remove Windows Live Writer manifest link', 'enginescript-site-optimizer' )
	);

	// Shortlink settings.
	es_optimizer_render_checkbox_option(
		$options,
		'remove_shortlink',
		__( 'Remove Shortlink', 'enginescript-site-optimizer' ),
		__( 'Remove WordPress shortlink URLs from header', 'enginescript-site-optimizer' )
	);

	// Recent Comments Style settings.
	es_optimizer_render_checkbox_option(
		$options,
		'remove_recent_comments_style',
		__( 'Remove Recent Comments Style', 'enginescript-site-optimizer' ),
		__( 'Remove recent comments widget inline CSS', 'enginescript-site-optimizer' )
	);
}

/**
 * Render additional optimization options
 *
 * @since 1.0.0
 * @param array $options Plugin options.
 */
function es_optimizer_render_additional_options( $options ) {
	// Jetpack Ads settings.
	es_optimizer_render_checkbox_option(
		$options,
		'disable_jetpack_ads',
		__( 'Disable Jetpack Ads', 'enginescript-site-optimizer' ),
		__( 'Remove Jetpack advertisements and promotions', 'enginescript-site-optimizer' )
	);

	// Post via Email settings.
	es_optimizer_render_checkbox_option(
		$options,
		'disable_post_via_email',
		__( 'Disable Post via Email', 'enginescript-site-optimizer' ),
		__( 'Disable WordPress post via email functionality for security and performance', 'enginescript-site-optimizer' )
	);

	// Preconnect settings.
	es_optimizer_render_checkbox_option(
		$options,
		'enable_preconnect',
		__( 'Enable Preconnect', 'enginescript-site-optimizer' ),
		__( 'Preconnect to external domains for faster resource loading', 'enginescript-site-optimizer' )
	);

	// Preconnect Domains textarea.
	es_optimizer_render_textarea_option(
		$options,
		'preconnect_domains',
		__( 'Preconnect Domains', 'enginescript-site-optimizer' ),
		__( 'Use preconnect for domains that host critical, frequently used resources, like Google Fonts. This hint tells the browser to establish a connection (including DNS lookup, TCP handshake, and TLS negotiation) as soon as possible, which can save 100–500ms on the subsequent request. Enter one HTTPS domain per line (e.g., https://fonts.googleapis.com). Only clean domains are allowed - no file paths, query parameters, or fragments.', 'enginescript-site-optimizer' )
	);

	// DNS Prefetch settings.
	es_optimizer_render_checkbox_option(
		$options,
		'enable_dns_prefetch',
		__( 'Enable DNS Prefetch', 'enginescript-site-optimizer' ),
		__( 'DNS prefetch for less critical external domains', 'enginescript-site-optimizer' )
	);

	// DNS Prefetch Domains textarea.
	es_optimizer_render_textarea_option(
		$options,
		'dns_prefetch_domains',
		__( 'DNS Prefetch Domains', 'enginescript-site-optimizer' ),
		__( 'DNS-prefetch is a lighter-weight alternative to preconnect that performs only the DNS lookup. Use it for less critical domains or as a fallback for browsers that don\'t support preconnect. Enter one HTTPS domain per line (e.g., https://adservice.google.com). Only clean domains are allowed - no file paths, query parameters, or fragments.', 'enginescript-site-optimizer' )
	);
}

/**
 * Helper function to render checkbox options
 *
 * This function uses proper escaping for output security:
 * - All text is escaped with esc_html_e() with translation support
 * - Attribute values are escaped with esc_attr()
 * - WordPress checked() function is used for checkbox state
 *
 * @since 1.0.0
 * @param array  $options       Plugin options.
 * @param string $option_name   Option name.
 * @param string $title         Option title.
 * @param string $description   Option description.
 */
function es_optimizer_render_checkbox_option( $options, $option_name, $title, $description ) {
	?>
	<tr valign="top">
		<th scope="row">
			<?php
			// Using esc_html for secure output of titles.
			echo esc_html( $title );
			?>
		</th>
		<td>
			<label>
				<input type="checkbox" name="<?php printf( 'es_optimizer_options[%s]', esc_attr( $option_name ) ); ?>" value="1" <?php checked( 1, isset( $options[ $option_name ] ) ? $options[ $option_name ] : 0 ); ?> />
				<?php echo esc_html( $description ); ?>
			</label>
		</td>
	</tr>
	<?php
}

/**
 * Helper function to render textarea options
 *
 * This function uses proper escaping for output security:
 * - All text is escaped with esc_html_e() with translation support
 * - Attribute values are escaped with esc_attr()
 * - Textarea content is escaped with esc_textarea()
 *
 * @since 1.0.0
 * @param array  $options       Plugin options.
 * @param string $option_name   Option name.
 * @param string $title         Option title.
 * @param string $description   Option description.
 */
function es_optimizer_render_textarea_option( $options, $option_name, $title, $description ) {
	?>
	<tr valign="top">
		<th scope="row">
			<?php
			// Using esc_html for secure output of titles.
			echo esc_html( $title );
			?>
		</th>
		<td>
			<p><small>
				<?php
				// Using esc_html for secure output of descriptions.
				echo esc_html( $description );
				?>
			</small></p>
			<?php
			$textarea_value = isset( $options[ $option_name ] ) ? $options[ $option_name ] : '';
			?>
			<textarea name="<?php printf( 'es_optimizer_options[%s]', esc_attr( $option_name ) ); ?>" rows="5" cols="50" class="large-text code"><?php echo esc_textarea( $textarea_value ); ?></textarea>
		</td>
	</tr>
	<?php
}

/**
 * Validate options before saving
 *
 * This function implements a security-focused validation system:
 * 1. Verifies WordPress nonce for CSRF protection
 * 2. Checkboxes are validated to ensure they contain only boolean values (0 or 1)
 * 3. DNS prefetch domains undergo multiple validation steps:
 *    - Trimming to remove unwanted whitespace
 *    - Empty value checking
 *    - URL validation via filter_var()
 *    - Sanitization via esc_url_raw()
 *
 * @param array $input User submitted options.
 * @return array Validated and sanitized options.
 */
function es_optimizer_validate_options( $input ) {
	// CSRF protection is handled by WordPress Settings API via settings_fields() nonce.
	$valid = array();

	// Validate checkboxes (0 or 1).
	$checkboxes = array(
		'disable_emojis',
		'remove_jquery_migrate',
		'disable_classic_theme_styles',
		'remove_wp_version',
		'remove_rsd_link',
		'remove_wlw_manifest',
		'remove_shortlink',
		'remove_recent_comments_style',
		'enable_preconnect',
		'enable_dns_prefetch',
		'disable_jetpack_ads',
		'disable_post_via_email',
	);

	foreach ( $checkboxes as $checkbox ) {
		$valid[ $checkbox ] = isset( $input[ $checkbox ] ) ? 1 : 0;
	}

	// Validate and sanitize the preconnect domains with enhanced security.
	if ( isset( $input['preconnect_domains'] ) ) {
		$valid['preconnect_domains'] = es_optimizer_validate_preconnect_domains( $input['preconnect_domains'] );
	}

	// Validate and sanitize the DNS prefetch domains with enhanced security.
	if ( isset( $input['dns_prefetch_domains'] ) ) {
		$valid['dns_prefetch_domains'] = es_optimizer_validate_dns_prefetch_domains( $input['dns_prefetch_domains'] );
	}

	return $valid;
}

/**
 * Validate preconnect domains with enhanced security
 *
 * @since 1.4.0
 * @param string $domains_input Raw domain input from user.
 * @return string Validated and sanitized domains.
 */
function es_optimizer_validate_preconnect_domains( $domains_input ) {
	$domains           = explode( "\n", trim( $domains_input ) );
	$sanitized_domains = array();
	$rejected_domains  = array();

	foreach ( $domains as $domain ) {
		$domain = trim( $domain );
		if ( empty( $domain ) ) {
			continue;
		}

		$validation_result = es_optimizer_validate_single_domain( $domain );

		if ( $validation_result['valid'] ) {
			$sanitized_domains[] = $validation_result['domain'];
		} else {
			$rejected_domains[] = $validation_result['error'];
		}
	}

	// Show admin notice if any domains were rejected for security reasons.
	if ( ! empty( $rejected_domains ) ) {
		es_optimizer_show_domain_rejection_notice( $rejected_domains );
	}

	return implode( "\n", $sanitized_domains );
}

/**
 * Validate DNS prefetch domains with enhanced security
 *
 * @since 1.8.0
 * @param string $domains_input Raw domain input from user.
 * @return string Validated and sanitized domains.
 */
function es_optimizer_validate_dns_prefetch_domains( $domains_input ) {
	$domains           = explode( "\n", trim( $domains_input ) );
	$sanitized_domains = array();
	$rejected_domains  = array();

	foreach ( $domains as $domain ) {
		$domain = trim( $domain );
		if ( empty( $domain ) ) {
			continue;
		}

		$validation_result = es_optimizer_validate_single_domain( $domain );

		if ( $validation_result['valid'] ) {
			$sanitized_domains[] = $validation_result['domain'];
		} else {
			$rejected_domains[] = $validation_result['error'];
		}
	}

	// Show admin notice if any domains were rejected for security reasons.
	if ( ! empty( $rejected_domains ) ) {
		es_optimizer_show_dns_prefetch_rejection_notice( $rejected_domains );
	}

	return implode( "\n", $sanitized_domains );
}

/**
 * Show admin notice for rejected DNS prefetch domains
 *
 * @since 1.8.0
 * @param array $rejected_domains Array of rejected domain strings.
 */
function es_optimizer_show_dns_prefetch_rejection_notice( $rejected_domains ) {
	// Security: Properly escape and limit the rejected domains in error messages.
	$escaped_domains  = array_map( 'esc_html', array_slice( $rejected_domains, 0, 3 ) );
	$rejected_message = implode( ', ', $escaped_domains );

	if ( count( $rejected_domains ) > 3 ) {
		$rejected_message .= esc_html__( '...', 'enginescript-site-optimizer' );
	}

	$message = sprintf(
		// translators: %s is the list of rejected domain names.
		esc_html__( 'Some DNS prefetch domains were rejected for security reasons: %s', 'enginescript-site-optimizer' ),
		$rejected_message
	);

	add_settings_error(
		'es_optimizer_options',
		'dns_prefetch_security',
		$message,
		'warning'
	);
}

/**
 * Validate a single preconnect domain
 *
 * @since 1.4.0
 * @param string $domain Domain to validate.
 * @return array Validation result with 'valid' boolean and 'domain' or 'error'
 */
function es_optimizer_validate_single_domain( $domain ) {
	// Enhanced URL validation with security checks.
	if ( ! filter_var( $domain, FILTER_VALIDATE_URL ) ) {
		return array(
			'valid' => false,
			'error' => $domain . ' (invalid URL format)',
		);
	}

	// Use wp_parse_url instead of parse_url for WordPress compatibility.
	$parsed_url = wp_parse_url( $domain );

	// Security: Enforce HTTPS-only domains for preconnect.
	if ( ! isset( $parsed_url['scheme'] ) || 'https' !== $parsed_url['scheme'] ) {
		return array(
			'valid' => false,
			'error' => $domain . ' (HTTPS required for security)',
		);
	}

	// Additional security checks.
	if ( ! isset( $parsed_url['host'] ) ) {
		return array(
			'valid' => false,
			'error' => $domain . ' (no host found)',
		);
	}

	// Security: Preconnect should only use clean domains, not file paths.
	// Reject URLs with paths, query parameters, or fragments.
	if ( isset( $parsed_url['path'] ) && '/' !== $parsed_url['path'] && '' !== $parsed_url['path'] ) {
		return array(
			'valid' => false,
			'error' => $domain . ' (file paths not allowed for preconnect - use domain only)',
		);
	}

	if ( isset( $parsed_url['query'] ) || isset( $parsed_url['fragment'] ) ) {
		return array(
			'valid' => false,
			'error' => $domain . ' (query parameters and fragments not allowed for preconnect)',
		);
	}

	$host = $parsed_url['host'];

	// Security: Block localhost and known local addresses.
	$is_local = in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );
	if ( $is_local ) {
		return array(
			'valid' => false,
			'error' => $domain . ' (private/local address not allowed)',
		);
	}

	// Security: If the host is an IP address, ensure it is not in a private or reserved range.
	if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
		if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return array(
				'valid' => false,
				'error' => $domain . ' (private/local address not allowed)',
			);
		}
	}

	// Return clean domain URL with only scheme and host (no paths).
	$clean_domain = $parsed_url['scheme'] . '://' . $parsed_url['host'];

	// Add port if specified and not default HTTPS port.
	if ( isset( $parsed_url['port'] ) && 443 !== $parsed_url['port'] ) {
		$clean_domain .= ':' . $parsed_url['port'];
	}

	// Security: Use esc_url_raw to sanitize URLs before storing in database.
	return array(
		'valid'  => true,
		'domain' => esc_url_raw( $clean_domain ),
	);
}

/**
 * Show admin notice for rejected domains
 *
 * @since 1.4.0
 * @param array $rejected_domains Array of rejected domain strings.
 */
function es_optimizer_show_domain_rejection_notice( $rejected_domains ) {
	// Security: Properly escape and limit the rejected domains in error messages.
	$escaped_domains  = array_map( 'esc_html', array_slice( $rejected_domains, 0, 3 ) );
	$rejected_message = implode( ', ', $escaped_domains );

	if ( count( $rejected_domains ) > 3 ) {
		$rejected_message .= esc_html__( '...', 'enginescript-site-optimizer' );
	}

	$message = sprintf(
		// translators: %s is the list of rejected domain names.
		esc_html__( 'Some preconnect domains were rejected for security reasons: %s', 'enginescript-site-optimizer' ),
		$rejected_message
	);

	add_settings_error(
		'es_optimizer_options',
		'preconnect_security',
		$message,
		'warning'
	);
}

/**
 * Disable WordPress emoji functionality
 *
 * Completely removes emoji-related scripts and styles which most sites don't need.
 * This improves page load time and reduces HTTP requests.
 *
 * @since 1.0.0
 */
function es_optimizer_disable_emojis() {
	$options = es_optimizer_get_options();

	// Only proceed if the option is enabled.
	if ( ! isset( $options['disable_emojis'] ) || ! $options['disable_emojis'] ) {
		return;
	}

	// Remove emoji scripts and styles from front end.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );

	// Remove emoji scripts and styles from admin area.
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );

	// Remove emojis from RSS feeds.
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

	// Remove emojis from emails.
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	// Disable emoji in TinyMCE editor.
	add_filter( 'tiny_mce_plugins', 'es_optimizer_disable_emojis_tinymce' );

	// Remove emoji DNS prefetch.
	add_filter( 'wp_resource_hints', 'es_optimizer_disable_emojis_remove_dns_prefetch', 10, 2 );
}

/**
 * Add settings link to plugins page
 *
 * @since 1.0.0
 * @param array $links Plugin action links.
 * @return array Modified plugin action links.
 */
function es_optimizer_add_settings_link( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=es-optimizer-settings' ) ) . '">' . esc_html__( 'Settings', 'enginescript-site-optimizer' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

/**
 * Filter function used to remove the tinymce emoji plugin.
 *
 * @since 1.0.0
 * @param array $plugins Array of TinyMCE plugins.
 * @return array Difference betwen the two arrays.
 */
function es_optimizer_disable_emojis_tinymce( $plugins ) {
	if ( ! is_array( $plugins ) ) {
		$plugins = array();
	}
	return array_diff( $plugins, array( 'wpemoji' ) );
}

/**
 * Remove emoji CDN hostname from DNS prefetching hints.
 *
 * @since 1.0.0
 * @param array  $urls URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed for.
 * @return array Difference betwen the two arrays.
 */
function es_optimizer_disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
		$urls          = array_diff( $urls, array( $emoji_svg_url ) );
	}
	return $urls;
}

/**
 * Remove JQuery Migrate
 *
 * JQuery Migrate is primarily used for backward compatibility with older jQuery code.
 * Modern themes and plugins generally don't need it, so removing it improves load time.
 *
 * @since 1.0.0
 * @param WP_Scripts $scripts WP_Scripts object.
 */
function es_optimizer_remove_jquery_migrate( $scripts ) {
	$options = es_optimizer_get_options();

	// Only proceed if the option is enabled.
	if ( ! isset( $options['remove_jquery_migrate'] ) || ! $options['remove_jquery_migrate'] ) {
		return;
	}

	if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
		$script = $scripts->registered['jquery'];

		// Remove jquery-migrate from jquery dependencies.
		if ( $script->deps ) {
			$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
		}
	}
}

/**
 * Disable classic-themes css added in WP 6.1
 *
 * @since 1.3.0
 */
function es_optimizer_disable_classic_theme_styles() {
	$options = es_optimizer_get_options();

	// Only proceed if the option is enabled.
	if ( ! isset( $options['disable_classic_theme_styles'] ) || ! $options['disable_classic_theme_styles'] ) {
		return;
	}

	wp_deregister_style( 'classic-theme-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}

/**
 * Remove WordPress version, WLW manifest, and shortlink.
 *
 * @since 1.0.0
 */
function es_optimizer_remove_header_items() {
	$options = es_optimizer_get_options();

	// Remove WordPress Version from Header.
	if ( isset( $options['remove_wp_version'] ) && $options['remove_wp_version'] ) {
		remove_action( 'wp_head', 'wp_generator' );
	}

	// Remove RSD Link.
	if ( isset( $options['remove_rsd_link'] ) && $options['remove_rsd_link'] ) {
		remove_action( 'wp_head', 'rsd_link' );
	}

	// Remove Windows Live Writer Manifest.
	if ( isset( $options['remove_wlw_manifest'] ) && $options['remove_wlw_manifest'] ) {
		remove_action( 'wp_head', 'wlwmanifest_link' );
	}

	// Remove WP Shortlink URLs.
	if ( isset( $options['remove_shortlink'] ) && $options['remove_shortlink'] ) {
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	}
}

/**
 * Remove Recent Comments Widget CSS Styles.
 *
 * @since 1.0.0
 */
function es_optimizer_remove_recent_comments_style() {
	$options = es_optimizer_get_options();

	// Only proceed if the option is enabled.
	if ( isset( $options['remove_recent_comments_style'] ) && $options['remove_recent_comments_style'] ) {
		add_filter( 'show_recent_comments_widget_style', '__return_false', PHP_INT_MAX );
	}
}

/**
 * Add preconnect hints for common external domains.
 *
 * Preconnect establishes early connections (DNS + TCP + TLS handshake) to third-party domains.
 * This reduces latency when loading resources from external origins and improves LCP/FCP metrics.
 * More effective than dns-prefetch as it completes the full connection setup.
 *
 * Security note: All output is properly escaped with esc_url() before output to prevent XSS.
 *
 * @since 1.4.1
 */
function es_optimizer_add_preconnect() {
	// Only add if not admin and not doing AJAX.
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	// Use static caching to avoid repeated option retrieval.
	static $domains_cache   = null;
	static $options_checked = false;

	if ( ! $options_checked ) {
		$options         = es_optimizer_get_options();
		$options_checked = true;

		// Only proceed if the option is enabled.
		if ( ! isset( $options['enable_preconnect'] ) || ! $options['enable_preconnect'] ) {
			$domains_cache = array(); // Cache empty array to avoid re-checking.
			return;
		}

		// Get and process domains from settings.
		if ( isset( $options['preconnect_domains'] ) && ! empty( $options['preconnect_domains'] ) ) {
			// Process domains with optimization.
			$domains = explode( "\n", $options['preconnect_domains'] );
			$domains = array_map( 'trim', $domains );
			$domains = array_filter( $domains );

			// Remove duplicates and validate domains.
			$domains       = array_unique( $domains );
			$valid_domains = array();

			foreach ( $domains as $domain ) {
				// Validate URL format and ensure HTTPS.
				if ( filter_var( $domain, FILTER_VALIDATE_URL ) && strpos( $domain, 'https://' ) === 0 ) {
					$valid_domains[] = $domain;
				}
			}

			$domains_cache = $valid_domains;
		} else {
			$domains_cache = array();
		}
	}

	// Output the preconnect links.
	if ( ! empty( $domains_cache ) ) {
		foreach ( $domains_cache as $domain ) {
			// Add crossorigin attribute for font domains (required for CORS requests).
			$crossorigin = ( strpos( $domain, 'fonts.g' ) !== false || strpos( $domain, 'gstatic' ) !== false ) ? ' crossorigin' : '';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<link rel="preconnect" href="' . esc_url( $domain ) . '"' . $crossorigin . '>' . "\n";
		}
	}
}

/**
 * Add DNS prefetch hints for external domains.
 *
 * DNS-prefetch performs only the DNS lookup for third-party domains.
 * This is a lighter-weight alternative to preconnect for less critical resources.
 * Use for domains that may not be used immediately or as a fallback.
 *
 * Security note: All output is properly escaped with esc_url() before output to prevent XSS.
 *
 * @since 1.8.0
 */
function es_optimizer_add_dns_prefetch() {
	// Only add if not admin and not doing AJAX.
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	// Use static caching to avoid repeated option retrieval.
	static $domains_cache   = null;
	static $options_checked = false;

	if ( ! $options_checked ) {
		$options         = es_optimizer_get_options();
		$options_checked = true;

		// Only proceed if the option is enabled.
		if ( ! isset( $options['enable_dns_prefetch'] ) || ! $options['enable_dns_prefetch'] ) {
			$domains_cache = array(); // Cache empty array to avoid re-checking.
			return;
		}

		// Get and process domains from settings.
		if ( isset( $options['dns_prefetch_domains'] ) && ! empty( $options['dns_prefetch_domains'] ) ) {
			// Process domains with optimization.
			$domains = explode( "\n", $options['dns_prefetch_domains'] );
			$domains = array_map( 'trim', $domains );
			$domains = array_filter( $domains );

			// Remove duplicates and validate domains.
			$domains       = array_unique( $domains );
			$valid_domains = array();

			foreach ( $domains as $domain ) {
				// Validate URL format and ensure HTTPS.
				if ( filter_var( $domain, FILTER_VALIDATE_URL ) && strpos( $domain, 'https://' ) === 0 ) {
					$valid_domains[] = $domain;
				}
			}

			$domains_cache = $valid_domains;
		} else {
			$domains_cache = array();
		}
	}

	// Output the DNS prefetch links.
	if ( ! empty( $domains_cache ) ) {
		foreach ( $domains_cache as $domain ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<link rel="dns-prefetch" href="' . esc_url( $domain ) . '">' . "\n";
		}
	}
}

/**
 * Disable Jetpack advertisements.
 *
 * @since 1.0.0
 */
function es_optimizer_disable_jetpack_ads() {
	$options = es_optimizer_get_options();

	// Only proceed if the option is enabled.
	if ( isset( $options['disable_jetpack_ads'] ) && $options['disable_jetpack_ads'] ) {
		add_filter( 'jetpack_just_in_time_msgs', '__return_false', PHP_INT_MAX );
		add_filter( 'jetpack_show_promotions', '__return_false', PHP_INT_MAX );
		add_filter( 'jetpack_blaze_enabled', '__return_false', PHP_INT_MAX );
	}
}

/**
 * Disable WordPress post via email functionality.
 *
 * @since 1.0.0
 */
function es_optimizer_disable_post_via_email() {
	$options = es_optimizer_get_options();

	// Only proceed if the option is enabled.
	if ( isset( $options['disable_post_via_email'] ) && $options['disable_post_via_email'] ) {
		add_filter( 'enable_post_by_email_configuration', '__return_false', PHP_INT_MAX );
	}
}