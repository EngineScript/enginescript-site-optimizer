---
applyTo: '**'
---

# EngineScript Site Optimizer — Development Standards

## Project Context

- **Plugin:** EngineScript Site Optimizer — WordPress performance optimization plugin
- **Text Domain:** `enginescript-site-optimizer`
- **Function/Hook Prefix:** `es_optimizer_`
- **Version Constant:** `ES_SITE_OPTIMIZER_VERSION`
- **WordPress:** 6.5+ | **PHP:** 7.4+
- **Work Environment:** GitHub Codespaces (remote). Never suggest local terminal commands.

## Code Standards

### WordPress & PHP

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/) for PHP, JS, CSS, HTML, and accessibility
- Use WordPress APIs and hooks exclusively — no raw PHP/SQL or non-WP frameworks
- Prefix all functions, classes, hooks, and globals with `es_optimizer_`
- Use `wp_die()` instead of `die()` or `exit()`
- Use `WP_Error` for error handling; log errors without exposing sensitive data
- Use PHPDoc with `@param`, `@return`, `@since` tags on all functions
- Organize code by feature; use descriptive names; remove unused code
- Validate all function parameters and handle edge cases gracefully

### Modern PHP

- PHP 7.4+ features are required; PHP 8.x features are allowed if they degrade gracefully on 7.4
- Use typed function signatures wherever possible
- Before submitting changes, run `phpcs`, `phpmd`, and `phpstan` (config files present in project root)

## Security (Critical)

All code must follow OWASP Top 10 and WordPress security best practices. **Auto-identify and fix security vulnerabilities whenever found — never leave them.**

**Input:**
- Sanitize with `sanitize_text_field()`, `sanitize_email()`, `absint()`, or `wp_kses()` as appropriate
- Validate nonces with `wp_verify_nonce()` on all form submissions and AJAX handlers
- Use `$wpdb->prepare()` for every database query

**Output:**
- Escape with context-appropriate functions: `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()`, `esc_textarea()`
- Use `wp_nonce_field()` for all admin forms

**Access Control:**
- Check `current_user_can('manage_options')` before any settings operation
- Always include `if ( ! defined( 'ABSPATH' ) ) { return; }` at the top of every PHP file
- Prevent SQL injection, XSS, CSRF, LFI, and path traversal at all times

## Performance

- Cache expensive operations with `wp_cache_*()` and transients
- Avoid N+1 queries; optimize all database calls
- Enqueue assets via `wp_enqueue_scripts()` / `wp_enqueue_styles()` with correct dependencies and version strings
- Conditionally load admin assets only on relevant admin pages; conditionally load frontend assets only when needed

## Internationalization (i18n)

- Mark all user-facing strings with `__()`, `_e()`, `esc_html__()`, or `esc_attr__()`
- Always use text domain `enginescript-site-optimizer`
- Update `languages/enginescript-site-optimizer.pot` whenever translatable strings are added or changed

## Documentation & Versioning

**On every code change:**
- Add an entry to the `Unreleased` section of `CHANGELOG.md`
- Mirror the same entry in the changelog section of `readme.txt`

**Version releases (only when explicitly instructed):**
- Follow semantic versioning (MAJOR.MINOR.PATCH)
- Update version in: plugin file header, `ES_SITE_OPTIMIZER_VERSION` constant, `README.md`, `readme.txt`, `CHANGELOG.md`, `GEMINI.md`, `composer.json`, and `languages/enginescript-site-optimizer.pot`
- Move all `Unreleased` entries to the new version section in both `CHANGELOG.md` and `readme.txt`
- **Never auto-bump versions** — wait for an explicit instruction to do so

## Workflow

- Edit files in place — never create duplicate files or unnecessary new files
- Proceed automatically on non-destructive changes; ask before deleting files or data
- Auto-fix bugs and security issues when identified
- Keep responses concise and focused on what changed — no summary `.md` files