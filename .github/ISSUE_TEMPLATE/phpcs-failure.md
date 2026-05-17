---
name: "PHPCS Code Standards Failure"
about: "Automated issue created when PHPCS code standards check fails"
title: "PHPCS Code Standards Check Failed"
labels: ["bug", "phpcs", "code-standards"]
assignees: []
---

## PHPCS Code Standards Check Failed

The PHPCS (PHP CodeSniffer) check has failed for the repository.

**Failure Details:**

- **PHP Version:** {{ env.PHP_VERSION }}
- **Workflow Run:** [View Details]({{ env.WORKFLOW_URL }})
- **Run ID:** {{ env.RUN_ID }}

**What happened:**
The code does not meet WordPress coding standards as defined by PHPCS.

**What needs to be done:**

1. Review the PHPCS output in the failed workflow run
2. Fix coding standard violations in the code
3. Ensure all PHP files follow WordPress coding standards
4. Test locally with: `composer run phpcs`

**Resources:**

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [PHP CodeSniffer Documentation](https://github.com/squizlabs/PHP_CodeSniffer)

This issue was automatically created by the CI/CD pipeline.
