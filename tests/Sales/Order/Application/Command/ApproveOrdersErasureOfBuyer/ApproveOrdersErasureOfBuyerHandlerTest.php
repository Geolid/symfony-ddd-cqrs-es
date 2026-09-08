<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\ApproveOrdersErasureOfBuyer;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\ApproveOrdersErasureOfBuyer\ApproveOrdersErasureOfBuyer;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;

final class ApproveOrdersErasureOfBuyerHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itApproves(): void
    {
        // Given
        $otherBuyerId = Uuid::uuid7()->toString();
        $other = OrderBuilder::new()->withBuyerId($otherBuyerId)->create();

        $buyerId = Uuid::uuid7()->toString();
        $placed = OrderBuilder::new()->withBuyerId($buyerId)->create();
        $delivered = OrderBuilder::new()->withBuyerId($buyerId)->confirmed()->prepared()->dispatched()->delivered()->create();
        $this->store($other, $placed, $delivered);

        // When
        $this->dispatch(new ApproveOrdersErasureOfBuyer($buyerId));

        // Then
        $statusesById = [];
        foreach ($this->finder->byBuyer($buyerId) as $result) {
            $statusesById[$result->id] = $result->erasureStatus;
        }
        self::assertSame(ErasureStatus::APPROVED, $statusesById[$placed->id->toString()]);
        self::assertSame(ErasureStatus::ERASED, $statusesById[$delivered->id->toString()]);

        $otherResult = $this->finder->ofId($other->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $otherResult->erasureStatus);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $otherBuyerId = Uuid::uuid7()->toString();
        $other = OrderBuilder::new()->withBuyerId($otherBuyerId)->create();
        $this->store($other);

        // When
        $this->dispatch(new ApproveOrdersErasureOfBuyer($buyerId));

        // Then
        $otherResult = $this->finder->ofId($other->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $otherResult->erasureStatus);
    }
}
