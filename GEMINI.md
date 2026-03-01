# Project-specific instructions for Gemini AI

## Project Overview

**EngineScript Site Optimizer - WordPress Plugin**
This is a WordPress performance optimization plugin that removes unnecessary features and scripts to improve site performance. Designed for WordPress administrators who want to optimize their site's speed by disabling unused functionality and reducing resource overhead.

## Plugin Details

- **Name:** EngineScript Site Optimizer
- **Version:** 2.0.0
- **WordPress Compatibility:** 6.5+
- **PHP Compatibility:** 7.4+
- **License:** GPL-3.0-or-later
- **Text Domain:** enginescript-site-optimizer

## Architecture & Design Patterns

### Single-File Plugin Architecture

The plugin follows a single-file architecture pattern for simplicity:

```php
// All functionality contained in enginescript-site-optimizer.php
// Functions prefixed with 'es_optimizer_' for namespace consistency
function es_optimizer_function_name() {
    // Implementation
}
```

### Plugin Initialization

The plugin uses proper WordPress initialization patterns with plugins_loaded hook:

```php
function es_optimizer_init() {
    // Hook admin menu creation
    add_action( 'admin_menu', 'es_optimizer_admin_page' );
    // Hook optimization features
    add_action( 'init', 'es_optimizer_apply_optimizations' );
    // Other initialization code
}
add_action( 'plugins_loaded', 'es_optimizer_init' );
```

### File Structure

- `enginescript-site-optimizer.php` - Main plugin file (all functionality)
- `languages/` - Translation files (.pot file included)
- `CHANGELOG.md` - Developer changelog
- `README.md` - Developer documentation
- `readme.txt` - WordPress.org plugin directory readme
- `.github/workflows/` - CI/CD automation with AI-powered analysis

## WordPress Coding Standards

### Naming Conventions

- **Functions:** `es_optimizer_snake_case` (WordPress standard with plugin prefix)
- **Variables:** `$snake_case`
- **Constants:** `ES_SITE_OPTIMIZER_UPPER_SNAKE_CASE`
- **Text Domain:** Always use `'enginescript-site-optimizer'`

### Security Requirements

- Always use `esc_html()`, `esc_attr()`, `esc_url()` for output
- Sanitize input with `sanitize_text_field()`, `wp_unslash()`, etc.
- Use `current_user_can( 'manage_options' )` for capability checks
- Implement proper nonce verification for all forms and actions
- Validate and sanitize all user-provided URLs and domains
- Use WordPress Options API for settings storage

### WordPress Integration

- **Hooks:** Proper use of actions and filters with appropriate priorities
- **Performance Features:** Integration with WordPress caching and optimization APIs
- **Settings API:** WordPress Settings API for admin interface
- **Internationalization:** All strings use `esc_html__()` or `esc_html_e()`
- **Admin Interface:** Proper admin page integration with WordPress UI standards

## Plugin-Specific Context

### Core Functionality

#### Performance Optimization Features

- **XML-RPC Disabling:** Remove XML-RPC functionality for security and performance
- **JSON REST API Control:** Disable REST API for non-logged users
- **jQuery Migrate Removal:** Remove unnecessary jQuery Migrate script
- **Header Meta Cleanup:** Remove unnecessary WordPress meta tags from head
- **Auto-Embeds Disabling:** Disable WordPress auto-embed functionality
- **Emoji Support Removal:** Remove emoji scripts and styles
- **Gutenberg CSS Removal:** Remove unused Gutenberg block styles
- **DNS Prefetch Management:** User-configurable DNS prefetch for external domains

#### Settings Management

- **Options Caching:** Static caching system to reduce database queries
- **Conditional Admin Loading:** Admin assets only load on plugin settings page
- **User-Friendly Interface:** Toggle-based settings for easy optimization control
- **Input Validation:** Comprehensive validation for all user inputs

#### Security Features

- **Domain Validation:** DNS prefetch domains validated to prevent injection
- **Input Sanitization:** All user inputs properly sanitized and escaped
- **Capability Checks:** Admin-only access with proper permission verification
- **Nonce Protection:** CSRF protection on all form submissions

### Performance Optimization Focus

- **Frontend Performance:** Reduces HTTP requests and removes unused resources
- **Admin Performance:** Conditional loading of admin assets
- **Database Optimization:** Option caching to minimize database queries
- **Script Optimization:** Selective removal of unnecessary WordPress scripts

### DNS Prefetch Security

- **Domain Validation:** All DNS prefetch domains validated to prevent injection
- **Clean Domain Enforcement:** Only clean domains without paths/parameters allowed
- **Input Sanitization:** URL validation prevents malicious domain injection
- **Output Escaping:** All domain outputs properly escaped for security

### WordPress Hook Management

- **Priority Handling:** High-priority hooks (PHP_INT_MAX) to ensure optimization execution
- **Hook Timing:** Proper use of init, wp_head, and other WordPress lifecycle hooks
- **Filter Override Protection:** Prevents other plugins from disabling optimizations
- **Action Consolidation:** Organized hook management for better performance

### Option Caching System

- **Static Caching:** `es_optimizer_get_options()` function with static cache
- **Database Query Reduction:** Minimizes repeated option retrieval
- **Memory Efficiency:** Efficient caching without memory overhead
- **Cache Invalidation:** Proper cache clearing when options change

## Development Standards

### Error Handling

- **WP_Error Usage:** Consistent error object returns throughout
- **Comprehensive Logging:** Structured logging with severity levels
- **Security Logging:** Detailed logs for security events
- **User Feedback:** Clear error messages without information disclosure

### Documentation

- **PHPDoc Compliance:** Complete documentation for all functions
- **Security Comments:** Detailed security justifications
- **Code Examples:** Clear usage examples in documentation
- **Version Control:** Comprehensive changelog maintenance

### Testing & Quality Assurance

- **PHPStan Level 5:** Static analysis compliance
- **PHPCS WordPress Standards:** Full coding standards compliance
- **PHPMD Compliance:** Code quality and complexity management
- **Security Analysis:** Regular vulnerability assessments

## When Reviewing Code

### Critical Issues to Flag

1. **Performance Impact** (optimization conflicts, excessive resource usage)
2. **WordPress Compatibility** (plugin/theme conflicts, hook interference)
3. **Security Vulnerabilities** (input validation, output escaping)
4. **WordPress Standard Violations** (coding standards, API misuse)
5. **Option Management Issues** (database performance, caching problems)

### Plugin-Specific Security Concerns

1. **DNS Prefetch Validation:** Ensure domain inputs are properly validated
2. **Settings Security:** Verify admin-only access and nonce verification
3. **Hook Priority Conflicts:** Check for potential conflicts with other plugins
4. **Input Sanitization:** Validate all user-provided domains and settings
5. **Output Escaping:** Ensure all dynamic content is properly escaped

### Performance Focus Areas

1. **Frontend Optimization:** Script and style removal effectiveness
2. **Admin Performance:** Conditional asset loading efficiency
3. **Database Optimization:** Option caching and query reduction
4. **Hook Performance:** Efficient hook management and execution
5. **Memory Usage:** Optimization without excessive memory consumption

### Positive Patterns to Recognize

1. **WordPress API Compliance:** Proper use of WordPress hooks and functions
2. **Performance-First Design:** Optimizations that genuinely improve site speed
3. **User Experience:** Clear interface for managing optimizations
4. **Compatibility Focus:** Safe optimizations that don't break functionality
5. **Documentation Quality:** Clear documentation of optimization effects

### Suggestions to Provide

1. **WordPress-Specific Solutions:** Prefer WordPress APIs over generic PHP
2. **Performance Enhancements:** Additional optimization opportunities
3. **Compatibility Improvements:** Better plugin/theme compatibility
4. **User Experience:** Interface and workflow improvements
5. **Documentation Updates:** Clear explanation of optimization benefits

Remember: This plugin prioritizes WordPress performance optimization, security through input validation, and compatibility with the WordPress ecosystem. All optimizations must maintain site functionality while improving performance.
