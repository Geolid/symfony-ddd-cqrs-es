<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

final class DummyNestedObject
{
    public function __construct(public string $value)
    {
    }
}
