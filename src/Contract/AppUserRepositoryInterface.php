<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Contract;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('azymuthia.security.app_user_repository')]
interface AppUserRepositoryInterface
{
    public function getOneByUserId(Uuid $id): AppUserInterface;
}
