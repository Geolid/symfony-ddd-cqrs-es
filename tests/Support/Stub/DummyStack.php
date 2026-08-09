<?php

declare(strict_types=1);

namespace Support\Stub;

use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class DummyStack implements StackInterface
{
    public function __construct(private MiddlewareInterface $next)
    {
    }

    public function next(): MiddlewareInterface
    {
        return $this->next;
    }
}
