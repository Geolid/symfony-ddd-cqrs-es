<?php

declare(strict_types=1);

namespace Storefront\Form\ChangePassword;

use Iam\Authentication\Application\Validation\ValidPassword;
use Iam\Authentication\Domain\PasswordCredential\Exception\InvalidCurrentPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Storefront\Form\MapsError;
use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordFormData
{
    #[Assert\NotBlank]
    #[MapsError(InvalidCurrentPasswordException::class, translationId: 'change_error_invalid_current_password', translationDomain: 'account_security')]
    public ?string $currentPassword = null;

    #[ValidPassword]
    #[MapsError(SamePasswordException::class, translationId: 'change_error_same_password', translationDomain: 'account_security')]
    public ?string $newPassword = null;
}
