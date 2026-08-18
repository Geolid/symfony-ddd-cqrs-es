<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

final readonly class PostalAddress
{
    private function __construct(
        public FullName $fullName,
        public Address $address,
    ) {
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
