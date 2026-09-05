<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Finder\Refund;

use Finance\Refund\Application\Finder\Refund\Exception\RefundResultNotFoundException;

interface RefundFinderInterface
{
    /**
     * @throws RefundResultNotFoundException
     */
    public function ofId(string $id): RefundResult;
}
