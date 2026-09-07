<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\EraseSubject;

use Shared\Application\Command\CommandInterface;

final readonly class EraseSubject implements CommandInterface
{
    public function __construct(public string $subjectId)
    {
    }
}
