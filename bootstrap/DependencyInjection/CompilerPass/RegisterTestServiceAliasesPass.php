<?php

declare(strict_types=1);

namespace Bootstrap\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Symfony inlines/removes a private, singly-(or un-)referenced service at compile time — not
 * directly `get()`-able from a test afterward. Makes every own-code service's own implemented
 * interface public + aliased in test env so `$this->service(<X>Interface::class)` just works.
 * Scoped to our own top-level `src/` namespaces — never touches a vendor/Symfony/Doctrine
 * definition, which would disrupt the container's own internal decoration/inlining.
 */
final class RegisterTestServiceAliasesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ('test' !== $container->getParameter('kernel.environment')) {
            return;
        }

        $prefixes = $this->ownNamespacePrefixes();

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();

            if (null === $class || !class_exists($class) || !$this->isOwnNamespace($class, $prefixes)) {
                continue;
            }

            foreach (class_implements($class) as $interface) {
                if (!$this->isOwnNamespace($interface, $prefixes)) {
                    continue;
                }

                $definition->setPublic(true);

                if (!$container->hasAlias($interface) && !$container->has($interface)) {
                    $container->setAlias($interface, $id)->setPublic(true);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function ownNamespacePrefixes(): array
    {
        $directories = glob(\dirname(__DIR__, 3).'/src/*', \GLOB_ONLYDIR) ?: [];

        return array_map(static fn (string $directory): string => basename($directory).'\\', $directories);
    }

    /**
     * @param list<string> $prefixes
     */
    private function isOwnNamespace(string $class, array $prefixes): bool
    {
        return array_any($prefixes, static fn (string $prefix): bool => str_starts_with($class, $prefix));
    }
}
