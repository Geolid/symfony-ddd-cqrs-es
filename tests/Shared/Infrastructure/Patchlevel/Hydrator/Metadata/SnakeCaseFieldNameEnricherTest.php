<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Patchlevel\Hydrator\Metadata;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Metadata\PropertyMetadata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Patchlevel\Hydrator\Metadata\SnakeCaseFieldNameEnricher;
use Shared\Tests\Support\Double\DummyHydratable;

final class SnakeCaseFieldNameEnricherTest extends TestCase
{
    #[Test]
    #[DataProvider('provideFieldNames')]
    public function itConverts(string $fieldName, string $expected): void
    {
        // Given
        $classMetadata = $this->classMetadataFor($fieldName);

        // When
        new SnakeCaseFieldNameEnricher()->enrich($classMetadata);

        // Then
        $result = $classMetadata->properties()[0]->fieldName();
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideFieldNames(): iterable
    {
        yield 'same as property' => ['dateTime', 'date_time'];
        yield 'already set' => ['other', 'other'];
    }

    private function classMetadataFor(string $fieldName): ClassMetadata
    {
        $reflection = new \ReflectionClass(DummyHydratable::class);

        return new ClassMetadata($reflection, [
            new PropertyMetadata($reflection->getProperty('dateTime'), $fieldName),
        ]);
    }
}
