<?php
/**
 * Uninstall handler for EngineScript Site Optimizer.
 *
 * Removes only this plugin's settings before WordPress deletes its files.
 * On multisite, pause site provisioning, settings writes and plugin activation
 * during removal. Large installations may need an operator-run uninstall with
 * sufficient time and memory; ID pages do not bound other plugins' work or caches.
 *
 * @package EngineScript_Site_Optimizer
 * @since   2.0.1
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Validate a site ID returned by a checked database read.
 *
 * @since Unreleased
 * @param mixed $site_id Database site ID.
 * @return int Positive site ID.
 * @throws RuntimeException If the database value is not a positive integer.
 */
function es_optimizer_uninstall_validate_site_id( mixed $site_id ): int {
	if ( ! is_int( $site_id ) && ! is_string( $site_id ) ) {
		throw new RuntimeException();
	}

	$validated = filter_var( $site_id, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );

	if ( false === $validated ) {
		throw new RuntimeException();
	}

	return $validated;
}

/**
 * Read the highest existing site ID without the site-query cache.
 *
 * @since Unreleased
 * @return int Highest site ID, or zero for an empty directory.
 * @throws RuntimeException If the query fails or returns an unexpected result.
 */
function es_optimizer_uninstall_get_site_limit(): int {
	global $wpdb;

	// A fresh physical read distinguishes query failure from an empty directory.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall must check the current site directory, not cached IDs.
	$rows = $wpdb->query( $wpdb->prepare( 'SELECT blog_id FROM %i ORDER BY blog_id DESC LIMIT 1', $wpdb->blogs ) );

	if ( 0 === $rows ) {
		return 0;
	}

	if ( 1 !== $rows ) {
		throw new RuntimeException();
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Null reads the already checked prepared query result; it executes no SQL.
	return es_optimizer_uninstall_validate_site_id( $wpdb->get_var( null ) );
}

/**
 * Read a bounded, strictly ordered page of site IDs.
 *
 * @since Unreleased
 * @param int $last_id    Last successfully processed site ID.
 * @param int $maximum_id Highest site ID at the start of this uninstall.
 * @return array<int, int> At most 100 ascending site IDs.
 * @throws RuntimeException If the query or site-ID ordering is invalid.
 */
function es_optimizer_uninstall_get_site_ids( int $last_id, int $maximum_id ): array {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- A prepared cursor read avoids cached pages and offset skips during uninstall.
	$rows = $wpdb->query(
		$wpdb->prepare(
			'SELECT blog_id FROM %i WHERE blog_id > %d AND blog_id <= %d ORDER BY blog_id ASC LIMIT 100',
			$wpdb->blogs,
			$last_id,
			$maximum_id
		)
	);

	if ( ! is_int( $rows ) || $rows < 0 || $rows > 100 ) {
		throw new RuntimeException();
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Null reads the already checked prepared query result; it executes no SQL.
	$site_ids = $wpdb->get_col( null );

	if ( count( $site_ids ) !== $rows ) {
		throw new RuntimeException();
	}

	foreach ( $site_ids as $index => $site_id ) {
		$site_id = es_optimizer_uninstall_validate_site_id( $site_id );

		if ( $site_id <= $last_id || $site_id > $maximum_id ) {
			throw new RuntimeException();
		}

		$site_ids[ $index ] = $site_id;
		$last_id            = $site_id;
	}

	return $site_ids;
}

/**
 * Delete and physically verify the current site's fixed option.
 *
 * @since Unreleased
 * @return void
 * @throws RuntimeException If the site changes, verification fails or the row remains.
 */
function es_optimizer_uninstall_current_site_options(): void {
	global $wpdb;

	$site_id = get_current_blog_id();
	delete_option( 'es_optimizer_options' );

	if ( get_current_blog_id() !== $site_id ) {
		throw new RuntimeException();
	}

	// delete_option can mark notoptions even after a failed DELETE; bypass that cache.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Only a successful physical read proves absence after native deletion.
	$rows = $wpdb->query(
		$wpdb->prepare( 'SELECT option_id FROM %i WHERE option_name = %s LIMIT 1', $wpdb->options, 'es_optimizer_options' )
	);

	if ( 0 !== $rows ) {
		throw new RuntimeException();
	}
}

/**
 * Restore only the site-switch frames acquired by this uninstall operation.
 *
 * @since Unreleased
 * @param array{site_id: int, stack: array<int, int>, switched: bool} $context Original site context.
 * @return void
 * @throws RuntimeException If context cannot be restored without additional switching.
 */
function es_optimizer_uninstall_restore_context( array $context ): void {
	global $_wp_switched_stack, $switched;

	$original_depth = count( $context['stack'] );
	$current_depth  = count( $_wp_switched_stack );

	while ( $current_depth > $original_depth ) {
		$previous_depth = $current_depth;

		if ( ! restore_current_blog() ) {
			throw new RuntimeException();
		}

		$current_depth = count( $_wp_switched_stack );

		if ( $current_depth >= $previous_depth ) {
			throw new RuntimeException();
		}
	}

	if ( get_current_blog_id() !== $context['site_id'] || $_wp_switched_stack !== $context['stack'] || $switched !== $context['switched'] ) {
		throw new RuntimeException();
	}
}

/**
 * Clean one site and restore context even when a switch or deletion hook throws.
 *
 * @since Unreleased
 * @param int $site_id Site to clean.
 * @return void
 * @throws RuntimeException If switching or context restoration fails.
 */
function es_optimizer_uninstall_site_options( int $site_id ): void {
	global $_wp_switched_stack, $switched;

	if ( ! is_array( $_wp_switched_stack ) || ! is_bool( $switched ) ) {
		throw new RuntimeException();
	}

	$context = array(
		'site_id'  => get_current_blog_id(),
		'stack'    => $_wp_switched_stack,
		'switched' => $switched,
	);

	try {
		if ( $site_id !== $context['site_id'] ) {
			switch_to_blog( $site_id );
		}

		if ( get_current_blog_id() !== $site_id ) {
			throw new RuntimeException();
		}

		es_optimizer_uninstall_current_site_options();
	} finally {
		es_optimizer_uninstall_restore_context( $context );
	}
}

/**
 * Clean every site's option across all networks using an ID cursor.
 *
 * @since Unreleased
 * @return void
 * @throws RuntimeException If cleanup fails or the directory grows during removal.
 */
function es_optimizer_uninstall_network_options(): void {
	$maximum_id = es_optimizer_uninstall_get_site_limit();
	$last_id    = 0;

	while ( $last_id < $maximum_id ) {
		$site_ids = es_optimizer_uninstall_get_site_ids( $last_id, $maximum_id );

		if ( empty( $site_ids ) ) {
			break;
		}

		foreach ( $site_ids as $site_id ) {
			es_optimizer_uninstall_site_options( $site_id );
			$last_id = $site_id;
		}
	}

	if ( es_optimizer_uninstall_get_site_limit() > $maximum_id ) {
		throw new RuntimeException();
	}
}

/**
 * Run native deletion with private database diagnostics and checked reads.
 *
 * @since Unreleased
 * @return void
 */
function es_optimizer_uninstall_options(): void {
	global $wpdb;

	$previous_suppression = $wpdb->suppress_errors( true );

	try {
		if ( is_multisite() ) {
			es_optimizer_uninstall_network_options();
		} else {
			es_optimizer_uninstall_current_site_options();
		}
	} finally {
		$wpdb->suppress_errors( $previous_suppression );
	}
}

try {
	es_optimizer_uninstall_options();
} catch ( Throwable ) {
	wp_die(
		esc_html__( 'Site Optimizer settings could not be fully removed. The plugin files were retained. Pause site and settings changes, then retry removal with sufficient time and memory.', 'enginescript-site-optimizer' ),
		esc_html__( 'Site Optimizer removal failed', 'enginescript-site-optimizer' ),
		array( 'response' => 500 )
	);

	/**
	 * A custom wp_die handler may return; never let core delete files after failure.
	 *
	 * @phpstan-ignore deadCode.unreachable (A returning custom handler must still prevent file deletion.)
	 */
	exit( 1 );
}
