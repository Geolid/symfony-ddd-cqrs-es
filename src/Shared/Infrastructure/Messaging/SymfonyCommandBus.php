<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Messaging;

use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class SymfonyCommandBus implements CommandBusInterface
{
    use HandleTrait;
    use UnwrapsSymfonyExceptionTrait;

    public function __construct(#[Autowire(service: 'command.bus')] MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function dispatch(CommandInterface $command): void
    {
        try {
            $this->handle($command);
        } catch (HandlerFailedException $e) {
            throw $this->unwrap($e);
        }
    }
}
