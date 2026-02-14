# AGENTS.md

## Project
This is the official Mailrify PHP SDK. See `docs/` for the full specification.

## Context Files (read these FIRST)
1. [docs/sdk-plan.md] — Shared API spec, endpoints, auth, errors, testing strategy
2. [docs/sdk-plan-php.md] — PHP-specific implementation plan (structure, models, workflows)
3. [docs/openapi.json] — OpenAPI 3.0.3 specification (source of truth for schemas)

## Build Order
1. Scaffold: `composer.json`, PSR-4 dirs, `.gitignore`, `phpunit.xml`
2. Error classes (`src/Exceptions/`)
3. HttpClient (`src/HttpClient.php`) — Guzzle, auth, retry, error parsing
4. Client (`src/Mailrify.php`) — entry point with resource namespaces
5. Resources one at a time with tests: Emails → Events → Contacts → Campaigns → Segments
6. CI: .github/workflows/ci.yml, release-please.yml
7. README with install + usage examples
8. Run `./vendor/bin/phpunit` — all tests must pass

## Standards
- PHP 8.1+ with strict types
- PSR-4 autoloading, PSR-12 code style
- PHPUnit 10+ for testing
- Conventional Commits for all commit messages
