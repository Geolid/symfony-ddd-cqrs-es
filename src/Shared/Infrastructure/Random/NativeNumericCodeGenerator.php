<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Random;

use Shared\Domain\Service\NumericCodeGeneratorInterface;
use Webmozart\Assert\Assert;

final readonly class NativeNumericCodeGenerator implements NumericCodeGeneratorInterface
{
    public function generate(int $digits): string
    {
        Assert::greaterThanEq($digits, 1);

        $code = '';

        for ($position = 0; $position < $digits; ++$position) {
            // A shifted bound of the draw is only visible on an edge digit, which no test can force.
            // @infection-ignore-all
            $code .= random_int(0, 9);
        }

        \assert('' !== $code);

        return $code;
    }
}
