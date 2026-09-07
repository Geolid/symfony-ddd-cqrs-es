<?php

declare(strict_types=1);

namespace Shared\Infrastructure\CipherKey;

use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Application\CipherKey\CipherKeyDropperInterface;

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
