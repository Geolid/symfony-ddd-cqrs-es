<?php

declare(strict_types=1);

namespace Web\Controller\QueryString;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CompleteAddressQueryString
{
    public const array ALLOWED_RETURN_ROUTES = ['sales_order_place'];

    public function __construct(
        #[Assert\Choice(choices: self::ALLOWED_RETURN_ROUTES)]
        #[SerializedName('return_to')]
        public string $returnTo = self::ALLOWED_RETURN_ROUTES[0],
    ) {
    }
}
