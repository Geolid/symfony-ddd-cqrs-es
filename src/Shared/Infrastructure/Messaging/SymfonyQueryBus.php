<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Messaging;

use Shared\Application\Query\QueryBusInterface;
use Shared\Application\Query\QueryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class SymfonyQueryBus implements QueryBusInterface
{
    use HandleTrait;
    use UnwrapsSymfonyExceptionTrait;

    public function __construct(#[Autowire(service: 'query.bus')] MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @template TResult
     *
     * @param QueryInterface<TResult> $query
     *
     * @return TResult
     */
    public function ask(QueryInterface $query): mixed
    {
        try {
            return $this->handle($query);
        } catch (HandlerFailedException $e) {
            throw $this->unwrap($e);
        }
    }
}
