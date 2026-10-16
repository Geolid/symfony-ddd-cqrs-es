<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Random;

use Shared\Domain\Service\NumericCodeGeneratorInterface;
use Webmozart\Assert\Assert;

final readonly class NativeNumericCodeGenerator implements NumericCodeGeneratorInterface
{
    public const int MIN_DIGITS = 1;
    public const int MAX_DIGITS = 18;

    public function generate(int $digits): string
    {
        Assert::range($digits, self::MIN_DIGITS, self::MAX_DIGITS);

        /*
         * A one-off shift of either bound can only be observed by a test if the draw happens
         * to land on that exact edge value — no test can force that deterministically.
         *
         * @infection-ignore-all
         */
        return \sprintf('%0'.$digits.'d', random_int(0, (10 ** $digits) - 1));
    }
}
