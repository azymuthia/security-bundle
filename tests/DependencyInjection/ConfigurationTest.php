<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\Tests\DependencyInjection;

use Azymuthia\SecurityBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * @internal
 *
 * @coversNothing
 */
final class ConfigurationTest extends TestCase
{
    public function testExitUrlDefaultsToSecurityExitUrlEnvVar(): void
    {
        $config = $this->process([]);

        self::assertSame('%env(SECURITY_EXIT_URL)%', $config['exit_url']);
    }

    public function testExitUrlCanBeOverridden(): void
    {
        $config = $this->process(['exit_url' => 'https://security.example.test/exit']);

        self::assertSame('https://security.example.test/exit', $config['exit_url']);
    }

    /** @param array<string, mixed> $config */
    private function process(array $config): array
    {
        return new Processor()->processConfiguration(new Configuration(), [$config]);
    }
}
