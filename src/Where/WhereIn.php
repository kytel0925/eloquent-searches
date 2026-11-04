<?php

namespace SlothDevGuy\Searches\Where;

use SlothDevGuy\Searches\WhereBuilder;

class WhereIn extends BaseWhere
{
    use NegatedWhereMethods;

    protected static array $supportedMethods = ['whereIn', 'whereNotIn', 'orWhereIn', 'orWhereNotIn'];

    /**
     * @var array<string, string>
     */
    protected static array $negatedMethods = [
        'whereIn' => 'whereNotIn',
        'whereNotIn' => 'whereIn',
        'orWhereIn' => 'orWhereNotIn',
        'orWhereNotIn' => 'orWhereIn',
    ];

    public function redirect(): WhereBuilder
    {
        $arguments = $this->arguments();

        // a single value is a plain where
        if (! is_array($arguments->value())) {
            $arguments->method('where');

            return new Where($this->searchBuilder());
        }

        return $this;
    }

    public function where(): array
    {
        $arguments = $this->arguments();

        $this->negateIfRequired();
        $this->syncMethodWithOperand();

        $field = $this->getQualifiedField($arguments->field());
        $value = array_values((array) $arguments->value());

        return [
            'method' => $arguments->method(),
            'arguments' => compact('field', 'value'),
        ];
    }
}
