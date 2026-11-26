<?php
/**
 * Test for uninstall.php's own failure handling: a failed removal stops with wp_die() before
 * WordPress deletes the plugin files.
 *
 * @package EngineScript_Site_Optimizer
 */

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Loads uninstall.php in a fresh process, because loading it runs it and it can load only once.
 */
final class UninstallFailureTest extends EsOptimizerTestCase {

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_a_failed_uninstall_stops_with_wp_die_before_wordpress_deletes_files(): void {
		define( 'WP_UNINSTALL_PLUGIN', 'enginescript-site-optimizer/enginescript-site-optimizer.php' );
		$database            = new wpdb();
		$database->responder = static fn (): int => 1;
		$GLOBALS['wpdb']     = $database;

		try {
			require dirname( __DIR__, 2 ) . '/uninstall.php';
		} catch ( Es_Optimizer_Test_Die $die ) {
			$this->assertStringEndsWith( 'Reason: The settings of site 1 are still stored after deletion.', $die->getMessage() );
			$this->assertFalse( $database->suppressing );

			return;
		}

		$this->fail( 'The uninstall did not stop.' );
	}
}
