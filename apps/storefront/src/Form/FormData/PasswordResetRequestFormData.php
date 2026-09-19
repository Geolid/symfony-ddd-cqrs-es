<?php

declare(strict_types=1);

namespace Storefront\Form\FormData;

use Iam\Identity\Application\Validation\ValidEmail;

final class PasswordResetRequestFormData
{
    #[ValidEmail]
    public ?string $email = null;
}
