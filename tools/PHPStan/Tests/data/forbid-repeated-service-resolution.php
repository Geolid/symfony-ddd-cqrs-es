<?php

declare(strict_types=1);

final class OneResolutionTest
{
    public function test(): void
    {
        $finder = $this->service(SomeFinderInterface::class); // allowed: resolved once in this class
    }
}

final class DifferentClassesTest
{
    public function test(): void
    {
        $finder = $this->service(SomeFinderInterface::class); // allowed: each class resolved once
        $gateway = $this->serviceAs(SomeGatewayInterface::class, SomeGateway::class);
    }
}

final class WebTestCaseTest
{
    public function test(): void
    {
        $client = self::createClient();
        $finder = $this->service(SomeFinderInterface::class); // allowed: createClient() boot-order exception
        $other = $this->service(SomeFinderInterface::class);
    }
}

final class RepeatedResolutionTest
{
    public function first(): void
    {
        $finder = $this->service(SomeFinderInterface::class); // forbidden: resolved twice in this class
    }

    public function second(): void
    {
        $finder = $this->service(SomeFinderInterface::class); // forbidden: resolved twice in this class
    }
}
