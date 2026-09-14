<?php

declare(strict_types=1);

namespace Shared\Tests\Support\TestCase;

use PHPUnit\Framework\Attributes\Test;

trait IdTiebreakerTrait
{
    #[Test]
    public function itOrdersDeterministicallyWhenTied(): void
    {
        // Given
        $finder = $this->finder();
        $ids = $this->seedTie();

        // When
        $results = iterator_to_array($finder);

        // Then
        self::assertSame($ids, $this->resultIndexes($results));
    }

    /**
     * Seeds two rows sharing the same value on the Finder's own real default-sort column,
     * inserted in reverse id order, returning their ids ascending.
     *
     * @return array{string, string}
     */
    abstract protected function seedTie(): array;
}
