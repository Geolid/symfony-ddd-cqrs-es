<?php

declare(strict_types=1);

namespace Shared\Tests\Support\TestCase;

use Shared\Application\Finder\PaginatorInterface;
use Shared\Tests\Support\PaginationTrait;

/**
 * @template TResult of object
 *
 * @extends AbstractIterableFinderTestCase<TResult>
 */
abstract class AbstractPaginatableFinderTestCase extends AbstractIterableFinderTestCase
{
    /** @use PaginationTrait<PaginatorInterface<TResult>> */
    use PaginationTrait;
}
