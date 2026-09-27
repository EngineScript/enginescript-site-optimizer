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
 * Maximum raw bytes in one submitted or stored domain list.
 *
 * @since Unreleased
 */
const ES_SITE_OPTIMIZER_MAX_DOMAIN_LIST_BYTES = 32768;

/**
 * Maximum nonblank lines before domain validation or deduplication.
 *
 * @since Unreleased
 */
const ES_SITE_OPTIMIZER_MAX_DOMAIN_LINES = 100;

/**
 * Maximum raw bytes in each nonblank domain line.
 *
 * @since Unreleased
 */
const ES_SITE_OPTIMIZER_MAX_DOMAIN_LINE_BYTES = 512;

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
		'remove_shortlink'             => 0,
		'remove_recent_comments_style' => 0,
		'enable_preconnect'            => 0,
		'preconnect_domains'           => implode(
			"\n",
			array(
				'https://fonts.googleapis.com',
				'https://fonts.gstatic.com',
				'https://s.w.org', // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Preconnect origin only; no remote asset is loaded.
				'https://wordpress.com',
				'https://cdnjs.cloudflare.com', // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Preconnect origin only; no remote asset is loaded.
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
		'remove_shortlink',
		'remove_recent_comments_style',
		'enable_preconnect',
		'enable_dns_prefetch',
		'disable_jetpack_ads',
		'disable_post_via_email',
	);
}

/**
 * Get normalized options for the current site using WordPress option caching.
 *
 * The boolean argument retains the established public helper signature.
 *
 * @since 1.5.13
 * @param bool $force_refresh Retained for compatibility; WordPress owns cache freshness.
 * @return array<string, int|string> Plugin options.
 * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
 */
function es_optimizer_get_options( bool $force_refresh = false ): array {
	unset( $force_refresh );

	$stored_options = get_option( 'es_optimizer_options', array() );

	if ( ! is_array( $stored_options ) ) {
		$stored_options = array();
	}

	return es_optimizer_normalize_stored_options( $stored_options, es_optimizer_get_default_options() );
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
 * Retain the cache-clear helper for callers using earlier plugin versions.
 *
 * WordPress owns option caching; the plugin no longer stores a second copy.
 *
 * @since 1.5.13
 */
function es_optimizer_clear_options_cache(): void {
	// No plugin-owned cache remains to clear.
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
 * @param mixed $input Unslashed options supplied by WordPress or a PHP caller.
 * @return array<string, int|string> Validated and sanitized options.
 */
function es_optimizer_validate_options( mixed $input ): array {
	$valid = es_optimizer_get_options();

	if ( ! is_array( $input ) ) {
		es_optimizer_add_settings_warning(
			'options_invalid',
			esc_html__( 'The submitted settings have an invalid format. Your previous settings were retained.', 'enginescript-site-optimizer' )
		);

		return $valid;
	}

	foreach ( es_optimizer_get_boolean_option_keys() as $checkbox ) {
		$valid[ $checkbox ] = ! empty( $input[ $checkbox ] ) ? 1 : 0;
	}

	$domain_fields = array(
		'preconnect_domains'   => 'preconnect',
		'dns_prefetch_domains' => 'dns_prefetch',
	);

	foreach ( $domain_fields as $option_key => $context ) {
		if ( ! array_key_exists( $option_key, $input ) ) {
			continue;
		}

		if ( ! is_string( $input[ $option_key ] ) ) {
			es_optimizer_show_rejection_notice(
				array( __( 'Enter domains as text. Your previous list was retained.', 'enginescript-site-optimizer' ) ),
				$context
			);
			continue;
		}

		$valid[ $option_key ] = es_optimizer_validate_domain_list( $input[ $option_key ], $context );
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
	$budget_error = es_optimizer_get_domain_list_budget_error( $domains_input );

	if ( '' !== $budget_error ) {
		es_optimizer_show_rejection_notice(
			array( $budget_error . ' ' . __( 'Your previous list was retained.', 'enginescript-site-optimizer' ) ),
			$context
		);
		$options    = es_optimizer_get_options();
		$option_key = 'preconnect' === $context ? 'preconnect_domains' : 'dns_prefetch_domains';

		return (string) $options[ $option_key ];
	}

	$domains           = preg_split( '/\r\n|\r|\n/', $domains_input );
	$sanitized_domains = array();
	$rejected_domains  = array();
	$rejected_count    = 0;

	if ( false === $domains ) {
		return '';
	}

	foreach ( $domains as $line_number => $domain ) {
		$domain = trim( $domain, ' ' );

		if ( '' === $domain ) {
			continue;
		}

		$validation_result = es_optimizer_validate_domain_for_context( $domain, $context );

		if ( true === $validation_result['valid'] ) {
			$sanitized_domains[] = $validation_result['domain'];
		} else {
			++$rejected_count;

			if ( count( $rejected_domains ) < 3 ) {
				$rejected_domains[] = sprintf(
					/* translators: 1: Line number in the submitted domain list, 2: Validation reason without submitted text. */
					__( 'Line %1$d: %2$s', 'enginescript-site-optimizer' ),
					$line_number + 1,
					$validation_result['error']
				);
			}
		}
	}

	if ( ! empty( $rejected_domains ) ) {
		es_optimizer_show_rejection_notice( $rejected_domains, $context, $rejected_count );
	}

	return implode( "\n", array_values( array_unique( $sanitized_domains ) ) );
}

/**
 * Check raw list budgets before trimming, parsing or validating any origin.
 *
 * Empty and ASCII-space-only lines are blank. Duplicates and invalid nonblank
 * lines count before deduplication. No caller may partially use an oversized list.
 *
 * @since Unreleased
 * @param string $domains_input Original submitted or stored domain text.
 * @return string Safe localized reason, or an empty string when within budget.
 */
function es_optimizer_get_domain_list_budget_error( string $domains_input ): string {
	if ( strlen( $domains_input ) > ES_SITE_OPTIMIZER_MAX_DOMAIN_LIST_BYTES ) {
		return sprintf(
			/* translators: %d: Maximum raw bytes allowed in one domain list. */
			__( 'Each domain list is limited to %d bytes.', 'enginescript-site-optimizer' ),
			ES_SITE_OPTIMIZER_MAX_DOMAIN_LIST_BYTES
		);
	}

	$lines = preg_split( '/\r\n|\r|\n/', $domains_input );

	if ( false === $lines ) {
		return __( 'The domain list could not be read.', 'enginescript-site-optimizer' );
	}

	$nonblank_lines = 0;

	foreach ( $lines as $line ) {
		$line_bytes = strlen( $line );

		if ( strspn( $line, ' ' ) === $line_bytes ) {
			continue;
		}

		if ( $line_bytes > ES_SITE_OPTIMIZER_MAX_DOMAIN_LINE_BYTES ) {
			return sprintf(
				/* translators: %d: Maximum raw bytes allowed in a nonblank domain line. */
				__( 'Each nonblank domain line is limited to %d bytes.', 'enginescript-site-optimizer' ),
				ES_SITE_OPTIMIZER_MAX_DOMAIN_LINE_BYTES
			);
		}

		++$nonblank_lines;

		if ( $nonblank_lines > ES_SITE_OPTIMIZER_MAX_DOMAIN_LINES ) {
			return sprintf(
				/* translators: %d: Maximum nonblank lines allowed before removing duplicates or invalid domains. */
				__( 'Each domain list is limited to %d nonblank lines, including duplicates and invalid entries.', 'enginescript-site-optimizer' ),
				ES_SITE_OPTIMIZER_MAX_DOMAIN_LINES
			);
		}
	}

	return '';
}

/**
 * Validate one raw origin and apply the policy of its resource-hint context.
 *
 * @since Unreleased
 * @param string $domain  Original origin text.
 * @param string $context Either 'preconnect' or 'dns_prefetch'.
 * @return array{valid: bool, domain: string, error: string} Validation result.
 */
function es_optimizer_validate_domain_for_context( string $domain, string $context ): array {
	$result = es_optimizer_validate_single_domain( $domain );

	if ( true === $result['valid'] && ! es_optimizer_is_domain_allowed_for_context( $result['domain'], $context ) ) {
		return es_optimizer_get_domain_validation_error(
			__( 'Preconnect supports HTTPS port 443 only.', 'enginescript-site-optimizer' )
		);
	}

	return $result;
}

/**
 * Show admin notice for rejected domains.
 *
 * @since 1.4.0
 * @param array<int, string> $rejected_domains Safe rejection reasons without submitted text.
 * @param string             $context          Either 'preconnect' or 'dns_prefetch'.
 * @param int|null           $rejected_count   Total rejected lines, or null for a field-level warning.
 */
function es_optimizer_show_rejection_notice( array $rejected_domains, string $context, ?int $rejected_count = null ): void {
	$escaped_domains  = array_map( 'esc_html', array_slice( $rejected_domains, 0, 3 ) );
	$rejected_message = implode( ', ', $escaped_domains );

	if ( ( $rejected_count ?? count( $rejected_domains ) ) > 3 ) {
		$rejected_message .= esc_html__( '...', 'enginescript-site-optimizer' );
	}

	if ( null !== $rejected_count ) {
		$rejected_message = sprintf(
			/* translators: 1: Total number of rejected lines, 2: At most three safe reasons with line numbers. */
			esc_html( _n( '%1$d line was rejected. %2$s', '%1$d lines were rejected. %2$s', $rejected_count, 'enginescript-site-optimizer' ) ),
			$rejected_count,
			$rejected_message
		);
	}

	if ( 'preconnect' === $context ) {
		$message = sprintf(
			/* translators: %s: Safe validation reasons and line numbers, without submitted domains. */
			esc_html__( 'The preconnect list could not be fully accepted: %s', 'enginescript-site-optimizer' ),
			$rejected_message
		);
		$error_code = 'preconnect_security';
	} else {
		$message = sprintf(
			/* translators: %s: Safe validation reasons and line numbers, without submitted domains. */
			esc_html__( 'The DNS prefetch list could not be fully accepted: %s', 'enginescript-site-optimizer' ),
			$rejected_message
		);
		$error_code = 'dns_prefetch_security';
	}

	es_optimizer_add_settings_warning( $error_code, $message );
}

/**
 * Register a plugin warning once without consuming or replacing other notices.
 *
 * Read the existing collection only. The native getter can consume the saved
 * redirect transient, which must remain owned by the normal Settings wrapper.
 *
 * @since Unreleased
 * @global mixed $wp_settings_errors Settings API notices registered in this request.
 * @param string $code    Fixed plugin warning code.
 * @param string $message Escaped message containing only safe diagnostic text.
 * @return void
 */
function es_optimizer_add_settings_warning( string $code, string $message ): void {
	global $wp_settings_errors;

	foreach ( (array) $wp_settings_errors as $error ) {
		if ( is_array( $error ) && 'es_optimizer_options' === ( $error['setting'] ?? null ) && ( $error['code'] ?? null ) === $code ) {
			return;
		}
	}

	add_settings_error( 'es_optimizer_options', $code, $message, 'warning' );
}

/**
 * Validate a single domain for preconnect or DNS prefetch use.
 *
 * @since 1.4.0
 * @param string $domain Domain to validate.
 * @return array{valid: bool, domain: string, error: string} Validation result.
 */
function es_optimizer_validate_single_domain( string $domain ): array {
	if ( es_optimizer_domain_has_disallowed_characters( $domain ) ) {
		return es_optimizer_get_domain_validation_error(
			__( 'Encoded characters, backslashes, markup, and control characters are not allowed.', 'enginescript-site-optimizer' )
		);
	}

	$domain = trim( $domain, ' ' );

	if ( '' === $domain ) {
		return es_optimizer_get_domain_validation_error( __( 'Empty domain.', 'enginescript-site-optimizer' ) );
	}

	$parsed_url = wp_parse_url( $domain );

	if ( ! is_array( $parsed_url ) ) {
		return es_optimizer_get_domain_validation_error( __( 'Invalid URL.', 'enginescript-site-optimizer' ) );
	}

	$url_parts_error = es_optimizer_validate_domain_url_parts( $domain, $parsed_url );

	if ( null !== $url_parts_error ) {
		return $url_parts_error;
	}

	$host       = es_optimizer_normalize_host( $parsed_url['host'] ?? '' );
	$host_error = es_optimizer_validate_resource_hint_host( $domain, $host );

	if ( null !== $host_error ) {
		return $host_error;
	}

	$clean_domain = 'https://' . $host;

	if ( isset( $parsed_url['port'] ) && 443 !== $parsed_url['port'] ) {
		$clean_domain .= ':' . $parsed_url['port'];
	}

	if ( sanitize_url( $clean_domain, array( 'https' ) ) !== $clean_domain ) {
		return es_optimizer_get_domain_validation_error( __( 'Invalid URL.', 'enginescript-site-optimizer' ) );
	}

	return array(
		'valid'  => true,
		'domain' => $clean_domain,
		'error'  => '',
	);
}

/**
 * Check original domain text before parsing or normalization can remove bytes.
 *
 * @since Unreleased
 * @param string $domain Original domain input.
 * @return bool Whether the input contains a forbidden character.
 */
function es_optimizer_domain_has_disallowed_characters( string $domain ): bool {
	return str_contains( $domain, '\\' ) || 1 === preg_match( '/[%<>\x00-\x1F\x7F]/', $domain );
}

/**
 * Validate parsed URL components before host-specific checks.
 *
 * @since 2.0.1
 * @param string               $domain     Original input retained for signature compatibility.
 * @param array<string, mixed> $parsed_url Parsed URL parts.
 * @return array{valid: bool, domain: string, error: string}|null Validation error or null.
 */
function es_optimizer_validate_domain_url_parts( string $domain, array $parsed_url ): ?array {
	unset( $domain );

	if ( ! es_optimizer_url_has_https_scheme( $parsed_url ) ) {
		return es_optimizer_get_domain_validation_error( __( 'HTTPS is required.', 'enginescript-site-optimizer' ) );
	}

	if ( empty( $parsed_url['host'] ) ) {
		return es_optimizer_get_domain_validation_error( __( 'No host found.', 'enginescript-site-optimizer' ) );
	}

	if ( es_optimizer_url_has_disallowed_path( $parsed_url ) ) {
		return es_optimizer_get_domain_validation_error( __( 'File paths are not allowed; use domains only.', 'enginescript-site-optimizer' ) );
	}

	if ( es_optimizer_url_has_disallowed_parts( $parsed_url ) ) {
		return es_optimizer_get_domain_validation_error(
			__( 'Query parameters, fragments, and credentials are not allowed.', 'enginescript-site-optimizer' )
		);
	}

	if ( es_optimizer_url_has_invalid_port( $parsed_url ) ) {
		return es_optimizer_get_domain_validation_error( __( 'Invalid port.', 'enginescript-site-optimizer' ) );
	}

	return null;
}

/**
 * Validate a normalized host for resource hint use.
 *
 * @since 2.0.1
 * @param string $domain Original input retained for signature compatibility.
 * @param string $host   Normalized host.
 * @return array{valid: bool, domain: string, error: string}|null Validation error or null.
 */
function es_optimizer_validate_resource_hint_host( string $domain, string $host ): ?array {
	unset( $domain );

	if ( es_optimizer_is_disallowed_resource_hint_host( $host ) ) {
		return es_optimizer_get_domain_validation_error(
			__( 'IP addresses and private, local, or reserved hosts are not allowed.', 'enginescript-site-optimizer' )
		);
	}

	if ( ! es_optimizer_is_valid_resource_hint_hostname( $host ) ) {
		return es_optimizer_get_domain_validation_error( __( 'Invalid hostname.', 'enginescript-site-optimizer' ) );
	}

	return null;
}

/**
 * Build a failed domain validation result.
 *
 * @since 2.0.0
 * @param string $error Safe localized reason without submitted text.
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
 * Apply a resource-hint context policy to an already validated canonical origin.
 *
 * The shared validator removes explicit port 443, so any remaining port is custom.
 *
 * @since Unreleased
 * @param string $domain  Canonical HTTPS origin from the single-domain validator.
 * @param string $context Either 'preconnect' or 'dns_prefetch'.
 * @return bool Whether the origin is supported in this context.
 */
function es_optimizer_is_domain_allowed_for_context( string $domain, string $context ): bool {
	return 'preconnect' !== $context || null === wp_parse_url( $domain, PHP_URL_PORT );
}

/**
 * Detect saved custom-port origins without modifying their stored or displayed text.
 *
 * @since Unreleased
 * @param string $domains_input Saved preconnect list.
 * @return bool Whether a valid origin has a port unsupported by preconnect.
 */
function es_optimizer_has_unsupported_preconnect_domains( string $domains_input ): bool {
	if ( '' !== es_optimizer_get_domain_list_budget_error( $domains_input ) ) {
		return false;
	}

	$domains = preg_split( '/\r\n|\r|\n/', $domains_input );

	if ( false === $domains ) {
		return false;
	}

	foreach ( $domains as $domain ) {
		if ( '' === trim( $domain, ' ' ) ) {
			continue;
		}

		$result = es_optimizer_validate_single_domain( $domain );

		if ( true === $result['valid'] && ! es_optimizer_is_domain_allowed_for_context( $result['domain'], 'preconnect' ) ) {
			return true;
		}
	}

	return false;
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
 * @return array<int, string> Canonical origins permitted by the option's hint context.
 */
function es_optimizer_get_validated_domains( string $option_key ): array {
	$options = es_optimizer_get_options();
	$raw     = $options[ $option_key ] ?? '';

	if ( ! is_string( $raw ) || '' !== es_optimizer_get_domain_list_budget_error( $raw ) ) {
		return array();
	}

	$context       = 'preconnect_domains' === $option_key ? 'preconnect' : 'dns_prefetch';
	$domains       = preg_split( '/\r\n|\r|\n/', $raw );
	$valid_domains = array();

	if ( false === $domains ) {
		return array();
	}

	foreach ( $domains as $domain ) {
		if ( '' === trim( $domain, ' ' ) ) {
			continue;
		}

		$validation_result = es_optimizer_validate_domain_for_context( $domain, $context );

		if ( true === $validation_result['valid'] ) {
			$valid_domains[] = $validation_result['domain'];
		}
	}

	return array_values( array_unique( $valid_domains ) );
}
