<?php

declare(strict_types=1);

namespace Aquila\SecurityBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Aquila\SecurityBundle\Security\JWTUser;

/**
 * @internal
 *
 * @coversNothing
 */
final class JWTUserTest extends TestCase
{
    public function testCreateFromPayloadWithoutAppUser(): void
    {
        $uuid = Uuid::v4();
        $user = JWTUser::createFromPayload('john', [
            'userId' => $uuid->toRfc4122(),
            'roles' => ['ROLE_USER', 'ROLE_EXTRALIGA'],
        ]);

        self::assertSame('john', $user->getUserIdentifier());
        self::assertTrue($uuid->equals($user->getUserId()));
        self::assertSame(['ROLE_USER', 'ROLE_EXTRALIGA'], $user->getRoles());
        self::assertNull($user->getAppUser());
    }

    public function testCreateFromPayloadInvalidWithoutRolesThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        JWTUser::createFromPayload('john', [
            'userId' => Uuid::v4()->toRfc4122(),
        ]);
    }

    public function testCreateFromPayloadInvalidWithoutUserIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        JWTUser::createFromPayload('john', [
            'roles' => ['ROLE_USER'],
        ]);
    }
}
