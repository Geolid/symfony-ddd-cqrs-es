<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Checkout;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Application\Checkout\Exception\CartOutdatedException;
use Sales\Ordering\Application\Checkout\Exception\ShopperAddressesNotCompletedException;
use Sales\Ordering\Application\Checkout\Exception\ShopperErasureRequestedException;
use Sales\Ordering\Application\Checkout\Exception\ShopperNotRegisteredException;
use Sales\Ordering\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Sales\Ordering\Application\Finder\ListedProduct\ListedProductResult;
use Sales\Ordering\Application\Finder\Shopper\ShopperFinderInterface;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyConvertedException;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyExistsException;
use Sales\Ordering\Domain\Cart\Exception\CartEmptyException;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Sales\Ordering\Domain\Shared\Entity\Line;
use Shared\Application\Mapper\PostalAddressMapper;

final readonly class Checkout implements CheckoutInterface
{
    public function __construct(
        private CartRepositoryInterface $repository,
        private ShopperFinderInterface $shopperFinder,
        private ListedProductFinderInterface $listedProductFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShopperNotRegisteredException
     * @throws ShopperErasureRequestedException
     * @throws ShopperAddressesNotCompletedException
     * @throws CartOutdatedException
     * @throws CartNotFoundException
     * @throws CartAlreadyConvertedException
     * @throws CartEmptyException
     * @throws CartAlreadyExistsException
     */
    public function checkout(string $cartId): CheckoutResult
    {
        $cart = $this->repository->load(CartId::fromString($cartId));

        $shopper = $this->shopperFinder->ofIdOrNull($cart->shopperId)
            ?? throw ShopperNotRegisteredException::forId($cart->shopperId);

        if ($shopper->erasureRequested) {
            throw ShopperErasureRequestedException::forId($cart->shopperId);
        }

        if (null === $shopper->billingAddress) {
            throw ShopperAddressesNotCompletedException::forId($cart->shopperId);
        }

        $this->guardFreshness($cart->lines());

        $cart->checkout($this->clock->now());
        $this->repository->save($cart);

        return new CheckoutResult(
            cartId: $cartId,
            totalAmountInCents: $cart->totalAmountInCents(),
            billingAddress: PostalAddressMapper::fromArray([
                'recipientName' => $shopper->billingAddress->recipientName,
                'address' => (array) $shopper->billingAddress->address,
            ]),
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
}
