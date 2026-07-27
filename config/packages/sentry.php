<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;

/**
 * Error tracking is provider-agnostic: the Sentry SDK's wire protocol is also implemented by
 * free, self-hostable options (Sentry self-hosted, or GlitchTip — a lightweight open-source
 * reimplementation). Point SENTRY_DSN at any of them; nothing else in this file changes.
 */
return static function (ContainerConfigurator $container): void {
    if ('prod' === $container->env()) {
        $container->extension('sentry', [
            'dsn' => '%env(SENTRY_DSN)%',
            'options' => [
                'environment' => '%env(APP_ENV)%',
                'before_send' => 'sentry.callback.before_send',
                'ignore_exceptions' => [
                    AccessDeniedHttpException::class,
                    BadRequestHttpException::class,
                    ConflictHttpException::class,
                    NotFoundHttpException::class,
                    RecoverableMessageHandlingException::class,
                ],
                'traces_sample_rate' => '%env(float:SENTRY_TRACES_SAMPLE_RATE)%',
            ],
        ]);
    }
};
