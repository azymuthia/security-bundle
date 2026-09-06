<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Tests\Logout;

use Azymuthia\CrossAppBundle\Http\CrossAppClient;
use Azymuthia\SecurityBundle\Logout\BackChannelLogoutClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @internal
 *
 * @coversNothing
 */
final class BackChannelLogoutClientTest extends TestCase
{
    private const array HOST_CONNECTIONS = [
        'security' => ['base_uri' => 'https://security.internal', 'uuid' => 'ekstraliga-uuid', 'secret' => 'ekstraliga-secret'],
    ];

    public function testRequestLogoutReturnsRedirectUrlOnSuccess(): void
    {
        $client = $this->client(new MockResponse('{"redirectTo": "https://security.internal/login"}'));

        self::assertSame('https://security.internal/login', $client->requestLogout());
    }

    public function testRequestLogoutReturnsNullWhenSecurityIsUnreachable(): void
    {
        $httpClient = new MockHttpClient(static fn (): MockResponse => throw new TransportException('connection refused'));
        $client = new BackChannelLogoutClient(new CrossAppClient($httpClient, self::HOST_CONNECTIONS), '/api/cross-app/logout', new NullLogger());

        self::assertNull($client->requestLogout());
    }

    public function testRequestLogoutReturnsNullOnErrorResponse(): void
    {
        $client = $this->client(new MockResponse('{"error": "Invalid cross-app credentials."}', ['http_code' => 401]));

        self::assertNull($client->requestLogout());
    }

    public function testRequestLogoutReturnsNullOnMalformedResponse(): void
    {
        $client = $this->client(new MockResponse('{"unexpected": "shape"}'));

        self::assertNull($client->requestLogout());
    }

    private function client(MockResponse $response): BackChannelLogoutClient
    {
        $httpClient = new MockHttpClient($response);

        return new BackChannelLogoutClient(new CrossAppClient($httpClient, self::HOST_CONNECTIONS), '/api/cross-app/logout', new NullLogger());
    }
}
