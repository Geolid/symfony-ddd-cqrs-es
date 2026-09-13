<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class TaxRate
{
    public int $basisPoints;

    private function __construct(int $basisPoints)
    {
        Assert::greaterThanEq($basisPoints, 0, 'A tax rate cannot be negative, %s given.');

        $this->basisPoints = $basisPoints;
    }

    public static function fromBasisPoints(int $basisPoints): self
    {
        return new self($basisPoints);
    }

    public function equals(self $other): bool
    {
        return $this->basisPoints === $other->basisPoints;
    }
}
