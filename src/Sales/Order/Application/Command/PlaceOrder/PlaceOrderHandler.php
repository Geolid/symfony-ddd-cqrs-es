<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\PlaceOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Buyer\BuyerResolverInterface;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Exception\ProductNotAvailableException;
use Sales\Order\Application\Product\ProductResolverInterface;
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
        private ProductResolverInterface $productResolver,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BuyerNotRegisteredException
     * @throws ProductNotAvailableException
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
                function (array $line): OrderLine {
                    $product = $this->productResolver->resolveFor($line['productId'])
                        ?? throw ProductNotAvailableException::forId($line['productId']);

                    return OrderLine::of(
                        $product->label,
                        $line['quantity'],
                        Money::fromCents($product->unitAmountInCents),
                    );
                },
                $command->lines,
            ),
            $this->clock->now(),
        );

        $this->repository->save($order);
    }
}
