<?php

declare(strict_types=1);

namespace Webhook\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * The vendor WebhookController dispatches ConsumeRemoteEventMessage on a synchronous bus, which
 * wraps any exception the consumer raises in a HandlerFailedException. The framework error
 * mapping (config/packages/exceptions.php) only sees that wrapper and falls back to 500.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 0)]
final class UnwrapHandlerFailedExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if (!$throwable instanceof HandlerFailedException) {
            return;
        }

        $wrapped = current($throwable->getWrappedExceptions(recursive: true));

        if ($wrapped instanceof \Throwable) {
            $event->setThrowable($wrapped);
        }
    }
}
