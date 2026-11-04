<?php

namespace SlothDevGuy\Searches\Where;

use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;

class WhereBetween extends BaseWhere
{
    use NegatedWhereMethods;

    protected static array $supportedMethods = [
        'whereBetween',
        'whereNotBetween',
        'orWhereBetween',
        'orWhereNotBetween',
        'whereBetweenColumns',
        'whereNotBetweenColumns',
        'orWhereBetweenColumns',
        'orWhereNotBetweenColumns',
    ];

    /**
     * @var array<string, string>
     */
    protected static array $negatedMethods = [
        'whereBetween' => 'whereNotBetween',
        'whereNotBetween' => 'whereBetween',
        'whereBetweenColumns' => 'whereNotBetweenColumns',
        'whereNotBetweenColumns' => 'whereBetweenColumns',
        'orWhereBetween' => 'orWhereNotBetween',
        'orWhereNotBetween' => 'orWhereBetween',
        'orWhereBetweenColumns' => 'orWhereNotBetweenColumns',
        'orWhereNotBetweenColumns' => 'orWhereBetweenColumns',
    ];

    public function columns(): array
    {
        $columns = parent::columns();

        if (str_contains($this->arguments()->method(), 'Columns')) {
            $columns = [...$columns, ...array_map('strval', $this->values())];
        }

        return $columns;
    }

    public function where(): array
    {
        $arguments = $this->arguments();

        $this->negateIfRequired();
        $this->syncMethodWithOperand();

        $field = $this->getQualifiedField($arguments->field());
        $values = collect($this->values())->sort()->values()->all();

        return [
            'method' => $arguments->method(),
            'arguments' => compact('field', 'values'),
        ];
    }

    /**
     * @return array{0: mixed, 1: mixed}
     *
     * @throws InvalidSearchCondition
     */
    protected function values(): array
    {
        $value = $this->arguments()->value();

        if (! is_array($value) || count($value) !== 2) {
            throw InvalidSearchCondition::invalidValue('between expects exactly two values');
        }

        return array_values($value);
    }
}
