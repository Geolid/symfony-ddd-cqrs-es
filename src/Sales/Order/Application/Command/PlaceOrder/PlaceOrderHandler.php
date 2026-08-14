<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\PlaceOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Exception\BuyerAddressesNotCompletedException;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Exception\OutdatedOrderException;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\BuyerResult;
use Sales\Order\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Sales\Order\Application\Finder\ListedProduct\ListedProductResult;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\Order\Domain\Exception\OutdatedOrderLineException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\Service\OrderLineOffer;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;

#[AsCommandHandler]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private BuyerFinderInterface $buyerFinder,
        private ListedProductFinderInterface $listedProductFinder,
        private OrderLineOffer $orderLineOffer,
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
        $buyer = $this->buyerFinder->ofId($command->customerId)
            ?? throw BuyerNotRegisteredException::forId($command->customerId);

        if (!$buyer->hasCompletedAddresses()) {
            throw BuyerAddressesNotCompletedException::forId($command->customerId);
        }

        $productIds = array_column($command->lines, 'productId');

        /** @var array<string, ListedProductResult> $currentProducts */
        $currentProducts = iterator_to_array($this->listedProductFinder->byIds(...$productIds)->indexedBy(
            static fn (ListedProductResult $result): string => $result->productId,
        ));

        $order = Order::place(
            id: OrderId::fromString($command->id),
            customerId: $buyer->customerId,
            shippingAddress: $this->shippingAddressOf($buyer),
            billingAddress: $this->billingAddressOf($buyer),
            lines: array_map(
                fn (array $line): OrderLine => $this->resolveLine($line, $currentProducts),
                $command->lines,
            ),
            placedAt: $this->clock->now(),
        );

        $this->repository->save($order);
    }

    private function shippingAddressOf(BuyerResult $buyer): PostalAddress
    {
        \assert(null !== $buyer->shippingFirstName && null !== $buyer->shippingLastName && null !== $buyer->shippingStreet && null !== $buyer->shippingPostalCode && null !== $buyer->shippingCity);

        return PostalAddress::of(
            FullName::of($buyer->shippingFirstName, $buyer->shippingLastName),
            Address::of($buyer->shippingStreet, $buyer->shippingPostalCode, $buyer->shippingCity),
        );
    }

    private function billingAddressOf(BuyerResult $buyer): PostalAddress
    {
        \assert(null !== $buyer->billingFirstName && null !== $buyer->billingLastName && null !== $buyer->billingStreet && null !== $buyer->billingPostalCode && null !== $buyer->billingCity);

        return PostalAddress::of(
            FullName::of($buyer->billingFirstName, $buyer->billingLastName),
            Address::of($buyer->billingStreet, $buyer->billingPostalCode, $buyer->billingCity),
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
        $claimed = Product::of($line['productId'], Label::fromString($line['label']), Money::fromCents($line['unitAmountInCents']));

        try {
            $this->orderLineOffer->ensureStillValid($claimed, $current);
        } catch (OutdatedOrderLineException $e) {
            throw OutdatedOrderException::forReason($e->getMessage(), $e);
        }

        \assert(null !== $current);

        return OrderLine::of($current, $line['quantity']);
    }
}
