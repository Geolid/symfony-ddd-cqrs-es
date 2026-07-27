<?php

declare(strict_types=1);

namespace Bootstrap\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

final class TagEnvVarProcessorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(EnvVarProcessorInterface::class)
            ->addTag('container.env_var_processor');
    }
}
