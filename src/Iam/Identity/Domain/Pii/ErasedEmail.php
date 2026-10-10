<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Pii;

use Iam\Identity\Domain\ValueObject\Email;
use Shared\Domain\Pii\ErasedFieldSentinel;

final readonly class ErasedEmail
{
    public function __invoke(string $subjectId): Email
    {
        return Email::fromString((new ErasedFieldSentinel('%s@erased.invalid'))($subjectId));
    }
}
