<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\GetApiTokenCredentialByIdentifier;

use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<ApiTokenCredentialResult>
 */
final readonly class GetApiTokenCredentialByIdentifier implements QueryInterface
{
    public function __construct(public string $identifier)
    {
    }
}
