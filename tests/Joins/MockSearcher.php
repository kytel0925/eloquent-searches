<?php

namespace Tests\Joins;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\MockObject\MockObject;
use SlothDevGuy\Searches\Searcher;

/**
 * Trait MockSearcher
 */
trait MockSearcher
{
    protected function mockSearcher(array $options, array $builders = []): Searcher
    {
        /** @var Model $from */
        $from = data_get($options, 'model');
        $table = data_get($options, 'table', $from->getTable());

        $builders = array_merge([
            'get_from' => $this->mockFrom($from),
            'get_from_qualified_field' => $this->mockGetFromQualifiedField($table),
        ], $builders);
        $builders = array_filter($builders);

        $searcher = $this->createMock(Searcher::class);

        foreach ($builders as $builder) {
            $searcher = call_user_func_array($builder, [$searcher]);
        }

        return $searcher;
    }

    protected function mockGetFromQualifiedField(string $table)
    {
        return function (MockObject $searcher) use ($table) {
            $searcher->expects($this->any())
                ->method('getFromQualifiedField')
                ->willReturnCallback(fn ($field) => "{$table}.{$field}");

            return $searcher;
        };
    }

    protected function mockFrom(Model $from)
    {
        return function (MockObject $searcher) use ($from) {
            $searcher->expects($this->atLeastOnce())
                ->method('from')
                ->willReturnCallback(fn () => $from);

            return $searcher;
        };
    }
}
