<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\IntegrationEvent\BuyerErased\BuyerErasedIntegrationEvent;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\Policy\ApproveOrdersErasureOnBuyerErased;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ApproveOrdersErasureOnBuyerErasedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itApproves(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->create();
        $this->store($other, $order);

        // When
        $this->trigger(ApproveOrdersErasureOnBuyerErased::class, new BuyerErasedIntegrationEvent($buyerId, Clock::get()->now()));

        // Then
        $finder = $this->service(OrderFinderInterface::class);
        $results = iterator_to_array($finder->byBuyer($buyerId), false);
        self::assertSame($order->id->toString(), $results[0]->id);
        self::assertSame(ErasureStatus::APPROVED, $results[0]->erasureStatus);

        $otherResult = $finder->ofId($other->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $otherResult->erasureStatus);
    }
}
