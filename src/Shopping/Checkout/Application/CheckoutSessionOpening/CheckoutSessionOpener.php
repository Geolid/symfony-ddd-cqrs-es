<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\CheckoutSessionOpening;

use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\CountryCode;
use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;
use Shared\Domain\ValueObject\TaxedAmount;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerAddressesNotCompletedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerErasureRequestedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerNotRegisteredException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\ProductNotListedException;
use Shopping\Checkout\Application\Command\OpenCheckoutSession\OpenCheckoutSession;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;
use Shopping\Checkout\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Checkout\Application\Finder\CartItem\CartItemResult;
use Shopping\Checkout\Application\Finder\Customer\CustomerFinderInterface;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductResult;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
use Shopping\Checkout\Application\Tax\TaxRateResolverInterface;
use Shopping\Checkout\Domain\Specification\CheckoutSessionExpiredSpecification;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\ValueObject\TaxRate;

final readonly class CheckoutSessionOpener implements CheckoutSessionOpenerInterface
{
    public function __construct(
        private CartFinderInterface $cartFinder,
        private CartItemFinderInterface $cartItemFinder,
        private CustomerFinderInterface $customerFinder,
        private ListedProductFinderInterface $listedProductFinder,
        private TaxRateResolverInterface $taxRateResolver,
        private CommandBusInterface $commandBus,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CustomerNotRegisteredException
     * @throws CustomerErasureRequestedException
     * @throws CustomerAddressesNotCompletedException
     * @throws CartResultNotFoundException
     * @throws ProductNotListedException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function openFor(string $cartId): OpenedCheckoutSession
    {
        $cart = $this->cartFinder->ofId($cartId);
        $cartItems = iterator_to_array($this->cartItemFinder->byCart($cartId));

        $customer = $this->customerFinder->ofIdOrNull($cart->customerId)
            ?? throw CustomerNotRegisteredException::forId($cart->customerId);

        if ($customer->erasureStatus->isRequested()) {
            throw CustomerErasureRequestedException::forId($cart->customerId);
        }

        if (null === $customer->shippingAddress || null === $customer->billingAddress) {
            throw CustomerAddressesNotCompletedException::forId($cart->customerId);
        }

        $checkoutSessionId = Uuid::uuid7()->toString();
        $currency = Currency::EUR;
        $taxRate = $this->taxRateResolver->resolve(CountryCode::from($customer->shippingAddress->address->countryCode));
        $items = $this->assembleItems($cartItems, $currency, $taxRate);
        $total = array_reduce(
            $items,
            static fn (TaxedAmount $carry, CheckoutItem $item): TaxedAmount => $carry->plus($item->total()),
            TaxedAmount::zero($currency),
        );

        $shippingAddress = PostalAddressMapper::fromArray([
            'recipientName' => $customer->shippingAddress->recipientName,
            'address' => (array) $customer->shippingAddress->address,
        ]);
        $billingAddress = PostalAddressMapper::fromArray([
            'recipientName' => $customer->billingAddress->recipientName,
            'address' => (array) $customer->billingAddress->address,
        ]);
        $now = $this->clock->now();

        $this->commandBus->dispatch(new OpenCheckoutSession(
            id: $checkoutSessionId,
            cartId: $cartId,
            customerId: $cart->customerId,
            lines: array_map(CheckoutItemMapper::toArray(...), $items),
            currency: $currency->value,
            taxRateBasisPoints: $taxRate->basisPoints,
            shippingAddress: PostalAddressMapper::toArray($shippingAddress),
            billingAddress: PostalAddressMapper::toArray($billingAddress),
            openedAt: $now,
        ));

        return new OpenedCheckoutSession(
            checkoutSessionId: $checkoutSessionId,
            total: $total,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            expiresAt: $now->modify(\sprintf('+%d minutes', CheckoutSessionExpiredSpecification::TTL_MINUTES)),
        );
    }

    /**
     * @param array<CartItemResult> $cartItems
     *
     * @return list<CheckoutItem>
     *
     * @throws ProductNotListedException
     */
    private function assembleItems(array $cartItems, Currency $currency, TaxRate $taxRate): array
    {
        $listedProducts = iterator_to_array($this->listedProductFinder->byIds(
            ...array_map(static fn (CartItemResult $item): string => $item->productId, $cartItems),
        )->indexBy(static fn (ListedProductResult $result): string => $result->productId));

        $items = [];
        foreach ($cartItems as $cartItem) {
            $listedProduct = $listedProducts[$cartItem->productId] ?? throw ProductNotListedException::forId($cartItem->productId);

            $items[] = CheckoutItem::of(
                $cartItem->productId,
                Label::fromString($listedProduct->label),
                Money::fromCents($listedProduct->unitPriceInCents, $currency->value),
                Quantity::of($cartItem->quantity),
                $taxRate,
            );
        }

        return $items;
    }
}
