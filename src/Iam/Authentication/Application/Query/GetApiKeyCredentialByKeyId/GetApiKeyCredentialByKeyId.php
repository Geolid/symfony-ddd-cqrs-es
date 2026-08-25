<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetApiKeyCredentialByKeyId;

use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<ApiKeyCredentialResult>
 */
final readonly class GetApiKeyCredentialByKeyId implements QueryInterface
{
    public function __construct(public string $keyId)
    {
    }
}
