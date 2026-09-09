<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Finder\Erasure;

use Compliance\Erasing\Application\Finder\Erasure\Exception\ErasureResultNotFoundException;
use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<ErasureResult>
 */
interface ErasureFinderInterface extends IterableFinderInterface
{
    /**
     * @throws ErasureResultNotFoundException
     */
    public function ofId(string $id): ErasureResult;

    public function requestedBefore(\DateTimeImmutable $cutoff): static;
}
