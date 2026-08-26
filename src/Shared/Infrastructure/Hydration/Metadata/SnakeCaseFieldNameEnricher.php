<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Hydration\Metadata;

use Patchlevel\Hydrator\Metadata\ClassMetadata;
use Patchlevel\Hydrator\Metadata\MetadataEnricher;
use Symfony\Component\String\UnicodeString;

final class SnakeCaseFieldNameEnricher implements MetadataEnricher
{
    public function enrich(ClassMetadata $classMetadata): void
    {
        foreach ($classMetadata->properties() as $property) {
            if ($property->fieldName() !== $property->propertyName()) {
                continue;
            }

            $property->fieldName = new UnicodeString($property->propertyName())->snake()->toString();
        }
    }
}
