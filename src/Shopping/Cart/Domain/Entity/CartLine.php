<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain\Entity;

use Shared\Domain\ValueObject\Quantity;
use Shopping\Cart\Domain\ValueObject\CartLineId;

final readonly class CartLine
{
    public function __construct(
        public CartLineId $id,
        public string $productId,
        public Quantity $quantity,
        public \DateTimeImmutable $addedAt,
        public ?\DateTimeImmutable $changedAt = null,
    ) {
    }

    public function withQuantity(Quantity $quantity, \DateTimeImmutable $changedAt): self
    {
        return new self($this->id, $this->productId, $quantity, $this->addedAt, $changedAt);
    }
}
