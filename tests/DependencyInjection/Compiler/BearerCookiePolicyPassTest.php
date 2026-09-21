<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Tests\DependencyInjection\Compiler;

use Azymuthia\SecurityBundle\DependencyInjection\AzymuthiaSecurityExtension;
use Azymuthia\SecurityBundle\DependencyInjection\Compiler\BearerCookiePolicyPass;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 *
 * @coversNothing
 */
final class BearerCookiePolicyPassTest extends TestCase
{
    private const string COOKIE_NAME_VAR = 'BEARER_COOKIE_NAME';
    private const string DEPLOYMENT_VAR = 'APP_DEPLOYMENT_ENV';

    /** @var array<string, ?string> */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        foreach ([self::COOKIE_NAME_VAR, self::DEPLOYMENT_VAR] as $name) {
            $this->originalEnv[$name] = isset($_ENV[$name]) ? (string) $_ENV[$name] : null;
            unset($_ENV[$name]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $name => $value) {
            if (null === $value) {
                unset($_ENV[$name]);

                continue;
            }

            $_ENV[$name] = $value;
        }
    }

    public function testAllowsAnApplicationThatOnlyReadsTheCookie(): void
    {
        $this->expectNotToPerformAssertions();

        $this->process($this->container());
    }

    public function testRejectsAnApplicationThatIssuesBearerCookies(): void
    {
        $container = $this->container();
        $container->setDefinition('lexik_jwt_authentication.cookie_provider.BEARER', new Definition());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/must not issue bearer cookies/');

        $this->process($container);
    }

    public function testRejectsANonProductionDeploymentKeepingTheProductionCookieName(): void
    {
        $_ENV[self::DEPLOYMENT_VAR] = 'staging';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/must not keep the production BEARER_COOKIE_NAME/');

        $this->process($this->container());
    }

    public function testAllowsANonProductionDeploymentWithItsOwnCookieName(): void
    {
        $_ENV[self::DEPLOYMENT_VAR] = 'staging';
        $_ENV[self::COOKIE_NAME_VAR] = 'BEARER_STAGING';

        $this->expectNotToPerformAssertions();

        $this->process($this->container());
    }

    public function testAllowsProductionToKeepTheProductionCookieName(): void
    {
        $_ENV[self::DEPLOYMENT_VAR] = 'prod';

        $this->expectNotToPerformAssertions();

        $this->process($this->container());
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        new AzymuthiaSecurityExtension()->load([[]], $container);

        return $container;
    }

    private function process(ContainerBuilder $container): void
    {
        new BearerCookiePolicyPass()->process($container);
    }
}
