<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Doctrine\Dbal\Enum;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Doctrine\Dbal\Enum\UnwrapBackedEnumStatementMiddleware;
use Shared\Tests\Support\Double\DummyEnum;

final class UnwrapBackedEnumStatementMiddlewareTest extends TestCase
{
    private Statement&MockObject $wrapped;
    private UnwrapBackedEnumStatementMiddleware $middleware;

    protected function setUp(): void
    {
        $this->wrapped = $this->createMock(Statement::class);
        $this->middleware = new UnwrapBackedEnumStatementMiddleware($this->wrapped);
    }

    #[Test]
    public function itUnwrapsBackedEnum(): void
    {
        // Given
        $this->wrapped->expects(self::once())
            ->method('bindValue')
            ->with(1, 'default', ParameterType::STRING);

        // When
        $this->middleware->bindValue(1, DummyEnum::DEFAULT, ParameterType::STRING);
    }

    #[Test]
    public function itPassesThroughNonEnumValue(): void
    {
        // Given
        $this->wrapped->expects(self::once())
            ->method('bindValue')
            ->with(1, 'plain', ParameterType::STRING);

        // When
        $this->middleware->bindValue(1, 'plain', ParameterType::STRING);
    }
}
