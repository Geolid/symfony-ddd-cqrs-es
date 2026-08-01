<?php

declare(strict_types=1);

namespace Webhook\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

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
