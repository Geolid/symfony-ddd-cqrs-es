<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Patchlevel\Hydrator\Metadata;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Metadata\MetadataEnricher;
use Patchlevel\Hydrator\Normalizer\Normalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\BooleanNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\IntegerNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\JsonObjectNormalizer;
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

            $normalizer = $this->resolveNormalizer($type);

            if (null !== $normalizer) {
                $property->normalizer = $normalizer;
            }
        }
    }

    private function resolveNormalizer(\ReflectionNamedType $type): ?Normalizer
    {
        $name = $type->getName();

        $normalizer = match ($name) {
            \DateTimeImmutable::class => new UtcDateTimeImmutableNormalizer(),
            'bool' => new BooleanNormalizer(),
            'int' => new IntegerNormalizer(),
            default => null,
        };

        if (null !== $normalizer) {
            return $normalizer;
        }

        if (!class_exists($name) || is_a($name, \BackedEnum::class, true)) {
            return null;
        }

        return new JsonObjectNormalizer($name);
    }
}
