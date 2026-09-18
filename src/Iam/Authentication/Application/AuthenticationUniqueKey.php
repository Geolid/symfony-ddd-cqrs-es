<?php

declare(strict_types=1);

namespace Iam\Authentication\Application;

enum AuthenticationUniqueKey: string
{
    case API_KEY_CREDENTIAL_LABEL = 'iam.authentication.api_key_credential.label';
    case TOTP_CREDENTIAL_IDENTITY = 'iam.authentication.totp_credential.identity';
}
