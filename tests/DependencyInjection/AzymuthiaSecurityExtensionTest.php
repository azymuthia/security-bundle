<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Tests\DependencyInjection;

use Azymuthia\SecurityBundle\DependencyInjection\AzymuthiaSecurityExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 *
 * @coversNothing
 */
final class AzymuthiaSecurityExtensionTest extends TestCase
{
    private const string ENV_VAR = 'BEARER_COOKIE_NAME';

    private ?string $originalEnvValue = null;

    protected function setUp(): void
    {
        $this->originalEnvValue = isset($_ENV[self::ENV_VAR]) ? (string) $_ENV[self::ENV_VAR] : null;
        unset($_ENV[self::ENV_VAR]);
    }

    protected function tearDown(): void
    {
        if (null === $this->originalEnvValue) {
            unset($_ENV[self::ENV_VAR]);

            return;
        }

        $_ENV[self::ENV_VAR] = $this->originalEnvValue;
    }

    public function testBearerCookieNameFallsBackToTheProductionNameWhenTheEnvVarIsUnset(): void
    {
        self::assertSame('BEARER', $this->resolveBearerCookieName());
    }

    public function testBearerCookieNameFollowsTheEnvVarWhenItIsSet(): void
    {
        $_ENV[self::ENV_VAR] = 'BEARER_STAGING';

        self::assertSame('BEARER_STAGING', $this->resolveBearerCookieName());
    }

    public function testExitUrlParameterIsStillRegistered(): void
    {
        $container = $this->load();

        self::assertSame('%env(SECURITY_EXIT_URL)%', $container->getParameter('azymuthia_security.exit_url'));
    }

    private function resolveBearerCookieName(): string
    {
        $container = $this->load();

        return (string) $container->resolveEnvPlaceholders(
            $container->getParameter('azymuthia_security.bearer_cookie_name'),
            true,
        );
    }

    private function load(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        new AzymuthiaSecurityExtension()->load([[]], $container);

        return $container;
    }
}
