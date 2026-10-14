<?php
/**
 * Tests for the settings page, its registration, and what it prints.
 *
 * @package EngineScript_Site_Optimizer
 */

/**
 * The menu entry, the Settings API registration, the page, the fields, and the action link.
 */
final class SettingsPageTest extends EsOptimizerTestCase {

	/**
	 * The page is added under Settings for administrators.
	 *
	 * @return void
	 */
	public function test_menu_entry(): void {
		es_optimizer_add_settings_page();

		$this->assertSame(
			array(
				array(
					'page_title' => 'Site Optimizer Settings',
					'menu_title' => 'Site Optimizer',
					'capability' => 'manage_options',
					'menu_slug'  => 'es-optimizer-settings',
					'callback'   => 'es_optimizer_settings_page',
				),
			),
			$GLOBALS['es_optimizer_test']['pages']
		);
	}

	/**
	 * A translation cannot put markup into the menu, which WordPress prints unescaped (CR-SEC-001).
	 *
	 * @return void
	 */
	public function test_menu_titles_are_escaped_under_a_hostile_translation(): void {
		$GLOBALS['es_optimizer_test']['translations'] = array(
			'Site Optimizer'          => '<img src=x onerror=alert(1)>Menu',
			'Site Optimizer Settings' => '<script>alert(2)</script>Page',
		);
		es_optimizer_add_settings_page();
		$page = $GLOBALS['es_optimizer_test']['pages'][0];

		$this->assertSame( '&lt;img src=x onerror=alert(1)&gt;Menu', $page['menu_title'] );
		$this->assertSame( '&lt;script&gt;alert(2)&lt;/script&gt;Page', $page['page_title'] );
	}

	/**
	 * The option is registered once, with its sanitizer, its defaults, and no REST exposure.
	 *
	 * @return void
	 */
	public function test_setting_registration(): void {
		es_optimizer_init_settings();
		$setting = $GLOBALS['es_optimizer_test']['settings'];

		$this->assertCount( 1, $setting );
		$this->assertSame( 'es_optimizer_settings', $setting[0]['option_group'] );
		$this->assertSame( 'es_optimizer_options', $setting[0]['option_name'] );
		$this->assertSame( 'es_optimizer_validate_options', $setting[0]['args']['sanitize_callback'] );
		$this->assertSame( es_optimizer_get_default_options(), $setting[0]['args']['default'] );
		$this->assertFalse( $setting[0]['args']['show_in_rest'] );
		$this->assertSame( 'array', $setting[0]['args']['type'] );
	}

	/**
	 * Three sections in order, and one field for every option, each in its section.
	 *
	 * @return void
	 */
	public function test_sections_and_fields_cover_every_option(): void {
		es_optimizer_init_settings();
		$sections = $GLOBALS['es_optimizer_test']['sections'];
		$fields   = $GLOBALS['es_optimizer_test']['fields'];

		$this->assertSame( array( 'es_optimizer_performance', 'es_optimizer_header_cleanup', 'es_optimizer_additional_features' ), array_column( $sections, 'id' ) );
		$this->assertSame( array( 'Performance Optimizations', 'Header Cleanup', 'Additional Features' ), array_column( $sections, 'title' ) );
		$this->assertSame( array( 'es-optimizer-settings' ), array_values( array_unique( array_column( $sections, 'page' ) ) ) );

		$this->assertSame( array_keys( es_optimizer_get_default_options() ), $this->field_options_in_default_order( $fields ), 'Each option has exactly one field.' );
		$by_section = array();
		foreach ( $fields as $field ) {
			$by_section[ $field['section'] ][] = $field['id'];
			$this->assertSame( 'es_optimizer_field_' . $field['id'], $field['args']['label_for'] );
			$this->assertSame( 'es-optimizer-field', $field['args']['class'] );
			$expected_callback = str_ends_with( $field['id'], '_domains' ) ? 'es_optimizer_render_textarea_field' : 'es_optimizer_render_checkbox_field';
			$this->assertSame( $expected_callback, $field['callback'], $field['id'] );
		}
		$this->assertSame( array( 'disable_emojis', 'remove_jquery_migrate', 'disable_classic_theme_styles' ), $by_section['es_optimizer_performance'] );
		$this->assertSame( array( 'remove_wp_version', 'remove_rsd_link', 'remove_shortlink', 'remove_recent_comments_style' ), $by_section['es_optimizer_header_cleanup'] );
		$this->assertSame( array( 'disable_jetpack_ads', 'disable_post_via_email', 'enable_preconnect', 'preconnect_domains', 'enable_dns_prefetch', 'dns_prefetch_domains' ), $by_section['es_optimizer_additional_features'] );
	}

	/**
	 * Section and field titles are escaped when they are registered; WordPress prints them as given.
	 *
	 * @return void
	 */
	public function test_titles_are_escaped_when_registered(): void {
		$GLOBALS['es_optimizer_test']['translations'] = array(
			'Header Cleanup'   => '<b>Header</b>',
			'Remove RSD Link'  => '<i>RSD</i>',
			'DNS Prefetch Domains' => '"DNS" & <u>lists</u>',
		);
		es_optimizer_init_settings();

		$this->assertContains( '&lt;b&gt;Header&lt;/b&gt;', array_column( $GLOBALS['es_optimizer_test']['sections'], 'title' ) );
		$titles = array_column( $GLOBALS['es_optimizer_test']['fields'], 'title', 'id' );
		$this->assertSame( '&lt;i&gt;RSD&lt;/i&gt;', $titles['remove_rsd_link'] );
		$this->assertSame( '&quot;DNS&quot; &amp; &lt;u&gt;lists&lt;/u&gt;', $titles['dns_prefetch_domains'] );
	}

	/**
	 * Without manage_options the page stops before printing anything.
	 *
	 * @return void
	 */
	public function test_page_refuses_a_user_without_the_capability(): void {
		$GLOBALS['es_optimizer_test']['capabilities'] = array( 'read' );

		try {
			$output = $this->capture( 'es_optimizer_settings_page' );
			$this->fail( 'The page rendered for a user without manage_options: ' . $output );
		} catch ( Es_Optimizer_Test_Die $stop ) {
			$this->assertSame( 'You do not have sufficient permissions to access this page.', $stop->getMessage() );
		}
	}

	/**
	 * The page posts to options.php with the plugin's group, prints all fields, and labels its new-tab link.
	 *
	 * @return void
	 */
	public function test_page_prints_the_form_and_the_footer_link(): void {
		es_optimizer_init_settings();
		$page = $this->capture( 'es_optimizer_settings_page' );

		$this->assertStringContainsString( '<h1>Site Optimizer Settings</h1>', $page );
		$this->assertStringContainsString( '<form method="post" action="options.php">', $page );
		$this->assertStringContainsString( 'name="option_page" value="es_optimizer_settings"', $page );
		$this->assertSame( 11, substr_count( $page, 'type="checkbox"' ) );
		$this->assertSame( 2, substr_count( $page, '<textarea ' ) );
		$this->assertStringContainsString( 'value="Save Changes"', $page );
		$this->assertMatchesRegularExpression(
			'#<a href="https://github\.com/EngineScript/EngineScript" target="_blank" rel="noopener noreferrer">\s*Visit the EngineScript GitHub page\s*<span class="screen-reader-text">\s*\(opens in a new tab\)\s*</span>\s*</a>#',
			$page,
			'The link says, inside the link, that it opens a new tab (CR-UI-002).'
		);
	}

	/**
	 * A hostile translation of any page string prints as text.
	 *
	 * @return void
	 */
	public function test_page_escapes_every_translated_string(): void {
		$payload                                      = '<img src=x onerror=alert(1)>';
		$GLOBALS['es_optimizer_test']['translations'] = array_fill_keys(
			array( 'Site Optimizer Settings', 'Select which optimizations you want to enable and customize the resource hint domains.', 'Save Changes', 'This plugin is part of the EngineScript project.', 'Visit the EngineScript GitHub page', '(opens in a new tab)', 'Remove WordPress emoji scripts and styles.', 'Optional suggestions: copy only the domains your site uses into the field above, one per line. This list starts empty; suggestions are not enabled automatically. Enable the setting and save your list to add hints.' ),
			$payload
		);
		es_optimizer_init_settings();
		$page = $this->capture( 'es_optimizer_settings_page' );

		$this->assertStringNotContainsString( '<img', $page );
		$this->assertGreaterThanOrEqual( 7, substr_count( $page, '&lt;img src=x onerror=alert(1)&gt;' ) );
	}

	/**
	 * A checkbox reflects the stored flag, read the way WordPress reads booleans.
	 *
	 * @return void
	 */
	public function test_checkbox_reflects_the_stored_flag(): void {
		es_optimizer_init_settings();
		$args = array( 'label_for' => 'es_optimizer_field_remove_rsd_link' );

		$off = $this->capture( static fn () => es_optimizer_render_checkbox_field( $args ) );
		$this->assertStringContainsString( 'id="es_optimizer_field_remove_rsd_link" type="checkbox" name="es_optimizer_options[remove_rsd_link]" value="1"', $off );
		$this->assertStringNotContainsString( 'checked', $off );
		$this->assertStringContainsString( 'Remove the Really Simple Discovery (RSD) link from the document head.', $off );

		$this->store_options( array( 'remove_rsd_link' => 1 ) );
		$this->assertStringContainsString( "checked='checked'", $this->capture( static fn () => es_optimizer_render_checkbox_field( $args ) ) );

		$this->store_options( array( 'remove_rsd_link' => 'false' ) );
		$this->assertStringNotContainsString( 'checked', $this->capture( static fn () => es_optimizer_render_checkbox_field( $args ) ), 'A stored "false" is off (CR-SEM-003).' );
	}

	/**
	 * A field whose arguments name no option of the plugin prints nothing.
	 *
	 * @return void
	 */
	public function test_fields_ignore_unknown_arguments(): void {
		foreach ( array( array(), array( 'label_for' => 'other_field' ), array( 'label_for' => 'es_optimizer_field_unknown' ) ) as $args ) {
			$this->assertSame( '', $this->capture( static fn () => es_optimizer_render_checkbox_field( $args ) ) );
			$this->assertSame( '', $this->capture( static fn () => es_optimizer_render_textarea_field( $args ) ) );
		}
		$this->assertSame( 'remove_rsd_link', es_optimizer_get_option_name_from_field_args( array( 'label_for' => 'es_optimizer_field_remove_rsd_link' ) ) );
		$this->assertSame( '', es_optimizer_get_option_name_from_field_args( array( 'label_for' => 'field_remove_rsd_link' ) ) );
	}

	/**
	 * A stored list is printed as text in the text area, and the description is tied to it.
	 *
	 * @return void
	 */
	public function test_textarea_escapes_the_stored_list(): void {
		es_optimizer_init_settings();
		$this->store_options( array( 'preconnect_domains' => "https://ok.example.org\n</textarea><script>alert(1)</script>" ) );
		$output = $this->capture( static fn () => es_optimizer_render_textarea_field( array( 'label_for' => 'es_optimizer_field_preconnect_domains' ) ) );

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( "https://ok.example.org\n&lt;/textarea&gt;&lt;script&gt;alert(1)&lt;/script&gt;</textarea>", $output );
		$this->assertStringContainsString( 'aria-describedby="es_optimizer_field_preconnect_domains_description"', $output );
		$this->assertStringContainsString( '<div id="es_optimizer_field_preconnect_domains_description" class="description">', $output );
	}

	/**
	 * Suggestions are printed below the two lists only, and are never stored.
	 *
	 * @return void
	 */
	public function test_suggestions_are_help_text_only(): void {
		es_optimizer_init_settings();
		$preconnect = $this->capture( static fn () => es_optimizer_render_textarea_field( array( 'label_for' => 'es_optimizer_field_preconnect_domains' ) ) );
		$dns        = $this->capture( static fn () => es_optimizer_render_textarea_field( array( 'label_for' => 'es_optimizer_field_dns_prefetch_domains' ) ) );

		$this->assertStringContainsString( "<pre>https://fonts.googleapis.com\nhttps://fonts.gstatic.com\nhttps://s.w.org\nhttps://wordpress.com\nhttps://cdnjs.cloudflare.com\nhttps://www.googletagmanager.com</pre>", $preconnect );
		$this->assertStringContainsString( '<pre>https://adservice.google.com</pre>', $dns );
		$this->assertStringContainsString( '></textarea>', $preconnect, 'The list itself starts empty.' );
		$this->assertSame( array(), es_optimizer_get_domain_suggestions( 'disable_emojis' ) );
		$this->assertSame( '', es_optimizer_get_options()['preconnect_domains'] );
	}

	/**
	 * A saved list over a limit, or a preconnect entry with a port, is explained under the field.
	 *
	 * @return void
	 */
	public function test_descriptions_explain_saved_lists_that_add_no_hints(): void {
		es_optimizer_init_settings();
		$preconnect = array( 'label_for' => 'es_optimizer_field_preconnect_domains' );
		$dns        = array( 'label_for' => 'es_optimizer_field_dns_prefetch_domains' );

		$this->store_options( array( 'preconnect_domains' => implode( "\n", array_fill( 0, 101, 'https://a.example.org' ) ) ) );
		$this->assertStringContainsString( 'This saved list exceeds a size limit.', $this->capture( static fn () => es_optimizer_render_textarea_field( $preconnect ) ) );

		$this->store_options( array( 'preconnect_domains' => 'https://a.example.org:8443', 'dns_prefetch_domains' => 'https://a.example.org:8443' ) );
		$this->assertStringContainsString( 'Some saved preconnect entries use unsupported ports.', $this->capture( static fn () => es_optimizer_render_textarea_field( $preconnect ) ) );
		$this->assertStringNotContainsString( 'unsupported ports', $this->capture( static fn () => es_optimizer_render_textarea_field( $dns ) ), 'A port is fine for DNS prefetch.' );

		$this->store_options( array( 'preconnect_domains' => 'https://a.example.org' ) );
		$output = $this->capture( static fn () => es_optimizer_render_textarea_field( $preconnect ) );
		$this->assertStringNotContainsString( 'size limit', $output );
		$this->assertStringNotContainsString( 'unsupported ports', $output );
		$this->assertStringContainsString( 'Limits: 32768 bytes per list, 100 nonblank lines, and 512 bytes per nonblank line.', $output );
	}

	/**
	 * A field description that is not text reads as empty.
	 *
	 * @return void
	 */
	public function test_field_description_lookup(): void {
		$this->assertSame( '', es_optimizer_get_field_description( 'disable_emojis' ) );
		$GLOBALS['es_optimizer_field_descriptions'] = array( 'disable_emojis' => array( 'not text' ), 'remove_rsd_link' => 7 );
		$this->assertSame( '', es_optimizer_get_field_description( 'disable_emojis' ) );
		$this->assertSame( '7', es_optimizer_get_field_description( 'remove_rsd_link' ) );
		$GLOBALS['es_optimizer_field_descriptions'] = 'not an array';
		$this->assertSame( '', es_optimizer_get_field_description( 'remove_rsd_link' ) );
	}

	/**
	 * The Plugins screen gets a Settings link first; anything other than a list passes through (CR-SEM-002).
	 *
	 * @return void
	 */
	public function test_settings_link(): void {
		$links = es_optimizer_add_settings_link( array( 'deactivate' => '<a>Deactivate</a>' ) );

		$this->assertSame( array( 0 => '<a href="https://site.example/wp-admin/options-general.php?page=es-optimizer-settings">Settings</a>', 'deactivate' => '<a>Deactivate</a>' ), $links );
		foreach ( array( null, false, 'text', 7 ) as $foreign ) {
			$this->assertSame( $foreign, es_optimizer_add_settings_link( $foreign ) );
		}
		$GLOBALS['es_optimizer_test']['translations'] = array( 'Settings' => '<b>Settings</b>' );
		$this->assertStringContainsString( '>&lt;b&gt;Settings&lt;/b&gt;</a>', es_optimizer_add_settings_link( array() )[0] );
	}

	/**
	 * The option names of the recorded fields, ordered as the defaults are.
	 *
	 * @param array<int, array<string, mixed>> $fields Recorded fields.
	 * @return array<int, string> Option names.
	 */
	private function field_options_in_default_order( array $fields ): array {
		$ids   = array_column( $fields, 'id' );
		$order = array_flip( array_keys( es_optimizer_get_default_options() ) );
		usort( $ids, static fn ( string $a, string $b ): int => ( $order[ $a ] ?? PHP_INT_MAX ) <=> ( $order[ $b ] ?? PHP_INT_MAX ) );
		return $ids;
	}
}
