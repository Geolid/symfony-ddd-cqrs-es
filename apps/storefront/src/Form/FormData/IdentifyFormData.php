<?php

declare(strict_types=1);

namespace Storefront\Form\FormData;

use Iam\Identity\Application\Validation\ValidEmail;

final class IdentifyFormData
{
    #[ValidEmail]
    public ?string $email = null;
}
