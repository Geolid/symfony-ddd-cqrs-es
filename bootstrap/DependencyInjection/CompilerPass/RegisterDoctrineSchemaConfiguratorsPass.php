<?php

declare(strict_types=1);

namespace Bootstrap\DependencyInjection\CompilerPass;

use Patchlevel\EventSourcing\Schema\DoctrineSchemaConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterDoctrineSchemaConfiguratorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(DoctrineSchemaConfigurator::class)
            ->addTag('event_sourcing.doctrine_schema_configurator');
    }
}
