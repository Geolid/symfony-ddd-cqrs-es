<?php

declare(strict_types=1);

namespace Tools\PHPat\Selector;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;

final class ConcreteImplementation
{
    /**
     * @return list<SelectorInterface>
     */
    public static function selectors(): array
    {
        return [
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
            Selector::Not(Selector::isAbstract()),
            Selector::Not(Selector::isInterface()),
        ];
    }
}
