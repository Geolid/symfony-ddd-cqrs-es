<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\AnonymizeExpiredOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\Service\RetentionWindow;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class AnonymizeExpiredOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
        private RetentionWindow $retentionWindow,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     */
    public function __invoke(AnonymizeExpiredOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));

        $order->anonymize($this->clock->now(), $this->retentionWindow);
        $this->repository->save($order);
    }
}
