# Contributing to the CleverReach PHP SDK

Thank you for your interest in contributing to this project!
Contributions in the form of bug fixes, improvements, documentation updates, or new features are very welcome.

Please read these guidelines carefully before creating an issue or submitting a pull request.

---

## Ways to Contribute

You can contribute by:

- Reporting bugs
- Suggesting new features
- Submitting bug fixes
- Improving documentation
- Adding tests or refactoring code

---

## Reporting Issues

Before creating a new issue:

1. Check if a similar issue already exists
2. Use the provided **Issue Templates**
3. Include the following information:
   - Expected behavior
   - Actual behavior
   - Steps to reproduce
   - PHP version
   - SDK version
   - Composer version

**Do not report security vulnerabilities publicly.**
Please refer to [`SECURITY.md`](SECURITY.md).

---

## Pull Requests

### Workflow

1. Fork the repository
2. Create a feature or fix branch
3. Implement your changes
4. Make sure all tests and checks pass
5. Open a pull request against the `main` branch

Please use the provided **Pull Request Template**.

---

## Branching Strategy

- `main` → stable production branch
- `feature/<short-description>`
- `fix/<issue-or-description>`

Examples:

```
feature/add-order-sync
fix/null-pointer-on-install
```

---

## Commit Message Guidelines

We recommend using **Conventional Commits**:

```
type(scope): short description
```

**Examples:**

- `feat(sync): add order export`
- `fix(config): handle missing api key`
- `docs(readme): update installation steps`

**Allowed types:**

- `feat`
- `fix`
- `docs`
- `refactor`
- `test`
- `chore`

---

## Coding Standards

- PHP: **PSR-12** (run `composer cs-check` and `composer cs-fix`)
- Make sure tests are passing (run `composer test`)
- Use strict typing where reasonable
- Keep code readable and well-structured

---

## Local Development Setup

Recommended:

- PHP ≥ 8.2
- Composer

### Example setup

```bash
git clone https://github.com/cleverreach/cleverreachsdk-php.git
cd cleverreachsdk-php
composer install

# Run tests
composer test

# Run code style checks
composer cs-check
```

---

## Tests & Quality

- Changes must not break existing functionality
- New features should include tests if possible
- Code must run without PHP errors or warnings
- All CI checks must pass before review

---

## Legal Notice

By submitting a pull request, you agree that your contribution
will be licensed under the **MIT License** of this project.

---

## Thank You

Thank you for contributing and helping to improve this project!
If you have questions, feel free to open an issue or start a discussion.
