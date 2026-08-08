<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Service;

interface SecretHasherInterface
{
    public function hash(#[\SensitiveParameter] string $secret): string;

    public function verify(string $hash, #[\SensitiveParameter] string $secret): bool;

    public function needsRehash(string $hash): bool;
}
