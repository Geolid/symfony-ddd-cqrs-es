<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\ValueObject;

enum ApiTokenCredentialUniqueValue: string
{
    case LABEL = 'iam.identity.api_token_credential.label';
}
