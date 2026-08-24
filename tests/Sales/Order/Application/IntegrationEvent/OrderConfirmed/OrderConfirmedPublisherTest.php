<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderConfirmed;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class OrderConfirmedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->confirmed()->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOfType(OrderConfirmedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($customerId, $event->customerId);
        self::assertSame($this->address($order->shippingAddress), $event->shippingAddress);
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string}
     */
    private function address(PostalAddress $address): array
    {
        return [
            'firstName' => $address->fullName->firstName,
            'lastName' => $address->fullName->lastName,
            'street' => $address->address->street,
            'postalCode' => $address->address->postalCode,
            'city' => $address->address->city,
        ];
    }
}
