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
		array(
			'sanitize_callback' => 'es_optimizer_validate_options',
			'default'           => es_optimizer_get_default_options(),
			'show_in_rest'      => false,
			'type'              => 'array',
		)
	);

	if ( false === get_option( 'es_optimizer_options' ) ) {
		add_option( 'es_optimizer_options', es_optimizer_get_default_options() );
	}

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
			$section_title,
			'__return_null',
			$page
		);
	}

	foreach ( es_optimizer_get_settings_fields() as $field ) {
		if ( 'textarea' === $field['type'] ) {
			es_optimizer_register_textarea_field( $page, $field['section'], $field['option'], $field['title'], $field['description'] );
		} else {
			es_optimizer_register_checkbox_field( $page, $field['section'], $field['option'], $field['title'], $field['description'] );
		}
	}
}

/**
 * Get settings section labels.
 *
 * @since 2.0.0
 * @return array<string, string> Section labels keyed by section ID.
 */
function es_optimizer_get_settings_sections(): array {
	return array(
		'es_optimizer_performance'         => __( 'Performance Optimizations', 'enginescript-site-optimizer' ),
		'es_optimizer_header_cleanup'      => __( 'Header Cleanup', 'enginescript-site-optimizer' ),
		'es_optimizer_additional_features' => __( 'Additional Features', 'enginescript-site-optimizer' ),
	);
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

	return array(
		es_optimizer_get_checkbox_field_definition(
			$section,
			'disable_emojis',
			__( 'Disable WordPress Emojis', 'enginescript-site-optimizer' ),
			__( 'Remove emoji scripts and styles to improve page load time', 'enginescript-site-optimizer' )
		),
		es_optimizer_get_checkbox_field_definition(
			$section,
			'remove_jquery_migrate',
			__( 'Remove jQuery Migrate', 'enginescript-site-optimizer' ),
			__( 'Remove jQuery Migrate script. This may affect compatibility with very old plugins.', 'enginescript-site-optimizer' )
		),
		es_optimizer_get_checkbox_field_definition(
			$section,
			'disable_classic_theme_styles',
			__( 'Disable Classic Theme Styles', 'enginescript-site-optimizer' ),
			__( 'Remove classic theme styles added in WordPress 6.1+', 'enginescript-site-optimizer' )
		),
	);
}

/**
 * Get header cleanup settings field definitions.
 *
 * @since 2.0.0
 * @return array<int, array{type: string, section: string, option: string, title: string, description: string}>
 */
function es_optimizer_get_header_cleanup_settings_fields(): array {
	$section = 'es_optimizer_header_cleanup';

	return array(
		es_optimizer_get_checkbox_field_definition(
			$section,
			'remove_wp_version',
			__( 'Remove WordPress Version', 'enginescript-site-optimizer' ),
			__( 'Remove WordPress version from the document head.', 'enginescript-site-optimizer' )
		),
		es_optimizer_get_checkbox_field_definition(
			$section,
			'remove_rsd_link',
			__( 'Remove RSD Link', 'enginescript-site-optimizer' ),
			__( 'Remove Really Simple Discovery (RSD) link from the document head.', 'enginescript-site-optimizer' )
		),
		es_optimizer_get_checkbox_field_definition(
			$section,
			'remove_wlw_manifest',
			__( 'Remove WLW Manifest', 'enginescript-site-optimizer' ),
			__( 'Remove Windows Live Writer manifest link.', 'enginescript-site-optimizer' )
		),
		es_optimizer_get_checkbox_field_definition(
			$section,
			'remove_shortlink',
			__( 'Remove Shortlink', 'enginescript-site-optimizer' ),
			__( 'Remove WordPress shortlink URLs from the document head.', 'enginescript-site-optimizer' )
		),
		es_optimizer_get_checkbox_field_definition(
			$section,
			'remove_recent_comments_style',
			__( 'Remove Recent Comments Style', 'enginescript-site-optimizer' ),
			__( 'Remove recent comments widget inline CSS.', 'enginescript-site-optimizer' )
		),
	);
}

/**
 * Get additional settings field definitions.
 *
 * @since 2.0.0
 * @return array<int, array{type: string, section: string, option: string, title: string, description: string}>
 */
function es_optimizer_get_additional_settings_fields(): array {
	$section = 'es_optimizer_additional_features';

	return array(
		es_optimizer_get_checkbox_field_definition(
			$section,
			'disable_jetpack_ads',
			__( 'Disable Jetpack Ads', 'enginescript-site-optimizer' ),
			__( 'Remove Jetpack advertisements and promotions.', 'enginescript-site-optimizer' )
		),
		es_optimizer_get_checkbox_field_definition(
			$section,
			'disable_post_via_email',
			__( 'Disable Post via Email', 'enginescript-site-optimizer' ),
			__( 'Disable WordPress post via email functionality for security and performance.', 'enginescript-site-optimizer' )
		),
		es_optimizer_get_checkbox_field_definition(
			$section,
			'enable_preconnect',
			__( 'Enable Preconnect', 'enginescript-site-optimizer' ),
			__( 'Preconnect to external domains for faster resource loading.', 'enginescript-site-optimizer' )
		),
		es_optimizer_get_textarea_field_definition(
			$section,
			'preconnect_domains',
			__( 'Preconnect Domains', 'enginescript-site-optimizer' ),
			__( 'Use preconnect for domains that host critical, frequently used resources, such as Google Fonts. Enter one HTTPS domain per line. Only bare domains are allowed: no file paths, query parameters, fragments, or credentials.', 'enginescript-site-optimizer' )
		),
		es_optimizer_get_checkbox_field_definition(
			$section,
			'enable_dns_prefetch',
			__( 'Enable DNS Prefetch', 'enginescript-site-optimizer' ),
			__( 'DNS prefetch for less critical external domains.', 'enginescript-site-optimizer' )
		),
		es_optimizer_get_textarea_field_definition(
			$section,
			'dns_prefetch_domains',
			__( 'DNS Prefetch Domains', 'enginescript-site-optimizer' ),
			__( 'DNS prefetch is a lighter-weight alternative to preconnect that performs only the DNS lookup. Enter one HTTPS domain per line. Only bare domains are allowed: no file paths, query parameters, fragments, or credentials.', 'enginescript-site-optimizer' )
		),
	);
}

/**
 * Build a checkbox settings field definition.
 *
 * @since 2.0.0
 * @param string $section     Settings section ID.
 * @param string $option_name Option name.
 * @param string $title       Field title.
 * @param string $description Field description.
 * @return array{type: string, section: string, option: string, title: string, description: string}
 */
function es_optimizer_get_checkbox_field_definition( string $section, string $option_name, string $title, string $description ): array {
	return es_optimizer_get_field_definition( 'checkbox', $section, $option_name, $title, $description );
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
	return array(
		'type'        => $type,
		'section'     => $section,
		'option'      => $option_name,
		'title'       => $title,
		'description' => $description,
	);
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
		$es_optimizer_field_descriptions = array();
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

	return (string) ( $es_optimizer_field_descriptions[ $option_name ] ?? '' );
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
 * @since 2.0.0
 * @param array{label_for?: string, class?: string} $args Field arguments.
 * @return string Option name.
 */
function es_optimizer_get_option_name_from_field_args( array $args ): string {
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
 * @since 2.0.0
 * @param string $page        Settings page slug.
 * @param string $section     Settings section ID.
 * @param string $option_name Option name.
 * @param string $title       Field title.
 * @param string $description Field description.
 */
function es_optimizer_register_checkbox_field( string $page, string $section, string $option_name, string $title, string $description ): void {
	es_optimizer_register_field_description( $option_name, $description );

	add_settings_field(
		$option_name,
		$title,
		'es_optimizer_render_checkbox_field',
		$page,
		$section,
		array(
			'label_for' => es_optimizer_get_field_id( $option_name ),
			'class'     => 'es-optimizer-field',
		)
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
		$title,
		'es_optimizer_render_textarea_field',
		$page,
		$section,
		array(
			'label_for' => es_optimizer_get_field_id( $option_name ),
			'class'     => 'es-optimizer-field',
		)
	);
}

/**
 * Add settings page to the admin menu.
 *
 * @since 1.0.0
 */
function es_optimizer_add_settings_page(): void {
	add_options_page(
		__( 'Site Optimizer Settings', 'enginescript-site-optimizer' ),
		__( 'Site Optimizer', 'enginescript-site-optimizer' ),
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
		<?php settings_errors( 'es_optimizer_options' ); ?>

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
			</a>
		</p>
	</div>
	<?php
}

/**
 * Render a checkbox option field.
 *
 * @since 1.0.0
 * @param array{label_for?: string, class?: string} $args Field arguments.
 */
function es_optimizer_render_checkbox_field( array $args ): void {
	$options     = es_optimizer_get_options();
	$option_name = es_optimizer_get_option_name_from_field_args( $args );

	if ( ! array_key_exists( $option_name, es_optimizer_get_default_options() ) ) {
		return;
	}

	$field_id    = es_optimizer_get_field_id( $option_name );
	$description = es_optimizer_get_field_description( $option_name );
	?>
	<label>
		<input id="<?php echo esc_attr( $field_id ); ?>" type="checkbox" name="<?php echo esc_attr( "es_optimizer_options[{$option_name}]" ); ?>" value="1" <?php checked( 1, (int) ( $options[ $option_name ] ?? 0 ) ); ?> />
		<?php echo esc_html( $description ); ?>
	</label>
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
	$description    = es_optimizer_get_field_description( $option_name );
	$textarea_value = $options[ $option_name ] ?? '';
	?>
	<p class="description"><?php echo esc_html( $description ); ?></p>
	<textarea id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( "es_optimizer_options[{$option_name}]" ); ?>" rows="5" cols="50" class="large-text code"><?php echo esc_textarea( (string) $textarea_value ); ?></textarea>
	<?php
}

/**
 * Add settings link to plugins page.
 *
 * @since 1.0.0
 * @param array<int|string, string> $links Plugin action links.
 * @return array<int|string, string> Modified plugin action links.
 */
function es_optimizer_add_settings_link( array $links ): array {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=es-optimizer-settings' ) ) . '">' . esc_html__( 'Settings', 'enginescript-site-optimizer' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
