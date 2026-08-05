<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Support\Factory;

use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
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
        return static::new(array_merge($this->attributes, ['orderId' => $orderId]));
    }

    public function withCustomerId(string $customerId): self
    {
        return static::new(array_merge($this->attributes, ['customerId' => $customerId]));
    }

    public function withBuyerAddress(?string $buyerAddress): self
    {
        return static::new(array_merge($this->attributes, ['buyerAddress' => $buyerAddress]));
    }

    public function withAmountInCents(int $amountInCents): self
    {
        return static::new(array_merge($this->attributes, ['amountInCents' => $amountInCents]));
    }

    public function withReference(string $reference): self
    {
        return static::new(array_merge($this->attributes, ['reference' => $reference]));
    }

    public function withCheckoutUrl(string $checkoutUrl): self
    {
        return static::new(array_merge($this->attributes, ['checkoutUrl' => $checkoutUrl]));
    }

    public function captured(): self
    {
        return $this->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->capture(new \DateTimeImmutable('now +00:00')));
    }

    protected function defaults(): array
    {
        return [
            'orderId' => Uuid::uuid7()->toString(),
            'customerId' => Uuid::uuid7()->toString(),
            'buyerAddress' => self::faker()->safeEmail(),
            'amountInCents' => self::faker()->numberBetween(500, 5_000),
            'reference' => 'GLBX-'.self::faker()->bothify('????????'),
            'checkoutUrl' => 'https://fake-checkout.test/?ref='.self::faker()->bothify('????????'),
            'requestedAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): OrderPayment
    {
        Assert::stringNotEmpty($orderId = $attributes['orderId']);
        Assert::stringNotEmpty($customerId = $attributes['customerId']);
        Assert::nullOrStringNotEmpty($buyerAddress = $attributes['buyerAddress']);
        Assert::integer($amountInCents = $attributes['amountInCents']);
        Assert::stringNotEmpty($reference = $attributes['reference']);
        Assert::stringNotEmpty($checkoutUrl = $attributes['checkoutUrl']);
        Assert::isInstanceOf($requestedAt = $attributes['requestedAt'], \DateTimeInterface::class);

        return OrderPayment::request(
            OrderPaymentId::forOrder($orderId),
            $orderId,
            $customerId,
            $buyerAddress,
            Money::fromCents($amountInCents),
            $reference,
            $checkoutUrl,
            \DateTimeImmutable::createFromInterface($requestedAt),
        );
    }
}
