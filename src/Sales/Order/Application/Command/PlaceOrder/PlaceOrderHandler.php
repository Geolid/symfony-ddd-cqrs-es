<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\PlaceOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Buyer\BuyerResolverInterface;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Exception\ProductChangedException;
use Sales\Order\Application\Exception\ProductNotAvailableException;
use Sales\Order\Application\Finder\Product\ProductAvailabilityFinderInterface;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\ValueObject\Money;

#[AsCommandHandler]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private BuyerResolverInterface $buyerResolver,
        private ProductAvailabilityFinderInterface $productFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BuyerNotRegisteredException
     * @throws ProductNotAvailableException
     * @throws ProductChangedException
     * @throws OrderWithoutLineException
     */
    public function __invoke(PlaceOrder $command): void
    {
        $buyer = $this->buyerResolver->resolveFor($command->customerId)
            ?? throw BuyerNotRegisteredException::forId($command->customerId);

        $order = Order::place(
            id: OrderId::fromString($command->id),
            customerId: $buyer->id,
            buyerAddress: $buyer->address,
            lines: array_map(
                function (array $line): OrderLine {
                    $this->productFinder->ensureAvailable($line['productId'], $line['label'], $line['unitAmountInCents']);

                    return OrderLine::of(
                        $line['label'],
                        $line['quantity'],
                        Money::fromCents($line['unitAmountInCents']),
                    );
                },
                $command->lines,
            ),
            placedAt: $this->clock->now(),
        );

        $this->repository->save($order);
    }
}
