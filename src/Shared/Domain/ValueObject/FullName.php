<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class FullName
{
    public const int MAX_LENGTH = 255;

    public string $firstName;

    public string $lastName;

    private function __construct(string $firstName, string $lastName)
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        Assert::notEmpty($firstName, 'A first name cannot be empty, %s given.');
        Assert::maxLength($firstName, self::MAX_LENGTH, 'A first name cannot exceed %2$d characters, %s given.');
        Assert::notEmpty($lastName, 'A last name cannot be empty, %s given.');
        Assert::maxLength($lastName, self::MAX_LENGTH, 'A last name cannot exceed %2$d characters, %s given.');

        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public static function of(string $firstName, string $lastName): self
    {
        return new self($firstName, $lastName);
    }

    public function equals(self $other): bool
    {
        return $this->firstName === $other->firstName
            && $this->lastName === $other->lastName;
    }

    public function toString(): string
    {
        return \sprintf('%s %s', $this->firstName, $this->lastName);
    }
}
