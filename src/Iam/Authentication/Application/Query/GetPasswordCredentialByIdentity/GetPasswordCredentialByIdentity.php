<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetPasswordCredentialByIdentity;

use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<PasswordCredentialResult>
 */
final readonly class GetPasswordCredentialByIdentity implements QueryInterface
{
    public function __construct(public string $identityId)
    {
    }
}
