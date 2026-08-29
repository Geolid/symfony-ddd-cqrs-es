<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Patchlevel\Hydrator;

use Patchlevel\Hydrator\CoreExtension;
use Patchlevel\Hydrator\StackHydrator;
use Patchlevel\Hydrator\StackHydratorBuilder;
use Shared\Infrastructure\Patchlevel\Hydrator\Metadata\SnakeCaseFieldNameEnricher;
use Shared\Infrastructure\Patchlevel\Hydrator\Metadata\TypeBasedNormalizerEnricher;

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
