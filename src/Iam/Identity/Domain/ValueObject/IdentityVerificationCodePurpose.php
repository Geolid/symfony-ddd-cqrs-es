<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\ValueObject;

enum IdentityVerificationCodePurpose: string
{
    case EMAIL_CONFIRMATION = 'iam.identity.identity.email_confirmation';
    case EMAIL_CHANGE = 'iam.identity.identity.email_change';
}
