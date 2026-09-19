<?php

declare(strict_types=1);

namespace Storefront\Form\FormData;

use Iam\Identity\Application\Validation\ValidConfirmationCode;

final class ConfirmationFormData
{
    #[ValidConfirmationCode]
    public ?string $code = null;
}
