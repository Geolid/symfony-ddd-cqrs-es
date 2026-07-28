<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class GatewayTest
{
    #[TestRule]
    public function rawHttpClientStaysInSharedGateway(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::AnyOf(
                    Selector::withFilepath('#/src/#', true),
                    Selector::withFilepath('#/apps/#', true),
                ),
                Selector::Not(Selector::withFilepath('#/vendor/#', true)),
                Selector::Not(Selector::withFilepath('#/tests/#', true)),
                Selector::Not(Selector::withFilepath('#/Shared/Infrastructure/Gateway/#', true)),
            ))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname('Symfony\Contracts\HttpClient\HttpClientInterface'))
            ->because('A vendor is reached through its scoped client (host/auth on the service, errors wrapped typed) — a raw HttpClientInterface scatters both.');
    }
}
