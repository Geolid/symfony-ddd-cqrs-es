<?php

declare(strict_types=1);

namespace Sales\Customer\Domain;

use Webmozart\Assert\Assert;

final readonly class Email
{
    private function __construct(private string $value)
    {
        Assert::email($value, 'An email address is expected, %s given.');
    }

    public static function fromString(string $value): self
    {
        return new self(mb_strtolower(trim($value)));
    }

    /**
     * The uniqueness registry stores this instead of the address itself: a lookup row is not an
     * event, so no cipher key covers it, and a readable address there would survive erasure.
     */
    public function fingerprint(): string
    {
        return hash('sha256', $this->value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
