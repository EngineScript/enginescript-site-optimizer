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
 * The login screen prints resource hints from its own head action. It is not
 * a page for visitors, so the plugin's hints are left out there.
 *
 * @since 2.0.0
 * @return bool True when the current request is a regular frontend request.
 */
function es_optimizer_is_frontend_request(): bool {
	return ! is_admin() && ! wp_doing_ajax() && ! doing_action( 'login_head' );
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
 * @since 2.2.0
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
 * @param mixed $plugins List of TinyMCE plugin names; another filter callback may pass anything.
 * @return mixed Plugins without wpemoji, or the incoming value when it is not an array.
 */
function es_optimizer_disable_emojis_tinymce( mixed $plugins ): mixed {
	if ( ! is_array( $plugins ) || ! es_optimizer_is_option_enabled( 'disable_emojis' ) ) {
		return $plugins;
	}

	return array_values(
		array_filter(
			$plugins,
			static function ( mixed $plugin ): bool {
				return 'wpemoji' !== $plugin;
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
 * The version is removed from the document head and from feeds, and the
 * shortlink from the document head and the HTTP response header. Version
 * arguments on asset URLs are left alone, and so is an export file: see
 * es_optimizer_remove_feed_generator() for the one feed action it shares.
 *
 * @since 1.0.0
 */
function es_optimizer_remove_header_items(): void {
	if ( es_optimizer_is_option_enabled( 'remove_wp_version' ) ) {
		remove_action( 'wp_head', 'wp_generator' );

		// The feed actions that WordPress gives a generator tag, except the one an export file also runs.
		foreach ( array( 'commentsrss2_head', 'rss_head', 'rdf_header', 'atom_head', 'comments_atom_head', 'opml_head', 'app_head' ) as $feed_action ) {
			remove_action( $feed_action, 'the_generator' );
		}
	}

	if ( es_optimizer_is_option_enabled( 'remove_rsd_link' ) ) {
		remove_action( 'wp_head', 'rsd_link' );
	}

	if ( es_optimizer_is_option_enabled( 'remove_shortlink' ) ) {
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	}
}

/**
 * Remove the generator tag from the RSS 2 feed while a feed is being served.
 *
 * WordPress runs the head action of the RSS 2 feed inside an export file as
 * well, in the admin area, under WP-CLI, and wherever other code builds an
 * export. An export must stay as WordPress writes it, so the tag is removed
 * only on a feed request, which is known once the request has been parsed.
 *
 * @since 2.3.0
 * @return void
 */
function es_optimizer_remove_feed_generator(): void {
	if ( is_feed() && es_optimizer_is_option_enabled( 'remove_wp_version' ) ) {
		remove_action( 'rss2_head', 'the_generator' );
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
 * @since 2.2.0
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
 * @param mixed $urls          Resource hints, including unknown foreign values; another filter callback may pass a non-array.
 * @param mixed $relation_type The relation type the URLs are printed for.
 * @return mixed Original entries plus at most 100 supported plugin origins, or the incoming value when it is not an array.
 */
function es_optimizer_add_preconnect_resource_hints( mixed $urls, mixed $relation_type = '' ): mixed {
	if ( ! is_array( $urls ) || 'preconnect' !== $relation_type || ! es_optimizer_is_frontend_request() ) {
		return $urls;
	}

	if ( ! es_optimizer_is_option_enabled( 'enable_preconnect' ) ) {
		return $urls;
	}

	$existing_hrefs = es_optimizer_get_resource_hint_hrefs( $urls );

	foreach ( es_optimizer_get_validated_domains( 'preconnect_domains' ) as $domain ) {
		if ( isset( $existing_hrefs[ $domain ] ) ) {
			continue;
		}

		$host = wp_parse_url( $domain, PHP_URL_HOST );
		$hint = array( 'href' => $domain );

		// Font files are fetched without credentials and need their own connection.
		// A stylesheet is fetched with credentials, so its host must not be marked.
		if ( 'fonts.gstatic.com' === $host ) {
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
 * @param mixed $urls          Resource hints, including unknown foreign values; another filter callback may pass a non-array.
 * @param mixed $relation_type The relation type the URLs are printed for.
 * @return mixed Original entries plus at most 100 origins for core's host-only DNS output, or the incoming value when it is not an array.
 */
function es_optimizer_add_dns_prefetch_resource_hints( mixed $urls, mixed $relation_type = '' ): mixed {
	if ( ! is_array( $urls ) || 'dns-prefetch' !== $relation_type || ! es_optimizer_is_frontend_request() ) {
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
 * Index existing string href values without changing or coercing foreign hints.
 *
 * Callers build this set once, then add each new href as they append a hint.
 * The domain-list budget bounds plugin additions; foreign input is not truncated.
 *
 * @since 2.2.0
 * @param array<int|string, mixed> $urls Existing resource hints.
 * @return array<int|string, true> Exact string href membership set.
 */
function es_optimizer_get_resource_hint_hrefs( array $urls ): array {
	$hrefs = array();

	foreach ( array_map( 'es_optimizer_get_resource_hint_href', $urls ) as $href ) {
		if ( null !== $href ) {
			$hrefs[ $href ] = true;
		}
	}

	return $hrefs;
}

/**
 * Get the href of one resource hint.
 *
 * @since 2.3.0
 * @param mixed $url A resource hint: a URL string, or an array with an href.
 * @return string|null The href, or null when the hint has no string href.
 */
function es_optimizer_get_resource_hint_href( mixed $url ): ?string {
	if ( is_array( $url ) ) {
		return isset( $url['href'] ) && is_string( $url['href'] ) ? $url['href'] : null;
	}

	return is_string( $url ) ? $url : null;
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
 * @since 2.2.0
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
 * @since 2.2.0
 * @param mixed $enabled Incoming value from WordPress or another plugin.
 * @return mixed False when disabled; otherwise the original value.
 */
function es_optimizer_filter_post_via_email( mixed $enabled ): mixed {
	return es_optimizer_is_option_enabled( 'disable_post_via_email' ) ? false : $enabled;
}
