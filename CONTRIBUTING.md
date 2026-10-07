# Contributing

Thanks for considering contributing to `larasell-dev/cushion`!

## Development Setup

The package lives in two parts:

- **PHP package** (`larasell-dev/cushion`) — the Laravel draft engine, at the repository root
- **npm package** (`@larasell-dev/cushion`) — the React/Inertia hooks, in [`js/`](js/)

### Requirements

- PHP 8.3+
- Composer
- Node.js (for the JS package)

### PHP Package

```bash
composer install
vendor/bin/pest
```

Tests run on Orchestra Testbench, so no Laravel application is needed. The test suite boots a minimal app with the package's service provider, a test form, and an in-memory SQLite database.

### JS Package

```bash
cd js
npm install
npm run build       # compiles src/ to dist/ (also typechecks)
npm run typecheck   # tsc --noEmit only
```

## Testing Guidelines

- Tests use [Pest](https://pestphp.com/). Feature tests live in `tests/Feature/`, unit tests in `tests/Unit/`
- Every behavior change or bug fix should come with a test that would have failed before the change
- The test fixtures (`TestForm`, `TestUser` in `tests/TestCase.php`, migrations in `tests/migrations/`) are intentionally generic — extend them rather than introducing app-specific domain
- Run the narrowest set covering your change:

  ```bash
  vendor/bin/pest tests/Feature/FormDraftTest.php
  ```

## Pull Requests

1. Keep PRs focused — one concern per PR
2. Add or update tests for behavior changes
3. Run the checks below and make sure they pass:

   ```bash
   vendor/bin/pint
   vendor/bin/pest
   cd js && npm run build
   ```

4. Describe the user-visible or API-visible impact in the PR body
