<?php

declare(strict_types=1);

namespace Aquila\SecurityBundle\Contract;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('aquila.security.app_user_repository')]
interface AppUserRepositoryInterface
{
    public function getOneByUserId(Uuid $id): AppUserInterface;
}
