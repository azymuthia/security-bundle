<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Security;

use Azymuthia\SecurityBundle\Contract\AppUserRepositoryInterface;
use Azymuthia\SecurityBundle\Event\UserIdDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JWTEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

#[AsEventListener(event: JWTEvents::JWT_DECODED, method: 'onJwtDecoded')]
#[AsEventListener(event: JWTEvents::JWT_INVALID, method: 'onJwtInvalid')]
#[AsEventListener(event: JWTEvents::JWT_NOT_FOUND, method: 'onJwtNotFound')]
final readonly class JwtEventSubscriber implements EventSubscriberInterface
{
    /** @param iterable<AppUserRepositoryInterface> $appUserRepositories */
    public function __construct(
        private UrlGeneratorInterface $urls,
        private EventDispatcherInterface $eventDispatcher,
        private iterable $appUserRepositories = [],
        private ?LoggerInterface $logger = null,
    ) {}

    public function onJwtNotFound(JWTNotFoundEvent $event): void
    {
        $event->setResponse(new RedirectResponse($this->urls->generate('login')));
    }

    public function onJwtInvalid(JWTInvalidEvent $event): void
    {
        $event->setResponse(new RedirectResponse($this->urls->generate('logout')));
    }

    public function onJwtDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        // Defensive: ensure userId exists and is a valid Uuid string/object
        $userIdRaw = $payload['userId'] ?? null;

        if (null === $userIdRaw) {
            // best-effort: leave the payload unchanged
            $this->logger?->debug('JWT payload missing userId; skipping appUser enrichment');

            return;
        }

        try {
            $userId = $userIdRaw instanceof Uuid ? $userIdRaw : Uuid::fromString((string) $userIdRaw);

            $this->eventDispatcher->dispatch(new UserIdDecodedEvent($userId, $payload['name'] ?? null));
        } catch (Throwable) {
            // invalid UUID – ignore
            $this->logger?->debug('JWT payload has invalid userId; skipping appUser enrichment', ['userId' => $userIdRaw]);

            return;
        }

        // Find the first available repository (if any)
        $repository = $this->firstRepositoryOrNull();

        if (null === $repository) {
            // No repositories registered – supported scenario (JWT-only mode)
            return;
        }

        $payload['appUser'] = null;
        try {
            // Attempt fetching domain user; do not put the entity into JWT payload.
            // Hydration of JWTUser will not receive the entity from payload in this mode.
            $payload['appUser'] = $repository->getOneByUserId($userId);
            $event->setPayload($payload);
        } catch (Throwable $e) {
            // Best-effort: ignore any repository exceptions (e.g. EntityNotFound/AppUserNotFound)
            $this->logger?->debug('AppUser repository lookup failed; continuing without appUser', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            JWTEvents::JWT_NOT_FOUND => 'onJwtNotFound',
            JWTEvents::JWT_INVALID => 'onJwtInvalid',
            JWTEvents::JWT_DECODED => 'onJwtDecoded',
        ];
    }

    private function firstRepositoryOrNull(): ?AppUserRepositoryInterface
    {
        foreach ($this->appUserRepositories as $repo) {
            if ($repo instanceof AppUserRepositoryInterface) {
                return $repo;
            }
        }

        return null;
    }
}
