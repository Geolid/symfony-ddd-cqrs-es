<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\CheckoutSessionOpening;

use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\ErasureStatus;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CartPricesStaleException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\ShopperAddressesNotCompletedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\ShopperErasureRequestedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\ShopperNotRegisteredException;
use Shopping\Checkout\Application\Command\OpenCheckoutSession\OpenCheckoutSession;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;
use Shopping\Checkout\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductResult;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Domain\CheckoutSession\CheckoutSession;

final readonly class CheckoutSessionOpener implements CheckoutSessionOpenerInterface
{
    public function __construct(
        private CartFinderInterface $cartFinder,
        private ShopperFinderInterface $shopperFinder,
        private ListedProductFinderInterface $listedProductFinder,
        private CommandBusInterface $commandBus,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShopperNotRegisteredException
     * @throws ShopperErasureRequestedException
     * @throws ShopperAddressesNotCompletedException
     * @throws CartPricesStaleException
     * @throws CartResultNotFoundException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function openFor(string $cartId): OpenedCheckoutSession
    {
        $cart = $this->cartFinder->ofId($cartId);

        $shopper = $this->shopperFinder->ofIdOrNull($cart->shopperId)
            ?? throw ShopperNotRegisteredException::forId($cart->shopperId);

        if (ErasureStatus::REQUESTED === $shopper->erasureStatus) {
            throw ShopperErasureRequestedException::forId($cart->shopperId);
        }

        if (null === $shopper->shippingAddress || null === $shopper->billingAddress) {
            throw ShopperAddressesNotCompletedException::forId($cart->shopperId);
        }

        $this->guardPricesNotStale($cart->lines);

        $shippingAddress = PostalAddressMapper::fromArray([
            'recipientName' => $shopper->shippingAddress->recipientName,
            'address' => (array) $shopper->shippingAddress->address,
        ]);
        $billingAddress = PostalAddressMapper::fromArray([
            'recipientName' => $shopper->billingAddress->recipientName,
            'address' => (array) $shopper->billingAddress->address,
        ]);
        $checkoutSessionId = Uuid::uuid7()->toString();
        $now = $this->clock->now();

        $this->commandBus->dispatch(new OpenCheckoutSession(
            id: $checkoutSessionId,
            cartId: $cartId,
            shopperId: $cart->shopperId,
            lines: $cart->lines,
            shippingAddress: PostalAddressMapper::toArray($shippingAddress),
            billingAddress: PostalAddressMapper::toArray($billingAddress),
            totalAmountInCents: $cart->totalAmountInCents,
            openedAt: $now,
        ));

        return new OpenedCheckoutSession(
            checkoutSessionId: $checkoutSessionId,
            totalAmountInCents: $cart->totalAmountInCents,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            expiresAt: $now->modify(\sprintf('+%d minutes', CheckoutSession::TTL_MINUTES)),
        );
    }

    /**
     * @param list<array{lineId: string, productId: string, label: string, unitPriceInCents: int, quantity: int}> $lines
     *
     * @throws CartPricesStaleException
     */
    private function guardPricesNotStale(array $lines): void
    {
        $currentProducts = iterator_to_array($this->listedProductFinder->byIds(
            ...array_map(static fn (array $line): string => $line['productId'], $lines),
        )->indexBy(static fn (ListedProductResult $result): string => $result->productId));

        foreach ($lines as $line) {
            $current = $currentProducts[$line['productId']] ?? null;
            if (!$current instanceof ListedProductResult || $current->unitPriceInCents !== $line['unitPriceInCents']) {
                throw CartPricesStaleException::forProduct($line['productId']);
            }
        }
    }
}
