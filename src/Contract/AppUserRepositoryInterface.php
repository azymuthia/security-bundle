<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Contract;

use Symfony\Component\Uid\Uuid;

interface AppUserRepositoryInterface
{
    public function getOneByUserId(Uuid $id): AppUserInterface;
}
