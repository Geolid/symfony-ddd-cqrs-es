<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Cart;

use Psr\Clock\ClockInterface;
use Shared\Application\ErasureStatus;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\Cart\Exception\CartOutdatedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperAddressesNotCompletedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperErasureRequestedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperNotRegisteredException;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductResult;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Domain\Cart\Entity\Line;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyConvertedException;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyExistsException;
use Shopping\Checkout\Domain\Cart\Exception\CartEmptyException;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;

final readonly class SubmitCart implements SubmitCartInterface
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
    public function submit(string $cartId): SubmitCartResult
    {
        $cart = $this->repository->load(CartId::fromString($cartId));

        $shopper = $this->shopperFinder->ofIdOrNull($cart->shopperId)
            ?? throw ShopperNotRegisteredException::forId($cart->shopperId);

        if (ErasureStatus::REQUESTED === $shopper->erasureStatus) {
            throw ShopperErasureRequestedException::forId($cart->shopperId);
        }

        if (null === $shopper->billingAddress) {
            throw ShopperAddressesNotCompletedException::forId($cart->shopperId);
        }

        $this->guardFreshness($cart->lines());

        $cart->checkout($this->clock->now());
        $this->repository->save($cart);

        return new SubmitCartResult(
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
