<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Service;

interface SecretHasherInterface
{
    public function hash(string $secret): string;

    public function verify(string $hash, string $secret): bool;
}
