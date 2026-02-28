=== Simple WP Optimizer ===
Contributors: enginescript
Tags: optimization, performance, cleanup
Requires at least: 6.5
Tested up to: 6.9
Stable tag: 1.8.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimizes WordPress by removing unnecessary features and scripts to improve performance.

== Description ==

Simple WP Optimizer removes unnecessary WordPress features and scripts to optimize your site's performance. 
It helps reduce page load times and improves overall site speed by disabling unused functionality.

Key features:
* Disable XML-RPC
* Disable JSON REST API for non-logged users
* Remove jQuery Migrate
* Remove unnecessary header meta
* Disable auto-embeds
* Disable emoji support
* Remove Gutenberg CSS

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/simple-wp-optimizer` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings page to configure the optimization options.

== Frequently Asked Questions ==

= Will this plugin break my site? =

The optimizations are carefully selected to be safe for most sites. You can enable/disable specific optimizations as needed.

= Do I need technical knowledge to use this plugin? =

No, the plugin has a simple interface where you can toggle features on and off.


== Changelog ==

= Unreleased =
* **BUG FIX (Critical)**: Fixed whitespace inside form field `name` attributes that prevented all settings from being saved — browsers sent literal newlines/tabs as part of the field name so WordPress never received the data
* **BUG FIX (Critical)**: Fixed inverted IP-validation logic that caused every preconnect/DNS-prefetch domain name to be incorrectly rejected when saving settings
* **BUG FIX (Critical)**: Fixed `es_optimizer_clear_options_cache()` which was not actually clearing the static options cache
* **BUG FIX**: Fixed textarea content containing leading whitespace between the HTML tag and the PHP value output
* **SECURITY**: Added missing `esc_url()` and `esc_html__()` escaping to the Settings link in the Plugins list
* **SECURITY**: Removed redundant custom nonce field and its bypassable verification; CSRF protection is handled by WordPress Settings API
* **CODE QUALITY**: Fixed double-escaping — render callers now pass `__()` instead of `esc_html__()`, with escaping done at output in the render functions
* **CODE QUALITY**: Renamed 11 globally-scoped functions to use the `es_optimizer_` prefix, preventing potential naming collisions with other plugins
* **CODE QUALITY**: Unified all option retrieval calls to use the `es_optimizer_get_options()` caching wrapper
* **CODE QUALITY**: Settings page heading, description, and submit button are now fully translatable
* **CODE QUALITY**: Removed unreachable dead code in `es_optimizer_add_settings_page()`
* **CODE QUALITY**: Refactored textarea rendering to place PHP open/close tags on their own lines, resolving Codacy best-practice warnings

= 1.8.0 - 2025-10-23 =
* **FEATURE**: Added new option to remove RSD (Really Simple Discovery) link from WordPress header
* **FEATURE**: Added separate DNS Prefetch option for lighter-weight domain lookups
* **PERFORMANCE**: Upgraded from DNS-prefetch to Preconnect for critical resources
* **PERFORMANCE**: Preconnect now establishes full connections (DNS + TCP + TLS) instead of just DNS lookup
* **PERFORMANCE**: Added crossorigin attribute for font domains to improve CORS request handling
* **ENHANCEMENT**: Added helpful descriptions explaining when to use Preconnect vs DNS-prefetch
* **ENHANCEMENT**: Added preconnect for Google Tag Manager and additional critical domains
* **ENHANCEMENT**: Added DNS prefetch for Google Ad Service as lighter-weight fallback
* **OPTIMIZATION**: Updated default preconnect domains by removing deprecated Google CDN URLs
* **USER EXPERIENCE**: All optimization options are now disabled by default for better user control
* **CODE QUALITY**: Fixed WordPress coding standards compliance for PHP tag formatting and indentation

= 1.7.0 =
* **ARCHITECTURE**: Major plugin architecture refactor - completely restructured initialization to use WordPress `plugins_loaded` hook
* **ARCHITECTURE**: Improved plugin load order by removing immediate global scope execution
* **ARCHITECTURE**: Consolidated plugin initialization into proper WordPress lifecycle management
* **ARCHITECTURE**: Enhanced plugin activation, deactivation, and uninstall lifecycle management
* **CODE QUALITY**: Removed unused `es_optimizer_enqueue_admin_scripts()` function (dead code removal)
* **CODE QUALITY**: Fixed inline comment punctuation to comply with WordPress coding standards
* **STABILITY**: Enhanced plugin stability and compatibility with other WordPress plugins

= 1.6.0 =
* **PERFORMANCE**: Implemented conditional admin asset loading - admin scripts and styles now only load on plugin settings page
* **PERFORMANCE**: Added option caching system with `es_optimizer_get_options()` function to reduce database queries
* **PERFORMANCE**: Enhanced DNS prefetch function with static caching, duplicate removal, and AJAX detection
* **SECURITY**: Enhanced DNS prefetch validation to reject file paths, query parameters, and fragments - only clean domains accepted
* **SECURITY**: Strengthened domain validation to prevent file path injection (e.g., `https://google.com/file.php` now rejected)
* **DOCUMENTATION**: Added @since version tags to all PHPDoc blocks for better change tracking
* **DEVELOPER EXPERIENCE**: Created comprehensive CONTRIBUTING.md file with development standards and security requirements
* **USER EXPERIENCE**: Updated DNS prefetch textarea description to clearly explain clean domain requirements
* **CODE QUALITY**: Enhanced function documentation while maintaining WordPress coding standards compliance

= 1.5.12 =
* ADDED: New option to disable the post-via-email feature for enhanced security and performance.
* SECURITY: Hardened all feature-disabling filters to use `PHP_INT_MAX` priority, ensuring they cannot be overridden by other plugins or themes.

= 1.5.11 =
* CODE QUALITY: Converted all code to use spaces instead of tabs for indentation
* CODE STYLE: Fixed file comment header to use "/**" style instead of "/*" style
* CODE STYLE: Added proper spacing around operators (e.g., `! defined` instead of `!defined`)
* CODE STYLE: Added proper full stops to inline comments for consistency
* CODE STYLE: Removed trailing whitespace from documentation blocks
* VARIABLE NAMING: Converted variable names to use snake_case instead of camelCase for WordPress compliance
* FUNCTION FORMATTING: Improved function parameter spacing and alignment
* ARRAY FORMATTING: Enhanced array formatting with proper alignment and trailing commas

= 1.5.10 =
* MAINTENANCE: Updated changelog and version references across documentation files for new release
* DOCUMENTATION: Synced CHANGELOG.md and readme.txt as per project standards
* NO CODE CHANGES: This release is documentation and changelog only

= 1.5.9 =
* REQUIREMENTS: Updated minimum WordPress version requirement to 6.5+ across all files for modern WordPress compatibility
* INTERNATIONALIZATION: Created languages/simple-wp-optimizer.pot file for translation support
* DOCUMENTATION: Updated all version references to reflect new WordPress 6.5+ minimum requirement
* WORKFLOW: Updated GitHub Actions workflow compatibility testing from WordPress 6.0 to 6.5
* COMPLIANCE: Enhanced project structure compliance with copilot coding standards and documentation guidelines

= 1.5.8 =
* **CODE QUALITY**: Fixed all WordPress Plugin Check compliance issues for WordPress.org standards
* **CODE QUALITY**: Implemented camelCase variable naming convention throughout codebase
* **CODE QUALITY**: Reduced function complexity by extracting DNS validation logic into separate functions
* **CODE QUALITY**: Added proper translator comments for all internationalization strings with placeholders
* **CODE QUALITY**: Enhanced $_POST data handling with proper sanitization using sanitize_text_field()
* **CODE QUALITY**: Improved code organization with single responsibility principle
* **CODE QUALITY**: Fixed upgrade notice character limits to meet WordPress.org requirements
* **SECURITY**: Enhanced nonce verification with additional sanitization layer
* **SECURITY**: Improved domain validation architecture with dedicated validation functions
* **MAINTENANCE**: Optimized function structure for better maintainability and testing
* **MAINTENANCE**: Updated code documentation for improved developer experience
* **COMPLIANCE**: Full WordPress Plugin Check compatibility - passes all automated tests

= 1.5.7 =
* **SECURITY ENHANCEMENT**: Added WordPress nonce protection for CSRF security in all form submissions
* **SECURITY ENHANCEMENT**: Enhanced DNS prefetch security with HTTPS-only domain enforcement
* **SECURITY ENHANCEMENT**: Added private IP and localhost blocking for DNS prefetch to prevent SSRF attacks
* **SECURITY ENHANCEMENT**: Implemented comprehensive domain validation with multi-layer security checks
* Added user-friendly error messages for rejected domains with proper HTML escaping
* Added security event notifications for administrators when domains are rejected for security reasons
* Strengthened form security with proper wp_nonce_field() and wp_verify_nonce() implementation
* Improved DNS prefetch domain validation with enhanced URL parsing and filtering
* Enhanced error handling with proper WordPress admin notices and comprehensive escaping
* Updated help text to clearly indicate HTTPS requirement for DNS prefetch domains
* Fixed potential XSS vulnerability in error message display through proper HTML escaping
* Improved domain validation to prevent bypass of security checks and information disclosure
* Better user experience with informative, security-focused error messages
* Comprehensive input validation preventing malicious domain submissions and attacks

= 1.5.6 =
* Enhanced GitHub Actions workflows for comprehensive plugin testing and security analysis
* Added PHPStan WordPress static analysis with proper WordPress stubs configuration
* Integrated WordPress Vulnerability Scanner and comprehensive security scanning
* Fixed PHPStan static analysis errors: register_setting(), remove_action(), wp_print_link_tag() replacement, disable_emojis_tinymce() logic
* Enhanced security implementation with comprehensive OWASP-compliant documentation
* Added detailed security implementation notes following WordPress best practices
* Improved input validation with strict type checking for all user inputs
* Enhanced output escaping with context-appropriate WordPress functions (esc_html, esc_attr, esc_url, esc_textarea)
* Added proper capability checks for all admin functions using current_user_can('manage_options')
* Implemented secure coding patterns and multi-layer domain validation for DNS prefetch
* Added comprehensive code documentation with security explanations and best practices
* Fixed potential security vulnerabilities with proper WordPress coding standards
* Improved code structure and organization for better maintainability
* Enhanced development workflow with reliable testing and WordPress stubs support

= 1.5.5 =
* Enhanced code quality with comprehensive static analysis fixes
* Fixed register_setting function to use proper array parameters instead of string callback
* Corrected remove_action function calls to use proper parameter count (2-3 parameters)
* Replaced non-existent wp_print_link_tag function with proper HTML output using esc_url()
* Improved disable_emojis_tinymce function logic to eliminate unreachable code
* Added comprehensive PHPStan WordPress static analysis with proper WordPress stubs
* Enhanced security scanning with WordPress-specific vulnerability patterns
* Improved workflow reliability by removing problematic external dependencies
* Added Composer support with WordPress stubs for better development experience
* Updated code to pass PHPStan level 5 analysis with zero errors
* Added compatibility with WordPress 6.8
* Fixed text domain to comply with WordPress.org standards (changed from 'simple-wp-optimizer-enginescript' to 'Simple-WP-Optimizer')
* Updated all internationalization function calls with proper text domain
* Fixed missing text domain parameter in translation functions
* Resolved issues with WordPress plugin check requirements
* Fixed issue template formatting for automated GitHub issue creation
* Made the plugin fully compatible with the WordPress Plugin Check tool
* Improved documentation and code comments

== Upgrade Notice ==

= 1.5.9 =
REQUIREMENTS UPDATE: Updated minimum WordPress version to 6.5+. Added translation support and enhanced compliance.

= 1.5.8 =
CODE QUALITY UPDATE: Fixed all WordPress Plugin Check issues for full WordPress.org compliance. Enhanced code organization.

= 1.5.7 =
SECURITY UPDATE: Important security enhancements including CSRF protection and DNS prefetch security. Update recommended.

= 1.5.6 =
Major security enhancement update with OWASP-compliant security implementation. Recommended for all users.

= 1.5.5 =
WordPress 6.8 compatibility and internationalization fixes. Passes all Plugin Check tests.

= 1.5.4 =
This update includes security enhancements and code optimization.
