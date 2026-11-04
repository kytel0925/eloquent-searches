<?php

namespace SlothDevGuy\Searches\Where;

use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;
use SlothDevGuy\Searches\WhereBuilder;

class Where extends BaseWhere
{
    protected static array $supportedMethods = ['where', 'orWhere'];

    /**
     * @var array<string, string>
     */
    protected static array $negatedOperators = [
        '=' => '<>',
        '<>' => '=',
        '>' => '<=',
        '>=' => '<',
        '<' => '>=',
        '<=' => '>',
        'like' => 'not like',
        'not like' => 'like',
    ];

    public function redirect(): WhereBuilder
    {
        $arguments = $this->arguments();

        if ($arguments->option('nest')) {
            return new WhereNest($this->searchBuilder());
        }

        if (is_array($arguments->value())) {
            $arguments->method('whereIn');

            return new WhereIn($this->searchBuilder());
        }

        return $this;
    }

    protected function negateIfRequired(): static
    {
        $arguments = $this->arguments();

        if ($arguments->not()) {
            $arguments->operator(static::$negatedOperators[$arguments->operator()] ?? $arguments->operator());
        }

        return $this;
    }

    public function where(): array
    {
        $arguments = $this->arguments();

        $this->negateIfRequired();
        $this->syncMethodWithOperand();

        $field = $this->getQualifiedField($arguments->field());
        $operator = $arguments->operator();
        $value = $arguments->value();

        $this->assertScalar($value);

        if (in_array($operator, ['like', 'not like'], true) && ($wildcard = $arguments->option('wildcard'))) {
            $value = str_replace('_', (string) $value, $wildcard);
        }

        return [
            'method' => $arguments->method(),
            'arguments' => compact('field', 'operator', 'value'),
        ];
    }

    /**
     * @throws InvalidSearchCondition
     */
    protected function assertScalar(mixed $value): void
    {
        if (! is_null($value) && ! is_scalar($value) && ! $value instanceof \Stringable && ! $value instanceof \DateTimeInterface) {
            throw InvalidSearchCondition::invalidValue('a single value is expected');
        }
    }
}
