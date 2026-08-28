<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Hydration;

use Patchlevel\Hydrator\CoreExtension;
use Patchlevel\Hydrator\StackHydrator;
use Patchlevel\Hydrator\StackHydratorBuilder;
use Shared\Infrastructure\Hydration\Patchlevel\Metadata\SnakeCaseFieldNameEnricher;
use Shared\Infrastructure\Hydration\Patchlevel\Metadata\TypeBasedNormalizerEnricher;

final readonly class HydratorFactory
{
    public function __construct(
        private SnakeCaseFieldNameEnricher $snakeCaseFieldNameEnricher,
        private TypeBasedNormalizerEnricher $typeBasedNormalizerEnricher,
    ) {
    }

    public function create(): StackHydrator
    {
        return new StackHydratorBuilder()
            ->useExtension(new CoreExtension())
            ->addMetadataEnricher($this->snakeCaseFieldNameEnricher)
            ->addMetadataEnricher($this->typeBasedNormalizerEnricher)
            ->build();
    }
}
