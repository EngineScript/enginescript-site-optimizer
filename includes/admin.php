<?php
/**
 * Admin settings page and Settings API registration.
 *
 * @package EngineScript_Site_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Initialize the plugin settings.
 *
 * @since 1.0.0
 */
function es_optimizer_init_settings(): void {
	register_setting(
		'es_optimizer_settings',
		'es_optimizer_options',
		[
			'sanitize_callback' => 'es_optimizer_validate_options',
			'default'           => es_optimizer_get_default_options(),
			'show_in_rest'      => false,
			'type'              => 'array',
		]
	);

	es_optimizer_register_settings_sections();
}

/**
 * Register all settings sections and fields.
 *
 * @since 2.0.0
 */
function es_optimizer_register_settings_sections(): void {
	$page = 'es-optimizer-settings';

	foreach ( es_optimizer_get_settings_sections() as $section_id => $section_title ) {
		add_settings_section(
			$section_id,
			esc_html( $section_title ),
			'__return_null',
			$page
		);
	}

	foreach ( es_optimizer_get_settings_fields() as $field ) {
		if ( 'textarea' === $field['type'] ) {
			es_optimizer_register_textarea_field( $page, $field['section'], $field['option'], $field['title'], $field['description'] );
			continue;
		}

		es_optimizer_register_checkbox_field( $page, $field['section'], $field['option'], $field['title'] );
	}
}

/**
 * Get settings section labels.
 *
 * @since 2.0.0
 * @return array<string, string> Section labels keyed by section ID.
 */
function es_optimizer_get_settings_sections(): array {
	return [
		'es_optimizer_performance'         => __( 'Performance Optimizations', 'enginescript-site-optimizer' ),
		'es_optimizer_header_cleanup'      => __( 'Header Cleanup', 'enginescript-site-optimizer' ),
		'es_optimizer_additional_features' => __( 'Additional Features', 'enginescript-site-optimizer' ),
	];
}

/**
 * Get settings field definitions.
 *
 * @since 2.0.0
 * @return array<int, array{type: string, section: string, option: string, title: string, description: string}>
 */
function es_optimizer_get_settings_fields(): array {
	return array_merge(
		es_optimizer_get_performance_settings_fields(),
		es_optimizer_get_header_cleanup_settings_fields(),
		es_optimizer_get_additional_settings_fields()
	);
}

/**
 * Get performance settings field definitions.
 *
 * @since 2.0.0
 * @return array<int, array{type: string, section: string, option: string, title: string, description: string}>
 */
function es_optimizer_get_performance_settings_fields(): array {
	$section = 'es_optimizer_performance';

	return [
		es_optimizer_get_checkbox_field_definition( $section, 'disable_emojis', __( 'Disable WordPress Emojis', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'remove_jquery_migrate', __( 'Remove jQuery Migrate', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'disable_classic_theme_styles', __( 'Disable Classic Theme Styles', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'heartbeat_control', __( 'Limit Heartbeat', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'disable_remote_patterns', __( 'Disable Pattern Directory Patterns', 'enginescript-site-optimizer' ) ),
	];
}

/**
 * Get header cleanup settings field definitions.
 *
 * @since 2.0.0
 * @return array<int, array{type: string, section: string, option: string, title: string, description: string}>
 */
function es_optimizer_get_header_cleanup_settings_fields(): array {
	$section = 'es_optimizer_header_cleanup';

	return [
		es_optimizer_get_checkbox_field_definition( $section, 'remove_wp_version', __( 'Remove WordPress Version', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'remove_rsd_link', __( 'Remove RSD Link', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'remove_shortlink', __( 'Remove Shortlink', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'remove_recent_comments_style', __( 'Remove Recent Comments Style', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'remove_rest_api_link', __( 'Remove REST API Link', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'remove_oembed_links', __( 'Remove oEmbed Discovery Links', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'remove_feed_links', __( 'Remove Feed Links', 'enginescript-site-optimizer' ) ),
	];
}

/**
 * Get additional settings field definitions.
 *
 * @since 2.0.0
 * @return array<int, array{type: string, section: string, option: string, title: string, description: string}>
 */
function es_optimizer_get_additional_settings_fields(): array {
	$section = 'es_optimizer_additional_features';

	return [
		es_optimizer_get_checkbox_field_definition( $section, 'disable_jetpack_ads', __( 'Disable Jetpack Promotions', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'disable_post_via_email', __( 'Disable Post via Email', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'disable_ai_features', __( 'Disable WordPress AI Support', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_checkbox_field_definition( $section, 'enable_preconnect', __( 'Enable Preconnect', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_textarea_field_definition(
			$section,
			'preconnect_domains',
			__( 'Preconnect Domains', 'enginescript-site-optimizer' ),
			__( 'Use preconnect for domains that host critical resources. Enter one HTTPS domain per line, with no port or port 443 only. Do not include file paths, query parameters, fragments, or credentials. Invalid lines are removed on save; an all-invalid list is cleared. Domain checks do not verify DNS destinations.', 'enginescript-site-optimizer' ) . ' ' . es_optimizer_get_domain_list_limits_description()
		),
		es_optimizer_get_checkbox_field_definition( $section, 'enable_dns_prefetch', __( 'Enable DNS Prefetch', 'enginescript-site-optimizer' ) ),
		es_optimizer_get_textarea_field_definition(
			$section,
			'dns_prefetch_domains',
			__( 'DNS Prefetch Domains', 'enginescript-site-optimizer' ),
			__( 'DNS prefetch performs only a DNS lookup of the domain name; ports do not affect the hint. Enter one HTTPS domain per line without file paths, query parameters, fragments, or credentials. Invalid lines are removed on save; an all-invalid list is cleared. Domain checks do not verify DNS destinations.', 'enginescript-site-optimizer' ) . ' ' . es_optimizer_get_domain_list_limits_description()
		),
	];
}

/**
 * Describe the finite domain-list policy shared by both textarea controls.
 *
 * @since 2.2.0
 * @return string Localized plain-text instructions.
 */
function es_optimizer_get_domain_list_limits_description(): string {
	return sprintf(
		/* translators: 1: Maximum bytes per domain list, 2: Maximum nonblank lines, 3: Maximum bytes per nonblank line. */
		__( 'Limits: %1$d bytes per list, %2$d nonblank lines, and %3$d bytes per nonblank line. Duplicate and invalid lines count toward the limit. Oversize submissions keep your previous list.', 'enginescript-site-optimizer' ),
		ES_SITE_OPTIMIZER_MAX_DOMAIN_LIST_BYTES,
		ES_SITE_OPTIMIZER_MAX_DOMAIN_LINES,
		ES_SITE_OPTIMIZER_MAX_DOMAIN_LINE_BYTES
	);
}

/**
 * Get optional domain suggestions for display in settings help only.
 *
 * @since 2.3.0
 * @param string $option_name Domain-list option name.
 * @return array<int, string> Suggested domains, never used as saved defaults.
 */
function es_optimizer_get_domain_suggestions( string $option_name ): array {
	return match ( $option_name ) {
		'preconnect_domains' => [
			'https://fonts.googleapis.com',
			'https://fonts.gstatic.com',
			'https://s.w.org', // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Selectable help text only; no remote asset is loaded.
			'https://wordpress.com',
			'https://cdnjs.cloudflare.com', // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Selectable help text only; no remote asset is loaded.
			'https://www.googletagmanager.com',
		],
		'dns_prefetch_domains' => [ 'https://adservice.google.com' ],
		default => [],
	};
}

/**
 * Get the title of a settings field.
 *
 * @since 2.4.0
 * @param string $option_name Option name.
 * @return string Field title, or an empty string for an unknown option.
 */
function es_optimizer_get_field_title( string $option_name ): string {
	foreach ( es_optimizer_get_settings_fields() as $field ) {
		if ( $field['option'] === $option_name ) {
			return $field['title'];
		}
	}

	return '';
}

/**
 * Get the help text shown under each setting.
 *
 * Every setting explains what it does, its effect on the site, and its
 * performance gain or cost, so that the person changing it can decide well.
 *
 * @since 2.4.0
 * @return array<string, array{what: string, effect: string, performance: string}> Help text keyed by option name.
 */
function es_optimizer_get_option_help(): array {
	return [
		'disable_emojis'               => [
			'what'        => __( 'Stops WordPress from adding its emoji detection script and emoji styles to every page, the admin area, and embedded posts, and from replacing emoji in feeds and emails with images from WordPress.org.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Emoji still appear, drawn by the visitor\'s own device, which every current browser and operating system supports. On very old systems without emoji fonts, some emoji may show as empty boxes. Emoji in feeds and emails stay as characters instead of images.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Removes a small inline script and its styles from every page, and the check that script runs in the browser: a small gain for visitors. No change on the server.', 'enginescript-site-optimizer' ),
		],
		'remove_jquery_migrate'        => [
			'what'        => __( 'Stops loading jQuery Migrate on the front end when a theme or plugin loads jQuery. jQuery Migrate restores functions that newer jQuery versions removed. The admin area is not changed.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Front-end code written for old jQuery versions can stop working, which shows as broken sliders, menus, or forms and as errors in the browser console. Check the interactive parts of the site after turning this on. Pages that do not load jQuery are not affected.', 'enginescript-site-optimizer' ),
			'performance' => __( 'One script file fewer to download and run on every page that loads jQuery: a small gain for visitors. No change on the server.', 'enginescript-site-optimizer' ),
		],
		'disable_classic_theme_styles' => [
			'what'        => __( 'Removes the small classic-theme stylesheet that WordPress adds on the front end for themes without a theme.json file. It gives the Button and File blocks their default look. Block themes never load it, so on them this setting changes nothing. The editor is not changed.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Buttons and File blocks in your content may lose their default spacing and colors if the theme does not style them itself. A stylesheet that another plugin loads on top of this one will not load either.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Removes a few lines of CSS, usually printed inline in the page head, on classic themes: a very small gain for visitors. No change on the server.', 'enginescript-site-optimizer' ),
		],
		'heartbeat_control'            => [
			'what'        => __( 'Limits the Heartbeat, a script that keeps a page in touch with the server through regular background requests. On the front end it stops the script from loading; WordPress loads it there only when a theme or plugin asks for it. On admin screens it sends a request every 120 seconds instead of every 60. The post editor, the site editor, and the Customizer keep the usual timing.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Front-end features that rely on the Heartbeat, such as live chat, live notifications, or front-end editing in some plugins, stop updating, and their scripts may not load. In the admin area, dashboard widgets that refresh through the Heartbeat update half as often, and the notice that your login has expired can appear up to two minutes later. Post locking and autosave in the editors are not changed.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Each Heartbeat request loads WordPress in full on the server and is never served from a page cache. Halving them on admin screens, and removing them from the front end, saves server work, which adds up when several people keep the admin area open. Visitors see no difference in page speed.', 'enginescript-site-optimizer' ),
		],
		'disable_remote_patterns'      => [
			'what'        => __( 'Stops the block editor from loading patterns from the WordPress.org Pattern Directory: the general and featured patterns, and the directory patterns a theme asks for. Patterns that come with your theme, your plugins, or WordPress itself, and the patterns you create, stay available.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'The pattern inserter shows far fewer ready-made patterns, because most of the patterns it offers come from the directory. Patterns already used in your content are not changed.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Saves the requests the server makes to WordPress.org when the editor loads patterns, and the stored copies of their results, which are refreshed about every hour. The editor\'s pattern list can open faster. No change on the front end.', 'enginescript-site-optimizer' ),
		],
		'remove_wp_version'            => [
			'what'        => __( 'Removes the generator tag that names your WordPress version from the page head and from your RSS, Atom, and other feeds. Version numbers on script and stylesheet addresses, and export files, are left as they are.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Hides the version from casual inspection and from scanners that read the tag. It is not real protection, because the version can be worked out in other ways; keeping WordPress up to date matters far more. Nothing on the site looks different.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Removes one line from each page and feed. No measurable speed change.', 'enginescript-site-optimizer' ),
		],
		'remove_rsd_link'              => [
			'what'        => __( 'Removes the Really Simple Discovery (RSD) link from the page head. Old desktop blogging programs used it to find the site\'s XML-RPC address.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'A blogging program that relies on this link may need the XML-RPC address entered by hand. XML-RPC itself keeps working; only the link is removed. Most sites notice no difference.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Removes one line from each page. No measurable speed change.', 'enginescript-site-optimizer' ),
		],
		'remove_shortlink'             => [
			'what'        => __( 'Removes the shortlink, the short ?p=123 address of a post, from the page head and from the Link header that WordPress sends with each page.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Shortlinks still work when someone uses them; they are only no longer advertised. A tool that reads the shortlink from the page uses the full address instead.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Removes one line from each page and one response header. No measurable speed change.', 'enginescript-site-optimizer' ),
		],
		'remove_recent_comments_style' => [
			'what'        => __( 'Stops WordPress from printing a small inline style for the classic Recent Comments widget. WordPress prints it only on classic themes that support widgets.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'The widget\'s links may look slightly different if your theme relied on that style. Block themes and the Latest Comments block are not affected.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Removes a few bytes of CSS from pages that show the widget. No measurable speed change.', 'enginescript-site-optimizer' ),
		],
		'remove_rest_api_link'         => [
			'what'        => __( 'Removes the link to the site\'s REST API address from the page head and from the Link header that WordPress sends with each page.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'The REST API keeps working: the block editor, mobile apps, and plugins that use it are not affected. Only the advertised address is removed, which a few tools and services use to find the API on their own.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Removes one line from each page and one response header. No measurable speed change.', 'enginescript-site-optimizer' ),
		],
		'remove_oembed_links'          => [
			'what'        => __( 'Removes the oEmbed discovery links from the page head of posts and pages. Other sites read them to show one of your posts as an embedded card when someone pastes its link.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'A link to one of your posts pasted into another WordPress site, or into some apps and services, shows as a plain link instead of a card. Embedding videos, posts, and other content from other sites in your own posts keeps working.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Removes two lines from each post and page. No measurable speed change.', 'enginescript-site-optimizer' ),
		],
		'remove_feed_links'            => [
			'what'        => __( 'Removes the links to the site\'s RSS feeds, for posts, comments, and categories, tags, or authors, from the page head.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'The feeds keep working at their usual addresses, but browsers and feed readers can no longer find them from a page, so someone who subscribes by entering the site\'s address may need the feed\'s address instead. Leave this off if people follow the site in a feed reader.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Removes a few lines from each page. No measurable speed change.', 'enginescript-site-optimizer' ),
		],
		'disable_ai_features'          => [
			'what'        => __( 'Tells WordPress that AI features are not supported on this site. WordPress then does not register its built-in AI provider connectors, and refuses AI provider connectors from plugins.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'AI features that rely on WordPress\'s AI support are unavailable, including those in plugins that check for it. Connectors that are not AI providers, such as Akismet, are not affected. Setting the WP_AI_SUPPORT constant to false in wp-config.php has the same effect.', 'enginescript-site-optimizer' ),
			'performance' => __( 'No measurable speed change. WordPress only skips registering its AI provider connectors on each request.', 'enginescript-site-optimizer' ),
		],
		'disable_jetpack_ads'          => [
			'what'        => __( 'Turns off Jetpack\'s promotional notices, its Just-in-Time upgrade messages, and its Blaze prompts to advertise your posts. It does nothing unless Jetpack is installed.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Jetpack keeps working; you no longer see its upgrade and advertising prompts in the admin area. Visitors see no difference.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Jetpack skips loading and showing these messages, which saves a little work and some requests in the admin area. No change on the front end.', 'enginescript-site-optimizer' ),
		],
		'disable_post_via_email'       => [
			'what'        => __( 'Turns off publishing by email: the mail server settings disappear from Settings > Writing and can no longer be saved, and the wp-mail.php address that collects the mail refuses requests.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Nobody can publish posts by sending email to the configured mailbox. Existing posts are not affected. Leave this off if you post by email.', 'enginescript-site-optimizer' ),
			'performance' => __( 'No speed change. It closes a rarely used way into the site.', 'enginescript-site-optimizer' ),
		],
		'enable_preconnect'            => [
			'what'        => __( 'Adds a preconnect hint to the page head for each domain in the Preconnect Domains list, so the browser opens a connection to those servers before it needs them. Front-end pages only; the admin area and the login page are skipped.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Files from those domains, such as web fonts, can start downloading sooner. A listed domain that the page does not use wastes a connection.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Can save one or more network round trips the first time each listed domain is used, which helps most on slow or distant connections. Each hint costs the browser a connection, so list only the few domains that serve files a page needs early. No measurable cost on the server.', 'enginescript-site-optimizer' ),
		],
		'preconnect_domains'           => [
			'what'        => __( 'The domains that get a preconnect hint while Enable Preconnect is on.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Only the domains entered here get hints. The suggestions below are examples and are never added on their own.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Two or three domains are usually enough. More hints compete for the browser\'s connections and can slow down the files that matter most.', 'enginescript-site-optimizer' ),
		],
		'enable_dns_prefetch'          => [
			'what'        => __( 'Adds a DNS prefetch hint to the page head for each domain in the DNS Prefetch Domains list, so the browser looks up those domain names before it needs them. Front-end pages only; the admin area and the login page are skipped.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Requests to those domains start slightly sooner. Unlike preconnect, no connection is opened, so it suits domains that are used later or only on some pages.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Saves the name lookup, often a few tens of milliseconds, the first time each listed domain is used. It costs the browser very little, even for a domain that ends up unused. No measurable cost on the server.', 'enginescript-site-optimizer' ),
		],
		'dns_prefetch_domains'         => [
			'what'        => __( 'The domains that get a DNS prefetch hint while Enable DNS Prefetch is on.', 'enginescript-site-optimizer' ),
			'effect'      => __( 'Only the domains entered here get hints. The suggestion below is an example and is never added on its own.', 'enginescript-site-optimizer' ),
			'performance' => __( 'Lookups are cheap, so a longer list does little harm, but each entry only helps if pages request files from that domain.', 'enginescript-site-optimizer' ),
		],
	];
}

/**
 * Print the three-part help text of a setting.
 *
 * @since 2.4.0
 * @param string $option_name Option name.
 */
function es_optimizer_render_option_help( string $option_name ): void {
	$help = es_optimizer_get_option_help()[ $option_name ] ?? null;

	if ( null === $help ) {
		return;
	}

	$labels = [
		'what'        => __( 'What it does:', 'enginescript-site-optimizer' ),
		'effect'      => __( 'Effect on your site:', 'enginescript-site-optimizer' ),
		'performance' => __( 'Performance:', 'enginescript-site-optimizer' ),
	];

	foreach ( $labels as $part => $label ) {
		printf( '<p><strong>%1$s</strong> %2$s</p>', esc_html( $label ), esc_html( $help[ $part ] ) );
	}
}

/**
 * Build a checkbox settings field definition.
 *
 * A checkbox has no one-line description: its help text comes from
 * es_optimizer_get_option_help().
 *
 * @since 2.0.0
 * @since 2.4.0 The description parameter was removed.
 * @param string $section     Settings section ID.
 * @param string $option_name Option name.
 * @param string $title       Field title.
 * @return array{type: string, section: string, option: string, title: string, description: string}
 */
function es_optimizer_get_checkbox_field_definition( string $section, string $option_name, string $title ): array {
	return es_optimizer_get_field_definition( 'checkbox', $section, $option_name, $title, '' );
}

/**
 * Build a textarea settings field definition.
 *
 * @since 2.0.0
 * @param string $section     Settings section ID.
 * @param string $option_name Option name.
 * @param string $title       Field title.
 * @param string $description Field description.
 * @return array{type: string, section: string, option: string, title: string, description: string}
 */
function es_optimizer_get_textarea_field_definition( string $section, string $option_name, string $title, string $description ): array {
	return es_optimizer_get_field_definition( 'textarea', $section, $option_name, $title, $description );
}

/**
 * Build a settings field definition.
 *
 * @since 2.0.0
 * @param string $type        Field type.
 * @param string $section     Settings section ID.
 * @param string $option_name Option name.
 * @param string $title       Field title.
 * @param string $description Field description.
 * @return array{type: string, section: string, option: string, title: string, description: string}
 */
function es_optimizer_get_field_definition( string $type, string $section, string $option_name, string $title, string $description ): array {
	return [
		'type'        => $type,
		'section'     => $section,
		'option'      => $option_name,
		'title'       => $title,
		'description' => $description,
	];
}

/**
 * Store a settings field description for render callbacks.
 *
 * @since 2.0.0
 * @param string $option_name Option name.
 * @param string $description Field description.
 */
function es_optimizer_register_field_description( string $option_name, string $description ): void {
	global $es_optimizer_field_descriptions;

	if ( ! isset( $es_optimizer_field_descriptions ) || ! is_array( $es_optimizer_field_descriptions ) ) {
		$es_optimizer_field_descriptions = [];
	}

	$es_optimizer_field_descriptions[ $option_name ] = $description;
}

/**
 * Get a settings field description for render callbacks.
 *
 * @since 2.0.0
 * @param string $option_name Option name.
 * @return string Field description.
 */
function es_optimizer_get_field_description( string $option_name ): string {
	global $es_optimizer_field_descriptions;

	if ( ! isset( $es_optimizer_field_descriptions ) || ! is_array( $es_optimizer_field_descriptions ) ) {
		return '';
	}

	if ( ! isset( $es_optimizer_field_descriptions[ $option_name ] ) || ! is_scalar( $es_optimizer_field_descriptions[ $option_name ] ) ) {
		return '';
	}

	return (string) $es_optimizer_field_descriptions[ $option_name ];
}

/**
 * Build a field ID for a settings option.
 *
 * @since 2.0.0
 * @param string $option_name Option name.
 * @return string Field ID.
 */
function es_optimizer_get_field_id( string $option_name ): string {
	return "es_optimizer_field_{$option_name}";
}

/**
 * Get an option name from Settings API field arguments.
 *
 * Checkbox fields name their option directly; text areas carry it in the
 * field ID that WordPress uses for their label.
 *
 * @since 2.0.0
 * @since 2.4.0 Reads the `option` argument first.
 * @param array{option?: mixed, label_for?: string, class?: string} $args Field arguments.
 * @return string Option name.
 */
function es_optimizer_get_option_name_from_field_args( array $args ): string {
	if ( isset( $args['option'] ) && is_string( $args['option'] ) ) {
		return $args['option'];
	}

	$field_id = $args['label_for'] ?? '';
	$prefix   = 'es_optimizer_field_';

	if ( str_starts_with( $field_id, $prefix ) ) {
		return substr( $field_id, strlen( $prefix ) );
	}

	return '';
}

/**
 * Register a checkbox settings field.
 *
 * The field is registered without `label_for`, so WordPress prints its title
 * as plain text rather than as a label. A click on the title or the help text
 * then cannot toggle the setting; only the checkbox itself does.
 *
 * @since 2.0.0
 * @since 2.4.0 The description parameter was removed; the title is no longer a label.
 * @param string $page        Settings page slug.
 * @param string $section     Settings section ID.
 * @param string $option_name Option name.
 * @param string $title       Field title.
 */
function es_optimizer_register_checkbox_field( string $page, string $section, string $option_name, string $title ): void {
	add_settings_field(
		$option_name,
		esc_html( $title ),
		'es_optimizer_render_checkbox_field',
		$page,
		$section,
		[
			'option' => $option_name,
			'class'  => 'es-optimizer-field',
		]
	);
}

/**
 * Register a textarea settings field.
 *
 * @since 2.0.0
 * @param string $page        Settings page slug.
 * @param string $section     Settings section ID.
 * @param string $option_name Option name.
 * @param string $title       Field title.
 * @param string $description Field description.
 */
function es_optimizer_register_textarea_field( string $page, string $section, string $option_name, string $title, string $description ): void {
	es_optimizer_register_field_description( $option_name, $description );

	add_settings_field(
		$option_name,
		esc_html( $title ),
		'es_optimizer_render_textarea_field',
		$page,
		$section,
		[
			'label_for' => es_optimizer_get_field_id( $option_name ),
			'class'     => 'es-optimizer-field',
		]
	);
}

/**
 * Add settings page to the admin menu.
 *
 * @since 1.0.0
 */
function es_optimizer_add_settings_page(): void {
	add_options_page(
		esc_html__( 'Site Optimizer Settings', 'enginescript-site-optimizer' ),
		esc_html__( 'Site Optimizer', 'enginescript-site-optimizer' ),
		'manage_options',
		'es-optimizer-settings',
		'es_optimizer_settings_page'
	);
}

/**
 * Render the settings page.
 *
 * @since 1.0.0
 */
function es_optimizer_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'enginescript-site-optimizer' ) );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Site Optimizer Settings', 'enginescript-site-optimizer' ); ?></h1>
		<p><?php esc_html_e( 'Select which optimizations you want to enable and customize the resource hint domains.', 'enginescript-site-optimizer' ); ?></p>

		<form method="post" action="options.php">
			<?php
			settings_fields( 'es_optimizer_settings' );
			do_settings_sections( 'es-optimizer-settings' );
			submit_button( __( 'Save Changes', 'enginescript-site-optimizer' ) );
			?>
		</form>

		<hr>
		<p>
			<?php esc_html_e( 'This plugin is part of the EngineScript project.', 'enginescript-site-optimizer' ); ?>
			<a href="https://github.com/EngineScript/EngineScript" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Visit the EngineScript GitHub page', 'enginescript-site-optimizer' ); ?>
				<span class="screen-reader-text">
					<?php
					/* translators: Hidden accessibility text. */
					esc_html_e( '(opens in a new tab)', 'enginescript-site-optimizer' );
					?>
				</span>
			</a>
		</p>
	</div>
	<?php
}

/**
 * Render a checkbox option field.
 *
 * Only the checkbox toggles the setting. Its label is for screen readers only
 * and takes no space on screen; the help text below is plain text, tied to the
 * checkbox as its description.
 *
 * @since 1.0.0
 * @since 2.4.0 Prints the three-part help text, and no visible label.
 * @param array{option?: mixed, label_for?: string, class?: string} $args Field arguments.
 */
function es_optimizer_render_checkbox_field( array $args ): void {
	$options     = es_optimizer_get_options();
	$option_name = es_optimizer_get_option_name_from_field_args( $args );

	if ( ! in_array( $option_name, es_optimizer_get_boolean_option_keys(), true ) ) {
		return;
	}

	$field_id       = es_optimizer_get_field_id( $option_name );
	$description_id = $field_id . '_description';
	?>
	<label for="<?php echo esc_attr( $field_id ); ?>" class="screen-reader-text"><?php echo esc_html( es_optimizer_get_field_title( $option_name ) ); ?></label>
	<input id="<?php echo esc_attr( $field_id ); ?>" type="checkbox" name="<?php echo esc_attr( "es_optimizer_options[{$option_name}]" ); ?>" value="1" aria-describedby="<?php echo esc_attr( $description_id ); ?>" <?php checked( 1, (int) ( $options[ $option_name ] ?? 0 ) ); ?> />
	<div id="<?php echo esc_attr( $description_id ); ?>" class="description">
		<?php es_optimizer_render_option_help( $option_name ); ?>
	</div>
	<?php
}

/**
 * Render a textarea option field.
 *
 * @since 1.0.0
 * @param array{label_for?: string, class?: string} $args Field arguments.
 */
function es_optimizer_render_textarea_field( array $args ): void {
	$options     = es_optimizer_get_options();
	$option_name = es_optimizer_get_option_name_from_field_args( $args );

	if ( ! array_key_exists( $option_name, es_optimizer_get_default_options() ) ) {
		return;
	}

	$field_id       = es_optimizer_get_field_id( $option_name );
	$description_id = $field_id . '_description';
	$description    = es_optimizer_get_field_description( $option_name );
	$textarea_value = (string) ( $options[ $option_name ] ?? '' );
	$budget_error   = es_optimizer_get_domain_list_budget_error( $textarea_value );
	$suggestions    = es_optimizer_get_domain_suggestions( $option_name );

	if ( '' !== $budget_error ) {
		$description .= ' ' . $budget_error . ' ' . __( 'This saved list exceeds a size limit. It remains displayed here but adds no resource hints until you shorten and save it.', 'enginescript-site-optimizer' );
	} elseif ( 'preconnect_domains' === $option_name && es_optimizer_has_unsupported_preconnect_domains( $textarea_value ) ) {
		$description .= ' ' . __( 'Some saved preconnect entries use unsupported ports. They remain displayed here but are skipped in page hints. Saving removes invalid entries; if none are valid, the list is cleared.', 'enginescript-site-optimizer' );
	}
	?>
	<textarea id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( "es_optimizer_options[{$option_name}]" ); ?>" aria-describedby="<?php echo esc_attr( $description_id ); ?>" rows="5" cols="50" class="large-text code"><?php echo esc_textarea( $textarea_value ); ?></textarea>
	<div id="<?php echo esc_attr( $description_id ); ?>" class="description">
		<?php es_optimizer_render_option_help( $option_name ); ?>
		<p><strong><?php esc_html_e( 'Format:', 'enginescript-site-optimizer' ); ?></strong> <?php echo esc_html( $description ); ?></p>
		<?php if ( [] !== $suggestions ) : ?>
			<p><?php esc_html_e( 'Optional suggestions: copy only the domains your site uses into the field above, one per line. This list starts empty; suggestions are not enabled automatically. Enable the setting and save your list to add hints.', 'enginescript-site-optimizer' ); ?></p>
			<pre><?php echo esc_html( implode( "\n", $suggestions ) ); ?></pre>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Add settings link to plugins page.
 *
 * @since 1.0.0
 * @param mixed $links Plugin action links; another filter callback may pass a non-array.
 * @return mixed Links with the settings link first, or the incoming value when it is not an array.
 */
function es_optimizer_add_settings_link( mixed $links ): mixed {
	if ( ! is_array( $links ) ) {
		return $links;
	}

	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=es-optimizer-settings' ) ) . '">' . esc_html__( 'Settings', 'enginescript-site-optimizer' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
