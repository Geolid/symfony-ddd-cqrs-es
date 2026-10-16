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
            /*
             * A one-off shift of either bound can only be observed by a test if the draw happens
             * to land on that exact edge value — no test can force that deterministically.
             *
             * @infection-ignore-all
             */
            $code .= random_int(0, 9);
        }

        \assert('' !== $code);

        return $code;
    }
}
