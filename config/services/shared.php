<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Itspire\MonologLoki\Handler\LokiHandler;
use Psr\Log\LogLevel;
use Sentry\Monolog\ExceptionToSentryIssueHandler;
use Sentry\State\HubInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Infrastructure\Gateway\FakeReferenceResponseFactory;
use Shared\Infrastructure\Messaging\CommandBus;
use Shared\Infrastructure\Messaging\QueryBus;
use Shared\Infrastructure\Monitoring\Sentry\SentryEventEnricher;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\MockHttpClient;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Shared');

    $commandBusAlias = $services->alias(CommandBusInterface::class, CommandBus::class);
    $queryBusAlias = $services->alias(QueryBusInterface::class, QueryBus::class);

    if (in_array($container->env(), ['dev', 'demo'], true)) {
        $services->set('acme.client.fake_response_factory', FakeReferenceResponseFactory::class)
            ->args(['trackingNumber', 'ACME-LOCAL']);
        $services->set('acme.client', MockHttpClient::class)
            ->args([service('acme.client.fake_response_factory'), '%env(ACME_BASE_URL)%']);

        $services->set('globex.client.fake_response_factory', FakeReferenceResponseFactory::class)
            ->args(['chargeReference', 'GLBX-LOCAL']);
        $services->set('globex.client', MockHttpClient::class)
            ->args([service('globex.client.fake_response_factory'), '%env(GLOBEX_BASE_URL)%']);
    }

    if ('test' === $container->env()) {
        // CqrsTrait::dispatch()/ask() fetch these by type from the container; must be public.
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
