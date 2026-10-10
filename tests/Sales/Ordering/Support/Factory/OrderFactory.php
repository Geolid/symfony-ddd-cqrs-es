<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Support\Factory;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Order\ValueObject\OrderItem;
use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Tests\Support\Factory\MoneyFactory;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

use function Zenstruck\Foundry\faker;

/**
 * @phpstan-type Inputs = array{
 *     id: OrderId,
 *     cartId: string,
 *     customerId: string,
 *     checkoutSessionId: string,
 *     shippingAddress: PostalAddress,
 *     items: list<OrderItem>,
 *     currency: Currency,
 *     confirmedAt: \DateTimeImmutable,
 *     preparedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     failedAt: \DateTimeImmutable,
 *     dispatchedAt: \DateTimeImmutable,
 *     deliveredAt: \DateTimeImmutable,
 *     erasureApprovedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<Order, Inputs>
 */
final class OrderFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return Order::class;
    }

    public function withCartId(string $cartId): self
    {
        return $this->with(['cartId' => $cartId]);
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->with(['customerId' => $customerId]);
    }

    public function withCheckoutSessionId(string $checkoutSessionId): self
    {
        return $this->with(['checkoutSessionId' => $checkoutSessionId]);
    }

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->with(['shippingAddress' => $shippingAddress]);
    }

    /**
     * @param list<OrderItem> $items
     */
    public function withItems(array $items): self
    {
        return $this->with(['items' => $items]);
    }

    public function withCurrency(Currency $currency): self
    {
        return $this->with(['currency' => $currency]);
    }

    public function withConfirmedAt(\DateTimeImmutable $confirmedAt): self
    {
        return $this->with(['confirmedAt' => $confirmedAt]);
    }

    public function prepared(?\DateTimeImmutable $preparedAt = null): self
    {
        return $this->with(array_filter(['preparedAt' => $preparedAt]))->transition(
            static function (Order $order, array $inputs): void {
                $order->prepare($inputs['preparedAt']);
            },
        );
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        return $this->with(array_filter(['cancelledAt' => $cancelledAt]))->transition(
            static function (Order $order, array $inputs): void {
                $order->cancel($inputs['customerId'], $inputs['cancelledAt']);
            },
        );
    }

    public function failed(?\DateTimeImmutable $failedAt = null): self
    {
        return $this->with(array_filter(['failedAt' => $failedAt]))->transition(
            static function (Order $order, array $inputs): void {
                $order->fail($inputs['failedAt']);
            },
        );
    }

    public function dispatched(?\DateTimeImmutable $dispatchedAt = null): self
    {
        return $this->with(array_filter(['dispatchedAt' => $dispatchedAt]))->transition(
            static function (Order $order, array $inputs): void {
                $order->dispatch($inputs['dispatchedAt']);
            },
        );
    }

    public function delivered(?\DateTimeImmutable $deliveredAt = null): self
    {
        return $this->with(array_filter(['deliveredAt' => $deliveredAt]))->transition(
            static function (Order $order, array $inputs): void {
                $order->deliver($inputs['deliveredAt']);
            },
        );
    }

    public function erasureApproved(?\DateTimeImmutable $erasureApprovedAt = null): self
    {
        return $this->with(array_filter(['erasureApprovedAt' => $erasureApprovedAt]))->transition(
            static function (Order $order, array $inputs): void {
                $order->approveErasure($inputs['erasureApprovedAt']);
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return Order::confirm(
            id: $parameters['id'],
            cartId: $parameters['cartId'],
            customerId: $parameters['customerId'],
            checkoutSessionId: $parameters['checkoutSessionId'],
            shippingAddress: $parameters['shippingAddress'],
            items: $parameters['items'],
            currency: $parameters['currency'],
            confirmedAt: $parameters['confirmedAt'],
        );
    }

    protected function initialize(): static
    {
        return parent::initialize()->beforeInstantiate(static function (array $parameters): array {
            Assert::string($parameters['checkoutSessionId']);
            Assert::isInstanceOf($parameters['currency'], Currency::class);
            $parameters['id'] ??= OrderId::forCheckoutSession($parameters['checkoutSessionId']);
            $parameters['items'] ??= OrderItemFactory::new([
                'product' => ProductFactory::new(['price' => MoneyFactory::new(['currency' => $parameters['currency']->value])]),
                'taxAmount' => MoneyFactory::new(['cents' => faker()->numberBetween(50, 500), 'currency' => $parameters['currency']->value]),
            ])->many(faker()->numberBetween(1, 3))->create();

            return $parameters;
        });
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'cartId' => Uuid::uuid7()->toString(),
            'customerId' => Uuid::uuid7()->toString(),
            'checkoutSessionId' => Uuid::uuid7()->toString(),
            'shippingAddress' => PostalAddressFactory::new(),
            'currency' => Currency::EUR,
            'confirmedAt' => $now,
            'preparedAt' => $now->modify('+1 day'),
            'cancelledAt' => $now->modify('+1 day'),
            'failedAt' => $now->modify('+1 day'),
            'dispatchedAt' => $now->modify('+2 day'),
            'deliveredAt' => $now->modify('+3 day'),
            'erasureApprovedAt' => $now->modify('+4 day'),
        ];
    }
}
