# Aquila Security Bundle

Domain-agnostic JWT integration for Symfony 7 with optional wiring of an application user.

## Requirements
- PHP >= 8.4
- Symfony 7.3+
- lexik/jwt-authentication-bundle ^2.20

## Installation
Add to your composer.json in the client app (once the package is published):

```
"aquila/security-bundle": "^1.0"
```

Register the bundle (if Flex doesn’t do it automatically):

```php
// config/bundles.php
return [
    // ...
    Aquila\SecurityBundle\AquilaSecurityBundle::class => ['all' => true],
];
```

## Integration (security.yaml)
Set Lexik JWT provider user class to the bundle’s JWTUser and keep the main firewall using jwt: ~.

Example:

```yaml
# config/packages/security.yaml
security:
  providers:
    jwt:
      lexik_jwt:
        class: Aquila\SecurityBundle\Security\JWTUser

  firewalls:
    main:
      pattern: ^/
      stateless: true
      jwt: ~   # provided by lexik/jwt-authentication-bundle
```

Notes:
- Do NOT add manual service tags for your app user repositories. Autoconfiguration will automatically tag any implementation of Aquila\SecurityBundle\Contract\AppUserRepositoryInterface with `aquila.security.app_user_repository`.
- If your application provides a repository implementing the interface above, the bundle will best‑effort attach `appUser` to the JWT payload during JWT decoding. If you don’t provide any repository, the bundle works in JWT‑only mode (no `appUser` attached).

## Optional: App user repository
Implement the interface in your app to resolve a domain user by UUID:

```php
<?php

declare(strict_types=1);

namespace App\Security\Repository;

use Symfony\Component\Uid\Uuid;
use Aquila\SecurityBundle\Contract\AppUserInterface;
use Aquila\SecurityBundle\Contract\AppUserRepositoryInterface;

final readonly class YourAppUserRepository implements AppUserRepositoryInterface
{
    public function getOneByUserId(Uuid $id): AppUserInterface
    {
        // return your domain user implementing AppUserInterface
    }
}
```

Autoconfiguration will take care of tagging; no YAML/XML is needed.

## JWT-only mode
If you don’t implement AppUserRepositoryInterface, the bundle still works:
- JwtEventSubscriber won’t attach any domain user to the payload;
- Aquila\SecurityBundle\Security\JWTUser will have `appUser = null`.

## Snippet: PlayerAppUserRepository adapter (copy-paste)
Poniższy przykład pokazuje, jak w aplikacji klienckiej dodać adapter repozytorium dla encji Player. Adapter implementuje
Aquila\SecurityBundle\Contract\AppUserRepositoryInterface i deleguje do App\Infrastructure\Doctrine\Repository\PlayerRepository.
Autokonfiguracja automatycznie doda wymagany tag – nie dodawaj żadnych ręcznych tagów.

```php
<?php

declare(strict_types=1);

namespace App\Security\Repository;

use Symfony\Component\Uid\Uuid;
use Aquila\SecurityBundle\Contract\AppUserInterface;
use Aquila\SecurityBundle\Contract\AppUserRepositoryInterface;
use App\Infrastructure\Doctrine\Repository\PlayerRepository;

final readonly class PlayerAppUserRepository implements AppUserRepositoryInterface
{
    public function __construct(private PlayerRepository $players) {}

    public function getOneByUserId(Uuid $id): AppUserInterface
    {
        // Encja Player musi implementować AppUserInterface
        return $this->players->getOneByUserId($id);
    }
}
```

### Player implements AppUserInterface
Upewnij się, że encja Player implementuje Aquila\SecurityBundle\Contract\AppUserInterface. Zazwyczaj wymaga to:
- dodania `implements AppUserInterface` do klasy encji,
- zapewnienia metody `getUserId(): Uuid` (zwykle już istnieje),
- dodania metody `getRoles(): array` (może zwracać pustą tablicę, jeśli role są tylko w JWT),
- metoda `__toString(): string` może zwracać np. nazwę gracza.

Przykład minimalny w encji Player:

```php
use Aquila\SecurityBundle\Contract\AppUserInterface;
use Symfony\Component\Uid\Uuid;

class Player implements \Stringable, AppUserInterface
{
    // ... istniejące pola i metody ...

    public function getUserId(): Uuid { /* ... */ }

    /** @return string[] */
    public function getRoles(): array
    {
        return [];
    }

    public function __toString(): string
    {
        return $this->name; // lub inny czytelny identyfikator
    }
}
```

To wszystko – dzięki autokonfiguracji Symfony 7.3+ każda implementacja AppUserRepositoryInterface zostanie
automatycznie oznaczona tagiem `aquila.security.app_user_repository`, więc nie jest potrzebna żadna dodatkowa konfiguracja.

## Copy-paste: security.yaml change
W aplikacji-kliencie podmień konfigurację providera JWT tak, aby używała klasy z bundle:

```yaml
security:
  providers:
    jwt:
      lexik_jwt:
        class: Aquila\SecurityBundle\Security\JWTUser
```

Przypomnienie: usuń lub wyłącz stary aplikacyjny JwtSubscriber (np. App\Application\EventSubscriber\JwtSubscriber). Bundle rejestruje własny JwtEventSubscriber automatycznie.

## License
Proprietary. See the root project license terms.

## Release and installation from a private GitHub repository

Minimum versions (recap):
- PHP: >= 8.4
- Symfony: 7.3+
- lexik/jwt-authentication-bundle: ^2.20

### 1) Prepare a private GitHub repository
- Create a new private repo, e.g. github.com/your-org/aquila-security-bundle.
- Initialize it with this package’s code (packages/security-bundle).
- Set remote and push:

```bash
git remote add origin git@github.com:your-org/aquila-security-bundle.git
git push -u origin main
```

- Tag a release using semantic versioning (the client apps will require ^1.0):

```bash
git tag v1.0.0
git push origin v1.0.0
```

### 2) Install in client applications via Composer
Two common options for private GitHub:

A) Use Composer’s GitHub OAuth token (preferred when using Packagist/Private Packagist or GitHub registry).
- Configure token locally or in CI:

```bash
composer config -g github-oauth.github.com YOUR_GH_TOKEN
```

- Then require the package normally in the app:

```bash
composer require aquila/security-bundle:"^1.0"
```

B) Use a direct VCS repository entry (no Packagist required):
- In the client app’s composer.json add:

```json
{
  "repositories": [
    { "type": "vcs", "url": "git@github.com:your-org/aquila-security-bundle.git" }
  ],
  "require": {
    "aquila/security-bundle": "^1.0"
  }
}
```

- Then run:

```bash
composer update aquila/security-bundle
```

### 3) Example GitHub Actions CI for the bundle (phpstan, php-cs-fixer, phpunit)
Create .github/workflows/ci.yml in the bundle repository:

```yaml
name: CI

on:
  push:
    branches: [ main ]
    tags: [ 'v*.*.*' ]
  pull_request:

jobs:
  tests:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [ '8.4' ]
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mbstring, intl
          tools: composer:v2
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-interaction
      - name: PHPStan
        run: |
          if [ -f vendor/bin/phpstan ]; then vendor/bin/phpstan analyse --no-progress; else echo "phpstan not configured"; fi
      - name: PHP-CS-Fixer (dry-run)
        run: |
          if [ -f vendor/bin/php-cs-fixer ]; then vendor/bin/php-cs-fixer fix --dry-run --diff; else echo "php-cs-fixer not configured"; fi
      - name: PHPUnit
        run: |
          if [ -f vendor/bin/phpunit ]; then vendor/bin/phpunit; else echo "phpunit not configured"; fi
```

Notes:
- The bundle already includes phpunit.xml.dist and tests under tests/.
- For PHPStan and PHP-CS-Fixer, include your configs in the bundle repo (e.g., phpstan.neon, .php-cs-fixer.php) or rely on project-level configs.
- You can extend the workflow with caching (actions/cache) for faster composer installs.


## Local development from subfolder (Composer path repository)
If you keep this bundle inside a monorepo (e.g. under packages/security-bundle) and want to use it directly
without publishing to a separate repository, point Composer to the subfolder using a path repository.

Add to your application’s composer.json:

```json
{
  "repositories": [
    { "type": "path", "url": "packages/security-bundle", "options": { "symlink": true } }
  ],
  "require": {
    "aquila/security-bundle": "*@dev"
  }
}
```

Then install/update only the bundle:

```bash
composer update aquila/security-bundle
```

Notes:
- options.symlink=true keeps a live symlink to the subfolder (great for local development). Set to false to copy files.
- The version is taken from the package itself; using "*@dev" is convenient during development. For stricter constraints,
  you may use dev-main or a branch alias if defined.
- This works both in this monorepo and other apps that vendor this repository as a subtree.
