<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Identity\Domain\Identity;
use Support\Foundry\Story\AbstractAggregateStory;

/**
 * @method static string id()
 * @method static string email()
 * @method static string fullName()
 */
abstract class AbstractAccountStory extends AbstractAggregateStory
{
    final protected function addIdentityStates(Identity $identity): void
    {
        $this->addState('id', $identity->id->toString());
        $this->addState('email', $identity->email->value);
        $this->addState('fullName', $identity->fullName->value);
    }
}
