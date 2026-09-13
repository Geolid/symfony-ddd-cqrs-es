<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ConfirmOrder;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Application\Mapper\OrderItemMapper;
use Sales\Ordering\Domain\Order\Exception\OrderAlreadyExistsException;
use Sales\Ordering\Domain\Order\Exception\OrderWithoutLineException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Currency;

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
        $currency = Currency::from($command->currency);

        $order = Order::confirm(
            id: OrderId::fromString($command->id),
            cartId: $command->cartId,
            customerId: $command->customerId,
            checkoutSessionId: $command->checkoutSessionId,
            shippingAddress: PostalAddressMapper::fromArray($command->shippingAddress),
            items: array_map(static fn (array $line): \Sales\Ordering\Domain\Order\ValueObject\OrderItem => OrderItemMapper::fromArray($line, $currency), $command->lines),
            currency: $currency,
            confirmedAt: $this->clock->now(),
        );

        try {
            $this->repository->save($order);
        } catch (OrderAlreadyExistsException) {
            return;
        }
    }
}
