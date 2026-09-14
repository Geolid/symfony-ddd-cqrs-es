<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Patchlevel\Hydrator\Metadata;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Metadata\MetadataEnricher;
use Patchlevel\Hydrator\Normalizer\Normalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\BooleanNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\IntegerNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\JsonNormalizer;
use Shared\Infrastructure\Patchlevel\Hydrator\Normalizer\UtcDateTimeImmutableNormalizer;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

final readonly class TypeBasedNormalizerEnricher implements MetadataEnricher
{
    private TypeResolver $typeResolver;

    public function __construct()
    {
        $this->typeResolver = TypeResolver::create();
    }

    public function enrich(ClassMetadata $classMetadata): void
    {
        foreach ($classMetadata->properties() as $property) {
            $normalizer = $this->resolveNormalizer($this->typeResolver->resolve($property->reflection()));

            if (null !== $normalizer) {
                $property->normalizer = $normalizer;
            }
        }
    }

    private function resolveNormalizer(Type $type): ?Normalizer
    {
        if ($type instanceof NullableType) {
            return $this->resolveNormalizer($type->getWrappedType());
        }

        if ($type instanceof CollectionType) {
            if (!$type->isList()) {
                return null;
            }

            $valueType = $type->getCollectionValueType();

            return $valueType instanceof ObjectType ? $this->resolveClassNormalizer($valueType->getClassName()) : null;
        }

        if ($type instanceof BuiltinType) {
            return match ($type->getTypeIdentifier()) {
                TypeIdentifier::BOOL => new BooleanNormalizer(),
                TypeIdentifier::INT => new IntegerNormalizer(),
                default => null,
            };
        }

        if ($type instanceof ObjectType) {
            return \DateTimeImmutable::class === $type->getClassName()
                ? new UtcDateTimeImmutableNormalizer()
                : $this->resolveClassNormalizer($type->getClassName());
        }

        return null;
    }

    private function resolveClassNormalizer(string $name): ?Normalizer
    {
        if (!class_exists($name) || is_a($name, \BackedEnum::class, true)) {
            return null;
        }

        return new JsonNormalizer($name);
    }
}
