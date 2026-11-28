<?php
/**
 * The Autoloaded Options tab of Settings > Site Optimizer, and the uninstall warning on the Plugins screen.
 *
 * @package EngineScript_Site_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Get the address of the Autoloaded Options tab, with the given query arguments.
 *
 * @since 2.5.0
 * @param array<string, int|string> $args Query arguments.
 * @return string Admin URL.
 */
function es_optimizer_autoload_page_url( array $args = [] ): string {
	return add_query_arg(
		array_merge(
			[
				'page' => 'es-optimizer-settings',
				'tab'  => 'autoload',
			],
			$args
		),
		admin_url( 'options-general.php' )
	);
}

/**
 * Handle a submitted change, then redirect back with its result.
 *
 * @since 2.5.0
 * @SuppressWarnings("PHPMD.ExitExpression") A redirect must end the request.
 */
function es_optimizer_autoload_handle_request(): void {
	$redirect = es_optimizer_autoload_process_request();

	if ( null !== $redirect ) {
		wp_safe_redirect( $redirect );
		exit;
	}
}

/**
 * Check and apply a submitted change.
 *
 * @since 2.5.0
 * @return string|null Address to redirect to with the result, or null when nothing was submitted.
 */
function es_optimizer_autoload_process_request(): ?string {
	if ( ! es_optimizer_autoload_is_submission() ) {
		return null;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to change autoloaded options.', 'enginescript-site-optimizer' ), 403 );
	}

	check_admin_referer( 'es_optimizer_autoload' );

	$submission = es_optimizer_autoload_read_submission();

	return match ( $submission['action'] ) {
		'switch_off' => es_optimizer_autoload_switch_off_and_check( $submission['names'] ),
		'switch_on' => es_optimizer_autoload_get_result_url( 'switch_on', es_optimizer_autoload_switch_on( $submission['names'] ) ),
		'record_start' => es_optimizer_autoload_notice_url( es_optimizer_autoload_start_recording( $submission['minutes'] ) ? 'recording_started' : 'recording_failed' ),
		'record_stop' => es_optimizer_autoload_notice_url( es_optimizer_autoload_stop_recording() ? 'recording_stopped' : 'recording_failed' ),
		'record_clear' => es_optimizer_autoload_notice_url( es_optimizer_autoload_clear_usage() ? 'results_cleared' : 'recording_failed' ),
		default => es_optimizer_autoload_unknown_action(),
	};
}

/**
 * Stop a request that names no known action.
 *
 * @since 2.5.0
 * @SuppressWarnings("PHPMD.ExitExpression") The request must end if a custom wp_die() handler returns.
 */
function es_optimizer_autoload_unknown_action(): never {
	wp_die( esc_html__( 'Unknown action.', 'enginescript-site-optimizer' ), 400 );

	/**
	 * A custom wp_die() handler may return; the request must still end.
	 *
	 * @phpstan-ignore deadCode.unreachable (A custom wp_die() handler may return.)
	 */
	exit;
}

/**
 * Switch options off, check that the site still loads, and undo the change if it does not.
 *
 * @since 2.5.0
 * @param mixed $names Requested option names.
 * @return string Address that shows the result.
 */
function es_optimizer_autoload_switch_off_and_check( mixed $names ): string {
	$result = es_optimizer_autoload_switch_off( $names );
	$check  = [] === $result['changed'] ? 'none' : es_optimizer_autoload_check_site();
	$undone = 0;

	if ( 'failed' === $check ) {
		$undone = count( es_optimizer_autoload_switch_on( $result['changed'] )['changed'] );
	}

	return es_optimizer_autoload_get_result_url(
		'switch_off',
		$result,
		[
			'es_check'  => $check,
			'es_undone' => $undone,
		]
	);
}

/**
 * Build the address that shows a notice after a recording action.
 *
 * @since 2.5.0
 * @param string $notice Notice code.
 * @return string Admin URL.
 */
function es_optimizer_autoload_notice_url( string $notice ): string {
	return es_optimizer_autoload_page_url( [ 'es_notice' => $notice ] );
}

/**
 * Check that the home page and the dashboard still load, as a visitor and as the current administrator.
 *
 * WordPress requests both pages from the site itself, as Site Health does. Some
 * hosts block such requests; the result is then "unchecked", and nothing is undone.
 *
 * @since 2.5.0
 * @return string 'ok', 'failed' when a page answers with a server error, or 'unchecked' when a page could not be requested.
 */
function es_optimizer_autoload_check_site(): string {
	$base     = [
		// A loaded dashboard can take more than VIP's 3 seconds; this runs once, for an administrator.
		'timeout'     => 15, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
		'redirection' => 3,
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core's filter for requests to the site itself.
		'sslverify'   => (bool) apply_filters( 'https_local_ssl_verify', false ),
		'headers'     => [ 'Cache-Control' => 'no-cache' ],
	];
	$requests = [
		[ add_query_arg( 'es-optimizer-check', (string) time(), home_url( '/' ) ), $base ],
		[ admin_url(), $base + [ 'cookies' => es_optimizer_autoload_auth_cookies() ] ],
	];
	$status   = 'ok';

	foreach ( $requests as [ $url, $args ] ) {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get -- A request to this site itself, as Site Health makes, not to a remote service.
		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			$status = 'unchecked';
			continue;
		}

		if ( (int) wp_remote_retrieve_response_code( $response ) >= 500 ) {
			return 'failed';
		}
	}

	return $status;
}

/**
 * Get the current administrator's login cookies, to load the dashboard as them.
 *
 * Core sends the same cookies when it checks a file edit from the plugin editor.
 *
 * @since 2.5.0
 * @SuppressWarnings("PHPMD.Superglobals") Reads only WordPress's own login cookies, sent back to this site.
 * @return array<string, string> Cookie values by name.
 */
function es_optimizer_autoload_auth_cookies(): array {
	$cookies = [];

	foreach ( [ 'LOGGED_IN_COOKIE', 'AUTH_COOKIE', 'SECURE_AUTH_COOKIE' ] as $constant ) {
		$name = es_optimizer_autoload_constant( $constant );

		// phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE -- An administrator's request in the dashboard, which no page cache serves.
		if ( '' !== $name && isset( $_COOKIE[ $name ] ) ) {
			$cookies[ $name ] = sanitize_text_field( es_optimizer_autoload_to_string( wp_unslash( $_COOKIE[ $name ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by sanitize_text_field() once read as text.
		}
		// phpcs:enable
	}

	return $cookies;
}

/**
 * Check whether the request submits a change form.
 *
 * @since 2.5.0
 * @SuppressWarnings("PHPMD.Superglobals") Reads only the request method and whether the form's action field is present.
 * @return bool Whether a change form was posted.
 */
function es_optimizer_autoload_is_submission(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only detects the form; the caller checks the nonce before reading it.
	return isset( $_SERVER['REQUEST_METHOD'], $_POST['es_optimizer_autoload_action'] ) && 'POST' === $_SERVER['REQUEST_METHOD'];
}

/**
 * Read the submitted change. Called only after the capability and nonce checks.
 *
 * @since 2.5.0
 * @SuppressWarnings("PHPMD.Superglobals") The one place that reads the submitted form.
 * @return array{action: string, names: mixed, minutes: int} The action key, the names as submitted, and the minutes to record.
 */
function es_optimizer_autoload_read_submission(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- The caller has checked the nonce.
	$action = isset( $_POST['es_optimizer_autoload_action'] ) && is_string( $_POST['es_optimizer_autoload_action'] ) ? sanitize_key( wp_unslash( $_POST['es_optimizer_autoload_action'] ) ) : '';
	// Option names are compared exactly with stored names in prepared queries; sanitizing would change some names.
	$names   = isset( $_POST['option_names'] ) ? wp_unslash( $_POST['option_names'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- See above; each name is validated as a string of at most 191 bytes.
	$minutes = isset( $_POST['minutes'] ) && is_string( $_POST['minutes'] ) ? absint( $_POST['minutes'] ) : 10;
	// phpcs:enable

	return [
		'action'  => $action,
		'names'   => $names,
		'minutes' => $minutes,
	];
}

/**
 * Build the address that shows the result of a change.
 *
 * @since 2.5.0
 * @param string                                                          $action Action that ran.
 * @param array{changed: array<int, string>, skipped: array<string, int>} $result Its result.
 * @param array<string, int|string>                                       $extra  More query arguments, such as the site check.
 * @return string Admin URL with the counts.
 */
function es_optimizer_autoload_get_result_url( string $action, array $result, array $extra = [] ): string {
	$args = [
		'view'       => 'switch_on' === $action ? 'changed' : 'loaded',
		'es_changed' => count( $result['changed'] ),
	] + $extra;

	foreach ( $result['skipped'] as $reason => $count ) {
		$args[ 'es_skipped_' . $reason ] = $count;
	}

	return es_optimizer_autoload_page_url( $args );
}

/**
 * Get the messages for each reason an option was skipped.
 *
 * @since 2.5.0
 * @param string $reason Reason code.
 * @param int    $count  Number of options.
 * @return string Message, or an empty string for an unknown reason.
 */
function es_optimizer_autoload_get_skip_message( string $reason, int $count ): string {
	$message = match ( $reason ) {
		/* translators: %d: Number of options. */
		'locked' => _n( '%d locked option was left as it is: WordPress or this plugin reads it on every page.', '%d locked options were left as they are: WordPress or this plugin reads them on every page.', $count, 'enginescript-site-optimizer' ),
		/* translators: %d: Number of options. */
		'missing' => _n( '%d option no longer exists.', '%d options no longer exist.', $count, 'enginescript-site-optimizer' ),
		/* translators: %d: Number of options. */
		'not_autoloaded' => _n( '%d option was already not loaded on every page.', '%d options were already not loaded on every page.', $count, 'enginescript-site-optimizer' ),
		/* translators: %d: Number of options. */
		'failed' => _n( '%d option could not be changed; it was left as it was.', '%d options could not be changed; they were left as they were.', $count, 'enginescript-site-optimizer' ),
		/* translators: %d: Number of entries. */
		'invalid' => _n( '%d entry was not a valid option name or was over the limit of 100 options per change.', '%d entries were not valid option names or were over the limit of 100 options per change.', $count, 'enginescript-site-optimizer' ),
		/* translators: %d: Number of options. */
		'not_logged' => _n( '%d option was not switched off by Site Optimizer, so it was left as it is.', '%d options were not switched off by Site Optimizer, so they were left as they are.', $count, 'enginescript-site-optimizer' ),
		/* translators: %d: Number of options. */
		'changed_elsewhere' => _n( '%d option had already been changed or deleted by other code; it was taken off the list without a change.', '%d options had already been changed or deleted by other code; they were taken off the list without a change.', $count, 'enginescript-site-optimizer' ),
		'log_failed' => __( 'The list of changes could not be saved. Reload the page to see the current state.', 'enginescript-site-optimizer' ),
		default => '',
	};

	return str_contains( $message, '%d' ) ? sprintf( $message, $count ) : $message;
}

/**
 * Read the result of the last action from the redirect's query arguments.
 *
 * @since 2.5.0
 * @return array{view: string, changed: int, skipped: array<string, int>, check: string, undone: int, notice: string}|null The result, or null when the page was not reached by a redirect.
 */
function es_optimizer_autoload_read_result(): ?array {
	if ( null === es_optimizer_autoload_query_int( 'es_changed' ) && '' === es_optimizer_autoload_query_key( 'es_notice' ) ) {
		return null;
	}

	$skipped = [];

	foreach ( [ 'locked', 'missing', 'not_autoloaded', 'failed', 'invalid', 'not_logged', 'changed_elsewhere', 'log_failed' ] as $reason ) {
		$skipped[ $reason ] = es_optimizer_autoload_query_int( 'es_skipped_' . $reason ) ?? 0;
	}

	$view = es_optimizer_autoload_query_key( 'view' );

	return [
		'view'    => '' !== $view ? $view : 'loaded',
		'changed' => es_optimizer_autoload_query_int( 'es_changed' ) ?? 0,
		'skipped' => array_filter( $skipped ),
		'check'   => es_optimizer_autoload_query_key( 'es_check' ),
		'undone'  => es_optimizer_autoload_query_int( 'es_undone' ) ?? 0,
		'notice'  => es_optimizer_autoload_query_key( 'es_notice' ),
	];
}

/**
 * Read a query argument of the result as a key.
 *
 * @since 2.5.0
 * @SuppressWarnings("PHPMD.Superglobals") Display only: the result of an action that was nonce-checked before the redirect.
 * @param string $name Argument name.
 * @return string Lowercase key, or an empty string when absent.
 */
function es_optimizer_autoload_query_key( string $name ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only, see above.
	return isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] ) ? sanitize_key( wp_unslash( $_GET[ $name ] ) ) : '';
}

/**
 * Read a query argument of the result as a count.
 *
 * @since 2.5.0
 * @SuppressWarnings("PHPMD.Superglobals") Display only: the result of an action that was nonce-checked before the redirect.
 * @param string $name Argument name.
 * @return int|null Non-negative integer, or null when absent.
 */
function es_optimizer_autoload_query_int( string $name ): ?int {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only, see above.
	return isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] ) ? absint( $_GET[ $name ] ) : null;
}

/**
 * Turn the result of an action into the lines of a notice.
 *
 * @since 2.5.0
 * @param array{view: string, changed: int, skipped: array<string, int>, check: string, undone: int, notice: string} $result Result of the action.
 * @return array{lines: array<int, string>, type: string} Lines, never empty, and the notice type: success, warning, or error.
 */
function es_optimizer_autoload_get_result_lines( array $result ): array {
	if ( '' !== $result['notice'] ) {
		$line = es_optimizer_autoload_get_notice_message( $result['notice'] );

		return [
			'lines' => [ $line ],
			'type'  => 'recording_failed' === $result['notice'] ? 'error' : 'success',
		];
	}

	$changed = $result['changed'];
	$lines   = [];

	if ( $changed > 0 && 'failed' !== $result['check'] ) {
		$lines[] = sprintf(
			'changed' === $result['view']
				/* translators: %d: Number of options. */
				? _n( '%d option is loaded on every page again, with the setting it had before.', '%d options are loaded on every page again, with the settings they had before.', $changed, 'enginescript-site-optimizer' )
				/* translators: %d: Number of options. */
				: _n( '%d option is no longer loaded on every page.', '%d options are no longer loaded on every page.', $changed, 'enginescript-site-optimizer' ),
			$changed
		);
	}

	$check = es_optimizer_autoload_get_check_message( $result['check'], $result['undone'] );

	if ( '' !== $check ) {
		$lines[] = $check;
	}

	foreach ( $result['skipped'] as $reason => $count ) {
		$lines[] = es_optimizer_autoload_get_skip_message( $reason, $count );
	}

	return [
		'lines' => [] === $lines ? [ __( 'Nothing was selected, so nothing was changed.', 'enginescript-site-optimizer' ) ] : $lines,
		'type'  => es_optimizer_autoload_get_result_type( $result, count( $lines ) ),
	];
}

/**
 * Choose the notice type of a change's result.
 *
 * @since 2.5.0
 * @param array{view: string, changed: int, skipped: array<string, int>, check: string, undone: int, notice: string} $result Result of the change.
 * @param int                                                                                                        $lines  Number of lines before the fallback.
 * @return string success, warning, or error.
 */
function es_optimizer_autoload_get_result_type( array $result, int $lines ): string {
	return match ( true ) {
		'failed' === $result['check'] => 'error',
		0 === $result['changed'] || $lines > 1 => 'warning',
		default => 'success',
	};
}

/**
 * Describe the check that runs after options are switched off.
 *
 * @since 2.5.0
 * @param string $check  'ok', 'failed', 'unchecked', 'none', or empty.
 * @param int    $undone Number of options switched back on after a failed check.
 * @return string Message, or an empty string when there is nothing to say.
 */
function es_optimizer_autoload_get_check_message( string $check, int $undone ): string {
	return match ( $check ) {
		'ok' => __( 'The home page and the dashboard still load.', 'enginescript-site-optimizer' ),
		/* translators: %d: Number of options. */
		'failed' => sprintf( _n( 'After the change, the home page or the dashboard answered with a server error, so the change was undone: %d option is loaded on every page again.', 'After the change, the home page or the dashboard answered with a server error, so the change was undone: %d options are loaded on every page again.', $undone, 'enginescript-site-optimizer' ), $undone ),
		'unchecked' => __( 'The site could not be checked after the change, because it did not answer a request from itself. Open the home page and the dashboard to check them yourself.', 'enginescript-site-optimizer' ),
		default => '',
	};
}

/**
 * Get the message of a recording action.
 *
 * @since 2.5.0
 * @param string $notice Notice code.
 * @return string Message.
 */
function es_optimizer_autoload_get_notice_message( string $notice ): string {
	return match ( $notice ) {
		'recording_started' => __( 'Recording started. Load the pages you want to measure, on the front end and in the dashboard. Pages served from a page cache do not run WordPress, so they are not counted.', 'enginescript-site-optimizer' ),
		'recording_stopped' => __( 'Recording stopped. The results are kept until you clear them or start a new recording.', 'enginescript-site-optimizer' ),
		'results_cleared' => __( 'The recorded results were cleared.', 'enginescript-site-optimizer' ),
		default => __( 'The recording could not be changed. Reload the page to see the current state.', 'enginescript-site-optimizer' ),
	};
}

/**
 * Print the result of the last action, read from the redirect's query arguments.
 *
 * @since 2.5.0
 */
function es_optimizer_autoload_render_result(): void {
	$result = es_optimizer_autoload_read_result();

	if ( null === $result ) {
		return;
	}

	$notice = es_optimizer_autoload_get_result_lines( $result );

	echo '<div class="notice notice-' . esc_attr( $notice['type'] ) . '">';

	foreach ( $notice['lines'] as $line ) {
		echo '<p>' . esc_html( $line ) . '</p>';
	}

	echo '</div>';
}

/**
 * Get the help text of the screen: what it does, its effect, and its performance.
 *
 * @since 2.5.0
 * @return array{what: string, effect: string, performance: string} Help text.
 */
function es_optimizer_autoload_get_help(): array {
	return [
		'what'        => __( 'Lists the options that WordPress loads from the database on every page, largest first, and lets you stop loading the ones you select on every page. Only the option\'s autoload setting changes; its value stays in the database, and WordPress still reads it when code asks for it. WordPress does not record which plugin or theme created an option, so the "Probably from" column is an informed guess. To firm it up, search the installed code for the names on a page, or record which options pages actually read and which code reads them.', 'enginescript-site-optimizer' ),
		'effect'      => __( 'Code that needs a switched-off option still gets it, so a site rarely breaks; after each change the plugin loads your home page and the dashboard, and undoes the change at once if either answers with a server error. The best candidates are large options that no recorded page reads, or that no installed plugin or theme contains: those were usually left behind by a removed plugin. A plugin can switch an option back on when it saves it, and the second list shows when that has happened. Options that WordPress and this plugin need are locked. If a site still breaks, WP-CLI can undo every change, even with all plugins skipped: see the plugin\'s FAQ.', 'enginescript-site-optimizer' ),
		'performance' => __( 'Every page loads all of these options in one query, and with a persistent object cache, such as Redis, they are kept together in one cache entry, which a very large total can push past the cache server\'s size limit. A smaller total makes that load faster and lighter on memory. Each option you switch off that a page still needs costs that page one extra small query when no persistent object cache is in use. Recording adds a little work to each page while it runs, and the code search reads many files once, when you ask for it.', 'enginescript-site-optimizer' ),
	];
}

/**
 * Render the Autoloaded Options tab of the settings page.
 *
 * The settings page checks the capability before it prints any tab.
 *
 * @since 2.5.0
 */
function es_optimizer_autoload_render_tab(): void {
	$view = es_optimizer_autoload_read_view();
	$log  = es_optimizer_autoload_get_log();

	es_optimizer_autoload_render_result();
	es_optimizer_autoload_render_uninstall_notice( $log );
	es_optimizer_autoload_render_help();
	es_optimizer_autoload_render_summary();
	es_optimizer_autoload_render_views( $view['view'], count( $log ) );
	es_optimizer_autoload_render_list( $view );
}

/**
 * Read the list, page, and search the tab shows.
 *
 * @since 2.5.0
 * @SuppressWarnings("PHPMD.Superglobals") The one place that reads the view parameters; each is reduced to a known list, a page number, or plain text.
 * @return array{view: string, paged: int, search: string, find: bool} View parameters.
 */
function es_optimizer_autoload_read_view(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only view parameters.
	return [
		'view'   => isset( $_GET['view'] ) && is_string( $_GET['view'] ) && 'changed' === sanitize_key( wp_unslash( $_GET['view'] ) ) ? 'changed' : 'loaded',
		'paged'  => isset( $_GET['paged'] ) && is_string( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1,
		'search' => isset( $_GET['s'] ) && is_string( $_GET['s'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '',
		'find'   => es_optimizer_autoload_read_find(),
	];
	// phpcs:enable
}

/**
 * Check whether the code search was asked for.
 *
 * The code search reads many files, so it runs only from its own button, which carries a nonce.
 *
 * @since 2.5.0
 * @SuppressWarnings("PHPMD.Superglobals") Reads the code search button's nonce.
 * @return bool Whether the request carries a valid code search nonce.
 */
function es_optimizer_autoload_read_find(): bool {
	if ( ! isset( $_GET['es_find'] ) || ! is_string( $_GET['es_find'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The value is the nonce, checked below.
		return false;
	}

	return false !== wp_verify_nonce( sanitize_key( wp_unslash( $_GET['es_find'] ) ), 'es_optimizer_autoload_find' );
}

/**
 * Say that deleting the plugin restores the switched-off options, while there are any.
 *
 * @since 2.5.0
 * @param array<array-key, array{previous: string, time: int}> $log Logged changes.
 */
function es_optimizer_autoload_render_uninstall_notice( array $log ): void {
	if ( [] === $log ) {
		return;
	}
	?>
	<div class="notice notice-info inline">
		<p><?php esc_html_e( 'When you delete Site Optimizer, it first loads every option it switched off on every page again, with the setting each one had before. Deactivating the plugin changes nothing.', 'enginescript-site-optimizer' ); ?></p>
	</div>
	<?php
}

/**
 * Print the screen's help: what it does, its effect, and its performance.
 *
 * @since 2.5.0
 */
function es_optimizer_autoload_render_help(): void {
	$help   = es_optimizer_autoload_get_help();
	$labels = [
		'what'        => __( 'What it does:', 'enginescript-site-optimizer' ),
		'effect'      => __( 'Effect on your site:', 'enginescript-site-optimizer' ),
		'performance' => __( 'Performance:', 'enginescript-site-optimizer' ),
	];

	echo '<div class="description">';

	foreach ( $labels as $part => $label ) {
		printf( '<p><strong>%1$s</strong> %2$s</p>', esc_html( $label ), esc_html( $help[ $part ] ) );
	}

	echo '</div>';
}

/**
 * Print the links between the tab's two lists.
 *
 * @since 2.5.0
 * @param string $current Current list: 'loaded' or 'changed'.
 * @param int    $logged  Number of logged options.
 */
function es_optimizer_autoload_render_views( string $current, int $logged ): void {
	$views = [
		'loaded'  => [ es_optimizer_autoload_page_url(), __( 'Loaded on every page', 'enginescript-site-optimizer' ) ],
		/* translators: %d: Number of options switched off by the plugin. */
		'changed' => [ es_optimizer_autoload_page_url( [ 'view' => 'changed' ] ), sprintf( __( 'Switched off by Site Optimizer (%d)', 'enginescript-site-optimizer' ), $logged ) ],
	];
	$last = array_key_last( $views );

	printf( '<nav aria-label="%s"><ul class="subsubsub">', esc_attr__( 'Autoloaded option lists', 'enginescript-site-optimizer' ) );

	// As in core's list views, the separator sits inside each item, so the list holds list items only.
	foreach ( $views as $key => [ $url, $label ] ) {
		$active = $key === $current;
		printf(
			'<li><a href="%1$s"%2$s>%3$s</a>%4$s</li>',
			esc_url( $url ),
			$active ? ' class="current" aria-current="page"' : '',
			esc_html( $label ),
			$key === $last ? '' : ' |'
		);
	}

	echo '</ul></nav><div class="clear"></div>';
}

/**
 * Print the list of the current tab.
 *
 * @since 2.5.0
 * @param array{view: string, paged: int, search: string, find: bool} $view View parameters.
 */
function es_optimizer_autoload_render_list( array $view ): void {
	if ( 'changed' === $view['view'] ) {
		es_optimizer_autoload_render_changed_table();

		return;
	}

	es_optimizer_autoload_render_loaded_table( $view );
}

/**
 * Print the autoloaded total, compared with Site Health's limit.
 *
 * @since 2.5.0
 */
function es_optimizer_autoload_render_summary(): void {
	$summary = es_optimizer_autoload_get_summary();
	$cache   = wp_using_ext_object_cache()
		? __( 'A persistent object cache is in use.', 'enginescript-site-optimizer' )
		: __( 'No persistent object cache is in use.', 'enginescript-site-optimizer' );
	?>
	<p>
		<?php
		echo esc_html(
			sprintf(
				/* translators: 1: Number of options, 2: Total size such as "412 KB", 3: Site Health's limit such as "781 KB". */
				_n( '%1$d option is loaded on every page, %2$s in total. WordPress\'s Site Health check warns above %3$s.', '%1$d options are loaded on every page, %2$s in total. WordPress\'s Site Health check warns above %3$s.', $summary['count'], 'enginescript-site-optimizer' ),
				$summary['count'],
				es_optimizer_autoload_format_bytes( $summary['bytes'] ),
				es_optimizer_autoload_format_bytes( ES_SITE_OPTIMIZER_AUTOLOAD_HEALTH_LIMIT )
			)
		);
		echo ' ' . esc_html( $cache );
		?>
	</p>
	<?php
}

/**
 * Print the opening of a change form: nonce, action, and the table's start.
 *
 * @since 2.5.0
 * @param string                $action  Action the form submits.
 * @param string                $form_url Address the form posts to.
 * @param array<string, string> $columns Column headings after the checkbox column, keyed by column ID.
 */
function es_optimizer_autoload_open_form( string $action, string $form_url, array $columns ): void {
	?>
	<form method="post" action="<?php echo esc_url( $form_url ); /* nosemgrep: php.lang.security.injection.echoed-request.echoed-request */ ?>">
		<?php wp_nonce_field( 'es_optimizer_autoload' ); ?>
		<input type="hidden" name="es_optimizer_autoload_action" value="<?php echo esc_attr( $action ); ?>" />
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td class="manage-column column-cb check-column">
						<input id="es-optimizer-select-all" type="checkbox" />
						<label for="es-optimizer-select-all"><span class="screen-reader-text"><?php esc_html_e( 'Select all options on this page', 'enginescript-site-optimizer' ); ?></span></label>
					</td>
					<?php foreach ( $columns as $id => $heading ) : ?>
						<th scope="col" class="manage-column column-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $heading ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
	<?php
}

/**
 * Print the checkbox cell of an option row.
 *
 * @since 2.5.0
 * @param string $name  Option name.
 * @param int    $index Row number, for a unique ID.
 */
function es_optimizer_autoload_render_checkbox_cell( string $name, int $index ): void {
	$field_id = 'es-optimizer-option-' . $index;
	?>
	<th scope="row" class="check-column">
		<input id="<?php echo esc_attr( $field_id ); ?>" type="checkbox" name="option_names[]" value="<?php echo esc_attr( $name ); ?>" />
		<label for="<?php echo esc_attr( $field_id ); ?>"><span class="screen-reader-text">
			<?php
			/* translators: %s: Option name. */
			echo esc_html( sprintf( __( 'Select %s', 'enginescript-site-optimizer' ), $name ) ); /* nosemgrep: php.lang.security.injection.tainted-sql-string.tainted-sql-string */
			?>
		</span></label>
	</th>
	<?php
}

/**
 * Print the checkbox cell of a row in the main list; a locked option gets an empty cell.
 *
 * @since 2.5.0
 * @param string $name  Option name.
 * @param int    $index Row number, for a unique ID.
 */
function es_optimizer_autoload_render_switch_off_cell( string $name, int $index ): void {
	if ( es_optimizer_autoload_is_locked( $name ) ) {
		echo '<th scope="row" class="check-column"></th>';

		return;
	}

	es_optimizer_autoload_render_checkbox_cell( $name, $index );
}

/**
 * Print the list of options loaded on every page.
 *
 * @since 2.5.0
 * @param array{view: string, paged: int, search: string, find: bool} $view View parameters.
 */
function es_optimizer_autoload_render_loaded_table( array $view ): void {
	$paged   = $view['paged'];
	$search  = $view['search'];
	$page    = es_optimizer_autoload_get_page( $paged, $search );
	$pages   = max( 1, (int) ceil( $page['total'] / ES_SITE_OPTIMIZER_AUTOLOAD_PER_PAGE ) );
	$sources = es_optimizer_autoload_get_sources();
	$usage   = es_optimizer_autoload_get_usage();
	$args    = array_filter(
		[
			'paged' => $paged > 1 ? $paged : '',
			's'     => $search,
		]
	);

	es_optimizer_autoload_render_search_form( $search );
	es_optimizer_autoload_render_recording_panel( $usage );

	if ( $view['find'] ) {
		es_optimizer_autoload_render_code_search( array_column( $page['rows'], 'name' ), $sources );
	}

	es_optimizer_autoload_render_find_form( $paged, $search );
	$code    = es_optimizer_autoload_get_code_results( $sources );
	$columns = [
		'name'     => __( 'Option', 'enginescript-site-optimizer' ),
		'size'     => __( 'Size', 'enginescript-site-optimizer' ),
		'autoload' => __( 'Autoload setting', 'enginescript-site-optimizer' ),
	];

	if ( $usage['pages'] > 0 ) {
		$columns['usage'] = __( 'Read on', 'enginescript-site-optimizer' );
	}

	$columns['source'] = __( 'Probably from', 'enginescript-site-optimizer' );
	es_optimizer_autoload_open_form( 'switch_off', es_optimizer_autoload_page_url( $args ), $columns );

	if ( [] === $page['rows'] ) {
		printf( '<tr><td colspan="%d">%s</td></tr>', count( $columns ) + 1, esc_html__( 'No autoloaded options match.', 'enginescript-site-optimizer' ) );
	}

	foreach ( $page['rows'] as $index => $row ) {
		es_optimizer_autoload_render_loaded_row( $row, $index, $sources, $code[ $row['name'] ] ?? null, $usage );
	}
	?>
			</tbody>
		</table>
		<p>
			<?php
			submit_button( __( 'Stop loading the selected options on every page', 'enginescript-site-optimizer' ), 'primary', 'submit', false );
			echo ' ';
			echo esc_html(
				sprintf(
					/* translators: 1: Current page number, 2: Number of pages, 3: Number of matching options. */
					__( 'Page %1$d of %2$d (%3$d options).', 'enginescript-site-optimizer' ),
					min( $paged, $pages ),
					$pages,
					$page['total']
				)
			);
			es_optimizer_autoload_render_page_links( $paged, $pages, $search );
			?>
		</p>
	</form>
	<?php
}

/**
 * Print one row of the main list.
 *
 * @since 2.5.0
 * @param array{name: string, bytes: int, autoload: string}                                                                 $row     Option row.
 * @param int                                                                                                               $index   Row number, for a unique ID.
 * @param array<int, array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}> $sources Installed plugins and themes.
 * @param array{found: array<int, string>, searched: bool}|null                                                             $code    Code search result for the option, if any.
 * @param array{pages: int, options: array<array-key, array{pages: int, reader: string}>}                                   $usage   Results of the last usage recording.
 */
function es_optimizer_autoload_render_loaded_row( array $row, int $index, array $sources, ?array $code, array $usage ): void {
	$read   = $usage['options'][ $row['name'] ] ?? null;
	$reader = null !== $read && 'core' !== $read['reader'] ? $read['reader'] : null;
	$source = es_optimizer_autoload_guess_source( $row['name'], $sources, $code, $reader );
	$locked = es_optimizer_autoload_is_locked( $row['name'] );

	echo '<tr>';
	es_optimizer_autoload_render_switch_off_cell( $row['name'], $index );
	printf(
		'<td class="column-name"><code>%1$s</code>%2$s</td><td class="column-size">%3$s</td><td class="column-autoload">%4$s</td>',
		esc_html( $row['name'] ),
		$locked ? ' <span class="description">' . esc_html__( '(locked)', 'enginescript-site-optimizer' ) . '</span>' : '',
		esc_html( es_optimizer_autoload_format_bytes( $row['bytes'] ) ),
		esc_html( $row['autoload'] )
	);

	if ( $usage['pages'] > 0 ) {
		printf( '<td class="column-usage">%s</td>', esc_html( es_optimizer_autoload_usage_label( $read['pages'] ?? 0, $usage['pages'] ) ) );
	}

	printf( '<td class="column-source">%s</td></tr>', esc_html( $source['label'] ) );
}

/**
 * Describe how many recorded pages read an option.
 *
 * @since 2.5.0
 * @param int $read  Pages that read the option.
 * @param int $pages Pages recorded.
 * @return string Label.
 */
function es_optimizer_autoload_usage_label( int $read, int $pages ): string {
	if ( 0 === $read ) {
		return __( 'No recorded page', 'enginescript-site-optimizer' );
	}

	/* translators: 1: Pages that read the option, 2: Pages recorded. */
	return sprintf( _n( '%1$d of %2$d recorded page', '%1$d of %2$d recorded pages', $pages, 'enginescript-site-optimizer' ), $read, $pages );
}

/**
 * Print the option name search.
 *
 * @since 2.5.0
 * @param string $search Current search text.
 */
function es_optimizer_autoload_render_search_form( string $search ): void {
	?>
	<form method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" class="search-form">
		<input type="hidden" name="page" value="es-optimizer-settings" />
		<input type="hidden" name="tab" value="autoload" />
		<p class="search-box">
			<label for="es-optimizer-search"><?php esc_html_e( 'Search option names', 'enginescript-site-optimizer' ); ?></label>
			<input type="search" id="es-optimizer-search" name="s" value="<?php echo esc_attr( $search ); ?>" />
			<?php submit_button( __( 'Search', 'enginescript-site-optimizer' ), '', '', false ); ?>
		</p>
	</form>
	<?php
}

/**
 * Print the button that searches the installed code for the options on this page.
 *
 * @since 2.5.0
 * @param int    $paged  Current page.
 * @param string $search Current search text.
 */
function es_optimizer_autoload_render_find_form( int $paged, string $search ): void {
	?>
	<form method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">
		<input type="hidden" name="page" value="es-optimizer-settings" />
		<input type="hidden" name="tab" value="autoload" />
		<input type="hidden" name="paged" value="<?php echo esc_attr( (string) $paged ); ?>" />
		<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>" />
		<input type="hidden" name="es_find" value="<?php echo esc_attr( wp_create_nonce( 'es_optimizer_autoload_find' ) ); ?>" />
		<p>
			<?php submit_button( __( 'Search installed plugins and themes for the options on this page', 'enginescript-site-optimizer' ), 'secondary', '', false ); ?>
			<span class="description"><?php esc_html_e( 'Reads their PHP files to find which one uses each option name, for up to 20 seconds. Results are kept for a day.', 'enginescript-site-optimizer' ); ?></span>
		</p>
	</form>
	<?php
}

/**
 * Run the code search for the options on this page and say how it went.
 *
 * WordPress's own options, transients, and this plugin's options need no search.
 *
 * @since 2.5.0
 * @param array<int, string>                                                                                                $names   Option names on this page.
 * @param array<int, array{type: string, slug: string, name: string, active: bool, keys: array<int, string>, path: string}> $sources Installed plugins and themes.
 */
function es_optimizer_autoload_render_code_search( array $names, array $sources ): void {
	$names    = array_values( array_filter( $names, static fn ( string $name ): bool => null === es_optimizer_autoload_get_fixed_source( $name ) ) );
	$complete = es_optimizer_autoload_search_and_keep( $names, $sources );
	$message  = $complete
		? __( 'The installed plugins and themes were searched for the options on this page.', 'enginescript-site-optimizer' )
		: __( 'The search stopped after 20 seconds; the options it found are marked. Search again to continue with the rest.', 'enginescript-site-optimizer' );

	printf( '<div class="notice notice-%1$s inline"><p>%2$s</p></div>', $complete ? 'success' : 'warning', esc_html( $message ) );
}

/**
 * Print the usage recording controls and state.
 *
 * @since 2.5.0
 * @param array{pages: int, options: array<array-key, array{pages: int, reader: string}>} $usage Results of the last usage recording.
 */
function es_optimizer_autoload_render_recording_panel( array $usage ): void {
	$until = es_optimizer_autoload_recording_until();

	echo '<div class="es-optimizer-recording">';

	if ( $until > time() ) {
		printf(
			'<p>%s</p>',
			esc_html(
				sprintf(
					/* translators: 1: Time the recording ends, 2: Pages recorded so far. */
					_n( 'Recording which options pages read until %1$s: %2$d page so far.', 'Recording which options pages read until %1$s: %2$d pages so far.', $usage['pages'], 'enginescript-site-optimizer' ),
					es_optimizer_autoload_format_time( $until ),
					$usage['pages']
				)
			)
		);
		es_optimizer_autoload_render_action_button( 'record_stop', __( 'Stop recording', 'enginescript-site-optimizer' ) );
		echo '</div>';

		return;
	}

	echo '<p>' . esc_html__( 'Record which options pages read, and which plugin or theme reads each one: while recording, every page loaded on the front end or in the dashboard is noted, at a small cost per page.', 'enginescript-site-optimizer' ) . '</p>';
	es_optimizer_autoload_render_action_button( 'record_start', __( 'Record for 10 minutes', 'enginescript-site-optimizer' ), [ 'minutes' => '10' ] );
	es_optimizer_autoload_render_action_button( 'record_start', __( 'Record for 1 hour', 'enginescript-site-optimizer' ), [ 'minutes' => '60' ] );

	if ( $usage['pages'] > 0 ) {
		es_optimizer_autoload_render_action_button( 'record_clear', __( 'Clear the recorded results', 'enginescript-site-optimizer' ) );
	}

	echo '</div>';
}

/**
 * Print a one-button form that posts an action with the screen's nonce.
 *
 * @since 2.5.0
 * @param string                $action Action key.
 * @param string                $label  Button text.
 * @param array<string, string> $fields More hidden fields.
 */
function es_optimizer_autoload_render_action_button( string $action, string $label, array $fields = [] ): void {
	echo '<form method="post" action="' . esc_url( es_optimizer_autoload_page_url() ) . '" style="display:inline-block;margin:0 8px 8px 0">';
	wp_nonce_field( 'es_optimizer_autoload' );
	echo '<input type="hidden" name="es_optimizer_autoload_action" value="' . esc_attr( $action ) . '" />';

	foreach ( $fields as $name => $value ) {
		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
	}

	submit_button( $label, 'secondary', '', false );
	echo '</form>';
}

/**
 * Format a stored size for display.
 *
 * @since 2.5.0
 * @param int $bytes Size in bytes.
 * @return string Size such as "4.2 KB", or "0 B" for an empty value.
 */
function es_optimizer_autoload_format_bytes( int $bytes ): string {
	$formatted = size_format( $bytes, 1 );

	return is_string( $formatted ) ? $formatted : '0 B';
}

/**
 * Format when an option was switched off.
 *
 * @since 2.5.0
 * @param int $time Unix time, or 0 when unknown.
 * @return string Date and time in the site's time zone, or an empty string.
 */
function es_optimizer_autoload_format_time( int $time ): string {
	$formatted = $time > 0 ? wp_date( 'Y-m-d H:i', $time ) : '';

	return is_string( $formatted ) ? $formatted : '';
}

/**
 * Print the previous and next page links.
 *
 * @since 2.5.0
 * @param int    $paged  Current page.
 * @param int    $pages  Number of pages.
 * @param string $search Name filter.
 */
function es_optimizer_autoload_render_page_links( int $paged, int $pages, string $search ): void {
	$base = '' !== $search ? [ 's' => $search ] : [];

	if ( $paged > 1 ) {
		printf( ' <a href="%s">%s</a>', esc_url( es_optimizer_autoload_page_url( $base + [ 'paged' => $paged - 1 ] ) ), esc_html__( 'Previous page', 'enginescript-site-optimizer' ) );
	}

	if ( $paged < $pages ) {
		printf( ' <a href="%s">%s</a>', esc_url( es_optimizer_autoload_page_url( $base + [ 'paged' => $paged + 1 ] ) ), esc_html__( 'Next page', 'enginescript-site-optimizer' ) );
	}
}

/**
 * Get the label of a logged option's current state.
 *
 * @since 2.5.0
 * @param string $state State code from es_optimizer_autoload_get_logged_rows().
 * @return string Label.
 */
function es_optimizer_autoload_get_state_label( string $state ): string {
	return match ( $state ) {
		'off' => __( 'Not loaded on every page', 'enginescript-site-optimizer' ),
		'reenabled' => __( 'Loaded on every page again by other code', 'enginescript-site-optimizer' ),
		'deleted' => __( 'Deleted by other code', 'enginescript-site-optimizer' ),
		default => __( 'Setting changed by other code', 'enginescript-site-optimizer' ),
	};
}

/**
 * Print the list of options the plugin switched off.
 *
 * @since 2.5.0
 */
function es_optimizer_autoload_render_changed_table(): void {
	$rows = es_optimizer_autoload_get_logged_rows();

	if ( null === $rows ) {
		echo '<div class="notice notice-error inline"><p>' . esc_html__( 'The current settings of these options could not be read. Reload the page to try again.', 'enginescript-site-optimizer' ) . '</p></div>';

		return;
	}

	es_optimizer_autoload_open_form(
		'switch_on',
		es_optimizer_autoload_page_url( [ 'view' => 'changed' ] ),
		[
			'name'     => __( 'Option', 'enginescript-site-optimizer' ),
			'size'     => __( 'Size', 'enginescript-site-optimizer' ),
			'previous' => __( 'Setting before', 'enginescript-site-optimizer' ),
			'state'    => __( 'Now', 'enginescript-site-optimizer' ),
			'time'     => __( 'Switched off', 'enginescript-site-optimizer' ),
		]
	);

	if ( [] === $rows ) {
		echo '<tr><td colspan="6">' . esc_html__( 'Site Optimizer has not switched off any option.', 'enginescript-site-optimizer' ) . '</td></tr>';
	}

	foreach ( $rows as $index => $row ) {
		echo '<tr>';
		es_optimizer_autoload_render_checkbox_cell( $row['name'], $index );
		printf(
			'<td class="column-name"><code>%1$s</code></td><td class="column-size">%2$s</td><td class="column-previous">%3$s</td><td class="column-state">%4$s</td><td class="column-time">%5$s</td></tr>',
			esc_html( $row['name'] ),
			esc_html( es_optimizer_autoload_format_bytes( $row['bytes'] ) ),
			esc_html( $row['previous'] ),
			esc_html( es_optimizer_autoload_get_state_label( $row['state'] ) ),
			esc_html( es_optimizer_autoload_format_time( $row['time'] ) )
		);
	}
	?>
			</tbody>
		</table>
		<p><?php submit_button( __( 'Load the selected options on every page again', 'enginescript-site-optimizer' ), 'primary', 'submit', false ); ?></p>
	</form>
	<?php
}

/**
 * Warn under the plugin's row on the Plugins screen that deleting it restores the switched-off options.
 *
 * @since 2.5.0
 * @global WP_List_Table|null $wp_list_table The Plugins screen's list table.
 */
function es_optimizer_autoload_plugin_row_notice(): void {
	global $wp_list_table;

	if ( ! es_optimizer_autoload_has_changes_to_restore() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$columns = $wp_list_table instanceof WP_List_Table ? $wp_list_table->get_column_count() : 4;
	?>
	<tr class="plugin-update-tr active">
		<td colspan="<?php echo esc_attr( (string) $columns ); ?>" class="plugin-update colspanchange">
			<div class="notice inline notice-warning notice-alt">
				<p><?php esc_html_e( 'Site Optimizer has stopped some options from loading on every page. Deleting the plugin loads them on every page again, with the settings they had before. Deactivating it changes nothing.', 'enginescript-site-optimizer' ); ?></p>
			</div>
		</td>
	</tr>
	<?php
}

/**
 * Check whether deleting the plugin would restore any switched-off option.
 *
 * On a network, plugins are deleted from the network Plugins screen, which reads
 * the network's list of sites with changes.
 *
 * @since 2.5.0
 * @return bool Whether there are logged changes.
 */
function es_optimizer_autoload_has_changes_to_restore(): bool {
	if ( ! is_network_admin() ) {
		return [] !== es_optimizer_autoload_get_log();
	}

	return [] !== es_optimizer_autoload_get_listed_sites();
}
