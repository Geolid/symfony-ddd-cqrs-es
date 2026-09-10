<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ConfirmOrder;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\ShopperAddressesNotCompletedException;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\ShopperNotRegisteredException;
use Sales\Ordering\Application\Finder\Shopper\ShopperFinderInterface;
use Sales\Ordering\Domain\Order\Entity\Line;
use Sales\Ordering\Domain\Order\Exception\OrderAlreadyExistsException;
use Sales\Ordering\Domain\Order\Exception\OrderWithoutLineException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\Order\ValueObject\LineId;
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
        private ShopperFinderInterface $shopperFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShopperNotRegisteredException
     * @throws ShopperAddressesNotCompletedException
     * @throws OrderWithoutLineException
     */
    public function __invoke(ConfirmOrder $command): void
    {
        $shopper = $this->shopperFinder->ofIdOrNull($command->shopperId)
            ?? throw ShopperNotRegisteredException::forId($command->shopperId);

        if (null === $shopper->shippingAddress || null === $shopper->billingAddress) {
            throw ShopperAddressesNotCompletedException::forId($command->shopperId);
        }

        $order = Order::confirm(
            id: OrderId::fromString($command->id),
            cartId: $command->cartId,
            shopperId: $shopper->shopperId,
            paymentId: $command->paymentId,
            shippingAddress: PostalAddressMapper::fromArray([
                'recipientName' => $shopper->shippingAddress->recipientName,
                'address' => (array) $shopper->shippingAddress->address,
            ]),
            billingAddress: PostalAddressMapper::fromArray([
                'recipientName' => $shopper->billingAddress->recipientName,
                'address' => (array) $shopper->billingAddress->address,
            ]),
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
     * @param array{lineId: string, productId: string, label: string, unitPriceInCents: int, quantity: int} $line
     */
    private function resolveLine(array $line): Line
    {
        return new Line(
            LineId::fromString($line['lineId']),
            Product::of($line['productId'], Label::fromString($line['label']), Money::fromCents($line['unitPriceInCents'])),
            Quantity::of($line['quantity']),
        );
    }
}
