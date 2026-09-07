<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Policy;

use Compliance\Erasure\Application\IntegrationEvent\SubjectErased\SubjectErasedIntegrationEvent;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Policy\EraseIdentityOnSubjectErased;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class EraseIdentityOnSubjectErasedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $identity = IdentityBuilder::new()->withId($id)->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->trigger(EraseIdentityOnSubjectErased::class, new SubjectErasedIntegrationEvent($id, Clock::get()->now()));
        $this->service(IdentityFinderInterface::class)->ofId($id);
    }
}
