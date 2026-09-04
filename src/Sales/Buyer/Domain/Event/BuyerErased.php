<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;

#[Event('sales.buyer.buyer.erased')]
final readonly class BuyerErased implements DataSubjectErasureInterface
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $erasedAt,
    ) {
    }

    public function subjectId(): string
    {
        return $this->id;
    }
}
