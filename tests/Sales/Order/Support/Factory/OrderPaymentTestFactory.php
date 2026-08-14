<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Support\Factory;

use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Domain\ValueObject\Money;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<OrderPayment>
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

    public function captured(\DateTimeImmutable $capturedAt = new \DateTimeImmutable('now +00:00')): self
    {
        return $this->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->capture($capturedAt));
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
