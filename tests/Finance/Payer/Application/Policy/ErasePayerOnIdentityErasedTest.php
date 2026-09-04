<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Application\Policy;

use Finance\Payer\Application\Exception\PayerResultNotFoundException;
use Finance\Payer\Application\Finder\Payer\PayerFinderInterface;
use Finance\Payer\Application\Policy\ErasePayerOnIdentityErased;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ErasePayerOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $payer = PayerBuilder::new()->withId($id)->create();
        $this->store($payer);

        // Then
        $this->expectException(PayerResultNotFoundException::class);

        // When
        $this->trigger(ErasePayerOnIdentityErased::class, new IdentityErasedIntegrationEvent($id, Clock::get()->now()));
        $this->service(PayerFinderInterface::class)->ofId($id);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        $this->trigger(ErasePayerOnIdentityErased::class, new IdentityErasedIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
