<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

enum DummyState: string
{
    case INIT = 'init';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCESS = 'success';
    case FAILED = 'failed';
}
