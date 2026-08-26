<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Hydration\Normalizer;

use Patchlevel\Hydrator\Normalizer\Normalizer;

final readonly class BooleanNormalizer implements Normalizer
{
    public function normalize(mixed $value): bool
    {
        return (bool) $value;
    }

    public function denormalize(mixed $value): bool
    {
        return (bool) $value;
    }
}
