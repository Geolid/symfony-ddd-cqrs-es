<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shared\Infrastructure\Persistence\Projection\Projector\Projector;

final class ProjectorTest
{
    #[TestRule]
    public function extendsTheDbalBase(): Rule
    {
        return PHPat::rule()
            ->classes($this->projectors())
            ->should()->extend()
            ->classes(Selector::classname(AbstractDbalProjector::class))
            ->because('AbstractDbalProjector brings #[Setup]/#[Teardown] and the projector group — outside it the table is never created and store() projects nothing in tests.');
    }

    #[TestRule]
    public function declaresItsSubscription(): Rule
    {
        return PHPat::rule()
            ->classes($this->projectors())
            ->should()->applyAttribute()
            ->classes(Selector::classname(Projector::class))
            ->because('Without #[Projector] patchlevel never subscribes the class — the projection dies silently.');
    }

    #[TestRule]
    public function isReadonly(): Rule
    {
        return PHPat::rule()
            ->classes($this->projectors())
            ->should()->beReadonly()
            ->because('A projector holds no state — a mutable one is a latent concurrency bug.');
    }

    private function projectors(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::withFilepath('#/Infrastructure/Persistence/Projection/Projector/#', true),
            Selector::Not(Selector::withFilepath('#/Shared/#', true)),
            Selector::Not(Selector::withFilepath('#/vendor/#', true)),
            Selector::Not(Selector::withFilepath('#/tests/#', true)),
            Selector::Not(Selector::isAbstract()),
            Selector::Not(Selector::isInterface()),
        );
    }
}
