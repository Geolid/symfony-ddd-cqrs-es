<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\GetApiTokenByIdentifier;

use Iam\Identity\Application\Finder\ApiToken\ApiTokenCredentialResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<ApiTokenCredentialResult>
 */
final readonly class GetApiTokenByIdentifier implements QueryInterface
{
    public function __construct(public string $identifier)
    {
    }
}
