<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Support\Foundry\Story\AbstractAggregateStory;
use Webmozart\Assert\Assert;

/**
 * A Story of this family leaves one `Account` as its `account` state: who it is, and every secret the
 * credentials it persisted were built from.
 */
abstract class AbstractAccountStory extends AbstractAggregateStory
{
    final public static function account(): Account
    {
        Assert::isInstanceOf($account = static::get('account'), Account::class);

        return $account;
    }
}
