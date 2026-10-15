<?php
/**
 * WordPress doubles for the isolated unit suite.
 *
 * These doubles do not establish native WordPress hook, storage or HTTP behavior.
 *
 * @package EngineScript_Site_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/wordpress/' );
}

/**
 * Start from a clean set of recorded calls and request flags.
 *
 * The stand-ins below record what the plugin registers, removes, and prints in
 * $GLOBALS['es_optimizer_test']. Stored options keep their own global, which the
 * option stand-ins and the older tests share.
 *
 * @return void
 */
function es_optimizer_test_reset(): void {
	$GLOBALS['es_optimizer_test'] = array(
		'hooks'        => array(),
		'removed'      => array(),
		'pages'        => array(),
		'settings'     => array(),
		'sections'     => array(),
		'fields'       => array(),
		'styles'       => array(),
		'capabilities' => array( 'manage_options' ),
		'is_admin'     => false,
		'ajax'         => false,
		'feed'         => false,
		'doing'        => null,
		'translations' => array(),
		'domains'      => array(),
	);
}

/**
 * Get the priority at which a callback is registered on a hook.
 *
 * @param string $hook     Hook name.
 * @param mixed  $callback Callback.
 * @return int|false Priority, or false when the callback is not registered.
 */
function es_optimizer_test_hook_priority( string $hook, mixed $callback ): int|false {
	foreach ( $GLOBALS['es_optimizer_test']['hooks'][ $hook ] ?? array() as $priority => $registered ) {
		foreach ( $registered as $entry ) {
			if ( $entry['callback'] === $callback ) {
				return $priority;
			}
		}
	}

	return false;
}

/**
 * Return a translation that a test has set for a source string.
 *
 * @param string $text Source string.
 * @return string Translated string, or the source string.
 */
function es_optimizer_test_translate( string $text, string $domain = 'default' ): string {
	$GLOBALS['es_optimizer_test']['domains'][ $text ] = $domain;

	return $GLOBALS['es_optimizer_test']['translations'][ $text ] ?? $text;
}

/**
 * The wp_die() stand-in throws this, so that a test can see the call.
 */
class Es_Optimizer_Test_Die extends RuntimeException {
}

es_optimizer_test_reset();

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
		$GLOBALS['es_optimizer_test']['hooks'][ $hook ][ $priority ][] = array(
			'callback' => $callback,
			'args'     => $accepted_args,
		);
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
		$GLOBALS['es_optimizer_test']['removed'][] = array( $hook, $callback, $priority );
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
		return es_optimizer_test_translate( $text, $domain );
	}
}

if ( ! function_exists( '_n' ) ) {
	/**
	 * Select an English plural for isolated assertions, without loading a locale.
	 *
	 * @since 2.2.0
	 * @param string $single Singular message.
	 * @param string $plural Plural message.
	 * @param int    $number Number.
	 * @param string $domain Text domain.
	 * @return string Selected English message.
	 */
	function _n( string $single, string $plural, int $number, string $domain = 'default' ): string {
		return es_optimizer_test_translate( 1 === $number ? $single : $plural, $domain );
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
		return esc_html( es_optimizer_test_translate( $text, $domain ) );
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
	 * @return bool What the test set; false by default.
	 */
	function is_admin(): bool {
		return (bool) $GLOBALS['es_optimizer_test']['is_admin'];
	}
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	/**
	 * Check whether current request is AJAX.
	 *
	 * @return bool What the test set; false by default.
	 */
	function wp_doing_ajax(): bool {
		return (bool) $GLOBALS['es_optimizer_test']['ajax'];
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

if ( ! function_exists( 'doing_action' ) ) {
	/**
	 * Check whether an action is currently running.
	 *
	 * @param string|null $hook_name Action name.
	 * @return bool Whether the test set this action as running; no action runs by default.
	 */
	function doing_action( ?string $hook_name = null ): bool {
		$doing = $GLOBALS['es_optimizer_test']['doing'];

		return null === $hook_name ? null !== $doing : $hook_name === $doing;
	}
}

if ( ! function_exists( 'wp_validate_boolean' ) ) {
	/**
	 * Read a value as a boolean the way WordPress does.
	 *
	 * @param mixed $value Value to read.
	 * @return bool False for false and for the string "false" in any letter case.
	 */
	function wp_validate_boolean( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) && 'false' === strtolower( $value ) ) {
			return false;
		}

		return (bool) $value;
	}
}

if ( ! function_exists( '__return_null' ) ) {
	/**
	 * Return null.
	 *
	 * The native null return type is intentional; this project requires PHP 8.2+.
	 *
	 * @return null
	 */
	function __return_null(): null {
		return null;
	}
}

if ( ! function_exists( 'is_feed' ) ) {
	/**
	 * Check whether the request is for a feed.
	 *
	 * @return bool What the test set; false by default.
	 */
	function is_feed(): bool {
		return (bool) $GLOBALS['es_optimizer_test']['feed'];
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Check a capability of the current user.
	 *
	 * @param string $capability Capability.
	 * @return bool Whether the test granted it; manage_options by default.
	 */
	function current_user_can( string $capability ): bool {
		return in_array( $capability, $GLOBALS['es_optimizer_test']['capabilities'], true );
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	/**
	 * Stop the request.
	 *
	 * @param string $message Message.
	 * @return never
	 * @throws Es_Optimizer_Test_Die Always, with the message.
	 */
	function wp_die( string $message = '' ): never {
		throw new Es_Optimizer_Test_Die( $message );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Escape an attribute value.
	 *
	 * @param string $text Text.
	 * @return string Escaped text.
	 */
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	/**
	 * Escape text area content.
	 *
	 * @param string $text Text.
	 * @return string Escaped text.
	 */
	function esc_textarea( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Escape a URL for output.
	 *
	 * @param string $url URL.
	 * @return string Escaped URL, or an empty string for a scheme other than HTTP or HTTPS.
	 */
	function esc_url( string $url ): string {
		return preg_match( '#\Ahttps?://#', $url ) ? htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' ) : '';
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	/**
	 * Print escaped translated text.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 * @return void
	 */
	function esc_html_e( string $text, string $domain = 'default' ): void {
		echo esc_html__( $text, $domain );
	}
}

if ( ! function_exists( 'checked' ) ) {
	/**
	 * Print the checked attribute when two values are equal as strings.
	 *
	 * @param mixed $checked Value to compare.
	 * @param mixed $current Current value.
	 * @return string The attribute, or an empty string.
	 */
	function checked( mixed $checked, mixed $current = true ): string {
		$attribute = (string) $checked === (string) $current ? " checked='checked'" : '';
		echo $attribute; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- A fixed attribute.
		return $attribute;
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	/**
	 * Build an admin URL.
	 *
	 * @param string $path Path relative to the admin URL.
	 * @return string URL.
	 */
	function admin_url( string $path = '' ): string {
		return 'https://site.example/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'add_options_page' ) ) {
	/**
	 * Record a page under Settings.
	 *
	 * @param string $page_title Page title.
	 * @param string $menu_title Menu title.
	 * @param string $capability Capability.
	 * @param string $menu_slug  Menu slug.
	 * @param mixed  $callback   Page callback.
	 * @return string Page hook suffix.
	 */
	function add_options_page( string $page_title, string $menu_title, string $capability, string $menu_slug, mixed $callback = '' ): string {
		$GLOBALS['es_optimizer_test']['pages'][] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug', 'callback' );
		return 'settings_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'register_setting' ) ) {
	/**
	 * Record a registered setting.
	 *
	 * @param string               $option_group Option group.
	 * @param string               $option_name  Option name.
	 * @param array<string, mixed> $args         Arguments.
	 * @return void
	 */
	function register_setting( string $option_group, string $option_name, array $args = array() ): void {
		$GLOBALS['es_optimizer_test']['settings'][] = compact( 'option_group', 'option_name', 'args' );
	}
}

if ( ! function_exists( 'add_settings_section' ) ) {
	/**
	 * Record a settings section.
	 *
	 * @param string $id       Section ID.
	 * @param string $title    Title.
	 * @param mixed  $callback Callback.
	 * @param string $page     Page slug.
	 * @return void
	 */
	function add_settings_section( string $id, string $title, mixed $callback, string $page ): void {
		$GLOBALS['es_optimizer_test']['sections'][] = compact( 'id', 'title', 'callback', 'page' );
	}
}

if ( ! function_exists( 'add_settings_field' ) ) {
	/**
	 * Record a settings field.
	 *
	 * @param string               $id       Field ID.
	 * @param string               $title    Title.
	 * @param mixed                $callback Render callback.
	 * @param string               $page     Page slug.
	 * @param string               $section  Section ID.
	 * @param array<string, mixed> $args     Arguments.
	 * @return void
	 */
	function add_settings_field( string $id, string $title, mixed $callback, string $page, string $section = 'default', array $args = array() ): void {
		$GLOBALS['es_optimizer_test']['fields'][] = compact( 'id', 'title', 'callback', 'page', 'section', 'args' );
	}
}

if ( ! function_exists( 'settings_fields' ) ) {
	/**
	 * Print a marker for the hidden fields of an option group.
	 *
	 * @param string $option_group Option group.
	 * @return void
	 */
	function settings_fields( string $option_group ): void {
		echo '<input type="hidden" name="option_page" value="' . esc_attr( $option_group ) . '" />';
	}
}

if ( ! function_exists( 'do_settings_sections' ) ) {
	/**
	 * Print the recorded sections of a page and call each field's render callback.
	 *
	 * @param string $page Page slug.
	 * @return void
	 */
	function do_settings_sections( string $page ): void {
		foreach ( $GLOBALS['es_optimizer_test']['sections'] as $section ) {
			if ( $section['page'] !== $page ) {
				continue;
			}

			echo '<h2>' . $section['title'] . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The plugin escapes the title when it registers it.

			foreach ( $GLOBALS['es_optimizer_test']['fields'] as $field ) {
				if ( $field['page'] === $page && $field['section'] === $section['id'] ) {
					call_user_func( $field['callback'], $field['args'] );
				}
			}
		}
	}
}

if ( ! function_exists( 'submit_button' ) ) {
	/**
	 * Print a submit button.
	 *
	 * @param string $text Button text.
	 * @return void
	 */
	function submit_button( string $text = 'Save Changes' ): void {
		echo '<input type="submit" name="submit" id="submit" value="' . esc_attr( $text ) . '" />';
	}
}

if ( ! function_exists( 'wp_dequeue_style' ) ) {
	/**
	 * Record a dequeued style.
	 *
	 * @param string $handle Style handle.
	 * @return void
	 */
	function wp_dequeue_style( string $handle ): void {
		$GLOBALS['es_optimizer_test']['styles'][] = array( 'dequeue', $handle );
	}
}

if ( ! function_exists( 'wp_deregister_style' ) ) {
	/**
	 * Record a deregistered style.
	 *
	 * @param string $handle Style handle.
	 * @return void
	 */
	function wp_deregister_style( string $handle ): void {
		$GLOBALS['es_optimizer_test']['styles'][] = array( 'deregister', $handle );
	}
}

require dirname( __DIR__ ) . '/enginescript-site-optimizer.php';

// What loading the plugin file registered, before any test changes the record.
$GLOBALS['es_optimizer_test_load_time'] = $GLOBALS['es_optimizer_test'];

require __DIR__ . '/EsOptimizerTestCase.php';
