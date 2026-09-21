<?php

declare(strict_types=1);

namespace Azymuthia\SecurityBundle\DependencyInjection\Compiler;

use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function sprintf;
use function strlen;

/**
 * Enforces the two rules that keep Aquila deployments from colliding on the shared bearer cookie.
 *
 * Production and staging applications share a cookie domain, because staging hosts are siblings of
 * the production hosts under the same parent. The cookie name is the only separator between them,
 * and the Security identity provider is the only component allowed to write the cookie at all.
 */
final class BearerCookiePolicyPass implements CompilerPassInterface
{
    private const string PRODUCTION_COOKIE_NAME = 'BEARER';
    private const string PRODUCTION_DEPLOYMENT = 'prod';
    private const string COOKIE_PROVIDER_PREFIX = 'lexik_jwt_authentication.cookie_provider.';

    public function process(ContainerBuilder $container): void
    {
        $this->assertDoesNotIssueCookies($container);
        $this->assertDeploymentSpecificCookieName($container);
    }

    /**
     * A consuming application must read the bearer cookie, never write it. Lexik registers one
     * cookie provider service per configured `set_cookies` entry, so their presence means this
     * application would issue its own bearer cookie and overwrite the identity provider's.
     */
    private function assertDoesNotIssueCookies(ContainerBuilder $container): void
    {
        $issued = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            if (str_starts_with($id, self::COOKIE_PROVIDER_PREFIX)) {
                $issued[] = substr($id, strlen(self::COOKIE_PROVIDER_PREFIX));
            }
        }

        if ([] === $issued) {
            return;
        }

        throw new LogicException(sprintf('This application must not issue bearer cookies (lexik_jwt_authentication.set_cookies configures: %s). Only the Security identity provider writes the bearer cookie; consuming applications read it through token_extractors.cookie. Remove the set_cookies configuration.', implode(', ', $issued)));
    }

    /**
     * A deployment that names itself something other than production must not keep the production
     * cookie name, or it would overwrite production sessions on the shared cookie domain.
     */
    private function assertDeploymentSpecificCookieName(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('azymuthia_security.deployment_env')
            || !$container->hasParameter('azymuthia_security.bearer_cookie_name')
        ) {
            return;
        }

        $deployment = trim((string) $container->resolveEnvPlaceholders(
            $container->getParameter('azymuthia_security.deployment_env'),
            true,
        ));

        $cookieName = trim((string) $container->resolveEnvPlaceholders(
            $container->getParameter('azymuthia_security.bearer_cookie_name'),
            true,
        ));

        if (self::PRODUCTION_DEPLOYMENT === $deployment || self::PRODUCTION_COOKIE_NAME !== $cookieName) {
            return;
        }

        throw new LogicException(sprintf('APP_DEPLOYMENT_ENV "%s" must not keep the production BEARER_COOKIE_NAME "%s", which would overwrite production sessions on the shared cookie domain.', $deployment, self::PRODUCTION_COOKIE_NAME));
    }
}
