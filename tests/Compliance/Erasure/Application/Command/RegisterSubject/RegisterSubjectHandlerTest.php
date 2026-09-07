<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Command\RegisterSubject;

use Compliance\Erasure\Application\Command\RegisterSubject\RegisterSubject;
use Compliance\Erasure\Application\Finder\Subject\SubjectFinderInterface;
use Compliance\Erasure\Application\SubjectStatus;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RegisterSubjectHandlerTest extends AbstractIntegrationTestCase
{
    private SubjectFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(SubjectFinderInterface::class);
    }

    #[Test]
    public function itRegisters(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new RegisterSubject($identityId, Clock::get()->now()));

        // Then
        $id = SubjectId::forIdentity($identityId)->toString();
        $result = $this->finder->ofId($id);
        self::assertSame(SubjectStatus::RETAINED, $result->status);
    }
}
