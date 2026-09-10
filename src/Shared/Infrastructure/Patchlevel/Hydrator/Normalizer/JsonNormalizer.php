<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Patchlevel\Hydrator\Normalizer;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\Hydrator\Normalizer\HydratorAwareNormalizer;
use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use Patchlevel\Hydrator\Normalizer\Normalizer;
use Patchlevel\Hydrator\Normalizer\NormalizerWithContext;

/**
 * A raw SQL SELECT never converts a JSON column into a PHP array (Doctrine's type
 * conversion applies to bound parameters, not to result columns) — this wraps
 * whichever normalizer the vendor already resolved for the property's real shape
 * (ObjectNormalizer for a single object, ArrayNormalizer for a list<object>) with
 * a decode-the-string-first step, instead of duplicating that normalizer's own job.
 */
final readonly class JsonNormalizer implements Normalizer, HydratorAwareNormalizer
{
    public function __construct(private Normalizer $normalizer)
    {
    }

    public function normalize(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = $this->normalizer instanceof NormalizerWithContext
            ? $this->normalizer->normalize($value, [])
            : $this->normalizer->normalize($value);

        try {
            return json_encode($normalized, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw InvalidArgument::fromThrowable($exception);
        }
    }

    public function denormalize(mixed $value): mixed
    {
        if (null === $value) {
            return null;
        }

        if (\is_string($value)) {
            try {
                $value = json_decode($value, true, flags: \JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw InvalidArgument::fromThrowable($exception);
            }
        }

        return $this->normalizer instanceof NormalizerWithContext
            ? $this->normalizer->denormalize($value, [])
            : $this->normalizer->denormalize($value);
    }

    public function setHydrator(Hydrator $hydrator): void
    {
        if ($this->normalizer instanceof HydratorAwareNormalizer) {
            $this->normalizer->setHydrator($hydrator);
        }
    }
}
