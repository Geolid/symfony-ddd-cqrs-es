<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Itspire\MonologLoki\Handler\LokiHandler;
use Patchlevel\Hydrator\StackHydrator;
use Psr\Log\LogLevel;
use Sentry\Monolog\ExceptionToSentryIssueHandler;
use Sentry\State\HubInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Infrastructure\Hydration\HydratorFactory;
use Shared\Infrastructure\Messaging\MessengerCommandBus;
use Shared\Infrastructure\Messaging\MessengerQueryBus;
use Shared\Infrastructure\Monitoring\Sentry\SentryEventEnricher;
use Shared\Infrastructure\Persistence\EventStore\Appender\IntegrationEventAppender;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Shared');

    $commandBusAlias = $services->alias(CommandBusInterface::class, MessengerCommandBus::class);
    $queryBusAlias = $services->alias(QueryBusInterface::class, MessengerQueryBus::class);
    $services->alias(IntegrationEventPublisherInterface::class, IntegrationEventAppender::class);

    // StackHydratorBuilder is fluent, not constructor-injectable — assembled through HydratorFactory instead.
    $services->set('shared.hydration.result_hydrator', StackHydrator::class)
        ->factory([service(HydratorFactory::class), 'create']);

    if ('test' === $container->env()) {
        // Fetched by type from the container; must be public for that.
        $commandBusAlias->public();
        $queryBusAlias->public();
    }

    if ('prod' === $container->env()) {
        $services->set('sentry.callback.before_send', Closure::class)
            ->public()
            ->factory([service(SentryEventEnricher::class), 'beforeSend']);

        $services->set(ExceptionToSentryIssueHandler::class)
            ->args([service(HubInterface::class), LogLevel::ERROR]);

        $services->set(LokiHandler::class)
            ->arg('$apiConfig', [
                'entrypoint' => '%env(LOKI_URL)%',
                'context' => ['app' => '%kernel.app_id%'],
                'labels' => ['env' => '%env(APP_ENV)%'],
                'client_name' => '%kernel.app_id%',
                'auth' => [
                    'basic' => [
                        'user' => '%env(LOKI_BASIC_AUTH_USER)%',
                        'password' => '%env(LOKI_BASIC_AUTH_PASSWORD)%',
                    ],
                ],
            ]);
    }
};
