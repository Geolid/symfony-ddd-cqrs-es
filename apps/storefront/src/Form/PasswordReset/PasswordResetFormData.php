<?php

declare(strict_types=1);

namespace Storefront\Form\PasswordReset;

use Iam\Authentication\Application\Validation\ValidPassword;
use Iam\Authentication\Domain\PasswordCredential\Exception\InvalidPasswordResetCodeException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Shared\Application\Validation\ValidVerificationCode;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Storefront\Form\MapsError;

final class PasswordResetFormData
{
    #[ValidVerificationCode]
    #[MapsError(InvalidPasswordResetCodeException::class, translationId: 'error_invalid', translationDomain: 'verification_code')]
    #[MapsError(VerificationCodeNotFoundException::class, translationId: 'error_not_found', translationDomain: 'verification_code')]
    #[MapsError(VerificationCodeAttemptsExceededException::class, translationId: 'error_attempts_exceeded', translationDomain: 'verification_code')]
    public ?string $code = null;

    #[ValidPassword]
    #[MapsError(SamePasswordException::class, translationId: 'reset_error_same_password', translationDomain: 'forgot_password')]
    public ?string $newPassword = null;
}
