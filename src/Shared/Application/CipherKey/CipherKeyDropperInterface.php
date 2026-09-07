<?php

declare(strict_types=1);

namespace Shared\Application\CipherKey;

interface CipherKeyDropperInterface
{
    public function drop(string $subjectId): void;
}
