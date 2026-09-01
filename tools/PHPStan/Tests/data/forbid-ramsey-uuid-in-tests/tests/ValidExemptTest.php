<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;

final class ValidExemptTest
{
    public function test(): void
    {
        $id = Uuid::uuid7()->toString(); // allowed: basename matches Valid<X>Test.php
    }
}
