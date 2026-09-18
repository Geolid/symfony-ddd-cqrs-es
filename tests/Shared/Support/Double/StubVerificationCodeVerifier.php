<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

use Shared\Domain\Service\VerificationCodeVerifierInterface;

final readonly class StubVerificationCodeVerifier implements VerificationCodeVerifierInterface
{
    public function __construct(private bool $valid = true)
    {
    }

    public function verify(\BackedEnum $purpose, string $subjectId, #[\SensitiveParameter] string $code, \DateTimeImmutable $now): bool
    {
        return $this->valid;
    }
}
