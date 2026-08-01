<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\PlaceOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Buyer\BuyerResolverInterface;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\Order\Domain\Money;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\OrderId;
use Sales\Order\Domain\OrderLine;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private BuyerResolverInterface $buyerResolver,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BuyerNotRegisteredException
     * @throws OrderWithoutLineException
     */
    public function __invoke(PlaceOrder $command): void
    {
        $buyer = $this->buyerResolver->resolveFor($command->customerId)
            ?? throw BuyerNotRegisteredException::forId($command->customerId);

        $order = Order::place(
            OrderId::fromString($command->id),
            $buyer->id,
            $buyer->address,
            array_map(
                static fn (array $line): OrderLine => OrderLine::of(
                    $line['label'],
                    $line['quantity'],
                    Money::fromCents($line['unitAmountInCents']),
                ),
                $command->lines,
            ),
            $this->clock->now(),
        );

        $this->repository->save($order);
    }
}
