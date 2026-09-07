<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Domain\ValueObject;

use Compliance\Erasure\Domain\ValueObject\ErasureHoldReference;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ErasureHoldReferenceTest extends TestCase
{
    #[Test]
    public function itCreatesFor(): void
    {
        // When
        $reference = ErasureHoldReference::for('compliance.tests.source', 'order-1');

        // Then
        self::assertSame('compliance.tests.source', $reference->sourceType);
        self::assertSame('order-1', $reference->sourceId);
        self::assertSame('compliance.tests.source:order-1', $reference->toString());
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = ErasureHoldReference::for('compliance.tests.source', 'order-1');
        $b = ErasureHoldReference::for('compliance.tests.source', 'order-1');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = ErasureHoldReference::for('compliance.tests.source', 'order-1');
        $b = ErasureHoldReference::for('compliance.tests.source', 'order-2');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
