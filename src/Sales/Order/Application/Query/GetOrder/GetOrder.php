<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\GetOrder;

use Sales\Order\Application\Finder\Order\OrderResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<OrderResult>
 */
final readonly class GetOrder implements QueryInterface
{
    public function __construct(public string $id)
    {
    }
}
