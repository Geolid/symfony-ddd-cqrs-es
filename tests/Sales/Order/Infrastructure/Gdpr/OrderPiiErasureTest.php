<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Store\Store;
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
    public function itCryptoShredsTheBuyerAddressOnErasure(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()
            ->withCustomerId($customerId)
            ->withBuyerAddress('buyer@example.com')
            ->create();
        $this->store($order);

        // When
        $this->service(DataSubjectEraser::class)->onEvent(
            Message::create(new DummyDataSubjectErased($customerId)),
        );

        // Then
        self::assertNull($this->placedEventOf($order->id()->toString())->buyerAddress);
    }

    private function placedEventOf(string $id): OrderPlaced
    {
        foreach ($this->service(Store::class)->load() as $message) {
            $event = $message->event();

            if ($event instanceof OrderPlaced && $event->id === $id) {
                return $event;
            }
        }

        self::fail('OrderPlaced event not found in the stream.');
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
