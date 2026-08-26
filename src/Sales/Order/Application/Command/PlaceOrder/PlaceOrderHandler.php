<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\PlaceOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Exception\BuyerAddressesNotCompletedException;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Exception\OutdatedOrderException;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\PostalAddressResult;
use Sales\Order\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Sales\Order\Application\Finder\ListedProduct\ListedProductResult;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;

#[CommandHandler]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private BuyerFinderInterface $buyerFinder,
        private ListedProductFinderInterface $listedProductFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BuyerAddressesNotCompletedException
     * @throws BuyerNotRegisteredException
     * @throws OutdatedOrderException
     * @throws OrderWithoutLineException
     */
    public function __invoke(PlaceOrder $command): void
    {
        $buyer = $this->buyerFinder->ofIdOrNull($command->customerId)
            ?? throw BuyerNotRegisteredException::forId($command->customerId);

        if (null === $buyer->shippingAddress || null === $buyer->billingAddress) {
            throw BuyerAddressesNotCompletedException::forId($command->customerId);
        }

        $productIds = array_column($command->lines, 'productId');

        /** @var array<string, ListedProductResult> $currentProducts */
        $currentProducts = iterator_to_array($this->listedProductFinder->byIds(...$productIds)->indexBy(
            static fn (ListedProductResult $result): string => $result->productId,
        ));

        $order = Order::place(
            id: OrderId::fromString($command->id),
            customerId: $buyer->customerId,
            shippingAddress: $this->postalAddressOf($buyer->shippingAddress),
            billingAddress: $this->postalAddressOf($buyer->billingAddress),
            lines: array_map(
                fn (array $line): OrderLine => $this->resolveLine($line, $currentProducts),
                $command->lines,
            ),
            placedAt: $this->clock->now(),
        );

        $this->repository->save($order);
    }

    private function postalAddressOf(PostalAddressResult $address): PostalAddress
    {
        return PostalAddress::of(
            FullName::of($address->firstName, $address->lastName),
            Address::of($address->street, $address->postalCode, $address->city),
        );
    }

    /**
     * @param array{productId: string, label: string, unitAmountInCents: int, quantity: int} $line
     * @param array<string, ListedProductResult>                                             $currentProducts
     *
     * @throws OutdatedOrderException
     */
    private function resolveLine(array $line, array $currentProducts): OrderLine
    {
        $currentResult = $currentProducts[$line['productId']] ?? null;
        $current = null !== $currentResult
            ? Product::of($currentResult->productId, Label::fromString($currentResult->label), Money::fromCents($currentResult->unitAmountInCents))
            : null;

        if (null === $current || !Money::fromCents($line['unitAmountInCents'])->equals($current->price)) {
            throw OutdatedOrderException::forId($line['productId']);
        }

        return OrderLine::of($current, $line['quantity']);
    }
}
