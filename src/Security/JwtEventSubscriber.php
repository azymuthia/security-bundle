<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JWTEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Azymuthia\SecurityBundle\Contract\AppUserRepositoryInterface;

#[AsEventListener(event: Events::JWT_DECODED, method: 'onJwtDecoded')]
#[AsEventListener(event: Events::JWT_INVALID, method: 'onJwtInvalid')]
#[AsEventListener(event: Events::JWT_NOT_FOUND, method: 'onJwtNotFound')]
final readonly class JwtEventSubscriber implements EventSubscriberInterface
{
    /** @param iterable<AppUserRepositoryInterface> $appUserRepositories */
    public function __construct(
        private UrlGeneratorInterface $urls,
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
            // best-effort: leave payload unchanged
            $this->logger?->debug('JWT payload missing userId; skipping appUser enrichment');

            return;
        }

        try {
            $userId = $userIdRaw instanceof Uuid ? $userIdRaw : Uuid::fromString((string) $userIdRaw);
        } catch (\Throwable) {
            // invalid UUID – ignore
            $this->logger?->debug('JWT payload has invalid userId; skipping appUser enrichment', ['userId' => $userIdRaw]);

            return;
        }

        // Find first available repository (if any)
        $repository = $this->firstRepositoryOrNull();
        if (null === $repository) {
            // No repositories registered – supported scenario (JWT-only mode)
            return;
        }

        try {
            // Attempt fetching domain user; do not put the entity into JWT payload.
            // Hydration of JWTUser will not receive the entity from payload in this mode.
            $payload['appUser'] = $repository->getOneByUserId($userId);
            $event->setPayload($payload);
        } catch (\Throwable $e) {
            // Best-effort: ignore any repository exceptions (e.g., EntityNotFound/AppUserNotFound)
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
