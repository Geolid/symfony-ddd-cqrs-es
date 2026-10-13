<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Pii;

use Iam\Identity\Domain\ValueObject\Email;
use Shared\Domain\Pii\ErasedFieldSentinel;

final readonly class ErasedEmail
{
    public function __invoke(string $subjectId): Email
    {
        $value = (new ErasedFieldSentinel('%s@erased.invalid'))($subjectId);
        \assert(\is_string($value));

        return Email::fromString($value);
    }
}
