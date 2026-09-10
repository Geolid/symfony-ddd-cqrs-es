<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\Policy\ApproveOrdersErasureOnShopperErased;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Shared\Application\ErasureStatus;
use Shopping\Checkout\Application\IntegrationEvent\ShopperErased\ShopperErasedIntegrationEvent;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ApproveOrdersErasureOnShopperErasedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itApproves(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $shopperId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withShopperId($shopperId)->create();
        $this->store($other, $order);

        // When
        $this->trigger(ApproveOrdersErasureOnShopperErased::class, new ShopperErasedIntegrationEvent($shopperId, Clock::get()->now()));

        // Then
        $finder = $this->service(OrderFinderInterface::class);
        $results = iterator_to_array($finder->byShopper($shopperId), false);
        self::assertSame($order->id->toString(), $results[0]->id);
        self::assertSame(ErasureStatus::APPROVED, $results[0]->erasureStatus);

        $otherResult = $finder->ofId($other->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $otherResult->erasureStatus);
    }
}
