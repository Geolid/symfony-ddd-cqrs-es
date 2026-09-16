<?php

declare(strict_types=1);

namespace Storefront\Form\FormData;

use Iam\Authentication\Application\Validation\ValidTotpCode;

final class TotpConfirmFormData
{
    #[ValidTotpCode]
    public ?string $code = null;
}
