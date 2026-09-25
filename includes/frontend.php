<?php
/**
 * Frontend optimization callbacks.
 *
 * @package EngineScript_Site_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Determine whether frontend-only optimizations should run.
 *
 * @since 2.0.0
 * @return bool True when the current request is a regular frontend request.
 */
function es_optimizer_is_frontend_request(): bool {
	return ! is_admin() && ! wp_doing_ajax();
}

/**
 * Disable WordPress emoji functionality.
 *
 * @since 1.0.0
 */
function es_optimizer_disable_emojis(): void {
	if ( ! es_optimizer_is_option_enabled( 'disable_emojis' ) ) {
		return;
	}

	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'embed_head', 'print_emoji_detection_script', 10 );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}

/**
 * Remove admin emoji output after WordPress has registered its admin callbacks.
 *
 * @since Unreleased
 * @return void
 */
function es_optimizer_disable_admin_emojis(): void {
	if ( ! es_optimizer_is_option_enabled( 'disable_emojis' ) ) {
		return;
	}

	remove_action( 'admin_print_scripts', 'print_emoji_detection_script', 10 );
	remove_action( 'admin_print_styles', 'print_emoji_styles', 10 );
}

/**
 * Filter function used to remove the TinyMCE emoji plugin.
 *
 * @since 1.0.0
 * @param array<int, string> $plugins Array of TinyMCE plugins.
 * @return array<int, string> Plugins without wpemoji.
 */
function es_optimizer_disable_emojis_tinymce( array $plugins ): array {
	if ( ! es_optimizer_is_option_enabled( 'disable_emojis' ) ) {
		return $plugins;
	}

	return array_values( array_diff( $plugins, array( 'wpemoji' ) ) );
}

/**
 * Remove emoji CDN hostname from DNS prefetching hints.
 *
 * @since 1.0.0
 * @param array<int|string, mixed> $urls          Resource hints, including unknown foreign entries.
 * @param string                   $relation_type The relation type the URLs are printed for.
 * @return array<int|string, mixed> Filtered hints with surviving keys, order and values preserved.
 */
function es_optimizer_disable_emojis_remove_dns_prefetch( array $urls, string $relation_type ): array {
	if ( 'dns-prefetch' !== $relation_type || ! es_optimizer_is_option_enabled( 'disable_emojis' ) ) {
		return $urls;
	}

	$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );

	if ( ! is_string( $emoji_svg_url ) ) {
		return $urls;
	}

	$emoji_host = es_optimizer_get_resource_hint_host( $emoji_svg_url );

	if ( '' === $emoji_host ) {
		return $urls;
	}

	return array_filter(
		$urls,
		static function ( mixed $url ) use ( $emoji_host ): bool {
			$href = is_array( $url ) ? ( $url['href'] ?? null ) : $url;

			return ! is_string( $href ) || es_optimizer_get_resource_hint_host( $href ) !== $emoji_host;
		}
	);
}

/**
 * Extract a comparable hostname from an existing browser resource-hint value.
 *
 * Accepts absolute, scheme-relative and bare host forms for matching only.
 * This does not relax the stricter policy for administrator-supplied origins.
 *
 * @since Unreleased
 * @param string $url Existing URL or hostname to compare.
 * @return string Lowercase hostname, or an empty string for an unsupported value.
 */
function es_optimizer_get_resource_hint_host( string $url ): string {
	if ( 1 === preg_match( '/[<>\x00-\x20\x7F\\\\]/', $url ) ) {
		return '';
	}

	if ( ! str_contains( $url, '://' ) && ! str_starts_with( $url, '//' ) ) {
		$url = '//' . $url;
	}

	$parts = wp_parse_url( $url );

	if ( ! is_array( $parts ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
		return '';
	}

	if ( ! in_array( strtolower( $parts['scheme'] ?? 'https' ), array( 'http', 'https' ), true ) ) {
		return '';
	}

	$host = strtolower( $parts['host'] ?? '' );

	return str_contains( $host, '%' ) ? '' : $host;
}

/**
 * Remove jQuery Migrate from the frontend jQuery dependency list.
 *
 * @since 1.0.0
 * @param WP_Scripts $scripts WP_Scripts object.
 */
function es_optimizer_remove_jquery_migrate( WP_Scripts $scripts ): void {
	if ( ! es_optimizer_is_option_enabled( 'remove_jquery_migrate' ) || is_admin() ) {
		return;
	}

	if ( empty( $scripts->registered['jquery'] ) ) {
		return;
	}

	$script = $scripts->registered['jquery'];

	$script->deps = array_values( array_diff( $script->deps, array( 'jquery-migrate' ) ) );
}

/**
 * Disable classic theme styles added in WordPress 6.1+.
 *
 * @since 1.3.0
 */
function es_optimizer_disable_classic_theme_styles(): void {
	if ( ! es_optimizer_is_option_enabled( 'disable_classic_theme_styles' ) ) {
		return;
	}

	wp_dequeue_style( 'classic-theme-styles' );
	wp_deregister_style( 'classic-theme-styles' );
}

/**
 * Remove selected WordPress header items.
 *
 * @since 1.0.0
 */
function es_optimizer_remove_header_items(): void {
	if ( es_optimizer_is_option_enabled( 'remove_wp_version' ) ) {
		remove_action( 'wp_head', 'wp_generator' );
	}

	if ( es_optimizer_is_option_enabled( 'remove_rsd_link' ) ) {
		remove_action( 'wp_head', 'rsd_link' );
	}

	if ( es_optimizer_is_option_enabled( 'remove_shortlink' ) ) {
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	}
}

/**
 * Remove Recent Comments Widget CSS styles.
 *
 * @since 1.0.0
 */
function es_optimizer_remove_recent_comments_style(): void {
	add_filter( 'show_recent_comments_widget_style', 'es_optimizer_filter_recent_comments_style', PHP_INT_MAX );
}

/**
 * Apply the current site's recent-comment style policy when the filter runs.
 *
 * @since Unreleased
 * @param mixed $show_style Incoming value from WordPress or another plugin.
 * @return mixed False when disabled; otherwise the original value.
 */
function es_optimizer_filter_recent_comments_style( mixed $show_style ): mixed {
	return es_optimizer_is_option_enabled( 'remove_recent_comments_style' ) ? false : $show_style;
}

/**
 * Add preconnect hints through the native WordPress resource hints API.
 *
 * @since 1.4.1
 * @param array<int|string, mixed> $urls          Resource hints, including unknown foreign values.
 * @param string                   $relation_type The relation type the URLs are printed for.
 * @return array<int|string, mixed> Original entries plus at most 100 supported plugin origins.
 */
function es_optimizer_add_preconnect_resource_hints( array $urls, string $relation_type ): array {
	if ( 'preconnect' !== $relation_type || ! es_optimizer_is_frontend_request() ) {
		return $urls;
	}

	if ( ! es_optimizer_is_option_enabled( 'enable_preconnect' ) ) {
		return $urls;
	}

	$font_domains   = array( 'fonts.googleapis.com', 'fonts.gstatic.com' );
	$existing_hrefs = es_optimizer_get_resource_hint_hrefs( $urls );

	foreach ( es_optimizer_get_validated_domains( 'preconnect_domains' ) as $domain ) {
		if ( isset( $existing_hrefs[ $domain ] ) ) {
			continue;
		}

		$host = wp_parse_url( $domain, PHP_URL_HOST );
		$hint = array( 'href' => $domain );

		if ( is_string( $host ) && in_array( $host, $font_domains, true ) ) {
			$hint['crossorigin'] = 'anonymous';
		}

		$urls[]                    = $hint;
		$existing_hrefs[ $domain ] = true;
	}

	return $urls;
}

/**
 * Add DNS prefetch hints through the native WordPress resource hints API.
 *
 * @since 1.8.0
 * @param array<int|string, mixed> $urls          Resource hints, including unknown foreign values.
 * @param string                   $relation_type The relation type the URLs are printed for.
 * @return array<int|string, mixed> Original entries plus at most 100 origins for core's host-only DNS output.
 */
function es_optimizer_add_dns_prefetch_resource_hints( array $urls, string $relation_type ): array {
	if ( 'dns-prefetch' !== $relation_type || ! es_optimizer_is_frontend_request() ) {
		return $urls;
	}

	if ( ! es_optimizer_is_option_enabled( 'enable_dns_prefetch' ) ) {
		return $urls;
	}

	$existing_hrefs = es_optimizer_get_resource_hint_hrefs( $urls );

	foreach ( es_optimizer_get_validated_domains( 'dns_prefetch_domains' ) as $domain ) {
		if ( ! isset( $existing_hrefs[ $domain ] ) ) {
			$urls[]                    = $domain;
			$existing_hrefs[ $domain ] = true;
		}
	}

	return $urls;
}

/**
 * Determine whether a resource hint already exists.
 *
 * @since 2.0.0
 * @param array<int|string, mixed> $urls Resource hints, including unknown foreign values.
 * @param string                   $href URL to check.
 * @return bool True when the hint exists.
 */
function es_optimizer_resource_hint_exists( array $urls, string $href ): bool {
	return isset( es_optimizer_get_resource_hint_hrefs( $urls )[ $href ] );
}

/**
 * Index existing string href values without changing or coercing foreign hints.
 *
 * Callers build this set once, then add each new href as they append a hint.
 * The domain-list budget bounds plugin additions; foreign input is not truncated.
 *
 * @since Unreleased
 * @param array<int|string, mixed> $urls Existing resource hints.
 * @return array<int|string, true> Exact string href membership set.
 */
function es_optimizer_get_resource_hint_hrefs( array $urls ): array {
	$hrefs = array();

	foreach ( $urls as $url ) {
		$href = is_array( $url ) ? ( $url['href'] ?? null ) : $url;

		if ( is_string( $href ) ) {
			$hrefs[ $href ] = true;
		}
	}

	return $hrefs;
}

/**
 * Disable Jetpack promotional messages and Blaze initialization.
 *
 * @since 1.0.0
 */
function es_optimizer_disable_jetpack_ads(): void {
	add_filter( 'jetpack_just_in_time_msgs', 'es_optimizer_filter_jetpack_ads', PHP_INT_MAX );
	add_filter( 'jetpack_show_promotions', 'es_optimizer_filter_jetpack_ads', PHP_INT_MAX );
	add_filter( 'jetpack_blaze_enabled', 'es_optimizer_filter_jetpack_ads', PHP_INT_MAX );
}

/**
 * Apply the current site's policy to the three existing Jetpack filters.
 *
 * @since Unreleased
 * @param mixed $show_promotion Incoming value from Jetpack or another plugin.
 * @return mixed False when disabled; otherwise the original value.
 */
function es_optimizer_filter_jetpack_ads( mixed $show_promotion ): mixed {
	return es_optimizer_is_option_enabled( 'disable_jetpack_ads' ) ? false : $show_promotion;
}

/**
 * Disable WordPress post via email functionality.
 *
 * @since 1.0.0
 */
function es_optimizer_disable_post_via_email(): void {
	add_filter( 'enable_post_by_email_configuration', 'es_optimizer_filter_post_via_email', PHP_INT_MAX );
}

/**
 * Apply the current site's post-via-email policy when the filter runs.
 *
 * @since Unreleased
 * @param mixed $enabled Incoming value from WordPress or another plugin.
 * @return mixed False when disabled; otherwise the original value.
 */
function es_optimizer_filter_post_via_email( mixed $enabled ): mixed {
	return es_optimizer_is_option_enabled( 'disable_post_via_email' ) ? false : $enabled;
}
