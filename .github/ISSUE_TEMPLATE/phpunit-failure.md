---
name: "PHPUnit Test Failure"
about: "Automated issue created when PHPUnit tests fail"
title: "PHPUnit Test Failure on PHP {{ env.PHP_VERSION }}"
labels: bug, testing, phpunit, automated
assignees: []
---

## PHPUnit Test Failure

The automated PHPUnit test suite has failed in the EngineScript Site Optimizer plugin.

### Details

- **PHP Version:** {{ env.PHP_VERSION }}
- **Test Date:** {{ date | date('YYYY-MM-DD HH:mm:ss') }}
- **Workflow Run:** [View detailed logs]({{ env.WORKFLOW_URL }})

### Test execution

The compatibility workflow runs isolated unit tests with the Composer-selected
PHPUnit 11.5/12 before pinning PHPUnit 9.6 for native WordPress tests. The native
suite runs in both single-site and multisite modes in each existing matrix cell.

### Diagnosis

1. Identify the failed setup, unit, native, or intentional-failure control step.
2. Inspect the run's PHP, resolved dependency, core revision and JUnit artifacts.
3. Reproduce on a fresh GitHub runner for the exact candidate commit; do not run
   plugin suites locally or copy generated runner files into tracked source.
4. Keep an empty suite, bootstrap failure and failed assertion distinct. All must
   fail the job; none establishes a plugin regression without further diagnosis.

The workflow generates its installer, native bootstrap, optimizer test file and
PHPUnit configuration. Use that workflow's commands and versions when rerunning.
Root `composer test` selects only the isolated unit config before generation.

### References

- [PHPUnit documentation](https://phpunit.de/documentation.html)
- [WordPress testing documentation](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
