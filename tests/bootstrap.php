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
	 * @param string                                           $hook          Hook name.
	 * @param callable|string|array{0: object|string, 1: string} $callback      Callback.
	 * @param int                                              $priority      Hook priority.
	 * @param int                                              $accepted_args Accepted args.
	 * @return bool True.
	 */
	function add_action( string $hook, callable|string|array $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		return add_filter( $hook, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Register a filter callback.
	 *
	 * @param string                                           $hook          Hook name.
	 * @param callable|string|array{0: object|string, 1: string} $callback      Callback.
	 * @param int                                              $priority      Hook priority.
	 * @param int                                              $accepted_args Accepted args.
	 * @return bool True.
	 */
	function add_filter( string $hook, callable|string|array $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		unset( $hook, $callback, $priority, $accepted_args );
		return true;
	}
}

if ( ! function_exists( 'remove_action' ) ) {
	/**
	 * Remove an action callback.
	 *
	 * @param string                                           $hook     Hook name.
	 * @param callable|string|array{0: object|string, 1: string} $callback Callback.
	 * @param int                                              $priority Hook priority.
	 * @return bool True.
	 */
	function remove_action( string $hook, callable|string|array $callback, int $priority = 10 ): bool {
		return remove_filter( $hook, $callback, $priority );
	}
}

if ( ! function_exists( 'remove_filter' ) ) {
	/**
	 * Remove a filter callback.
	 *
	 * @param string                                           $hook     Hook name.
	 * @param callable|string|array{0: object|string, 1: string} $callback Callback.
	 * @param int                                              $priority Hook priority.
	 * @return bool True.
	 */
	function remove_filter( string $hook, callable|string|array $callback, int $priority = 10 ): bool {
		unset( $hook, $callback, $priority );
		return true;
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	/**
	 * Register activation hook.
	 *
	 * @param string                                           $file     Plugin file.
	 * @param callable|string|array{0: object|string, 1: string} $callback Callback.
	 */
	function register_activation_hook( string $file, callable|string|array $callback ): void {
		global $es_optimizer_test_activation_hooks;

		if ( ! is_array( $es_optimizer_test_activation_hooks ) ) {
			$es_optimizer_test_activation_hooks = array();
		}

		if ( ! isset( $es_optimizer_test_activation_hooks[ $file ] ) || ! is_array( $es_optimizer_test_activation_hooks[ $file ] ) ) {
			$es_optimizer_test_activation_hooks[ $file ] = array();
		}

		$es_optimizer_test_activation_hooks[ $file ][] = $callback;
	}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	/**
	 * Register deactivation hook.
	 *
	 * @param string                                           $file     Plugin file.
	 * @param callable|string|array{0: object|string, 1: string} $callback Callback.
	 */
	function register_deactivation_hook( string $file, callable|string|array $callback ): void {
		global $es_optimizer_test_deactivation_hooks;

		if ( ! is_array( $es_optimizer_test_deactivation_hooks ) ) {
			$es_optimizer_test_deactivation_hooks = array();
		}

		if ( ! isset( $es_optimizer_test_deactivation_hooks[ $file ] ) || ! is_array( $es_optimizer_test_deactivation_hooks[ $file ] ) ) {
			$es_optimizer_test_deactivation_hooks[ $file ] = array();
		}

		$es_optimizer_test_deactivation_hooks[ $file ][] = $callback;
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

		if ( ! is_array( $es_optimizer_test_options ) ) {
			return $default;
		}

		return array_key_exists( $option, $es_optimizer_test_options ) ? $es_optimizer_test_options[ $option ] : $default;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	/**
	 * Add an option to the test option store.
	 *
	 * @param string           $option     Option name.
	 * @param mixed            $value      Option value.
	 * @param string           $deprecated Deprecated parameter, not used.
	 * @param bool|string|null $autoload   Autoload setting.
	 * @return bool True.
	 */
	function add_option( string $option, mixed $value = '', string $deprecated = '', bool|string|null $autoload = null ): bool {
		global $es_optimizer_test_options;

		unset( $deprecated, $autoload );

		if ( ! is_array( $es_optimizer_test_options ) ) {
			$es_optimizer_test_options = array();
		}

		if ( array_key_exists( $option, $es_optimizer_test_options ) ) {
			return false;
		}

		$es_optimizer_test_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Update an option in the test option store.
	 *
	 * @param string           $option   Option name.
	 * @param mixed            $value    Option value.
	 * @param bool|string|null $autoload Autoload setting.
	 * @return bool True.
	 */
	function update_option( string $option, mixed $value, bool|string|null $autoload = null ): bool {
		global $es_optimizer_test_options;

		unset( $autoload );

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

		if ( ! is_array( $es_optimizer_test_options ) || ! array_key_exists( $option, $es_optimizer_test_options ) ) {
			return false;
		}

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
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}

		if ( is_object( $value ) ) {
			foreach ( get_object_vars( $value ) as $key => $property_value ) {
				$value->$key = wp_unslash( $property_value );
			}

			return $value;
		}

		return is_string( $value ) ? stripslashes( $value ) : $value;
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
	 * @param string $value         Value to strip.
	 * @param bool   $remove_breaks Whether to remove line breaks and whitespace.
	 * @return string Stripped value.
	 */
	function wp_strip_all_tags( string $value, bool $remove_breaks = false ): string {
		$value = strip_tags( $value );

		if ( $remove_breaks ) {
			$normalized_value = preg_replace( '/[\r\n\t ]+/', ' ', $value );

			if ( null === $normalized_value ) {
				throw new RuntimeException( 'Failed to normalize stripped tag whitespace: ' . preg_last_error_msg() );
			}

			$value = $normalized_value;
		}

		return trim( $value );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Sanitize a URL for tests.
	 *
	 * @param string                  $url       URL.
	 * @param array<int, string>|null $protocols Allowed protocols.
	 * @return string Sanitized URL.
	 */
	function esc_url_raw( string $url, ?array $protocols = null ): string {
		$url               = trim( $url );
		$scheme            = parse_url( $url, PHP_URL_SCHEME );
		$allowed_protocols = $protocols ?? array();

		if ( false === $scheme ) {
			return '';
		}

		if ( ! empty( $allowed_protocols ) && is_string( $scheme ) && ! in_array( $scheme, $allowed_protocols, true ) ) {
			return '';
		}

		return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
	}
}

if ( ! function_exists( 'sanitize_url' ) ) {
	/**
	 * Sanitize a URL for tests.
	 *
	 * @param string                  $url       URL.
	 * @param array<int, string>|null $protocols Allowed protocols.
	 * @return string Sanitized URL.
	 */
	function sanitize_url( string $url, ?array $protocols = null ): string {
		return esc_url_raw( $url, $protocols );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * Parse a URL.
	 *
	 * @param string   $url       URL.
	 * @param int      $component URL component.
	 * @return array<string, mixed>|string|int|false|null Parsed URL.
	 */
	function wp_parse_url( string $url, int $component = -1 ): mixed {
		$to_unset = array();

		if ( str_starts_with( $url, '//' ) ) {
			$to_unset[] = 'scheme';
			$url        = 'placeholder:' . $url;
		} elseif ( str_starts_with( $url, '/' ) ) {
			$to_unset[] = 'scheme';
			$to_unset[] = 'host';
			$url        = 'placeholder://placeholder' . $url;
		}

		$parts = parse_url( $url );
		if ( false === $parts ) {
			return false;
		}

		foreach ( $to_unset as $key ) {
			unset( $parts[ $key ] );
		}

		if ( -1 === $component ) {
			return $parts;
		}

		$translation = array(
			PHP_URL_SCHEME   => 'scheme',
			PHP_URL_HOST     => 'host',
			PHP_URL_PORT     => 'port',
			PHP_URL_USER     => 'user',
			PHP_URL_PASS     => 'pass',
			PHP_URL_PATH     => 'path',
			PHP_URL_QUERY    => 'query',
			PHP_URL_FRAGMENT => 'fragment',
		);
		$key         = $translation[ $component ] ?? false;

		return false !== $key && isset( $parts[ $key ] ) ? $parts[ $key ] : null;
	}
}

if ( ! function_exists( 'rest_is_ip_address' ) ) {
	/**
	 * Check whether a value is an IP address.
	 *
	 * @param string $ip IP address.
	 * @return string|false IP address when valid; false otherwise.
	 */
	function rest_is_ip_address( string $ip ): string|false {
		$validated_ip = filter_var( $ip, FILTER_VALIDATE_IP );

		return is_string( $validated_ip ) ? $validated_ip : false;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Return translated text.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 * @return string Text.
	 */
	function __( string $text, string $domain = 'default' ): string {
		unset( $domain );
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
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 * @return string Escaped text.
	 */
	function esc_html__( string $text, string $domain = 'default' ): string {
		unset( $domain );
		return esc_html( $text );
	}
}

if ( ! function_exists( 'add_settings_error' ) ) {
	/**
	 * Register a settings error.
	 *
	 * @param string $setting Setting slug.
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param string $type    Error type.
	 */
	function add_settings_error( string $setting, string $code, string $message, string $type = 'error' ): void {
		global $wp_settings_errors;

		if ( ! is_array( $wp_settings_errors ) ) {
			$wp_settings_errors = array();
		}

		$wp_settings_errors[] = array(
			'setting' => $setting,
			'code'    => $code,
			'message' => $message,
			'type'    => $type,
		);
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
	 * @param mixed  ...$args Additional filter arguments.
	 * @return mixed Filtered value.
	 */
	function apply_filters( string $hook, mixed $value, mixed ...$args ): mixed {
		unset( $hook, $args );
		return $value;
	}
}

if ( ! function_exists( '__return_null' ) ) {
	/**
	 * Return null.
	 *
	 * @return null
	 */
	function __return_null(): null {
		return null;
	}
}

require dirname( __DIR__ ) . '/enginescript-site-optimizer.php';
