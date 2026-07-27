<?php

declare(strict_types=1);

namespace Bootstrap\DependencyInjection\CompilerPass;

use Shared\Application\Command\AsCommandHandler;
use Shared\Application\Query\AsQueryHandler;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Translates the project's own #[AsCommandHandler]/#[AsQueryHandler] attributes into Messenger
 * handler tags, so Application code depends on our own Shared contracts instead of Messenger
 * directly (see deptrac_layers.yaml: Messenger is an Infrastructure-only vendor).
 */
final class RegisterMessageBusHandlersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsCommandHandler::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag('messenger.message_handler', ['bus' => 'command.bus']);
            },
        );

        $container->registerAttributeForAutoconfiguration(
            AsQueryHandler::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag('messenger.message_handler', ['bus' => 'query.bus']);
            },
        );
    }
}
