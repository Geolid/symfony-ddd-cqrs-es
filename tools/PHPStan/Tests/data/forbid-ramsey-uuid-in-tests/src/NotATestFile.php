<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;

final class NotATestFile
{
    public function test(): string
    {
        return Uuid::uuid7()->toString(); // allowed: path has no /tests/ segment at all
    }
}
