<?php
/**
 * Autoloaded options: listing them, switching autoloading off and back on, and
 * restoring every change when the plugin is uninstalled.
 *
 * The plugin changes only the autoload flag of an option, never its value. Each
 * change is logged with the flag it replaced, so that it can be undone exactly.
 * uninstall.php loads this file too, so it depends on no other plugin file.
 *
 * @package EngineScript_Site_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Option that logs the options whose autoloading the plugin switched off.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION = 'es_optimizer_autoload_changes';

/**
 * Network option that lists the sites with logged changes, for the warning on the network Plugins screen.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_SITES_OPTION = 'es_optimizer_autoload_sites';

/**
 * Option that holds the end time of a usage recording; it exists, autoloaded, only while recording.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION = 'es_optimizer_autoload_recording';

/**
 * Option that holds the results of the last usage recording; never autoloaded.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION = 'es_optimizer_autoload_usage';

/**
 * Most options changed by one request.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_MAX_CHANGES = 100;

/**
 * Options listed per page.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_PER_PAGE = 100;

/**
 * Longest option name WordPress stores.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_MAX_NAME_LENGTH = 191;

/**
 * Autoload size above which WordPress's Site Health check warns, in bytes.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_HEALTH_LIMIT = 800000;

/**
 * Get the autoload flags that make WordPress load an option on every request.
 *
 * @since 2.5.0
 * @return array<int, string> Flag values.
 */
function es_optimizer_autoload_get_loading_flags(): array {
	return array_values( wp_autoload_values_to_autoload() );
}

/**
 * Options that WordPress creates and reads on almost every request.
 *
 * WordPress's installed defaults, plus the options core adds later.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_CORE_OPTIONS = [
	'active_plugins',
	'admin_email',
	'admin_email_lifespan',
	'auto_plugin_theme_update_emails',
	'auto_update_core_dev',
	'auto_update_core_major',
	'auto_update_core_minor',
	'avatar_default',
	'avatar_rating',
	'blog_charset',
	'blog_public',
	'blogdescription',
	'blogname',
	'can_compress_scripts',
	'category_base',
	'close_comments_days_old',
	'close_comments_for_old_posts',
	'comment_max_links',
	'comment_moderation',
	'comment_order',
	'comment_previously_approved',
	'comment_registration',
	'comments_notify',
	'comments_per_page',
	'cron',
	'current_theme',
	'date_format',
	'db_version',
	'default_category',
	'default_comment_status',
	'default_comments_page',
	'default_email_category',
	'default_link_category',
	'default_ping_status',
	'default_pingback_flag',
	'default_post_format',
	'default_role',
	'disallowed_keys',
	'finished_splitting_shared_terms',
	'fresh_site',
	'gmt_offset',
	'hack_file',
	'home',
	'html_type',
	'image_default_align',
	'image_default_link_type',
	'image_default_size',
	'initial_db_version',
	'large_size_h',
	'large_size_w',
	'link_manager_enabled',
	'links_updated_date_format',
	'mailserver_login',
	'mailserver_pass',
	'mailserver_port',
	'mailserver_url',
	'medium_large_size_h',
	'medium_large_size_w',
	'medium_size_h',
	'medium_size_w',
	'moderation_keys',
	'moderation_notify',
	'nav_menu_options',
	'page_comments',
	'page_for_posts',
	'page_on_front',
	'permalink_structure',
	'ping_sites',
	'posts_per_page',
	'posts_per_rss',
	'recently_activated',
	'recently_edited',
	'require_name_email',
	'rewrite_rules',
	'rss_use_excerpt',
	'show_avatars',
	'show_comments_cookies_opt_in',
	'show_on_front',
	'sidebars_widgets',
	'site_icon',
	'site_logo',
	'siteurl',
	'start_of_week',
	'sticky_posts',
	'stylesheet',
	'tag_base',
	'template',
	'theme_switched',
	'thread_comments',
	'thread_comments_depth',
	'thumbnail_crop',
	'thumbnail_size_h',
	'thumbnail_size_w',
	'time_format',
	'timezone_string',
	'uninstall_plugins',
	'upload_path',
	'upload_url_path',
	'uploads_use_yearmonth_folders',
	'use_balanceTags',
	'use_smilies',
	'use_trackback',
	'users_can_register',
	'wp_attachment_pages_enabled',
	'wp_force_deactivated_plugins',
	'wp_notes_notify',
	'wp_page_for_privacy_policy',
	'WPLANG',
];

/**
 * Get the options that WordPress creates and reads on almost every request.
 *
 * Switching one of these off would only add a database query to each page, so
 * they are locked. The list is WordPress's installed defaults plus the options
 * core adds later.
 *
 * @since 2.5.0
 * @return array<int, string> Option names.
 */
function es_optimizer_autoload_get_core_options(): array {
	return ES_SITE_OPTIMIZER_AUTOLOAD_CORE_OPTIONS;
}

/**
 * Check whether an option is locked: a core option, or one this plugin reads on every request.
 *
 * @since 2.5.0
 * @param string $name Option name.
 * @return bool Whether the option may not be switched off.
 */
function es_optimizer_autoload_is_locked( string $name ): bool {
	// WordPress refuses to change these two names at all; 'es_optimizer_options' is read on every request.
	if ( in_array( $name, [ 'alloptions', 'notoptions', 'es_optimizer_options', ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION ], true ) ) {
		return true;
	}

	if ( in_array( $name, es_optimizer_autoload_get_core_options(), true ) ) {
		return true;
	}

	if ( str_starts_with( $name, 'widget_' ) || str_starts_with( $name, 'theme_mods_' ) ) {
		return true;
	}

	$database = es_optimizer_autoload_db();

	return null !== $database && $database->prefix . 'user_roles' === $name;
}

/**
 * Check whether a submitted value can be an option name.
 *
 * @since 2.5.0
 * @param mixed $name Submitted value.
 * @return bool Whether it is a non-empty string within WordPress's name length.
 * @psalm-assert-if-true non-empty-string $name
 * @phpstan-assert-if-true non-empty-string $name
 */
function es_optimizer_autoload_is_valid_name( mixed $name ): bool {
	return is_string( $name ) && '' !== $name && strlen( $name ) <= ES_SITE_OPTIMIZER_AUTOLOAD_MAX_NAME_LENGTH;
}

/**
 * Get WordPress's database object, when it is the expected one.
 *
 * @since 2.5.0
 * @global mixed $wpdb WordPress database abstraction object.
 * @return wpdb|null The database object, or null when it is unavailable.
 */
function es_optimizer_autoload_db(): ?wpdb {
	global $wpdb;

	return $wpdb instanceof wpdb ? $wpdb : null;
}

/**
 * Read a database value as an integer.
 *
 * @since 2.5.0
 * @param mixed $value Value from a query result.
 * @return int The number, or 0 when the value is not numeric.
 */
function es_optimizer_autoload_to_int( mixed $value ): int {
	return is_numeric( $value ) ? (int) $value : 0;
}

/**
 * Read a database value as text.
 *
 * @since 2.5.0
 * @param mixed $value Value from a query result.
 * @return string The text, or an empty string when the value is not scalar.
 */
function es_optimizer_autoload_to_string( mixed $value ): string {
	return is_scalar( $value ) ? (string) $value : '';
}

/**
 * Read a WordPress path constant as text.
 *
 * @since 2.5.0
 * @param string $name Constant name, such as WP_PLUGIN_DIR.
 * @return string Its value, or an empty string when it is not defined.
 */
function es_optimizer_autoload_constant( string $name ): string {
	return defined( $name ) ? es_optimizer_autoload_to_string( constant( $name ) ) : '';
}

/**
 * Get the autoloaded total of the current site.
 *
 * @since 2.5.0
 * @return array{count: int, bytes: int} Number of autoloaded options and their stored size in bytes.
 */
function es_optimizer_autoload_get_summary(): array {
	$database = es_optimizer_autoload_db();
	$flags    = es_optimizer_autoload_get_loading_flags();
	$summary  = [
		'count' => 0,
		'bytes' => 0,
	];

	if ( null === $database || [] === $flags ) {
		return $summary;
	}

	$placeholders = implode( ',', array_fill( 0, count( $flags ), '%s' ) );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The live total is the point of the page.
	$row = $database->get_row(
		(string) $database->prepare(
			"SELECT COUNT(*) AS option_count, COALESCE(SUM(LENGTH(option_value)), 0) AS option_bytes FROM %i WHERE autoload IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Only %s placeholders are interpolated.
			array_merge( [ $database->options ], $flags )
		),
		'ARRAY_A'
	);

	if ( is_array( $row ) ) {
		$summary['count'] = es_optimizer_autoload_to_int( $row['option_count'] ?? 0 );
		$summary['bytes'] = es_optimizer_autoload_to_int( $row['option_bytes'] ?? 0 );
	}

	return $summary;
}

/**
 * Get one page of autoloaded options, largest first.
 *
 * @since 2.5.0
 * @param int    $page   Page number, from 1.
 * @param string $search Text the option name must contain, or an empty string.
 * @return array{total: int, rows: array<int, array{name: string, bytes: int, autoload: string}>} Matching total and the page's rows.
 */
function es_optimizer_autoload_get_page( int $page, string $search ): array {
	$database = es_optimizer_autoload_db();
	$flags    = es_optimizer_autoload_get_loading_flags();
	$result   = [
		'total' => 0,
		'rows'  => [],
	];

	if ( null === $database || [] === $flags ) {
		return $result;
	}

	$where = 'autoload IN (' . implode( ',', array_fill( 0, count( $flags ), '%s' ) ) . ')';
	$args  = $flags;

	if ( '' !== $search ) {
		$where .= ' AND option_name LIKE %s';
		$args[] = '%' . $database->esc_like( $search ) . '%';
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- A live list; $where holds placeholders only, and the values array fills them.
	$result['total'] = es_optimizer_autoload_to_int( $database->get_var( (string) $database->prepare( "SELECT COUNT(*) FROM %i WHERE $where", array_merge( [ $database->options ], $args ) ) ) );
	$found           = $database->get_results(
		(string) $database->prepare(
			"SELECT option_name, LENGTH(option_value) AS option_bytes, autoload FROM %i WHERE $where ORDER BY option_bytes DESC, option_name ASC LIMIT %d OFFSET %d",
			array_merge( [ $database->options ], $args, [ ES_SITE_OPTIMIZER_AUTOLOAD_PER_PAGE, max( 0, $page - 1 ) * ES_SITE_OPTIMIZER_AUTOLOAD_PER_PAGE ] )
		),
		'ARRAY_A'
	);
	// phpcs:enable

	foreach ( is_array( $found ) ? $found : [] as $row ) {
		if ( isset( $row['option_name'], $row['autoload'] ) ) {
			$result['rows'][] = [
				'name'     => es_optimizer_autoload_to_string( $row['option_name'] ),
				'bytes'    => es_optimizer_autoload_to_int( $row['option_bytes'] ?? 0 ),
				'autoload' => es_optimizer_autoload_to_string( $row['autoload'] ),
			];
		}
	}

	return $result;
}

/**
 * Read the stored autoload flag and size of some options.
 *
 * @since 2.5.0
 * @param array<int, string> $names Option names.
 * @return array<array-key, array{autoload: string, bytes: int}>|null Flag and size by name, without missing options; null when the read fails.
 */
function es_optimizer_autoload_get_flags( array $names ): ?array {
	$database = es_optimizer_autoload_db();

	if ( [] === $names ) {
		return [];
	}

	if ( null === $database ) {
		return null;
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The stored flag, not a cached copy, decides each change.
	$found = $database->get_results(
		(string) $database->prepare(
			'SELECT option_name, autoload, LENGTH(option_value) AS option_bytes FROM %i WHERE option_name IN (' . implode( ',', array_fill( 0, count( $names ), '%s' ) ) . ')',
			array_merge( [ $database->options ], $names )
		),
		'ARRAY_A'
	);
	// phpcs:enable

	if ( ! is_array( $found ) ) {
		return null;
	}

	$flags = [];

	foreach ( $found as $row ) {
		if ( isset( $row['option_name'], $row['autoload'] ) ) {
			$flags[ es_optimizer_autoload_to_string( $row['option_name'] ) ] = [
				'autoload' => es_optimizer_autoload_to_string( $row['autoload'] ),
				'bytes'    => es_optimizer_autoload_to_int( $row['option_bytes'] ?? 0 ),
			];
		}
	}

	return $flags;
}

/**
 * Get the log of options whose autoloading the plugin switched off.
 *
 * An entry that is not well formed is dropped. The earlier flag must be one
 * that makes WordPress load the option, because only such an option is ever
 * switched off, so not even an edited log can restore any other value.
 *
 * @since 2.5.0
 * @return array<array-key, array{previous: string, time: int}> The flag each option had before, and when it was switched off, by name. A numeric name is an integer key, as PHP stores it.
 */
function es_optimizer_autoload_get_log(): array {
	$stored = get_option( ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION, [] );
	$log    = [];
	$flags  = array_merge( [ 'on', 'yes', 'auto', 'auto-on' ], es_optimizer_autoload_get_loading_flags() );

	if ( ! is_array( $stored ) ) {
		return [];
	}

	foreach ( $stored as $key => $entry ) {
		$name = (string) $key;

		if ( ! es_optimizer_autoload_is_valid_name( $name ) || ! is_array( $entry ) ) {
			continue;
		}

		$previous = $entry['previous'] ?? null;

		if ( ! is_string( $previous ) || ! in_array( $previous, $flags, true ) ) {
			continue;
		}

		$log[ $name ] = [
			'previous' => $previous,
			'time'     => isset( $entry['time'] ) && is_int( $entry['time'] ) ? $entry['time'] : 0,
		];
	}

	return $log;
}

/**
 * Store the log, never autoloaded, and keep the network's list of sites with changes current.
 *
 * @since 2.5.0
 * @param array<array-key, array{previous: string, time: int}> $log The whole log.
 * @return bool Whether the log is stored as given.
 */
function es_optimizer_autoload_save_log( array $log ): bool {
	if ( [] === $log ) {
		delete_option( ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION );

		return es_optimizer_autoload_confirm_saved( false === get_option( ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION, false ), false );
	}

	update_option( ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION, $log, false );

	return es_optimizer_autoload_confirm_saved( get_option( ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION ) === $log, true );
}

/**
 * Update the network's list of sites once the log is known to be saved.
 *
 * @since 2.5.0
 * @param bool $saved       Whether the log was stored as given.
 * @param bool $has_changes Whether the stored log has entries.
 * @return bool The value of $saved.
 */
function es_optimizer_autoload_confirm_saved( bool $saved, bool $has_changes ): bool {
	if ( $saved ) {
		es_optimizer_autoload_mark_site( $has_changes );
	}

	return $saved;
}

/**
 * Add the current site to, or remove it from, the network's list of sites with changes.
 *
 * @since 2.5.0
 * @param bool $has_changes Whether the current site has logged changes.
 */
function es_optimizer_autoload_mark_site( bool $has_changes ): void {
	if ( ! is_multisite() ) {
		return;
	}

	$site_id = get_current_blog_id();
	$sites   = es_optimizer_autoload_get_listed_sites();
	$listed  = in_array( $site_id, $sites, true );

	if ( $has_changes === $listed ) {
		return;
	}

	$sites = $has_changes ? array_merge( $sites, [ $site_id ] ) : array_values( array_diff( $sites, [ $site_id ] ) );

	if ( [] === $sites ) {
		delete_site_option( ES_SITE_OPTIMIZER_AUTOLOAD_SITES_OPTION );

		return;
	}

	update_site_option( ES_SITE_OPTIMIZER_AUTOLOAD_SITES_OPTION, $sites );
}

/**
 * Get the network's list of sites with logged changes.
 *
 * @since 2.5.0
 * @return array<int, int> Site IDs.
 */
function es_optimizer_autoload_get_listed_sites(): array {
	return es_optimizer_autoload_to_int_list( get_site_option( ES_SITE_OPTIMIZER_AUTOLOAD_SITES_OPTION, [] ) );
}

/**
 * Keep the integers of a stored list.
 *
 * @since 2.5.0
 * @param mixed $value Stored value.
 * @return array<int, int> Integers, in order.
 */
function es_optimizer_autoload_to_int_list( mixed $value ): array {
	return is_array( $value ) ? array_values( array_filter( $value, is_int( ... ) ) ) : [];
}

/**
 * Turn requested option names into a bounded list of distinct names.
 *
 * @since 2.5.0
 * @param mixed $names Submitted names.
 * @return array{names: array<int, string>, invalid: int} Names to process, and how many entries were not names or were over the limit.
 */
function es_optimizer_autoload_normalize_names( mixed $names ): array {
	if ( ! is_array( $names ) ) {
		return [
			'names'   => [],
			'invalid' => 0,
		];
	}

	$valid   = [];
	$invalid = 0;

	foreach ( $names as $name ) {
		if ( ! es_optimizer_autoload_is_valid_name( $name ) ) {
			++$invalid;
			continue;
		}

		$valid[ $name ] = $name;
	}

	$valid = array_values( $valid );
	$over  = max( 0, count( $valid ) - ES_SITE_OPTIMIZER_AUTOLOAD_MAX_CHANGES );

	return [
		'names'   => array_slice( $valid, 0, ES_SITE_OPTIMIZER_AUTOLOAD_MAX_CHANGES ),
		'invalid' => $invalid + $over,
	];
}

/**
 * Stop WordPress from loading the given options on every request.
 *
 * Each option is checked against its stored flag first. The log is written
 * before any flag changes, so a change can always be undone; an option whose
 * flag could not be changed is taken out of the log again.
 *
 * @since 2.5.0
 * @param mixed $names Requested option names.
 * @return array{changed: array<int, string>, skipped: array<string, int>} Names switched off, and the number skipped for each reason.
 */
function es_optimizer_autoload_switch_off( mixed $names ): array {
	$request = es_optimizer_autoload_normalize_names( $names );
	$result  = [
		'changed' => [],
		'skipped' => [],
	];

	es_optimizer_autoload_count_skipped( $result, 'invalid', $request['invalid'] );

	$stored = es_optimizer_autoload_get_flags( $request['names'] );

	if ( null === $stored ) {
		es_optimizer_autoload_count_skipped( $result, 'failed', count( $request['names'] ) );

		return $result;
	}

	$candidates = es_optimizer_autoload_get_switch_off_candidates( $request['names'], $stored, $result );

	if ( [] === $candidates ) {
		return $result;
	}

	$log   = es_optimizer_autoload_get_log();
	$added = array_diff_key( $candidates, $log );

	foreach ( $added as $name => $previous ) {
		$log[ $name ] = [
			'previous' => $previous,
			'time'     => time(),
		];
	}

	if ( ! es_optimizer_autoload_save_log( $log ) ) {
		es_optimizer_autoload_count_skipped( $result, 'failed', count( $candidates ) );

		return $result;
	}

	$changes = wp_set_option_autoload_values( array_fill_keys( array_keys( $candidates ), false ) );

	foreach ( array_keys( $candidates ) as $name ) {
		if ( true === ( $changes[ $name ] ?? false ) ) {
			$result['changed'][] = $name;
			continue;
		}

		es_optimizer_autoload_count_skipped( $result, 'failed', 1 );

		if ( isset( $added[ $name ] ) ) {
			unset( $log[ $name ] );
		}
	}

	if ( count( $result['changed'] ) !== count( $candidates ) ) {
		es_optimizer_autoload_save_log( $log );
	}

	return $result;
}

/**
 * Select the requested options that may be switched off, with the flag each one has now.
 *
 * @since 2.5.0
 * @param array<int, string>                                              $names  Requested names.
 * @param array<array-key, array{autoload: string, bytes: int}>           $stored Stored flags by name.
 * @param array{changed: array<int, string>, skipped: array<string, int>} $result Result, updated with the reasons for skipping.
 * @return array<string, string> Current flag by name.
 */
function es_optimizer_autoload_get_switch_off_candidates( array $names, array $stored, array &$result ): array {
	$loading    = es_optimizer_autoload_get_loading_flags();
	$candidates = [];

	foreach ( $names as $name ) {
		$reason = match ( true ) {
			es_optimizer_autoload_is_locked( $name ) => 'locked',
			! isset( $stored[ $name ] ) => 'missing',
			! in_array( $stored[ $name ]['autoload'], $loading, true ) => 'not_autoloaded',
			default => '',
		};

		if ( '' === $reason ) {
			$candidates[ $name ] = $stored[ $name ]['autoload'];
			continue;
		}

		es_optimizer_autoload_count_skipped( $result, $reason, 1 );
	}

	return $candidates;
}

/**
 * Let WordPress load logged options on every request again, with the exact flag each had before.
 *
 * An option that is no longer switched off, because something else changed or
 * removed it, is only taken out of the log.
 *
 * @since 2.5.0
 * @param mixed $names Requested option names.
 * @return array{changed: array<int, string>, skipped: array<string, int>} Names restored, and the number skipped for each reason.
 */
function es_optimizer_autoload_switch_on( mixed $names ): array {
	$request = es_optimizer_autoload_normalize_names( $names );
	$result  = [
		'changed' => [],
		'skipped' => [],
	];
	$log     = es_optimizer_autoload_get_log();

	es_optimizer_autoload_count_skipped( $result, 'invalid', $request['invalid'] );

	foreach ( $request['names'] as $name ) {
		if ( ! isset( $log[ $name ] ) ) {
			es_optimizer_autoload_count_skipped( $result, 'not_logged', 1 );
			continue;
		}

		$restored = es_optimizer_autoload_restore_flag( $name, $log[ $name ]['previous'] );

		if ( false === $restored ) {
			es_optimizer_autoload_count_skipped( $result, 'failed', 1 );
			continue;
		}

		unset( $log[ $name ] );

		if ( true === $restored ) {
			$result['changed'][] = $name;
			continue;
		}

		es_optimizer_autoload_count_skipped( $result, 'changed_elsewhere', 1 );
	}

	if ( ! es_optimizer_autoload_save_log( $log ) && [] !== $result['changed'] ) {
		es_optimizer_autoload_count_skipped( $result, 'log_failed', 1 );
	}

	return $result;
}

/**
 * Put back the flag an option had, if the plugin's "off" is still in place.
 *
 * The update applies only while the stored flag is "off", so a flag that
 * something else has changed since is left alone.
 *
 * @since 2.5.0
 * @param string $name     Option name.
 * @param string $previous Flag the option had before the plugin switched it off.
 * @return bool|null True when restored, null when the option is no longer switched off, false when the update failed.
 */
function es_optimizer_autoload_restore_flag( string $name, string $previous ): ?bool {
	$database = es_optimizer_autoload_db();

	if ( null === $database ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Core's setter writes only 'on' and 'off'; the earlier flag, such as 'auto', is restored exactly. The caches are cleared below.
	$updated = $database->query(
		(string) $database->prepare( "UPDATE %i SET autoload = %s WHERE option_name = %s AND autoload = 'off'", $database->options, $previous, $name )
	);

	if ( ! is_int( $updated ) ) {
		return false;
	}

	if ( 0 === $updated ) {
		return null;
	}

	// As core does when it switches autoloading on.
	wp_cache_delete( $name, 'options' );
	wp_cache_delete( 'alloptions', 'options' );

	return true;
}

/**
 * Restore every logged option of the current site, then delete the log.
 *
 * Used when the plugin is uninstalled. The log is kept when an option could
 * not be restored, so that nothing is lost.
 *
 * @since 2.5.0
 * @return array<int, string> Names that could not be restored; empty on success.
 */
function es_optimizer_autoload_restore_all(): array {
	$failed = [];

	foreach ( es_optimizer_autoload_get_log() as $name => $entry ) {
		if ( false === es_optimizer_autoload_restore_flag( (string) $name, $entry['previous'] ) ) {
			$failed[] = (string) $name;
		}
	}

	if ( [] === $failed ) {
		delete_option( ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION );
	}

	return $failed;
}

/**
 * Add to the count of options skipped for a reason.
 *
 * @since 2.5.0
 * @param array{changed: array<int, string>, skipped: array<string, int>} $result Result to update.
 * @param string                                                          $reason Reason code.
 * @param int                                                             $count  Number of options.
 */
function es_optimizer_autoload_count_skipped( array &$result, string $reason, int $count ): void {
	if ( $count > 0 ) {
		$result['skipped'][ $reason ] = ( $result['skipped'][ $reason ] ?? 0 ) + $count;
	}
}

/**
 * Get the state of each logged option.
 *
 * @since 2.5.0
 * @return array<int, array{name: string, previous: string, time: int, state: string, bytes: int}>|null Logged options, oldest first; null when the stored flags cannot be read.
 */
function es_optimizer_autoload_get_logged_rows(): ?array {
	$log    = es_optimizer_autoload_get_log();
	$stored = es_optimizer_autoload_get_flags( array_map( strval( ... ), array_keys( $log ) ) );

	if ( null === $stored ) {
		return null;
	}

	$loading = es_optimizer_autoload_get_loading_flags();
	$rows    = [];

	foreach ( $log as $name => $entry ) {
		$flag  = $stored[ $name ]['autoload'] ?? null;
		$state = match ( true ) {
			null === $flag => 'deleted',
			'off' === $flag => 'off',
			in_array( $flag, $loading, true ) => 'reenabled',
			default => 'changed',
		};

		$rows[] = [
			'name'     => (string) $name,
			'previous' => $entry['previous'],
			'time'     => $entry['time'],
			'state'    => $state,
			'bytes'    => $stored[ $name ]['bytes'] ?? 0,
		];
	}

	usort( $rows, static fn ( array $first, array $second ): int => [ $first['time'], $first['name'] ] <=> [ $second['time'], $second['name'] ] );

	return $rows;
}

/**
 * Guess which code an option belongs to.
 *
 * WordPress records no owner for an option, so the guess comes from the best
 * evidence available: which code read it while usage was recorded, then which
 * installed code contains its name (when that search has run), then which
 * plugin's or theme's folder name or text domain the name starts with.
 *
 * @since 2.5.0
 * @param string                                                                                                            $name    Option name.
 * @param array<int, array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}> $sources Installed plugins and themes.
 * @param array{found: array<int, string>, searched: bool}|null                                                             $code    Code search result for the option, if any.
 * @param string|null                                                                                                       $reader  Source key ("plugin:slug" or "theme:slug") of the code that read the option while recording, if known.
 * @return array{type: string, label: string} Source type ('core', 'plugin', 'theme', 'transient', 'this', 'orphan', or 'unknown') and a label.
 */
function es_optimizer_autoload_guess_source( string $name, array $sources, ?array $code = null, ?string $reader = null ): array {
	$fixed = es_optimizer_autoload_get_fixed_source( $name );

	if ( null !== $fixed ) {
		return $fixed;
	}

	$owner = es_optimizer_autoload_source_by_key( $reader, $sources )
		?? es_optimizer_autoload_source_by_key( $code['found'][0] ?? null, $sources )
		?? es_optimizer_autoload_match_source( strtolower( ltrim( $name, '_' ) ), $sources );

	if ( null !== $owner ) {
		return [
			'type'  => $owner['type'],
			'label' => es_optimizer_autoload_source_label( $owner ),
		];
	}

	if ( null !== $code && $code['searched'] ) {
		return [
			'type'  => 'orphan',
			'label' => __( 'Not found in any installed plugin or theme: probably left behind by one that was removed', 'enginescript-site-optimizer' ),
		];
	}

	return [
		'type'  => 'unknown',
		'label' => __( 'Unknown, possibly a removed plugin or theme', 'enginescript-site-optimizer' ),
	];
}

/**
 * Get the source of an option that needs no guessing: this plugin, WordPress, or a transient.
 *
 * @since 2.5.0
 * @param string $name Option name.
 * @return array{type: string, label: string}|null Source, or null when it must be guessed.
 */
function es_optimizer_autoload_get_fixed_source( string $name ): ?array {
	return match ( true ) {
		in_array( $name, es_optimizer_autoload_get_own_options(), true ) => [
			'type'  => 'this',
			'label' => __( 'Site Optimizer', 'enginescript-site-optimizer' ),
		],
		es_optimizer_autoload_is_locked( $name ) => [
			'type'  => 'core',
			'label' => __( 'WordPress', 'enginescript-site-optimizer' ),
		],
		str_starts_with( $name, '_transient_' ) || str_starts_with( $name, '_site_transient_' ) => [
			'type'  => 'transient',
			'label' => __( 'Temporary cached data (transient)', 'enginescript-site-optimizer' ),
		],
		default => null,
	};
}

/**
 * Get the names of the options this plugin stores.
 *
 * @since 2.5.0
 * @return array<int, string> Option names.
 */
function es_optimizer_autoload_get_own_options(): array {
	return [ 'es_optimizer_options', ES_SITE_OPTIMIZER_AUTOLOAD_LOG_OPTION, ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION, ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION ];
}

/**
 * Describe a plugin or theme, with whether it is active.
 *
 * @since 2.5.0
 * @param array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string} $source Plugin or theme.
 * @return string Label such as "Elementor (active plugin)".
 */
function es_optimizer_autoload_source_label( array $source ): string {
	$format = match ( true ) {
		/* translators: %s: Theme name. */
		'theme' === $source['type'] && $source['active'] => __( '%s (active theme)', 'enginescript-site-optimizer' ),
		/* translators: %s: Theme name. */
		'theme' === $source['type'] => __( '%s (inactive theme)', 'enginescript-site-optimizer' ),
		/* translators: %s: Plugin name. */
		$source['active'] => __( '%s (active plugin)', 'enginescript-site-optimizer' ),
		/* translators: %s: Plugin name. */
		default => __( '%s (inactive plugin)', 'enginescript-site-optimizer' ),
	};

	return sprintf( $format, $source['name'] );
}

/**
 * Find a plugin or theme by its key, "plugin:slug" or "theme:slug".
 *
 * @since 2.5.0
 * @param string|null                                                                                                       $key     Source key.
 * @param array<int, array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}> $sources Installed plugins and themes.
 * @return array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}|null The source, or null when none has that key.
 */
function es_optimizer_autoload_source_by_key( ?string $key, array $sources ): ?array {
	foreach ( $sources as $source ) {
		if ( $source['type'] . ':' . $source['slug'] === $key ) {
			return $source;
		}
	}

	return null;
}

/**
 * Find the plugin or theme whose folder name or text domain best matches the start of an option name.
 *
 * @since 2.5.0
 * @param string                                                                                                            $name    Lowercase option name without leading underscores.
 * @param array<int, array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}> $sources Installed plugins and themes.
 * @return array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}|null The source with the longest matching key, or null.
 */
function es_optimizer_autoload_match_source( string $name, array $sources ): ?array {
	$best        = null;
	$best_length = 0;

	foreach ( $sources as $source ) {
		foreach ( $source['keys'] as $key ) {
			$matches = $name === $key || str_starts_with( $name, $key . '_' ) || str_starts_with( $name, $key . '-' );

			if ( $matches && strlen( $key ) > $best_length ) {
				$best        = $source;
				$best_length = strlen( $key );
			}
		}
	}

	return $best;
}

/**
 * Get the name prefixes a plugin or theme probably uses: its folder name and text domain, with dashes or underscores.
 *
 * @since 2.5.0
 * @param string $slug        Folder name.
 * @param string $text_domain Text domain, or an empty string.
 * @return array<int, string> Lowercase keys of at least three characters.
 */
function es_optimizer_autoload_source_keys( string $slug, string $text_domain ): array {
	$keys = [];

	foreach ( [ $slug, $text_domain ] as $base ) {
		$base = strtolower( $base );

		if ( strlen( $base ) >= 3 ) {
			$keys[] = $base;
			$keys[] = str_replace( '-', '_', $base );
		}
	}

	return array_values( array_unique( $keys ) );
}

/**
 * Get the installed plugins, must-use plugins, and themes.
 *
 * @since 2.5.0
 * @return array<int, array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}> Sources, each with its name keys and its folder or file.
 */
function es_optimizer_autoload_get_sources(): array {
	$sources = [];

	if ( function_exists( 'get_plugins' ) ) {
		foreach ( get_plugins() as $key => $data ) {
			$sources[] = es_optimizer_autoload_plugin_source( (string) $key, $data, is_plugin_active( (string) $key ), es_optimizer_autoload_constant( 'WP_PLUGIN_DIR' ) );
		}
	}

	if ( function_exists( 'get_mu_plugins' ) ) {
		foreach ( get_mu_plugins() as $key => $data ) {
			$sources[] = es_optimizer_autoload_plugin_source( (string) $key, $data, true, es_optimizer_autoload_constant( 'WPMU_PLUGIN_DIR' ) );
		}
	}

	$active_themes = [ get_stylesheet(), get_template() ];

	foreach ( wp_get_themes() as $slug => $theme ) {
		$slug       = (string) $slug;
		$domain     = $theme->get( 'TextDomain' );
		$theme_name = es_optimizer_autoload_to_string( $theme->get( 'Name' ) );
		$sources[]  = [
			'type'   => 'theme',
			'slug'   => $slug,
			'name'   => '' !== $theme_name ? $theme_name : $slug,
			'active' => in_array( $slug, $active_themes, true ),
			'keys'   => es_optimizer_autoload_source_keys( $slug, es_optimizer_autoload_to_string( $domain ) ),
			'path'   => $theme->get_stylesheet_directory(),
		];
	}

	return $sources;
}

/**
 * Describe one plugin as a source.
 *
 * @since 2.5.0
 * @param string                  $file   Plugin file, relative to its plugins folder.
 * @param array<array-key, mixed> $data   Plugin header data.
 * @param bool                    $active Whether the plugin is active.
 * @param string                  $root   Plugins folder.
 * @return array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string} Source.
 */
function es_optimizer_autoload_plugin_source( string $file, array $data, bool $active, string $root ): array {
	$in_folder = str_contains( $file, '/' );
	$slug      = $in_folder ? dirname( $file ) : basename( $file, '.php' );
	$name      = es_optimizer_autoload_to_string( $data['Name'] ?? '' );

	return [
		'type'   => 'plugin',
		'slug'   => $slug,
		'name'   => '' !== $name ? $name : $slug,
		'active' => $active,
		'keys'   => es_optimizer_autoload_source_keys( $slug, es_optimizer_autoload_to_string( $data['TextDomain'] ?? '' ) ),
		'path'   => $root . '/' . ( $in_folder ? $slug : $file ),
	];
}
