<?php

declare(strict_types=1);

namespace Shared\Application\Uniqueness;

final readonly class UniqueKey
{
    private const string SEPARATOR = "\x1F";

    /**
     * @param list<string> $scope
     */
    private function __construct(
        public \BackedEnum $discriminator,
        private array $scope,
    ) {
    }

    public static function for(\BackedEnum $discriminator, string ...$scope): self
    {
        return new self($discriminator, array_values($scope));
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
