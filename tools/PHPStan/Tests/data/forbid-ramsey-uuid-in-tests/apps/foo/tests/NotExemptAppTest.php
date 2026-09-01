<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;

final class NotExemptAppTest
{
    public function test(): void
    {
        $id = Uuid::uuid7()->toString(); // forbidden: an app/DM test is not exempt either
    }
}
