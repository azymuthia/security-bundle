<?php

declare(strict_types=1);

namespace Aquila\SecurityBundle\Security;

use Symfony\Component\Uid\Uuid;
use Aquila\SecurityBundle\Contract\AppUserInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;

/**
 * Domain-agnostic JWT user representation for LexikJWT provider.
 *
 * Works in two modes:
 * - JWT-only: when only userId and roles are provided in payload, appUser remains null.
 * - With domain user attached: when payload is enriched by our subscriber with an AppUserInterface instance.
 */
final class JWTUser implements JWTUserInterface
{
    private readonly string $userIdentifier;

    private readonly Uuid $userId;

    /** @var string[] */
    private readonly array $roles;

    private readonly ?AppUserInterface $appUser;

    /**
     * Use factory createFromPayload.
     *
     * @param string[] $roles
     */
    private function __construct(string $userIdentifier, Uuid $userId, array $roles, ?AppUserInterface $appUser)
    {
        $this->userIdentifier = $userIdentifier;
        $this->userId = $userId;
        $this->roles = $roles;
        $this->appUser = $appUser;
    }

    /**
     * Factory used by Lexik to re-create user from token payload.
     *
     * Expected payload keys:
     * - 'userId' (string|Uuid) required
     * - 'roles' (string[]) required
     * - 'appUser' (AppUserInterface) optional, set by our subscriber if available
     *
     * @param array<string,mixed> $payload
     * @param mixed               $username
     */
    public static function createFromPayload($username, array $payload): self
    {
        if (!\array_key_exists('userId', $payload)) {
            throw new \InvalidArgumentException('JWT payload is missing required key: userId');
        }
        if (!\array_key_exists('roles', $payload) || !\is_array($payload['roles'])) {
            throw new \InvalidArgumentException('JWT payload is missing required key: roles');
        }

        $userIdRaw = $payload['userId'];
        $userId = $userIdRaw instanceof Uuid ? $userIdRaw : Uuid::fromString((string) $userIdRaw);

        // Normalize roles to string[]
        $roles = array_values(array_map(static fn ($r) => (string) $r, $payload['roles']));

        $appUser = null;
        if (isset($payload['appUser']) && $payload['appUser'] instanceof AppUserInterface) {
            $appUser = $payload['appUser'];
        }

        return new self($username, $userId, $roles, $appUser);
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getAppUser(): ?AppUserInterface
    {
        return $this->appUser;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // Intentionally left blank: no sensitive data stored on this user
    }
}
