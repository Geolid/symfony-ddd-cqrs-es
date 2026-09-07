<?php

declare(strict_types=1);

namespace Compliance\Erasure\Infrastructure\CipherKey;

use Compliance\Erasure\Application\CipherKey\CipherKeyDropperInterface;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;

final readonly class PatchlevelCipherKeyDropper implements CipherKeyDropperInterface
{
    public function __construct(private CipherKeyStore $cipherKeyStore)
    {
    }

    public function drop(string $subjectId): void
    {
        $this->cipherKeyStore->removeWithSubjectId($subjectId);
    }
}
