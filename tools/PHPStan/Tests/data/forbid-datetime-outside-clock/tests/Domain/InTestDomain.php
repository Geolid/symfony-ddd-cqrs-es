<?php

declare(strict_types=1);

final class InTestDomain
{
    public function test(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-01T00:00:00+00:00'); // forbidden: /tests/ never gets the /Domain/ exemption, even mirroring one
    }
}
