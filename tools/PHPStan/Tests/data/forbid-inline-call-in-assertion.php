<?php

declare(strict_types=1);

final class ForbidInlineCallInAssertionFixture
{
    public function test(): void
    {
        $result = $this->finder->ofId('x');
        $expected = [];
        $flag = true;
        $code = 200;

        self::assertSame($result->status, $code); // allowed: property fetch and variable, no call
        self::assertTrue($flag); // allowed: bare variable
        self::assertSame(SomeEnum::CASE, $code); // allowed: class const fetch, not a call
        $this->doSomethingElse($this->finder->ofId('x')); // allowed: not an assertion call at all
        self::assertSame($this->ids($expected), $this->ids($result)); // allowed: own private helper, not an I/O boundary
        self::assertSame($code, $this->finder->ofId('x')->status); // forbidden: nested call in an argument
        self::assertCount(2, $this->finder->list()); // forbidden: nested call in an argument
        $this->assertTrue($this->service->isReady()); // forbidden: instance-style assertion call too
        self::assertSame('2026-01-01', $event->createdAt->format('Y-m-d')); // allowed: whitelisted method
        self::assertSame('id-123', $aggregate->id->toString()); // allowed: whitelisted method
    }

    /**
     * @param list<object> $items
     *
     * @return list<string>
     */
    private function ids(array $items): array
    {
        return array_column($items, 'id');
    }
}
