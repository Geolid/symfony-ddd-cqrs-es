<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\OpenCheckoutSession;

use Shared\Application\Command\CommandHandler;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
use Shopping\Checkout\Domain\CheckoutSession;
use Shopping\Checkout\Domain\Exception\CheckoutSessionAlreadyExistsException;
use Shopping\Checkout\Domain\Exception\CheckoutSessionEmptyException;
use Shopping\Checkout\Domain\Repository\CheckoutSessionRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\CheckoutSessionId;

#[CommandHandler]
final readonly class OpenCheckoutSessionHandler
{
    public function __construct(private CheckoutSessionRepositoryInterface $repository)
    {
    }

    /**
     * @throws CheckoutSessionEmptyException
     * @throws CheckoutSessionAlreadyExistsException
     */
    public function __invoke(OpenCheckoutSession $command): void
    {
        $checkoutSession = CheckoutSession::open(
            id: CheckoutSessionId::fromString($command->id),
            cartId: $command->cartId,
            customerId: $command->customerId,
            items: array_map(CheckoutItemMapper::fromArray(...), $command->lines),
            shippingAddress: PostalAddressMapper::fromArray($command->shippingAddress),
            billingAddress: PostalAddressMapper::fromArray($command->billingAddress),
            openedAt: $command->openedAt,
        );

        $this->repository->save($checkoutSession);
    }
}
