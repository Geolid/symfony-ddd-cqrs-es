<?php

declare(strict_types=1);

namespace Web\Controller\QueryString;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListQueryString
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Positive]
        public int $itemsPerPage = 10,
    ) {
    }
}
