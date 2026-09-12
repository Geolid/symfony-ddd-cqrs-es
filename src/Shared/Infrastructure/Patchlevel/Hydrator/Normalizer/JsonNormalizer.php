<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Patchlevel\Hydrator\Normalizer;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\Hydrator\Normalizer\ArrayNormalizer;
use Patchlevel\Hydrator\Normalizer\HydratorAwareNormalizer;
use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use Patchlevel\Hydrator\Normalizer\Normalizer;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

final readonly class JsonNormalizer implements Normalizer, HydratorAwareNormalizer
{
    private ObjectNormalizer $objectNormalizer;

    /**
     * @param class-string $className
     */
    public function __construct(string $className)
    {
        $this->objectNormalizer = new ObjectNormalizer($className);
    }

    public function normalize(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalizer = \is_array($value) ? new ArrayNormalizer($this->objectNormalizer) : $this->objectNormalizer;

        try {
            return json_encode($normalizer->normalize($value), \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw InvalidArgument::fromThrowable($exception);
        }
    }

    /**
     * @return object|list<object>|null
     */
    public function denormalize(mixed $value): object|array|null
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

        if (!\is_array($value)) {
            throw InvalidArgument::withWrongType('array<string, mixed>|list<array<string, mixed>>', $value);
        }

        if (array_is_list($value)) {
            /** @var list<object> $denormalized */
            $denormalized = new ArrayNormalizer($this->objectNormalizer)->denormalize($value);

            return $denormalized;
        }

        return $this->objectNormalizer->denormalize($value);
    }

    public function setHydrator(Hydrator $hydrator): void
    {
        $this->objectNormalizer->setHydrator($hydrator);
    }
}
