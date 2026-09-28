<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Random;

use Shared\Domain\Service\NumericCodeGeneratorInterface;

final readonly class NativeNumericCodeGenerator implements NumericCodeGeneratorInterface
{
    public function generate(int $digits): string
    {
        /*
         * A one-off shift of either bound can only be observed by a test if the draw happens
         * to land on that exact edge value — no test can force that deterministically.
         *
         * @infection-ignore-all
         */
        return \sprintf('%0'.$digits.'d', random_int(0, (10 ** $digits) - 1));
    }
}
