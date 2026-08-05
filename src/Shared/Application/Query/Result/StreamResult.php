<?php

declare(strict_types=1);

namespace Shared\Application\Query\Result;

use Shared\Application\Finder\CollectionFinderInterface;
use Shared\Application\Result\ResultInterface;

/**
 * @template TResult of object
 *
 * @implements \IteratorAggregate<int, TResult>
 */
final readonly class StreamResult implements \IteratorAggregate, \Countable, ResultInterface
{
    /**
     * @param CollectionFinderInterface<TResult> $finder
     */
    public function __construct(private CollectionFinderInterface $finder)
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
