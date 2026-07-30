<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class DomainEventTest
{
    #[TestRule]
    public function isReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::withFilepath('#/Domain/Event/#', true),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::isInterface()),
            ))
            ->should()->beReadonly()
            ->because('A Domain Event is a recorded fact — immutable.');
    }

    /**
     * @return iterable<string, Rule>
     */
    #[TestRule]
    public function staysInsideItsBc(): iterable
    {
        $root = \dirname(__DIR__, 2);
        $eventDirs = [
            ...glob($root.'/src/*/*/Domain/Event', \GLOB_ONLYDIR) ?: [],
            ...glob($root.'/src/*/Domain/Event', \GLOB_ONLYDIR) ?: [],
        ];

        foreach ($eventDirs as $eventDir) {
            $bcPath = \dirname($eventDir, 2);
            $bcName = str_replace('/', '', substr($bcPath, \strlen($root.'/src/')));

            yield $bcName => PHPat::rule()
                ->classes(Selector::AllOf(
                    Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                    Selector::Not(Selector::withFilepath('#/tests/#', true)),
                    Selector::Not(Selector::withFilepath('#'.preg_quote(substr($bcPath, \strlen($root)), '#').'/#', true)),
                ))
                ->shouldNot()
                ->dependOn()
                ->classes(Selector::AllOf(
                    Selector::withFilepath('#'.preg_quote(substr($eventDir, \strlen($root)), '#').'/#', true),
                    Selector::Not(Selector::isInterface()),
                ))
                ->because('A Domain Event is an internal fact of its BC — another BC reacts to the Integration Event.');
        }
    }
}
