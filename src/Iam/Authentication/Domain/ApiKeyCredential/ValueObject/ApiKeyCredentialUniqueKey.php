<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\ValueObject;

enum ApiKeyCredentialUniqueKey: string
{
    case LABEL = 'iam.authentication.api_key_credential.label';
}
