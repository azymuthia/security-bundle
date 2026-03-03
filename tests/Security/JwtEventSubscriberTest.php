<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Tests\Security;

use Azymuthia\SecurityBundle\Contract\AppUserInterface;
use Azymuthia\SecurityBundle\Contract\AppUserRepositoryInterface;
use Azymuthia\SecurityBundle\Security\JwtEventSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @internal
 *
 * @coversNothing
 */
final class JwtEventSubscriberTest extends TestCase
{
    public function testOnJwtNotFoundRedirectsToLogin(): void
    {
        $subscriber = $this->subscriber();
        $event = new JWTNotFoundEvent();
        $subscriber->onJwtNotFound($event);
        self::assertNotNull($event->getResponse());
        self::assertSame('/login', $event->getResponse()->getTargetUrl());
    }

    public function testOnJwtInvalidRedirectsToLogout(): void
    {
        $subscriber = $this->subscriber();
        $event = new JWTInvalidEvent('bad');
        $subscriber->onJwtInvalid($event);
        self::assertNotNull($event->getResponse());
        self::assertSame('/logout', $event->getResponse()->getTargetUrl());
    }

    public function testOnJwtDecodedMissingUserIdLeavesPayloadUntouched(): void
    {
        $subscriber = $this->subscriber();
        $payload = ['roles' => ['ROLE_USER']];
        $event = new JWTDecodedEvent($payload);
        $subscriber->onJwtDecoded($event);
        self::assertSame($payload, $event->getPayload());
    }

    public function testOnJwtDecodedInvalidUuidLeavesPayloadUntouched(): void
    {
        $subscriber = $this->subscriber();
        $payload = ['userId' => 'not-a-uuid', 'roles' => ['ROLE_USER']];
        $event = new JWTDecodedEvent($payload);
        $subscriber->onJwtDecoded($event);
        self::assertSame($payload, $event->getPayload());
    }

    public function testOnJwtDecodedWithRepositoryDoesNotInjectEntity(): void
    {
        $repo = new class implements AppUserRepositoryInterface
        {
            public function getOneByUserId(Uuid $id): AppUserInterface
            {
                // This would normally return a domain entity, but subscriber must not inject it into payload.
                throw new class('Not found') extends RuntimeException {};
            }
        };

        $subscriber = $this->subscriber([$repo]);
        $payload = ['userId' => Uuid::v4()->toRfc4122(), 'roles' => ['ROLE_USER']];
        $event = new JWTDecodedEvent($payload);
        $subscriber->onJwtDecoded($event);
        $newPayload = $event->getPayload();
        self::assertArrayNotHasKey('appUser', $newPayload);
        self::assertSame($payload['userId'], $newPayload['userId']);
    }

    private function subscriber(iterable $repos = []): JwtEventSubscriber
    {
        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->method('generate')->willReturnCallback(static fn (string $route) => '/' . $route);

        return new JwtEventSubscriber($urls, $repos, new NullLogger());
    }
}
