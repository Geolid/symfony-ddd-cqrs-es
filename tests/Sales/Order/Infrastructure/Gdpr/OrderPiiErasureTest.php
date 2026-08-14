<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Event\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
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
        self::assertSame(self::erasedAddress(), $rehydrated->shippingAddress);
        self::assertNotSame('erased', $rehydrated->billingAddress['street']);
    }

    #[Test]
    public function itCryptoShredsTheBillingAddressOnBillingRetentionExpiry(): void
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
        self::assertSame(self::erasedAddress(), $rehydrated->billingAddress);
        self::assertNotSame('erased', $rehydrated->shippingAddress['street']);
    }

    #[Test]
    public function itCryptoShredsThePaymentCapturedShippingAddressOnCustomerErasure(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->store();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->captured()->create();
        $this->store($orderPayment);
        $serialized = $this->serializedEventOf(
            OrderPaymentCapturedIntegrationEvent::class,
            static fn (OrderPaymentCapturedIntegrationEvent $event): bool => $event->orderId === $order->id()->toString(),
        );

        // When
        $this->service(DataSubjectEraser::class)->onEvent(
            Message::create(new DummyDataSubjectErased($customerId)),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(OrderPaymentCapturedIntegrationEvent::class, $rehydrated);
        self::assertSame(self::erasedAddress(), $rehydrated->shippingAddress);
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string}
     */
    private static function erasedAddress(): array
    {
        return ['firstName' => 'erased', 'lastName' => 'erased', 'street' => 'erased', 'postalCode' => '00000', 'city' => 'erased'];
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
