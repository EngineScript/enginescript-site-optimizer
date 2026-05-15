<?php
/**
 * PHPUnit bootstrap for local test runs.
 *
 * @package EngineScript_Site_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/wordpress/' );
}

if ( ! class_exists( 'WP_Scripts' ) ) {
	/**
	 * Minimal WP_Scripts test double.
	 */
	class WP_Scripts {
		/**
		 * Registered scripts.
		 *
		 * @var array<string, object>
		 */
		public array $registered = array();
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	/**
	 * Get the filesystem path for a plugin file.
	 *
	 * @param string $file Plugin file.
	 * @return string Directory path.
	 */
	function plugin_dir_path( string $file ): string {
		return rtrim( dirname( $file ), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	/**
	 * Get a plugin basename for tests.
	 *
	 * @param string $file Plugin file.
	 * @return string Basename.
	 */
	function plugin_basename( string $file ): string {
		return basename( $file );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Register an action callback.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted args.
	 * @return bool True.
	 */
	function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		return add_filter( $hook, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Register a filter callback.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted args.
	 * @return bool True.
	 */
	function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		unset( $hook, $callback, $priority, $accepted_args );
		return true;
	}
}

if ( ! function_exists( 'remove_action' ) ) {
	/**
	 * Remove an action callback.
	 *
	 * @return bool True.
	 */
	function remove_action(): bool {
		return true;
	}
}

if ( ! function_exists( 'remove_filter' ) ) {
	/**
	 * Remove a filter callback.
	 *
	 * @return bool True.
	 */
	function remove_filter(): bool {
		return true;
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	/**
	 * Register activation hook.
	 *
	 * @param string   $file     Plugin file.
	 * @param callable $callback Callback.
	 */
	function register_activation_hook( string $file, callable $callback ): void {
		unset( $file, $callback );
	}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	/**
	 * Register deactivation hook.
	 *
	 * @param string   $file     Plugin file.
	 * @param callable $callback Callback.
	 */
	function register_deactivation_hook( string $file, callable $callback ): void {
		unset( $file, $callback );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Get an option from the test option store.
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Default value.
	 * @return mixed Option value.
	 */
	function get_option( string $option, mixed $default = false ): mixed {
		global $es_optimizer_test_options;

		return $es_optimizer_test_options[ $option ] ?? $default;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	/**
	 * Add an option to the test option store.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return bool True.
	 */
	function add_option( string $option, mixed $value ): bool {
		return update_option( $option, $value );
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Update an option in the test option store.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return bool True.
	 */
	function update_option( string $option, mixed $value ): bool {
		global $es_optimizer_test_options;

		if ( ! is_array( $es_optimizer_test_options ) ) {
			$es_optimizer_test_options = array();
		}

		$es_optimizer_test_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * Delete an option from the test option store.
	 *
	 * @param string $option Option name.
	 * @return bool True.
	 */
	function delete_option( string $option ): bool {
		global $es_optimizer_test_options;

		unset( $es_optimizer_test_options[ $option ] );
		return true;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Remove slashes from a value.
	 *
	 * @param mixed $value Value to unslash.
	 * @return mixed Unslashed value.
	 */
	function wp_unslash( mixed $value ): mixed {
		return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string) $value );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	/**
	 * Sanitize textarea text for tests.
	 *
	 * @param string $value Value to sanitize.
	 * @return string Sanitized value.
	 */
	function sanitize_textarea_field( string $value ): string {
		return trim( wp_strip_all_tags( $value ) );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Strip all HTML tags.
	 *
	 * @param string $value Value to strip.
	 * @return string Stripped value.
	 */
	function wp_strip_all_tags( string $value ): string {
		return strip_tags( $value );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Sanitize a URL for tests.
	 *
	 * @param string             $url       URL.
	 * @param array<int, string> $protocols Allowed protocols.
	 * @return string Sanitized URL.
	 */
	function esc_url_raw( string $url, array $protocols = array() ): string {
		$url    = trim( $url );
		$scheme = parse_url( $url, PHP_URL_SCHEME );

		if ( ! empty( $protocols ) && ( ! is_string( $scheme ) || ! in_array( $scheme, $protocols, true ) ) ) {
			return '';
		}

		return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
	}
}

if ( ! function_exists( 'sanitize_url' ) ) {
	/**
	 * Sanitize a URL for tests.
	 *
	 * @param string             $url       URL.
	 * @param array<int, string> $protocols Allowed protocols.
	 * @return string Sanitized URL.
	 */
	function sanitize_url( string $url, array $protocols = array() ): string {
		return esc_url_raw( $url, $protocols );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * Parse a URL.
	 *
	 * @param string   $url       URL.
	 * @param int|null $component URL component.
	 * @return mixed Parsed URL.
	 */
	function wp_parse_url( string $url, ?int $component = null ): mixed {
		return null === $component ? parse_url( $url ) : parse_url( $url, $component );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Return translated text.
	 *
	 * @param string $text Text.
	 * @return string Text.
	 */
	function __( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Escape HTML text.
	 *
	 * @param string $text Text.
	 * @return string Escaped text.
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Return escaped translated text.
	 *
	 * @param string $text Text.
	 * @return string Escaped text.
	 */
	function esc_html__( string $text ): string {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'add_settings_error' ) ) {
	/**
	 * Register a settings error.
	 */
	function add_settings_error(): void {
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	/**
	 * Check whether current request is admin.
	 *
	 * @return bool False for tests.
	 */
	function is_admin(): bool {
		return false;
	}
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	/**
	 * Check whether current request is AJAX.
	 *
	 * @return bool False for tests.
	 */
	function wp_doing_ajax(): bool {
		return false;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Apply filters.
	 *
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value.
	 * @return mixed Filtered value.
	 */
	function apply_filters( string $hook, mixed $value ): mixed {
		unset( $hook );
		return $value;
	}
}

if ( ! function_exists( '__return_null' ) ) {
	/**
	 * Return null.
	 *
	 * @return null
	 */
	function __return_null() {
		return null;
	}
}

require dirname( __DIR__ ) . '/enginescript-site-optimizer.php';
