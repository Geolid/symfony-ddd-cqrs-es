<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Query\ListSubjectsDueForErasure;

use Compliance\Erasure\Application\Finder\Subject\SubjectResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\StreamResult;

/**
 * @implements QueryInterface<StreamResult<SubjectResult>>
 */
final readonly class ListSubjectsDueForErasure implements QueryInterface
{
}
