<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Hydration\Normalizer;

use Patchlevel\Hydrator\Normalizer\Normalizer;

final readonly class IntegerNormalizer implements Normalizer
{
    public function normalize(mixed $value): ?int
    {
        \assert(null === $value || \is_scalar($value));

        return null !== $value ? (int) $value : null;
    }

    public function denormalize(mixed $value): ?int
    {
        \assert(null === $value || \is_scalar($value));

        return null !== $value ? (int) $value : null;
    }
}
