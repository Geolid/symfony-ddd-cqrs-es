<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\ApproveOrderErasure;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\ApproveOrderErasure\ApproveOrderErasure;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Domain\Exception\OrderNotFoundException;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;

final class ApproveOrderErasureHandlerTest extends AbstractIntegrationTestCase
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
        $order = OrderBuilder::new()->create();
        $this->store($order);

        // When
        $this->dispatch(new ApproveOrderErasure($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(ErasureStatus::APPROVED, $result->erasureStatus);
    }

    #[Test]
    public function itApprovesAndErasesWhenAlreadyDelivered(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $this->store($order);

        // When
        $this->dispatch(new ApproveOrderErasure($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(ErasureStatus::ERASED, $result->erasureStatus);
    }

    #[Test]
    public function itIgnoresWhenAlreadyApproved(): void
    {
        // Given
        $order = OrderBuilder::new()->erasureApproved()->create();
        $this->store($order);

        // When
        $this->dispatch(new ApproveOrderErasure($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(ErasureStatus::APPROVED, $result->erasureStatus);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new ApproveOrderErasure(Uuid::uuid7()->toString()));
    }
}
