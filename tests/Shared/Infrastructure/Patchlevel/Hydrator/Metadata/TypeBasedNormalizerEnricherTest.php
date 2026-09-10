<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Patchlevel\Hydrator\Metadata;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Metadata\PropertyMetadata;
use Patchlevel\Hydrator\Normalizer\ArrayNormalizer;
use Patchlevel\Hydrator\Normalizer\Normalizer;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Patchlevel\Hydrator\Metadata\TypeBasedNormalizerEnricher;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\BooleanNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\IntegerNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\JsonNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\UtcDateTimeImmutableNormalizer;
use Shared\Tests\Support\Double\DummyHydratable;
use Shared\Tests\Support\Double\DummyNestedObject;

final class TypeBasedNormalizerEnricherTest extends TestCase
{
    #[Test]
    #[DataProvider('provideProperties')]
    public function itAssigns(string $property, ?Normalizer $alreadyGuessed, ?string $expected): void
    {
        // Given
        $classMetadata = $this->classMetadataFor($property, $alreadyGuessed);

        // When
        new TypeBasedNormalizerEnricher()->enrich($classMetadata);

        // Then
        $normalizer = $classMetadata->properties()[0]->normalizer();
        self::assertSame($expected, null !== $normalizer ? $normalizer::class : null);
    }

    /**
     * @return iterable<string, array{string, ?Normalizer, ?class-string}>
     */
    public static function provideProperties(): iterable
    {
        yield 'date time' => ['dateTime', null, UtcDateTimeImmutableNormalizer::class];
        yield 'boolean' => ['boolean', null, BooleanNormalizer::class];
        yield 'integer' => ['integer', null, IntegerNormalizer::class];
        yield 'object, already guessed by the vendor' => ['object', new ObjectNormalizer(DummyNestedObject::class), JsonNormalizer::class];
        yield 'object, nothing guessed' => ['object', null, null];
        yield 'list of objects, already guessed by the vendor' => ['objects', new ArrayNormalizer(new ObjectNormalizer(DummyNestedObject::class)), JsonNormalizer::class];
        yield 'string' => ['string', null, null];
        yield 'backed enum' => ['enum', null, null];
        yield 'no named type' => ['union', null, null];
    }

    #[Test]
    public function itWrapsTheAlreadyGuessedNormalizerRatherThanReplacingIt(): void
    {
        // Given
        $guessed = new ObjectNormalizer(DummyNestedObject::class);
        $classMetadata = $this->classMetadataFor('object', $guessed);

        // When
        new TypeBasedNormalizerEnricher()->enrich($classMetadata);

        // Then
        $normalizer = $classMetadata->properties()[0]->normalizer();
        self::assertInstanceOf(JsonNormalizer::class, $normalizer);

        $wrapped = new \ReflectionObject($normalizer)->getProperty('normalizer')->getValue($normalizer);
        self::assertSame($guessed, $wrapped);
    }

    private function classMetadataFor(string $property, ?Normalizer $alreadyGuessed): ClassMetadata
    {
        $reflection = new \ReflectionClass(DummyHydratable::class);

        return new ClassMetadata($reflection, [
            new PropertyMetadata($reflection->getProperty($property), $property, normalizer: $alreadyGuessed),
        ]);
    }
}
