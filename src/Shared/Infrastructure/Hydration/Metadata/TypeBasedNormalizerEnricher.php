<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Hydration\Metadata;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Metadata\MetadataEnricher;
use Shared\Infrastructure\Hydration\Normalizer\BooleanNormalizer;
use Shared\Infrastructure\Hydration\Normalizer\IntegerNormalizer;
use Shared\Infrastructure\Hydration\Normalizer\JsonObjectNormalizer;
use Shared\Infrastructure\Hydration\Normalizer\UtcDateTimeImmutableNormalizer;

final class TypeBasedNormalizerEnricher implements MetadataEnricher
{
    public function enrich(ClassMetadata $classMetadata): void
    {
        foreach ($classMetadata->properties() as $property) {
            $type = $property->reflection()->getType();

            if (!$type instanceof \ReflectionNamedType) {
                continue;
            }

            $typeName = $type->getName();

            $normalizer = match (true) {
                \DateTimeImmutable::class === $typeName => new UtcDateTimeImmutableNormalizer(),
                'bool' === $typeName => new BooleanNormalizer(),
                'int' === $typeName => new IntegerNormalizer(),
                !$type->isBuiltin() && !is_a($typeName, \BackedEnum::class, true) => $this->jsonObjectNormalizer($typeName),
                default => null,
            };

            if (null === $normalizer) {
                continue;
            }

            $property->normalizer = $normalizer;
        }
    }

    private function jsonObjectNormalizer(string $className): JsonObjectNormalizer
    {
        \assert(class_exists($className));

        return new JsonObjectNormalizer($className);
    }
}
