<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Support\Factory;

use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Domain\ValueObject\Money;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     orderId: string,
 *     amountInCents: int,
 *     reference: string,
 *     checkoutUrl: string,
 *     requestedAt: \DateTimeInterface,
 * }
 *
 * @extends AbstractAggregateTestFactory<OrderPayment, Attributes>
 */
final class OrderPaymentTestFactory extends AbstractAggregateTestFactory
{
    public function withOrderId(string $orderId): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['orderId' => $orderId]));
    }

    public function withAmountInCents(int $amountInCents): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['amountInCents' => $amountInCents]));
    }

    public function withReference(string $reference): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['reference' => $reference]));
    }

    public function withCheckoutUrl(string $checkoutUrl): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['checkoutUrl' => $checkoutUrl]));
    }

    public function withRequestedAt(\DateTimeImmutable $requestedAt): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['requestedAt' => $requestedAt]));
    }

    public function authorized(?\DateTimeImmutable $authorizedAt = null): self
    {
        $authorizedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->authorize($authorizedAt));
    }

    public function failed(?\DateTimeImmutable $failedAt = null): self
    {
        $failedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->fail($failedAt));
    }

    public function captured(?\DateTimeImmutable $capturedAt = null): self
    {
        $capturedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->capture($capturedAt));
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $cancelledAt ??= Clock::get()->now();

        return $this->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($cancelledAt));
    }

    public function refundInitiated(?\DateTimeImmutable $initiatedAt = null): self
    {
        $initiatedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->initiateRefund($initiatedAt));
    }

    public function refundConfirmed(?\DateTimeImmutable $refundedAt = null): self
    {
        $refundedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->confirmRefund($refundedAt));
    }

    protected function defaults(): array
    {
        return [
            'orderId' => Uuid::uuid7()->toString(),
            'amountInCents' => self::faker()->numberBetween(500, 5_000),
            'reference' => 'GLBX-'.self::faker()->bothify('????????'),
            'checkoutUrl' => 'https://fake-checkout.test/?ref='.self::faker()->bothify('????????'),
            'requestedAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): OrderPayment
    {
        Assert::stringNotEmpty($orderId = $attributes['orderId']);
        Assert::integer($amountInCents = $attributes['amountInCents']);
        Assert::stringNotEmpty($reference = $attributes['reference']);
        Assert::stringNotEmpty($checkoutUrl = $attributes['checkoutUrl']);
        Assert::isInstanceOf($requestedAt = $attributes['requestedAt'], \DateTimeInterface::class);

        return OrderPayment::request(
            OrderPaymentId::forOrder($orderId),
            $orderId,
            Money::fromCents($amountInCents),
            PaymentReference::fromString($reference),
            $checkoutUrl,
            \DateTimeImmutable::createFromInterface($requestedAt),
        );
    }
}
