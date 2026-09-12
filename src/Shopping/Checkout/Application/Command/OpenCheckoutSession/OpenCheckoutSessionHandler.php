<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\OpenCheckoutSession;

use Shared\Application\Command\CommandHandler;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Domain\CheckoutSession\CheckoutSession;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionAlreadyExistsException;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionEmptyException;
use Shopping\Checkout\Domain\CheckoutSession\Repository\CheckoutSessionRepositoryInterface;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionId;

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
            shopperId: $command->shopperId,
            lines: $command->lines,
            shippingAddress: PostalAddressMapper::fromArray($command->shippingAddress),
            billingAddress: PostalAddressMapper::fromArray($command->billingAddress),
            totalAmountInCents: $command->totalAmountInCents,
            openedAt: $command->openedAt,
        );

        $this->repository->save($checkoutSession);
    }
}
