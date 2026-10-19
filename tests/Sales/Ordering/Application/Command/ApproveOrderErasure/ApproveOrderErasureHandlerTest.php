<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\ApproveOrderErasure;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\Command\ApproveOrderErasure\ApproveOrderErasure;
use Sales\Ordering\Application\Finder\Order\Exception\OrderResultNotFoundException;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Sales\Tests\Ordering\Support\Factory\OrderIdFactory;
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
        $order = OrderFactory::new()->create();
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
        $order = OrderFactory::new()->prepared()->paid()->dispatched()->delivered()->create();
        $this->store($order);

        // When
        $this->dispatch(new ApproveOrderErasure($order->id->toString()));

        // Then
        $this->expectException(OrderResultNotFoundException::class);

        $this->finder->ofId($order->id->toString());
    }

    #[Test]
    public function itIgnoresWhenAlreadyApproved(): void
    {
        // Given
        $order = OrderFactory::new()->erasureApproved()->create();
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
        // Given
        $unknownId = OrderIdFactory::new()->create()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new ApproveOrderErasure($unknownId));
    }
}
