<?php

declare(strict_types=1);

namespace Shared\Domain\Gdpr;

/**
 * @template-covariant T
 */
final readonly class ErasedFieldSentinel
{
    /**
     * @param T $fallbackValue
     */
    public function __construct(private mixed $fallbackValue)
    {
    }

    public function __invoke(string $subjectId): mixed
    {
        if (\is_string($this->fallbackValue) && str_contains($this->fallbackValue, '%s')) {
            $hash = substr(hash('sha256', $subjectId), 0, 8);

            return \sprintf($this->fallbackValue, $hash);
        }

        if (\is_array($this->fallbackValue)) {
            return array_map(
                static fn (mixed $value): mixed => \is_callable($value) ? $value($subjectId) : $value,
                $this->fallbackValue,
            );
        }

        return $this->fallbackValue;
    }
}
