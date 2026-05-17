---
name: "PHPMD Code Quality Failure"
about: "Automated issue created when PHPMD code quality check fails"
title: "PHPMD Code Quality Check Failed"
labels: ["bug", "phpmd", "code-quality"]
assignees: []
---

## PHPMD Code Quality Check Failed

The PHPMD (PHP Mess Detector) check has failed for the repository.

**Failure Details:**

- **PHP Version:** {{ env.PHP_VERSION }}
- **Workflow Run:** [View Details]({{ env.WORKFLOW_URL }})
- **Run ID:** {{ env.RUN_ID }}

**What happened:**
The code has quality issues detected by PHPMD analysis.

**What needs to be done:**

1. Review the PHPMD output in the failed workflow run
2. Address code quality issues such as:
   - Complex methods that should be simplified
   - Unused variables or parameters
   - Code duplication
   - Naming conventions
   - Design issues
3. Test locally with: `composer run phpmd`

**Resources:**

- [PHPMD Documentation](https://phpmd.org/)
- [PHPMD Rules](https://phpmd.org/rules/index.html)

This issue was automatically created by the CI/CD pipeline.
