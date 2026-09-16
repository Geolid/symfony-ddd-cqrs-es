<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Service;

interface TotpVerifierInterface
{
    public function verify(#[\SensitiveParameter] string $secret, string $code): bool;
}
