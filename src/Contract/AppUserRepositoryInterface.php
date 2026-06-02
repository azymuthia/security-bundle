<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Contract;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Uid\Uuid;

#[AsTaggedItem('azymuthia.security.app_user_repository')]
interface AppUserRepositoryInterface
{
    public function getOneByUserId(Uuid $id): AppUserInterface;
}
