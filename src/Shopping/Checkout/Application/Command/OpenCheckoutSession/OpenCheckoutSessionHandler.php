<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\OpenCheckoutSession;

use Shared\Application\Command\CommandHandler;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Currency;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
use Shopping\Checkout\Domain\CheckoutSession;
use Shopping\Checkout\Domain\Exception\CheckoutSessionAlreadyExistsException;
use Shopping\Checkout\Domain\Exception\CheckoutSessionEmptyException;
use Shopping\Checkout\Domain\Repository\CheckoutSessionRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\CheckoutSessionId;
use Shopping\Checkout\Domain\ValueObject\TaxRate;

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
        $currency = Currency::from($command->currency);
        $taxRate = TaxRate::fromBasisPoints($command->taxRateBasisPoints);

        $checkoutSession = CheckoutSession::open(
            id: CheckoutSessionId::fromString($command->id),
            cartId: $command->cartId,
            customerId: $command->customerId,
            items: array_map(static fn (array $line): \Shopping\Checkout\Domain\ValueObject\CheckoutItem => CheckoutItemMapper::fromArray($line, $currency, $taxRate), $command->lines),
            currency: $currency,
            shippingAddress: PostalAddressMapper::fromArray($command->shippingAddress),
            billingAddress: PostalAddressMapper::fromArray($command->billingAddress),
            openedAt: $command->openedAt,
        );

        $this->repository->save($checkoutSession);
    }
}
