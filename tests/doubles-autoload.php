<?php
/**
 * WordPress doubles for the autoloaded-options code, loaded by the unit bootstrap.
 *
 * They record what the plugin asks for in $GLOBALS['es_optimizer_test'] and
 * answer from values a test sets there. They do not establish native behavior;
 * the generated WordPress suite covers that.
 *
 * @package EngineScript_Site_Optimizer
 */

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

foreach ( array( 'MINUTE_IN_SECONDS' => 60, 'DAY_IN_SECONDS' => 86400, 'WP_PLUGIN_DIR' => '/srv/wp/wp-content/plugins', 'WPMU_PLUGIN_DIR' => '/srv/wp/wp-content/mu-plugins' ) as $es_optimizer_test_constant => $es_optimizer_test_value ) {
	if ( ! defined( $es_optimizer_test_constant ) ) {
		define( $es_optimizer_test_constant, $es_optimizer_test_value );
	}
}
unset( $es_optimizer_test_constant, $es_optimizer_test_value );

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal error object.
	 */
	class WP_Error {
		/**
		 * Error message.
		 *
		 * @var string
		 */
		public $message;

		/**
		 * Build the error.
		 *
		 * @param string $code    Error code, ignored.
		 * @param string $message Message.
		 */
		public function __construct( string $code = '', string $message = '' ) {
			unset( $code );
			$this->message = $message;
		}
	}
}

if ( ! class_exists( 'Es_Optimizer_Test_Theme' ) ) {
	/**
	 * A theme as wp_get_themes() returns it.
	 */
	class Es_Optimizer_Test_Theme {
		/**
		 * Header values.
		 *
		 * @var array<string, string>
		 */
		private $headers;

		/**
		 * Folder.
		 *
		 * @var string
		 */
		private $folder;

		/**
		 * Build the theme.
		 *
		 * @param array<string, string> $headers Header values.
		 * @param string                $folder  Folder.
		 */
		public function __construct( array $headers, string $folder ) {
			$this->headers = $headers;
			$this->folder  = $folder;
		}

		/**
		 * Get a header value.
		 *
		 * @param string $header Header name.
		 * @return string|false Value.
		 */
		public function get( string $header ): string|false {
			return $this->headers[ $header ] ?? false;
		}

		/**
		 * Get the theme's folder.
		 *
		 * @return string Folder.
		 */
		public function get_stylesheet_directory(): string {
			return $this->folder;
		}
	}
}

$es_optimizer_test_doubles = array(
	// Autoloaded options and the request type.
	'wp_load_alloptions'                => static fn ( bool $force = false ): array => $GLOBALS['es_optimizer_test']['alloptions'] ?? array(),
	'wp_doing_cron'                     => static fn (): bool => (bool) ( $GLOBALS['es_optimizer_test']['cron'] ?? false ),
	// Addresses, nonces, and paths.
	'home_url'                          => static fn ( string $path = '' ): string => 'https://site.example' . $path,
	'wp_create_nonce'                   => static fn ( string $action ): string => 'nonce-' . $action,
	'wp_verify_nonce'                   => static fn ( string $nonce, string $action ): int|false => 'nonce-' . $action === $nonce ? 1 : false,
	'wp_normalize_path'                 => static fn ( string $path ): string => str_replace( '\\', '/', $path ),
	// Themes and must-use plugins.
	'get_stylesheet'                    => static fn (): string => $GLOBALS['es_optimizer_test']['stylesheet'] ?? 'child',
	'get_template'                      => static fn (): string => $GLOBALS['es_optimizer_test']['template'] ?? 'parent',
	'wp_get_themes'                     => static fn (): array => $GLOBALS['es_optimizer_test']['themes'] ?? array(),
	'get_mu_plugins'                    => static fn (): array => $GLOBALS['es_optimizer_test']['mu_plugins'] ?? array(),
	'get_theme_root'                    => static fn (): string => '/srv/wp/wp-content/themes',
	// Transients, kept beside the options.
	'get_transient'                     => static fn ( string $name ): mixed => $GLOBALS['es_optimizer_test']['transients'][ $name ] ?? false,
	'set_transient'                     => static function ( string $name, mixed $value, int $expiration = 0 ): bool {
		unset( $expiration );
		$GLOBALS['es_optimizer_test']['transients'][ $name ] = $value;
		return true;
	},
	'delete_transient'                  => static function ( string $name ): bool {
		unset( $GLOBALS['es_optimizer_test']['transients'][ $name ] );
		return true;
	},
	// HTTP: a test sets 'http' to a callable that answers each URL with a status code or a WP_Error.
	'wp_remote_get'                     => static function ( string $url, array $args = array() ): array|WP_Error {
		$GLOBALS['es_optimizer_test']['requests'][] = array( $url, $args );
		$answer                                     = is_callable( $GLOBALS['es_optimizer_test']['http'] ?? null ) ? $GLOBALS['es_optimizer_test']['http']( $url ) : 200;
		return $answer instanceof WP_Error ? $answer : array( 'response' => array( 'code' => $answer ) );
	},
	'is_wp_error'                       => static fn ( mixed $thing ): bool => $thing instanceof WP_Error,
	'wp_remote_retrieve_response_code'  => static fn ( mixed $response ): int|string => is_array( $response ) ? $response['response']['code'] : '',
);

foreach ( $es_optimizer_test_doubles as $es_optimizer_test_name => $es_optimizer_test_double ) {
	if ( ! function_exists( $es_optimizer_test_name ) ) {
		$GLOBALS['es_optimizer_test_double_functions'][ $es_optimizer_test_name ] = $es_optimizer_test_double;
		eval( "function {$es_optimizer_test_name}( ...\$args ) { return \$GLOBALS['es_optimizer_test_double_functions']['{$es_optimizer_test_name}']( ...\$args ); }" ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- Defines plain test doubles from the table above.
	}
}
unset( $es_optimizer_test_doubles, $es_optimizer_test_name, $es_optimizer_test_double );

if ( ! function_exists( 'wp_autoload_values_to_autoload' ) ) {
	/**
	 * The flags WordPress loads on every request.
	 *
	 * @return array<int, string> Flags.
	 */
	function wp_autoload_values_to_autoload(): array {
		return array( 'yes', 'on', 'auto-on', 'auto' );
	}
}

if ( ! function_exists( 'wp_set_option_autoload_values' ) ) {
	/**
	 * Record a change of autoload flags, and answer from 'autoload_results' (true by default).
	 *
	 * @param array<string, mixed> $options Flags by option name.
	 * @return array<string, bool> Whether each option was changed.
	 */
	function wp_set_option_autoload_values( array $options ): array {
		$GLOBALS['es_optimizer_test']['autoload_set'][] = $options;
		$results                                        = array();

		foreach ( array_keys( $options ) as $name ) {
			$results[ $name ] = $GLOBALS['es_optimizer_test']['autoload_results'][ $name ] ?? true;
		}

		return $results;
	}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
	/**
	 * Record a cache deletion.
	 *
	 * @param string $key   Key.
	 * @param string $group Group.
	 * @return bool True.
	 */
	function wp_cache_delete( string $key, string $group = '' ): bool {
		$GLOBALS['es_optimizer_test']['cache_deleted'][] = "{$group}:{$key}";

		return true;
	}
}

if ( ! function_exists( 'get_site_option' ) ) {
	/**
	 * Read a network option.
	 *
	 * @param string $name    Name.
	 * @param mixed  $default Default.
	 * @return mixed Value.
	 */
	function get_site_option( string $name, mixed $default = false ): mixed {
		return $GLOBALS['es_optimizer_test']['site_options'][ $name ] ?? $default;
	}
}

if ( ! function_exists( 'update_site_option' ) ) {
	/**
	 * Store a network option.
	 *
	 * @param string $name  Name.
	 * @param mixed  $value Value.
	 * @return bool True.
	 */
	function update_site_option( string $name, mixed $value ): bool {
		$GLOBALS['es_optimizer_test']['site_options'][ $name ] = $value;

		return true;
	}
}

if ( ! function_exists( 'delete_site_option' ) ) {
	/**
	 * Delete a network option.
	 *
	 * @param string $name Name.
	 * @return bool True.
	 */
	function delete_site_option( string $name ): bool {
		unset( $GLOBALS['es_optimizer_test']['site_options'][ $name ] );
		$GLOBALS['es_optimizer_test']['site_options_deleted'][] = $name;

		return true;
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * Append query arguments to a URL.
	 *
	 * @param array<string, int|string> $args Arguments.
	 * @param string                    $url  URL.
	 * @return string URL.
	 */
	function add_query_arg( array|string $args, string $url, string $third = '' ): string {
		// WordPress also takes a key, a value, and the URL.
		if ( is_string( $args ) ) {
			$args = array( $args => $url );
			$url  = $third;
		}

		return $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . http_build_query( $args );
	}
}

if ( ! function_exists( 'size_format' ) ) {
	/**
	 * Format a byte count simply.
	 *
	 * @param int $bytes    Bytes.
	 * @param int $decimals Decimals, ignored.
	 * @return string|false Text, or false for zero as WordPress returns.
	 */
	function size_format( int $bytes, int $decimals = 0 ): string|false {
		unset( $decimals );

		return 0 === $bytes ? false : $bytes . ' B';
	}
}

if ( ! function_exists( 'wp_using_ext_object_cache' ) ) {
	/**
	 * Whether a persistent object cache is in use.
	 *
	 * @return bool Flag from the test.
	 */
	function wp_using_ext_object_cache(): bool {
		return (bool) ( $GLOBALS['es_optimizer_test']['object_cache'] ?? false );
	}
}

if ( ! function_exists( 'get_plugins' ) ) {
	/**
	 * Installed plugins from the test.
	 *
	 * @return array<string, array<string, string>> Plugin data by file.
	 */
	function get_plugins(): array {
		return $GLOBALS['es_optimizer_test']['plugins'] ?? array();
	}
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	/**
	 * Whether a plugin file is active in the test.
	 *
	 * @param string $file Plugin file.
	 * @return bool Whether it is listed as active.
	 */
	function is_plugin_active( string $file ): bool {
		return in_array( $file, $GLOBALS['es_optimizer_test']['active_plugins'] ?? array(), true );
	}
}

if ( ! function_exists( 'is_network_admin' ) ) {
	/**
	 * Whether the network admin is being shown.
	 *
	 * @return bool Flag from the test.
	 */
	function is_network_admin(): bool {
		return (bool) ( $GLOBALS['es_optimizer_test']['network_admin'] ?? false );
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	/**
	 * Format a timestamp in UTC.
	 *
	 * @param string $format    Format.
	 * @param int    $timestamp Timestamp.
	 * @return string Date.
	 */
	function wp_date( string $format, int $timestamp ): string {
		return gmdate( $format, $timestamp );
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	/**
	 * Print a nonce field for an action.
	 *
	 * @param string $action Action.
	 * @return void
	 */
	function wp_nonce_field( string $action ): void {
		echo '<input type="hidden" name="_wpnonce" value="nonce-' . esc_attr( $action ) . '" />';
	}
}

if ( ! function_exists( 'check_admin_referer' ) ) {
	/**
	 * Check the nonce; a test sets 'nonce_ok' to false to make it fail as WordPress does.
	 *
	 * @param string $action Action.
	 * @return int 1 when valid.
	 * @throws Es_Optimizer_Test_Die When the test marks the nonce as invalid.
	 */
	function check_admin_referer( string $action ): int {
		$GLOBALS['es_optimizer_test']['nonce_checked'][] = $action;

		if ( false === ( $GLOBALS['es_optimizer_test']['nonce_ok'] ?? true ) ) {
			throw new Es_Optimizer_Test_Die( 'The link you followed has expired.' );
		}

		return 1;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Lowercase and keep only key characters, as WordPress does.
	 *
	 * @param string $key Key.
	 * @return string Key.
	 */
	function sanitize_key( string $key ): string {
		return (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Non-negative integer.
	 *
	 * @param mixed $value Value.
	 * @return int Integer.
	 */
	function absint( mixed $value ): int {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Strip tags and line breaks.
	 *
	 * @param string $text Text.
	 * @return string Text.
	 */
	function sanitize_text_field( string $text ): string {
		return trim( (string) preg_replace( '/[\r\n\t ]+/', ' ', wp_strip_all_tags( $text ) ) );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	/**
	 * Return a translated, attribute-escaped string.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 * @return string Escaped text.
	 */
	function esc_attr__( string $text, string $domain = 'default' ): string {
		return esc_attr( __( $text, $domain ) ); // phpcs:ignore WordPress.WP.I18n -- A test double passes the text through.
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	/**
	 * Print a translated, attribute-escaped string.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 * @return void
	 */
	function esc_attr_e( string $text, string $domain = 'default' ): void {
		echo esc_attr( __( $text, $domain ) ); // phpcs:ignore WordPress.WP.I18n -- A test double passes the text through.
	}
}
