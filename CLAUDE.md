# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`azymuthia/security-bundle` is a domain-agnostic Symfony bundle providing JWT integration on top of
`lexik/jwt-authentication-bundle`, with optional wiring to an application-specific user repository. It ships
as a `symfony-bundle` Composer package autoloaded as `Azymuthia\SecurityBundle\` from `src/`.

## Commands

- Install dependencies: `composer install` (or `docker-compose run -T --rm app composer install`)
- Run tests: `vendor/bin/phpunit` — note `phpunit/phpunit` is **not** declared in `composer.json` or vendored;
  this only works when PHPUnit is supplied by the consuming project/root, or added intentionally.
- Run a single test: `vendor/bin/phpunit --filter testOnJwtDecodedMissingUserIdLeavesPayloadUntouched tests/Security/JwtEventSubscriberTest.php`
- Check coding style: `vendor/bin/php-cs-fixer fix --dry-run --diff`
- Apply coding style: `vendor/bin/php-cs-fixer fix`
- Normalize `composer.json`: `composer normalize --dry-run` / `composer normalize`
- No PHPStan config is present in this repo (README shows only an example CI step that runs it conditionally).
- No Composer scripts and no checked-in `.github/workflows` — CI is documented only as an example in `README.md`.

## Architecture

Request flow: Lexik's JWT authenticator fires events → `JwtEventSubscriber` (`src/Security/JwtEventSubscriber.php`)
handles them → `JWTUser::createFromPayload()` (`src/Security/JWTUser.php`) rebuilds the security user from the
(possibly enriched) payload.

- **Bundle wiring** (`src/AzymuthiaSecurityBundle.php`): in `build()`, registers autoconfiguration so any
  `AppUserRepositoryInterface` implementation found in the *consuming app's* container is auto-tagged
  `azymuthia.security.app_user_repository` — no manual service tags or attributes needed on the app side. It also
  registers `AppUserAutowirePass` (`src/DependencyInjection/Compiler/AppUserAutowirePass.php`), which currently has
  an empty `process()` body — don't assume it does anything beyond the autoconfiguration tag.
- **Extension/config**: `AzymuthiaSecurityExtension` (alias `azymuthia_security`) loads `config/services.php`,
  which enables autowire/autoconfigure/private-by-default and registers `JwtEventSubscriber`. `Configuration`
  defines an intentionally empty config tree — don't add options without tests and docs.
- **`JwtEventSubscriber`** (`final readonly`, `src/Security/JwtEventSubscriber.php`) subscribes to three Lexik
  events:
  - `JWT_NOT_FOUND` → redirects to the `login` route.
  - `JWT_INVALID` → redirects to the `logout` route.
  - `JWT_DECODED` → validates `payload['userId']` as a `Uuid`, dispatches `UserIdDecodedEvent`, then best-effort
    looks up the first repository from the `#[AutowireIterator('azymuthia.security.app_user_repository')]`
    collection and sets `payload['appUser']`. Every failure path (missing/invalid `userId`, no repository
    registered, repository throws) is swallowed and logged at debug level — JWT auth must keep working even
    with zero or misbehaving app-user repositories ("JWT-only mode").
- **`JWTUser`** (`src/Security/JWTUser.php`) is the Lexik `JWTUserInterface` implementation. Its private
  constructor forces use of the static `createFromPayload()` factory, which throws `InvalidArgumentException`
  if `userId` or `roles` are missing from the payload. It normalizes roles to `string[]` and only accepts an
  `appUser` from the payload if it's an `AppUserInterface` instance; otherwise `appUser` stays `null`.
- **Public extension points** (`src/Contract/`): `AppUserInterface` (a minimal, Doctrine-free contract:
  `__toString()`, `getUserId(): Uuid`, `getRoles(): array`) and `AppUserRepositoryInterface`
  (`getOneByUserId(Uuid $id): AppUserInterface`). Client apps implement these to opt into `appUser` enrichment;
  the bundle never depends on app-specific Doctrine entities directly.
- **`UserIdDecodedEvent`** (`src/Event/`) is dispatched on every successful JWT decode with a valid `userId`,
  independent of whether an app-user repository exists — useful as an app-side hook that doesn't require
  implementing the repository contract.

## Conventions

- `declare(strict_types=1)`, typed properties/returns, `final` classes, constructor promotion, `readonly` where
  useful. Formatting/rule sets are governed by `.php-cs-fixer.php` (PER-CS, Symfony, PHP 8.5 migration,
  PhpCsFixer sets, risky rules enabled). `.php-cs-fixer.php` is gitignored local config; `.php-cs-fixer.php.dist`
  is the tracked template — be careful not to conflate the two when changing fixer rules.
- Tests live under `tests/`, mirroring `src/` (e.g. `tests/Security/`). They are plain PHPUnit `TestCase`
  classes (no Symfony kernel boot), `final`, tagged `@internal` / `@coversNothing`, and construct services
  directly with PHPUnit mocks. For JWT behavior, use Lexik's own event classes (`JWTDecodedEvent`,
  `JWTInvalidEvent`, `JWTNotFoundEvent`) rather than mocking them.
- Preserve backward compatibility for `src/Contract/*`, JWT payload expectations (`userId`, `roles`, optional
  `appUser`), the `azymuthia.security.app_user_repository` tag, and Symfony integration points, unless a task
  explicitly calls for a breaking change.
- Keep compatibility with `composer.json` constraints: PHP `>=8.4`; Symfony `framework-bundle`/`security-bundle`/
  `uid` `^7.4|^8.0`; `lexik/jwt-authentication-bundle` `^3.0`. Note `README.md` still references older
  Symfony 7.3+ / Lexik `^2.20` minimums in places — prefer `composer.json` as the source of truth unless the
  task is to update the docs.
- Don't introduce new runtime dependencies without clear justification and compatibility with the above
  constraints.
