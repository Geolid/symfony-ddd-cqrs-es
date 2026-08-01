<?php

declare(strict_types=1);

namespace Shared\Application\Query\Result;

use Shared\Application\Finder\FinderInterface;

/**
 * @template TResult of object
 *
 * @implements \IteratorAggregate<int, TResult>
 */
final readonly class StreamResult implements \IteratorAggregate, \Countable, ResultInterface
{
    /**
     * @param FinderInterface<TResult> $finder
     */
    public function __construct(private FinderInterface $finder)
    {
    }

    public function getIterator(): \Traversable
    {
        return $this->finder;
    }

    public function count(): int
    {
        return \count($this->finder);
    }
}
