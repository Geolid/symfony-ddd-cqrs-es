<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Hydration\Normalizer;

use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use Patchlevel\Hydrator\Normalizer\Normalizer;

final readonly class IntegerNormalizer implements Normalizer
{
    public function normalize(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!\is_scalar($value)) {
            throw InvalidArgument::withWrongType('int|null', $value);
        }

        return (int) $value;
    }

    public function denormalize(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!\is_scalar($value)) {
            throw InvalidArgument::withWrongType('int|null', $value);
        }

        return (int) $value;
    }
}
