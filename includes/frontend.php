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
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	add_filter( 'tiny_mce_plugins', 'es_optimizer_disable_emojis_tinymce' );
	add_filter( 'wp_resource_hints', 'es_optimizer_disable_emojis_remove_dns_prefetch', 10, 2 );
}

/**
 * Filter function used to remove the TinyMCE emoji plugin.
 *
 * @since 1.0.0
 * @param array<int, string> $plugins Array of TinyMCE plugins.
 * @return array<int, string> Plugins without wpemoji.
 */
function es_optimizer_disable_emojis_tinymce( array $plugins ): array {
	return array_values( array_diff( $plugins, array( 'wpemoji' ) ) );
}

/**
 * Remove emoji CDN hostname from DNS prefetching hints.
 *
 * @since 1.0.0
 * @param array<int, array<string, mixed>|string> $urls          URLs to print for resource hints.
 * @param string                                  $relation_type The relation type the URLs are printed for.
 * @return array<int, array<string, mixed>|string> Filtered URLs.
 */
function es_optimizer_disable_emojis_remove_dns_prefetch( array $urls, string $relation_type ): array {
	if ( 'dns-prefetch' !== $relation_type ) {
		return $urls;
	}

	$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
	$emoji_host    = wp_parse_url( $emoji_svg_url, PHP_URL_HOST );

	if ( ! is_string( $emoji_host ) || '' === $emoji_host ) {
		return $urls;
	}

	return array_values(
		array_filter(
			$urls,
			static function ( array|string $url ) use ( $emoji_host ): bool {
				$href = is_array( $url ) ? (string) ( $url['href'] ?? '' ) : $url;
				$host = wp_parse_url( $href, PHP_URL_HOST );

				return $emoji_host !== $host;
			}
		)
	);
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

	if ( es_optimizer_is_option_enabled( 'remove_wlw_manifest' ) ) {
		remove_action( 'wp_head', 'wlwmanifest_link' );
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
	if ( ! es_optimizer_is_option_enabled( 'remove_recent_comments_style' ) ) {
		return;
	}

	add_filter( 'show_recent_comments_widget_style', '__return_false', PHP_INT_MAX );
}

/**
 * Add preconnect hints through the native WordPress resource hints API.
 *
 * @since 1.4.1
 * @param array<int, array<string, mixed>|string> $urls          URLs to print for resource hints.
 * @param string                                  $relation_type The relation type the URLs are printed for.
 * @return array<int, array<string, mixed>|string> Resource hints.
 */
function es_optimizer_add_preconnect_resource_hints( array $urls, string $relation_type ): array {
	if ( 'preconnect' !== $relation_type || ! es_optimizer_is_frontend_request() ) {
		return $urls;
	}

	if ( ! es_optimizer_is_option_enabled( 'enable_preconnect' ) ) {
		return $urls;
	}

	$font_domains = array( 'fonts.googleapis.com', 'fonts.gstatic.com' );

	foreach ( es_optimizer_get_validated_domains( 'preconnect_domains' ) as $domain ) {
		if ( es_optimizer_resource_hint_exists( $urls, $domain ) ) {
			continue;
		}

		$host = wp_parse_url( $domain, PHP_URL_HOST );
		$hint = array( 'href' => $domain );

		if ( is_string( $host ) && in_array( $host, $font_domains, true ) ) {
			$hint['crossorigin'] = 'anonymous';
		}

		$urls[] = $hint;
	}

	return $urls;
}

/**
 * Add DNS prefetch hints through the native WordPress resource hints API.
 *
 * @since 1.8.0
 * @param array<int, array<string, mixed>|string> $urls          URLs to print for resource hints.
 * @param string                                  $relation_type The relation type the URLs are printed for.
 * @return array<int, array<string, mixed>|string> Resource hints.
 */
function es_optimizer_add_dns_prefetch_resource_hints( array $urls, string $relation_type ): array {
	if ( 'dns-prefetch' !== $relation_type || ! es_optimizer_is_frontend_request() ) {
		return $urls;
	}

	if ( ! es_optimizer_is_option_enabled( 'enable_dns_prefetch' ) ) {
		return $urls;
	}

	foreach ( es_optimizer_get_validated_domains( 'dns_prefetch_domains' ) as $domain ) {
		if ( ! es_optimizer_resource_hint_exists( $urls, $domain ) ) {
			$urls[] = $domain;
		}
	}

	return $urls;
}

/**
 * Determine whether a resource hint already exists.
 *
 * @since 2.0.0
 * @param array<int, array<string, mixed>|string> $urls Resource hints.
 * @param string                                  $href URL to check.
 * @return bool True when the hint exists.
 */
function es_optimizer_resource_hint_exists( array $urls, string $href ): bool {
	foreach ( $urls as $url ) {
		$existing_href = is_array( $url ) ? (string) ( $url['href'] ?? '' ) : $url;

		if ( $href === $existing_href ) {
			return true;
		}
	}

	return false;
}

/**
 * Disable Jetpack advertisements.
 *
 * @since 1.0.0
 */
function es_optimizer_disable_jetpack_ads(): void {
	if ( ! es_optimizer_is_option_enabled( 'disable_jetpack_ads' ) ) {
		return;
	}

	add_filter( 'jetpack_just_in_time_msgs', '__return_false', PHP_INT_MAX );
	add_filter( 'jetpack_show_promotions', '__return_false', PHP_INT_MAX );
	add_filter( 'jetpack_blaze_enabled', '__return_false', PHP_INT_MAX );
}

/**
 * Disable WordPress post via email functionality.
 *
 * @since 1.0.0
 */
function es_optimizer_disable_post_via_email(): void {
	if ( ! es_optimizer_is_option_enabled( 'disable_post_via_email' ) ) {
		return;
	}

	add_filter( 'enable_post_by_email_configuration', '__return_false', PHP_INT_MAX );
}
