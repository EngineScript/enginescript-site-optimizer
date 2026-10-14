# Contributing to EngineScript Site Optimizer

Thank you for considering a contribution to EngineScript Site Optimizer. This guide keeps development, code quality, and review expectations aligned with this plugin.

## Code of Conduct

This project follows the [WordPress Community Code of Conduct](https://make.wordpress.org/handbook/community-code-of-conduct/). By participating, you are expected to uphold this code.

## Development Environment

### Requirements

- **PHP**: 8.2 or higher
- **WordPress**: 6.8 or higher
- **Composer**: For dependency management and quality tools
- **Git**: For version control

### Setup

1. Fork the repository on GitHub.
2. Clone your fork locally:

   ```bash
   git clone https://github.com/YOUR-USERNAME/enginescript-site-optimizer.git
   cd enginescript-site-optimizer
   ```

3. Install dependencies:

   ```bash
   composer install
   npm install
   ```

   `composer.lock` and `package-lock.json` are not tracked. Composer and npm resolve the development tools on your machine, and each CI job resolves them on its runner.

4. Create a feature branch:

   ```bash
   git checkout -b feature/your-feature-name
   ```

## Coding Standards

This project follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/) for PHP, HTML, and accessibility.

The plugin ships no JavaScript and no CSS. Its settings screen is rendered by PHP through the WordPress Settings API. If a change needs a script or a stylesheet, open an issue first.

### Key Principles

1. **Security First**: Validate and sanitize input, escape output, protect forms with nonces, and check capabilities before privileged actions.
2. **Performance**: Use WordPress option caching, avoid unnecessary database work, and do nothing on a request that an option does not apply to.
3. **Internationalization**: Mark user-facing strings with WordPress i18n functions and use the `enginescript-site-optimizer` text domain.
4. **Accessibility**: Use semantic markup and preserve keyboard and screen reader behavior in admin UI changes.

## Code Quality Tools

Run the main checks before submitting a pull request:

```bash
composer run phpcs
composer run phpstan
composer run phpmd
composer run psalm
composer test
```

`composer run check-all` runs these checks, Psalm's taint analysis, and the unit tests in one go. PHPStan runs at level `max` and Psalm at error level 2, with no baseline.

### Test Layers

- **Unit tests** (`tests/unit`, run by `composer test`): PHPUnit tests that run without WordPress. `tests/bootstrap.php` defines stand-ins for the WordPress functions the plugin calls.
- **WordPress integration tests**: the compatibility workflow writes this suite on the runner and runs it against real WordPress versions, on a single site and on multisite. It is not kept in the repository.

## File Structure

```text
enginescript-site-optimizer/
|-- enginescript-site-optimizer.php     # Main plugin file
|-- uninstall.php                       # Removes the plugin's settings on uninstall
|-- includes/                           # Plugin PHP functions
|   |-- bootstrap.php                   # Hook registration
|   |-- options.php                     # Option defaults, retrieval, and validation
|   |-- admin.php                       # Settings page and Settings API registration
|   `-- frontend.php                    # Front-end optimization callbacks
|-- languages/                          # Translation template
|   `-- enginescript-site-optimizer.pot
|-- tests/
|   |-- bootstrap.php                   # WordPress stand-ins for the unit tests
|   `-- unit/                           # PHPUnit unit tests
|-- stubs/                              # Static-analysis stubs
|-- README.md                           # Project documentation
|-- readme.txt                          # Plugin readme; the only readme in the release package
|-- CHANGELOG.md                        # Version history
|-- CONTRIBUTING.md                     # This file
|-- SECURITY.md                         # Security policy
|-- LICENSE                             # GPL license
|-- composer.json                       # PHP dependencies and check scripts
|-- phpcs.xml                           # PHPCS configuration
|-- phpstan.neon                        # PHPStan configuration
|-- phpmd.xml                           # PHPMD configuration
|-- psalm.xml                           # Psalm configuration
|-- phpunit.xml                         # PHPUnit configuration
|-- .distignore                         # Files left out of the release package
`-- .github/                            # GitHub workflows and templates
```

The release package holds the main plugin file, `uninstall.php`, `readme.txt`, `LICENSE`, `includes/`, and `languages/`. When you add a top-level file or directory, add it to `.distignore` and `.gitattributes` unless it must ship; `.github/scripts/check-plugin-package.py` fails when the package lists disagree.

## Making Changes

### Before You Start

1. Check existing [issues](https://github.com/EngineScript/enginescript-site-optimizer/issues) and [pull requests](https://github.com/EngineScript/enginescript-site-optimizer/pulls).
2. Create an issue for significant behavior changes.
3. Follow the existing code patterns in the relevant `includes/` file.

### Code Requirements

- **Input validation**: Validate all user input before use.
- **Output escaping**: Use `esc_html()`, `esc_attr()`, `esc_url()`, or another context-appropriate escaping function.
- **Sanitization**: Use WordPress sanitizers such as `sanitize_text_field()`, `sanitize_textarea_field()`, and `absint()`.
- **Nonce verification**: Use WordPress nonces for forms and state-changing requests. The settings form relies on the Settings API for this.
- **Capability checks**: Verify permissions with `current_user_can()`.
- **PHPDoc**: Add `@param`, `@return`, and `@since` tags for new functions.

### Example Function

```php
/**
 * Example function with proper documentation.
 *
 * @since 2.2.0
 * @param string $input User input to process.
 * @return string Sanitized output.
 */
function es_optimizer_example_function( string $input ): string {
    if ( ! current_user_can( 'manage_options' ) ) {
        return '';
    }

    $sanitized = sanitize_text_field( $input );

    return esc_html( $sanitized );
}
```

### Testing

1. **Manual testing**:

   - Test with WordPress 6.8 or higher.
   - Test with PHP 8.2 or higher.
   - Verify the settings screen at **Settings > Site Optimizer**.
   - Turn each option on and off and check the page source of the front end for its effect.
   - Save a preconnect list and a DNS prefetch list, including a line that must be rejected, and check the notice and the printed resource hints.
   - On multisite, check that each site keeps its own settings.

2. **Automated testing**:

   - Run PHPCS for coding standards.
   - Run PHPStan and Psalm for static analysis.
   - Run PHPMD for code quality checks.
   - The compatibility workflow on GitHub runs all of these, the unit tests, and the WordPress integration tests, on every push and pull request.

## Submitting Changes

### Pull Request Process

1. Create a feature branch:

   ```bash
   git checkout -b feature/description-of-change
   ```

2. Make your changes:

   - Follow WordPress coding standards.
   - Add or update tests where useful.
   - Update documentation when behavior changes.

3. Run checks:

   ```bash
   composer run check-all
   ```

4. Commit your changes:

   ```bash
   git add .
   git commit -m "fix: update optimization behavior"
   ```

5. Push and open a pull request:

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
- `test:` Test additions or changes
- `chore:` Maintenance tasks

## Pull Request Checklist

- [ ] Code follows WordPress coding standards
- [ ] New functions have useful PHPDoc
- [ ] Security best practices are implemented
- [ ] PHPCS, PHPStan, PHPMD, and Psalm checks pass
- [ ] Manual testing is complete
- [ ] Documentation is updated if needed
- [ ] CHANGELOG.md and readme.txt are updated if behavior changes

## Version Management

When releasing new versions, update these files:

- `enginescript-site-optimizer.php` plugin header and the `ES_SITE_OPTIMIZER_VERSION` constant
- `README.md`, including the version badge
- `readme.txt` stable tag and changelog
- `CHANGELOG.md`
- `languages/enginescript-site-optimizer.pot`

This project follows [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features
- **PATCH**: Bug fixes

## Translation Template

Use the literal `enginescript-site-optimizer` text domain for user-facing strings. Add translator comments for placeholders, use numbered placeholders when there is more than one, and escape translated text for its output context.

After source strings settle, run the same extraction command as the [Update Translation File workflow](.github/workflows/update-pot-file.yml) from the repository root with WP-CLI's `i18n` command available:

```bash
wp i18n make-pot . languages/enginescript-site-optimizer.pot \
  --slug=enginescript-site-optimizer \
  --domain=enginescript-site-optimizer \
  --include=enginescript-site-optimizer.php,includes,uninstall.php \
  --exclude=vendor,node_modules,tests,build,plugin-check-build,.git,.github,.private
```

Review message text, source references, translator notes, and plural forms in the generated diff. A POT is a translation template; it does not provide a translated interface.

## Support Channels

- **Issues**: [GitHub Issues](https://github.com/EngineScript/enginescript-site-optimizer/issues)
- **Security**: Report security issues privately through the [vulnerability report form](https://github.com/EngineScript/enginescript-site-optimizer/security/advisories/new); see [SECURITY.md](SECURITY.md)

## Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Security Guidelines](https://developer.wordpress.org/plugins/security/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [PHPStan](https://phpstan.org/)
- [WordPress Plugin Check](https://wordpress.org/plugins/plugin-check/)

## License

By contributing to EngineScript Site Optimizer, you agree that your contributions will be licensed under [GPL-3.0-or-later](LICENSE).
