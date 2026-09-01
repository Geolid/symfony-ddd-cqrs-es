<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;

final class ExemptBuilder
{
    public function test(): string
    {
        return Uuid::uuid7()->toString(); // allowed: basename ends with Builder.php
    }
}
