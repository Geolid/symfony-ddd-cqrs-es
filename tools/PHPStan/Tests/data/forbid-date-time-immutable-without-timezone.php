<?php

declare(strict_types=1);

/** @var array<string, string> $row */
new stdClass(); // allowed: not a forbidden class
new DateTimeImmutable('2024-01-01T00:00:00+00:00'); // allowed: explicit offset
new DateTimeImmutable('2024-01-01T00:00:00Z'); // allowed: Z is an explicit offset
new DateTimeImmutable($undefinedVariable); // allowed: rule only checks array-fetch (a raw DB column), not any dynamic value
new DateTimeImmutable($row['placed_at'], new DateTimeZone('UTC')); // allowed: explicit timezone argument
new DateTime(); // forbidden: no args, implicit system clock
new DateTimeImmutable(); // forbidden: no args, implicit system clock
new DateTimeImmutable('2024-01-01'); // forbidden: no timezone offset
new DateTimeImmutable($row['placed_at']); // forbidden: rehydrating a database column with no explicit timezone argument
