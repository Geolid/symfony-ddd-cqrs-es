<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderPaymentCaptured;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderPaymentCaptured\OrderPaymentCapturedIntegrationEvent;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class OrderPaymentCapturedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->create();
        $this->store($order);
        $now = Clock::get()->now();
        $orderPayment = OrderPaymentBuilder::new()
            ->withOrderId($order->id->toString())
            ->authorized()
            ->captured($now)
            ->create();

        // When
        $this->store($orderPayment);

        // Then
        $event = $this->publishedEventOf(OrderPaymentCapturedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($customerId, $event->customerId);
        self::assertSame($this->address($order->shippingAddress), $event->shippingAddress);
        self::assertSame($now->format(\DateTimeImmutable::ATOM), $event->capturedAt->format(\DateTimeImmutable::ATOM));
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function address(PostalAddress $address): array
    {
        return [
            'firstName' => $address->fullName->firstName,
            'lastName' => $address->fullName->lastName,
            'street' => $address->address->street,
            'postalCode' => $address->address->postalCode,
            'city' => $address->address->city,
            'countryCode' => $address->address->countryCode->value,
        ];
    }
}
