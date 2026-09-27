# EngineScript Site Optimizer

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/cf82cdb35973466abe7895e6d37666ed)](https://app.codacy.com/gh/EngineScript/enginescript-site-optimizer/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![GitHub License](https://img.shields.io/badge/License-GPL%20v3-green.svg?logo=gnu)](https://www.gnu.org/licenses/gpl-3.0.html)
[![WordPress Compatible](https://img.shields.io/badge/WordPress-6.8%2B-blue.svg?logo=wordpress)](https://wordpress.org/)
[![PHP Compatible](https://img.shields.io/badge/PHP-8.2%2B-purple.svg?logo=php)](https://www.php.net/)

## Current Version

[![Version](https://img.shields.io/badge/Version-2.1.0-orange.svg?logo=github)](https://github.com/EngineScript/enginescript-site-optimizer/releases/latest/download/enginescript-site-optimizer-2.1.0.zip)

## Description

A WordPress plugin for disabling selected features and configuring resource hints. Choose the options that suit your site and check their effects on your theme and plugins.

## Features

- **Header Cleanup:** Remove generator output, RSD links, and shortlinks from the page head
- **Script Optimization:** Disable WordPress emojis and remove jQuery Migrate
- **Style Optimization:** Remove inline styles from recent comments widget and disable classic theme styles
- **Resource Hints:** Configure DNS prefetch and preconnect for HTTPS origins used by your site
- **Jetpack Promotions:** Disable Jetpack promotional messages and Blaze promotions

## Installation

### Manual Installation

1. Download the latest release from the [releases page](https://github.com/EngineScript/enginescript-site-optimizer/releases)
2. Upload the plugin files to the `/wp-content/plugins/enginescript-site-optimizer` directory
3. Activate the plugin from the Plugins menu in WordPress
4. Configure the plugin under **Settings > Site Optimizer**

## Usage

1. Navigate to the Site Optimizer menu in your WordPress admin dashboard (under Settings)
2. Enable the optimization features you want to use
3. Configure resource hint domains if needed
4. Save your changes

All feature toggles start disabled. On multisite, configure each site's settings
separately; network activation does not create shared network settings.

### Resource Hint Lists

Enter one HTTPS origin per line, such as `https://cdn.example.com`. Paths other
than a trailing slash, query strings, fragments and credentials are rejected.
Preconnect accepts an omitted port or port 443. DNS prefetch uses only the
hostname, even if an accepted entry includes a port.

Each list allows 32,768 bytes in total, 100 nonblank lines and 512 bytes per
nonblank line. These limits apply before trimming or removing duplicate and
invalid entries. Empty or ASCII-space-only lines are blank.

Saving a blank list clears it. Within the limits, invalid entries are omitted;
an entirely invalid list is saved empty. A malformed or oversized submission
retains the previous list. An oversized saved list produces no plugin hints
until corrected. Legacy custom-port preconnect entries are skipped without
rewriting their saved text until you explicitly save the settings.

Use hints for origins your pages actually need and measure their effect on your
site. Browsers decide whether to use them; adding a hint does not establish a
performance improvement.

## Settings Protection

Settings submissions use WordPress Settings API nonce and capability checks.
The plugin checks domain input types and original URL text, escapes settings
output for its context, and reports rejected lines without repeating submitted
URLs or credentials.

Host validation rejects IP literals, localhost and configured reserved hostname
suffixes. It checks hostname syntax without resolving DNS. An accepted hostname
can still resolve to a private address in a visitor's environment; the plugin
does not verify its DNS destination or fetch the origin from the server.

## Quality Checks

The repository configures coding standards, static analysis, dependency checks,
unit tests, native WordPress tests and WordPress Plugin Check in GitHub Actions.
Passing results must be checked against the exact commit and resolved tool
versions. [Plugin Check](https://wordpress.org/plugins/plugin-check/) provides
automated checks; passing them does not guarantee WordPress.org approval or
replace manual review.

## Frequently Asked Questions

### Will this plugin work with my theme?

Compatibility depends on which features your theme and plugins use. Enable
options one at a time and check the affected pages, editor and admin screens.

### What does "Remove jQuery Migrate" do?

jQuery Migrate supports older jQuery APIs. Removing it can affect scripts that
depend on those APIs. Check your theme and plugins before enabling this option.

### What does "Disable WordPress Emojis" do?

This option disables selected WordPress emoji scripts, styles and conversion
filters. Check emoji rendering in the site, editor, feeds and email if you use
those features.

### Will removing the WordPress version improve security?

The option removes WordPress's generator output from the page head. Other
version indicators can remain. It does not fix vulnerabilities or replace
WordPress, theme and plugin updates.

### What happens when I deactivate or delete the plugin?

Deactivation retains settings. Deleting the plugin through WordPress runs its
uninstall cleanup, including separate site settings across multisite networks.
Pause site creation and settings changes during removal. Large installations
may need an operator-run uninstall with sufficient time and memory. If cleanup
fails, resolve the reported problem and retry removal.

## Development

### Requirements

- Plugin runtime: PHP 8.2 or higher and WordPress 6.8 or higher
- Development tools: Composer and a PHP version that satisfies `composer.lock`

### Set Up the Development Environment

1. Clone this repository: `git clone https://github.com/EngineScript/enginescript-site-optimizer.git`
2. Install development tools from the lock: `composer install --no-interaction --prefer-dist`.
   The development lock was validated with PHP 8.5.6; let Composer check its exact
   platform requirements. These are separate from the plugin's PHP 8.2 runtime minimum.
3. Run scoped static checks with `composer check-all`.
4. Submit changes for the [WordPress Compatibility workflow](.github/workflows/wp-compatibility-test.yml).

Plugin and automation suites run on fresh GitHub runners. The workflow runs the
unit suite through `composer test` before selecting PHPUnit 9.6 for native
WordPress tests. It generates its installer, native bootstrap, tests and config
on the runner; there is no checked-in local WordPress installer. Do not provision
a local suite as a replacement for the CI evidence.

### PHP Compatibility

The declared plugin minimum is PHP 8.2. The workflow covers PHP 8.2–8.5 with
WordPress 6.8, latest and nightly, plus a lowest-dependency lane. A compatibility
claim requires a successful run for the exact reviewed commit and resolved
versions. Static analysis and a Composer installation alone do not establish it.

## Development & Maintenance

### Automated WordPress Compatibility

This repository uses GitHub Actions to test the plugin against supported PHP and WordPress versions. The compatibility workflow runs WordPress Plugin Check, PHPCS, PHPMD, Psalm, PHPStan, dependency security checks, and generated WordPress integration tests.

### Automated Testing

The GitHub workflow defines isolated unit and generated native WordPress tests. Its configured matrix includes:

- PHP versions: 8.2, 8.3, 8.4, 8.5
- WordPress versions: 6.8, latest, nightly

Compatibility acceptance requires a successful run for the exact source commit;
the configured matrix alone does not establish a passing result.

## Contributing

Contributions are welcome. Please feel free to submit a pull request.

1. Fork the project
2. Create your feature branch: `git checkout -b feature/new-optimization`
3. Commit your changes: `git commit -m 'Add some new optimization'`
4. Push to the branch: `git push origin feature/new-optimization`
5. Open a Pull Request

## License

This project is licensed under the GPL-3.0-or-later license. See the [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes in each release.

## Credits

- Developed by [EngineScript](https://github.com/EngineScript)
- Special thanks to all contributors

## Support

For support, please open an issue in the GitHub repository or contact us at [support@enginescript.com](mailto:support@enginescript.com).

Report suspected vulnerabilities privately using [SECURITY.md](SECURITY.md).
