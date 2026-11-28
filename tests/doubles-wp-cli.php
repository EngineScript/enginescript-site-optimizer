<?php
/**
 * A WP-CLI double for the command tests: it records registrations, output, and
 * formatted lists, and turns WP_CLI::error() into an exception instead of exiting.
 *
 * Only AutoloadCommandTest loads it, so no other test sees a WP_CLI class.
 *
 * @package EngineScript_Site_Optimizer
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile, PSR1.Classes.ClassDeclaration.MultipleClasses, Universal.Namespaces.DisallowCurlyBraceSyntax, Universal.Namespaces.OneDeclarationPerFile

namespace {

	/**
	 * Thrown where WP_CLI::error() would end the process.
	 */
	final class Es_Optimizer_Test_Cli_Error extends RuntimeException {
	}

	/**
	 * Records what the command asks WP-CLI to do.
	 */
	final class WP_CLI {

		/**
		 * Registered commands, by name.
		 *
		 * @var array<string, string>
		 */
		public static array $commands = array();

		/**
		 * Printed messages, each as [type, message].
		 *
		 * @var array<int, array{0: string, 1: string}>
		 */
		public static array $output = array();

		/**
		 * Lists passed to format_items(), each as [format, items, fields].
		 *
		 * @var array<int, array{0: string, 1: array<int, array<string, mixed>>, 2: array<int, string>}>
		 */
		public static array $formatted = array();

		/**
		 * Record a registered command.
		 *
		 * @param string $name    Command name.
		 * @param string $handler Class name.
		 * @return bool Always true.
		 */
		public static function add_command( string $name, string $handler ): bool {
			self::$commands[ $name ] = $handler;

			return true;
		}

		/**
		 * Record a success message.
		 *
		 * @param string $message Message.
		 * @return void
		 */
		public static function success( string $message ): void {
			self::$output[] = array( 'success', $message );
		}

		/**
		 * Record a warning.
		 *
		 * @param string $message Message.
		 * @return void
		 */
		public static function warning( string $message ): void {
			self::$output[] = array( 'warning', $message );
		}

		/**
		 * Record an error and stop, as WP-CLI does.
		 *
		 * @param string $message Message.
		 * @return never
		 * @throws Es_Optimizer_Test_Cli_Error Always.
		 */
		public static function error( string $message ): never {
			self::$output[] = array( 'error', $message );

			throw new Es_Optimizer_Test_Cli_Error( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Test double.
		}

		/**
		 * Forget everything recorded, except the registrations.
		 *
		 * @return void
		 */
		public static function reset(): void {
			self::$output    = array();
			self::$formatted = array();
		}
	}
}

namespace WP_CLI\Utils {

	/**
	 * Record a formatted list.
	 *
	 * @param string                           $format Output format.
	 * @param array<int, array<string, mixed>> $items  Rows.
	 * @param array<int, string>               $fields Columns.
	 * @return void
	 */
	function format_items( string $format, array $items, array $fields ): void {
		\WP_CLI::$formatted[] = array( $format, $items, $fields );
	}
}
