<?php

declare(strict_types=1);

namespace Web\Exception;

final class MissingCatalogSnapshotException extends \RuntimeException
{
    public function __construct(string $productId)
    {
        parent::__construct(\sprintf('No catalog snapshot found for product "%s".', $productId));
    }
}
