<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Infrastructure\Projection\Finder;

use Finance\Refund\Application\Exception\RefundResultNotFoundException;
use Finance\Refund\Application\Finder\Refund\RefundFinderInterface;
use Finance\Refund\Application\RefundStatus;
use Finance\Tests\Refund\Support\Builder\RefundBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalRefundFinderTest extends AbstractIntegrationTestCase
{
    private RefundFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(RefundFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = RefundBuilder::new()->create();
        $builder = RefundBuilder::new();
        $refund = $builder->create();
        $this->store($other, $refund);

        // When
        $result = $this->finder->ofId($refund->id->toString());

        // Then
        self::assertSame($refund->id->toString(), $result->id);
        self::assertSame($builder['paymentId'], $result->paymentId);
        self::assertSame($builder['orderId'], $result->orderId);
        self::assertSame($builder['amount']->cents, $result->amountInCents);
        self::assertSame(RefundStatus::INITIATED, $result->status);
        self::assertSame($builder['initiatedAt']->format(\DateTimeInterface::ATOM), $result->initiatedAt->format(\DateTimeInterface::ATOM));
        self::assertNull($result->refundedAt);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(RefundResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }
}
