<?php
/**
 * WP-CLI commands to list and restore the options whose autoloading Site Optimizer switched off.
 *
 * The plugin loads this file under WP-CLI. It also works on its own, for a site
 * that a plugin breaks, with every plugin and theme skipped:
 *
 *     wp --skip-plugins --skip-themes \
 *       --require=wp-content/plugins/enginescript-site-optimizer/includes/class-es-optimizer-autoload-command.php \
 *       site-optimizer autoload restore --all
 *
 * @package EngineScript_Site_Optimizer
 */

// Loaded by WP-CLI, possibly before WordPress defines ABSPATH (as with --require); never by a web request.
defined( 'WP_CLI' ) || defined( 'ABSPATH' ) || exit;

// PHP declares the class below when it compiles this file, so the guard tests WP-CLI only;
// require_once keeps the plugin and --require from loading the file twice.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * List and restore the options Site Optimizer stopped from loading on every page.
 *
 * @since 2.5.0
 * @SuppressWarnings("PHPMD.StaticAccess") WP-CLI's API is static.
 * @SuppressWarnings("PHPMD.CamelCaseNamespace") PHPMD reads the WordPress package name as a namespace.
 * @psalm-api WP-CLI calls it by name.
 */
final class ES_Optimizer_Autoload_Command {

	/**
	 * Lists the options Site Optimizer stopped from loading on every page.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp site-optimizer autoload list
	 *
	 * @subcommand list
	 * @since 2.5.0
	 * @param array<int, string>    $args       Positional arguments; none.
	 * @param array<string, string> $assoc_args Named arguments.
	 */
	public function list_changes( array $args, array $assoc_args ): void {
		unset( $args );
		$this->load();
		$rows = es_optimizer_autoload_get_logged_rows();

		if ( null === $rows ) {
			WP_CLI::error( 'The stored autoload settings could not be read.' );

			return;
		}

		$items = array_map(
			static fn ( array $row ): array => [
				'option'       => $row['name'],
				'setting'      => $row['previous'],
				'now'          => $row['state'],
				'switched_off' => $row['time'] > 0 ? gmdate( 'Y-m-d H:i', $row['time'] ) . ' UTC' : '',
			],
			$rows
		);

		WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $items, [ 'option', 'setting', 'now', 'switched_off' ] );
	}

	/**
	 * Loads options on every page again, with the autoload setting each one had before.
	 *
	 * An option that other code has changed or deleted since is only taken off
	 * the list. On a network, run it for each site with --url.
	 *
	 * ## OPTIONS
	 *
	 * [<option>...]
	 * : Names of the options to restore.
	 *
	 * [--all]
	 * : Restore every option Site Optimizer switched off.
	 *
	 * ## EXAMPLES
	 *
	 *     wp site-optimizer autoload restore --all
	 *     wp site-optimizer autoload restore oldplugin_cache
	 *
	 *     # On a site a plugin breaks, with every plugin and theme skipped:
	 *     wp --skip-plugins --skip-themes --require=wp-content/plugins/enginescript-site-optimizer/includes/class-es-optimizer-autoload-command.php site-optimizer autoload restore --all
	 *
	 * @since 2.5.0
	 * @param array<int, string>    $args       Option names.
	 * @param array<string, string> $assoc_args Named arguments.
	 */
	public function restore( array $args, array $assoc_args ): void {
		$this->load();
		$names = isset( $assoc_args['all'] ) ? array_map( strval( ... ), array_keys( es_optimizer_autoload_get_log() ) ) : $args;

		if ( [] === $names ) {
			WP_CLI::error( isset( $assoc_args['all'] ) ? 'Site Optimizer has not switched off any option.' : 'Name the options to restore, or use --all.' );
		}

		$restored = 0;
		$skipped  = [];

		foreach ( array_chunk( $names, ES_SITE_OPTIMIZER_AUTOLOAD_MAX_CHANGES ) as $chunk ) {
			$result    = es_optimizer_autoload_switch_on( $chunk );
			$restored += count( $result['changed'] );

			foreach ( $result['skipped'] as $reason => $count ) {
				$skipped[ $reason ] = ( $skipped[ $reason ] ?? 0 ) + $count;
			}
		}

		foreach ( $skipped as $reason => $count ) {
			WP_CLI::warning( sprintf( '%d skipped: %s', $count, $this->reason( $reason ) ) );
		}

		if ( isset( $skipped['failed'] ) || isset( $skipped['log_failed'] ) ) {
			WP_CLI::error( sprintf( '%d restored; some options could not be restored.', $restored ) );
		}

		WP_CLI::success( sprintf( '%d restored.', $restored ) );
	}

	/**
	 * Describe a reason an option was skipped.
	 *
	 * @since 2.5.0
	 * @param string $reason Reason code.
	 * @return string Description.
	 */
	private function reason( string $reason ): string {
		return match ( $reason ) {
			'not_logged' => 'not switched off by Site Optimizer',
			'changed_elsewhere' => 'already changed or deleted by other code, taken off the list',
			'failed' => 'the database update failed',
			'log_failed' => 'the list of changes could not be saved',
			'invalid' => 'not a valid option name',
			default => $reason,
		};
	}

	/**
	 * Load the restore code, which needs no other plugin file, when the plugin itself is skipped.
	 *
	 * @since 2.5.0
	 */
	private function load(): void {
		if ( ! function_exists( 'es_optimizer_autoload_switch_on' ) ) {
			require_once __DIR__ . '/autoload.php';
		}
	}
}

WP_CLI::add_command( 'site-optimizer autoload', 'ES_Optimizer_Autoload_Command' );
