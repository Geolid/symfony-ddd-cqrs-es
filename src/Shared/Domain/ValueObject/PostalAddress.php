<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class PostalAddress
{
    public const int RECIPIENT_NAME_MAX_LENGTH = 255;

    public string $recipientName;

    private function __construct(
        string $recipientName,
        public Address $address,
    ) {
        $recipientName = trim($recipientName);
        Assert::notEmpty($recipientName, 'A recipient name cannot be empty, %s given.');
        Assert::maxLength($recipientName, self::RECIPIENT_NAME_MAX_LENGTH, 'A recipient name cannot exceed %2$d characters, %s given.');

        $this->recipientName = $recipientName;
    }

    public static function of(string $recipientName, Address $address): self
    {
        return new self($recipientName, $address);
    }

    public function equals(self $other): bool
    {
        return $this->recipientName === $other->recipientName
            && $this->address->equals($other->address);
    }

    /**
     * @return array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    public function toArray(): array
    {
        return ['recipientName' => $this->recipientName, ...$this->address->toArray()];
    }
}
