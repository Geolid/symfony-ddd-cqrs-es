<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\OpenCheckoutSession;

use Shared\Application\Command\CommandHandler;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Domain\Cart\Entity\Line;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;
use Shopping\Checkout\Domain\Cart\ValueObject\Product;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;
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
            lines: array_map($this->resolveLine(...), $command->lines),
            shippingAddress: PostalAddressMapper::fromArray($command->shippingAddress),
            billingAddress: PostalAddressMapper::fromArray($command->billingAddress),
            totalAmountInCents: $command->totalAmountInCents,
            openedAt: $command->openedAt,
        );

        $this->repository->save($checkoutSession);
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
