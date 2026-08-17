<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Doubles;

use Shared\Domain\Gdpr\DataSubjectErasureInterface;

final readonly class StubDataSubjectErased implements DataSubjectErasureInterface
{
    public function __construct(private string $subjectId)
    {
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }
}
