<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Hydration\Patchlevel\Normalizer;

use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use Patchlevel\Hydrator\Normalizer\Normalizer;

final readonly class BooleanNormalizer implements Normalizer
{
    public function normalize(mixed $value): ?bool
    {
        if (null === $value) {
            return null;
        }

        if (!\is_bool($value)) {
            throw InvalidArgument::withWrongType('bool|null', $value);
        }

        return $value;
    }

    public function denormalize(mixed $value): ?bool
    {
        if (null === $value) {
            return null;
        }

        if (\in_array($value, [true, 1, '1', 'true'], true)) {
            return true;
        }

        if (\in_array($value, [false, 0, '0', 'false'], true)) {
            return false;
        }

        throw InvalidArgument::withWrongType('bool|null', $value);
    }
}
