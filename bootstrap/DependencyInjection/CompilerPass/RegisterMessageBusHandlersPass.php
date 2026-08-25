<?php

declare(strict_types=1);

namespace Bootstrap\DependencyInjection\CompilerPass;

use Shared\Application\Command\CommandUseCase;
use Shared\Application\Query\QueryUseCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterMessageBusHandlersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            CommandUseCase::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag('messenger.message_handler', ['bus' => 'command.bus']);
            },
        );

        $container->registerAttributeForAutoconfiguration(
            QueryUseCase::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag('messenger.message_handler', ['bus' => 'query.bus']);
            },
        );
    }
}
