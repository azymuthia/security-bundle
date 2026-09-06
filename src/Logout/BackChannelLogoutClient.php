<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Logout;

use Azymuthia\CrossAppBundle\Http\CrossAppClient;
use Psr\Log\LoggerInterface;
use Throwable;

use function is_string;

/**
 * Calls Security's back-channel logout endpoint (SEC-2) via GEN-16's cross-app client and returns
 * the login redirect target. Never throws into the consumer app's request cycle: any transport,
 * HTTP, or malformed-response failure is logged and results in a null return, leaving the fallback
 * (e.g. rendering a local login page) to the caller.
 */
final readonly class BackChannelLogoutClient
{
    private const string HOST_CONNECTION_NAME = 'security';

    public function __construct(
        private CrossAppClient $crossAppClient,
        private string $logoutEndpoint,
        private ?LoggerInterface $logger = null,
    ) {}

    public function requestLogout(): ?string
    {
        try {
            $response = $this->crossAppClient->request(self::HOST_CONNECTION_NAME, 'POST', $this->logoutEndpoint);
            $data = $response->toArray();
        } catch (Throwable $e) {
            $this->logger?->warning('Back-channel logout call to Security failed.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $redirectTo = $data['redirectTo'] ?? null;

        if (!is_string($redirectTo) || '' === $redirectTo) {
            $this->logger?->warning('Back-channel logout response from Security was malformed.', ['response' => $data]);

            return null;
        }

        return $redirectTo;
    }
}
