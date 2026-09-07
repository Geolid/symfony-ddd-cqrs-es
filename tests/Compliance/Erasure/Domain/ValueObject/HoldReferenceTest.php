<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Domain\ValueObject;

use Compliance\Erasure\Domain\ValueObject\HoldReference;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HoldReferenceTest extends TestCase
{
    #[Test]
    public function itCreatesFor(): void
    {
        // When
        $reference = HoldReference::for('sales.order.order', 'order-1');

        // Then
        self::assertSame('sales.order.order', $reference->sourceType);
        self::assertSame('order-1', $reference->sourceId);
        self::assertSame('sales.order.order:order-1', $reference->toString());
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = HoldReference::for('sales.order.order', 'order-1');
        $b = HoldReference::for('sales.order.order', 'order-1');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = HoldReference::for('sales.order.order', 'order-1');
        $b = HoldReference::for('sales.order.order', 'order-2');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
