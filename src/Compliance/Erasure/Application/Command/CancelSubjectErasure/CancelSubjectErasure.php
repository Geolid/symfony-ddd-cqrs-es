<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\CancelSubjectErasure;

use Shared\Application\Command\CommandInterface;

final readonly class CancelSubjectErasure implements CommandInterface
{
    public function __construct(public string $subjectId)
    {
    }
}
