<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Patchlevel\Hydrator\Metadata;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Metadata\MetadataEnricher;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\BooleanNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\IntegerNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\JsonNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\UtcDateTimeImmutableNormalizer;

final class TypeBasedNormalizerEnricher implements MetadataEnricher
{
    public function enrich(ClassMetadata $classMetadata): void
    {
        foreach ($classMetadata->properties() as $property) {
            $type = $property->reflection()->getType();

            if (!$type instanceof \ReflectionNamedType) {
                continue;
            }

            $name = $type->getName();

            $normalizer = match ($name) {
                \DateTimeImmutable::class => new UtcDateTimeImmutableNormalizer(),
                'bool' => new BooleanNormalizer(),
                'int' => new IntegerNormalizer(),
                default => null,
            };

            if (null !== $normalizer) {
                $property->normalizer = $normalizer;

                continue;
            }

            // A JSON-stored shape (a single nested object, or a list<object>) arrives from
            // a raw SQL SELECT as a string — wrap whichever normalizer the vendor already
            // guessed for that shape (ObjectNormalizer/ArrayNormalizer) with a decode-first
            // step, instead of replacing it.
            $isJsonStoredShape = 'array' === $name || (class_exists($name) && !is_a($name, \BackedEnum::class, true));

            if ($isJsonStoredShape && null !== $property->normalizer) {
                $property->normalizer = new JsonNormalizer($property->normalizer);
            }
        }
    }
}
