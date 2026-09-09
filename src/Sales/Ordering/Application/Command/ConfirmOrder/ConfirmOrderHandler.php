<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ConfirmOrder;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\BuyerAddressesNotCompletedException;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\BuyerNotRegisteredException;
use Sales\Ordering\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Ordering\Domain\Order\Exception\OrderAlreadyExistsException;
use Sales\Ordering\Domain\Order\Exception\OrderWithoutLineException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Shared\Entity\Line;
use Sales\Ordering\Domain\Shared\ValueObject\LineId;
use Sales\Ordering\Domain\Shared\ValueObject\Product;
use Sales\Ordering\Domain\Shared\ValueObject\Quantity;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

#[CommandHandler]
final readonly class ConfirmOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private BuyerFinderInterface $buyerFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BuyerNotRegisteredException
     * @throws BuyerAddressesNotCompletedException
     * @throws OrderWithoutLineException
     */
    public function __invoke(ConfirmOrder $command): void
    {
        $buyer = $this->buyerFinder->ofIdOrNull($command->buyerId)
            ?? throw BuyerNotRegisteredException::forId($command->buyerId);

        if (null === $buyer->shippingAddress || null === $buyer->billingAddress) {
            throw BuyerAddressesNotCompletedException::forId($command->buyerId);
        }

        $order = Order::confirm(
            id: OrderId::fromString($command->id),
            buyerId: $buyer->buyerId,
            paymentId: $command->paymentId,
            shippingAddress: PostalAddressMapper::fromArray([
                'recipientName' => $buyer->shippingAddress->recipientName,
                'address' => (array) $buyer->shippingAddress->address,
            ]),
            billingAddress: PostalAddressMapper::fromArray([
                'recipientName' => $buyer->billingAddress->recipientName,
                'address' => (array) $buyer->billingAddress->address,
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
