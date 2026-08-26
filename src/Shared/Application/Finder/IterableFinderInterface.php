<?php

declare(strict_types=1);

namespace Shared\Application\Finder;

/**
 * @template TResult of object
 *
 * @extends \IteratorAggregate<int, TResult>
 */
interface IterableFinderInterface extends \IteratorAggregate, \Countable
{
    /**
     * @return \Iterator<int, TResult>
     */
    public function getIterator(): \Iterator;

    /**
     * @param callable(TResult): string $keyExtractor
     *
     * @return \Traversable<string, TResult>
     */
    public function indexBy(callable $keyExtractor): \Traversable;
}
