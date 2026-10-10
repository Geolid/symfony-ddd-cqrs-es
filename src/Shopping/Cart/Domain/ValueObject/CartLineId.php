<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain\ValueObject;

use Ramsey\Uuid\Uuid;
use Webmozart\Assert\Assert;

final readonly class CartLineId
{
    private const string CART_NAMESPACE = '3cad118b-c700-4ba6-9fa9-fc63e5a75e82';

    private function __construct(public string $value)
    {
        Assert::uuid($value, 'A cart line id must be a valid UUID, %s given.');
    }

    public static function forCart(string $cartId, int $sequence): self
    {
        return new self(Uuid::uuid5(self::CART_NAMESPACE, $cartId.$sequence)->toString());
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
