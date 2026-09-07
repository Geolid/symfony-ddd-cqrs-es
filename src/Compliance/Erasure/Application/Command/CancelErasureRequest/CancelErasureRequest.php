<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\CancelErasureRequest;

use Shared\Application\Command\CommandInterface;

final readonly class CancelErasureRequest implements CommandInterface
{
    public function __construct(public string $subjectId)
    {
    }
}
