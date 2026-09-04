<?php

declare(strict_types=1);

namespace Finance\Payer\Application\Finder\Payer;

use Finance\Payer\Application\Exception\PayerResultNotFoundException;

interface PayerFinderInterface
{
    /**
     * @throws PayerResultNotFoundException
     */
    public function ofId(string $id): PayerResult;
}
