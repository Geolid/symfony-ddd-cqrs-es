<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureApproved\ErasureApprovedIntegrationEvent;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Policy\EraseIdentityOnErasureApproved;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class EraseIdentityOnErasureApprovedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->trigger(EraseIdentityOnErasureApproved::class, new ErasureApprovedIntegrationEvent($identity->id->toString(), Clock::get()->now()));
        $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
    }
}
