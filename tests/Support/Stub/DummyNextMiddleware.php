<?php

declare(strict_types=1);

namespace Support\Stub;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class DummyNextMiddleware implements MiddlewareInterface
{
    public function __construct(private ?\Throwable $failure = null)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (null !== $this->failure) {
            throw $this->failure;
        }

        return $envelope;
    }
}
