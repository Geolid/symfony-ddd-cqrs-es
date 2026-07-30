<?php

declare(strict_types=1);

namespace Shared\Domain\Gdpr;

interface DataSubjectErasureInterface
{
    public function subjectId(): string;
}
