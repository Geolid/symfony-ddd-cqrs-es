<?php

declare(strict_types=1);

namespace Storefront\Form\RequestEmailChange;

use Iam\Identity\Application\Command\RequestEmailChange\Exception\IdentityEmailAlreadyInUseException;
use Iam\Identity\Application\Validation\ValidEmail;
use Iam\Identity\Domain\Exception\EmailChangeRequestedTooRecentlyException;
use Storefront\Form\MapsError;

final class RequestEmailChangeFormData
{
    #[ValidEmail]
    #[MapsError(IdentityEmailAlreadyInUseException::class, translationId: 'change_email_error_already_in_use', translationDomain: 'account_security')]
    #[MapsError(EmailChangeRequestedTooRecentlyException::class, translationId: 'change_email_error_too_recent', translationDomain: 'account_security')]
    public ?string $newEmail = null;
}
