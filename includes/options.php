<?php
/**
 * Option defaults, retrieval, and validation.
 *
 * @package EngineScript_Site_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Get default plugin options.
 *
 * @since 1.0.0
 * @return array<string, int|string> Default options.
 */
function es_optimizer_get_default_options(): array {
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
 * Get boolean option keys.
 *
 * @since 2.0.0
 * @return array<int, string> Boolean option keys.
 */
function es_optimizer_get_boolean_option_keys(): array {
	return array(
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
}

/**
 * Get cached plugin options to reduce database queries.
 *
 * @since 1.5.13
 * @param bool $force_refresh Whether to force a fresh database read.
 * @return array<string, int|string> Plugin options.
 */
function es_optimizer_get_options( bool $force_refresh = false ): array {
	static $cached_options = null;

	if ( null === $cached_options || $force_refresh ) {
		$stored_options = get_option( 'es_optimizer_options', array() );

		if ( ! is_array( $stored_options ) ) {
			$stored_options = array();
		}

		$default_options = es_optimizer_get_default_options();
		$cached_options  = es_optimizer_normalize_stored_options( $stored_options, $default_options );
	}

	return $cached_options;
}

/**
 * Normalize stored options before they are used by admin or frontend callbacks.
 *
 * @since 2.0.1
 * @param array<string, mixed>      $stored_options  Stored option values.
 * @param array<string, int|string> $default_options Default option values.
 * @return array<string, int|string> Normalized options.
 */
function es_optimizer_normalize_stored_options( array $stored_options, array $default_options ): array {
	$options = $default_options;

	foreach ( es_optimizer_get_boolean_option_keys() as $option_key ) {
		if ( array_key_exists( $option_key, $stored_options ) ) {
			$options[ $option_key ] = ! empty( $stored_options[ $option_key ] ) ? 1 : 0;
		}
	}

	foreach ( array( 'preconnect_domains', 'dns_prefetch_domains' ) as $option_key ) {
		if ( isset( $stored_options[ $option_key ] ) && is_scalar( $stored_options[ $option_key ] ) ) {
			$options[ $option_key ] = (string) $stored_options[ $option_key ];
		}
	}

	return $options;
}

/**
 * Clear the options cache.
 *
 * @since 1.5.13
 */
function es_optimizer_clear_options_cache(): void {
	es_optimizer_get_options( true );
}

/**
 * Check whether a boolean plugin option is enabled.
 *
 * @since 2.0.0
 * @param string $option_key Option key.
 * @return bool True when the option is enabled.
 */
function es_optimizer_is_option_enabled( string $option_key ): bool {
	$options = es_optimizer_get_options();

	return 1 === (int) ( $options[ $option_key ] ?? 0 );
}

/**
 * Validate options before saving.
 *
 * @since 1.0.0
 * @param mixed $input User submitted options.
 * @return array<string, int|string> Validated and sanitized options.
 */
function es_optimizer_validate_options( mixed $input ): array {
	$input = is_array( $input ) ? wp_unslash( $input ) : array();
	$valid = es_optimizer_get_default_options();

	foreach ( es_optimizer_get_boolean_option_keys() as $checkbox ) {
		$valid[ $checkbox ] = ! empty( $input[ $checkbox ] ) ? 1 : 0;
	}

	if ( isset( $input['preconnect_domains'] ) ) {
		$valid['preconnect_domains'] = es_optimizer_validate_domain_list( (string) $input['preconnect_domains'], 'preconnect' );
	}

	if ( isset( $input['dns_prefetch_domains'] ) ) {
		$valid['dns_prefetch_domains'] = es_optimizer_validate_domain_list( (string) $input['dns_prefetch_domains'], 'dns_prefetch' );
	}

	return $valid;
}

/**
 * Validate a list of HTTPS domains.
 *
 * @since 1.4.0
 * @param string $domains_input Raw domain input from user.
 * @param string $context       Either 'preconnect' or 'dns_prefetch'.
 * @return string Validated and sanitized domains.
 */
function es_optimizer_validate_domain_list( string $domains_input, string $context ): string {
	$domains           = preg_split( '/\r\n|\r|\n/', trim( sanitize_textarea_field( $domains_input ) ) );
	$sanitized_domains = array();
	$rejected_domains  = array();

	if ( false === $domains ) {
		return '';
	}

	foreach ( $domains as $domain ) {
		$domain = trim( $domain );

		if ( '' === $domain ) {
			continue;
		}

		$validation_result = es_optimizer_validate_single_domain( $domain );

		if ( true === $validation_result['valid'] ) {
			$sanitized_domains[] = $validation_result['domain'];
		} else {
			$rejected_domains[] = $validation_result['error'];
		}
	}

	if ( ! empty( $rejected_domains ) ) {
		es_optimizer_show_rejection_notice( $rejected_domains, $context );
	}

	return implode( "\n", array_values( array_unique( $sanitized_domains ) ) );
}

/**
 * Show admin notice for rejected domains.
 *
 * @since 1.4.0
 * @param array<int, string> $rejected_domains Array of rejected domain strings.
 * @param string             $context          Either 'preconnect' or 'dns_prefetch'.
 */
function es_optimizer_show_rejection_notice( array $rejected_domains, string $context ): void {
	$escaped_domains  = array_map( 'esc_html', array_slice( $rejected_domains, 0, 3 ) );
	$rejected_message = implode( ', ', $escaped_domains );

	if ( count( $rejected_domains ) > 3 ) {
		$rejected_message .= esc_html__( '...', 'enginescript-site-optimizer' );
	}

	if ( 'preconnect' === $context ) {
		$message = sprintf(
			/* translators: %s is the list of rejected domain names. */
			esc_html__( 'Some preconnect domains were rejected for security reasons: %s', 'enginescript-site-optimizer' ),
			$rejected_message
		);
		$error_code = 'preconnect_security';
	} else {
		$message = sprintf(
			/* translators: %s is the list of rejected domain names. */
			esc_html__( 'Some DNS prefetch domains were rejected for security reasons: %s', 'enginescript-site-optimizer' ),
			$rejected_message
		);
		$error_code = 'dns_prefetch_security';
	}

	add_settings_error(
		'es_optimizer_options',
		$error_code,
		$message,
		'warning'
	);
}

/**
 * Validate a single domain for preconnect or DNS prefetch use.
 *
 * @since 1.4.0
 * @param string $domain Domain to validate.
 * @return array{valid: bool, domain: string, error: string} Validation result.
 */
function es_optimizer_validate_single_domain( string $domain ): array {
	$domain = trim( $domain );

	if ( '' === $domain ) {
		return es_optimizer_get_domain_validation_error( __( 'Empty domain', 'enginescript-site-optimizer' ) );
	}

	$sanitized_url = sanitize_url( $domain, array( 'https' ) );
	$parsed_url    = wp_parse_url( $sanitized_url );

	if ( '' === $sanitized_url || ! is_array( $parsed_url ) ) {
		return es_optimizer_get_domain_validation_error( $domain . ' (invalid URL)' );
	}

	$url_parts_error = es_optimizer_validate_domain_url_parts( $domain, $parsed_url );

	if ( null !== $url_parts_error ) {
		return $url_parts_error;
	}

	$host       = es_optimizer_normalize_host( (string) $parsed_url['host'] );
	$host_error = es_optimizer_validate_resource_hint_host( $domain, $host );

	if ( null !== $host_error ) {
		return $host_error;
	}

	$clean_domain = 'https://' . $host;

	if ( isset( $parsed_url['port'] ) && 443 !== (int) $parsed_url['port'] ) {
		$clean_domain .= ':' . (int) $parsed_url['port'];
	}

	return array(
		'valid'  => true,
		'domain' => sanitize_url( $clean_domain, array( 'https' ) ),
		'error'  => '',
	);
}

/**
 * Validate parsed URL components before host-specific checks.
 *
 * @since 2.0.1
 * @param string               $domain     Original domain input.
 * @param array<string, mixed> $parsed_url Parsed URL parts.
 * @return array{valid: bool, domain: string, error: string}|null Validation error or null.
 */
function es_optimizer_validate_domain_url_parts( string $domain, array $parsed_url ): ?array {
	if ( ! es_optimizer_url_has_https_scheme( $parsed_url ) ) {
		return es_optimizer_get_domain_validation_error( $domain . ' (HTTPS is required)' );
	}

	if ( empty( $parsed_url['host'] ) ) {
		return es_optimizer_get_domain_validation_error( $domain . ' (no host found)' );
	}

	if ( es_optimizer_url_has_disallowed_path( $parsed_url ) ) {
		return es_optimizer_get_domain_validation_error( $domain . ' (file paths are not allowed; use domains only)' );
	}

	if ( es_optimizer_url_has_disallowed_parts( $parsed_url ) ) {
		return es_optimizer_get_domain_validation_error( $domain . ' (query parameters, fragments, and credentials are not allowed)' );
	}

	if ( es_optimizer_url_has_invalid_port( $parsed_url ) ) {
		return es_optimizer_get_domain_validation_error( $domain . ' (invalid port)' );
	}

	return null;
}

/**
 * Validate a normalized host for resource hint use.
 *
 * @since 2.0.1
 * @param string $domain Original domain input.
 * @param string $host   Normalized host.
 * @return array{valid: bool, domain: string, error: string}|null Validation error or null.
 */
function es_optimizer_validate_resource_hint_host( string $domain, string $host ): ?array {
	if ( es_optimizer_is_disallowed_resource_hint_host( $host ) ) {
		return es_optimizer_get_domain_validation_error( $domain . ' (IP addresses and private, local, or reserved hosts are not allowed)' );
	}

	if ( ! es_optimizer_is_valid_resource_hint_hostname( $host ) ) {
		return es_optimizer_get_domain_validation_error( $domain . ' (invalid hostname)' );
	}

	return null;
}

/**
 * Build a failed domain validation result.
 *
 * @since 2.0.0
 * @param string $error Error message.
 * @return array{valid: bool, domain: string, error: string} Validation result.
 */
function es_optimizer_get_domain_validation_error( string $error ): array {
	return array(
		'valid'  => false,
		'domain' => '',
		'error'  => $error,
	);
}

/**
 * Check whether parsed URL parts use HTTPS.
 *
 * @since 2.0.0
 * @param array<string, mixed> $parsed_url Parsed URL parts.
 * @return bool True when the URL uses HTTPS.
 */
function es_optimizer_url_has_https_scheme( array $parsed_url ): bool {
	return ! empty( $parsed_url['scheme'] ) && 'https' === strtolower( (string) $parsed_url['scheme'] );
}

/**
 * Check whether parsed URL parts include a disallowed path.
 *
 * @since 2.0.0
 * @param array<string, mixed> $parsed_url Parsed URL parts.
 * @return bool True when a non-root path is present.
 */
function es_optimizer_url_has_disallowed_path( array $parsed_url ): bool {
	$path = $parsed_url['path'] ?? '';

	return '/' !== $path && '' !== $path;
}

/**
 * Check whether parsed URL parts include disallowed components.
 *
 * @since 2.0.0
 * @param array<string, mixed> $parsed_url Parsed URL parts.
 * @return bool True when disallowed parts are present.
 */
function es_optimizer_url_has_disallowed_parts( array $parsed_url ): bool {
	$disallowed_parts = array( 'query', 'fragment', 'user', 'pass' );

	foreach ( $disallowed_parts as $part ) {
		if ( isset( $parsed_url[ $part ] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Check whether parsed URL parts include an invalid port.
 *
 * @since 2.0.0
 * @param array<string, mixed> $parsed_url Parsed URL parts.
 * @return bool True when an invalid port is present.
 */
function es_optimizer_url_has_invalid_port( array $parsed_url ): bool {
	if ( ! isset( $parsed_url['port'] ) ) {
		return false;
	}

	$port = (int) $parsed_url['port'];

	return $port < 1 || $port > 65535;
}

/**
 * Normalize a URL host before validating or storing it.
 *
 * @since 2.0.1
 * @param string $host Hostname from a parsed URL.
 * @return string Normalized host.
 */
function es_optimizer_normalize_host( string $host ): string {
	$host = strtolower( trim( $host ) );

	if ( str_starts_with( $host, '[' ) && str_ends_with( $host, ']' ) ) {
		return substr( $host, 1, -1 );
	}

	return $host;
}

/**
 * Check whether a host is disallowed for resource hints.
 *
 * @since 2.0.1
 * @param string $host Hostname or IP address.
 * @return bool True when the host should be rejected.
 */
function es_optimizer_is_disallowed_resource_hint_host( string $host ): bool {
	if ( es_optimizer_is_ip_literal_host( $host ) || es_optimizer_looks_like_obfuscated_ip_host( $host ) ) {
		return true;
	}

	return es_optimizer_is_special_use_hostname( $host );
}

/**
 * Check whether a host is an IP literal.
 *
 * @since 2.0.1
 * @param string $host Hostname or IP address.
 * @return bool True when the host is an IP literal.
 */
function es_optimizer_is_ip_literal_host( string $host ): bool {
	return false !== rest_is_ip_address( $host );
}

/**
 * Check whether a host looks like an obfuscated IP literal.
 *
 * @since 2.0.1
 * @param string $host Hostname or IP address.
 * @return bool True when the host resembles an obfuscated IP literal.
 */
function es_optimizer_looks_like_obfuscated_ip_host( string $host ): bool {
	return 1 === preg_match( '/^(?:0x[0-9a-f]+|0[0-7]+|\d+)(?:\.(?:0x[0-9a-f]+|0[0-7]+|\d+)){0,3}$/i', $host );
}

/**
 * Check whether a host is a reserved or local-use hostname.
 *
 * @since 2.0.1
 * @param string $host Hostname.
 * @return bool True when the hostname is reserved or local-use.
 */
function es_optimizer_is_special_use_hostname( string $host ): bool {
	$reserved_suffixes = array(
		'example',
		'home.arpa',
		'internal',
		'invalid',
		'local',
		'localhost',
		'test',
	);

	foreach ( $reserved_suffixes as $suffix ) {
		if ( $host === $suffix || str_ends_with( $host, '.' . $suffix ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Check whether a host is a syntactically valid public hostname.
 *
 * @since 2.0.1
 * @param string $host Hostname.
 * @return bool True when the hostname syntax is valid.
 */
function es_optimizer_is_valid_resource_hint_hostname( string $host ): bool {
	if ( '' === $host || strlen( $host ) > 253 || str_ends_with( $host, '.' ) ) {
		return false;
	}

	$labels = explode( '.', $host );

	if ( count( $labels ) < 2 ) {
		return false;
	}

	foreach ( $labels as $label ) {
		if ( 1 !== preg_match( '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $label ) ) {
			return false;
		}
	}

	$top_level_domain = $labels[ array_key_last( $labels ) ];

	return 1 !== preg_match( '/^\d+$/', $top_level_domain );
}

/**
 * Get validated domains from a settings option.
 *
 * @since 2.0.1
 * @param string $option_key The option key to read domains from.
 * @return array<int, string> Validated domain URLs.
 */
function es_optimizer_get_validated_domains( string $option_key ): array {
	$options = es_optimizer_get_options();
	$raw     = $options[ $option_key ] ?? '';

	if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
		return array();
	}

	$domains       = preg_split( '/\r\n|\r|\n/', $raw );
	$valid_domains = array();

	if ( false === $domains ) {
		return array();
	}

	foreach ( $domains as $domain ) {
		$validation_result = es_optimizer_validate_single_domain( $domain );

		if ( true === $validation_result['valid'] ) {
			$valid_domains[] = $validation_result['domain'];
		}
	}

	return array_values( array_unique( $valid_domains ) );
}
