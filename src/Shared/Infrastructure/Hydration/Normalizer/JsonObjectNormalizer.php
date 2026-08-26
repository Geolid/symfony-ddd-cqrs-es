<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Hydration\Normalizer;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\Hydrator\Normalizer\HydratorAwareNormalizer;
use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use Patchlevel\Hydrator\Normalizer\Normalizer;

final class JsonObjectNormalizer implements Normalizer, HydratorAwareNormalizer
{
    private ?Hydrator $hydrator = null;

    /**
     * @param class-string $className
     */
    public function __construct(private readonly string $className)
    {
    }

    public function normalize(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof $this->className || null === $this->hydrator) {
            throw InvalidArgument::withWrongType($this->className.'|null', $value);
        }

        try {
            return json_encode($this->hydrator->extract($value), \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw InvalidArgument::fromThrowable($exception);
        }
    }

    public function denormalize(mixed $value): ?object
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value) || null === $this->hydrator) {
            throw InvalidArgument::withWrongType('string|null', $value);
        }

        try {
            $decoded = json_decode($value, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw InvalidArgument::fromThrowable($exception);
        }

        if (!\is_array($decoded)) {
            throw InvalidArgument::withWrongType('array', $decoded);
        }

        return $this->hydrator->hydrate($this->className, $decoded);
    }

    public function setHydrator(Hydrator $hydrator): void
    {
        $this->hydrator = $hydrator;
    }
}
