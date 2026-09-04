<?php

declare(strict_types=1);

namespace Finance\Payer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;

#[Event('finance.payer.payer.erased')]
final readonly class PayerErased implements DataSubjectErasureInterface
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
