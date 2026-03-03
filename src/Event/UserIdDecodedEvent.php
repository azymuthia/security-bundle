<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Event;

use Symfony\Component\Uid\Uuid;

final class UserIdDecodedEvent
{
    public function __construct(
        public readonly Uuid $userId,
        public readonly ?string $name,
    ) {}
}
