# Contributing to EngineScript Site Optimizer

Thank you for considering contributing to EngineScript Site Optimizer! This document provides guidelines and instructions for contributors.

## Code of Conduct

Please follow the project's [Code of Conduct](.github/CODE_OF_CONDUCT.md).

## Development Environment

### Requirements

- **Plugin runtime**: PHP 8.2 or higher and WordPress 6.8 or higher
- **Development PHP**: A version satisfying `composer.lock`; the current lock was validated with PHP 8.5.6
- **Composer**: For dependency management
- **Git**: For version control

### Setup

1. Fork the repository on GitHub
2. Clone your fork locally:

   ```bash
   git clone https://github.com/YOUR-USERNAME/enginescript-site-optimizer.git
   cd enginescript-site-optimizer
   ```

3. Install dependencies:

   ```bash
   composer install --no-interaction --prefer-dist
   ```

4. Create a feature branch:

   ```bash
   git checkout -b feature/your-feature-name
   ```

## Coding Standards

### WordPress Coding Standards

This project adheres to **WordPress Coding Standards**:

- **PHP**: [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- **JavaScript**: [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- **CSS**: [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- **HTML**: [WordPress HTML Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/html/)

### Key Principles

1. **Security First**: All code must follow OWASP security guidelines
   - Input validation and sanitization
   - Output escaping with context-appropriate functions
   - CSRF protection with nonces
   - Capability checks for admin functions

2. **Performance**: Optimize for efficiency
   - Use WordPress caching mechanisms
   - Minimize database queries
   - Conditional loading of assets

3. **Internationalization**: All user-facing strings must be translatable
   - Use `__()`, `_e()`, `esc_html__()`, `esc_html_e()` functions
   - Text domain: `enginescript-site-optimizer`

4. **Accessibility**: Follow WCAG guidelines
   - Proper semantic markup
   - Keyboard navigation support
   - Screen reader compatibility

## Code Quality Tools

### PHP CodeSniffer (PHPCS)

Run coding standards checks:

```bash
composer run phpcs
```

### PHPStan

Run static analysis:

```bash
composer run phpstan
```

### PHPUnit

The [WordPress Compatibility workflow](.github/workflows/wp-compatibility-test.yml)
runs the isolated unit suite through `composer test` on a fresh runner before
pinning PHPUnit 9.6 for generated native WordPress tests. Each matrix cell then
runs single-site and multisite modes. Root `phpunit.xml` discovers only unit
tests; the generated config selects the native file explicitly, excluding
dependency directories and the unit bootstrap.

Run plugin and automation suites in GitHub Actions. Use `composer check-all`
for local static feedback. The runner generates `tests/bin/install-wp-tests.sh`;
do not copy ignored integration dependencies or create a local test environment
to replace remote acceptance. Link the exact commit/run, matrix results and
diagnostic artifacts when reporting compatibility. Browser, assistive-technology,
external-plugin and deployment-scale checks need separate recorded results.

## File Structure

```text
enginescript-site-optimizer/
|-- enginescript-site-optimizer.php    # Main plugin file
|-- uninstall.php                      # Site and multisite settings cleanup
|-- includes/                          # Plugin source files
|-- README.md                          # Project documentation
|-- readme.txt                         # WordPress.org readme
|-- CHANGELOG.md                       # Version history
|-- CONTRIBUTING.md                    # This file
|-- LICENSE                            # GPL license
|-- composer.json                      # PHP dependencies
|-- composer.lock                      # Locked development dependencies
|-- phpcs.xml                          # PHPCS configuration
|-- phpstan.neon                       # PHPStan configuration
|-- phpmd.xml                          # PHPMD configuration
|-- psalm.xml                          # Psalm configuration
|-- phpunit.xml                        # Isolated unit-suite configuration
|-- tests/                             # Unit bootstrap and tests
|-- stubs/                             # Static-analysis bootstrap declarations
|-- languages/                         # Translation files
|   `-- enginescript-site-optimizer.pot
`-- .github/
    |-- scripts/                       # Package/metadata checks and automation fixtures
    |-- ISSUE_TEMPLATE/                # Failure-report templates
    `-- workflows/                     # Quality gates and generated native tests
```

## Making Changes

### Before You Start

1. Check existing [issues](https://github.com/EngineScript/enginescript-site-optimizer/issues) and [pull requests](https://github.com/EngineScript/enginescript-site-optimizer/pulls)
2. Create an issue for significant changes to discuss the approach
3. Follow the existing code patterns and conventions

### Code Requirements

#### Security

- **Input Validation**: Validate all user inputs
- **Output Escaping**: Use `esc_html()`, `esc_attr()`, `esc_url()` as appropriate
- **Sanitization**: Choose helpers for the input's contract. Validate resource-hint URL text before lossy normalization; use the existing domain helpers.
- **Nonce Verification**: Protect forms with WordPress nonces
- **Capability Checks**: Verify user permissions with `current_user_can()`

#### Documentation

- **PHPDoc**: All functions must have PHPDoc comments
- **@since**: Include version tags for new functions
- **Inline Comments**: Explain complex logic
- **Security Notes**: Document security measures taken

#### Example Function

```php
/**
 * Return escaped plain text for an HTML text node.
 *
 * @since Unreleased
 * @param string $input User input to process.
 * @return string Escaped text, or an empty string without permission.
 */
function es_optimizer_example_function( string $input ): string {
    // Security: Validate and sanitize input.
    if ( ! current_user_can( 'manage_options' ) ) {
        return '';
    }

    $sanitized = sanitize_text_field( $input );

    // Additional processing.

    return esc_html( $sanitized );
}
```

### Testing

1. **Manual Testing in an approved environment**:
   - Record the exact source commit, PHP/WordPress versions, theme and relevant plugins
   - Check settings saves, permission/nonce failures and rendered notices
   - Check frontend, admin, editor and embed behavior in separate complete requests
   - Check separate multisite settings and lifecycle behavior, including failure paths
   - Record browser/accessibility results and any checks still pending

2. **Automated Testing**:
   - Run PHPCS for coding standards
   - Run PHPStan for static analysis
   - Link GitHub results and artifacts for the exact source commit; distinguish setup failures from failed assertions

### Performance Guidelines

1. **Option Caching**: Use `es_optimizer_get_options()`; WordPress owns the current site's option cache
2. **Conditional Loading**: Only load assets when needed
3. **Database Queries**: Minimize and optimize database interactions
4. **Hook Priority**: Use appropriate hook priorities

## Submitting Changes

### Pull Request Process

1. **Create Feature Branch**:

   ```bash
   git checkout -b feature/description-of-change
   ```

2. **Make Changes**:
   - Follow coding standards
   - Add/update tests if applicable
   - Update documentation

3. **Test Changes**:

   ```bash
   composer run phpcs
   composer run phpstan
   ```

4. **Commit Changes**:

   ```bash
   git add .
   git commit -m "feat: add new optimization feature"
   ```

5. **Push and Create PR**:

   ```bash
   git push origin feature/description-of-change
   ```

### Commit Message Format

Use [Conventional Commits](https://conventionalcommits.org/):

- `feat:` New features
- `fix:` Bug fixes
- `docs:` Documentation changes
- `style:` Code style changes
- `refactor:` Code refactoring
- `test:` Test additions/changes
- `chore:` Maintenance tasks

Examples:

```text
feat: add DNS prefetch domain validation
fix: resolve jQuery migrate removal issue
docs: update installation instructions
style: fix PHPCS formatting violations
```

### Pull Request Checklist

- [ ] Code follows WordPress coding standards
- [ ] All functions have proper PHPDoc documentation
- [ ] Security best practices implemented
- [ ] Applicable static checks pass and exact-commit GitHub results are linked
- [ ] Applicable manual checks are recorded, including any pending work
- [ ] Documentation updated if needed
- [ ] Main plugin changes are recorded in both `CHANGELOG.md` and the `readme.txt` changelog

Changes limited to `.github/`, `tests/`, `stubs/`, `.private/` or `languages/`
do not require changelog entries.

## Version Management

### Updating Versions

Change version numbers only for an explicitly requested release. Keep these
surfaces synchronized and move the paired Unreleased entries into the release:

- `enginescript-site-optimizer.php` (plugin header and `ES_SITE_OPTIMIZER_VERSION`)
- `README.md` (version badge and download link)
- `readme.txt` (stable tag and changelog)
- `CHANGELOG.md` (release heading and entries)
- `languages/enginescript-site-optimizer.pot` (project version)

### Semantic Versioning

This project follows [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

## Translation Template

Use the literal `enginescript-site-optimizer` text domain for user-facing strings.
Add translator comments for placeholders, use numbered placeholders when there
is more than one, and escape translated text for its output context.

After source strings settle, run the same extraction command as the
[Update Translation File workflow](.github/workflows/update-pot-file.yml) from
the repository root with WP-CLI's `i18n` command available:

```bash
wp i18n make-pot . languages/enginescript-site-optimizer.pot \
  --slug=enginescript-site-optimizer \
  --domain=enginescript-site-optimizer \
  --include=enginescript-site-optimizer.php,includes,uninstall.php \
  --exclude=vendor,node_modules,tests,build,plugin-check-build,.git,.github,.private
```

Review message text, source references, translator notes, and plural forms in
the generated diff. Repeating extraction with the same sources and WP-CLI/i18n
versions should change only `POT-Creation-Date`. A POT is a translation template;
it does not provide a translated interface. Verify actual locale loading and
rendered output separately when adding translations.

## Support Channels

- **Issues**: [GitHub Issues](https://github.com/EngineScript/enginescript-site-optimizer/issues)
- **Discussions**: [GitHub Discussions](https://github.com/EngineScript/enginescript-site-optimizer/discussions)
- **Security**: Follow the private reporting instructions in [SECURITY.md](SECURITY.md)

## Resources

### WordPress Development

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Security Guidelines](https://developer.wordpress.org/plugins/security/)

### Security Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [WordPress Security Handbook](https://make.wordpress.org/core/handbook/testing/reporting-security-vulnerabilities/)

### Tools

- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [PHPStan](https://phpstan.org/)
- [WordPress Plugin Check](https://wordpress.org/plugins/plugin-check/)

## License

By contributing to EngineScript Site Optimizer, you agree that your contributions will be licensed under the [GPL-3.0-or-later](LICENSE) license.

---

Thank you for contributing to EngineScript Site Optimizer!
