<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Sentry\Monolog\ExceptionToSentryIssueHandler;
use Sentry\State\HubInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Infrastructure\Messaging\CommandBus;
use Shared\Infrastructure\Messaging\QueryBus;
use Shared\Infrastructure\Monitoring\Sentry\SentryEventEnricher;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Shared');

    $commandBusAlias = $services->alias(CommandBusInterface::class, CommandBus::class);
    $queryBusAlias = $services->alias(QueryBusInterface::class, QueryBus::class);

    if ('test' === $container->env()) {
        $commandBusAlias->public();
        $queryBusAlias->public();
    }

    if ('prod' === $container->env()) {
        $services->set('sentry.callback.before_send', Closure::class)
            ->public()
            ->factory([service(SentryEventEnricher::class), 'beforeSend']);

        $services->set(ExceptionToSentryIssueHandler::class)
            ->args([service(HubInterface::class), \Psr\Log\LogLevel::ERROR]);
    }
};
