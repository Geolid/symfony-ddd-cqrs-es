<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Patchlevel\Hydrator\Metadata;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Metadata\PropertyMetadata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Patchlevel\Hydrator\Metadata\TypeBasedNormalizerEnricher;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\BooleanNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\IntegerNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\JsonObjectNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\UtcDateTimeImmutableNormalizer;
use Shared\Tests\Support\Double\DummyHydratable;
use Shared\Tests\Support\Double\DummyNestedObject;

final class TypeBasedNormalizerEnricherTest extends TestCase
{
    #[Test]
    #[DataProvider('provideProperties')]
    public function itAssigns(string $property, ?string $expected): void
    {
        // Given
        $classMetadata = $this->classMetadataFor($property);

        // When
        new TypeBasedNormalizerEnricher()->enrich($classMetadata);

        // Then
        $normalizer = $classMetadata->properties()[0]->normalizer();
        self::assertSame($expected, null !== $normalizer ? $normalizer::class : null);
    }

    /**
     * @return iterable<string, array{string, ?class-string}>
     */
    public static function provideProperties(): iterable
    {
        yield 'date time' => ['dateTime', UtcDateTimeImmutableNormalizer::class];
        yield 'boolean' => ['boolean', BooleanNormalizer::class];
        yield 'integer' => ['integer', IntegerNormalizer::class];
        yield 'object' => ['object', JsonObjectNormalizer::class];
        yield 'string' => ['string', null];
        yield 'backed enum' => ['enum', null];
        yield 'no named type' => ['union', null];
    }

    #[Test]
    public function itSetsClassName(): void
    {
        // Given
        $classMetadata = $this->classMetadataFor('object');

        // When
        new TypeBasedNormalizerEnricher()->enrich($classMetadata);

        // Then
        $normalizer = $classMetadata->properties()[0]->normalizer();
        self::assertInstanceOf(JsonObjectNormalizer::class, $normalizer);

        $className = new \ReflectionObject($normalizer)->getProperty('className')->getValue($normalizer);
        self::assertSame(DummyNestedObject::class, $className);
    }

    private function classMetadataFor(string $property): ClassMetadata
    {
        $reflection = new \ReflectionClass(DummyHydratable::class);

        return new ClassMetadata($reflection, [
            new PropertyMetadata($reflection->getProperty($property), $property),
        ]);
    }
}
