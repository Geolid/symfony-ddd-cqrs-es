<?php

declare(strict_types=1);

namespace Shared\Domain\Service;

interface NumericCodeGeneratorInterface
{
    /**
     * @return non-empty-string
     */
    public function generate(int $digits): string;
}
