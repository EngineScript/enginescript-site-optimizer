---
name: Plugin Check Failure
about: Automated issue for WordPress Plugin Check failures
title: WordPress Plugin Check Failure
labels: bug, plugin-check, automated
assignees: []
---

## WordPress Plugin Check Failure

The Plugin Check job failed. Inspect the failure stage before attributing the failure to plugin code.

**Failure stage:** `{{ env.FAILURE_STAGE }}`

### Details

- **Test Date:** {{ date | date('YYYY-MM-DD HH:mm:ss') }}
- **PHP Version:** {{ env.PHP_VERSION }}
- **Workflow Run:** [View detailed logs]({{ env.WORKFLOW_URL }})

### Configured Scope

The workflow selects the `accessibility`, `general`, `performance`, `plugin_repo`
and `security` categories. Individual checks depend on the resolved Plugin Check
version; use its actual output to identify check names and findings.

The job stages the optimizer package before running Plugin Check. If staging or
package verification failed, inspect the eleven-file manifest and original error
first. It includes `uninstall.php`, the four `includes/` files and the POT, along
with the entry file, public readmes, changelog and license. There are no standalone
`css/` or `js/` directories to copy.

### Next Steps

1. Record the exact commit, failed stage, resolved checker version and run logs
2. Distinguish installation/package errors from checker findings
3. Review each reported check in the context of the selected package
4. Correct the demonstrated issue and re-run the existing GitHub job

[WordPress Plugin Check](https://wordpress.org/plugins/plugin-check/) provides
automated checks. A passing result does not guarantee WordPress.org approval,
replace manual review, or establish browser accessibility and site performance.
