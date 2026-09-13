<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\CompleteCheckoutSession;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionAlreadyExistsException;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionNotFoundException;
use Shopping\Checkout\Domain\CheckoutSession\Repository\CheckoutSessionRepositoryInterface;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionId;

#[CommandHandler]
final readonly class CompleteCheckoutSessionHandler
{
    public function __construct(
        private CheckoutSessionRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CheckoutSessionNotFoundException
     * @throws CheckoutSessionAlreadyExistsException
     */
    public function __invoke(CompleteCheckoutSession $command): void
    {
        $checkoutSession = $this->repository->load(CheckoutSessionId::fromString($command->id));
        $checkoutSession->complete(
            cartId: $command->cartId,
            customerId: $command->customerId,
            items: array_map($this->resolveItem(...), $command->items),
            shippingAddress: PostalAddressMapper::fromArray($command->shippingAddress),
            billingAddress: PostalAddressMapper::fromArray($command->billingAddress),
            paymentId: $command->paymentId,
            completedAt: $this->clock->now(),
        );
        $this->repository->save($checkoutSession);
    }

    /**
     * @param array{productId: string, label: string, unitPriceInCents: int, quantity: int} $item
     */
    private function resolveItem(array $item): CheckoutItem
    {
        return CheckoutItem::of(
            $item['productId'],
            Label::fromString($item['label']),
            Money::fromCents($item['unitPriceInCents']),
            Quantity::of($item['quantity']),
        );
    }
}
