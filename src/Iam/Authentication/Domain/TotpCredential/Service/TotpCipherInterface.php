<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Service;

interface TotpCipherInterface
{
    public function encrypt(#[\SensitiveParameter] string $secret): string;

    public function decrypt(string $encryptedSecret): string;
}
