<?php

declare(strict_types=1);

namespace Shared\Tests\Application;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Application\ErasureStatus;

final class ErasureStatusTest extends TestCase
{
    #[Test]
    public function itIsRequested(): void
    {
        foreach (ErasureStatus::cases() as $status) {
            self::assertSame(ErasureStatus::REQUESTED === $status, $status->isRequested(), $status->value);
        }
    }
}
