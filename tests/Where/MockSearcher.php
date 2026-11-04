<?php

namespace Tests\Where;

use PHPUnit\Framework\MockObject\MockObject;
use SlothDevGuy\Searches\Searcher;

/**
 * Trait MockSearcher
 */
trait MockSearcher
{
    protected function mockSearcher(array $builders = []): Searcher
    {
        $builders = array_merge([
            'get_from_qualified_field' => $this->mockGetFromQualifiedField(),
        ], $builders);
        $builders = array_filter($builders);

        $searcher = $builders ? $this->createMock(Searcher::class) : $this->createStub(Searcher::class);

        foreach ($builders as $builder) {
            $searcher = call_user_func_array($builder, [$searcher]);
        }

        return $searcher;
    }

    protected function mockGetFromQualifiedField()
    {
        return function (MockObject $searcher) {
            $searcher->expects($this->atLeastOnce())
                ->method('getFromQualifiedField')
                ->willReturnCallback(fn ($field) => "test.{$field}");

            return $searcher;
        };
    }
}
