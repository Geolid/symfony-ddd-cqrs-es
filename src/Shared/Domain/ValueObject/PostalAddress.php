<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

final readonly class PostalAddress
{
    public FullName $fullName;

    public Address $address;

    private function __construct(FullName $fullName, Address $address)
    {
        $this->fullName = $fullName;
        $this->address = $address;
    }

    public static function of(FullName $fullName, Address $address): self
    {
        return new self($fullName, $address);
    }

    public function equals(self $other): bool
    {
        return $this->fullName->equals($other->fullName)
            && $this->address->equals($other->address);
    }

    public function toString(): string
    {
        return \sprintf('%s, %s', $this->fullName->toString(), $this->address->toString());
    }
}
