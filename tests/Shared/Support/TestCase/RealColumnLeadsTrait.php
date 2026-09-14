<?php

declare(strict_types=1);

namespace Shared\Tests\Support\TestCase;

use PHPUnit\Framework\Attributes\Test;

trait RealColumnLeadsTrait
{
    #[Test]
    public function itOrdersByRealColumnEvenWhenIdDisagrees(): void
    {
        // Given
        $finder = $this->finder();
        $ids = $this->seedConflictingOrder();

        // When
        $results = iterator_to_array($finder);

        // Then
        self::assertSame($ids, $this->resultIndexes($results));
    }

    /**
     * Seeds two rows whose real default-sort column order is the reverse of their id order,
     * returning their ids in the order the real column dictates.
     *
     * @return array{string, string}
     */
    abstract protected function seedConflictingOrder(): array;
}
