<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\ValueObject;

use Ramsey\Uuid\Uuid;
use Webmozart\Assert\Assert;

final readonly class LineId
{
    private const string PRODUCT_NAMESPACE = 'b3f1c2a4-6d5e-4f8a-9b0c-1d2e3f4a5b6c';

    private function __construct(public string $value)
    {
        Assert::uuid($value, 'A cart line id must be a valid UUID, %s given.');
    }

    public static function forProduct(string $cartId, string $productId): self
    {
        return new self(Uuid::uuid5(self::PRODUCT_NAMESPACE, $cartId.$productId)->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
