<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\CheckoutSessionOpening;

use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerAddressesNotCompletedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerErasureRequestedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerNotRegisteredException;
use Shopping\Checkout\Application\Command\OpenCheckoutSession\OpenCheckoutSession;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;
use Shopping\Checkout\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Checkout\Application\Finder\CartItem\CartItemResult;
use Shopping\Checkout\Application\Finder\Customer\CustomerFinderInterface;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductResult;
use Shopping\Checkout\Domain\CheckoutSession\CheckoutSession;

final readonly class CheckoutSessionOpener implements CheckoutSessionOpenerInterface
{
    public function __construct(
        private CartFinderInterface $cartFinder,
        private CartItemFinderInterface $cartItemFinder,
        private CustomerFinderInterface $customerFinder,
        private ListedProductFinderInterface $listedProductFinder,
        private CommandBusInterface $commandBus,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CustomerNotRegisteredException
     * @throws CustomerErasureRequestedException
     * @throws CustomerAddressesNotCompletedException
     * @throws CartResultNotFoundException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function openFor(string $cartId): OpenedCheckoutSession
    {
        $cart = $this->cartFinder->ofId($cartId);
        $items = iterator_to_array($this->cartItemFinder->byCart($cartId));

        $customer = $this->customerFinder->ofIdOrNull($cart->customerId)
            ?? throw CustomerNotRegisteredException::forId($cart->customerId);

        if ($customer->erasureStatus->isRequested()) {
            throw CustomerErasureRequestedException::forId($cart->customerId);
        }

        if (null === $customer->shippingAddress || null === $customer->billingAddress) {
            throw CustomerAddressesNotCompletedException::forId($cart->customerId);
        }

        [$lines, $totalAmountInCents] = $this->resolveLines($items);

        $shippingAddress = PostalAddressMapper::fromArray([
            'recipientName' => $customer->shippingAddress->recipientName,
            'address' => (array) $customer->shippingAddress->address,
        ]);
        $billingAddress = PostalAddressMapper::fromArray([
            'recipientName' => $customer->billingAddress->recipientName,
            'address' => (array) $customer->billingAddress->address,
        ]);
        $checkoutSessionId = Uuid::uuid7()->toString();
        $now = $this->clock->now();

        $this->commandBus->dispatch(new OpenCheckoutSession(
            id: $checkoutSessionId,
            cartId: $cartId,
            customerId: $cart->customerId,
            lines: $lines,
            shippingAddress: PostalAddressMapper::toArray($shippingAddress),
            billingAddress: PostalAddressMapper::toArray($billingAddress),
            openedAt: $now,
        ));

        return new OpenedCheckoutSession(
            checkoutSessionId: $checkoutSessionId,
            totalAmountInCents: $totalAmountInCents,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            expiresAt: $now->modify(\sprintf('+%d minutes', CheckoutSession::TTL_MINUTES)),
        );
    }

    /**
     * @param array<CartItemResult> $items
     *
     * @return array{0: list<array{productId: string, label: string, unitPriceInCents: int, quantity: int}>, 1: int}
     */
    private function resolveLines(array $items): array
    {
        $listedProducts = iterator_to_array($this->listedProductFinder->byIds(
            ...array_map(static fn (CartItemResult $item): string => $item->productId, $items),
        )->indexBy(static fn (ListedProductResult $result): string => $result->productId));

        $lines = [];
        $totalAmountInCents = 0;
        foreach ($items as $item) {
            $listedProduct = $listedProducts[$item->productId] ?? null;
            if (!$listedProduct instanceof ListedProductResult) {
                continue;
            }

            $lines[] = [
                'productId' => $item->productId,
                'label' => $listedProduct->label,
                'unitPriceInCents' => $listedProduct->unitPriceInCents,
                'quantity' => $item->quantity,
            ];
            $totalAmountInCents += $listedProduct->unitPriceInCents * $item->quantity;
        }

        return [$lines, $totalAmountInCents];
    }
}
