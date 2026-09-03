<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Patchlevel\Hydrator\Normalizer;

use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use Patchlevel\Hydrator\Normalizer\Normalizer;

final readonly class UtcDateTimeImmutableNormalizer implements Normalizer
{
    public function normalize(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \DateTimeImmutable) {
            throw InvalidArgument::withWrongType('DateTimeImmutable|null', $value);
        }

        return $value->format('Y-m-d H:i:s');
    }

    public function denormalize(mixed $value): ?\DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw InvalidArgument::withWrongType('string|null', $value);
        }

        try {
            return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Exception $exception) {
            throw InvalidArgument::fromThrowable($exception);
        }
    }
}
