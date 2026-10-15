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
 * Exception code that marks a failure raised by this file.
 *
 * Only these failures carry a fixed reason without database details or stored
 * values. The value spells "ESO" in ASCII.
 *
 * @since 2.3.0
 */
const ES_SITE_OPTIMIZER_UNINSTALL_ERROR = 0x45534F;

/**
 * Get the fixed reason of a failure raised by this file.
 *
 * @since 2.3.0
 * @param Throwable $error Failure that stopped the uninstall.
 * @return string Escaped reason, or an empty string for a failure raised by other code.
 */
function es_optimizer_uninstall_get_failure_reason( Throwable $error ): string {
	if ( ! $error instanceof RuntimeException || ES_SITE_OPTIMIZER_UNINSTALL_ERROR !== $error->getCode() ) {
		return '';
	}

	return $error->getMessage();
}

/**
 * Build the message shown when the uninstall fails.
 *
 * @since 2.3.0
 * @param Throwable $error Failure that stopped the uninstall.
 * @return string Message with the reason, when this file knows it.
 */
function es_optimizer_uninstall_get_failure_message( Throwable $error ): string {
	$message = __( 'Site Optimizer settings could not be fully removed. The plugin files were retained. Pause site and settings changes, then retry removal.', 'enginescript-site-optimizer' );
	$reason  = es_optimizer_uninstall_get_failure_reason( $error );

	if ( '' === $reason ) {
		return $message;
	}

	return $message . ' ' . sprintf(
		/* translators: %s: Why the settings could not be removed. */
		__( 'Reason: %s', 'enginescript-site-optimizer' ),
		$reason
	);
}

/**
 * Validate a site ID returned by a checked database read.
 *
 * @since 2.2.0
 * @param mixed $site_id Database site ID.
 * @return int Positive site ID.
 * @throws RuntimeException If the database value is not a positive integer.
 */
function es_optimizer_uninstall_validate_site_id( mixed $site_id ): int {
	if ( ! is_int( $site_id ) && ! is_string( $site_id ) ) {
		throw new RuntimeException( esc_html__( 'The list of network sites returned an invalid site ID.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}

	$validated = filter_var( $site_id, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );

	if ( false === $validated ) {
		throw new RuntimeException( esc_html__( 'The list of network sites returned an invalid site ID.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}

	return $validated;
}

/**
 * Read the highest existing site ID without the site-query cache.
 *
 * @since 2.2.0
 * @return int Highest site ID, or zero for an empty directory.
 * @throws RuntimeException If the query fails or returns an unexpected result.
 */
function es_optimizer_uninstall_get_site_limit(): int {
	global $wpdb;

	if ( ! $wpdb instanceof wpdb ) {
		throw new RuntimeException( esc_html__( 'The WordPress database object is unavailable.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}

	// A fresh physical read distinguishes query failure from an empty directory.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall must check the current site directory, not cached IDs.
	$rows = $wpdb->query( (string) $wpdb->prepare( 'SELECT blog_id FROM %i ORDER BY blog_id DESC LIMIT 1', $wpdb->blogs ) );

	if ( 0 === $rows ) {
		return 0;
	}

	if ( 1 !== $rows ) {
		throw new RuntimeException( esc_html__( 'The list of network sites could not be read.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Null reads the already checked prepared query result; it executes no SQL.
	return es_optimizer_uninstall_validate_site_id( $wpdb->get_var( null ) );
}

/**
 * Read a bounded, strictly ordered page of site IDs.
 *
 * @since 2.2.0
 * @param int $last_id    Last successfully processed site ID.
 * @param int $maximum_id Highest site ID at the start of this uninstall.
 * @return array<int, int> At most 100 ascending site IDs.
 * @throws RuntimeException If the query or site-ID ordering is invalid.
 */
function es_optimizer_uninstall_get_site_ids( int $last_id, int $maximum_id ): array {
	global $wpdb;

	if ( ! $wpdb instanceof wpdb ) {
		throw new RuntimeException( esc_html__( 'The WordPress database object is unavailable.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- A prepared cursor read avoids cached pages and offset skips during uninstall.
	$rows = $wpdb->query(
		(string) $wpdb->prepare(
			'SELECT blog_id FROM %i WHERE blog_id > %d AND blog_id <= %d ORDER BY blog_id ASC LIMIT 100',
			$wpdb->blogs,
			$last_id,
			$maximum_id
		)
	);

	if ( ! is_int( $rows ) || $rows < 0 || $rows > 100 ) {
		throw new RuntimeException( esc_html__( 'The list of network sites could not be read.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Null reads the already checked prepared query result; it executes no SQL.
	$site_ids = $wpdb->get_col( null );

	if ( count( $site_ids ) !== $rows ) {
		throw new RuntimeException( esc_html__( 'The list of network sites could not be read.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}

	$validated_ids = array();

	foreach ( $site_ids as $site_id ) {
		$site_id = es_optimizer_uninstall_validate_site_id( $site_id );

		if ( $site_id <= $last_id || $site_id > $maximum_id ) {
			throw new RuntimeException( esc_html__( 'The list of network sites returned an invalid site ID.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
		}

		$validated_ids[] = $site_id;
		$last_id         = $site_id;
	}

	return $validated_ids;
}

/**
 * Delete and physically verify the current site's fixed option.
 *
 * @since 2.2.0
 * @return void
 * @throws RuntimeException If the site changes, verification fails or the row remains.
 */
function es_optimizer_uninstall_current_site_options(): void {
	global $wpdb;

	if ( ! $wpdb instanceof wpdb ) {
		throw new RuntimeException( esc_html__( 'The WordPress database object is unavailable.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}

	$site_id = get_current_blog_id();
	delete_option( 'es_optimizer_options' );

	if ( get_current_blog_id() !== $site_id ) {
		throw new RuntimeException(
			sprintf(
				/* translators: %d: Site ID. */
				esc_html__( 'The current site changed while the settings of site %d were being deleted.', 'enginescript-site-optimizer' ),
				$site_id // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- An integer site ID for the %d placeholder.
			),
			ES_SITE_OPTIMIZER_UNINSTALL_ERROR // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
		);
	}

	// delete_option can mark notoptions even after a failed DELETE; bypass that cache.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Only a successful physical read proves absence after native deletion.
	$rows = $wpdb->query(
		(string) $wpdb->prepare( 'SELECT option_id FROM %i WHERE option_name = %s LIMIT 1', $wpdb->options, 'es_optimizer_options' )
	);

	if ( ! is_int( $rows ) ) {
		throw new RuntimeException(
			sprintf(
				/* translators: %d: Site ID. */
				esc_html__( 'The settings of site %d could not be verified as removed.', 'enginescript-site-optimizer' ),
				$site_id // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- An integer site ID for the %d placeholder.
			),
			ES_SITE_OPTIMIZER_UNINSTALL_ERROR // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
		);
	}

	if ( 0 !== $rows ) {
		throw new RuntimeException(
			sprintf(
				/* translators: %d: Site ID. */
				esc_html__( 'The settings of site %d are still stored after deletion.', 'enginescript-site-optimizer' ),
				$site_id // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- An integer site ID for the %d placeholder.
			),
			ES_SITE_OPTIMIZER_UNINSTALL_ERROR // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
		);
	}
}

/**
 * Read the site-switch state that WordPress currently holds.
 *
 * WordPress sets both values at file scope. They are unset until the first
 * switch when WordPress was loaded inside a function, as WP-CLI loads it; that
 * state means "no switch is active".
 *
 * @since 2.3.0
 * @return array{stack: array<int|string, mixed>, switched: bool} Switch stack and flag.
 * @throws RuntimeException If either value is set to an unexpected type.
 */
function es_optimizer_uninstall_get_switch_state(): array {
	global $_wp_switched_stack, $switched;

	$stack = $_wp_switched_stack ?? array();
	$flag  = $switched ?? false;

	if ( ! is_array( $stack ) || ! is_bool( $flag ) ) {
		throw new RuntimeException( esc_html__( 'The site-switch state could not be read.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}

	return array(
		'stack'    => $stack,
		'switched' => $flag,
	);
}

/**
 * Count the site-switch frames that WordPress currently holds.
 *
 * @since 2.3.0
 * @return int Number of frames on the switch stack.
 * @throws RuntimeException If the switch state cannot be read.
 */
function es_optimizer_uninstall_get_switch_depth(): int {
	return count( es_optimizer_uninstall_get_switch_state()['stack'] );
}

/**
 * Restore only the site-switch frames acquired by this uninstall operation.
 *
 * @since 2.2.0
 * @param array{site_id: int, stack: array<int|string, mixed>, switched: bool} $context Original site context.
 * @return void
 * @throws RuntimeException If context cannot be restored without additional switching.
 */
function es_optimizer_uninstall_restore_context( array $context ): void {
	$original_depth = count( $context['stack'] );
	$current_depth  = es_optimizer_uninstall_get_switch_depth();

	while ( $current_depth > $original_depth ) {
		$previous_depth = $current_depth;

		if ( ! restore_current_blog() ) {
			throw new RuntimeException( esc_html__( 'WordPress could not switch back to the original site.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
		}

		$current_depth = es_optimizer_uninstall_get_switch_depth();

		if ( $current_depth >= $previous_depth ) {
			throw new RuntimeException( esc_html__( 'WordPress could not switch back to the original site.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
		}
	}

	$state = es_optimizer_uninstall_get_switch_state();

	if ( get_current_blog_id() !== $context['site_id'] || $state['stack'] !== $context['stack'] || $state['switched'] !== $context['switched'] ) {
		throw new RuntimeException( esc_html__( 'WordPress could not switch back to the original site.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}
}

/**
 * Clean one site and restore context even when a switch or deletion hook throws.
 *
 * @since 2.2.0
 * @param int $site_id Site to clean.
 * @return void
 * @throws RuntimeException If the switch state is unreadable, or switching or context restoration fails.
 */
function es_optimizer_uninstall_site_options( int $site_id ): void {
	$state   = es_optimizer_uninstall_get_switch_state();
	$context = array(
		'site_id'  => get_current_blog_id(),
		'stack'    => $state['stack'],
		'switched' => $state['switched'],
	);

	try {
		if ( $site_id !== $context['site_id'] ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.switch_to_blog_switch_to_blog -- Only that site's options are removed; none of its code has to load.
			switch_to_blog( $site_id );
		}

		if ( get_current_blog_id() !== $site_id ) {
			throw new RuntimeException(
				sprintf(
					/* translators: %d: Site ID. */
					esc_html__( 'WordPress could not switch to site %d.', 'enginescript-site-optimizer' ),
					$site_id // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- An integer site ID for the %d placeholder.
				),
				ES_SITE_OPTIMIZER_UNINSTALL_ERROR // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
			);
		}

		es_optimizer_uninstall_current_site_options();
	} finally {
		es_optimizer_uninstall_restore_context( $context );
	}
}

/**
 * Clean one site of a network and report a failure that may be passed over.
 *
 * A failure may be passed over only when the site and switch state from before
 * the attempt are still in place. Any other failure stops the uninstall.
 *
 * @since 2.3.0
 * @param int $site_id Site to clean.
 * @return Throwable|null The failure, or null when the site was cleaned.
 * @throws Throwable If a failure left another site or switch state in place.
 */
function es_optimizer_uninstall_try_site_options( int $site_id ): ?Throwable {
	$origin = get_current_blog_id();
	$state  = es_optimizer_uninstall_get_switch_state();

	try {
		es_optimizer_uninstall_site_options( $site_id );
	} catch ( Throwable $error ) {
		if ( get_current_blog_id() !== $origin || es_optimizer_uninstall_get_switch_state() !== $state ) {
			throw $error;
		}

		return $error;
	}

	return null;
}

/**
 * Describe the sites that could not be cleaned.
 *
 * @since 2.3.0
 * @param int             $failed_count Number of sites that could not be cleaned.
 * @param array<int, int> $failed_ids   At most 20 of their IDs, in ascending order.
 * @param Throwable       $first_error  Failure of the first of those sites.
 * @return string Escaped reason with the count, the IDs and the first known cause.
 */
function es_optimizer_uninstall_get_sites_failure_reason( int $failed_count, array $failed_ids, Throwable $first_error ): string {
	$reason = sprintf(
		/* translators: 1: Number of sites, 2: Comma-separated site IDs. */
		_n(
			'Settings could not be removed from %1$d site. Site ID: %2$s.',
			'Settings could not be removed from %1$d sites. Site IDs, at most the first 20: %2$s.',
			$failed_count,
			'enginescript-site-optimizer'
		),
		$failed_count,
		implode( ', ', array_map( 'intval', $failed_ids ) )
	);

	return trim( esc_html( $reason ) . ' ' . es_optimizer_uninstall_get_failure_reason( $first_error ) );
}

/**
 * Clean every site's option across all networks using an ID cursor.
 *
 * A site that cannot be cleaned is passed over, so that one unreadable site
 * does not keep the settings of every later site. The uninstall still fails.
 *
 * @since 2.2.0
 * @return void
 * @throws RuntimeException If any site could not be cleaned or the directory grows during removal.
 */
function es_optimizer_uninstall_network_options(): void {
	$maximum_id   = es_optimizer_uninstall_get_site_limit();
	$last_id      = 0;
	$failed_count = 0;
	$failed_ids   = array();
	$first_error  = null;

	while ( $last_id < $maximum_id ) {
		$site_ids = es_optimizer_uninstall_get_site_ids( $last_id, $maximum_id );

		if ( empty( $site_ids ) ) {
			break;
		}

		foreach ( $site_ids as $site_id ) {
			$error   = es_optimizer_uninstall_try_site_options( $site_id );
			$last_id = $site_id;

			// Switching loads each site's options into the in-memory cache; clear it, so memory does not grow with the network.
			if ( wp_cache_supports( 'flush_runtime' ) ) {
				wp_cache_flush_runtime();
			}

			if ( null === $error ) {
				continue;
			}

			++$failed_count;
			$first_error ??= $error;

			if ( $failed_count <= 20 ) {
				$failed_ids[] = $site_id;
			}
		}
	}

	if ( es_optimizer_uninstall_get_site_limit() > $maximum_id ) {
		throw new RuntimeException( esc_html__( 'A site was created during removal.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}

	if ( null !== $first_error ) {
		throw new RuntimeException(
			esc_html( es_optimizer_uninstall_get_sites_failure_reason( $failed_count, $failed_ids, $first_error ) ),
			ES_SITE_OPTIMIZER_UNINSTALL_ERROR, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
			$first_error // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The previous exception is passed on, not printed.
		);
	}
}

/**
 * Run native deletion with private database diagnostics and checked reads.
 *
 * @since 2.2.0
 * @return void
 * @throws RuntimeException If the database object is unavailable.
 */
function es_optimizer_uninstall_options(): void {
	global $wpdb;

	if ( ! $wpdb instanceof wpdb ) {
		throw new RuntimeException( esc_html__( 'The WordPress database object is unavailable.', 'enginescript-site-optimizer' ), ES_SITE_OPTIMIZER_UNINSTALL_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The code is an integer constant.
	}

	// The checked object, so that suppression is restored on the object it was set on.
	$database             = $wpdb;
	$previous_suppression = $database->suppress_errors( true );

	try {
		if ( is_multisite() ) {
			es_optimizer_uninstall_network_options();

			return;
		}

		es_optimizer_uninstall_current_site_options();
	} finally {
		$database->suppress_errors( $previous_suppression );
	}
}

try {
	es_optimizer_uninstall_options();
} catch ( Throwable $es_optimizer_uninstall_failure ) {
	wp_die(
		esc_html( es_optimizer_uninstall_get_failure_message( $es_optimizer_uninstall_failure ) ),
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
