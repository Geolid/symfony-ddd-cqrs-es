<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Application\Command\RegisterPayer;

use Finance\Payer\Application\Command\RegisterPayer\RegisterPayer;
use Finance\Payer\Application\Finder\Payer\PayerFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class RegisterPayerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new RegisterPayer($id));

        // Then
        $result = $this->service(PayerFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
    }
}
