<?php

declare(strict_types=1);

namespace Web\Controller\Criteria;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CustomerCriteria
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Positive]
        public int $itemsPerPage = 10,
    ) {
    }
}
