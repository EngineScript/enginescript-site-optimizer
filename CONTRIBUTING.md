# Contributing to EngineScript Site Optimizer

Thank you for considering contributing to EngineScript Site Optimizer! This document provides guidelines and instructions for contributors.

## Code of Conduct

This project follows the [WordPress Community Code of Conduct](https://make.wordpress.org/handbook/community-code-of-conduct/). By participating, you're expected to uphold this code.

## Development Environment

### Requirements

- **PHP**: 7.4 or higher
- **WordPress**: 6.5 or higher
- **Composer**: For dependency management
- **Node.js**: 16+ (if working with build tools)
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
   composer install
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
composer run lint:php
# OR
./vendor/bin/phpcs
```

### PHPStan

Run static analysis:
```bash
./vendor/bin/phpstan analyse
```

### PHPUnit

Run tests (when available):
```bash
./vendor/bin/phpunit
```

## File Structure

```
enginescript-site-optimizer/
├── enginescript-site-optimizer.php    # Main plugin file
├── README.md                  # Project documentation
├── readme.txt                 # WordPress.org readme
├── CHANGELOG.md              # Version history
├── CONTRIBUTING.md           # This file
├── LICENSE                   # GPL license
├── composer.json             # PHP dependencies
├── phpcs.xml                 # PHPCS configuration
├── phpstan.neon              # PHPStan configuration
├── phpmd.xml                 # PHPMD configuration
├── languages/                # Translation files
│   └── enginescript-site-optimizer.pot
└── .github/                  # GitHub workflows
    └── workflows/
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
- **Sanitization**: Use `sanitize_text_field()`, `sanitize_textarea_field()`, etc.
- **Nonce Verification**: Protect forms with WordPress nonces
- **Capability Checks**: Verify user permissions with `current_user_can()`

#### Documentation
- **PHPDoc**: All functions must have PHPDoc comments
- **@since**: Include version tags for new functions
- **Inline Comments**: Explain complex logic
- **Security Notes**: Document security measures taken

#### Example Function:
```php
/**
 * Example function with proper documentation
 *
 * @since 1.5.13
 * @param string $input User input to process.
 * @return string Sanitized output.
 */
function es_optimizer_example_function( $input ) {
    // Security: Validate and sanitize input
    if ( ! current_user_can( 'manage_options' ) ) {
        return '';
    }
    
    $sanitized = sanitize_text_field( $input );
    
    // Additional processing...
    
    return esc_html( $sanitized );
}
```

### Testing

1. **Manual Testing**:
   - Test in WordPress 6.5+ and latest version
   - Test with PHP 7.4 and 8.3+
   - Verify admin interface functionality
   - Check frontend optimizations

2. **Automated Testing**:
   - Run PHPCS for coding standards
   - Run PHPStan for static analysis
   - Ensure CI/CD tests pass

### Performance Guidelines

1. **Option Caching**: Use the plugin's `es_optimizer_get_options()` function
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
   composer run lint:php
   ./vendor/bin/phpstan analyse
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
```
feat: add DNS prefetch domain validation
fix: resolve jQuery migrate removal issue
docs: update installation instructions
style: fix PHPCS formatting violations
```

### Pull Request Checklist

- [ ] Code follows WordPress coding standards
- [ ] All functions have proper PHPDoc documentation
- [ ] Security best practices implemented
- [ ] PHPCS and PHPStan checks pass
- [ ] Manual testing completed
- [ ] Documentation updated if needed
- [ ] CHANGELOG.md updated

## Version Management

### Updating Versions

When releasing new versions, update these files:
- `enginescript-site-optimizer.php` (plugin header)
- `README.md`
- `readme.txt`
- `CHANGELOG.md`
- `languages/enginescript-site-optimizer.pot`

### Semantic Versioning

This project follows [Semantic Versioning](https://semver.org/):
- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

## Support Channels

- **Issues**: [GitHub Issues](https://github.com/EngineScript/enginescript-site-optimizer/issues)
- **Discussions**: [GitHub Discussions](https://github.com/EngineScript/enginescript-site-optimizer/discussions)
- **Security**: Email security@enginescript.com for security issues

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

By contributing to EngineScript Site Optimizer, you agree that your contributions will be licensed under the [GPL v2 or later](LICENSE) license.

---

Thank you for contributing to EngineScript Site Optimizer! 🚀
