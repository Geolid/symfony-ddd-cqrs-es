<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GatewayTest
{
    #[TestRule]
    public function rawHttpClientStaysInVendorClient(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::AnyOf(
                    Selector::withFilepath('#/src/#', true),
                    Selector::withFilepath('#/apps/#', true),
                ),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
                Selector::Not(Selector::AllOf(
                    Selector::classname('#Client$#', true),
                    Selector::withFilepath('#/Infrastructure/#', true),
                )),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname(HttpClientInterface::class))
            ->because('Reaching a vendor from more than one place scatters its auth, host, and error handling inconsistently.');
    }
}
