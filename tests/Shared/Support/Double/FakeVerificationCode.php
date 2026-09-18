<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

use Shared\Domain\Service\VerificationCodeInterface;

final readonly class FakeVerificationCode implements VerificationCodeInterface
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
