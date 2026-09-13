<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ConfirmOrder;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Domain\Order\Exception\OrderAlreadyExistsException;
use Sales\Ordering\Domain\Order\Exception\OrderWithoutLineException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Order\ValueObject\Product;
use Sales\Ordering\Domain\Order\ValueObject\Quantity;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

#[CommandHandler]
final readonly class ConfirmOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderWithoutLineException
     */
    public function __invoke(ConfirmOrder $command): void
    {
        $order = Order::confirm(
            id: OrderId::fromString($command->id),
            cartId: $command->cartId,
            customerId: $command->customerId,
            checkoutSessionId: $command->checkoutSessionId,
            shippingAddress: PostalAddressMapper::fromArray($command->shippingAddress),
            lines: array_map($this->resolveLine(...), $command->lines),
            confirmedAt: $this->clock->now(),
        );

        try {
            $this->repository->save($order);
        } catch (OrderAlreadyExistsException) {
            return;
        }
    }

    /**
     * @param array{productId: string, label: string, unitPriceInCents: int, quantity: int} $line
     *
     * @return array{product: Product, quantity: Quantity}
     */
    private function resolveLine(array $line): array
    {
        return [
            'product' => Product::of($line['productId'], Label::fromString($line['label']), Money::fromCents($line['unitPriceInCents'])),
            'quantity' => Quantity::of($line['quantity']),
        ];
    }
}
