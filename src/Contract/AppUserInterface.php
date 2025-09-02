<?php

declare(strict_types=1);

namespace Aquila\SecurityBundle\Contract;

use Symfony\Component\Uid\Uuid;

/**
 * Minimal contract representing an application user recognized by the security bundle.
 * No dependency on Doctrine or specific domain models.
 */
interface AppUserInterface
{
    public function __toString(): string;

    /**
     * Returns stable user identifier (UUID) used across services.
     */
    public function getUserId(): Uuid;

    /**
     * Returns security roles for the user. May be an empty array if roles are provided solely by JWT.
     *
     * @return string[]
     */
    public function getRoles(): array;
}
