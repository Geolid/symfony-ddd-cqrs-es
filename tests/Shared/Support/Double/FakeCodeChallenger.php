<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

use Shared\Domain\Service\CodeChallengerInterface;
use Shared\Domain\ValueObject\VerificationCodeKey;

final readonly class FakeCodeChallenger implements CodeChallengerInterface
{
    public const string CODE = '123456';

    public function issue(VerificationCodeKey $key, \DateTimeImmutable $now): string
    {
        return self::CODE;
    }

    public function verify(VerificationCodeKey $key, #[\SensitiveParameter] string $code, \DateTimeImmutable $now): bool
    {
        return self::CODE === $code;
    }
}
