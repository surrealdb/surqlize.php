# Contributing

Thank you for considering a contribution to Surqlize.

## Pull Request Review Policy

All code changes should be submitted through a pull request and reviewed before merge. Direct pushes to protected branches should be avoided except for emergency repository administration.

Before a pull request is merged:

- At least one approving human review is required.
- The approving reviewer should not be the pull request author.
- Required GitHub Actions checks must pass.
- Security-sensitive changes should receive extra scrutiny from a maintainer familiar with the affected area.
- Administrators and maintainers should follow the same review requirements as other contributors.

This policy helps catch correctness, security, and maintainability issues before changes are released.

## Local Checks

Run the same checks used by CI before opening a pull request:

```bash
composer validate --no-check-publish
composer audit --locked
composer analyse
composer test
```

## Security Issues

Do not report suspected vulnerabilities in public issues or pull requests. See `SECURITY.md` for private vulnerability reporting instructions.

## GitHub Repository Settings

Maintainers should enforce this policy in GitHub branch protection or repository rulesets for the default branch:

- Require a pull request before merging.
- Require at least one approval.
- Dismiss stale approvals when new commits are pushed.
- Require review from Code Owners once `CODEOWNERS` is configured.
- Require the `Tests` workflow to pass.
- Include administrators in the rule.
- Prevent bypassing the rule except for narrowly scoped emergency access.
