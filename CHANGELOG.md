# Changelog

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Renamed 11 globally-scoped functions to use the `es_optimizer_` prefix to prevent naming collisions with other plugins (`disable_emojis`, `remove_jquery_migrate`, `disable_classic_theme_styles`, `remove_header_items`, `remove_recent_comments_style`, `add_preconnect`, `add_dns_prefetch`, `disable_jetpack_ads`, `disable_post_via_email`, `disable_emojis_tinymce`, `disable_emojis_remove_dns_prefetch`)
- `es_optimizer_get_options()` now accepts an optional `$force_refresh` parameter so the static cache can be properly invalidated
- `es_optimizer_add_settings_page()` page title and menu title are now wrapped in `__()` for correct translation
- Settings page heading, description, and submit button value are now translatable
- All functions that previously called `get_option( 'es_optimizer_options' )` directly now use the `es_optimizer_get_options()` caching wrapper for consistency
- Render function callers now pass `__()` instead of `esc_html__()` to avoid double-escaping; the render functions themselves handle escaping at output
- Removed redundant custom `wp_nonce_field()` and its bypassable verification from the settings form; CSRF protection is already handled by WordPress Settings API via `settings_fields()`

### Fixed

- **Security**: Moved `esc_textarea()` escaping to the point of output for textarea values, preventing a potential XSS vector
- **Code Quality**: Refactored textarea rendering to place PHP open/close tags on their own lines, resolving Codacy best-practice warnings
- **Critical**: Fixed whitespace embedded inside form field `name` attributes (checkbox and textarea) that prevented settings from ever being saved — `$_POST['es_optimizer_options']` was never set because browsers sent the literal newlines/tabs as part of the field name
- **Critical**: Fixed inverted IP-validation logic in `es_optimizer_validate_single_domain()` that caused every domain name (e.g. `fonts.googleapis.com`) to be incorrectly rejected when saving preconnect/DNS-prefetch settings
- **Critical**: Fixed `es_optimizer_clear_options_cache()` which created an independent closure-scoped static variable and therefore never cleared the cache inside `es_optimizer_get_options()`
- Fixed textarea content containing leading whitespace (newlines/tabs between `<textarea>` tag and PHP output)
- Fixed missing `esc_url()` and `esc_html__()` escaping on the Settings link rendered in the Plugins list
- Removed unreachable dead code (`if ( ! is_admin() ) { return; }`) inside `es_optimizer_add_settings_page()`, which is only ever called from the `admin_menu` hook



## [1.8.0] - 2025-10-23

### Added
- Added new option to remove RSD (Really Simple Discovery) link from WordPress header
- **NEW FEATURE**: Added separate DNS Prefetch option alongside Preconnect for better resource hint control
  - DNS-prefetch performs lighter-weight DNS-only lookups for less critical domains
  - Preconnect performs full connection setup (DNS + TCP + TLS) for critical domains
  - Clear UI explanations help users understand when to use each option
- Added preconnect domains: `https://s.w.org`, `https://wordpress.com`, `https://cdnjs.cloudflare.com`, and `https://www.googletagmanager.com`
- Added DNS prefetch domain: `https://adservice.google.com`

### Changed
- **PERFORMANCE UPGRADE**: Changed from DNS-prefetch to Preconnect for better performance
  - Preconnect establishes full connection (DNS + TCP + TLS) vs dns-prefetch which only does DNS lookup
  - Adds `crossorigin` attribute for font domains (required for CORS requests)
  - Improves Largest Contentful Paint (LCP) and First Contentful Paint (FCP) metrics
- Updated default preconnect domains to remove deprecated Google CDN URLs (`ajax.googleapis.com` and `apis.google.com`)
- All optimization options are now disabled by default for better user control
- Improved preconnect textarea display to eliminate extra whitespace on first line

### Fixed
- Fixed WordPress coding standards compliance: PHP opening and closing tags now on separate lines
- Fixed indentation in textarea rendering function (3 tabs instead of 4)

## [1.7.0] - 2025-01-27

### Changed
- **Major Plugin Architecture Refactor**: Completely restructured plugin initialization to use WordPress `plugins_loaded` hook
- Improved plugin load order by removing immediate global scope execution
- Consolidated plugin initialization into proper WordPress lifecycle management
- Moved all `add_action` and `add_filter` calls into structured initialization functions
- Enhanced plugin activation, deactivation, and uninstall lifecycle management

### Removed
- Removed unused `es_optimizer_enqueue_admin_scripts()` function (dead code removal)
- Cleaned up scattered global scope function calls

### Fixed
- Fixed inline comment punctuation to comply with WordPress coding standards
- Improved code organization and readability
- Enhanced plugin stability and compatibility with other WordPress plugins

## [1.6.0] - 2025-08-10
### Added
- **Performance Optimization**: Implemented conditional admin asset loading - admin scripts and styles now only load on the plugin settings page
- **Option Caching System**: Added `es_optimizer_get_options()` function with static caching to reduce database queries throughout the plugin
- **DNS Prefetch Security Enhancement**: Enhanced DNS prefetch validation to reject file paths, query parameters, and fragments - only clean domains are now accepted
- **Documentation Enhancement**: Added @since version tags to all PHPDoc blocks for better change tracking
- **Developer Guidelines**: Created comprehensive CONTRIBUTING.md file with development standards, security requirements, and contribution workflow

### Enhanced
- **DNS Prefetch Optimization**: Improved DNS prefetch function with static caching, duplicate removal, AJAX detection, and enhanced domain validation
- **Security Validation**: Strengthened DNS prefetch domain validation to prevent file path injection (e.g., `https://google.com/file.php` is now rejected)
- **User Experience**: Updated DNS prefetch textarea description to clearly explain that only clean domains are allowed
- **Code Quality**: Enhanced function documentation and maintained WordPress coding standards compliance
- **Testing Infrastructure**: Complete test suite with fixtures, helpers, and comprehensive coverage reporting

### Security
- **Domain Validation**: Enhanced DNS prefetch security to block file paths, query parameters, and URL fragments
- **Input Sanitization**: Maintained comprehensive input validation and output escaping throughout all changes
- **Clean Domain Output**: DNS prefetch now automatically strips paths and returns only clean domain URLs for security
- **Security Testing**: Comprehensive security validation tests including XSS prevention and malicious input handling

### Performance
- **Reduced Database Queries**: Option caching system minimizes repeated database calls
- **Conditional Loading**: Admin assets only load when needed, reducing unnecessary resource usage
- **Optimized DNS Prefetch**: Enhanced processing efficiency with static caching and validation improvements

### Developer Experience
- **Testing Framework**: PHPUnit with WP_Mock for WordPress-specific testing
- **Code Coverage**: 80% minimum coverage requirement with detailed reporting
- **Quality Assurance**: PHPCS, PHPMD, and PHPStan integration for code quality
- **Development Workflow**: Automated testing with GitHub Actions CI/CD pipeline

## [1.5.12] - 2025-08-02
### Added
- **Disable Post via Email**: New option to disable the post-via-email feature for enhanced security and performance.

### Security
- **Hardened Disabling Filters**: Updated all feature-disabling filters to use `PHP_INT_MAX` priority, ensuring they cannot be overridden by other plugins or themes.

## [1.5.11] - 2025-07-11
### Code Quality
- **WordPress Coding Standards**: Converted all code to use spaces instead of tabs for indentation
- **Code Style**: Fixed file comment header to use "/**" style instead of "/*" style
- **Code Style**: Added proper spacing around operators (e.g., `! defined` instead of `!defined`)
- **Code Style**: Added proper full stops to inline comments for consistency
- **Code Style**: Removed trailing whitespace from documentation blocks
- **Variable Naming**: Converted variable names to use snake_case instead of camelCase for WordPress compliance
- **Function Formatting**: Improved function parameter spacing and alignment
- **Array Formatting**: Enhanced array formatting with proper alignment and trailing commas

## [1.5.10] - 2025-07-07
### Maintenance
- Updated changelog and version references across documentation files for new release
### Documentation
- Synced CHANGELOG.md and readme.txt as per project standards
### Note
- No code changes in this release; documentation and changelog only

## [1.5.9] - 2025-06-26
### Updated
- **Requirements**: Updated minimum WordPress version requirement to 6.5+ across all files for modern WordPress compatibility
- **Internationalization**: Created languages/simple-wp-optimizer.pot file for translation support
- **Documentation**: Updated all version references to reflect new WordPress 6.5+ minimum requirement
- **Workflow**: Updated GitHub Actions workflow compatibility testing from WordPress 6.0 to 6.5
- **Compliance**: Enhanced project structure compliance with copilot coding standards and documentation guidelines

## [1.5.8] - 2025-06-15
### Added
- Enhanced code organization with single responsibility principle implementation
- Dedicated DNS domain validation functions for improved maintainability
- Proper translator comments for all internationalization strings with placeholders
- Additional sanitization layer for nonce verification using sanitize_text_field()

### Fixed
- **WordPress Plugin Check Compliance**: Resolved all WordPress.org compatibility issues
- **Variable Naming**: Implemented camelCase convention for all variables (nonceValue, domainsInput, etc.)
- **Function Complexity**: Reduced cyclomatic complexity by extracting validation logic into separate functions
- **Code Standards**: Fixed $_POST data handling with proper WordPress sanitization practices
- **i18n Compliance**: Added missing translator comments for sprintf() placeholders
- **Documentation**: Updated upgrade notice character limits to meet WordPress.org requirements

### Enhanced
- Improved code architecture with es_optimizer_validate_single_domain() function
- Better error handling with es_optimizer_show_domain_rejection_notice() function
- Enhanced maintainability through function separation and reduced complexity
- Cleaner code structure following WordPress coding standards
- Optimized function organization for better testing and debugging

### Security
- **Enhanced Nonce Handling**: Additional sanitization layer for CSRF protection
- **Improved Input Validation**: Strengthened domain validation with dedicated functions
- **WordPress Standards**: Full compliance with WordPress security best practices

## [1.5.7] - 2025-06-15
### Added
- WordPress nonce protection for CSRF security in settings forms
- Enhanced DNS prefetch security with HTTPS-only domain enforcement
- Private IP and localhost blocking for DNS prefetch domains to prevent SSRF attacks
- Comprehensive domain validation with multi-layer security checks
- User-friendly error messages for rejected domains with proper HTML escaping
- Security event notifications for administrators when domains are rejected

### Enhanced
- Strengthened form security with wp_nonce_field() and wp_verify_nonce() implementation
- Improved DNS prefetch domain validation with parse_url() and enhanced filtering
- Enhanced error handling with proper WordPress admin notices and escaping
- Updated help text to clearly indicate HTTPS requirement for DNS prefetch domains
- Better user experience with informative security-related error messages
- Comprehensive input validation preventing malicious domain submissions

### Security
- **CSRF Protection**: Added WordPress nonce verification for all form submissions
- **HTTPS Enforcement**: DNS prefetch domains now require HTTPS protocol for security
- **SSRF Prevention**: Blocked private IP ranges and localhost addresses in DNS prefetch
- **Input Validation**: Enhanced multi-layer validation for all user-submitted domains  
- **Output Escaping**: Improved HTML escaping for all error messages and user feedback
- **Attack Surface Reduction**: Eliminated potential vectors for security exploitation

### Fixed
- Resolved potential XSS vulnerability in error message display by adding proper HTML escaping
- Fixed domain validation to prevent bypass of security checks
- Improved error message construction to prevent information disclosure

## [1.5.6] - 2025-05-31
### Added
- Enhanced GitHub Actions workflows for comprehensive plugin testing and security analysis
- PHPStan WordPress static analysis with proper WordPress stubs configuration
- WordPress Vulnerability Scanner integration for security testing
- Comprehensive WordPress security scanning using pattern analysis
- Composer support with WordPress stubs for better development experience
- PHPStan WordPress extension (szepeviktor/phpstan-wordpress) for enhanced analysis
- Comprehensive security implementation documentation following OWASP guidelines
- Detailed security implementation notes in plugin header
- Enhanced input validation with strict type checking for all user inputs
- Improved output escaping with context-appropriate WordPress functions (esc_html, esc_attr, esc_url, esc_textarea)
- Proper capability checks for all admin functions using current_user_can('manage_options')
- Secure coding patterns throughout the plugin codebase
- Multi-layer domain validation for DNS prefetch functionality
- Comprehensive code documentation with security explanations

### Fixed
- Fixed register_setting() function to use proper array parameters instead of string callback
- Corrected remove_action() function calls to use proper parameter count (removed invalid 4th parameter)
- Replaced non-existent wp_print_link_tag() function with proper HTML output using esc_url()
- Fixed "unreachable statement" in disable_emojis_tinymce() function by restructuring logic
- Resolved all PHPStan static analysis errors at level 5
- Fixed WordPress Plugin Check compatibility issues
- Fixed potential security vulnerabilities with proper WordPress best practices

### Enhanced
- Updated plugin to pass PHPStan level 5 analysis with zero errors
- Improved workflow reliability by removing problematic external dependencies
- Enhanced security scanning with WordPress-specific vulnerability patterns
- Better WordPress API compliance and coding standards
- Improved code quality and maintainability
- Security headers and implementation comments for better code understanding
- DNS prefetch domain validation with enhanced security measures
- Settings validation and sanitization functions
- Code structure and organization for better maintainability
- Direct script access prevention with proper WordPress checks

### Security
- Enhanced all user input validation and output escaping
- Added security-focused code comments explaining safety measures
- Implemented OWASP-compliant security patterns throughout the codebase

## [1.5.5] - 2025-05-21
### Added
- WordPress 6.8 compatibility
- WordPress Plugin Check workflow for code quality verification
- Automated GitHub issue creation for test failures

### Fixed
- Changed text domain from 'simple-wp-optimizer-enginescript' to 'simple-wp-optimizer' to comply with WordPress.org standards
- Updated all internationalization function calls with the correct text domain
- Added missing text domain parameter in translation functions
- Fixed issue template formatting for automated GitHub issue creation

### Improved
- Made the plugin fully compatible with the WordPress Plugin Check tool
- Enhanced plugin repo compatibility
- Improved documentation and code comments

## [1.5.4] - 2025-05-04
### Changed
- Updated plugin name to "EngineScript: Simple WP Optimization"
- Improved code documentation and security notes
- Aligned version numbers in plugin header and constant definition

## [1.5.3] - 2025-03-15
### Added
- Enhanced security implementation with detailed documentation
- Added PHPCS ignore comments with security explanations
- Improved validation for DNS prefetch domains

### Fixed
- Fixed potential security issues with escaped outputs
- Fixed DNS prefetch implementation for better performance

## [1.5.2] - 2025-01-20
### Added
- Added Jetpack Blaze disabling feature

### Changed
- Improved function documentation with security notes
- Enhanced settings page with better organization

## [1.5.1] - 2024-11-05
### Changed
- Updated WordPress compatibility to 6.5
- Improved code organization
- Enhanced settings validation

## [1.5.0] - 2024-09-10
### Added
- Added option to disable Jetpack advertisements and promotions
- Implemented settings link in plugins page

### Changed
- Refactored settings page rendering for better security
- Updated text domain for better translation support

## [1.4.1] - 2024-07-25
### Added
- Enhanced DNS prefetching with better security measures
- Added proper input validation for domain entries

### Fixed
- Fixed escaping in DNS prefetch output
- Improved error handling for invalid domains

## [1.4.0] - 2024-05-30
### Added
- Added DNS prefetching for common external domains
- Added textarea input for custom DNS prefetch domains
- Implemented domain validation and sanitization

### Changed
- Improved settings organization with grouped options
- Enhanced option descriptions

## [1.3.0] - 2024-03-15
### Added
- Option to disable classic theme styles (added in WordPress 6.1+)
- Improved header cleanup options

### Changed
- Enhanced security for settings page
- Better user capability checks

## [1.2.0] - 2023-12-10
### Added
- Option to remove recent comments widget inline CSS
- Option to remove shortlinks from WordPress header

### Changed
- Improved settings validation
- Better code organization

## [1.1.0] - 2023-09-05
### Added
- Option to remove WLW manifest
- Option to remove WordPress version from header
- Comprehensive settings page with checkboxes

### Changed
- Switched to WordPress Settings API
- Better organization of options

## [1.0.0] - 2023-06-01
### Added
- Initial release
- Option to disable WordPress emojis
- Option to remove jQuery Migrate
- Basic settings page
- Default options