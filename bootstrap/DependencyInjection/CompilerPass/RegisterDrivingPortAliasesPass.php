<?php

declare(strict_types=1);

namespace Bootstrap\DependencyInjection\CompilerPass;

use Shared\Application\Port\AsDrivingPort;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Symfony inlines/removes a private, singly-(or un-)referenced service at compile time — not
 * directly `get()`-able from a test afterward. Makes every #[AsDrivingPort] implementation public
 * + interface-aliased in test env so `$this->service(<Port>Interface::class)` just works.
 */
final class RegisterDrivingPortAliasesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ('test' !== $container->getParameter('kernel.environment')) {
            return;
        }

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();

            if (null === $class || !class_exists($class)) {
                continue;
            }

            foreach (class_implements($class) as $interface) {
                if ([] === new \ReflectionClass($interface)->getAttributes(AsDrivingPort::class)) {
                    continue;
                }

                $definition->setPublic(true);

                if (!$container->hasAlias($interface) && !$container->has($interface)) {
                    $container->setAlias($interface, $id)->setPublic(true);
                }
            }
        }
    }
}
