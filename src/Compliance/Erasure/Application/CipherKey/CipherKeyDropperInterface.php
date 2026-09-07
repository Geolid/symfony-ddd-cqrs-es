<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\CipherKey;

interface CipherKeyDropperInterface
{
    public function drop(string $subjectId): void;
}
