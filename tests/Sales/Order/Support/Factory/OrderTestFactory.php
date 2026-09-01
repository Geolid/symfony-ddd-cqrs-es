<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Support\Factory;

use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;
use Support\ClockSequence;
use Support\Factory\AbstractAggregateTestFactory;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: OrderId,
 *     customerId: string,
 *     shippingAddress: PostalAddress,
 *     billingAddress: PostalAddress,
 *     lines: list<OrderLine>,
 *     placedAt: \DateTimeImmutable,
 *     returnRejectionReason?: string,
 *     cancelledAt?: \DateTimeImmutable,
 *     confirmedAt?: \DateTimeImmutable,
 *     dispatchedAt?: \DateTimeImmutable,
 *     deliveredAt?: \DateTimeImmutable,
 *     completedAt?: \DateTimeImmutable,
 *     returnRequestedAt?: \DateTimeImmutable,
 *     returnedAt?: \DateTimeImmutable,
 *     returnRejectedAt?: \DateTimeImmutable,
 *     anonymizedAt?: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateTestFactory<Order, Attributes>
 */
final class OrderTestFactory extends AbstractAggregateTestFactory
{
    public function withId(string $id): self
    {
        return $this->withAttributes(['id' => OrderId::fromString($id)]);
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->withAttributes(['customerId' => $customerId]);
    }

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->withAttributes(['shippingAddress' => $shippingAddress]);
    }

    public function withBillingAddress(PostalAddress $billingAddress): self
    {
        return $this->withAttributes(['billingAddress' => $billingAddress]);
    }

    /**
     * @param list<OrderLine> $lines
     */
    public function withLines(array $lines): self
    {
        return $this->withAttributes(['lines' => $lines]);
    }

    public function withTotalAmountInCents(int $totalAmountInCents): self
    {
        return $this->withLines([OrderLine::of(
            Product::of(Uuid::uuid7()->toString(), Label::fromString('Assorted goods'), Money::fromCents($totalAmountInCents)),
            1,
        )]);
    }

    public function withPlacedAt(\DateTimeImmutable $placedAt): self
    {
        return $this->withAttributes(['placedAt' => $placedAt]);
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $cancelledAt ??= Clock::get()->now();

        return $this->withAttributes(['cancelledAt' => $cancelledAt])
            ->withModifier(static function (Order $order, array $attributes) use ($cancelledAt): void {
                $order->cancel($attributes['customerId'], $cancelledAt);
            });
    }

    public function confirmed(?\DateTimeImmutable $confirmedAt = null): self
    {
        $confirmedAt ??= Clock::get()->now();

        return $this->withAttributes(['confirmedAt' => $confirmedAt])
            ->withModifier(static fn (Order $order) => $order->confirm($confirmedAt));
    }

    public function dispatched(?\DateTimeImmutable $dispatchedAt = null): self
    {
        $dispatchedAt ??= Clock::get()->now();

        return $this->withAttributes(['dispatchedAt' => $dispatchedAt])
            ->withModifier(static fn (Order $order) => $order->dispatch($dispatchedAt));
    }

    public function delivered(?\DateTimeImmutable $deliveredAt = null): self
    {
        $deliveredAt ??= Clock::get()->now();

        return $this->withAttributes(['deliveredAt' => $deliveredAt])
            ->withModifier(static fn (Order $order) => $order->deliver($deliveredAt));
    }

    public function completed(?\DateTimeImmutable $now = null): self
    {
        $now ??= Clock::get()->now();

        return $this->withAttributes(['completedAt' => $now])
            ->withModifier(static fn (Order $order) => $order->complete($now));
    }

    public function returnRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        $requestedAt ??= Clock::get()->now();

        return $this->withAttributes(['returnRequestedAt' => $requestedAt])
            ->withModifier(static function (Order $order, array $attributes) use ($requestedAt): void {
                $order->requestReturn($attributes['customerId'], $requestedAt);
            });
    }

    public function returned(?\DateTimeImmutable $returnedAt = null): self
    {
        $returnedAt ??= Clock::get()->now();

        return $this->withAttributes(['returnedAt' => $returnedAt])
            ->withModifier(static fn (Order $order) => $order->confirmReturn($returnedAt));
    }

    public function returnRejected(
        ?string $reason = null,
        ?\DateTimeImmutable $rejectedAt = null,
    ): self {
        $reason ??= SeededFaker::get()->sentence(4);
        $rejectedAt ??= Clock::get()->now();

        return $this->withAttributes(['returnRejectionReason' => $reason, 'returnRejectedAt' => $rejectedAt])
            ->withModifier(static fn (Order $order) => $order->rejectReturn($reason, $rejectedAt));
    }

    public function anonymized(?\DateTimeImmutable $now = null): self
    {
        $now ??= Clock::get()->now();

        return $this->withAttributes(['anonymizedAt' => $now])
            ->withModifier(static fn (Order $order) => $order->anonymize($now));
    }

    protected function defaults(): array
    {
        return [
            'id' => OrderId::generate(...),
            'customerId' => static fn (): string => Uuid::uuid7()->toString(),
            'shippingAddress' => static fn (): PostalAddress => PostalAddress::of(
                FullName::of(SeededFaker::get()->firstName(), SeededFaker::get()->lastName()),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'billingAddress' => static fn (): PostalAddress => PostalAddress::of(
                FullName::of(SeededFaker::get()->firstName(), SeededFaker::get()->lastName()),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'lines' => static fn (): array => [OrderLine::of(
                Product::of(Uuid::uuid7()->toString(), Label::fromString(SeededFaker::get()->sentence(3)), Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000))),
                SeededFaker::get()->numberBetween(1, 5),
            )],
            'placedAt' => ClockSequence::next(...),
        ];
    }

    protected function build(): Order
    {
        return Order::place(
            id: $this->attribute('id'),
            customerId: $this->attribute('customerId'),
            shippingAddress: $this->attribute('shippingAddress'),
            billingAddress: $this->attribute('billingAddress'),
            lines: $this->attribute('lines'),
            placedAt: $this->attribute('placedAt'),
        );
    }
}
