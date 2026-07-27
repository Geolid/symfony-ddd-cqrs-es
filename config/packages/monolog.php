<?php

declare(strict_types=1);

use Itspire\MonologLoki\Handler\LokiHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\TagProcessor;
use Sentry\Monolog\ExceptionToSentryIssueHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('monolog', [
        'channels' => ['deprecation', 'messenger'],
    ]);

    $container->services()
        ->set('monolog.processor.app_id', TagProcessor::class)
        ->arg('$tags', ['app' => '%kernel.app_id%'])
        ->tag('monolog.processor');

    if ('dev' === $container->env()) {
        $container->extension('monolog', [
            'handlers' => [
                // Structured stdout logging is enough for a `docker compose logs` workflow —
                // no log-shipping SaaS needed. Point Monolog at a self-hosted Loki/Promtail
                // stack (or ELK) if you want log aggregation across services.
                'docker' => [
                    'type' => 'stream',
                    'path' => 'php://stdout',
                    'level' => 'notice',
                    'channels' => ['!event', '!doctrine', '!deprecation', '!console'],
                    'formatter' => 'monolog.formatter.line',
                ],
                'main' => [
                    'type' => 'stream',
                    'path' => '%kernel.logs_dir%/%kernel.environment%.log',
                    'level' => 'debug',
                    'channels' => ['!event'],
                ],
            ],
        ]);

        $container->services()
            ->set('monolog.formatter.line', LineFormatter::class)
            ->arg('$format', "[%%datetime%%] [%%extra.app%%] %%channel%%.%%level_name%%: %%message%% %%extra%%\n");
    }

    if ('prod' === $container->env()) {
        $container->extension('monolog', [
            'handlers' => [
                'main' => [
                    'type' => 'fingers_crossed',
                    'action_level' => 'error',
                    'handler' => 'nested',
                    'excluded_http_codes' => [404, 403],
                    'buffer_size' => 50,
                ],
                'nested' => [
                    'type' => 'stream',
                    'path' => 'php://stderr',
                    'level' => 'debug',
                    'formatter' => 'monolog.formatter.json',
                ],
                'sentry' => [
                    'type' => 'service',
                    'id' => ExceptionToSentryIssueHandler::class,
                ],
                // Loki push failures never break a request — see README's "Open source over
                // paid SaaS" for how to get a free LOKI_URL/basic-auth pair (Grafana Cloud's
                // free tier, or a self-hosted Loki).
                'loki_safe' => [
                    'type' => 'whatfailuregroup',
                    'members' => ['loki'],
                    'level' => 'notice',
                ],
                'loki' => [
                    'type' => 'service',
                    'id' => LokiHandler::class,
                ],
            ],
        ]);

        $container->services()
            ->set(LokiHandler::class)
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
