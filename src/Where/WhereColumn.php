<?php

namespace SlothDevGuy\Searches\Where;

use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;
use SlothDevGuy\Searches\WhereBuilder;

/**
 * Compare two columns: ['price|column|>' => 'cost'].
 */
class WhereColumn extends Where
{
    protected static array $supportedMethods = ['whereColumn', 'orWhereColumn'];

    public function redirect(): WhereBuilder
    {
        return $this;
    }

    public function columns(): array
    {
        $value = $this->arguments()->value();

        if (! is_string($value)) {
            throw InvalidSearchCondition::invalidValue('whereColumn expects a column name');
        }

        return [...parent::columns(), $value];
    }

    public function where(): array
    {
        $arguments = $this->arguments();

        $this->negateIfRequired();
        $this->syncMethodWithOperand();

        $field = $this->getQualifiedField($arguments->field());
        $operator = $arguments->operator();
        $value = $this->getQualifiedField($arguments->value());

        return [
            'method' => $arguments->method(),
            'arguments' => compact('field', 'operator', 'value'),
        ];
    }
}
