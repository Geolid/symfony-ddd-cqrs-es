<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

final class UniqueKey
{
    private const string SEPARATOR = "\x1F";

    public string $value {
        get => implode(self::SEPARATOR, [(string) $this->discriminator->value, ...$this->scope]);
    }

    /**
     * @param list<string> $scope
     */
    private function __construct(
        public readonly \BackedEnum $discriminator,
        private readonly array $scope,
    ) {
    }

    public static function for(\BackedEnum $discriminator, string ...$scope): self
    {
        return new self($discriminator, array_values($scope));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
