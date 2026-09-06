<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Patchlevel\Hydrator\Normalizer;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\Hydrator\Normalizer\HydratorAwareNormalizer;
use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use Patchlevel\Hydrator\Normalizer\Normalizer;
use Webmozart\Assert\Assert;

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

        if (null === $this->hydrator) {
            throw InvalidArgument::withWrongType('string|array<string, mixed>|null', $value);
        }

        if (\is_string($value)) {
            try {
                $value = json_decode($value, true, flags: \JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw InvalidArgument::fromThrowable($exception);
            }
        }

        try {
            $decoded = Assert::isMap($value);
        } catch (\InvalidArgumentException) {
            throw InvalidArgument::withWrongType('array<string, mixed>', $value);
        }

        return $this->hydrator->hydrate($this->className, $decoded);
    }

    public function setHydrator(Hydrator $hydrator): void
    {
        $this->hydrator = $hydrator;
    }
}
