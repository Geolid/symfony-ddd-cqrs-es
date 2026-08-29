<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Sentry;

use Sentry\SentrySdk;
use Sentry\State\Scope;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class ErrorContextMessengerMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $exception) {
            SentrySdk::getCurrentHub()->configureScope(static function (Scope $scope) use ($envelope): void {
                $scope->setContext('messenger', [
                    'message' => $envelope->getMessage()::class,
                ]);
            });

            throw $exception;
        }
    }
}
