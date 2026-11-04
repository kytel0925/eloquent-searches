<?php

namespace SlothDevGuy\Searches\Where;

use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;

/**
 * Group conditions inside parentheses: ['group|nest|or' => ['name' => 'foo', 'last_name' => 'bar']].
 */
class WhereNest extends BaseWhere
{
    protected static array $supportedMethods = ['where', 'orWhere'];

    /**
     * @var array<string, string>
     */
    protected static array $negatedMethods = [
        'where' => 'whereNot',
        'orWhere' => 'orWhereNot',
    ];

    protected ?string $negatedMethod = null;

    /**
     * The nested conditions are checked by the nested search.
     */
    public function columns(): array
    {
        return [];
    }

    protected function negateIfRequired(): static
    {
        $arguments = $this->arguments();

        if ($arguments->not()) {
            $this->negatedMethod = static::$negatedMethods[$arguments->method()] ?? null;
        }

        return $this;
    }

    public function where(): array
    {
        $this->syncMethodWithOperand();
        $this->negateIfRequired();

        $method = $this->arguments()->method();
        $values = $this->arguments()->value();

        if (! is_array($values)) {
            throw InvalidSearchCondition::invalidValue('nested conditions must be an array');
        }

        $nestCallback = function ($query) use ($values) {
            $searcher = $this->searchBuilder()->searcher();

            $nested = new $searcher($searcher->from(), $values, $query, [
                'from_alias' => $searcher->option('from_alias'),
                'qualified_fields' => $searcher->option('qualified_fields', true),
            ]);

            $query->setQuery($nested->builder()->getQuery());

            return $query;
        };

        if ($this->negatedMethod) {
            return [
                'method' => $this->negatedMethod,
                'arguments' => [fn ($query) => $query->where($nestCallback)],
            ];
        }

        return [
            'method' => $method,
            'arguments' => [$nestCallback],
        ];
    }
}
