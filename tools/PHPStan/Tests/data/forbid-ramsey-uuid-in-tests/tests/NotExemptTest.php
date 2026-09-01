<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;

final class NotExemptTest
{
    public function test(): void
    {
        $id = Uuid::uuid7()->toString(); // forbidden: a plain test, no exemption applies
    }
}
