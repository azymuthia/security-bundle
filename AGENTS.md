# AGENTS.md

## Project Overview

- This repository is the `azymuthia/security-bundle` Symfony bundle. Its evidence-backed purpose is domain-agnostic JWT integration for Symfony, using `lexik/jwt-authentication-bundle`, with optional application-user repository wiring.
- Package type is `symfony-bundle`; Composer autoloads `Azymuthia\SecurityBundle\` from `src/`.
- Important directories:
  - `src/Contract/`: public contracts for application users and repositories.
  - `src/Security/`: Lexik JWT user implementation and JWT event subscriber.
  - `src/DependencyInjection/`: bundle extension, configuration tree, and compiler pass.
  - `src/Event/`: bundle events.
  - `config/`: PHP service definitions loaded by the bundle extension.
  - `tests/`: PHPUnit-style unit tests for security classes.

## Development Commands

- Install dependencies:
  - `composer install`
  - Or with the checked-in Docker service: `docker-compose run -T --rm app composer install`
- Run tests:
  - `vendor/bin/phpunit`
  - This repo has `phpunit.xml.dist` and tests, but `phpunit/phpunit` is not currently declared in `composer.json`; use this when PHPUnit is provided by the local/root project or added intentionally.
- Run coding standards check:
  - `vendor/bin/php-cs-fixer fix --dry-run --diff`
- Apply coding standards:
  - `vendor/bin/php-cs-fixer fix`
- Normalize Composer metadata when Composer is available:
  - `composer normalize --dry-run`
  - `composer normalize`
- Static analysis:
  - No PHPStan dependency or `phpstan.neon` is present. The README only shows an example CI step that conditionally runs `vendor/bin/phpstan analyse --no-progress` if PHPStan is installed.

## Architecture Notes

- `AzymuthiaSecurityBundle` is the bundle entry point. It registers `AppUserAutowirePass` and returns `AzymuthiaSecurityExtension` from `getContainerExtension()`.
- `AzymuthiaSecurityExtension` loads `config/services.php` with `PhpFileLoader` and uses the DI alias `azymuthia_security`.
- `Configuration` defines an empty/minimal `azymuthia_security` config tree. Do not add config options without tests and docs.
- `config/services.php` enables autowiring/autoconfiguration/private services and registers `JwtEventSubscriber`.
- `JwtEventSubscriber` subscribes to Lexik JWT events:
  - missing JWT redirects to the `login` route;
  - invalid JWT redirects to the `logout` route;
  - decoded JWT validates `userId`, dispatches `UserIdDecodedEvent`, and best-effort enriches the payload from the first tagged `AppUserRepositoryInterface`.
- `JWTUser` is the Lexik `JWTUserInterface` implementation. It requires `userId` and `roles` in JWT payloads, normalizes roles to strings, and may carry an optional `AppUserInterface`.
- `AppUserRepositoryInterface` is tagged via `#[AsTaggedItem('azymuthia.security.app_user_repository')]`; `JwtEventSubscriber` also consumes that tag with `#[AutowireIterator(...)]`.
- `AppUserInterface` and `AppUserRepositoryInterface` appear to be public extension points. Treat classes under `src/Contract/` and `src/Security/JWTUser.php` as public API unless explicitly asked otherwise.
- `AppUserAutowirePass` currently has no behavior. Do not assume it wires repositories beyond the attribute/tag mechanism unless implementing and testing that behavior.

## Testing Guidance

- Tests live under `tests/`, mirroring source areas such as `tests/Security/`.
- Existing tests are PHPUnit `TestCase` classes with `declare(strict_types=1)`, final test classes, `@internal`, and `@coversNothing`.
- Add focused unit tests near the affected class. Current tests instantiate services directly with PHPUnit mocks rather than booting a Symfony kernel.
- For JWT behavior, use Lexik event objects such as `JWTDecodedEvent`, `JWTInvalidEvent`, and `JWTNotFoundEvent`.
- There is no checked-in test app, fixture kernel, or demo application. The README contains client-app integration examples only.

## Coding Conventions

- PHP code uses `declare(strict_types=1)`, typed properties/returns, `final` classes where appropriate, constructor property promotion, and `readonly` where useful.
- Formatting is governed by `.php-cs-fixer.php`: PER-CS, Symfony, PHP 8.5 migration, PhpCsFixer rule sets, risky rules enabled, short arrays, ordered imports, strict comparisons, strict params, and arrow/static closures where applicable.
- Prefer explicit exception behavior for invalid public input, as in `JWTUser::createFromPayload()` throwing `InvalidArgumentException` for missing required payload keys.
- JWT enrichment is intentionally best-effort: malformed/missing `userId` and repository lookup failures are logged at debug level and otherwise leave authentication flow intact.
- Keep namespace and class names aligned with `Azymuthia\SecurityBundle\...`; test namespaces use `Azymuthia\SecurityBundle\Tests\...`.
- Preserve backward compatibility for public contracts, payload expectations, service tags, and Symfony integration points unless the task explicitly requires a breaking change.

## Change Guidelines For Agents

- Keep changes minimal and focused on the requested behavior.
- Do not introduce new dependencies unless clearly justified by the change and compatible with this package's version constraints.
- Preserve public APIs in `src/Contract/` and documented integration behavior unless explicitly asked to change them.
- Update tests when behavior changes; update README/docs when integration behavior, commands, or configuration changes.
- Keep compatibility with `composer.json`: PHP `>=8.4`, Symfony FrameworkBundle/SecurityBundle/Uid `^7.4|^8.0`, and Lexik JWT Authentication Bundle `^3.0`.
- Avoid relying on app-specific Doctrine entities or repositories in bundle code; repository integration is through `AppUserRepositoryInterface`.

## Repository-Specific Gotchas

- There are no Composer scripts in `composer.json`.
- There is no checked-in `.github/workflows` CI configuration. The README includes an example CI workflow only.
- `.php-cs-fixer.php` exists but is ignored by `.gitignore`; `.php-cs-fixer.php.dist` is also present. Be careful before changing local-only fixer configuration.
- `.php_cs.cache`, `vendor/`, and `.idea/` are ignored/local artifacts.
- `docker-compose.yaml` defines a single `app` service using `caelumek/frankenphp:8.5-dev` and mounting the repository at `/app`.
- `composer.json` declares `lexik/jwt-authentication-bundle` `^3.0`, while the README still mentions older Symfony/Lexik minimums in some sections. Prefer `composer.json` for supported versions unless updating the documentation.
- The README says the subscriber should not put a domain entity into the JWT payload in one test comment, while current `JwtEventSubscriber` code attempts to set `appUser` when repository lookup succeeds. Check current tests and requested behavior carefully before changing this area.
