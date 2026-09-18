<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

use Shared\Domain\Service\CodeChallengerInterface;

final readonly class FakeCodeChallenger implements CodeChallengerInterface
{
    public const string CODE = '123456';

    public function issue(\BackedEnum $purpose, string $subjectId, \DateTimeImmutable $now): string
    {
        return self::CODE;
    }

    public function verify(\BackedEnum $purpose, string $subjectId, #[\SensitiveParameter] string $code, \DateTimeImmutable $now): bool
    {
        return self::CODE === $code;
    }
}
