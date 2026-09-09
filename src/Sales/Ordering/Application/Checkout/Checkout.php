<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Checkout;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Application\Checkout\Exception\BuyerAddressesNotCompletedException;
use Sales\Ordering\Application\Checkout\Exception\BuyerErasureRequestedException;
use Sales\Ordering\Application\Checkout\Exception\BuyerNotRegisteredException;
use Sales\Ordering\Application\Checkout\Exception\CartOutdatedException;
use Sales\Ordering\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Ordering\Application\Finder\Buyer\PostalAddressResult;
use Sales\Ordering\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Sales\Ordering\Application\Finder\ListedProduct\ListedProductResult;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyConvertedException;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyExistsException;
use Sales\Ordering\Domain\Cart\Exception\CartEmptyException;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Sales\Ordering\Domain\Shared\Entity\Line;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class Checkout implements CheckoutInterface
{
    public function __construct(
        private CartRepositoryInterface $repository,
        private BuyerFinderInterface $buyerFinder,
        private ListedProductFinderInterface $listedProductFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BuyerNotRegisteredException
     * @throws BuyerErasureRequestedException
     * @throws BuyerAddressesNotCompletedException
     * @throws CartOutdatedException
     * @throws CartNotFoundException
     * @throws CartAlreadyConvertedException
     * @throws CartEmptyException
     * @throws CartAlreadyExistsException
     */
    public function checkout(string $cartId): CheckoutResult
    {
        $cart = $this->repository->load(CartId::fromString($cartId));

        $buyer = $this->buyerFinder->ofIdOrNull($cart->buyerId)
            ?? throw BuyerNotRegisteredException::forId($cart->buyerId);

        if ($buyer->erasureRequested) {
            throw BuyerErasureRequestedException::forId($cart->buyerId);
        }

        if (null === $buyer->billingAddress) {
            throw BuyerAddressesNotCompletedException::forId($cart->buyerId);
        }

        $this->guardFreshness($cart->lines());

        $cart->checkout($this->clock->now());
        $this->repository->save($cart);

        return new CheckoutResult(
            cartId: $cartId,
            totalAmountInCents: $cart->totalAmountInCents(),
            billingAddress: $this->toPostalAddress($buyer->billingAddress),
        );
    }

    /**
     * @param list<Line> $lines
     *
     * @throws CartOutdatedException
     */
    private function guardFreshness(array $lines): void
    {
        $currentProducts = iterator_to_array($this->listedProductFinder->byIds(
            ...array_map(static fn (Line $line): string => $line->product->id, $lines),
        )->indexBy(static fn (ListedProductResult $result): string => $result->productId));

        foreach ($lines as $line) {
            $current = $currentProducts[$line->product->id] ?? null;
            if (!$current instanceof ListedProductResult || $current->unitPriceInCents !== $line->product->price->cents) {
                throw CartOutdatedException::forProduct($line->product->id);
            }
        }
    }

    private function toPostalAddress(PostalAddressResult $address): PostalAddress
    {
        return PostalAddress::of(
            $address->recipientName,
            Address::of($address->address->street, $address->address->postalCode, $address->address->city, $address->address->countryCode),
        );
    }
}
