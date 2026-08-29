<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Patchlevel\Hydrator\Normalizer;

use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use Patchlevel\Hydrator\Normalizer\Normalizer;

final readonly class IntegerNormalizer implements Normalizer
{
    public function normalize(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!\is_int($value)) {
            throw InvalidArgument::withWrongType('int|null', $value);
        }

        return $value;
    }

    public function denormalize(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value) && (string) (int) $value === $value) {
            return (int) $value;
        }

        throw InvalidArgument::withWrongType('int|null', $value);
    }
}
