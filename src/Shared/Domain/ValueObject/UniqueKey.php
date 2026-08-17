<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

final readonly class UniqueKey
{
    private const string SEPARATOR = "\x1F";

    /**
     * @param list<string> $scope
     */
    private function __construct(
        private \BackedEnum $discriminator,
        private array $scope,
    ) {
    }

    public static function for(\BackedEnum $discriminator, string ...$scope): self
    {
        return new self($discriminator, array_values($scope));
    }

    public function discriminator(): \BackedEnum
    {
        return $this->discriminator;
    }

    public function equals(self $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function toString(): string
    {
        return implode(self::SEPARATOR, [(string) $this->discriminator->value, ...$this->scope]);
    }
}
