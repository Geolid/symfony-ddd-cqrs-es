<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Messaging;

use Symfony\Component\Messenger\Exception\HandlerFailedException;

trait UnwrapsMessengerExceptionTrait
{
    private function unwrap(HandlerFailedException $e): \Throwable
    {
        $exception = current($e->getWrappedExceptions(recursive: true));

        return $exception ?: $e;
    }
}
