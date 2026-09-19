<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetPasswordCredentialByEmail;

use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<IdentityCredentialResult>
 */
final readonly class GetPasswordCredentialByEmail implements QueryInterface
{
    public function __construct(public string $email)
    {
    }
}
