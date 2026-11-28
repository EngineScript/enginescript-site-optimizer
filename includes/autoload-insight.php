<?php
/**
 * Evidence about autoloaded options: which installed code contains each name,
 * and which options pages actually read while a recording runs.
 *
 * WordPress records no owner for an option and nothing about its use, so the
 * Autoloaded Options tab gathers this evidence on request.
 *
 * @package EngineScript_Site_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Transient that keeps the code search results for a day.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_CODE_TRANSIENT = 'es_optimizer_autoload_code';

/**
 * Seconds the code search results are kept: a day.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_CODE_SECONDS = 86400;

/**
 * Seconds the code search may take in one request.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_SEARCH_SECONDS = 20;

/**
 * Largest file the code search reads, in bytes.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_SEARCH_MAX_FILE = 2097152;

/**
 * Most options a usage recording keeps.
 *
 * @since 2.5.0
 */
const ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_MAX_OPTIONS = 5000;

/**
 * Search the installed plugins and themes for the given option names.
 *
 * A name counts as found in a plugin or theme when one of its PHP files has the
 * name as a quoted string. Names under four characters are skipped: they match
 * too much. When the time budget runs out, the names found so far are kept and
 * the others are left unsearched.
 *
 * @since 2.5.0
 * @param array<int, string>                                                                                                $names   Option names.
 * @param array<int, array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}> $sources Installed plugins and themes.
 * @param float                                                                                                             $budget  Seconds the search may take.
 * @return array{results: array<string, array{found: array<int, string>, searched: bool}>, complete: bool} Plugin or theme keys ("plugin:slug") per name, and whether every file was read.
 */
function es_optimizer_autoload_find_in_code( array $names, array $sources, float $budget = ES_SITE_OPTIMIZER_AUTOLOAD_SEARCH_SECONDS ): array {
	$names = array_values( array_filter( $names, static fn ( string $name ): bool => strlen( $name ) >= 4 ) );
	$found = array_fill_keys( $names, [] );

	if ( [] === $names ) {
		return [
			'results'  => [],
			'complete' => true,
		];
	}

	$pattern  = '/[\'"](' . implode( '|', array_map( static fn ( string $name ): string => preg_quote( $name, '/' ), $names ) ) . ')[\'"]/';
	$deadline = microtime( true ) + $budget;
	$complete = es_optimizer_autoload_search_sources( $sources, $pattern, $deadline, $found );
	$results  = [];

	foreach ( $found as $name => $keys ) {
		$results[ (string) $name ] = [
			'found'    => array_values( array_unique( $keys ) ),
			'searched' => $complete || [] !== $keys,
		];
	}

	return [
		'results'  => $results,
		'complete' => $complete,
	];
}

/**
 * Read each plugin's and theme's PHP files and note which names each contains.
 *
 * @since 2.5.0
 * @param array<int, array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}> $sources  Installed plugins and themes.
 * @param non-empty-string                                                                                                  $pattern  Pattern that captures a quoted name.
 * @param float                                                                                                             $deadline Time at which to stop.
 * @param array<array-key, array<int, string>>                                                                              $found    Keys per name, updated.
 * @return bool Whether every file was read before the deadline.
 */
function es_optimizer_autoload_search_sources( array $sources, string $pattern, float $deadline, array &$found ): bool {
	foreach ( $sources as $source ) {
		$key = $source['type'] . ':' . $source['slug'];

		foreach ( es_optimizer_autoload_php_files( $source['path'] ) as $file ) {
			if ( microtime( true ) > $deadline ) {
				return false;
			}

			$content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- A local plugin or theme file, read to search for option names.

			if ( false === $content || 1 > (int) preg_match_all( $pattern, $content, $matches ) ) {
				continue;
			}

			foreach ( array_unique( $matches[1] ) as $name ) {
				$found[ $name ][] = $key;
			}
		}
	}

	return true;
}

/**
 * List the PHP files of a plugin or theme: the file itself, or every PHP file under its folder.
 *
 * Folders named node_modules or .git, linked folders, and files over 2 MB are skipped.
 *
 * @since 2.5.0
 * @param string $path  Plugin file or folder, or theme folder.
 * @param int    $depth Folder depth reached.
 * @return array<int, string> File paths.
 */
function es_optimizer_autoload_php_files( string $path, int $depth = 0 ): array {
	if ( is_file( $path ) ) {
		return str_ends_with( $path, '.php' ) && filesize( $path ) <= ES_SITE_OPTIMIZER_AUTOLOAD_SEARCH_MAX_FILE ? [ $path ] : [];
	}

	$entries = $depth <= 25 && is_dir( $path ) && ! is_link( $path ) ? scandir( $path ) : false;

	if ( false === $entries ) {
		return [];
	}

	$files = [];

	foreach ( array_diff( $entries, [ '.', '..', 'node_modules', '.git' ] ) as $entry ) {
		array_push( $files, ...es_optimizer_autoload_php_files( $path . '/' . $entry, $depth + 1 ) );
	}

	return $files;
}

/**
 * Get the code search results kept for the current set of plugins and themes.
 *
 * @since 2.5.0
 * @param array<int, array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}> $sources Installed plugins and themes.
 * @return array<array-key, array{found: array<int, string>, searched: bool}> Results by option name.
 */
function es_optimizer_autoload_get_code_results( array $sources ): array {
	$stored = get_transient( ES_SITE_OPTIMIZER_AUTOLOAD_CODE_TRANSIENT );

	if ( ! is_array( $stored ) || ( $stored['signature'] ?? '' ) !== es_optimizer_autoload_sources_signature( $sources ) ) {
		return [];
	}

	return isset( $stored['results'] ) && is_array( $stored['results'] )
		? array_filter( array_map( es_optimizer_autoload_code_entry( ... ), $stored['results'] ) )
		: [];
}

/**
 * Read one kept code search result.
 *
 * @since 2.5.0
 * @param mixed $result Stored result.
 * @return array{found: array<int, string>, searched: bool}|null The result, or null when it is not well formed.
 */
function es_optimizer_autoload_code_entry( mixed $result ): ?array {
	if ( ! is_array( $result ) || ! isset( $result['found'] ) || ! is_array( $result['found'] ) ) {
		return null;
	}

	return [
		'found'    => array_values( array_filter( $result['found'], is_string( ... ) ) ),
		'searched' => true === ( $result['searched'] ?? false ),
	];
}

/**
 * Search the code for some names and keep the results with the earlier ones.
 *
 * @since 2.5.0
 * @param array<int, string>                                                                                                $names   Option names.
 * @param array<int, array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}> $sources Installed plugins and themes.
 * @return bool Whether the search read every file.
 */
function es_optimizer_autoload_search_and_keep( array $names, array $sources ): bool {
	$search  = es_optimizer_autoload_find_in_code( $names, $sources );
	$results = array_merge( es_optimizer_autoload_get_code_results( $sources ), $search['results'] );

	set_transient(
		ES_SITE_OPTIMIZER_AUTOLOAD_CODE_TRANSIENT,
		[
			'signature' => es_optimizer_autoload_sources_signature( $sources ),
			'results'   => $results,
		],
		ES_SITE_OPTIMIZER_AUTOLOAD_CODE_SECONDS
	);

	return $search['complete'];
}

/**
 * Identify the current set of plugins and themes, so that kept results end when it changes.
 *
 * @since 2.5.0
 * @param array<int, array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}> $sources Installed plugins and themes.
 * @return string Signature.
 */
function es_optimizer_autoload_sources_signature( array $sources ): string {
	return hash( 'sha256', implode( '|', array_map( static fn ( array $source ): string => $source['type'] . ':' . $source['slug'], $sources ) ) );
}

/**
 * Get the time a usage recording ends, without a database query.
 *
 * The recording option is autoloaded while it exists, so WordPress has already
 * loaded it with every other autoloaded option.
 *
 * @since 2.5.0
 * @return int Unix time the recording ends, or 0 when none runs.
 */
function es_optimizer_autoload_recording_until(): int {
	$all = wp_load_alloptions();

	return isset( $all[ ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION ] ) ? es_optimizer_autoload_to_int( $all[ ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION ] ) : 0;
}

/**
 * Start recording which options pages read, replacing the last results.
 *
 * @since 2.5.0
 * @param int $minutes Minutes to record: 10 or 60.
 * @return bool Whether the recording started.
 */
function es_optimizer_autoload_start_recording( int $minutes ): bool {
	$minutes = 60 === $minutes ? 60 : 10;

	delete_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION );
	delete_option( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION );

	return add_option( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION, (string) ( time() + $minutes * 60 ), '', true );
}

/**
 * Stop a usage recording; its results are kept.
 *
 * @since 2.5.0
 * @return bool Whether no recording runs now.
 */
function es_optimizer_autoload_stop_recording(): bool {
	delete_option( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION );

	return false === get_option( ES_SITE_OPTIMIZER_AUTOLOAD_RECORDING_OPTION, false );
}

/**
 * Delete the results of the last usage recording.
 *
 * @since 2.5.0
 * @return bool Whether no results are stored now.
 */
function es_optimizer_autoload_clear_usage(): bool {
	delete_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION );

	return false === get_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION, false );
}

/**
 * Start recording on this request when a recording runs; end a recording whose time is up.
 *
 * Only page loads count: AJAX, cron, WP-CLI, and REST requests are not recorded.
 *
 * @since 2.5.0
 */
function es_optimizer_autoload_init_recording(): void {
	$until = es_optimizer_autoload_recording_until();

	if ( 0 === $until ) {
		return;
	}

	if ( time() > $until ) {
		es_optimizer_autoload_stop_recording();

		return;
	}

	if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	add_filter( 'pre_option', 'es_optimizer_autoload_record_read', 10, 2 );
	add_action( 'shutdown', 'es_optimizer_autoload_save_reads' );
}

/**
 * Note an option read on this request, and the plugin or theme that first read it.
 *
 * @since 2.5.0
 * @param mixed  $pre    Value another filter returned early; passed on unchanged.
 * @param string $option Option name.
 * @return mixed The incoming value.
 */
function es_optimizer_autoload_record_read( mixed $pre, string $option ): mixed {
	es_optimizer_autoload_reads( $option );

	return $pre;
}

/**
 * Keep the options read on this request, with their first reader.
 *
 * @since 2.5.0
 * @param string|null $option Option to add, or null to read the list.
 * @return array<string, string> First reader per option name: "plugin:slug", "theme:slug", or "core".
 */
function es_optimizer_autoload_reads( ?string $option = null ): array {
	/**
	 * Options read on this request, with their first reader.
	 *
	 * @var array<string, string> $reads
	 */
	static $reads = [];
	static $busy  = false;

	// Finding the reader can read options itself; those reads are the plugin's own.
	if ( null !== $option && ! $busy && ! isset( $reads[ $option ] ) ) {
		$busy             = true;
		$reads[ $option ] = es_optimizer_autoload_find_reader();
		$busy             = false;
	}

	return $reads;
}

/**
 * Find the plugin or theme whose code is reading an option, from the call stack.
 *
 * @since 2.5.0
 * @return string "plugin:slug", "theme:slug", or "core" when no plugin or theme code is on the stack.
 */
function es_optimizer_autoload_find_reader(): string {
	$own   = wp_normalize_path( dirname( ES_SITE_OPTIMIZER_FILE ) ) . '/';
	$roots = [
		'plugin' => [ wp_normalize_path( es_optimizer_autoload_constant( 'WP_PLUGIN_DIR' ) ) . '/', wp_normalize_path( es_optimizer_autoload_constant( 'WPMU_PLUGIN_DIR' ) ) . '/' ],
		'theme'  => [ wp_normalize_path( get_theme_root() ) . '/' ],
	];

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Attributes an option read to its plugin or theme while the administrator records usage.
	foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 20 ) as $frame ) {
		$file = isset( $frame['file'] ) ? wp_normalize_path( $frame['file'] ) : '';

		if ( '' === $file || str_starts_with( $file, $own ) ) {
			continue;
		}

		$reader = es_optimizer_autoload_reader_from_file( $file, $roots );

		if ( null !== $reader ) {
			return $reader;
		}
	}

	return 'core';
}

/**
 * Map a file to the plugin or theme it belongs to.
 *
 * @since 2.5.0
 * @param string                            $file  Normalized file path.
 * @param array<string, array<int, string>> $roots Normalized plugin and theme folders, with a trailing slash, by type.
 * @return string|null "plugin:slug" or "theme:slug", or null when the file is in neither.
 */
function es_optimizer_autoload_reader_from_file( string $file, array $roots ): ?string {
	foreach ( $roots as $type => $folders ) {
		foreach ( $folders as $folder ) {
			if ( ! str_starts_with( $file, $folder ) ) {
				continue;
			}

			$relative = substr( $file, strlen( $folder ) );
			$slug     = str_contains( $relative, '/' ) ? explode( '/', $relative, 2 )[0] : basename( $relative, '.php' );

			return $type . ':' . $slug;
		}
	}

	return null;
}

/**
 * Add this request's reads to the recording's results.
 *
 * REST requests are left out; concurrent page loads can each miss the other's
 * count, so the counts are close, not exact.
 *
 * @since 2.5.0
 */
function es_optimizer_autoload_save_reads(): void {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	$reads = es_optimizer_autoload_reads();

	if ( [] === $reads ) {
		return;
	}

	$usage = es_optimizer_autoload_get_usage();
	++$usage['pages'];

	foreach ( $reads as $name => $reader ) {
		if ( ! isset( $usage['options'][ $name ] ) && count( $usage['options'] ) >= ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_MAX_OPTIONS ) {
			continue;
		}

		$entry                     = $usage['options'][ $name ] ?? [
			'pages'  => 0,
			'reader' => $reader,
		];
		$entry['pages']           += 1;
		$entry['reader']           = 'core' === $entry['reader'] ? $reader : $entry['reader'];
		$usage['options'][ $name ] = $entry;
	}

	update_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION, $usage, false );
}

/**
 * Get the results of the last usage recording.
 *
 * @since 2.5.0
 * @return array{pages: int, options: array<array-key, array{pages: int, reader: string}>} Pages recorded, and for each option how many of them read it and its first reader.
 */
function es_optimizer_autoload_get_usage(): array {
	$stored = get_option( ES_SITE_OPTIMIZER_AUTOLOAD_USAGE_OPTION, [] );
	$usage  = [
		'pages'   => 0,
		'options' => [],
	];

	if ( ! is_array( $stored ) ) {
		return $usage;
	}

	$usage['pages'] = es_optimizer_autoload_to_int( $stored['pages'] ?? 0 );

	if ( isset( $stored['options'] ) && is_array( $stored['options'] ) ) {
		$usage['options'] = array_filter( array_map( es_optimizer_autoload_usage_entry( ... ), $stored['options'] ) );
	}

	return $usage;
}

/**
 * Read one option's recorded usage.
 *
 * @since 2.5.0
 * @param mixed $entry Stored entry.
 * @return array{pages: int, reader: string}|null The entry, or null when it is not well formed.
 */
function es_optimizer_autoload_usage_entry( mixed $entry ): ?array {
	if ( ! is_array( $entry ) ) {
		return null;
	}

	return [
		'pages'  => es_optimizer_autoload_to_int( $entry['pages'] ?? 0 ),
		'reader' => es_optimizer_autoload_to_string( $entry['reader'] ?? 'core' ),
	];
}
