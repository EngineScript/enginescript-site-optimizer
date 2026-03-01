# EngineScript Site Optimizer

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/cf82cdb35973466abe7895e6d37666ed)](https://app.codacy.com/gh/EngineScript/enginescript-site-optimizer/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Github License](https://img.shields.io/badge/License-GPL%20v3-green.svg?logo=gnu)](https://www.gnu.org/licenses/gpl-3.0.html)
[![WordPress Compatible](https://img.shields.io/badge/WordPress-6.5%2B-blue.svg?logo=wordpress)](https://wordpress.org/)
[![PHP Compatible](https://img.shields.io/badge/PHP-7.4%2B-purple.svg?logo=php)](https://www.php.net/)

## Current Version

[![Version](https://img.shields.io/badge/Version-2.0.0-orange.svg?logo=github)](https://github.com/EngineScript/enginescript-site-optimizer/releases/download/v2.0.0/enginescript-site-optimizer-2.0.0.zip)

## Description

A lightweight WordPress plugin designed to optimize your website by removing unnecessary scripts, styles, and header elements that can slow down your site.

## Features

- **Header Cleanup:** Remove WordPress version, WLW manifest links, and shortlinks
- **Script Optimization:** Disable WordPress emojis and remove jQuery Migrate
- **Style Optimization:** Remove inline styles from recent comments widget and disable classic theme styles
- **DNS Prefetching:** Add DNS prefetch for common external domains to improve load times (HTTPS-only for security)
- **Jetpack Optimization:** Remove Jetpack advertisements and promotions

## Installation

### Manual Installation

1. Download the latest release from the [releases page](https://github.com/EngineScript/enginescript-site-optimizer/releases)
2. Upload the plugin files to the `/wp-content/plugins/enginescript-site-optimizer` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin settings via the 'Site Optimizer' menu

### Using Composer

```bash
composer require enginescript/enginescript-site-optimizer
```

## Usage

1. Navigate to the Site Optimizer menu in your WordPress admin dashboard (under Settings)
2. Enable the optimization features you want to use
3. Configure the DNS Prefetch domains if needed
4. Save your changes

## Screenshots

1. **Settings Page:** Configure which optimizations to enable
2. **Header Cleanup Options:** Remove unnecessary elements from WordPress headers
3. **Performance Options:** Disable emojis and jQuery Migrate
4. **DNS Prefetch Configuration:** Add domains for DNS prefetching

## Security Features

This plugin implements comprehensive security measures following WordPress and OWASP best practices:

- **CSRF Protection:** WordPress nonce verification for all form submissions
- **Input Validation:** Multi-layer validation and sanitization for all user inputs
- **Output Escaping:** Context-appropriate escaping for all outputs (HTML, attributes, URLs)
- **HTTPS Enforcement:** DNS prefetch domains must use HTTPS protocol for security
- **SSRF Prevention:** Blocks private IP ranges and localhost addresses
- **Capability Checks:** Proper user permission verification for all admin functions
- **Direct Access Prevention:** Prevents direct script execution outside WordPress

## WordPress.org Compliance

This plugin is fully compliant with WordPress.org standards:

- ✅ **Plugin Check Passed:** Passes all WordPress Plugin Check automated tests
- ✅ **Security Standards:** Follows WordPress and OWASP security guidelines
- ✅ **Coding Standards:** Adheres to WordPress coding conventions and best practices
- ✅ **Internationalization:** Ready for translation with proper i18n implementation
- ✅ **Performance:** Optimized code structure with reduced complexity

## Frequently Asked Questions

### Will this plugin work with my theme?

EngineScript Site Optimizer is designed to be compatible with most WordPress themes. The optimizations focus on removing unnecessary WordPress elements rather than modifying theme functionality.

### What does "Remove jQuery Migrate" do?

jQuery Migrate is a script that helps maintain backward compatibility with older jQuery code. Modern themes and plugins generally don't need it, so removing it can improve load time without affecting functionality in most cases.

### What does "Disable WordPress Emojis" do?

This option removes emoji-related scripts and styles that WordPress adds by default. Most websites don't need these resources, so removing them can reduce HTTP requests and improve page load time.

### Will removing the WordPress version improve security?

Yes, hiding the WordPress version can provide a minor security benefit by making it slightly more difficult for potential attackers to identify vulnerability targets based on your WordPress version.

## Development

### Requirements

- PHP 7.4 or higher
- WordPress 6.5 or higher
- Composer (for development and testing)

### Setting up the development environment

1. Clone this repository: `git clone https://github.com/EngineScript/enginescript-site-optimizer.git`
2. Install dependencies: `composer install`
3. Set up the test environment: `bin/install-wp-tests.sh wordpress_test root '' localhost latest`
4. Run tests: `composer test`

### PHP 8.x Compatibility

This plugin is fully compatible with PHP versions 7.4 through 8.4. For testing with PHP 8.x, we provide a custom PHPUnit runner script that helps avoid common compatibility issues between PHPUnit and newer PHP versions:

```bash
# For PHP 7.4 (standard testing)
composer test

# For PHP 8.0, 8.1, 8.2, 8.3, and 8.4 (using the custom runner)
composer test:php8
```

The custom runner automatically detects your PHP version and applies the appropriate compatibility settings.

## Development & Maintenance

### Automated WordPress Compatibility

This repository uses the [WordPress Version Checker](https://github.com/skaut/wordpress-version-checker) GitHub Action to automatically monitor WordPress compatibility. When a new WordPress version is released, the action:

1. Creates a pull request to update the "Tested up to" version in readme.txt
2. Adds "automated" and "documentation" labels to the PR
3. Assigns repository maintainers for review

This ensures our plugin stays up-to-date with the latest WordPress versions without manual intervention.

### Automated Testing

The plugin includes a comprehensive PHPUnit test suite that runs automatically on GitHub Actions. Our testing matrix includes:

- PHP versions: 7.4, 8.0, 8.1, 8.2, 8.3, 8.4
- WordPress versions: 6.5, latest, nightly
- Both single site and multisite installations

This ensures code quality and compatibility across different PHP versions and WordPress configurations.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the project
2. Create your feature branch: `git checkout -b feature/new-optimization`
3. Commit your changes: `git commit -m 'Add some new optimization'`
4. Push to the branch: `git push origin feature/new-optimization`
5. Open a Pull Request

## License

This project is licensed under the GPL-2.0+ License - see the [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes in each release.

## Credits

- Developed by [EngineScript](https://github.com/EngineScript)
- Special thanks to all contributors

## Support

For support, please open an issue in the GitHub repository or contact us via [support@enginescript.com](mailto:support@enginescript.com).
