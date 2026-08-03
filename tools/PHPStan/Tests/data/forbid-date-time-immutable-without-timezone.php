<?php

declare(strict_types=1);

new DateTimeImmutable(); // forbidden: no args, implicit system clock
new DateTime(); // forbidden: no args, implicit system clock
new DateTimeImmutable('2024-01-01'); // forbidden: no timezone offset
new DateTimeImmutable('2024-01-01T00:00:00+00:00'); // allowed: explicit offset
new DateTimeImmutable('2024-01-01T00:00:00Z'); // allowed: Z is an explicit offset
new DateTimeImmutable($undefinedVariable); // allowed: rule only checks string literals, not the value at runtime
new stdClass(); // allowed: not a forbidden class
