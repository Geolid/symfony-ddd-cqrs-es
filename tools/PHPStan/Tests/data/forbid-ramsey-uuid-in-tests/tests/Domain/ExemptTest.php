<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;

final class ExemptTest
{
    public function test(): void
    {
        $id = Uuid::uuid7()->toString(); // allowed: path contains /Domain/
    }
}
