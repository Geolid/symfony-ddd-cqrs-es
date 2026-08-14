<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;
use Shared\Infrastructure\Gdpr\DataSubjectEraser;
use Support\AbstractIntegrationTestCase;

final class OrderPiiErasureTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCryptoShredsTheShippingAddressOnCustomerErasure(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()
            ->withCustomerId($customerId)
            ->store();
        $serialized = $this->serializedEventOf(
            OrderPlaced::class,
            static fn (OrderPlaced $event): bool => $event->id === $order->id()->toString(),
        );

        // When
        $this->service(DataSubjectEraser::class)->onEvent(
            Message::create(new DummyDataSubjectErased($customerId)),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(OrderPlaced::class, $rehydrated);
        self::assertSame('Erased', $rehydrated->shippingStreet);
        self::assertNotSame('Erased', $rehydrated->billingStreet);
    }

    #[Test]
    public function itCryptoShredsOnlyTheBillingAddressOnBillingRetentionExpiry(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $serialized = $this->serializedEventOf(
            OrderPlaced::class,
            static fn (OrderPlaced $event): bool => $event->id === $order->id()->toString(),
        );

        // When
        $this->service(DataSubjectEraser::class)->onEvent(
            Message::create(new DummyDataSubjectErased($order->id()->toString())),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(OrderPlaced::class, $rehydrated);
        self::assertSame('Erased', $rehydrated->billingStreet);
        self::assertNotSame('Erased', $rehydrated->shippingStreet);
    }
}

final readonly class DummyDataSubjectErased implements DataSubjectErasureInterface
{
    public function __construct(private string $customerId)
    {
    }

    public function subjectId(): string
    {
        return $this->customerId;
    }
}
