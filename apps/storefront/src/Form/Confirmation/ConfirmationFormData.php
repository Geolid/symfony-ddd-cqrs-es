<?php

declare(strict_types=1);

namespace Storefront\Form\Confirmation;

use Iam\Identity\Domain\Exception\InvalidConfirmationCodeException;
use Shared\Application\Validation\ValidVerificationCode;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Storefront\Form\MapsError;

final class ConfirmationFormData
{
    #[ValidVerificationCode]
    #[MapsError(InvalidConfirmationCodeException::class, translationId: 'error_invalid', translationDomain: 'verification_code')]
    #[MapsError(VerificationCodeNotFoundException::class, translationId: 'error_not_found', translationDomain: 'verification_code')]
    #[MapsError(VerificationCodeAttemptsExceededException::class, translationId: 'error_attempts_exceeded', translationDomain: 'verification_code')]
    public ?string $code = null;
}
