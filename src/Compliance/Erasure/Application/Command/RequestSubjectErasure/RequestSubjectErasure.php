<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\RequestSubjectErasure;

use Shared\Application\Command\CommandInterface;

final readonly class RequestSubjectErasure implements CommandInterface
{
    public function __construct(public string $subjectId)
    {
    }
}
