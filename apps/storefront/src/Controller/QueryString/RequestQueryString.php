<?php

declare(strict_types=1);

namespace Storefront\Controller\QueryString;

final class RequestQueryString
{
    public function __construct(public ?string $email = null)
    {
    }
}
