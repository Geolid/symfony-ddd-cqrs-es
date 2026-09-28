<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetTotpCredentialByIdentity;

use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<?TotpCredentialResult>
 */
final readonly class GetTotpCredentialByIdentity implements QueryInterface
{
    public function __construct(public string $identityId)
    {
    }
}
